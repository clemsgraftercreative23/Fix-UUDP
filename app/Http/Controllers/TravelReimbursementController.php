<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Reimbursement;
use App\TravelType;
use App\User;
use App\TravelTripType;
use App\TravelTripRate;
use App\TravelHotelCondition;
use App\ReimbursementAttachment;
use App\ReimbursementDetail;
use App\ReimbursementTravel;
use App\ReimbursementTravelDetail;
use App\Master_project;
use App\Master_kelompok_kegiatan;
use App\Master_daftar_rencana;
use DB;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Redirect;
use App\Support\ActivityLogger;
use App\Support\ExchangeRateParser;
use App\Support\FonnteMessenger;
use App\Support\ReimbursementInquiryNoFilter;
use App\Support\TravelDayTotal;

class TravelReimbursementController extends Controller
{

    private function attachmentTableReady(): bool
    {
        return Schema::hasTable('reimbursement_attachments');
    }

    /**
     * JS often builds URLs as "...start=null&end=null" (string) when Vue date range is unset.
     */
    private function sanitizeTravelPrintQueryDate($value): ?string
    {
        if ($value === null) {
            return null;
        }
        $s = trim((string) $value);
        if ($s === '' || strtolower($s) === 'null') {
            return null;
        }
        return $s;
    }

    /**
     * @param \Illuminate\Support\Collection|\Illuminate\Database\Eloquent\Collection $rows
     */
    private function travelPrintPeriodLabels($request, $rows): array
    {
        $start = $this->sanitizeTravelPrintQueryDate($request->input('start'));
        $end = $this->sanitizeTravelPrintQueryDate($request->input('end'));

        if ($start !== null || $end !== null) {
            return [
                'start_date' => $start ?? '—',
                'end_date' => $end ?? '—',
            ];
        }

        if ($rows->isEmpty()) {
            return ['start_date' => '—', 'end_date' => '—'];
        }

        $dates = $rows->pluck('created_at')->filter();
        if ($dates->isEmpty()) {
            return ['start_date' => '—', 'end_date' => '—'];
        }

        $min = $dates->min();
        $max = $dates->max();
        $fmt = function ($dt) {
            try {
                return \Carbon\Carbon::parse($dt)->format('Y-m-d');
            } catch (\Throwable $e) {
                return '—';
            }
        };

        return [
            'start_date' => $fmt($min),
            'end_date' => $fmt($max),
        ];
    }

    public function __construct() {
        $this->middleware('auth');
    }

    private function normalizeTripTypeId($value)
    {
        if ($value === null || $value === '' || $value === '0') {
            return 0;
        }

        return $value;
    }
    
    private function resolveNotStayHotelConditionId()
    {
        $hotelConditionId = TravelHotelCondition::whereRaw('LOWER(name) = ?', ['not stay'])->value('id');

        return $hotelConditionId ?: 0;
    }

    private function normalizeHotelConditionId($value, $tripTypeId = null)
    {
        if ((int) $this->normalizeTripTypeId($tripTypeId) === 0) {
            return $this->resolveNotStayHotelConditionId();
        }

        if ($value === null || $value === '' || $value === '0') {
            return $this->resolveNotStayHotelConditionId();
        }

        return $value;
    }

    private function normalizeTravelTime($value, $tripTypeId = null)
    {
        if ((int) $this->normalizeTripTypeId($tripTypeId) === 0) {
            return '00:00:00';
        }

        if ($value === null || $value === '') {
            return '00:00:00';
        }

        return $value;
    }

    /**
     * Gunakan travel id dari tab aktif di form bila valid.
     * Ini mencegah update tersimpan ke tab lain saat action URL stale.
     */
    private function resolveActiveTravelId(Request $request, int $idMain, int $fallbackIdTravel): int
    {
        $activeTravelId = (int) $request->input('active_travel_id', 0);
        if ($activeTravelId > 0) {
            $exists = ReimbursementTravel::where('id', $activeTravelId)
                ->where('reimbursement_id', $idMain)
                ->exists();
            if ($exists) {
                return $activeTravelId;
            }
        }

        return $fallbackIdTravel;
    }

    private function canManageTravelTabs(Reimbursement $reimbursement): bool
    {
        $status = (int) $reimbursement->status;
        $jabatan = (string) auth()->user()->jabatan;

        if ($status === 10) {
            return true;
        }

        if ($status === 0 && $jabatan === 'superadmin') {
            return true;
        }

        if ($status === 0 && $jabatan === 'Direktur Operasional') {
            return auth()->user()->isHeadDeptApproverForSubmitter((int) $reimbursement->id_user);
        }

        if ($status === 1 && in_array($jabatan, ['Finance', 'Finance Supervisor', 'HR', 'HR GA', 'superadmin'], true)) {
            return true;
        }

        if ($status === 2 && in_array($jabatan, ['Owner', 'Finance Supervisor', 'superadmin'], true)) {
            return true;
        }

        if ($status === 11 && in_array($jabatan, ['Owner', 'Finance Manager', 'superadmin'], true)) {
            return true;
        }

        if ($status === 3 && in_array($jabatan, ['Owner', 'Finance Manager', 'superadmin'], true)) {
            return true;
        }

        if ($status === 9) {
            $submitterId = (int) $reimbursement->id_user;
            return $submitterId > 0 && $submitterId === (int) auth()->id();
        }

        return false;
    }

    /** Normalisasi input exchange rate ke format desimal DB: 17000.50 (2 desimal). Mendukung 17.883 (=17883) / 139,88 / 16400. */
    private function normalizeExchangeRateValue($value): string
    {
        return ExchangeRateParser::normalizeForStorage($value);
    }

    /**
     * Nilai nominal dari maskMoney / format ID (17.000,50) — logika parse sama dengan kurs.
     */
    private function normalizeTravelMoneyValue($value): float
    {
        return (float) $this->normalizeExchangeRateValue($value);
    }

    /**
     * Nilai kolom amount detail travel: bilangan bulat (desimal dari input diabaikan).
     */
    private function normalizeTravelAmountInteger($value): int
    {
        return (int) floor($this->normalizeTravelMoneyValue($value));
    }

    /** Overseas wajib punya IDR (1) + USD; domestic wajib IDR. */
    private function ensureDefaultTripRates(int $reimbursementId, string $travelType): void
    {
        TravelTripRate::updateOrCreate(
            ['reimbursement_id' => $reimbursementId, 'currency' => 'IDR'],
            ['rate' => 1.00]
        );

        if ($travelType === 'Domestic') {
            return;
        }

        $hasUsd = TravelTripRate::where('reimbursement_id', $reimbursementId)
            ->where('currency', 'USD')
            ->exists();

        if ($hasUsd) {
            return;
        }

        $fallbackRate = 0.0;
        $reimbursement = Reimbursement::find($reimbursementId);
        if ($reimbursement && (float) $reimbursement->usd_rate > 0) {
            $fallbackRate = (float) $reimbursement->usd_rate;
        }

        TravelTripRate::create([
            'reimbursement_id' => $reimbursementId,
            'currency' => 'USD',
            'rate' => $fallbackRate,
        ]);
    }

    private function isCorruptTravelMoney($value): bool
    {
        if ($value === null || $value === '') {
            return false;
        }

        return (float) $value >= 2147483647.0;
    }

    /** Hitung allowance IDR dari master trip type × kurs reimbursement. */
    private function computeAllowanceInIdr(int $tripTypeId, int $reimbursementId): ?float
    {
        if ($tripTypeId <= 0) {
            return null;
        }

        $trip = TravelTripType::find($tripTypeId);
        if (!$trip) {
            return null;
        }

        $currency = strtoupper(trim((string) ($trip->currency ?? 'IDR')));
        $base = (float) $trip->allowance;

        if ($currency === 'IDR' || $currency === '') {
            return $base;
        }

        $rate = (float) TravelTripRate::where('reimbursement_id', $reimbursementId)
            ->where('currency', $currency)
            ->value('rate');

        if ($rate <= 0) {
            return null;
        }

        return $base * $rate;
    }

    /** Total per hari = allowance IDR + jumlah detail IDR (Cash/BDC). */
    private function recomputeTravelDayTotal(int $travelId): float
    {
        $row = ReimbursementTravel::find($travelId);
        if (!$row) {
            return 0.0;
        }

        $allowance = (float) $row->allowance;
        $detailsSum = (float) ReimbursementTravelDetail::where('reimbursement_travel_id', $travelId)
            ->where('status', 1)
            ->sum('idr_rate');

        return $allowance + $detailsSum;
    }

    private function syncTravelDayTotalAfterDetailsSave(int $travelId): void
    {
        ReimbursementTravel::whereKey($travelId)->update([
            'total' => $this->recomputeTravelDayTotal($travelId),
        ]);
    }

    /** Perbaiki kurs USD/JPY yang tersimpan sebagai desimal (17,88) padahal allowance sudah benar. */
    private function repairTripRatesFromStoredAllowances(int $reimbursementId): void
    {
        $travels = ReimbursementTravel::where('reimbursement_id', $reimbursementId)->get();

        foreach ($travels as $travel) {
            $tripTypeId = (int) ($travel->trip_type_id ?? 0);
            $storedAllowance = (float) ($travel->allowance ?? 0);

            if ($tripTypeId <= 0 || $storedAllowance < 1000) {
                continue;
            }

            $trip = TravelTripType::find($tripTypeId);
            if (!$trip) {
                continue;
            }

            $currency = strtoupper(trim((string) ($trip->currency ?? 'IDR')));
            $baseAllowance = (float) ($trip->allowance ?? 0);

            if ($currency === 'IDR' || $currency === '' || $baseAllowance <= 0) {
                continue;
            }

            $impliedRate = $storedAllowance / $baseAllowance;
            if ($impliedRate < 100) {
                continue;
            }

            $rateRow = TravelTripRate::where('reimbursement_id', $reimbursementId)
                ->where('currency', $currency)
                ->first();

            if (!$rateRow) {
                continue;
            }

            $currentRate = (float) $rateRow->rate;
            if ($currentRate >= 1000) {
                continue;
            }

            if ($impliedRate / max($currentRate, 0.01) >= 50) {
                TravelTripRate::whereKey($rateRow->id)->update([
                    'rate' => ExchangeRateParser::normalizeForStorage((string) $impliedRate),
                ]);
            }
        }
    }

    private function recomputeAllTravelDayTotalsForReimbursement(int $reimbursementId): float
    {
        $travelIds = ReimbursementTravel::where('reimbursement_id', $reimbursementId)->pluck('id');

        foreach ($travelIds as $travelId) {
            $this->syncTravelDayTotalAfterDetailsSave((int) $travelId);
        }

        $nominal = (float) ReimbursementTravel::where('reimbursement_id', $reimbursementId)->sum('total');

        Reimbursement::whereKey($reimbursementId)->update(['nominal_pengajuan' => $nominal]);

        return $nominal;
    }

    /** Perbaiki allowance INT32 overflow di DB & objek yang dikirim ke view. */
    private function repairCorruptTravelAllowance(object $travelRow, int $reimbursementId): void
    {
        if (!$this->isCorruptTravelMoney($travelRow->allowance ?? null)) {
            return;
        }

        $tripTypeId = (int) ($travelRow->trip_type_id ?? 0);
        $computed = $this->computeAllowanceInIdr($tripTypeId, $reimbursementId);
        if ($computed === null) {
            return;
        }

        $travelRow->allowance = $computed;
        ReimbursementTravel::where('id', (int) $travelRow->id)->update(['allowance' => $computed]);
    }

    private function resolveTravelAllowanceForSave(Request $request, int $reimbursementId): float
    {
        $tripTypeId = (int) $this->normalizeTripTypeId($request->trip_type_id);
        $computed = $this->computeAllowanceInIdr($tripTypeId, $reimbursementId);

        if ($computed !== null) {
            return $computed;
        }

        return $this->normalizeTravelMoneyValue($request->allowance ?? '');
    }

    /** Sinkronkan kurs dari form utama (currency_rate[] + rate[]) ke travel_trip_rates. */
    private function syncTripRatesFromMainForm(Request $request, int $reimbursementId): void
    {
        $currencies = $request->input('currency_rate', []);
        $rates = $request->input('rate', []);

        if (!is_array($currencies) || !is_array($rates)) {
            return;
        }

        $count = max(count($currencies), count($rates));
        $hasPayload = false;

        for ($i = 0; $i < $count; $i++) {
            $currency = strtoupper(trim((string) ($currencies[$i] ?? '')));
            $rate = $currency === 'IDR'
                ? '1.00'
                : $this->normalizeExchangeRateValue($rates[$i] ?? '');

            if ($currency === '') {
                continue;
            }

            $hasPayload = true;
            TravelTripRate::updateOrCreate(
                ['reimbursement_id' => $reimbursementId, 'currency' => $currency],
                ['rate' => $rate]
            );
        }

        if (!$hasPayload) {
            return;
        }

        $travelType = Reimbursement::where('id', $reimbursementId)->value('travel_type');
        $this->ensureDefaultTripRates($reimbursementId, (string) ($travelType ?? 'Domestic'));
    }

    /** Simpan file bukti ke public/images/file_bukti dan kembalikan nama file. */
    private function storeTravelEvidenceFile($file): string
    {
        if (!$file instanceof UploadedFile) {
            return '';
        }

        // Menghindari exception "not uploaded due to an unknown error"
        // saat object upload sudah invalid/terproses ulang.
        if (!$file->isValid()) {
            return '';
        }

        $targetDir = public_path('images/file_bukti');
        if (!is_dir($targetDir)) {
            @mkdir($targetDir, 0755, true);
        }

        $ext = strtolower((string) $file->getClientOriginalExtension());
        if ($ext === '') {
            $ext = 'jpg';
        }

        $filename = uniqid('bukti_', true) . '.' . $ext;
        $file->move($targetDir, $filename);

        return $filename;
    }

    private function buildAttachmentPayload(int $reimbursementId, string $module, string $detailType, int $detailId, string $storedFile, ?UploadedFile $uploadedFile = null): array
    {
        $originalName = $storedFile;
        $mimeType = null;
        $fileSize = 0;

        if ($uploadedFile) {
            try {
                $candidateName = (string) $uploadedFile->getClientOriginalName();
                if ($candidateName !== '') {
                    $originalName = $candidateName;
                }
            } catch (\Throwable $e) {
                // ignore, fallback to stored filename
            }

            try {
                $mimeType = $uploadedFile->getClientMimeType();
            } catch (\Throwable $e) {
                $mimeType = null;
            }

            try {
                $sizeCandidate = $uploadedFile->getSize();
                $fileSize = is_numeric($sizeCandidate) ? (int) $sizeCandidate : 0;
            } catch (\Throwable $e) {
                $fileSize = 0;
            }

            if ($fileSize <= 0) {
                try {
                    $realPath = $uploadedFile->getRealPath();
                    if ($realPath && is_file($realPath)) {
                        $fileSize = (int) (filesize($realPath) ?: 0);
                    }
                } catch (\Throwable $e) {
                    $fileSize = 0;
                }
            }
        }

        return [
            'reimbursement_id' => $reimbursementId,
            'module' => $module,
            'detail_type' => $detailType,
            'detail_id' => $detailId,
            'file_name' => $storedFile,
            'original_name' => $originalName,
            'mime_type' => $mimeType,
            'file_size' => $fileSize,
            'created_by' => auth()->id(),
        ];
    }

    private function appendUploadedAttachments(int $reimbursementId, string $module, string $detailType, int $detailId, array $uploadedFiles): array
    {
        $names = [];
        if (!$this->attachmentTableReady()) {
            return $names;
        }
        foreach ($uploadedFiles as $file) {
            if (!$file instanceof UploadedFile) {
                continue;
            }
            $stored = $this->storeTravelEvidenceFile($file);
            if ($stored === '') {
                continue;
            }
            ReimbursementAttachment::create(
                $this->buildAttachmentPayload($reimbursementId, $module, $detailType, $detailId, $stored, $file)
            );
            $names[] = $stored;
        }

        return $names;
    }

    private function getUploadedFilesByRow(Request $request, int $rowIndex): array
    {
        $files = [];
        $seen = [];
        $appendFile = static function ($candidate) use (&$files, &$seen): void {
            if (!$candidate instanceof UploadedFile) {
                return;
            }
            // Bisa terjadi object file yang sama masuk dari beberapa input.
            // Jika diproses dua kali, move() kedua dapat gagal.
            $objectId = spl_object_id($candidate);
            if (isset($seen[$objectId])) {
                return;
            }
            $seen[$objectId] = true;
            $files[] = $candidate;
        };

        $legacyFile = data_get($request->file('file'), $rowIndex);
        $appendFile($legacyFile);

        $legacyProof = data_get($request->file('proof'), $rowIndex);
        $appendFile($legacyProof);

        $batch = data_get($request->file('attachments'), $rowIndex);
        if ($batch instanceof UploadedFile) {
            $appendFile($batch);
        } elseif (is_array($batch)) {
            foreach ($batch as $f) {
                $appendFile($f);
            }
        }

        return $files;
    }

    private function ensureLegacyAttachmentMigrated(int $reimbursementId, string $module, string $detailType, int $detailId, string $legacyFile): void
    {
        if (!$this->attachmentTableReady()) {
            return;
        }
        $legacyFile = trim($legacyFile);
        if ($legacyFile === '' || $detailId <= 0) {
            return;
        }

        $exists = ReimbursementAttachment::where('detail_type', $detailType)
            ->where('detail_id', $detailId)
            ->where('file_name', $legacyFile)
            ->exists();

        if ($exists) {
            return;
        }

        ReimbursementAttachment::create(
            $this->buildAttachmentPayload($reimbursementId, $module, $detailType, $detailId, $legacyFile)
        );
    }

    private function syncAttachmentsFromPreviousDetail(Request $request, int $rowIndex, int $reimbursementId, string $module, string $detailType, int $oldDetailId, int $newDetailId, string $legacyEvidence = ''): array
    {
        if (!$this->attachmentTableReady()) {
            $uploaded = $this->getUploadedFilesByRow($request, $rowIndex);
            if (!empty($uploaded)) {
                $first = $this->storeTravelEvidenceFile($uploaded[0]);
                return $first === '' ? [] : [$first];
            }
            $legacyEvidence = trim((string) $legacyEvidence);
            return $legacyEvidence === '' ? [] : [$legacyEvidence];
        }

        $keptFileNames = [];

        if ($oldDetailId > 0) {
            $this->ensureLegacyAttachmentMigrated($reimbursementId, $module, $detailType, $oldDetailId, $legacyEvidence);

            $oldAttachments = ReimbursementAttachment::where('detail_type', $detailType)
                ->where('detail_id', $oldDetailId)
                ->orderBy('id')
                ->get();

            $hasKeepField = $request->has('keep_attachment_ids.' . $rowIndex);
            $keepIds = $hasKeepField
                ? collect((array) data_get($request->input('keep_attachment_ids', []), $rowIndex, []))
                    ->map(function ($v) { return (int) $v; })
                    ->filter(function ($v) { return $v > 0; })
                    ->values()
                : null;

            foreach ($oldAttachments as $attachment) {
                if ($keepIds !== null && !$keepIds->contains((int) $attachment->id)) {
                    continue;
                }

                ReimbursementAttachment::create([
                    'reimbursement_id' => $reimbursementId,
                    'module' => $module,
                    'detail_type' => $detailType,
                    'detail_id' => $newDetailId,
                    'file_name' => $attachment->file_name,
                    'original_name' => $attachment->original_name,
                    'mime_type' => $attachment->mime_type,
                    'file_size' => (int) $attachment->file_size,
                    'created_by' => auth()->id(),
                ]);
                $keptFileNames[] = (string) $attachment->file_name;
            }
        }

        $newFiles = $this->appendUploadedAttachments(
            $reimbursementId,
            $module,
            $detailType,
            $newDetailId,
            $this->getUploadedFilesByRow($request, $rowIndex)
        );

        return array_values(array_filter(array_merge($keptFileNames, $newFiles)));
    }

    /**
     * @return array{save: bool, save_draft: bool, save_item: bool, save_again: bool}
     */
    private function parseTravelFormActions(): array
    {
        $postKeys = is_array($_POST ?? null) ? array_keys($_POST) : [];

        return [
            'save' => in_array('save', $postKeys, true),
            'save_draft' => in_array('save_draft', $postKeys, true),
            'save_item' => in_array('save_item', $postKeys, true),
            'save_again' => in_array('save_again', $postKeys, true),
        ];
    }

    /**
     * Tanpa tombol aksi eksplisit, jangan anggap submit pada reject/draft.
     *
     * @param array{save: bool, save_draft: bool, save_item: bool, save_again: bool} $actions
     */
    private function applyTravelFormActionFallback(int $currentStatus, array $actions): array
    {
        if ($actions['save'] || $actions['save_draft'] || $actions['save_item'] || $actions['save_again']) {
            return $actions;
        }

        if ($currentStatus === 9 || $currentStatus === 10) {
            $actions['save_draft'] = true;
        } else {
            $actions['save'] = true;
        }

        return $actions;
    }

    /** Draft / tambah tab baru: boleh data belum lengkap. */
    private function travelItemAllowIncompleteForm(): bool
    {
        $isDraftOrNewTab = isset($_POST['save_draft']) || isset($_POST['save_item']);
        if ($isDraftOrNewTab) {
            return true;
        }

        // Improvement UX: di halaman update item, baris cost type pertama bisa dihapus.
        // Maka aksi "UPDATE" (name=save) tidak boleh gagal hanya karena tidak ada cost type terisi.
        $path = request()->path();
        $isUpdateItemPath =
            strpos($path, 'reimbursement-travel/update-item/') === 0 ||
            strpos($path, 'reimbursement-travel/update-item-reject/') === 0;
        $isPlainUpdateSave =
            isset($_POST['save']) &&
            !isset($_POST['save_again']) &&
            !isset($_POST['save_owner']) &&
            !isset($_POST['save_finance']) &&
            !isset($_POST['edit_owner']) &&
            !isset($_POST['edit_finance']);

        return $isUpdateItemPath && $isPlainUpdateSave;
    }

    /**
     * @return array{status: int, notif: string, sendSubmissionNotifications: bool}
     */
    private function resolveTravelItemSaveStatus(int $currentStatus, array $actions): array
    {
        $isSave = (bool) ($actions['save'] ?? false);
        $isSaveDraft = (bool) ($actions['save_draft'] ?? false);
        $isSaveItem = (bool) ($actions['save_item'] ?? false);
        $isSaveAgain = (bool) ($actions['save_again'] ?? false);
        $keepProgressStatus = ($currentStatus > 0 && $currentStatus !== 9 && $currentStatus !== 10);

        if ($isSaveAgain) {
            return [
                'status' => 0,
                'notif' => 'Reimbursement Successfully Submitted Again',
                'sendSubmissionNotifications' => true,
            ];
        }

        if ($isSaveDraft) {
            return [
                'status' => 10,
                'notif' => 'Reimbursement Successfully Saved as Draft',
                'sendSubmissionNotifications' => false,
            ];
        }

        if ($isSaveItem) {
            return [
                'status' => $currentStatus === 9 ? 9 : ($keepProgressStatus ? $currentStatus : 10),
                'notif' => 'redirect',
                'sendSubmissionNotifications' => false,
            ];
        }

        if ($isSave) {
            if ($currentStatus === 9) {
                return [
                    'status' => 10,
                    'notif' => 'Reimbursement Successfully Saved as Draft',
                    'sendSubmissionNotifications' => false,
                ];
            }

            if ($currentStatus === 10) {
                return [
                    'status' => 0,
                    'notif' => 'Reimbursement Successfully Submitted',
                    'sendSubmissionNotifications' => true,
                ];
            }

            if ($currentStatus === 0 || $keepProgressStatus) {
                return [
                    'status' => $currentStatus,
                    'notif' => 'Reimbursement Successfully Updated',
                    'sendSubmissionNotifications' => false,
                ];
            }

            return [
                'status' => 0,
                'notif' => 'Reimbursement Successfully Submitted',
                'sendSubmissionNotifications' => true,
            ];
        }

        return [
            'status' => $currentStatus,
            'notif' => 'Reimbursement Successfully Updated',
            'sendSubmissionNotifications' => false,
        ];
    }

    /** Validasi form item travel (simpan/update); cegah field kosong yang memicu error DB. */
    private function validateTravelReimbursementItemRequest(Request $request, bool $allowIncomplete): void
    {
        $base = [
            'travel_type' => 'nullable|string|max:32',
            'remark' => 'nullable|string|max:500',
        ];

        if ($allowIncomplete) {
            $rules = array_merge($base, [
                'date' => 'nullable|date',
                'purpose' => 'nullable|string|max:500',
                'reimbursement_department_id' => 'nullable|integer',
                'currency' => 'nullable|array',
                'cost_type_id' => 'nullable|array',
                'nominal_pengajuan' => 'nullable|string|max:80',
                'allowance' => 'nullable|string|max:80',
                'trip_type_id' => 'nullable',
                'hotel_condition_id' => 'nullable',
                'start_time' => 'nullable|string|max:32',
                'end_time' => 'nullable|string|max:32',
            ]);
        } else {
            $rules = array_merge($base, [
                'date' => 'required|date',
                'purpose' => 'required|string|max:500',
                'reimbursement_department_id' => 'required|integer',
                'currency' => 'required|array|min:1',
                'cost_type_id' => 'required|array',
                // Readonly / dihitung JS — boleh kosong sesaat, dicek di baris detail
                'nominal_pengajuan' => 'nullable|string|max:80',
                'allowance' => 'nullable|string|max:80',
                'trip_type_id' => 'nullable',
                'hotel_condition_id' => 'nullable',
                'start_time' => 'nullable|string|max:32',
                'end_time' => 'nullable|string|max:32',
            ]);
        }

        $messages = [
            'date.required' => 'Tanggal transaksi wajib diisi.',
            'purpose.required' => 'Purpose wajib diisi.',
            'reimbursement_department_id.required' => 'Department wajib dipilih.',
            'currency.required' => 'Rincian biaya (kolom currency) wajib ada.',
            'currency.min' => 'Minimal satu baris rincian biaya.',
            'cost_type_id.required' => 'Rincian biaya wajib diisi.',
        ];

        $validator = Validator::make($request->all(), $rules, $messages);

        if (!$allowIncomplete) {
            $validator->after(function ($v) use ($request) {
                $costTypes = (array) $request->input('cost_type_id', []);
                $hasDetail = false;
                foreach ($costTypes as $idx => $ct) {
                    if (trim((string) $ct) === '') {
                        continue;
                    }
                    $hasDetail = true;
                    $labels = [
                        'destination' => 'Remarks / tujuan biaya',
                        'currency' => 'Mata uang',
                        'payment_type' => 'Tipe pembayaran',
                        'idr_rate' => 'IDR rate',
                        'amount' => 'Jumlah',
                    ];
                    foreach ($labels as $field => $label) {
                        $arr = (array) $request->input($field, []);
                        if (!array_key_exists($idx, $arr) || trim((string) $arr[$idx]) === '') {
                            $v->errors()->add(
                                $field . '.' . $idx,
                                $label . ' pada baris ' . ($idx + 1) . ' wajib diisi.'
                            );
                        }
                    }
                }
                if (!$hasDetail) {
                    $v->errors()->add('cost_type_id', 'Minimal satu baris rincian biaya (pilih cost type) harus diisi lengkap.');
                }
            });
        }

        $validator->validate();
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        
        $id_user = auth()->user()->id;
        
        
        if(request()->ajax())
        {
            if(auth()->user()->jabatan=='superadmin') {
                $data = Reimbursement::leftJoin('master_project','reimbursement.id_project','master_project.id')
                        ->select('reimbursement.*','master_project.nama','master_project.no_project','master_project.keterangan')
                        ->where('reimbursement.reimbursement_type',2);
            } else {
                $status = $request->status;
                if($status==null) {
                    $data = Reimbursement::leftJoin('master_project','reimbursement.id_project','master_project.id')
                        ->leftJoin('users','users.id','reimbursement.id_user')
                        ->select('reimbursement.*','master_project.nama','master_project.no_project','master_project.keterangan')
                        ->where('reimbursement.reimbursement_type',2)->where('reimbursement.id_user', $id_user);    
                } else {
                    $data = Reimbursement::leftJoin('master_project','reimbursement.id_project','master_project.id')
                        ->leftJoin('users','users.id','reimbursement.id_user')
                        ->select('reimbursement.*','master_project.nama','master_project.no_project','master_project.keterangan')
                        ->where('reimbursement.reimbursement_type',2)->where('reimbursement.status',$request->status)->where('reimbursement.id_user', $id_user);    
                }
                
            }

            if(isset($request->first) && $request->first != "") {
                $data = $data->whereDate('reimbursement.created_at','>=',$request->first);
            }

            if(isset($request->last) && $request->last != "") {
                $data = $data->whereDate('reimbursement.created_at','<=',$request->last);
            }
            
             if(isset($request->status) && $request->status != "" && $request->status != "ALL") {
                $data = $data->where('reimbursement.status',$request->status);
            }

            if(isset($request->driver) && $request->driver != "") {
                $data = $data->where('reimbursement.id_user','=',$request->driver);
            }
            
            if(auth()->user()->jabatan == 'karyawan') {
                $data = $data->where('reimbursement.id_user', auth()->user()->id);
            }

            $data = ReimbursementInquiryNoFilter::apply($data, $request);

            $data = $data->orderBy('reimbursement.no_reimbursement', 'DESC');
            
            return datatables()->of($data)  
            ->addColumn('status_label', function ($data) {
                if($data->status == 0 ){
                $button = '<button" class="edit view btn btn-secondary  btn-sm">PENDING</button>';
                }elseif ($data->status == 1) {
                $button = '<button  class="view btn btn-success btn-sm">APPROVED HEAD DEPT</button>';
                } elseif ($data->status == 2) {
                $button = '<button   class="view btn btn-success btn-sm">APPROVED HR GA</button>';
                } elseif ($data->status == 11) {
                $button = '<button class="view btn btn-success btn-sm">APPROVED FINANCE SUPERVISOR</button>';
                } elseif ($data->status == 3) {
                $button = '<button  class=" view btn btn-success btn-sm">APPROVED FINANCE MANAGER / PROCESS SETTLEMENT</button>';
                } 
                elseif ($data->status == 4){
                    $button = '<button  class="view btn btn-danger btn-sm">REJECTED</button>';
                }
                elseif ($data->status == 5){
                    $button = '<button  class="view btn btn-success btn-sm">SETTLED</button>';
                }
                elseif ($data->status == 9){
                    if($data->mengetahui_op=='-') {
                        $meng = 'HEAD DEPT';
                    } else if($data->mengetahui_finance=='-') {
                        $meng = 'HR GA';
                    } else if($data->mengetahui_owner=='-') {
                        $meng = 'FINANCE';
                    } 
                    $button = '<button  class="view btn btn-danger btn-sm">REJECTED</button>';
                } elseif ($data->status == 10){
                    $button = '<button  class="view btn btn-warning btn-sm">DRAFT</button>';
                } else {
                  $button = '';
                }

                return $button;

            })
            ->addColumn('action', function ($data) {
                $buttons = '<div style="display:flex; gap:4px; align-items:center;">';

                // Show button (always visible)
                $buttons .= '<a href="' . route('reimbursement-travel.show', $data->id) . '" class="btn btn-info btn-sm" title="Detail" aria-label="Detail"><i class="fa fa-eye"></i></a>';

                // Edit button (always visible)
                $buttons .= '<a href="' . route('reimbursement-travel.edit', $data->id) . '" class="btn btn-primary btn-sm" title="Edit" aria-label="Edit"><i class="fa fa-edit"></i></a>';

                // Delete button (only for status 0 or 10)
                if (in_array((int) $data->status, [0, 10], true)) {
                    $buttons .= '<form method="POST" action="' . route('reimbursement-travel.destroy', $data->id) . '" style="display:inline-block; margin:0;" onsubmit="return confirm(\'Yakin ingin menghapus pengajuan ini?\')">'
                        . csrf_field()
                        . method_field('DELETE')
                        . '<button type="submit" class="btn btn-danger btn-sm" title="Delete" aria-label="Delete"><i class="fa fa-trash"></i></button></form>';
                } else {
                    $buttons .= '<span>-</span>';
                }

                $buttons .= '</div>';
              
                return $buttons;

            })
            ->addColumn('checkbox', function ($data) {
                    
                    $cek = '<div class="form-check"><input class="form-check-input check-print" type="checkbox" value="'.$data->id.'"></div>';
                    return $cek;
            })
            ->editColumn('no_project', function ($data) {
               
                return $data->user->name;
            })
            ->addColumn('nominal_pengajuan', function ($data) {
                $button ='';
                $button .= number_format($data->nominal_pengajuan,0, ',' , '.');
                return $button;
            })
            ->editColumn('no_reimbursement', function ($data) {
                return $data->no_reimbursement;
            })
            ->rawColumns(['status_label', 'action', 'checkbox', 'nominal_pengajuan'])
            ->make(true);
        }
        
        $check_approval  = DB::select( DB::raw("SELECT count(id) AS id FROM users WHERE id_approval = '$id_user'"))['0']->id;

        return view('reimbursement-travel.index',[
            'check_approval' => $check_approval,
            'project' => Master_project::get(),
            'kelompok' => Master_kelompok_kegiatan::get(),
            'daftar' => Master_daftar_rencana::get(),
            'driver' => User::whereIn('id',Reimbursement::select('id_user')->get()->pluck('id_user'))->get()
        ]);
    }
    
    
    public function approval(Request $request)
    {
        if(request()->ajax())
        {
            $id_user = auth()->user()->id;           
            
            if(auth()->user()->jabatan=='Finance' || auth()->user()->jabatan=='HR' || auth()->user()->jabatan=='HR GA' || auth()->user()->jabatan=='Finance Supervisor' || auth()->user()->jabatan=='Finance Manager' || auth()->user()->jabatan=='Owner' || auth()->user()->jabatan=='superadmin') {
                $data = Reimbursement::leftJoin('master_project','reimbursement.id_project','master_project.id')
                        ->select('reimbursement.*','master_project.nama','master_project.no_project','master_project.keterangan')
                        ->where('reimbursement.reimbursement_type',2)->where('reimbursement.status', '!=',10);
            } else {
                $status = $request->status;
                if($status==null) {
                    $data = Reimbursement::leftJoin('master_project','reimbursement.id_project','master_project.id')
                        ->leftJoin('users','users.id','reimbursement.id_user')
                        ->select('reimbursement.*','master_project.nama','master_project.no_project','master_project.keterangan')
                        ->where('reimbursement.reimbursement_type',2)->where('reimbursement.status', '!=',10)->where('users.id_approval', $id_user);    
                } else {
                    $data = Reimbursement::leftJoin('master_project','reimbursement.id_project','master_project.id')
                        ->leftJoin('users','users.id','reimbursement.id_user')
                        ->select('reimbursement.*','master_project.nama','master_project.no_project','master_project.keterangan')
                        ->where('reimbursement.reimbursement_type',2)->where('reimbursement.status', '!=',10)->where('reimbursement.status',$request->status)->where('users.id_approval', $id_user);    
                }
                
            }
            
            if(isset($request->first) && $request->first != "") {
                $data = $data->whereDate('reimbursement.created_at','>=',$request->first);
            }

            if(isset($request->last) && $request->last != "") {
                $data = $data->whereDate('reimbursement.created_at','<=',$request->last);
            }
            
             if(isset($request->status) && $request->status != "" && $request->status != "ALL") {
                $data = $data->where('reimbursement.status',$request->status);
            }

            if(isset($request->user_id) && $request->user_id != "") {
                $data = $data->where('reimbursement.id_user','=',$request->user_id);
            }
            
            if(auth()->user()->jabatan == 'karyawan') {
                $data = $data->where('reimbursement.id_user', auth()->user()->id);
            }

            $data = ReimbursementInquiryNoFilter::apply($data, $request);

            

            $data = $data->orderBy('reimbursement.no_reimbursement', 'DESC');
            return datatables()->of($data)
            ->addColumn('action', function ($data) {
                if($data->status == 0 ){
                $button = '<button" class="edit view btn btn-secondary  btn-sm">PENDING</button>';
                } elseif ($data->status == 1) {
                $button = '<button  class="view btn btn-success btn-sm">APPROVED HEAD DEPT</button>';
                } elseif ($data->status == 2) {
                $button = '<button   class="view btn btn-success btn-sm">APPROVED HR GA</button>';
                } elseif ($data->status == 11) {
                $button = '<button class="view btn btn-success btn-sm">APPROVED FINANCE SUPERVISOR</button>';
                } elseif ($data->status == 3) {
                $button = '<button  class=" view btn btn-success btn-sm">APPROVED FINANCE MANAGER / PROCESS SETTLEMENT</button>';
                } elseif ($data->status == 4){
                    $button = '<button  class="view btn btn-danger btn-sm">REJECTED</button>';
                } elseif ($data->status == 5){
                    $button = '<button  class="view btn btn-success btn-sm">SETTLED</button>';
                } elseif ($data->status == 9){
                    if($data->mengetahui_op=='-') {
                        $meng = 'HEAD DEPT';
                    } else if($data->mengetahui_finance=='-') {
                        $meng = 'HR GA';
                    } else if($data->mengetahui_owner=='-') {
                        $meng = 'FINANCE';
                    }
                    $button = '<button  class="view btn btn-danger btn-sm">REJECTED '.$meng.'</button>';
                } else {
                  $button = '';
                }
                $button .= '&nbsp;&nbsp;';

                return $button;

            })
            ->addColumn('checkbox', function ($data) {
                    
                    $cek = '<div class="form-check"><input class="form-check-input check-print" type="checkbox" value="'.$data->id.'"></div>';
                    return $cek;
            })
            ->editColumn('no_project', function ($data) {
               
                return $data->user->name;
            })
            ->addColumn('nominal_pengajuan', function ($data) {
                $button ='';
                $button .= number_format($data->nominal_pengajuan,0, ',' , '.');
                return $button;
            })
            ->editColumn('no_reimbursement', function ($data) {
                return "<a href='".route('reimbursement-travel.show',$data->id)."'>".$data->no_reimbursement."</a>";
            })
            ->rawColumns(['action', 'checkbox','nominal_pengajuan','no_reimbursement'])
            ->make(true);
        }

        return view('reimbursement-travel.approval',[
            'project' => Master_project::get(),
            'kelompok' => Master_kelompok_kegiatan::get(),
            'daftar' => Master_daftar_rencana::get(),
            'driver' => User::whereIn('id',Reimbursement::select('id_user')->get()->pluck('id_user'))->get()
        ]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        
        $tripTypes = TravelTripType::where('type','LOCAL')->get();
        $types = TravelType::get();
        $hotelCondition = TravelHotelCondition::get();

        return view('reimbursement-travel.create',[
            "trip_types" => $tripTypes,
            "types" => $types,
            "hotel_conditions" => $hotelCondition,
            "not_stay_hotel_condition_id" => $this->resolveNotStayHotelConditionId()
        ]);

    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function createOverseas()
    {
        
        $tripTypes = TravelTripType::where('type','INTERNATIONAL')->where('is_show', 1)->get();
        $types = TravelType::get();
        $hotelCondition = TravelHotelCondition::get();

        return view('reimbursement-travel.create-overseas',[
            "trip_types" => $tripTypes,
            "types" => $types,
            "hotel_conditions" => $hotelCondition,
            "not_stay_hotel_condition_id" => $this->resolveNotStayHotelConditionId()
        ]);

    }

    
    public function store(Request $request)
    {
        DB::beginTransaction();
        if (isset($_POST['save'])) {
            $status = 0;
            $notif = 'Reimbursement Successfully Submitted';
        } else if (isset($_POST['save_draft'])) {
            $status = 10; // DRAFT
            $notif = 'Reimbursement Successfully Saved as Draft';
        } else if (isset($_POST['save_item'])) {
            $status = 10;
            $notif = 'redirect';
        }
        try {
            $total = 0;
            foreach ($request->reimburse as $key => $value) {
                $total += (int) round($this->normalizeTravelMoneyValue($value['total'] ?? ''));
            }            

            $data = [
                "id_user" => auth()->user()->id,
                "no_reimbursement" => "PENDING",
                "date" => $request->reimburse['0']['date'],
                "mengetahui_op" => "-",
                "mengetahui_finance" => "-",
                "mengetahui_owner" => "-",
                "nominal_pengajuan" => $total,
                "status" => $status,
                "reimbursement_type" => 2,
                "created_by" => auth()->user()->name,
                "remark" => $request->remark,
                "travel_type" => $request->travel_type,
                "idr_rate" => $this->normalizeTravelMoneyValue($request->idr_rate ?? ''),
                "usd_rate" => $this->normalizeTravelMoneyValue($request->usd_rate ?? ''),
                "jpy_rate" => $this->normalizeTravelMoneyValue($request->jpy_rate ?? ''),
            ];
    
            $data = Reimbursement::create($data);
            $ticketNumber = Reimbursement::buildTicketNumber('T', $data->id);
            $data->update(['no_reimbursement' => $ticketNumber]);
            $data->no_reimbursement = $ticketNumber;
            ActivityLogger::log(
                'reimbursement-travel',
                $status == 10 ? 'draft' : 'create',
                $status == 10 ? 'Reimbursement travel disimpan sebagai draft' : 'Reimbursement travel dibuat',
                $data->no_reimbursement,
                'reimbursement',
                $data->id,
                ['status' => $status]
            );
            foreach ($request->rates as $key => $value) {
                TravelTripRate::create([
                    'reimbursement_id' => $data->id,
                    'currency' => $value['code'],
                    'rate' => $this->normalizeExchangeRateValue($value['rate'] ?? ''),
                ]);
            }

            $tripRateMap = TravelTripRate::where('reimbursement_id', $data->id)
                ->get()
                ->mapWithKeys(function ($row) {
                    return [strtoupper((string) $row->currency) => (float) $row->rate];
                })
                ->toArray();

            foreach ($request->reimburse as $key => $value) {
                $tripTypeId = $this->normalizeTripTypeId($value['trip_type_id'] ?? null);
                $payload = [
                    'reimbursement_id' => $data->id,
                    'date' => $value['date'],
                    'purpose' => $value['purpose'],
                    'trip_type_id' => $tripTypeId,
                    'hotel_condition_id' => $this->normalizeHotelConditionId($value['hotel_condition_id'] ?? null, $tripTypeId),
                    'start_time' => $this->normalizeTravelTime($value['start_time'] ?? null, $tripTypeId),
                    'end_time' => $this->normalizeTravelTime($value['end_time'] ?? null, $tripTypeId),
                    'allowance' => $this->normalizeTravelMoneyValue($value['allowance'] ?? ''),
                    'total' => $this->normalizeTravelMoneyValue($value['total'] ?? ''),
                ];
    
                $dt = ReimbursementTravel::create($payload);
                foreach ($value['detail'] as $k => $v) {
                    if (isset($v['cost_type_id'])) {

                    $currencyCode = !empty($v['currency']) ? strtoupper(trim((string) $v['currency'])) : 'IDR';
                    $amountValue = $this->normalizeTravelAmountInteger($v['amount'] ?? '');
                    $rateValue = ($currencyCode === 'IDR') ? 1.0 : ((float) ($tripRateMap[$currencyCode] ?? 0));
                    $computedIdrRate = $amountValue * $rateValue;
                        
                    $payloadDetail = [
                        'reimbursement_id' => $data->id,
                        'reimbursement_travel_id' => $dt->id,
                        'destination' => $v['destination'],
                        'payment_type' => $v['payment_type'],
                        'cost_type_id' => $v['cost_type_id'],
                        'currency' => $currencyCode,
                        'amount' => $amountValue,
                        'idr_rate' => $computedIdrRate,
                        'tax' => $this->normalizeTravelMoneyValue($v['tax'] ?? '0'),
                    ];
                    $uploadFiles = [];
                    $proofFile = $request->file('reimburse.'.$key.'.detail.'.$k.'.proof');
                    if ($proofFile instanceof UploadedFile) {
                        $uploadFiles[] = $proofFile;
                    }
                    $mainFile = $request->file('reimburse.'.$key.'.detail.'.$k.'.file');
                    if ($mainFile instanceof UploadedFile) {
                        $uploadFiles[] = $mainFile;
                    }

                    if (!empty($uploadFiles)) {
                        $firstStored = $this->storeTravelEvidenceFile($uploadFiles[0]);
                        if ($firstStored !== '') {
                            $payloadDetail['evidence'] = $firstStored;
                        }
                    }
                    $da = ReimbursementTravelDetail::create($payloadDetail);

                    if (!empty($uploadFiles)) {
                        $this->appendUploadedAttachments(
                            (int) $data->id,
                            'travel',
                            'reimbursement_travel_details',
                            (int) $da->id,
                            $uploadFiles
                        );
                    }
                    }
                }
            }

            $id_main = $data->id;
            $travel_type = DB::select( DB::raw("SELECT travel_type FROM reimbursement WHERE id='$id_main'"))['0']->travel_type;

            if ($travel_type=='Domestic') {
                $allowance = DB::select( DB::raw("SELECT sum(allowance) AS total FROM reimbursement_travel WHERE reimbursement_id='$id_main'"))['0']->total;
            } else {
                // $allowance_ = DB::select( DB::raw("SELECT sum(allowance) AS total FROM reimbursement_travel WHERE reimbursement_id='$id_main'"))['0']->total;
                // $rate = DB::select( DB::raw("SELECT rate FROM travel_trip_rates WHERE reimbursement_id='$id_main' AND currency='USD'"))['0']->rate;
                // $allowance = $allowance_ * $rate; 
                $allowance = DB::select( DB::raw("SELECT sum(allowance) AS total FROM reimbursement_travel WHERE reimbursement_id='$id_main'"))['0']->total;
            }


            $total_bdc  = DB::select( DB::raw("SELECT sum(idr_rate) AS total FROM reimbursement_travel_details WHERE reimbursement_id='$id_main' AND payment_type='BDC'"))['0']->total;
            $allowance_bdc = 0;
            $simcard_bdc  = DB::select( DB::raw("SELECT sum(idr_rate) AS total FROM reimbursement_travel_details WHERE reimbursement_id='$id_main' AND payment_type='BDC' AND cost_type_id = 8"))['0']->total;
            $flight_bdc  = DB::select( DB::raw("SELECT sum(idr_rate) AS total FROM reimbursement_travel_details WHERE reimbursement_id='$id_main' AND payment_type='BDC' AND cost_type_id = 4"))['0']->total;
            $rentalcar_bdc  = DB::select( DB::raw("SELECT sum(idr_rate) AS total FROM reimbursement_travel_details WHERE reimbursement_id='$id_main' AND payment_type='BDC' AND cost_type_id = 3"))['0']->total;
            $hotel_bdc  = DB::select( DB::raw("SELECT sum(idr_rate) AS total FROM reimbursement_travel_details WHERE reimbursement_id='$id_main' AND payment_type='BDC' AND cost_type_id = 1"))['0']->total;
            $toll_bdc  = DB::select( DB::raw("SELECT sum(idr_rate) AS total FROM reimbursement_travel_details WHERE reimbursement_id='$id_main' AND payment_type='BDC' AND cost_type_id = 5"))['0']->total;
            $gasoline_bdc  = DB::select( DB::raw("SELECT sum(idr_rate) AS total FROM reimbursement_travel_details WHERE reimbursement_id='$id_main' AND payment_type='BDC' AND cost_type_id = 7"))['0']->total;
            $taxi_bdc  = DB::select( DB::raw("SELECT sum(idr_rate) AS total FROM reimbursement_travel_details WHERE reimbursement_id='$id_main' AND payment_type='BDC' AND cost_type_id = 2"))['0']->total;
            $train_bdc  = DB::select( DB::raw("SELECT sum(idr_rate) AS total FROM reimbursement_travel_details WHERE reimbursement_id='$id_main' AND payment_type='BDC' AND cost_type_id = 6"))['0']->total;
            $tax_bdc  = DB::select( DB::raw("SELECT sum(tax) AS total FROM reimbursement_travel_details WHERE reimbursement_id='$id_main' AND payment_type='BDC'"))['0']->total;
            $others_bdc  = DB::select( DB::raw("SELECT sum(idr_rate) AS total FROM reimbursement_travel_details WHERE reimbursement_id='$id_main' AND payment_type='BDC' AND cost_type_id = 9"))['0']->total;

            $total_cash  = DB::select( DB::raw("SELECT sum(idr_rate) AS total FROM reimbursement_travel_details WHERE reimbursement_id='$id_main' AND payment_type='Cash'"))['0']->total;
            $allowance_cash = $allowance;
            $simcard_cash  = DB::select( DB::raw("SELECT sum(idr_rate) AS total FROM reimbursement_travel_details WHERE reimbursement_id='$id_main' AND payment_type='Cash' AND cost_type_id = 8"))['0']->total;
            $flight_cash  = DB::select( DB::raw("SELECT sum(idr_rate) AS total FROM reimbursement_travel_details WHERE reimbursement_id='$id_main' AND payment_type='Cash' AND cost_type_id = 4"))['0']->total;
            $rentalcar_cash  = DB::select( DB::raw("SELECT sum(idr_rate) AS total FROM reimbursement_travel_details WHERE reimbursement_id='$id_main' AND payment_type='Cash' AND cost_type_id = 3"))['0']->total;
            $hotel_cash  = DB::select( DB::raw("SELECT sum(idr_rate) AS total FROM reimbursement_travel_details WHERE reimbursement_id='$id_main' AND payment_type='Cash' AND cost_type_id = 1"))['0']->total;
            $toll_cash  = DB::select( DB::raw("SELECT sum(idr_rate) AS total FROM reimbursement_travel_details WHERE reimbursement_id='$id_main' AND payment_type='Cash' AND cost_type_id = 5"))['0']->total;
            $gasoline_cash  = DB::select( DB::raw("SELECT sum(idr_rate) AS total FROM reimbursement_travel_details WHERE reimbursement_id='$id_main' AND payment_type='Cash' AND cost_type_id = 7"))['0']->total;
            $taxi_cash  = DB::select( DB::raw("SELECT sum(idr_rate) AS total FROM reimbursement_travel_details WHERE reimbursement_id='$id_main' AND payment_type='Cash' AND cost_type_id = 2"))['0']->total;
            $train_cash  = DB::select( DB::raw("SELECT sum(idr_rate) AS total FROM reimbursement_travel_details WHERE reimbursement_id='$id_main' AND payment_type='Cash' AND cost_type_id = 6"))['0']->total;
            $tax_cash  = DB::select( DB::raw("SELECT sum(tax) AS total FROM reimbursement_travel_details WHERE reimbursement_id='$id_main' AND payment_type='Cash'"))['0']->total;
            $others_cash  = DB::select( DB::raw("SELECT sum(idr_rate) AS total FROM reimbursement_travel_details WHERE reimbursement_id='$id_main' AND payment_type='Cash' AND cost_type_id = 9"))['0']->total;

            $form_data = array(
                'total_bdc'        =>  $total_bdc ?? 0,
                'allowance_bdc'        =>  $allowance_bdc,
                'simcard_bdc'        =>  $simcard_bdc ?? 0,
                'flight_bdc'        =>  $flight_bdc ?? 0,
                'rentalcar_bdc'        =>  $rentalcar_bdc ?? 0,
                'hotel_bdc'        =>  $hotel_bdc ?? 0,
                'toll_bdc'        =>  $toll_bdc ?? 0,
                'gasoline_bdc'        =>  $gasoline_bdc ?? 0,
                'taxi_bdc'        =>  $taxi_bdc ?? 0,
                'train_bdc'        =>  $train_bdc ?? 0,
                'tax_bdc'        =>  $tax_bdc ?? 0,
                'others_bdc'        =>  $others_bdc ?? 0,
                'total_cash'        =>  $total_cash ?? 0,
                'allowance_cash'        =>  $allowance_cash,
                'simcard_cash'        =>  $simcard_cash ?? 0,
                'flight_cash'        =>  $flight_cash ?? 0,
                'rentalcar_cash'        =>  $rentalcar_cash ?? 0,
                'hotel_cash'        =>  $hotel_cash ?? 0,
                'toll_cash'        =>  $toll_cash ?? 0,
                'gasoline_cash'        =>  $gasoline_cash ?? 0,
                'taxi_cash'        =>  $taxi_cash ?? 0,
                'train_cash'        =>  $train_cash ?? 0,
                'tax_cash'        =>  $tax_cash ?? 0,
                'others_cash'        =>  $others_cash ?? 0,
            ); 
        
            Reimbursement::whereId($id_main)->update($form_data);

            $user = \App\User::where('id', $data->id_user)->first();
            if ($status != 10) {
                $curl = \Curl::to('https://api.fonnte.com/send')
                    ->withHeaders(['Authorization: ' . config('services.fonnte.token')])
                    ->withData([
                        'target' => FonnteMessenger::normalizePhone($user->phoneNumber),
                        'message' =>
                            "Hai *" .
                            $user->name .
                            "*,\n\nPengajuan reimbursement Anda dengan nomor *" .
                            $data->no_reimbursement .
                            "* sebesar *Rp " .
                            number_format($data->nominal_pengajuan, 0, ',', '.') .
                            "* telah diterima.\n\nSaat ini sedang menunggu Proses Verifikasi oleh Head Department.\n\nTerima kasih.
                            \n\nKlik untuk melihat detail pengajuan : " .
                            url('/reimbursement-travel/' . $data->id),
                    ])->post();
                
                $id_approval  = $user->id_approval;
                $approval = DB::select(DB::raw("SELECT * FROM users WHERE id='$id_approval'"));

                if (!empty($approval)) {
                    $curl = \Curl::to('https://api.fonnte.com/send')
                        ->withHeaders(['Authorization: ' . config('services.fonnte.token')])
                        ->withData([
                            'target' => FonnteMessenger::normalizePhone($approval[0]->phoneNumber),
                            'message' =>
                                "Hai *" .
                                $approval[0]->name .
                                "*,\n\nPengajuan reimbursement nama *".$user->name."* dengan nomor *" .
                                $data->no_reimbursement .
                                "* sebesar *Rp " .
                                number_format($data->nominal_pengajuan, 0, ',', '.') .
                                "* telah diajukan kembali.\n\nSaat ini sedang menunggu Proses *Verifikasi Anda*.\n\nTerima kasih.\n\nKlik untuk melihat detail pengajuan : " .
                                url('/reimbursement-travel/' . $data->id),
                        ])->post();
                }
            }

            DB::commit();

            $id_travel = DB::select(DB::raw("SELECT id FROM reimbursement_travel WHERE reimbursement_id='$id_main'"))['0']->id;

            if ($notif!='redirect') {
                return redirect()->route('reimbursement-travel.index')->with(['success' => $notif]);    
            } else {
                return redirect('reimbursement-travel/add-item/'.$id_main.'/?new=1');
            }

            

        } catch(\Exception $e) {
            DB::rollback();
            return redirect()->back()->withErrors(['Error '.$e->getMessage()]);
    
        } catch(\Throwable $e) {
            DB::rollback();
            return redirect()->back()->withErrors(['Error '.$e->getMessage()]);
        }
    }

    public function saveItem(Request $request, $id_main)
    {

        DB::beginTransaction();

        $currentStatus = (int) (Reimbursement::whereId($id_main)->value('status') ?? 0);

        $actions = $this->applyTravelFormActionFallback($currentStatus, $this->parseTravelFormActions());
        $isSave = $actions['save'];
        $isSaveDraft = $actions['save_draft'];
        $isSaveItem = $actions['save_item'];
        $isSaveAgain = $actions['save_again'];

        $resolved = $this->resolveTravelItemSaveStatus($currentStatus, $actions);
        $status = $resolved['status'];
        $notif = $resolved['notif'];
        $sendSubmissionNotifications = $resolved['sendSubmissionNotifications'];


        try {
            $allowIncomplete = $isSaveDraft || $isSaveItem;
            $this->validateTravelReimbursementItemRequest($request, $allowIncomplete);

            $remark = $request->remark;
            $reimbursement_department_id = $request->reimbursement_department_id;
            
            //Update table reimbursement
            
            $form_data = array(
                'remark'        =>  $request->remark,
                'reimbursement_department_id'        =>  $request->reimbursement_department_id,
            );
            if (!$allowIncomplete || trim((string) $request->date) !== '') {
                $form_data['date'] = $request->date;
            }
            
            Reimbursement::whereId($id_main)->update($form_data);

            $this->syncTripRatesFromMainForm($request, (int) $id_main);

            $tripRateMap = TravelTripRate::where('reimbursement_id', $id_main)
                ->get()
                ->mapWithKeys(function ($row) {
                    return [strtoupper((string) $row->currency) => (float) $row->rate];
                })
                ->toArray();

            $transactionDate = trim((string) $request->date);
            $purpose = trim((string) ($request->purpose ?? ''));
            $tripTypeRaw = trim((string) ($request->trip_type_id ?? ''));
            $allowanceRaw = trim((string) ($request->allowance ?? ''));
            $costTypeRows = is_array($request->cost_type_id) ? $request->cost_type_id : [];
            $destinationRows = is_array($request->destination) ? $request->destination : [];
            $currencyRows = is_array($request->currency) ? $request->currency : [];
            $amountRows = is_array($request->amount) ? $request->amount : [];
            $paymentRows = is_array($request->payment_type) ? $request->payment_type : [];
            $taxRows = is_array($request->tax) ? $request->tax : [];
            $maxRows = max(
                count($costTypeRows),
                count($destinationRows),
                count($currencyRows),
                count($amountRows),
                count($paymentRows),
                count($taxRows)
            );
            $hasDetailInput = false;
            for ($ri = 0; $ri < $maxRows; $ri++) {
                $hasUploadedEvidence = count($this->getUploadedFilesByRow($request, $ri)) > 0;
                $hasAnyField =
                    trim((string) ($costTypeRows[$ri] ?? '')) !== '' ||
                    trim((string) ($destinationRows[$ri] ?? '')) !== '' ||
                    trim((string) ($currencyRows[$ri] ?? '')) !== '' ||
                    trim((string) ($amountRows[$ri] ?? '')) !== '' ||
                    trim((string) ($paymentRows[$ri] ?? '')) !== '' ||
                    trim((string) ($taxRows[$ri] ?? '')) !== '';
                if ($hasAnyField || $hasUploadedEvidence) {
                    $hasDetailInput = true;
                    break;
                }
            }
            $hasMeaningfulHeader = ($purpose !== '' || $tripTypeRaw !== '' || $allowanceRaw !== '');
            $hasMeaningfulInput = $hasMeaningfulHeader || $hasDetailInput;

            $shouldCreateTravelRow = true;
            if ($allowIncomplete && $transactionDate === '') {
                if ($hasMeaningfulInput) {
                    throw ValidationException::withMessages([
                        'date' => 'Tanggal transaksi wajib diisi sebelum item dapat disimpan.',
                    ]);
                }
                $shouldCreateTravelRow = false;
            }

            $id_detail = null;
            if ($shouldCreateTravelRow) {
                $form_travel = array(
                    'reimbursement_id'        =>  $id_main,
                    'date'        =>  $request->date,
                    'purpose'        =>  $request->purpose,
                    'trip_type_id'        =>  $this->normalizeTripTypeId($request->trip_type_id),
                    'hotel_condition_id'        =>  $this->normalizeHotelConditionId($request->hotel_condition_id, $request->trip_type_id),
                    'start_time'        =>  $this->normalizeTravelTime($request->start_time, $request->trip_type_id),
                    'end_time'        =>  $this->normalizeTravelTime($request->end_time, $request->trip_type_id),
                    'allowance'        =>  $this->resolveTravelAllowanceForSave($request, (int) $id_main),
                    'total'        =>  $this->normalizeTravelMoneyValue($request->nominal_pengajuan ?? ''),
                );

                DB::table('reimbursement_travel')->insert($form_travel);
                $id_detail = DB::select(DB::raw("SELECT max(id) as id_max FROM reimbursement_travel WHERE reimbursement_id='$id_main'"))['0']->id_max;
            }

            $currencies = is_array($request->currency) ? $request->currency : [];
            $count_ = count($currencies);
            $draftFallbackCostTypeId = (int) (TravelType::min('id') ?: 0);

            for ($i=0; $i < $count_; $i++) {
                if (!$id_detail) {
                    break;
                }
                $costTypeId = isset($request->cost_type_id[$i]) ? trim((string) $request->cost_type_id[$i]) : '';
                $hasUploadedEvidence = count($this->getUploadedFilesByRow($request, $i)) > 0;
                if ($costTypeId === '') {
                    if ($allowIncomplete && $hasUploadedEvidence && $draftFallbackCostTypeId > 0) {
                        $costTypeId = (string) $draftFallbackCostTypeId;
                    } else {
                    continue;
                    }
                }

                $oldDetailId = 0;
                $legacyEvidence = '';
                $id_detail_ = $request->id_detail[$i] ?? '';
                if ($id_detail_ !== '' && ctype_digit((string) $id_detail_)) {
                    $oldDetailId = (int) $id_detail_;
                    $rowEv = DB::select(
                        'SELECT evidence FROM reimbursement_travel_details WHERE id = ? LIMIT 1',
                        [$oldDetailId]
                    );
                    $legacyEvidence = !empty($rowEv) ? ($rowEv[0]->evidence ?? '') : '';
                }

                $new = new ReimbursementTravelDetail;
                $new->reimbursement_id = $id_main;
                $new->reimbursement_travel_id = $id_detail;
                $new->cost_type_id = (int) $costTypeId;
                $new->destination = $request->destination[$i] ?? '';
                $new->payment_type = $request->payment_type[$i] ?? '';
                $currencyCode = strtoupper(trim((string) ($request->currency[$i] ?? '')));
                if ($currencyCode === '') {
                    $currencyCode = 'IDR';
                }
                $amountValue = $this->normalizeTravelAmountInteger($request->amount[$i] ?? '');
                $rateValue = ($currencyCode === 'IDR') ? 1.0 : ((float) ($tripRateMap[$currencyCode] ?? 0));
                $computedIdrRate = $amountValue * $rateValue;

                $new->currency = $currencyCode;
                $new->amount = $amountValue;
                $new->idr_rate = $computedIdrRate;
                $new->tax = $this->normalizeTravelMoneyValue($request->tax[$i] ?? '0');
                $new->evidence = '';
                $new->status = 1;
                $new->save();

                $allAttachmentNames = $this->syncAttachmentsFromPreviousDetail(
                    $request,
                    $i,
                    (int) $id_main,
                    'travel',
                    'reimbursement_travel_details',
                    $oldDetailId,
                    (int) $new->id,
                    $legacyEvidence
                );
                $new->evidence = $allAttachmentNames[0] ?? '';
                $new->save();
            }

            if ($id_detail) {
                $this->syncTravelDayTotalAfterDetailsSave((int) $id_detail);
            }

            $total  = DB::select( DB::raw("SELECT sum(total) as total FROM reimbursement_travel WHERE reimbursement_id='$id_main'"))['0']->total;
            
            $form_data = array(
                'status'        =>  $status,
                'nominal_pengajuan' =>  $this->normalizeTravelMoneyValue($total ?? ''),
            );
            
            Reimbursement::where('id', $id_main)->update($form_data);

            $id_max = DB::select( DB::raw("SELECT max(id) as id_max FROM reimbursement_travel WHERE reimbursement_id='$id_main'"))['0']->id_max;
            $travel_type = Reimbursement::whereKey($id_main)->value('travel_type');
            if ($travel_type === null) {
                DB::rollBack();

                return redirect()->back()->withErrors(['Reimbursement tidak ditemukan atau sudah dihapus.']);
            }

            if ($travel_type=='Domestic') {
                $allowance = DB::select( DB::raw("SELECT sum(allowance) AS total FROM reimbursement_travel WHERE reimbursement_id='$id_main'"))['0']->total;
            } else {
                // $allowance_ = DB::select( DB::raw("SELECT sum(allowance) AS total FROM reimbursement_travel WHERE reimbursement_id='$id_main'"))['0']->total;
                // $rate = DB::select( DB::raw("SELECT rate FROM travel_trip_rates WHERE reimbursement_id='$id_main' AND currency='USD'"))['0']->rate;
                // $allowance = $allowance_ * $rate; 
                $allowance = DB::select( DB::raw("SELECT sum(allowance) AS total FROM reimbursement_travel WHERE reimbursement_id='$id_main'"))['0']->total;
            }


            $total_bdc  = DB::select( DB::raw("SELECT sum(idr_rate) AS total FROM reimbursement_travel_details WHERE reimbursement_id='$id_main' AND payment_type='BDC'"))['0']->total;
            $allowance_bdc = 0;
            $simcard_bdc  = DB::select( DB::raw("SELECT sum(idr_rate) AS total FROM reimbursement_travel_details WHERE reimbursement_id='$id_main' AND payment_type='BDC' AND cost_type_id = 8"))['0']->total;
            $flight_bdc  = DB::select( DB::raw("SELECT sum(idr_rate) AS total FROM reimbursement_travel_details WHERE reimbursement_id='$id_main' AND payment_type='BDC' AND cost_type_id = 4"))['0']->total;
            $rentalcar_bdc  = DB::select( DB::raw("SELECT sum(idr_rate) AS total FROM reimbursement_travel_details WHERE reimbursement_id='$id_main' AND payment_type='BDC' AND cost_type_id = 3"))['0']->total;
            $hotel_bdc  = DB::select( DB::raw("SELECT sum(idr_rate) AS total FROM reimbursement_travel_details WHERE reimbursement_id='$id_main' AND payment_type='BDC' AND cost_type_id = 1"))['0']->total;
            $toll_bdc  = DB::select( DB::raw("SELECT sum(idr_rate) AS total FROM reimbursement_travel_details WHERE reimbursement_id='$id_main' AND payment_type='BDC' AND cost_type_id = 5"))['0']->total;
            $gasoline_bdc  = DB::select( DB::raw("SELECT sum(idr_rate) AS total FROM reimbursement_travel_details WHERE reimbursement_id='$id_main' AND payment_type='BDC' AND cost_type_id = 7"))['0']->total;
            $taxi_bdc  = DB::select( DB::raw("SELECT sum(idr_rate) AS total FROM reimbursement_travel_details WHERE reimbursement_id='$id_main' AND payment_type='BDC' AND cost_type_id = 2"))['0']->total;
            $train_bdc  = DB::select( DB::raw("SELECT sum(idr_rate) AS total FROM reimbursement_travel_details WHERE reimbursement_id='$id_main' AND payment_type='BDC' AND cost_type_id = 6"))['0']->total;
            $tax_bdc  = DB::select( DB::raw("SELECT sum(tax) AS total FROM reimbursement_travel_details WHERE reimbursement_id='$id_main' AND payment_type='BDC'"))['0']->total;
            $others_bdc  = DB::select( DB::raw("SELECT sum(idr_rate) AS total FROM reimbursement_travel_details WHERE reimbursement_id='$id_main' AND payment_type='BDC' AND cost_type_id = 9"))['0']->total;

            $total_cash  = DB::select( DB::raw("SELECT sum(idr_rate) AS total FROM reimbursement_travel_details WHERE reimbursement_id='$id_main' AND payment_type='Cash'"))['0']->total;
            $allowance_cash = $allowance;
            $simcard_cash  = DB::select( DB::raw("SELECT sum(idr_rate) AS total FROM reimbursement_travel_details WHERE reimbursement_id='$id_main' AND payment_type='Cash' AND cost_type_id = 8"))['0']->total;
            $flight_cash  = DB::select( DB::raw("SELECT sum(idr_rate) AS total FROM reimbursement_travel_details WHERE reimbursement_id='$id_main' AND payment_type='Cash' AND cost_type_id = 4"))['0']->total;
            $rentalcar_cash  = DB::select( DB::raw("SELECT sum(idr_rate) AS total FROM reimbursement_travel_details WHERE reimbursement_id='$id_main' AND payment_type='Cash' AND cost_type_id = 3"))['0']->total;
            $hotel_cash  = DB::select( DB::raw("SELECT sum(idr_rate) AS total FROM reimbursement_travel_details WHERE reimbursement_id='$id_main' AND payment_type='Cash' AND cost_type_id = 1"))['0']->total;
            $toll_cash  = DB::select( DB::raw("SELECT sum(idr_rate) AS total FROM reimbursement_travel_details WHERE reimbursement_id='$id_main' AND payment_type='Cash' AND cost_type_id = 5"))['0']->total;
            $gasoline_cash  = DB::select( DB::raw("SELECT sum(idr_rate) AS total FROM reimbursement_travel_details WHERE reimbursement_id='$id_main' AND payment_type='Cash' AND cost_type_id = 7"))['0']->total;
            $taxi_cash  = DB::select( DB::raw("SELECT sum(idr_rate) AS total FROM reimbursement_travel_details WHERE reimbursement_id='$id_main' AND payment_type='Cash' AND cost_type_id = 2"))['0']->total;
            $train_cash  = DB::select( DB::raw("SELECT sum(idr_rate) AS total FROM reimbursement_travel_details WHERE reimbursement_id='$id_main' AND payment_type='Cash' AND cost_type_id = 6"))['0']->total;
            $tax_cash  = DB::select( DB::raw("SELECT sum(tax) AS total FROM reimbursement_travel_details WHERE reimbursement_id='$id_main' AND payment_type='Cash'"))['0']->total;
            $others_cash  = DB::select( DB::raw("SELECT sum(idr_rate) AS total FROM reimbursement_travel_details WHERE reimbursement_id='$id_main' AND payment_type='Cash' AND cost_type_id = 9"))['0']->total;

            $total  = DB::select( DB::raw("SELECT sum(total) as total FROM reimbursement_travel WHERE reimbursement_id='$id_main'"))['0']->total;

            $form_data = array(
                'total_bdc'        =>  $total_bdc ?? 0,
                'allowance_bdc'        =>  $allowance_bdc,
                'simcard_bdc'        =>  $simcard_bdc ?? 0,
                'flight_bdc'        =>  $flight_bdc ?? 0,
                'rentalcar_bdc'        =>  $rentalcar_bdc ?? 0,
                'hotel_bdc'        =>  $hotel_bdc ?? 0,
                'toll_bdc'        =>  $toll_bdc ?? 0,
                'gasoline_bdc'        =>  $gasoline_bdc ?? 0,
                'taxi_bdc'        =>  $taxi_bdc ?? 0,
                'train_bdc'        =>  $train_bdc ?? 0,
                'tax_bdc'        =>  $tax_bdc ?? 0,
                'others_bdc'        =>  $others_bdc ?? 0,
                'total_cash'        =>  $total_cash ?? 0,
                'allowance_cash'        =>  $allowance_cash,
                'simcard_cash'        =>  $simcard_cash ?? 0,
                'flight_cash'        =>  $flight_cash ?? 0,
                'rentalcar_cash'        =>  $rentalcar_cash ?? 0,
                'hotel_cash'        =>  $hotel_cash ?? 0,
                'toll_cash'        =>  $toll_cash ?? 0,
                'gasoline_cash'        =>  $gasoline_cash ?? 0,
                'taxi_cash'        =>  $taxi_cash ?? 0,
                'train_cash'        =>  $train_cash ?? 0,
                'tax_cash'        =>  $tax_cash ?? 0,
                'others_cash'        =>  $others_cash ?? 0,
                'status'        =>  $status,
                'nominal_pengajuan' =>  $this->normalizeTravelMoneyValue($total ?? ''),
            ); 
        
            Reimbursement::whereId($id_main)->update($form_data);
            $data = Reimbursement::find($id_main);
            $user = \App\User::where('id', $data->id_user)->first();
            if ($sendSubmissionNotifications) {
                $curl = \Curl::to('https://api.fonnte.com/send')
                    ->withHeaders(['Authorization: ' . config('services.fonnte.token')])
                    ->withData([
                        'target' => FonnteMessenger::normalizePhone($user->phoneNumber),
                        'message' =>
                            "Hai *" .
                            $user->name .
                            "*,\n\nPengajuan reimbursement Anda dengan nomor *" .
                            $data->no_reimbursement .
                            "* sebesar *Rp " .
                            number_format($data->nominal_pengajuan, 0, ',', '.') .
                            "* telah diterima.\n\nSaat ini sedang menunggu Proses Verifikasi oleh Head Department.\n\nTerima kasih.
                            \n\nKlik untuk melihat detail pengajuan : " .
                            url('/reimbursement-travel/' . $data->id),
                    ])->post();
                
                $id_approval  = $user->id_approval;
                $approval = DB::select(DB::raw("SELECT * FROM users WHERE id='$id_approval'"));

                if (!empty($approval)) {
                    $curl = \Curl::to('https://api.fonnte.com/send')
                        ->withHeaders(['Authorization: ' . config('services.fonnte.token')])
                        ->withData([
                            'target' => FonnteMessenger::normalizePhone($approval[0]->phoneNumber),
                            'message' =>
                                "Hai *" .
                                $approval[0]->name .
                                "*,\n\nPengajuan reimbursement nama *".$user->name."* dengan nomor *" .
                                $data->no_reimbursement .
                                "* sebesar *Rp " .
                                number_format($data->nominal_pengajuan, 0, ',', '.') .
                                "* telah diajukan kembali.\n\nSaat ini sedang menunggu Proses *Verifikasi Anda*.\n\nTerima kasih.\n\nKlik untuk melihat detail pengajuan : " .
                                url('/reimbursement-travel/' . $data->id),
                        ])->post();
                }
            }

            DB::commit();

            $id_travel = DB::select(DB::raw("SELECT id FROM reimbursement_travel WHERE reimbursement_id='$id_main'"))['0']->id;

            if ($notif!='redirect') {
                return redirect()->route('reimbursement-travel.index')->with(['success' => $notif]);    
            } else {
                return redirect('reimbursement-travel/add-item/'.$id_main.'/?new=1');
            }

            

        } catch (ValidationException $e) {
            DB::rollback();
            return redirect()->back()->withErrors($e->validator)->withInput();
    
        } catch(\Exception $e) {
            DB::rollback();
            return redirect()->back()->withErrors(['Error '.$e->getMessage()]);
    
        } catch(\Throwable $e) {
            DB::rollback();
            return redirect()->back()->withErrors(['Error '.$e->getMessage()]);
        }

        
    }

    public function show($id)
    {
        $this->repairTripRatesFromStoredAllowances((int) $id);
        $this->recomputeAllTravelDayTotalsForReimbursement((int) $id);

        $data = Reimbursement::find($id);
        $cek  = DB::select( DB::raw("SELECT total_bdc,total_cash, allowance_cash, metode_allowance, metode_cash FROM reimbursement WHERE id = '$id'"));
        $bdc = $cek['0']->total_bdc;
        $cash = $cek['0']->total_cash;
        $allowance = $cek['0']->allowance_cash;
        $metode_allowance_ = $cek['0']->metode_allowance;
        $metode_cash_ = $cek['0']->metode_cash;
        
        if ($metode_allowance_ == null) {
            $metode_allowance = "";
        } else {
            $metode_allowance = DB::select( DB::raw("SELECT nama_list FROM listkasbank WHERE kode_kasbank = '$metode_allowance_'"))['0']->nama_list;  
        }

        if ($metode_cash_ == null) {
            $metode_cash = "";
        } else {
            $metode_cash = DB::select( DB::raw("SELECT nama_list FROM listkasbank WHERE kode_kasbank = '$metode_cash_'"))['0']->nama_list;    
        }

        return view('reimbursement-travel.detail',[
            'data' => $data,
            'bdc' => $bdc,
            'cash' => $cash,
            'allowance' => $allowance,
            'metode_allowance' => $metode_allowance,
            'metode_cash' => $metode_cash,
        ]);
    }

    public function addItem(Request $request, $id_main, $id_travel)
    {
        $data  = DB::select( DB::raw("SELECT * FROM reimbursement WHERE id='$id_main'"));
        $travel_type = $data['0']->travel_type;
        if ($travel_type == 'Domestic') {
            $tripTypes = TravelTripType::where('type','LOCAL')->get();  
            $file = 'add-item';  
        } else {
            $tripTypes = TravelTripType::where('type','INTERNATIONAL')->where('is_show', 1)->get();
            $file = 'add-item-overseas';
        }
        
        $types = TravelType::get();
        $hotelCondition = TravelHotelCondition::get();
        $itemQuery = DB::table('reimbursement_travel')->where('reimbursement_id', $id_main);
        $itemQuery->orderBy('id', 'asc');
        $item = $itemQuery->get()->toArray();
        $id_reimb = $data['0']->id;
        $id_travel_int = (int) $id_travel;
        $data_travel  = $id_travel_int > 0
            ? DB::select(DB::raw("SELECT * FROM reimbursement_travel WHERE id='$id_travel'"))
            : [];
        if ($id_travel_int <= 0 || empty($data_travel)) {
            $fallback = url('reimbursement-travel/add-item/' . $id_main);
            if (!empty($item)) {
                $fallback = url('reimbursement-travel/add-item/' . $id_main . '/' . $item[0]->id);
            }
            if ($request->query('rt_partial') === '1' || $request->header('X-RT-Partial') === '1') {
                $q = strpos($fallback, '?') !== false ? '&' : '?';

                return redirect($fallback . $q . 'rt_partial=1');
            }
            return redirect($fallback);
        }

        $this->ensureDefaultTripRates((int) $id_main, (string) $travel_type);
        foreach ($data_travel as $travelRow) {
            $this->repairCorruptTravelAllowance($travelRow, (int) $id_main);
        }

        $this->repairTripRatesFromStoredAllowances((int) $id_main);
        $this->recomputeAllTravelDayTotalsForReimbursement((int) $id_main);

        $data_travel  = DB::select(DB::raw("SELECT * FROM reimbursement_travel WHERE id='$id_travel'"));
        $travel_trip  = DB::select(DB::raw("SELECT * FROM travel_trip_rates WHERE reimbursement_id='$id_main'"));
        $id_detail = $id_travel;
        $travel_detail  = DB::select(DB::raw("SELECT * FROM reimbursement_travel_details WHERE reimbursement_travel_id='$id_detail'"));
        $currency  = DB::select( DB::raw("SELECT * FROM travel_trip_rates WHERE reimbursement_id='$id_reimb' "));

        $payload = [
            "trip_types" => $tripTypes,
            "types" => $types,
            "hotel_conditions" => $hotelCondition,
            "not_stay_hotel_condition_id" => $this->resolveNotStayHotelConditionId(),
            "data" => $data,
            "data_travel" => $data_travel,
            "travel_trip" => $travel_trip,
            "travel_detail" => $travel_detail,
            "currency" => $currency,
            "data_item" => $item,
            "travel_type" => $travel_type,
            "is_overseas" => ($travel_type !== 'Domestic'),
        ];

        if ($request->query('rt_partial') === '1' || $request->header('X-RT-Partial') === '1') {
            return response()->view('reimbursement-travel.partials.travel-item-pane', $payload);
        }

        return view('reimbursement-travel.'.$file.'', $payload);
    }

    public function addNewItem(Request $request, $id_main)
    {
        $data  = DB::select( DB::raw("SELECT * FROM reimbursement WHERE id='$id_main'"));
        if (empty($data)) {
            return redirect()->route('reimbursement-travel.index')->withErrors(['Data reimbursement tidak ditemukan.']);
        }

        $travel_type = $data['0']->travel_type;
        if ($travel_type == 'Domestic') {
            $tripTypes = TravelTripType::where('type','LOCAL')->get();   
            $file = "add-new-item"; 
        } else {
            $tripTypes = TravelTripType::where('type','INTERNATIONAL')->where('is_show', 1)->get();
            $file = "add-new-item-overseas";
        }
        $types = TravelType::get();
        $hotelCondition = TravelHotelCondition::get();
        
        
        $itemQuery = DB::table('reimbursement_travel')->where('reimbursement_id', $id_main);
        $itemQuery->orderBy('id', 'asc');
        $item = $itemQuery->get()->toArray();
        $id_reimb = $data['0']->id;
        $dataTravelQuery = DB::table('reimbursement_travel')->where('reimbursement_id', $id_main);
        $dataTravelQuery->orderBy('id', 'asc');
        $data_travel = $dataTravelQuery->get()->toArray();

        // Saat user menekan tombol "Add New Item" kita kirim flag ?new=1 sehingga
        // form kosong "new item" benar-benar ditampilkan. Tanpa flag ini,
        // pembukaan ulang halaman edit akan diarahkan ke item terakhir agar user
        // tidak selalu masuk ke layar "new add item" saat membuka edit kembali.
        $forceNew = $request->query('new') === '1';
        if (!$forceNew && !empty($data_travel)) {
            $activeTravelId = (int) ($data_travel[count($data_travel) - 1]->id ?? 0);
            if ($activeTravelId > 0) {
                return redirect('reimbursement-travel/add-item/'.$id_main.'/'.$activeTravelId);
            }
        }

        $this->ensureDefaultTripRates((int) $id_main, (string) $travel_type);

        $travel_trip  = DB::select( DB::raw("SELECT * FROM travel_trip_rates WHERE reimbursement_id='$id_main'"));
        $id_detail = !empty($data_travel) ? $data_travel[0]->id : 0;
        $travel_detail = $id_detail > 0
            ? DB::select(DB::raw("SELECT * FROM reimbursement_travel_details WHERE reimbursement_travel_id='$id_detail'"))
            : [];
        $currency  = DB::select( DB::raw("SELECT * FROM travel_trip_rates WHERE reimbursement_id='$id_reimb'"));

        if (!empty($data_travel)) {
            foreach ($data_travel as $travelRow) {
                $this->repairCorruptTravelAllowance($travelRow, (int) $id_main);
            }
        }

        return view('reimbursement-travel.'.$file.'',[
            "trip_types" => $tripTypes,
            "types" => $types,
            "hotel_conditions" => $hotelCondition,
            "not_stay_hotel_condition_id" => $this->resolveNotStayHotelConditionId(),
            "data" => $data,
            "data_travel" => $data_travel,
            "travel_trip" => $travel_trip,
            "travel_detail" => $travel_detail,
            "currency" => $currency,
            "data_item" => $item,
            "travel_type" => $travel_type,
        ]);
    }

    private function recalculateTravelSummary($id_main)
    {
        // Allowance per tab sudah disimpan dalam IDR (sudah dikali kurs di form).
        $allowance = (float) ReimbursementTravel::where('reimbursement_id', $id_main)->sum('allowance');

        $totalBdc = (float) ReimbursementTravelDetail::where('reimbursement_id', $id_main)->where('payment_type', 'BDC')->sum('idr_rate');
        $simcardBdc = (float) ReimbursementTravelDetail::where('reimbursement_id', $id_main)->where('payment_type', 'BDC')->where('cost_type_id', 8)->sum('idr_rate');
        $flightBdc = (float) ReimbursementTravelDetail::where('reimbursement_id', $id_main)->where('payment_type', 'BDC')->where('cost_type_id', 4)->sum('idr_rate');
        $rentalcarBdc = (float) ReimbursementTravelDetail::where('reimbursement_id', $id_main)->where('payment_type', 'BDC')->where('cost_type_id', 3)->sum('idr_rate');
        $hotelBdc = (float) ReimbursementTravelDetail::where('reimbursement_id', $id_main)->where('payment_type', 'BDC')->where('cost_type_id', 1)->sum('idr_rate');
        $tollBdc = (float) ReimbursementTravelDetail::where('reimbursement_id', $id_main)->where('payment_type', 'BDC')->where('cost_type_id', 5)->sum('idr_rate');
        $gasolineBdc = (float) ReimbursementTravelDetail::where('reimbursement_id', $id_main)->where('payment_type', 'BDC')->where('cost_type_id', 7)->sum('idr_rate');
        $taxiBdc = (float) ReimbursementTravelDetail::where('reimbursement_id', $id_main)->where('payment_type', 'BDC')->where('cost_type_id', 2)->sum('idr_rate');
        $trainBdc = (float) ReimbursementTravelDetail::where('reimbursement_id', $id_main)->where('payment_type', 'BDC')->where('cost_type_id', 6)->sum('idr_rate');
        $taxBdc = (float) ReimbursementTravelDetail::where('reimbursement_id', $id_main)->where('payment_type', 'BDC')->sum('tax');
        $othersBdc = (float) ReimbursementTravelDetail::where('reimbursement_id', $id_main)->where('payment_type', 'BDC')->where('cost_type_id', 9)->sum('idr_rate');

        $totalCash = (float) ReimbursementTravelDetail::where('reimbursement_id', $id_main)->where('payment_type', 'Cash')->sum('idr_rate');
        $simcardCash = (float) ReimbursementTravelDetail::where('reimbursement_id', $id_main)->where('payment_type', 'Cash')->where('cost_type_id', 8)->sum('idr_rate');
        $flightCash = (float) ReimbursementTravelDetail::where('reimbursement_id', $id_main)->where('payment_type', 'Cash')->where('cost_type_id', 4)->sum('idr_rate');
        $rentalcarCash = (float) ReimbursementTravelDetail::where('reimbursement_id', $id_main)->where('payment_type', 'Cash')->where('cost_type_id', 3)->sum('idr_rate');
        $hotelCash = (float) ReimbursementTravelDetail::where('reimbursement_id', $id_main)->where('payment_type', 'Cash')->where('cost_type_id', 1)->sum('idr_rate');
        $tollCash = (float) ReimbursementTravelDetail::where('reimbursement_id', $id_main)->where('payment_type', 'Cash')->where('cost_type_id', 5)->sum('idr_rate');
        $gasolineCash = (float) ReimbursementTravelDetail::where('reimbursement_id', $id_main)->where('payment_type', 'Cash')->where('cost_type_id', 7)->sum('idr_rate');
        $taxiCash = (float) ReimbursementTravelDetail::where('reimbursement_id', $id_main)->where('payment_type', 'Cash')->where('cost_type_id', 2)->sum('idr_rate');
        $trainCash = (float) ReimbursementTravelDetail::where('reimbursement_id', $id_main)->where('payment_type', 'Cash')->where('cost_type_id', 6)->sum('idr_rate');
        $taxCash = (float) ReimbursementTravelDetail::where('reimbursement_id', $id_main)->where('payment_type', 'Cash')->sum('tax');
        $othersCash = (float) ReimbursementTravelDetail::where('reimbursement_id', $id_main)->where('payment_type', 'Cash')->where('cost_type_id', 9)->sum('idr_rate');
        $nominalPengajuan = (float) ReimbursementTravel::where('reimbursement_id', $id_main)->sum('total');

        Reimbursement::whereId($id_main)->update([
            'total_bdc' => $totalBdc,
            'allowance_bdc' => 0,
            'simcard_bdc' => $simcardBdc,
            'flight_bdc' => $flightBdc,
            'rentalcar_bdc' => $rentalcarBdc,
            'hotel_bdc' => $hotelBdc,
            'toll_bdc' => $tollBdc,
            'gasoline_bdc' => $gasolineBdc,
            'taxi_bdc' => $taxiBdc,
            'train_bdc' => $trainBdc,
            'tax_bdc' => $taxBdc,
            'others_bdc' => $othersBdc,
            'total_cash' => $totalCash,
            'allowance_cash' => $allowance,
            'simcard_cash' => $simcardCash,
            'flight_cash' => $flightCash,
            'rentalcar_cash' => $rentalcarCash,
            'hotel_cash' => $hotelCash,
            'toll_cash' => $tollCash,
            'gasoline_cash' => $gasolineCash,
            'taxi_cash' => $taxiCash,
            'train_cash' => $trainCash,
            'tax_cash' => $taxCash,
            'others_cash' => $othersCash,
            'nominal_pengajuan' => $nominalPengajuan,
        ]);
    }

    public function deleteItem($id_main, $id_travel)
    {
        DB::beginTransaction();

        try {
            $reimbursement = Reimbursement::findOrFail($id_main);

            if (!$this->canManageTravelTabs($reimbursement)) {
                DB::rollback();
                return redirect()->back()->withErrors(['Anda tidak memiliki akses untuk menghapus tab ini pada status pengajuan saat ini']);
            }

            $travel = ReimbursementTravel::where('id', $id_travel)->where('reimbursement_id', $id_main)->first();
            if (!$travel) {
                DB::rollback();

                return redirect()->back()->withErrors([
                    'Tab travel tidak ditemukan. Kemungkinan sudah dihapus atau data tab di browser sudah usang — muat ulang halaman lalu coba lagi.',
                ]);
            }

            $details = ReimbursementTravelDetail::where('reimbursement_travel_id', $travel->id)->get();
            foreach ($details as $detail) {
                if (!empty($detail->evidence)) {
                    $filePath = public_path('images/file_bukti/' . $detail->evidence);
                    if (file_exists($filePath)) {
                        @unlink($filePath);
                    }
                }
            }

            ReimbursementTravelDetail::where('reimbursement_travel_id', $travel->id)->delete();
            $travel->delete();

            $remainingTravelCount = ReimbursementTravel::where('reimbursement_id', $id_main)->count();

            if ($remainingTravelCount === 0) {
                TravelTripRate::where('reimbursement_id', $id_main)->delete();
                $reimbursement->delete();
                DB::commit();

                return redirect()->route('reimbursement-travel.index')->with(['success' => 'Tab travel berhasil dihapus']);
            }

            $this->recalculateTravelSummary($id_main);
            $nextTravelId = ReimbursementTravel::where('reimbursement_id', $id_main)->orderBy('id')->value('id');

            DB::commit();

            return redirect('reimbursement-travel/add-item/' . $id_main . '/' . $nextTravelId)->with(['success' => 'Tab travel berhasil dihapus']);
        } catch (\Exception $e) {
            DB::rollback();
            return redirect()->back()->withErrors(['Error ' . $e->getMessage()]);
        } catch (\Throwable $e) {
            DB::rollback();
            return redirect()->back()->withErrors(['Error ' . $e->getMessage()]);
        }
    }

    public function edit($id)
    {
        // Route edit lama sekarang diseragamkan ke UI tab add-item (new flow).
        $reimbursement = Reimbursement::find($id);
        if (!$reimbursement) {
            return redirect()->route('reimbursement-travel.index')->withErrors(['Data reimbursement tidak ditemukan.']);
        }

        return redirect('reimbursement-travel/add-item/' . $id);
    }

    public function editInquiry($id)
    {
        // Endpoint legacy dipertahankan untuk kompatibilitas URL lama,
        // namun diarahkan ke UI tab add-item yang baru.
        return redirect('reimbursement-travel/add-item/' . $id);
    }
    
    public function editOverseas($id)
    {
        // Endpoint legacy dipertahankan untuk kompatibilitas URL lama,
        // namun diarahkan ke UI tab add-item yang baru.
        return redirect('reimbursement-travel/add-item/' . $id);
    }

    
    public function updateInquiry(Request $request, $id)
    {
        $request->validate([
            'currency_rate' => 'required|array|min:1',
            'rate' => 'required|array|min:1',
            'currency_rate.*' => 'required|string|max:32',
            'rate.*' => 'required|string|max:80',
        ], [
            'currency_rate.required' => 'Minimal satu baris kurs (currency rate) wajib diisi.',
            'rate.required' => 'Nilai kurs wajib diisi.',
        ]);

        $this->validateTravelReimbursementItemRequest($request, false);

        $remark = $request->remark;
        $reimbursement_department_id = $request->reimbursement_department_id;

        //Update table reimbursement

        $form_data = array(
            'remark'        =>  $request->remark,
            'reimbursement_department_id'        =>  $request->reimbursement_department_id,
            'date'        =>  $request->date,
        );

        Reimbursement::whereId($id)->update($form_data);

        //Update table travel_trip_rates

        $count = count($request->currency_rate);
        
        $delete  = DB::select( DB::raw("DELETE FROM travel_trip_rates WHERE reimbursement_id = '$id'"));
        
        for ($i=0; $i < $count; $i++) {
          $new = new TravelTripRate;
          $new->reimbursement_id = $id;
          $new->currency = $request->currency_rate[$i];
                    $new->rate = $this->normalizeExchangeRateValue($request->rate[$i] ?? '');
          $new->save();
        }
        
        //Update table  reimbursement_travel

        $form_data = array(
            'purpose'        =>  $request->remark,
            'trip_type_id'        =>  $this->normalizeTripTypeId($request->trip_type_id),
            'hotel_condition_id'        =>  $this->normalizeHotelConditionId($request->hotel_condition_id, $request->trip_type_id),
            'start_time'        =>  $this->normalizeTravelTime($request->start_time, $request->trip_type_id),
            'end_time'        =>  $this->normalizeTravelTime($request->end_time, $request->trip_type_id),
            'allowance'        =>  $this->resolveTravelAllowanceForSave($request, (int) $id),
        );
        
        ReimbursementTravel::where('reimbursement_id', $id)->update($form_data);

        //Update table  reimbursement_travel_details

        $currencies = is_array($request->currency) ? $request->currency : [];
        $count_ = count($currencies);

        $id_detail  = DB::select( DB::raw("SELECT id FROM reimbursement_travel WHERE reimbursement_id = '$id'"))['0']->id;
        DB::select( DB::raw("UPDATE reimbursement_travel_details SET status=0  WHERE reimbursement_travel_id = '$id_detail'"));

        for ($i=0; $i < $count_; $i++) {
            $costTypeId = isset($request->cost_type_id[$i]) ? trim((string) $request->cost_type_id[$i]) : '';
            if ($costTypeId === '') {
                continue;
            }

            $oldDetailId = 0;
            $legacyEvidence = '';
            $id_detail_ = $request->id_detail[$i] ?? '';
            if ($id_detail_ !== '' && ctype_digit((string) $id_detail_)) {
                $oldDetailId = (int) $id_detail_;
                $rowEv = DB::select(
                    'SELECT evidence FROM reimbursement_travel_details WHERE id = ? LIMIT 1',
                    [$oldDetailId]
                );
                $legacyEvidence = !empty($rowEv) ? ($rowEv[0]->evidence ?? '') : '';
            }

            $new = new ReimbursementTravelDetail;
            $new->reimbursement_id = $id;
            $new->reimbursement_travel_id = $id_detail;
            $new->cost_type_id = (int) $costTypeId;
            $new->destination = $request->destination[$i] ?? '';
            $new->payment_type = $request->payment_type[$i] ?? '';
            $new->currency = $request->currency[$i] ?? '';
            $new->idr_rate = $this->normalizeTravelMoneyValue($request->idr_rate[$i] ?? '');
            $new->amount = $this->normalizeTravelAmountInteger($request->amount[$i] ?? '');
            $new->tax = $this->normalizeTravelMoneyValue($request->tax[$i] ?? '0');
            $new->evidence = '';
            $new->status = 1;
            $new->save();

            $allAttachmentNames = $this->syncAttachmentsFromPreviousDetail(
                $request,
                $i,
                (int) $id,
                'travel',
                'reimbursement_travel_details',
                $oldDetailId,
                (int) $new->id,
                $legacyEvidence
            );
            $new->evidence = $allAttachmentNames[0] ?? '';
            $new->save();
        }

        $delete  = DB::select( DB::raw("DELETE FROM reimbursement_travel_details WHERE reimbursement_travel_id = '$id_detail' AND status=0"));

        $this->syncTravelDayTotalAfterDetailsSave((int) $id_detail);

        $total  = DB::select( DB::raw("SELECT sum(total) as total FROM reimbursement_travel WHERE reimbursement_id='$id'"))['0']->total;

        $form_data = array(
            'status'        =>  0,
            'nominal_pengajuan' =>  $this->normalizeTravelMoneyValue($total ?? ''),
        );

        Reimbursement::where('id', $id)->update($form_data);


        $id_max = DB::select( DB::raw("SELECT max(id) as id_max FROM reimbursement_travel WHERE reimbursement_id='$id'"))['0']->id_max;
        $travel_type = DB::select( DB::raw("SELECT travel_type FROM reimbursement WHERE id='$id'"))['0']->travel_type;

        if ($travel_type=='Domestic') {
            $allowance = DB::select( DB::raw("SELECT sum(allowance) AS total FROM reimbursement_travel WHERE reimbursement_id='$id'"))['0']->total;
        } else {
            // $allowance_ = DB::select( DB::raw("SELECT sum(allowance) AS total FROM reimbursement_travel WHERE reimbursement_id='$id_main'"))['0']->total;
            // $rate = DB::select( DB::raw("SELECT rate FROM travel_trip_rates WHERE reimbursement_id='$id' AND currency='USD'"))['0']->rate;
            // $allowance = $allowance_ * $rate; 
            $allowance = DB::select( DB::raw("SELECT sum(allowance) AS total FROM reimbursement_travel WHERE reimbursement_id='$id'"))['0']->total;
        }

        $total_bdc  = DB::select( DB::raw("SELECT sum(idr_rate) AS total FROM reimbursement_travel_details WHERE reimbursement_id='$id' AND payment_type='BDC'"))['0']->total;
        $allowance_bdc = 0;
        $simcard_bdc  = DB::select( DB::raw("SELECT sum(idr_rate) AS total FROM reimbursement_travel_details WHERE reimbursement_id='$id' AND payment_type='BDC' AND cost_type_id = 8"))['0']->total;
        $flight_bdc  = DB::select( DB::raw("SELECT sum(idr_rate) AS total FROM reimbursement_travel_details WHERE reimbursement_id='$id' AND payment_type='BDC' AND cost_type_id = 4"))['0']->total;
        $rentalcar_bdc  = DB::select( DB::raw("SELECT sum(idr_rate) AS total FROM reimbursement_travel_details WHERE reimbursement_id='$id' AND payment_type='BDC' AND cost_type_id = 3"))['0']->total;
        $hotel_bdc  = DB::select( DB::raw("SELECT sum(idr_rate) AS total FROM reimbursement_travel_details WHERE reimbursement_id='$id' AND payment_type='BDC' AND cost_type_id = 1"))['0']->total;
        $toll_bdc  = DB::select( DB::raw("SELECT sum(idr_rate) AS total FROM reimbursement_travel_details WHERE reimbursement_id='$id' AND payment_type='BDC' AND cost_type_id = 5"))['0']->total;
        $gasoline_bdc  = DB::select( DB::raw("SELECT sum(idr_rate) AS total FROM reimbursement_travel_details WHERE reimbursement_id='$id' AND payment_type='BDC' AND cost_type_id = 7"))['0']->total;
        $taxi_bdc  = DB::select( DB::raw("SELECT sum(idr_rate) AS total FROM reimbursement_travel_details WHERE reimbursement_id='$id' AND payment_type='BDC' AND cost_type_id = 2"))['0']->total;
        $train_bdc  = DB::select( DB::raw("SELECT sum(idr_rate) AS total FROM reimbursement_travel_details WHERE reimbursement_id='$id' AND payment_type='BDC' AND cost_type_id = 6"))['0']->total;
        $tax_bdc  = DB::select( DB::raw("SELECT sum(tax) AS total FROM reimbursement_travel_details WHERE reimbursement_id='$id' AND payment_type='BDC'"))['0']->total;
        $others_bdc  = DB::select( DB::raw("SELECT sum(idr_rate) AS total FROM reimbursement_travel_details WHERE reimbursement_id='$id' AND payment_type='BDC' AND cost_type_id = 9"))['0']->total;

        $total_cash  = DB::select( DB::raw("SELECT sum(idr_rate) AS total FROM reimbursement_travel_details WHERE reimbursement_id='$id' AND payment_type='Cash'"))['0']->total;
        $allowance_cash = $allowance;
        $simcard_cash  = DB::select( DB::raw("SELECT sum(idr_rate) AS total FROM reimbursement_travel_details WHERE reimbursement_id='$id' AND payment_type='Cash' AND cost_type_id = 8"))['0']->total;
        $flight_cash  = DB::select( DB::raw("SELECT sum(idr_rate) AS total FROM reimbursement_travel_details WHERE reimbursement_id='$id' AND payment_type='Cash' AND cost_type_id = 4"))['0']->total;
        $rentalcar_cash  = DB::select( DB::raw("SELECT sum(idr_rate) AS total FROM reimbursement_travel_details WHERE reimbursement_id='$id' AND payment_type='Cash' AND cost_type_id = 3"))['0']->total;
        $hotel_cash  = DB::select( DB::raw("SELECT sum(idr_rate) AS total FROM reimbursement_travel_details WHERE reimbursement_id='$id' AND payment_type='Cash' AND cost_type_id = 1"))['0']->total;
        $toll_cash  = DB::select( DB::raw("SELECT sum(idr_rate) AS total FROM reimbursement_travel_details WHERE reimbursement_id='$id' AND payment_type='Cash' AND cost_type_id = 5"))['0']->total;
        $gasoline_cash  = DB::select( DB::raw("SELECT sum(idr_rate) AS total FROM reimbursement_travel_details WHERE reimbursement_id='$id' AND payment_type='Cash' AND cost_type_id = 7"))['0']->total;
        $taxi_cash  = DB::select( DB::raw("SELECT sum(idr_rate) AS total FROM reimbursement_travel_details WHERE reimbursement_id='$id' AND payment_type='Cash' AND cost_type_id = 2"))['0']->total;
        $train_cash  = DB::select( DB::raw("SELECT sum(idr_rate) AS total FROM reimbursement_travel_details WHERE reimbursement_id='$id' AND payment_type='Cash' AND cost_type_id = 6"))['0']->total;
        $tax_cash  = DB::select( DB::raw("SELECT sum(tax) AS total FROM reimbursement_travel_details WHERE reimbursement_id='$id' AND payment_type='Cash'"))['0']->total;
        $others_cash  = DB::select( DB::raw("SELECT sum(idr_rate) AS total FROM reimbursement_travel_details WHERE reimbursement_id='$id' AND payment_type='Cash' AND cost_type_id = 9"))['0']->total;

        $form_report = array(
            'total_bdc'        =>  $total_bdc ?? 0,
            'allowance_bdc'        =>  $allowance_bdc,
            'simcard_bdc'        =>  $simcard_bdc ?? 0,
            'flight_bdc'        =>  $flight_bdc ?? 0,
            'rentalcar_bdc'        =>  $rentalcar_bdc ?? 0,
            'hotel_bdc'        =>  $hotel_bdc ?? 0,
            'toll_bdc'        =>  $toll_bdc ?? 0,
            'gasoline_bdc'        =>  $gasoline_bdc ?? 0,
            'taxi_bdc'        =>  $taxi_bdc ?? 0,
            'train_bdc'        =>  $train_bdc ?? 0,
            'tax_bdc'        =>  $tax_bdc ?? 0,
            'others_bdc'        =>  $others_bdc ?? 0,
            'total_cash'        =>  $total_cash ?? 0,
            'allowance_cash'        =>  $allowance_cash,
            'simcard_cash'        =>  $simcard_cash ?? 0,
            'flight_cash'        =>  $flight_cash ?? 0,
            'rentalcar_cash'        =>  $rentalcar_cash ?? 0,
            'hotel_cash'        =>  $hotel_cash ?? 0,
            'toll_cash'        =>  $toll_cash ?? 0,
            'gasoline_cash'        =>  $gasoline_cash ?? 0,
            'taxi_cash'        =>  $taxi_cash ?? 0,
            'train_cash'        =>  $train_cash ?? 0,
            'tax_cash'        =>  $tax_cash ?? 0,
            'others_cash'        =>  $others_cash ?? 0,
        ); 
    
        Reimbursement::whereId($id)->update($form_report);

        $data = Reimbursement::find($id);
        $user = \App\User::where('id', $data->id_user)->first();
        $curl = \Curl::to('https://api.fonnte.com/send')
            ->withHeaders(['Authorization: ' . config('services.fonnte.token')])
            ->withData([
                'target' => FonnteMessenger::normalizePhone($user->phoneNumber),
                'message' =>
                    "Hai *" .
                    $user->name .
                    "*,\n\nPengajuan reimbursement Anda dengan nomor *" .
                    $data->no_reimbursement .
                    "* sebesar *Rp " .
                    number_format($data->nominal_pengajuan, 0, ',', '.') .
                    "* telah diajukan kembali.\n\nSaat ini sedang menunggu Proses Verifikasi oleh Head Department.\n\nTerima kasih.
                    \n\nKlik untuk melihat detail pengajuan : " .
                    url('/reimbursement-travel/' . $data->id),
            ])->post();

        $dirops = \App\User::where('jabatan', 'Direktur Operasional')->where(function ($query) use ($user) {
                $query->where('departmentId', $user->departmentId)->orWhere('departmentId', null);
                })->get();

        $id_approval  = $user->id_approval;
        $approval = DB::select(DB::raw("SELECT * FROM users WHERE id='$id_approval'"));

        if (!empty($approval)) {
            $curl = \Curl::to('https://api.fonnte.com/send')
                ->withHeaders(['Authorization: ' . config('services.fonnte.token')])
                ->withData([
                    'target' => FonnteMessenger::normalizePhone($approval[0]->phoneNumber),
                    'message' =>
                        "Hai *" .
                        $approval[0]->name .
                        "*,\n\nPengajuan reimbursement nama *".$user->name."*  dengan nomor *" .
                        $data->no_reimbursement .
                        "* sebesar *Rp " .
                        number_format($data->nominal_pengajuan, 0, ',', '.') .
                        "* telah diajukan kembali.\n\nSaat ini sedang menunggu Proses *Verifikasi Anda*.\n\nTerima kasih.\n\nKlik untuk melihat detail pengajuan : " .
                        url('/reimbursement-travel/' . $data->id),
                ])->post();
        }
        
        return redirect('reimbursement-travel')->with(['success' => 'Reimbursement Berhasil Diajukan Kembali']);
    }

    public function updateItem(Request $request, $id_main, $id_travel)
    {
        $id_travel = $this->resolveActiveTravelId($request, (int) $id_main, (int) $id_travel);
        $currentStatus = (int) (Reimbursement::whereId($id_main)->value('status') ?? 0);
        $sendSubmissionNotifications = false;

        if($request->id_user == $request->id_editor) {
            $actions = $this->applyTravelFormActionFallback($currentStatus, $this->parseTravelFormActions());
            $resolved = $this->resolveTravelItemSaveStatus($currentStatus, $actions);
            $status = $resolved['status'];
            $sendSubmissionNotifications = $resolved['sendSubmissionNotifications'];

            if ($resolved['notif'] === 'redirect') {
                $return = redirect('reimbursement-travel/add-item/'.$id_main.'?new=1');
            } elseif ($status === 0 && $sendSubmissionNotifications) {
                $return = redirect('reimbursement-travel')->with(['success' => $resolved['notif']]);
            } else {
                $return = redirect()->back()->with(['success' => $resolved['notif']]);
            }
        } else {
            if (isset($_POST['save_item'])) {
                $status = $currentStatus;
                $return = redirect('reimbursement-travel/add-item/'.$id_main.'?new=1');
            } else
          
			if (isset($_POST['save_owner'])) {
                $status = 3;
                $return = redirect()->back()->with(['success' => "Reimbursement Successfully Updated"]);
            } else if (isset($_POST['edit_owner'])) {
                $status = $currentStatus;
                $return =  redirect()->to('reimbursement-travel/add-item/'.$id_main.'/'.$id_travel.'')->with('success', 'Reimbursement Successfully Updated');
                //$return = redirect('reimbursement-travel-approval')->with(['success' => "Reimbursement Successfully Submitted"]);
            } else if (isset($_POST['edit_finance'])) {
                $status = ($currentStatus > 1) ? $currentStatus : 1;
                $return =  redirect()->to('reimbursement-travel/add-item/'.$id_main.'/'.$id_travel.'')->with('success', 'Reimbursement Successfully Updated');
                //$return = redirect()->back()->with(['success' => "Reimbursement Successfully Updated"]);
            } else if (isset($_POST['save_finance'])) {
                $status = 2;
                $return = redirect('reimbursement-travel-approval')->with(['success' => "Reimbursement Successfully Submitted"]);
            } else {
                $status = 0;
                $return = redirect('reimbursement-travel-approval')->with(['success' => "Reimbursement Successfully Submitted"]);
            }
          	
            
        }

        $this->validateTravelReimbursementItemRequest($request, $this->travelItemAllowIncompleteForm());

        $remark = $request->remark;
        $reimbursement_department_id = $request->reimbursement_department_id;

        //Update table reimbursement

        $form_data = array(
            'remark'        =>  $request->remark,
            'reimbursement_department_id'        =>  $request->reimbursement_department_id,
            'date'        =>  $request->date,
        );

        Reimbursement::whereId($id_main)->update($form_data);

        $this->syncTripRatesFromMainForm($request, (int) $id_main);

        $tripRateMap = TravelTripRate::where('reimbursement_id', $id_main)
            ->get()
            ->mapWithKeys(function ($row) {
                return [strtoupper((string) $row->currency) => (float) $row->rate];
            })
            ->toArray();

        //Update table  reimbursement_travel

        $form_data = array(
            'date'        =>  $request->date,
            'purpose'        =>  $request->purpose,
            'trip_type_id'        =>  $this->normalizeTripTypeId($request->trip_type_id),
            'hotel_condition_id'        =>  $this->normalizeHotelConditionId($request->hotel_condition_id, $request->trip_type_id),
            'start_time'        =>  $this->normalizeTravelTime($request->start_time, $request->trip_type_id),
            'end_time'        =>  $this->normalizeTravelTime($request->end_time, $request->trip_type_id),
            'allowance'        =>  $this->resolveTravelAllowanceForSave($request, (int) $id_main),
            'total'        =>  $this->normalizeTravelMoneyValue($request->nominal_pengajuan ?? ''),
        );

        ReimbursementTravel::where('id', $id_travel)->update($form_data);

        //Update table  reimbursement_travel_details

        $currencies = is_array($request->currency) ? $request->currency : [];
        $count_ = count($currencies);
        $allowIncomplete = $this->travelItemAllowIncompleteForm();
        $draftFallbackCostTypeId = (int) (TravelType::min('id') ?: 0);

        $id_detail = $id_travel;
        DB::select( DB::raw("UPDATE reimbursement_travel_details SET status=0  WHERE reimbursement_travel_id = '$id_detail'"));

        for ($i=0; $i < $count_; $i++) {
            $costTypeId = isset($request->cost_type_id[$i]) ? trim((string) $request->cost_type_id[$i]) : '';
            $hasUploadedEvidence = count($this->getUploadedFilesByRow($request, $i)) > 0;
            if ($costTypeId === '') {
                if ($allowIncomplete && $hasUploadedEvidence && $draftFallbackCostTypeId > 0) {
                    $costTypeId = (string) $draftFallbackCostTypeId;
                } else {
                continue;
                }
            }

            $oldDetailId = 0;
            $legacyEvidence = '';
            $id_detail_ = $request->id_detail[$i] ?? '';
            if ($id_detail_ !== '' && ctype_digit((string) $id_detail_)) {
                $oldDetailId = (int) $id_detail_;
                $rowEv = DB::select(
                    'SELECT evidence FROM reimbursement_travel_details WHERE id = ? LIMIT 1',
                    [$oldDetailId]
                );
                $legacyEvidence = !empty($rowEv) ? ($rowEv[0]->evidence ?? '') : '';
            }

            $new = new ReimbursementTravelDetail;
            $new->reimbursement_id = $id_main;
            $new->reimbursement_travel_id = $id_detail;
            $new->cost_type_id = (int) $costTypeId;
            $new->destination = $request->destination[$i] ?? '';
            $new->payment_type = $request->payment_type[$i] ?? '';
            $currencyCode = strtoupper(trim((string) ($request->currency[$i] ?? '')));
            if ($currencyCode === '') {
                $currencyCode = 'IDR';
            }
            $amountValue = $this->normalizeTravelAmountInteger($request->amount[$i] ?? '');
            $rateValue = ($currencyCode === 'IDR') ? 1.0 : ((float) ($tripRateMap[$currencyCode] ?? 0));
            $computedIdrRate = $amountValue * $rateValue;

            $new->currency = $currencyCode;
            $new->amount = $amountValue;
            $new->idr_rate = $computedIdrRate;
            $new->tax = $this->normalizeTravelMoneyValue($request->tax[$i] ?? '0');
            $new->evidence = '';
            $new->status = 1;
            $new->save();

            $allAttachmentNames = $this->syncAttachmentsFromPreviousDetail(
                $request,
                $i,
                (int) $id_main,
                'travel',
                'reimbursement_travel_details',
                $oldDetailId,
                (int) $new->id,
                $legacyEvidence
            );
            $new->evidence = $allAttachmentNames[0] ?? '';
            $new->save();
        }

        $delete  = DB::select( DB::raw("DELETE FROM reimbursement_travel_details WHERE reimbursement_travel_id = '$id_detail' AND status=0"));

        $this->syncTravelDayTotalAfterDetailsSave((int) $id_detail);

        $total  = DB::select( DB::raw("SELECT sum(total) as total FROM reimbursement_travel WHERE reimbursement_id='$id_main'"))['0']->total;

        $form_data = array(
            'status'        =>  $status,
            'nominal_pengajuan' =>  $this->normalizeTravelMoneyValue($total ?? ''),
        );

        Reimbursement::where('id', $id_main)->update($form_data);
        $logRowReject = Reimbursement::find($id_main);
        ActivityLogger::log(
            'reimbursement-travel',
            $status == 9 ? 'reject' : 'update',
            $status == 9 ? 'Item reimbursement travel ditolak/diperbaharui' : 'Item reimbursement travel diperbaharui',
            $logRowReject ? $logRowReject->no_reimbursement : null,
            'reimbursement',
            $id_main,
            ['status' => $status]
        );


        $id_max = DB::select( DB::raw("SELECT max(id) as id_max FROM reimbursement_travel WHERE reimbursement_id='$id_travel'"))['0']->id_max;
        $travel_type = DB::select( DB::raw("SELECT travel_type FROM reimbursement WHERE id='$id_main'"))['0']->travel_type;

        if ($travel_type=='Domestic') {
            $allowance = DB::select( DB::raw("SELECT sum(allowance) AS total FROM reimbursement_travel WHERE reimbursement_id='$id_main'"))['0']->total;
        } else {
            // $allowance_ = DB::select( DB::raw("SELECT sum(allowance) AS total FROM reimbursement_travel WHERE reimbursement_id='$id_main'"))['0']->total;
            // $rate = DB::select( DB::raw("SELECT rate FROM travel_trip_rates WHERE reimbursement_id='$id_main' AND currency='USD'"))['0']->rate;
            // $allowance = $allowance_ * $rate; 
            $allowance = DB::select( DB::raw("SELECT sum(allowance) AS total FROM reimbursement_travel WHERE reimbursement_id='$id_main'"))['0']->total;

        }

        $total_bdc  = DB::select( DB::raw("SELECT sum(idr_rate) AS total FROM reimbursement_travel_details WHERE reimbursement_id='$id_main' AND payment_type='BDC'"))['0']->total;
        $allowance_bdc = 0;
        $simcard_bdc  = DB::select( DB::raw("SELECT sum(idr_rate) AS total FROM reimbursement_travel_details WHERE reimbursement_id='$id_main' AND payment_type='BDC' AND cost_type_id = 8"))['0']->total;
        $flight_bdc  = DB::select( DB::raw("SELECT sum(idr_rate) AS total FROM reimbursement_travel_details WHERE reimbursement_id='$id_main' AND payment_type='BDC' AND cost_type_id = 4"))['0']->total;
        $rentalcar_bdc  = DB::select( DB::raw("SELECT sum(idr_rate) AS total FROM reimbursement_travel_details WHERE reimbursement_id='$id_main' AND payment_type='BDC' AND cost_type_id = 3"))['0']->total;
        $hotel_bdc  = DB::select( DB::raw("SELECT sum(idr_rate) AS total FROM reimbursement_travel_details WHERE reimbursement_id='$id_main' AND payment_type='BDC' AND cost_type_id = 1"))['0']->total;
        $toll_bdc  = DB::select( DB::raw("SELECT sum(idr_rate) AS total FROM reimbursement_travel_details WHERE reimbursement_id='$id_main' AND payment_type='BDC' AND cost_type_id = 5"))['0']->total;
        $gasoline_bdc  = DB::select( DB::raw("SELECT sum(idr_rate) AS total FROM reimbursement_travel_details WHERE reimbursement_id='$id_main' AND payment_type='BDC' AND cost_type_id = 7"))['0']->total;
        $taxi_bdc  = DB::select( DB::raw("SELECT sum(idr_rate) AS total FROM reimbursement_travel_details WHERE reimbursement_id='$id_main' AND payment_type='BDC' AND cost_type_id = 2"))['0']->total;
        $train_bdc  = DB::select( DB::raw("SELECT sum(idr_rate) AS total FROM reimbursement_travel_details WHERE reimbursement_id='$id_main' AND payment_type='BDC' AND cost_type_id = 6"))['0']->total;
        $tax_bdc  = DB::select( DB::raw("SELECT sum(tax) AS total FROM reimbursement_travel_details WHERE reimbursement_id='$id_main' AND payment_type='BDC'"))['0']->total;
        $others_bdc  = DB::select( DB::raw("SELECT sum(idr_rate) AS total FROM reimbursement_travel_details WHERE reimbursement_id='$id_main' AND payment_type='BDC' AND cost_type_id = 9"))['0']->total;

        $total_cash  = DB::select( DB::raw("SELECT sum(idr_rate) AS total FROM reimbursement_travel_details WHERE reimbursement_id='$id_main' AND payment_type='Cash'"))['0']->total;
        $allowance_cash = $allowance;
        $simcard_cash  = DB::select( DB::raw("SELECT sum(idr_rate) AS total FROM reimbursement_travel_details WHERE reimbursement_id='$id_main' AND payment_type='Cash' AND cost_type_id = 8"))['0']->total;
        $flight_cash  = DB::select( DB::raw("SELECT sum(idr_rate) AS total FROM reimbursement_travel_details WHERE reimbursement_id='$id_main' AND payment_type='Cash' AND cost_type_id = 4"))['0']->total;
        $rentalcar_cash  = DB::select( DB::raw("SELECT sum(idr_rate) AS total FROM reimbursement_travel_details WHERE reimbursement_id='$id_main' AND payment_type='Cash' AND cost_type_id = 3"))['0']->total;
        $hotel_cash  = DB::select( DB::raw("SELECT sum(idr_rate) AS total FROM reimbursement_travel_details WHERE reimbursement_id='$id_main' AND payment_type='Cash' AND cost_type_id = 1"))['0']->total;
        $toll_cash  = DB::select( DB::raw("SELECT sum(idr_rate) AS total FROM reimbursement_travel_details WHERE reimbursement_id='$id_main' AND payment_type='Cash' AND cost_type_id = 5"))['0']->total;
        $gasoline_cash  = DB::select( DB::raw("SELECT sum(idr_rate) AS total FROM reimbursement_travel_details WHERE reimbursement_id='$id_main' AND payment_type='Cash' AND cost_type_id = 7"))['0']->total;
        $taxi_cash  = DB::select( DB::raw("SELECT sum(idr_rate) AS total FROM reimbursement_travel_details WHERE reimbursement_id='$id_main' AND payment_type='Cash' AND cost_type_id = 2"))['0']->total;
        $train_cash  = DB::select( DB::raw("SELECT sum(idr_rate) AS total FROM reimbursement_travel_details WHERE reimbursement_id='$id_main' AND payment_type='Cash' AND cost_type_id = 6"))['0']->total;
        $tax_cash  = DB::select( DB::raw("SELECT sum(tax) AS total FROM reimbursement_travel_details WHERE reimbursement_id='$id_main' AND payment_type='Cash'"))['0']->total;
        $others_cash  = DB::select( DB::raw("SELECT sum(idr_rate) AS total FROM reimbursement_travel_details WHERE reimbursement_id='$id_main' AND payment_type='Cash' AND cost_type_id = 9"))['0']->total;

        $form_report = array(
            'total_bdc'        =>  $total_bdc ?? 0,
            'allowance_bdc'        =>  $allowance_bdc,
            'simcard_bdc'        =>  $simcard_bdc ?? 0,
            'flight_bdc'        =>  $flight_bdc ?? 0,
            'rentalcar_bdc'        =>  $rentalcar_bdc ?? 0,
            'hotel_bdc'        =>  $hotel_bdc ?? 0,
            'toll_bdc'        =>  $toll_bdc ?? 0,
            'gasoline_bdc'        =>  $gasoline_bdc ?? 0,
            'taxi_bdc'        =>  $taxi_bdc ?? 0,
            'train_bdc'        =>  $train_bdc ?? 0,
            'tax_bdc'        =>  $tax_bdc ?? 0,
            'others_bdc'        =>  $others_bdc ?? 0,
            'total_cash'        =>  $total_cash ?? 0,
            'allowance_cash'        =>  $allowance_cash,
            'simcard_cash'        =>  $simcard_cash ?? 0,
            'flight_cash'        =>  $flight_cash ?? 0,
            'rentalcar_cash'        =>  $rentalcar_cash ?? 0,
            'hotel_cash'        =>  $hotel_cash ?? 0,
            'toll_cash'        =>  $toll_cash ?? 0,
            'gasoline_cash'        =>  $gasoline_cash ?? 0,
            'taxi_cash'        =>  $taxi_cash ?? 0,
            'train_cash'        =>  $train_cash ?? 0,
            'tax_cash'        =>  $tax_cash ?? 0,
            'others_cash'        =>  $others_cash ?? 0,
        ); 
    
        Reimbursement::whereId($id_main)->update($form_report);
        if($request->id_user == $request->id_editor) {
          if ($sendSubmissionNotifications && $status === 0) {
              $data = Reimbursement::find($id_main);
              $user = \App\User::where('id', $data->id_user)->first();
              $curl = \Curl::to('https://api.fonnte.com/send')
                  ->withHeaders(['Authorization: ' . config('services.fonnte.token')])
                  ->withData([
                      'target' => FonnteMessenger::normalizePhone($user->phoneNumber),
                      'message' =>
                          "Hai *" .
                          $user->name .
                          "*,\n\nPengajuan reimbursement Anda dengan nomor *" .
                          $data->no_reimbursement .
                          "* sebesar *Rp " .
                          number_format($data->nominal_pengajuan, 0, ',', '.') .
                          "* telah diajukan.\n\nSaat ini sedang menunggu Proses Verifikasi oleh Head Department.\n\nTerima kasih.
                          \n\nKlik untuk melihat detail pengajuan : " .
                          url('/reimbursement-travel/' . $data->id),
                  ])->post();

              $dirops = \App\User::where('jabatan', 'Direktur Operasional')->where(function ($query) use ($user) {
                      $query->where('departmentId', $user->departmentId)->orWhere('departmentId', null);
                      })->get();

              $id_approval  = $user->id_approval;
              $approval = DB::select(DB::raw("SELECT * FROM users WHERE id='$id_approval'"));

              if (!empty($approval)) {
                  $curl = \Curl::to('https://api.fonnte.com/send')
                      ->withHeaders(['Authorization: ' . config('services.fonnte.token')])
                      ->withData([
                          'target' => FonnteMessenger::normalizePhone($approval[0]->phoneNumber),
                          'message' =>
                              "Hai *" .
                              $approval[0]->name .
                              "*,\n\nPengajuan reimbursement nama *".$user->name."* dengan nomor *" .
                              $data->no_reimbursement .
                              "* sebesar *Rp " .
                              number_format($data->nominal_pengajuan, 0, ',', '.') .
                              "* telah diajukan kembali.\n\nSaat ini sedang menunggu Proses *Verifikasi Anda*.\n\nTerima kasih.\n\nKlik untuk melihat detail pengajuan : " .
                              url('/reimbursement-travel/' . $data->id),
                      ])->post();
              }
          }
        }
            
        return $return;
    }
  
    public function updateItemReject(Request $request, $id_main, $id_travel)
    {
        $id_travel = $this->resolveActiveTravelId($request, (int) $id_main, (int) $id_travel);

        $currentStatus = (int) (Reimbursement::whereId($id_main)->value('status') ?? 9);
        $actions = $this->applyTravelFormActionFallback($currentStatus, $this->parseTravelFormActions());
        $resolved = $this->resolveTravelItemSaveStatus($currentStatus, $actions);
        $status = $resolved['status'];
        $sendSubmissionNotifications = $resolved['sendSubmissionNotifications'];

        if (isset($_POST['save_finance'])) {
            $status = 1;
            $sendSubmissionNotifications = false;
            $return = back()->with(['success' => "Reimbursement Successfully Updated"]);
        } elseif ($resolved['notif'] === 'redirect') {
            $return = redirect('reimbursement-travel/add-item/'.$id_main.'?new=1');
        } elseif ($sendSubmissionNotifications && $status === 0) {
            $return = redirect('reimbursement-travel')->with(['success' => $resolved['notif']]);
        } else {
            $return = back()->with(['success' => $resolved['notif']]);
        }

        $this->validateTravelReimbursementItemRequest($request, $this->travelItemAllowIncompleteForm());

        $remark = $request->remark;
        $reimbursement_department_id = $request->reimbursement_department_id;

        //Update table reimbursement

        $form_data = array(
            'remark'        =>  $request->remark,
            'reimbursement_department_id'        =>  $request->reimbursement_department_id,
            'date'        =>  $request->date,
        );

        Reimbursement::whereId($id_main)->update($form_data);

        $this->syncTripRatesFromMainForm($request, (int) $id_main);

        //Update table  reimbursement_travel

        $form_data = array(
            'date'        =>  $request->date,
            'purpose'        =>  $request->purpose,
            'trip_type_id'        =>  $this->normalizeTripTypeId($request->trip_type_id),
            'hotel_condition_id'        =>  $this->normalizeHotelConditionId($request->hotel_condition_id, $request->trip_type_id),
            'start_time'        =>  $this->normalizeTravelTime($request->start_time, $request->trip_type_id),
            'end_time'        =>  $this->normalizeTravelTime($request->end_time, $request->trip_type_id),
            'allowance'        =>  $this->resolveTravelAllowanceForSave($request, (int) $id_main),
            'total'        =>  $this->normalizeTravelMoneyValue($request->nominal_pengajuan ?? ''),
        );

        ReimbursementTravel::where('id', $id_travel)->update($form_data);

        //Update table  reimbursement_travel_details

        $currencies = is_array($request->currency) ? $request->currency : [];
        $count_ = count($currencies);

        $id_detail = $id_travel;
        DB::select( DB::raw("UPDATE reimbursement_travel_details SET status=0  WHERE reimbursement_travel_id = '$id_detail'"));

        for ($i=0; $i < $count_; $i++) {
            $costTypeId = isset($request->cost_type_id[$i]) ? trim((string) $request->cost_type_id[$i]) : '';
            if ($costTypeId === '') {
                continue;
            }

            $oldDetailId = 0;
            $legacyEvidence = '';
            $id_detail_ = $request->id_detail[$i] ?? '';
            if ($id_detail_ !== '' && ctype_digit((string) $id_detail_)) {
                $oldDetailId = (int) $id_detail_;
                $rowEv = DB::select(
                    'SELECT evidence FROM reimbursement_travel_details WHERE id = ? LIMIT 1',
                    [$oldDetailId]
                );
                $legacyEvidence = !empty($rowEv) ? ($rowEv[0]->evidence ?? '') : '';
            }

            $new = new ReimbursementTravelDetail;
            $new->reimbursement_id = $id_main;
            $new->reimbursement_travel_id = $id_detail;
            $new->cost_type_id = (int) $costTypeId;
            $new->destination = $request->destination[$i] ?? '';
            $new->payment_type = $request->payment_type[$i] ?? '';
            $new->currency = $request->currency[$i] ?? '';
            $new->idr_rate = $this->normalizeTravelMoneyValue($request->idr_rate[$i] ?? '');
            $new->amount = $this->normalizeTravelAmountInteger($request->amount[$i] ?? '');
            $new->tax = $this->normalizeTravelMoneyValue($request->tax[$i] ?? '0');
            $new->evidence = '';
            $new->status = 1;
            $new->save();

            $allAttachmentNames = $this->syncAttachmentsFromPreviousDetail(
                $request,
                $i,
                (int) $id_main,
                'travel',
                'reimbursement_travel_details',
                $oldDetailId,
                (int) $new->id,
                $legacyEvidence
            );
            $new->evidence = $allAttachmentNames[0] ?? '';
            $new->save();
        }

        $delete  = DB::select( DB::raw("DELETE FROM reimbursement_travel_details WHERE reimbursement_travel_id = '$id_detail' AND status=0"));

        $this->syncTravelDayTotalAfterDetailsSave((int) $id_detail);

        $total  = DB::select( DB::raw("SELECT sum(total) as total FROM reimbursement_travel WHERE reimbursement_id='$id_main'"))['0']->total;

        $form_data = array(
            'status'        =>  $status,
            'nominal_pengajuan' =>  $this->normalizeTravelMoneyValue($total ?? ''),
        );

        Reimbursement::where('id', $id_main)->update($form_data);
        $logRowApproval = Reimbursement::find($id_main);
        ActivityLogger::log(
            'reimbursement-travel',
            'approve',
            'Item reimbursement travel disetujui/diperbaharui',
            $logRowApproval ? $logRowApproval->no_reimbursement : null,
            'reimbursement',
            $id_main,
            ['status' => $status]
        );


        $id_max = DB::select( DB::raw("SELECT max(id) as id_max FROM reimbursement_travel WHERE reimbursement_id='$id_travel'"))['0']->id_max;
        $travel_type = DB::select( DB::raw("SELECT travel_type FROM reimbursement WHERE id='$id_main'"))['0']->travel_type;

        if ($travel_type=='Domestic') {
            $allowance = DB::select( DB::raw("SELECT sum(allowance) AS total FROM reimbursement_travel WHERE reimbursement_id='$id_main'"))['0']->total;
        } else {
            // $allowance_ = DB::select( DB::raw("SELECT sum(allowance) AS total FROM reimbursement_travel WHERE reimbursement_id='$id_main'"))['0']->total;
            // $rate = DB::select( DB::raw("SELECT rate FROM travel_trip_rates WHERE reimbursement_id='$id_main' AND currency='USD'"))['0']->rate;
            // $allowance = $allowance_ * $rate; 
            $allowance = DB::select( DB::raw("SELECT sum(allowance) AS total FROM reimbursement_travel WHERE reimbursement_id='$id_main'"))['0']->total;

        }

        $total_bdc  = DB::select( DB::raw("SELECT sum(idr_rate) AS total FROM reimbursement_travel_details WHERE reimbursement_id='$id_main' AND payment_type='BDC'"))['0']->total;
        $allowance_bdc = 0;
        $simcard_bdc  = DB::select( DB::raw("SELECT sum(idr_rate) AS total FROM reimbursement_travel_details WHERE reimbursement_id='$id_main' AND payment_type='BDC' AND cost_type_id = 8"))['0']->total;
        $flight_bdc  = DB::select( DB::raw("SELECT sum(idr_rate) AS total FROM reimbursement_travel_details WHERE reimbursement_id='$id_main' AND payment_type='BDC' AND cost_type_id = 4"))['0']->total;
        $rentalcar_bdc  = DB::select( DB::raw("SELECT sum(idr_rate) AS total FROM reimbursement_travel_details WHERE reimbursement_id='$id_main' AND payment_type='BDC' AND cost_type_id = 3"))['0']->total;
        $hotel_bdc  = DB::select( DB::raw("SELECT sum(idr_rate) AS total FROM reimbursement_travel_details WHERE reimbursement_id='$id_main' AND payment_type='BDC' AND cost_type_id = 1"))['0']->total;
        $toll_bdc  = DB::select( DB::raw("SELECT sum(idr_rate) AS total FROM reimbursement_travel_details WHERE reimbursement_id='$id_main' AND payment_type='BDC' AND cost_type_id = 5"))['0']->total;
        $gasoline_bdc  = DB::select( DB::raw("SELECT sum(idr_rate) AS total FROM reimbursement_travel_details WHERE reimbursement_id='$id_main' AND payment_type='BDC' AND cost_type_id = 7"))['0']->total;
        $taxi_bdc  = DB::select( DB::raw("SELECT sum(idr_rate) AS total FROM reimbursement_travel_details WHERE reimbursement_id='$id_main' AND payment_type='BDC' AND cost_type_id = 2"))['0']->total;
        $train_bdc  = DB::select( DB::raw("SELECT sum(idr_rate) AS total FROM reimbursement_travel_details WHERE reimbursement_id='$id_main' AND payment_type='BDC' AND cost_type_id = 6"))['0']->total;
        $tax_bdc  = DB::select( DB::raw("SELECT sum(tax) AS total FROM reimbursement_travel_details WHERE reimbursement_id='$id_main' AND payment_type='BDC'"))['0']->total;
        $others_bdc  = DB::select( DB::raw("SELECT sum(idr_rate) AS total FROM reimbursement_travel_details WHERE reimbursement_id='$id_main' AND payment_type='BDC' AND cost_type_id = 9"))['0']->total;

        $total_cash  = DB::select( DB::raw("SELECT sum(idr_rate) AS total FROM reimbursement_travel_details WHERE reimbursement_id='$id_main' AND payment_type='Cash'"))['0']->total;
        $allowance_cash = $allowance;
        $simcard_cash  = DB::select( DB::raw("SELECT sum(idr_rate) AS total FROM reimbursement_travel_details WHERE reimbursement_id='$id_main' AND payment_type='Cash' AND cost_type_id = 8"))['0']->total;
        $flight_cash  = DB::select( DB::raw("SELECT sum(idr_rate) AS total FROM reimbursement_travel_details WHERE reimbursement_id='$id_main' AND payment_type='Cash' AND cost_type_id = 4"))['0']->total;
        $rentalcar_cash  = DB::select( DB::raw("SELECT sum(idr_rate) AS total FROM reimbursement_travel_details WHERE reimbursement_id='$id_main' AND payment_type='Cash' AND cost_type_id = 3"))['0']->total;
        $hotel_cash  = DB::select( DB::raw("SELECT sum(idr_rate) AS total FROM reimbursement_travel_details WHERE reimbursement_id='$id_main' AND payment_type='Cash' AND cost_type_id = 1"))['0']->total;
        $toll_cash  = DB::select( DB::raw("SELECT sum(idr_rate) AS total FROM reimbursement_travel_details WHERE reimbursement_id='$id_main' AND payment_type='Cash' AND cost_type_id = 5"))['0']->total;
        $gasoline_cash  = DB::select( DB::raw("SELECT sum(idr_rate) AS total FROM reimbursement_travel_details WHERE reimbursement_id='$id_main' AND payment_type='Cash' AND cost_type_id = 7"))['0']->total;
        $taxi_cash  = DB::select( DB::raw("SELECT sum(idr_rate) AS total FROM reimbursement_travel_details WHERE reimbursement_id='$id_main' AND payment_type='Cash' AND cost_type_id = 2"))['0']->total;
        $train_cash  = DB::select( DB::raw("SELECT sum(idr_rate) AS total FROM reimbursement_travel_details WHERE reimbursement_id='$id_main' AND payment_type='Cash' AND cost_type_id = 6"))['0']->total;
        $tax_cash  = DB::select( DB::raw("SELECT sum(tax) AS total FROM reimbursement_travel_details WHERE reimbursement_id='$id_main' AND payment_type='Cash'"))['0']->total;
        $others_cash  = DB::select( DB::raw("SELECT sum(idr_rate) AS total FROM reimbursement_travel_details WHERE reimbursement_id='$id_main' AND payment_type='Cash' AND cost_type_id = 9"))['0']->total;

        $form_report = array(
            'total_bdc'        =>  $total_bdc ?? 0,
            'allowance_bdc'        =>  $allowance_bdc,
            'simcard_bdc'        =>  $simcard_bdc ?? 0,
            'flight_bdc'        =>  $flight_bdc ?? 0,
            'rentalcar_bdc'        =>  $rentalcar_bdc ?? 0,
            'hotel_bdc'        =>  $hotel_bdc ?? 0,
            'toll_bdc'        =>  $toll_bdc ?? 0,
            'gasoline_bdc'        =>  $gasoline_bdc ?? 0,
            'taxi_bdc'        =>  $taxi_bdc ?? 0,
            'train_bdc'        =>  $train_bdc ?? 0,
            'tax_bdc'        =>  $tax_bdc ?? 0,
            'others_bdc'        =>  $others_bdc ?? 0,
            'total_cash'        =>  $total_cash ?? 0,
            'allowance_cash'        =>  $allowance_cash,
            'simcard_cash'        =>  $simcard_cash ?? 0,
            'flight_cash'        =>  $flight_cash ?? 0,
            'rentalcar_cash'        =>  $rentalcar_cash ?? 0,
            'hotel_cash'        =>  $hotel_cash ?? 0,
            'toll_cash'        =>  $toll_cash ?? 0,
            'gasoline_cash'        =>  $gasoline_cash ?? 0,
            'taxi_cash'        =>  $taxi_cash ?? 0,
            'train_cash'        =>  $train_cash ?? 0,
            'tax_cash'        =>  $tax_cash ?? 0,
            'others_cash'        =>  $others_cash ?? 0,
        ); 
    
        Reimbursement::whereId($id_main)->update($form_report);
      
        if ($sendSubmissionNotifications && $status === 0) {
              $data = Reimbursement::find($id_main);
              $user = \App\User::where('id', $data->id_user)->first();
              $curl = \Curl::to('https://api.fonnte.com/send')
                  ->withHeaders(['Authorization: ' . config('services.fonnte.token')])
                  ->withData([
                      'target' => FonnteMessenger::normalizePhone($user->phoneNumber),
                      'message' =>
                          "Hai *" .
                          $user->name .
                          "*,\n\nPengajuan reimbursement Anda dengan nomor *" .
                          $data->no_reimbursement .
                          "* sebesar *Rp " .
                          number_format($data->nominal_pengajuan, 0, ',', '.') .
                          "* telah diajukan kembali.\n\nSaat ini sedang menunggu Proses Verifikasi oleh Head Department.\n\nTerima kasih.
                          \n\nKlik untuk melihat detail pengajuan : " .
                          url('/reimbursement-travel/' . $data->id),
                  ])->post();

              $dirops = \App\User::where('jabatan', 'Direktur Operasional')->where(function ($query) use ($user) {
                      $query->where('departmentId', $user->departmentId)->orWhere('departmentId', null);
                      })->get();

              $id_approval  = $user->id_approval;
              $approval = DB::select(DB::raw("SELECT * FROM users WHERE id='$id_approval'"));

              if (!empty($approval)) {
                  $curl = \Curl::to('https://api.fonnte.com/send')
                      ->withHeaders(['Authorization: ' . config('services.fonnte.token')])
                      ->withData([
                          'target' => FonnteMessenger::normalizePhone($approval[0]->phoneNumber),
                          'message' =>
                              "Hai *" .
                              $approval[0]->name .
                              "*,\n\nPengajuan reimbursement nama *".$user->name."* dengan nomor *" .
                              $data->no_reimbursement .
                              "* sebesar *Rp " .
                              number_format($data->nominal_pengajuan, 0, ',', '.') .
                              "* telah diajukan kembali.\n\nSaat ini sedang menunggu Proses *Verifikasi Anda*.\n\nTerima kasih.\n\nKlik untuk melihat detail pengajuan : " .
                              url('/reimbursement-travel/' . $data->id),
                      ])->post();
              }
        }
            
        return $return;
    }

    public function updateItemApproval(Request $request, $id_main, $id_travel)
    {
        $id_travel = $this->resolveActiveTravelId($request, (int) $id_main, (int) $id_travel);

        $currentStatus = (int) (Reimbursement::whereId($id_main)->value('status') ?? 0);
        $user = auth()->user();
        $jabatan = (string) $user->jabatan;

        if ($jabatan === 'Direktur Operasional') {
            $status = 1;
        } elseif (in_array($jabatan, ['Finance', 'HR', 'HR GA'], true)) {
            $status = 2;
        } elseif ($jabatan === 'Finance Supervisor' && $currentStatus === 2) {
            $status = 11;
        } elseif (in_array($jabatan, ['Finance Manager', 'Owner'], true) && $currentStatus === 11) {
            $status = 3;
        } elseif ($jabatan === 'Owner' && $currentStatus === 2) {
            $status = 3;
        } else {
            $status = $currentStatus > 0 ? $currentStatus : 3;
        }

        $this->validateTravelReimbursementItemRequest($request, $this->travelItemAllowIncompleteForm());

        $remark = $request->remark;
        $reimbursement_department_id = $request->reimbursement_department_id;

        //Update table reimbursement

        $form_data = array(
            'remark'        =>  $request->remark,
            'reimbursement_department_id'        =>  $request->reimbursement_department_id,
            'date'        =>  $request->date,
        );

        Reimbursement::whereId($id_main)->update($form_data);

        $this->syncTripRatesFromMainForm($request, (int) $id_main);

        //Update table  reimbursement_travel

        $form_data = array(
            'date'        =>  $request->date,
            'purpose'        =>  $request->purpose,
            'trip_type_id'        =>  $this->normalizeTripTypeId($request->trip_type_id),
            'hotel_condition_id'        =>  $this->normalizeHotelConditionId($request->hotel_condition_id, $request->trip_type_id),
            'start_time'        =>  $this->normalizeTravelTime($request->start_time, $request->trip_type_id),
            'end_time'        =>  $this->normalizeTravelTime($request->end_time, $request->trip_type_id),
            'allowance'        =>  $this->resolveTravelAllowanceForSave($request, (int) $id_main),
            'total'        =>  $this->normalizeTravelMoneyValue($request->nominal_pengajuan ?? ''),
        );

        ReimbursementTravel::where('id', $id_travel)->update($form_data);

        //Update table  reimbursement_travel_details

        $currencies = is_array($request->currency) ? $request->currency : [];
        $count_ = count($currencies);

        $id_detail = $id_travel;
        DB::select( DB::raw("UPDATE reimbursement_travel_details SET status=0  WHERE reimbursement_travel_id = '$id_detail'"));

        for ($i=0; $i < $count_; $i++) {
            $costTypeId = isset($request->cost_type_id[$i]) ? trim((string) $request->cost_type_id[$i]) : '';
            if ($costTypeId === '') {
                continue;
            }

            $oldDetailId = 0;
            $legacyEvidence = '';
            $id_detail_ = $request->id_detail[$i] ?? '';
            if ($id_detail_ !== '' && ctype_digit((string) $id_detail_)) {
                $oldDetailId = (int) $id_detail_;
                $rowEv = DB::select(
                    'SELECT evidence FROM reimbursement_travel_details WHERE id = ? LIMIT 1',
                    [$oldDetailId]
                );
                $legacyEvidence = !empty($rowEv) ? ($rowEv[0]->evidence ?? '') : '';
            }

            $new = new ReimbursementTravelDetail;
            $new->reimbursement_id = $id_main;
            $new->reimbursement_travel_id = $id_detail;
            $new->cost_type_id = (int) $costTypeId;
            $new->destination = $request->destination[$i] ?? '';
            $new->payment_type = $request->payment_type[$i] ?? '';
            $new->currency = $request->currency[$i] ?? '';
            $new->idr_rate = $this->normalizeTravelMoneyValue($request->idr_rate[$i] ?? '');
            $new->amount = $this->normalizeTravelAmountInteger($request->amount[$i] ?? '');
            $new->tax = $this->normalizeTravelMoneyValue($request->tax[$i] ?? '0');
            $new->evidence = '';
            $new->status = 1;
            $new->save();

            $allAttachmentNames = $this->syncAttachmentsFromPreviousDetail(
                $request,
                $i,
                (int) $id_main,
                'travel',
                'reimbursement_travel_details',
                $oldDetailId,
                (int) $new->id,
                $legacyEvidence
            );
            $new->evidence = $allAttachmentNames[0] ?? '';
            $new->save();
        }

        $delete  = DB::select( DB::raw("DELETE FROM reimbursement_travel_details WHERE reimbursement_travel_id = '$id_detail' AND status=0"));

        $this->syncTravelDayTotalAfterDetailsSave((int) $id_detail);

        $total  = DB::select( DB::raw("SELECT sum(total) as total FROM reimbursement_travel WHERE reimbursement_id='$id_main'"))['0']->total;

        $form_data = array(
            'status'        =>  $status,
            'nominal_pengajuan' =>  $this->normalizeTravelMoneyValue($total ?? ''),
        );

        if ($status === 1 && $jabatan === 'Direktur Operasional') {
            $form_data['mengetahui_op'] = $user->name;
        } elseif ($status === 2 && in_array($jabatan, ['Finance', 'HR', 'HR GA'], true)) {
            $form_data['mengetahui_finance'] = $user->name;
        } elseif ($status === 11 && $jabatan === 'Finance Supervisor') {
            $form_data['menyetujui_finance_supervisor'] = $user->name;
        } elseif ($status === 3 && in_array($jabatan, ['Finance Manager', 'Owner'], true)) {
            $form_data['mengetahui_owner'] = $user->name;
        }

        Reimbursement::where('id', $id_main)->update($form_data);


        $id_max = DB::select( DB::raw("SELECT max(id) as id_max FROM reimbursement_travel WHERE reimbursement_id='$id_travel'"))['0']->id_max;
        $travel_type = DB::select( DB::raw("SELECT travel_type FROM reimbursement WHERE id='$id_main'"))['0']->travel_type;

        if ($travel_type=='Domestic') {
            $allowance = DB::select( DB::raw("SELECT sum(allowance) AS total FROM reimbursement_travel WHERE reimbursement_id='$id_main'"))['0']->total;
        } else {
            // $allowance_ = DB::select( DB::raw("SELECT sum(allowance) AS total FROM reimbursement_travel WHERE reimbursement_id='$id_main'"))['0']->total;
            // $rate = DB::select( DB::raw("SELECT rate FROM travel_trip_rates WHERE reimbursement_id='$id_main' AND currency='USD'"))['0']->rate;
            // $allowance = $allowance_ * $rate; 
            $allowance = DB::select( DB::raw("SELECT sum(allowance) AS total FROM reimbursement_travel WHERE reimbursement_id='$id_main'"))['0']->total;

        }

        $total_bdc  = DB::select( DB::raw("SELECT sum(idr_rate) AS total FROM reimbursement_travel_details WHERE reimbursement_id='$id_main' AND payment_type='BDC'"))['0']->total;
        $allowance_bdc = 0;
        $simcard_bdc  = DB::select( DB::raw("SELECT sum(idr_rate) AS total FROM reimbursement_travel_details WHERE reimbursement_id='$id_main' AND payment_type='BDC' AND cost_type_id = 8"))['0']->total;
        $flight_bdc  = DB::select( DB::raw("SELECT sum(idr_rate) AS total FROM reimbursement_travel_details WHERE reimbursement_id='$id_main' AND payment_type='BDC' AND cost_type_id = 4"))['0']->total;
        $rentalcar_bdc  = DB::select( DB::raw("SELECT sum(idr_rate) AS total FROM reimbursement_travel_details WHERE reimbursement_id='$id_main' AND payment_type='BDC' AND cost_type_id = 3"))['0']->total;
        $hotel_bdc  = DB::select( DB::raw("SELECT sum(idr_rate) AS total FROM reimbursement_travel_details WHERE reimbursement_id='$id_main' AND payment_type='BDC' AND cost_type_id = 1"))['0']->total;
        $toll_bdc  = DB::select( DB::raw("SELECT sum(idr_rate) AS total FROM reimbursement_travel_details WHERE reimbursement_id='$id_main' AND payment_type='BDC' AND cost_type_id = 5"))['0']->total;
        $gasoline_bdc  = DB::select( DB::raw("SELECT sum(idr_rate) AS total FROM reimbursement_travel_details WHERE reimbursement_id='$id_main' AND payment_type='BDC' AND cost_type_id = 7"))['0']->total;
        $taxi_bdc  = DB::select( DB::raw("SELECT sum(idr_rate) AS total FROM reimbursement_travel_details WHERE reimbursement_id='$id_main' AND payment_type='BDC' AND cost_type_id = 2"))['0']->total;
        $train_bdc  = DB::select( DB::raw("SELECT sum(idr_rate) AS total FROM reimbursement_travel_details WHERE reimbursement_id='$id_main' AND payment_type='BDC' AND cost_type_id = 6"))['0']->total;
        $tax_bdc  = DB::select( DB::raw("SELECT sum(tax) AS total FROM reimbursement_travel_details WHERE reimbursement_id='$id_main' AND payment_type='BDC'"))['0']->total;
        $others_bdc  = DB::select( DB::raw("SELECT sum(idr_rate) AS total FROM reimbursement_travel_details WHERE reimbursement_id='$id_main' AND payment_type='BDC' AND cost_type_id = 9"))['0']->total;

        $total_cash  = DB::select( DB::raw("SELECT sum(idr_rate) AS total FROM reimbursement_travel_details WHERE reimbursement_id='$id_main' AND payment_type='Cash'"))['0']->total;
        $allowance_cash = $allowance;
        $simcard_cash  = DB::select( DB::raw("SELECT sum(idr_rate) AS total FROM reimbursement_travel_details WHERE reimbursement_id='$id_main' AND payment_type='Cash' AND cost_type_id = 8"))['0']->total;
        $flight_cash  = DB::select( DB::raw("SELECT sum(idr_rate) AS total FROM reimbursement_travel_details WHERE reimbursement_id='$id_main' AND payment_type='Cash' AND cost_type_id = 4"))['0']->total;
        $rentalcar_cash  = DB::select( DB::raw("SELECT sum(idr_rate) AS total FROM reimbursement_travel_details WHERE reimbursement_id='$id_main' AND payment_type='Cash' AND cost_type_id = 3"))['0']->total;
        $hotel_cash  = DB::select( DB::raw("SELECT sum(idr_rate) AS total FROM reimbursement_travel_details WHERE reimbursement_id='$id_main' AND payment_type='Cash' AND cost_type_id = 1"))['0']->total;
        $toll_cash  = DB::select( DB::raw("SELECT sum(idr_rate) AS total FROM reimbursement_travel_details WHERE reimbursement_id='$id_main' AND payment_type='Cash' AND cost_type_id = 5"))['0']->total;
        $gasoline_cash  = DB::select( DB::raw("SELECT sum(idr_rate) AS total FROM reimbursement_travel_details WHERE reimbursement_id='$id_main' AND payment_type='Cash' AND cost_type_id = 7"))['0']->total;
        $taxi_cash  = DB::select( DB::raw("SELECT sum(idr_rate) AS total FROM reimbursement_travel_details WHERE reimbursement_id='$id_main' AND payment_type='Cash' AND cost_type_id = 2"))['0']->total;
        $train_cash  = DB::select( DB::raw("SELECT sum(idr_rate) AS total FROM reimbursement_travel_details WHERE reimbursement_id='$id_main' AND payment_type='Cash' AND cost_type_id = 6"))['0']->total;
        $tax_cash  = DB::select( DB::raw("SELECT sum(tax) AS total FROM reimbursement_travel_details WHERE reimbursement_id='$id_main' AND payment_type='Cash'"))['0']->total;
        $others_cash  = DB::select( DB::raw("SELECT sum(idr_rate) AS total FROM reimbursement_travel_details WHERE reimbursement_id='$id_main' AND payment_type='Cash' AND cost_type_id = 9"))['0']->total;

        $form_report = array(
            'total_bdc'        =>  $total_bdc ?? 0,
            'allowance_bdc'        =>  $allowance_bdc,
            'simcard_bdc'        =>  $simcard_bdc ?? 0,
            'flight_bdc'        =>  $flight_bdc ?? 0,
            'rentalcar_bdc'        =>  $rentalcar_bdc ?? 0,
            'hotel_bdc'        =>  $hotel_bdc ?? 0,
            'toll_bdc'        =>  $toll_bdc ?? 0,
            'gasoline_bdc'        =>  $gasoline_bdc ?? 0,
            'taxi_bdc'        =>  $taxi_bdc ?? 0,
            'train_bdc'        =>  $train_bdc ?? 0,
            'tax_bdc'        =>  $tax_bdc ?? 0,
            'others_bdc'        =>  $others_bdc ?? 0,
            'total_cash'        =>  $total_cash ?? 0,
            'allowance_cash'        =>  $allowance_cash,
            'simcard_cash'        =>  $simcard_cash ?? 0,
            'flight_cash'        =>  $flight_cash ?? 0,
            'rentalcar_cash'        =>  $rentalcar_cash ?? 0,
            'hotel_cash'        =>  $hotel_cash ?? 0,
            'toll_cash'        =>  $toll_cash ?? 0,
            'gasoline_cash'        =>  $gasoline_cash ?? 0,
            'taxi_cash'        =>  $taxi_cash ?? 0,
            'train_cash'        =>  $train_cash ?? 0,
            'tax_cash'        =>  $tax_cash ?? 0,
            'others_cash'        =>  $others_cash ?? 0,
        ); 
    
        Reimbursement::whereId($id_main)->update($form_report);
        
        $data = Reimbursement::find($id_main);
        $applicant = \App\User::where('id', $data->id_user)->first();

        if (auth()->user()->jabatan=='Direktur Operasional') {
            $curl = \Curl::to('https://api.fonnte.com/send')
            ->withHeaders(['Authorization: ' . config('services.fonnte.token')])
            ->withData([
                'target' => FonnteMessenger::normalizePhone($applicant->phoneNumber),
                'message' =>
                    "Hai *" .
                    $applicant->name .
                    "*,\n\nPengajuan reimbursement Anda dengan nomor *" .
                    $data->no_reimbursement .
                    "* sebesar *Rp " .
                    number_format($data->nominal_pengajuan, 0, ',', '.') .
                    "* telah diterima oleh *" .
                    auth()->user()->name  .
                    " (Head Department)* .\n\nSaat ini sedang menunggu Proses Verifikasi oleh HR GA.\n\nTerima kasih.
                       \n\nKlik untuk melihat detail pengajuan : " .
                    url('/reimbursement-driver/' . $data->id),
            ])
            ->post();

            $hr_ga = DB::select(DB::raw("SELECT * FROM users WHERE jabatan='Finance'"));

            foreach($hr_ga as $hr) {

                $curl = \Curl::to('https://api.fonnte.com/send')
                    ->withHeaders(['Authorization: ' . config('services.fonnte.token')])
                    ->withData([
                        'target' => FonnteMessenger::normalizePhone($hr->phoneNumber),
                        'message' =>
                            "Hai *" .
                            $hr->name .
                            "*,\n\nPengajuan reimbursement nama *".$applicant->name."* dengan nomor *" .
                            $data->no_reimbursement .
                            "* sebesar *Rp " .
                            number_format($data->nominal_pengajuan, 0, ',', '.') .
                            "* telah diterima oleh Head Department.\n\nSaat ini sedang menunggu Proses Verifikasi Anda.\n\nTerima kasih.
                             \n\nKlik untuk melihat detail pengajuan : " .
                            url('/reimbursement-driver/' . $data->id),
                    ])
                    ->post();
            }
        }

        if (auth()->user()->jabatan=='Finance' || auth()->user()->jabatan=='Finance Supervisor') {
            $curl = \Curl::to('https://api.fonnte.com/send')
            ->withHeaders(['Authorization: ' . config('services.fonnte.token')])
            ->withData([
                'target' => FonnteMessenger::normalizePhone($applicant->phoneNumber),
                'message' =>
                    "Hai *" .
                    $applicant->name .
                    "*,\n\nPengajuan reimbursement Anda dengan nomor *" .
                    $data->no_reimbursement .
                    "* sebesar *Rp " .
                    number_format($data->nominal_pengajuan, 0, ',', '.') .
                    "* telah diterima oleh *" .
                    auth()->user()->name  .
                    " (HR GA)* .\n\nSaat ini sedang menunggu Proses Verifikasi oleh Finance Supervisor.\n\nTerima kasih.
                       \n\nKlik untuk melihat detail pengajuan : " .
                    url('/reimbursement-driver/' . $data->id),
            ])
            ->post();

            $finance = DB::select(DB::raw("SELECT * FROM users WHERE jabatan='Owner'"));

            foreach($finance as $fn) {

                $curl = \Curl::to('https://api.fonnte.com/send')
                    ->withHeaders(['Authorization: ' . config('services.fonnte.token')])
                    ->withData([
                        'target' => FonnteMessenger::normalizePhone($fn->phoneNumber),
                        'message' =>
                            "Hai *" .
                            $fn->name .
                            "*,\n\nPengajuan reimbursement nama *".$applicant->name."* dengan nomor *" .
                            $data->no_reimbursement .
                            "* sebesar *Rp " .
                            number_format($data->nominal_pengajuan, 0, ',', '.') .
                            "* telah diterima oleh Finance.\n\nSaat ini sedang menunggu Proses Verifikasi Anda.\n\nTerima kasih.
                             \n\nKlik untuk melihat detail pengajuan : " .
                            url('/reimbursement-driver/' . $data->id),
                    ])
                    ->post();
            }
        }

        if (auth()->user()->jabatan=='Owner') {
            $curl = \Curl::to('https://api.fonnte.com/send')
            ->withHeaders(['Authorization: ' . config('services.fonnte.token')])
            ->withData([
                'target' => FonnteMessenger::normalizePhone($applicant->phoneNumber),
                'message' =>
                    "Hai *" .
                    $applicant->name .
                    "*,\n\nPengajuan reimbursement Anda dengan nomor *" .
                    $data->no_reimbursement .
                    "* sebesar *Rp " .
                    number_format($data->nominal_pengajuan, 0, ',', '.') .
                    "* telah diterima oleh *" .
                    auth()->user()->name  .
                    " (Finance)* .\n\nSaat ini sedang menunggu Proses Pencairan oleh Finance.\n\nTerima kasih.
                       \n\nKlik untuk melihat detail pengajuan : " .
                    url('/reimbursement-driver/' . $data->id),
            ])
            ->post();

            $finance = DB::select(DB::raw("SELECT * FROM users WHERE jabatan='Owner'"));

            foreach($finance as $fn) {

                $curl = \Curl::to('https://api.fonnte.com/send')
                    ->withHeaders(['Authorization: ' . config('services.fonnte.token')])
                    ->withData([
                        'target' => FonnteMessenger::normalizePhone($fn->phoneNumber),
                        'message' =>
                            "Hai *" .
                            $fn->name .
                            "*,\n\nPengajuan reimbursement nama *".$applicant->name."* dengan nomor *" .
                            $data->no_reimbursement .
                            "* sebesar *Rp " .
                            number_format($data->nominal_pengajuan, 0, ',', '.') .
                            "* telah disetujui oleh Finance.\n\nSilahkan lakukan proses Pencairan.\n\nTerima kasih.
                             \n\nKlik untuk melihat detail pengajuan : " .
                            url('/reimbursement-driver/' . $data->id),
                    ])
                    ->post();
            }
        }
        
        return redirect('reimbursement-travel-approval')->with(['success' => "Reimbursement Successfully Submitted"]);;
    }

    
    
    function approve(Request $requset, $id) {
        $data = Reimbursement::find($id);
        if(!$data)
            return redirect()->back()->withErrors(['Reimbursement tidak ditemukan']);

        if ((int) auth()->id() === (int) $data->id_user) {
            return redirect()->back()->withErrors(['Anda tidak dapat menyetujui pengajuan reimbursement yang Anda buat sendiri.']);
        }

        $user = auth()->user();
        if($data->status == 0 && $user->jabatan == "Direktur Operasional") {
            if (!$user->isHeadDeptApproverForSubmitter((int) $data->id_user)) {
                return redirect()->back()->withErrors(['Anda bukan Head Department yang ditunjuk untuk pengajuan ini.']);
            }
            $data->update([
                'status' => 1,
                'mengetahui_op' => $user->name
            ]);
        }
        if($data->status == 1 && in_array($user->jabatan, ["Finance", "HR", "HR GA", "superadmin"], true)) {
            $data->update([
                'status' => 2,
                'mengetahui_finance' => $user->name
            ]);
        }
        if ($data->status == 2 && $user->jabatan === 'Finance Supervisor') {
            $data->update([
                'status' => 11,
                'menyetujui_finance_supervisor' => $user->name,
            ]);
        } elseif ($data->status == 2 && in_array($user->jabatan, ['Owner', 'superadmin'], true)) {
            $data->update([
                'status' => 3,
                'mengetahui_owner' => $user->name,
            ]);
        } elseif ($data->status == 11 && in_array($user->jabatan, ['Finance Manager', 'Owner', 'superadmin'], true)) {
            $data->update([
                'status' => 3,
                'mengetahui_owner' => $user->name,
            ]);
        }
        ActivityLogger::log(
            'reimbursement-travel',
            'approve',
            'Reimbursement travel disetujui',
            $data->no_reimbursement,
            'reimbursement',
            $data->id,
            ['status' => $data->status]
        );
        return redirect()->back()->with(['success' => "Berhasil disetujui"]);
    }

    function print(Request $request) {

        if (isset($request->selected)) {

            $selected =  explode(',', $request->selected);
            $data = Reimbursement::select('*', 'reimbursement.date AS tgl')->orderBy('reimbursement.no_reimbursement','desc')->whereIn('id', $selected);
            $head_dept = $data->first()->mengetahui_op;
          
            $bdc = Reimbursement::selectRaw('SUM(total_bdc) as total')->whereIn('id', $selected);
            $allowance_bdc = Reimbursement::selectRaw('SUM(allowance_bdc) as total')->whereIn('id', $selected); 
            $simcard_bdc = Reimbursement::selectRaw('SUM(simcard_bdc) as total')->whereIn('id', $selected); 
            $flight_bdc = Reimbursement::selectRaw('SUM(flight_bdc) as total')->whereIn('id', $selected); 
            $rentalcar_bdc = Reimbursement::selectRaw('SUM(rentalcar_bdc) as total')->whereIn('id', $selected); 
            $hotel_bdc = Reimbursement::selectRaw('SUM(hotel_bdc) as total')->whereIn('id', $selected); 
            $toll_bdc = Reimbursement::selectRaw('SUM(toll_bdc) as total')->whereIn('id', $selected); 
            $gasoline_bdc = Reimbursement::selectRaw('SUM(gasoline_bdc) as total')->whereIn('id', $selected); 
            $taxi_bdc = Reimbursement::selectRaw('SUM(taxi_bdc) as total')->whereIn('id', $selected); 
            $train_bdc = Reimbursement::selectRaw('SUM(train_bdc) as total')->whereIn('id', $selected); 
            $tax_bdc = Reimbursement::selectRaw('SUM(tax_bdc) as total')->whereIn('id', $selected); 
            $others_bdc = Reimbursement::selectRaw('SUM(others_bdc) as total')->whereIn('id', $selected); 
            $total_cash = Reimbursement::selectRaw('SUM(total_cash) as total')->whereIn('id', $selected); 
            $allowance_cash = Reimbursement::selectRaw('SUM(allowance_cash) as total')->whereIn('id', $selected); 
            $simcard_cash = Reimbursement::selectRaw('SUM(simcard_cash) as total')->whereIn('id', $selected); 
            $flight_cash = Reimbursement::selectRaw('SUM(flight_cash) as total')->whereIn('id', $selected); 
            $rentalcar_cash = Reimbursement::selectRaw('SUM(rentalcar_cash) as total')->whereIn('id', $selected); 
            $hotel_cash = Reimbursement::selectRaw('SUM(hotel_cash) as total')->whereIn('id', $selected); 
            $toll_cash = Reimbursement::selectRaw('SUM(toll_cash) as total')->whereIn('id', $selected); 
            $gasoline_cash = Reimbursement::selectRaw('SUM(gasoline_cash) as total')->whereIn('id', $selected); 
            $taxi_cash = Reimbursement::selectRaw('SUM(taxi_cash) as total')->whereIn('id', $selected); 
            $train_cash = Reimbursement::selectRaw('SUM(train_cash) as total')->whereIn('id', $selected); 
            $tax_cash = Reimbursement::selectRaw('SUM(tax_cash) as total')->whereIn('id', $selected); 
            $others_cash = Reimbursement::selectRaw('SUM(others_cash) as total')->whereIn('id', $selected); 


        } else {

            $data = Reimbursement::select('*', 'reimbursement.date AS tgl')->orderBy('reimbursement.no_reimbursement','desc');
            $id_user = $_GET['driver'];
            $head_dept = DB::select( DB::raw("SELECT nama_approval FROM users WHERE id = '$id_user'"))['0']->nama_approval;
            $bdc = Reimbursement::selectRaw('SUM(total_bdc) as total');
            $allowance_bdc = Reimbursement::selectRaw('SUM(allowance_bdc) as total'); 
            $simcard_bdc = Reimbursement::selectRaw('SUM(simcard_bdc) as total'); 
            $flight_bdc = Reimbursement::selectRaw('SUM(flight_bdc) as total'); 
            $rentalcar_bdc = Reimbursement::selectRaw('SUM(rentalcar_bdc) as total'); 
            $hotel_bdc = Reimbursement::selectRaw('SUM(hotel_bdc) as total'); 
            $toll_bdc = Reimbursement::selectRaw('SUM(toll_bdc) as total'); 
            $gasoline_bdc = Reimbursement::selectRaw('SUM(gasoline_bdc) as total'); 
            $taxi_bdc = Reimbursement::selectRaw('SUM(taxi_bdc) as total'); 
            $train_bdc = Reimbursement::selectRaw('SUM(train_bdc) as total'); 
            $tax_bdc = Reimbursement::selectRaw('SUM(tax_bdc) as total'); 
            $others_bdc = Reimbursement::selectRaw('SUM(others_bdc) as total'); 
            $total_cash = Reimbursement::selectRaw('SUM(total_cash) as total'); 
            $allowance_cash = Reimbursement::selectRaw('SUM(allowance_cash) as total'); 
            $simcard_cash = Reimbursement::selectRaw('SUM(simcard_cash) as total'); 
            $flight_cash = Reimbursement::selectRaw('SUM(flight_cash) as total'); 
            $rentalcar_cash = Reimbursement::selectRaw('SUM(rentalcar_cash) as total'); 
            $hotel_cash = Reimbursement::selectRaw('SUM(hotel_cash) as total'); 
            $toll_cash = Reimbursement::selectRaw('SUM(toll_cash) as total'); 
            $gasoline_cash = Reimbursement::selectRaw('SUM(gasoline_cash) as total'); 
            $taxi_cash = Reimbursement::selectRaw('SUM(taxi_cash) as total'); 
            $train_cash = Reimbursement::selectRaw('SUM(train_cash) as total'); 
            $tax_cash = Reimbursement::selectRaw('SUM(tax_cash) as total'); 
            $others_cash = Reimbursement::selectRaw('SUM(others_cash) as total'); 
           

            
            if(isset($request->start))
                $data = $data->whereDate('reimbursement.created_at','>=',$request->start);
                $bdc = $bdc->whereDate('reimbursement.created_at','>=',$request->start);
                $allowance_bdc = $allowance_bdc->whereDate('reimbursement.created_at','>=',$request->start);
                $simcard_bdc = $simcard_bdc->whereDate('reimbursement.created_at','>=',$request->start);
                $flight_bdc = $flight_bdc->whereDate('reimbursement.created_at','>=',$request->start);
                $rentalcar_bdc = $rentalcar_bdc->whereDate('reimbursement.created_at','>=',$request->start);
                $hotel_bdc = $hotel_bdc->whereDate('reimbursement.created_at','>=',$request->start);
                $toll_bdc = $toll_bdc->whereDate('reimbursement.created_at','>=',$request->start);
                $gasoline_bdc = $gasoline_bdc->whereDate('reimbursement.created_at','>=',$request->start);
                $taxi_bdc = $taxi_bdc->whereDate('reimbursement.created_at','>=',$request->start);
                $train_bdc = $train_bdc->whereDate('reimbursement.created_at','>=',$request->start);
                $tax_bdc = $tax_bdc->whereDate('reimbursement.created_at','>=',$request->start);
                $others_bdc = $others_bdc->whereDate('reimbursement.created_at','>=',$request->start);
                $total_cash = $total_cash->whereDate('reimbursement.created_at','>=',$request->start);
                $allowance_cash = $allowance_cash->whereDate('reimbursement.created_at','>=',$request->start);
                $simcard_cash = $simcard_cash->whereDate('reimbursement.created_at','>=',$request->start);
                $flight_cash = $flight_cash->whereDate('reimbursement.created_at','>=',$request->start);
                $rentalcar_cash = $rentalcar_cash->whereDate('reimbursement.created_at','>=',$request->start);
                $hotel_cash = $hotel_cash->whereDate('reimbursement.created_at','>=',$request->start);
                $toll_cash = $toll_cash->whereDate('reimbursement.created_at','>=',$request->start);
                $gasoline_cash = $gasoline_cash->whereDate('reimbursement.created_at','>=',$request->start);
                $taxi_cash = $taxi_cash->whereDate('reimbursement.created_at','>=',$request->start);
                $train_cash = $train_cash->whereDate('reimbursement.created_at','>=',$request->start);
                $tax_cash = $tax_cash->whereDate('reimbursement.created_at','>=',$request->start);
                $others_cash = $others_cash->whereDate('reimbursement.created_at','>=',$request->start);

                
            if(isset($request->end))
                $data = $data->whereDate('reimbursement.created_at','<=',$request->end);
                $bdc = $bdc->whereDate('reimbursement.created_at','<=',$request->end);
                $allowance_bdc = $allowance_bdc->whereDate('reimbursement.created_at','<=',$request->end);
                $simcard_bdc = $simcard_bdc->whereDate('reimbursement.created_at','<=',$request->end);
                $flight_bdc = $flight_bdc->whereDate('reimbursement.created_at','<=',$request->end);
                $rentalcar_bdc = $rentalcar_bdc->whereDate('reimbursement.created_at','<=',$request->end);
                $hotel_bdc = $hotel_bdc->whereDate('reimbursement.created_at','<=',$request->end);
                $toll_bdc = $toll_bdc->whereDate('reimbursement.created_at','<=',$request->end);
                $gasoline_bdc = $gasoline_bdc->whereDate('reimbursement.created_at','<=',$request->end);
                $taxi_bdc = $taxi_bdc->whereDate('reimbursement.created_at','<=',$request->end);
                $train_bdc = $train_bdc->whereDate('reimbursement.created_at','<=',$request->end);
                $tax_bdc = $tax_bdc->whereDate('reimbursement.created_at','<=',$request->end);
                $others_bdc = $others_bdc->whereDate('reimbursement.created_at','<=',$request->end);
                $total_cash = $total_cash->whereDate('reimbursement.created_at','<=',$request->end);
                $allowance_cash = $allowance_cash->whereDate('reimbursement.created_at','<=',$request->end);
                $simcard_cash = $simcard_cash->whereDate('reimbursement.created_at','<=',$request->end);
                $flight_cash = $flight_cash->whereDate('reimbursement.created_at','<=',$request->end);
                $rentalcar_cash = $rentalcar_cash->whereDate('reimbursement.created_at','<=',$request->end);
                $hotel_cash = $hotel_cash->whereDate('reimbursement.created_at','<=',$request->end);
                $toll_cash = $toll_cash->whereDate('reimbursement.created_at','<=',$request->end);
                $gasoline_cash = $gasoline_cash->whereDate('reimbursement.created_at','<=',$request->end);
                $taxi_cash = $taxi_cash->whereDate('reimbursement.created_at','<=',$request->end);
                $train_cash = $train_cash->whereDate('reimbursement.created_at','<=',$request->end);
                $tax_cash = $tax_cash->whereDate('reimbursement.created_at','<=',$request->end);
                $others_cash = $others_cash->whereDate('reimbursement.created_at','<=',$request->end);

            if(isset($request->status) && $request->status != "" && $request->status != "ALL") {
                $data = $data->where('reimbursement.status',$request->status);
                $bdc = $bdc->where('reimbursement.status',$request->status);
                $allowance_bdc = $allowance_bdc->where('reimbursement.status',$request->status);
                $simcard_bdc = $simcard_bdc->where('reimbursement.status',$request->status);
                $flight_bdc = $flight_bdc->where('reimbursement.status',$request->status);
                $rentalcar_bdc = $rentalcar_bdc->where('reimbursement.status',$request->status);
                $hotel_bdc = $hotel_bdc->where('reimbursement.status',$request->status);
                $toll_bdc = $toll_bdc->where('reimbursement.status',$request->status);
                $gasoline_bdc = $gasoline_bdc->where('reimbursement.status',$request->status);
                $taxi_bdc = $taxi_bdc->where('reimbursement.status',$request->status);
                $train_bdc = $train_bdc->where('reimbursement.status',$request->status);
                $tax_bdc = $tax_bdc->where('reimbursement.status',$request->status);
                $others_bdc = $others_bdc->where('reimbursement.status',$request->status);
                $total_cash = $total_cash->where('reimbursement.status',$request->status);
                $allowance_cash = $allowance_cash->where('reimbursement.status',$request->status);
                $simcard_cash = $simcard_cash->where('reimbursement.status',$request->status);
                $flight_cash = $flight_cash->where('reimbursement.status',$request->status);
                $rentalcar_cash = $rentalcar_cash->where('reimbursement.status',$request->status);
                $hotel_cash = $hotel_cash->where('reimbursement.status',$request->status);
                $toll_cash = $toll_cash->where('reimbursement.status',$request->status);
                $gasoline_cash = $gasoline_cash->where('reimbursement.status',$request->status);
                $taxi_cash = $taxi_cash->where('reimbursement.status',$request->status);
                $train_cash = $train_cash->where('reimbursement.status',$request->status);
                $tax_cash = $tax_cash->where('reimbursement.status',$request->status);
                $others_cash = $others_cash->where('reimbursement.status',$request->status);
            }

            if(isset($request->driver) && $request->driver != "" && $request->driver != "null") {
                $data = $data->where('reimbursement.id_user','=',$request->driver);
                $bdc = $bdc->where('reimbursement.id_user','=',$request->driver);
                $allowance_bdc = $allowance_bdc->where('reimbursement.id_user','=',$request->driver);
                $simcard_bdc = $simcard_bdc->where('reimbursement.id_user','=',$request->driver);
                $flight_bdc = $flight_bdc->where('reimbursement.id_user','=',$request->driver);
                $rentalcar_bdc = $rentalcar_bdc->where('reimbursement.id_user','=',$request->driver);
                $hotel_bdc = $hotel_bdc->where('reimbursement.id_user','=',$request->driver);
                $toll_bdc = $toll_bdc->where('reimbursement.id_user','=',$request->driver);
                $gasoline_bdc = $gasoline_bdc->where('reimbursement.id_user','=',$request->driver);
                $taxi_bdc = $taxi_bdc->where('reimbursement.id_user','=',$request->driver);
                $train_bdc = $train_bdc->where('reimbursement.id_user','=',$request->driver);
                $tax_bdc = $tax_bdc->where('reimbursement.id_user','=',$request->driver);
                $others_bdc = $others_bdc->where('reimbursement.id_user','=',$request->driver);
                $total_cash = $total_cash->where('reimbursement.id_user','=',$request->driver);
                $allowance_cash = $allowance_cash->where('reimbursement.id_user','=',$request->driver);
                $simcard_cash = $simcard_cash->where('reimbursement.id_user','=',$request->driver);
                $flight_cash = $flight_cash->where('reimbursement.id_user','=',$request->driver);
                $rentalcar_cash = $rentalcar_cash->where('reimbursement.id_user','=',$request->driver);
                $hotel_cash = $hotel_cash->where('reimbursement.id_user','=',$request->driver);
                $toll_cash = $toll_cash->where('reimbursement.id_user','=',$request->driver);
                $gasoline_cash = $gasoline_cash->where('reimbursement.id_user','=',$request->driver);
                $taxi_cash = $taxi_cash->where('reimbursement.id_user','=',$request->driver);
                $train_cash = $train_cash->where('reimbursement.id_user','=',$request->driver);
                $tax_cash = $tax_cash->where('reimbursement.id_user','=',$request->driver);
                $others_cash = $others_cash->where('reimbursement.id_user','=',$request->driver);
            }
            
            if(auth()->user()->jabatan == 'karyawan') {
                $data = $data->where('reimbursement.id_user', auth()->user()->id);
                $bdc = $bdc->where('reimbursement.id_user', auth()->user()->id);
                $allowance_bdc = $allowance_bdc->where('reimbursement.id_user', auth()->user()->id);
                $simcard_bdc = $simcard_bdc->where('reimbursement.id_user', auth()->user()->id);
                $flight_bdc = $flight_bdc->where('reimbursement.id_user', auth()->user()->id);
                $rentalcar_bdc = $rentalcar_bdc->where('reimbursement.id_user', auth()->user()->id);
                $hotel_bdc = $hotel_bdc->where('reimbursement.id_user', auth()->user()->id);
                $toll_bdc = $toll_bdc->where('reimbursement.id_user', auth()->user()->id);
                $gasoline_bdc = $gasoline_bdc->where('reimbursement.id_user', auth()->user()->id);
                $taxi_bdc = $taxi_bdc->where('reimbursement.id_user', auth()->user()->id);
                $train_bdc = $train_bdc->where('reimbursement.id_user', auth()->user()->id);
                $tax_bdc = $tax_bdc->where('reimbursement.id_user', auth()->user()->id);
                $others_bdc = $others_bdc->where('reimbursement.id_user', auth()->user()->id);
                $total_cash = $total_cash->where('reimbursement.id_user', auth()->user()->id);
                $allowance_cash = $allowance_cash->where('reimbursement.id_user', auth()->user()->id);
                $simcard_cash = $simcard_cash->where('reimbursement.id_user', auth()->user()->id);
                $flight_cash = $flight_cash->where('reimbursement.id_user', auth()->user()->id);
                $rentalcar_cash = $rentalcar_cash->where('reimbursement.id_user', auth()->user()->id);
                $hotel_cash = $hotel_cash->where('reimbursement.id_user', auth()->user()->id);
                $toll_cash = $toll_cash->where('reimbursement.id_user', auth()->user()->id);
                $gasoline_cash = $gasoline_cash->where('reimbursement.id_user', auth()->user()->id);
                $taxi_cash = $taxi_cash->where('reimbursement.id_user', auth()->user()->id);
                $train_cash = $train_cash->where('reimbursement.id_user', auth()->user()->id);
                $tax_cash = $tax_cash->where('reimbursement.id_user', auth()->user()->id);
                $others_cash = $others_cash->where('reimbursement.id_user', auth()->user()->id);
            }
        }
      
        $printedRows = $data->get();
        if ($printedRows->count() === 0) {
            echo "Data not found. Please make sure the <strong>search button has been clicked first</strong>.";
        } else {
            foreach ($printedRows as $printedRow) {
                $this->repairTripRatesFromStoredAllowances((int) $printedRow->id);
                $this->recomputeAllTravelDayTotalsForReimbursement((int) $printedRow->id);
            }
            $printedRows = $data->get();

            $period = $this->travelPrintPeriodLabels($request, $printedRows);

            $driverRaw = $request->input('driver');
            $driverId = null;
            if ($driverRaw !== null && trim((string) $driverRaw) !== '' && strtolower(trim((string) $driverRaw)) !== 'null') {
                $driverId = (int) $driverRaw;
            }
            $printUser = $driverId > 0 ? User::find($driverId) : null;
            if (!$printUser && $printedRows->isNotEmpty()) {
                $printUser = User::find($printedRows->first()->id_user);
            }

            return view('print.travel-reimbursement', [
              'start_date' => $period['start_date'],
              'end_date' => $period['end_date'],
              'datas' => $printedRows,
              'head_dept' => $head_dept,
              'user' => $printUser,
              'bdc' => $bdc->get()['0']->total,
              'allowance_bdc' => $allowance_bdc->get()['0']->total,
              'simcard_bdc' => $simcard_bdc->get()['0']->total,
              'flight_bdc' => $flight_bdc->get()['0']->total,
              'rentalcar_bdc' => $rentalcar_bdc->get()['0']->total,
              'hotel_bdc' => $hotel_bdc->get()['0']->total,
              'toll_bdc' => $toll_bdc->get()['0']->total,
              'gasoline_bdc' => $gasoline_bdc->get()['0']->total,
              'taxi_bdc' => $taxi_bdc->get()['0']->total,
              'train_bdc' => $train_bdc->get()['0']->total,
              'tax_bdc' => $tax_bdc->get()['0']->total,
              'others_bdc' => $others_bdc->get()['0']->total,
              'total_cash' => $total_cash->get()['0']->total,
              'allowance_cash' => $allowance_cash->get()['0']->total,
              'simcard_cash' => $simcard_cash->get()['0']->total,
              'flight_cash' => $flight_cash->get()['0']->total,
              'rentalcar_cash' => $rentalcar_cash->get()['0']->total,
              'hotel_cash' => $hotel_cash->get()['0']->total,
              'toll_cash' => $toll_cash->get()['0']->total,
              'gasoline_cash' => $gasoline_cash->get()['0']->total,
              'taxi_cash' => $taxi_cash->get()['0']->total,
              'train_cash' => $train_cash->get()['0']->total,
              'tax_cash' => $tax_cash->get()['0']->total,
              'others_cash' => $others_cash->get()['0']->total

          ]);
          
        }
    }
    
    public function getCurrency($id, $cur)
    {
        $cur = strtoupper($cur);
        $row = TravelTripRate::where('reimbursement_id', $id)->where('currency', $cur)->first();
        if (!$row) {
            return response()->json(['message' => 'Kurs tidak ditemukan untuk ' . $cur], 404);
        }

        return response()->json(['data' => $row->rate]);
    }
    
    public function getTripType($id)
    {
        $data  = DB::select( DB::raw("SELECT allowance,type,currency FROM travel_trip_types WHERE id='$id'"));
        return response()->json(['data' => $data]);
    }
    
    public function getTripTypeOverseas($id)
    {
        $data  = DB::select( DB::raw("SELECT rate FROM travel_trip_types WHERE id='$id'"))['0']->rate;
        return response()->json(['data' => $data]);
    }
    
    public function getTravelTripRates($id)
    {
        $data  = DB::select( DB::raw("SELECT rate FROM travel_trip_rates WHERE reimbursement_id='$id' AND currency='USD'"))['0']->rate;
        return response()->json(['data' => $data]);
    }

    public function destroy($id)
    {
        $data = Reimbursement::where('id', $id)
            ->where('reimbursement_type', 2)
            ->first();

        if (!$data) {
            return redirect()->back()->withErrors(['Data reimbursement travel tidak ditemukan']);
        }

        $isOwner = (int) $data->id_user === (int) auth()->id();
        $isSuperadmin = auth()->user()->jabatan === 'superadmin';
        if (!$isOwner && !$isSuperadmin) {
            return redirect()->back()->withErrors(['Anda tidak memiliki akses untuk menghapus data ini']);
        }

        if (!in_array((int) $data->status, [0, 10], true)) {
            return redirect()->back()->withErrors(['Hanya pengajuan dengan status pending atau draft yang dapat dihapus']);
        }

        DB::beginTransaction();
        try {
            ActivityLogger::log(
                'reimbursement-travel',
                'delete',
                'Reimbursement travel dihapus',
                $data->no_reimbursement,
                'reimbursement',
                $data->id,
                ['status' => $data->status]
            );
            ReimbursementTravelDetail::where('reimbursement_id', $id)->delete();
            ReimbursementTravel::where('reimbursement_id', $id)->delete();
            TravelTripRate::where('reimbursement_id', $id)->delete();
            $data->delete();
            DB::commit();

            return redirect('reimbursement-travel')->with(['success' => 'Pengajuan berhasil dihapus']);
        } catch (\Throwable $e) {
            DB::rollBack();
            return redirect()->back()->withErrors(['Gagal menghapus pengajuan: ' . $e->getMessage()]);
        }
    }

    public function approveMultiple($id)
    {
        //if (auth()->user()->jabatan=='Direktur Operasional') {
        //    $status = 1;
        //} else if (auth()->user()->jabatan=='Finance') {
        //    $status = 2;
        //} else {
        //    $status = 3;
        //}
        //$idsArray = array_map('intval', explode(',', $id));
        //Reimbursement::whereIn('id', $idsArray)->update(['status' => $status]);
      
      	$idsArray = array_map('intval', explode(',', $id));
      	$user = auth()->user();
        $jab = $user->jabatan;

        $rows = Reimbursement::whereIn('id', $idsArray)->get();
        if ($rows->isEmpty()) {
            return response()->json(['message' => 'Tidak ada data'], 422);
        }
        if ($rows->pluck('status')->unique()->count() !== 1) {
            return response()->json(['message' => 'Pilih klaim dengan status yang sama.'], 422);
        }
        $bulkStatus = (int) $rows->first()->status;
        $status = $bulkStatus;

        $canBulk = ($bulkStatus === 0 && ($jab === 'Direktur Operasional' || $jab === 'superadmin'))
            || ($bulkStatus === 1 && ($jab === 'Finance' || $jab === 'HR' || $jab === 'HR GA' || $jab === 'superadmin'))
            || ($bulkStatus === 2 && ($jab === 'Finance Supervisor' || $jab === 'Owner' || $jab === 'superadmin'))
            || ($bulkStatus === 11 && ($jab === 'Finance Manager' || $jab === 'Owner' || $jab === 'superadmin'))
            || ($bulkStatus === 3 && ($jab === 'Owner' || $jab === 'superadmin'));
        if (!$canBulk) {
            return response()->json(['message' => 'Tidak dapat approve bulk untuk peran atau status ini.'], 422);
        }

        $ownClaimIds = $rows->where('id_user', (int) $user->id)->pluck('id')->values()->all();
        if ($ownClaimIds !== []) {
            return response()->json([
                'message' => 'Tidak dapat approve bulk untuk pengajuan Anda sendiri. Hapus dari pilihan: ' . implode(', ', $ownClaimIds),
            ], 422);
        }

        if ($bulkStatus === 0 && $jab === 'Direktur Operasional') {
            $unauthorizedIds = $rows
                ->filter(function ($row) use ($user) {
                    return !$user->isHeadDeptApproverForSubmitter((int) $row->id_user);
                })
                ->pluck('id')
                ->values()
                ->all();
            if ($unauthorizedIds !== []) {
                return response()->json([
                    'message' => 'Beberapa pengajuan bukan anak buah Anda. Hapus dari pilihan: ' . implode(', ', $unauthorizedIds),
                ], 422);
            }
        }

      	if ($bulkStatus === 0 && ($jab === 'Direktur Operasional' || $jab === 'superadmin')) {
            $status = 1;
            Reimbursement::whereIn('id', $idsArray)->where('status', 0)->update(['status' => $status, 'mengetahui_op' => $user->name]);
        } else if ($bulkStatus === 1 && ($jab === 'Finance' || $jab === 'HR' || $jab === 'HR GA' || $jab === 'superadmin')) {
            $status = 2;
            Reimbursement::whereIn('id', $idsArray)->where('status', 1)->update(['status' => $status, 'mengetahui_finance' => $user->name]);
        } else if ($bulkStatus === 2 && $jab === 'Finance Supervisor') {
            $status = 11;
            Reimbursement::whereIn('id', $idsArray)->where('status', 2)->update([
                'status' => $status,
                'menyetujui_finance_supervisor' => $user->name,
            ]);
        } else if ($bulkStatus === 2 && ($jab === 'Owner' || $jab === 'superadmin')) {
            $status = 3;
            Reimbursement::whereIn('id', $idsArray)->where('status', 2)->update(['status' => $status, 'mengetahui_owner' => $user->name]);
        } else if ($bulkStatus === 11 && ($jab === 'Finance Manager' || $jab === 'Owner' || $jab === 'superadmin')) {
            $status = 3;
            Reimbursement::whereIn('id', $idsArray)->where('status', 11)->update(['status' => $status, 'mengetahui_owner' => $user->name]);
        } else if ($bulkStatus === 3 && ($jab === 'Owner' || $jab === 'superadmin')) {
            $status = 3;
            Reimbursement::whereIn('id', $idsArray)->where('status', 3)->update(['status' => $status, 'mengetahui_owner' => $user->name]);
        }
        ActivityLogger::log(
            'reimbursement-travel',
            'approve_multiple',
            'Reimbursement travel disetujui secara massal',
            null,
            'reimbursement',
            null,
            ['ids' => $idsArray, 'status' => $status]
        );
        
        // Ambil id_user dari tabel pengajuan
        $userIds = Reimbursement::whereIn('id', $idsArray)->pluck('id_user')->toArray();

        $reimbursement = Reimbursement::whereIn('id', $idsArray)->get(['id', 'id_user', 'no_reimbursement', 'nominal_pengajuan', 'created_by']);

        foreach ($reimbursement as $row) {
            // Ambil nomor HP user berdasarkan id_user
            $user = User::where('id', $row->id_user)->first(['phoneNumber']);

            if ($user && $user->phoneNumber) {
                if ($bulkStatus === 0 && ($jab === 'Direktur Operasional' || $jab === 'superadmin')) {
                    $curl = \Curl::to('https://api.fonnte.com/send')
                    ->withHeaders(['Authorization: ' . config('services.fonnte.token')])
                    ->withData([
                        'target' => FonnteMessenger::normalizePhone($user->phoneNumber),
                        'message' =>
                            "Hai *" .
                            $row->created_by .
                            "*,\n\nPengajuan reimbursement Anda dengan nomor *" .
                            $row->no_reimbursement .
                            "* sebesar *Rp " .
                            number_format($row->nominal_pengajuan, 0, ',', '.') .
                            "* telah diterima oleh *" .
                            auth()->user()->name  .
                            " (Head Department)* .\n\nSaat ini sedang menunggu Proses Verifikasi oleh HR GA.\n\nTerima kasih.
                               \n\nKlik untuk melihat detail pengajuan : " .
                            url('/reimbursement-travel/' . $row->id),
                    ])
                    ->post();

                    $hr_ga = DB::select(DB::raw("SELECT * FROM users WHERE jabatan='Finance'"));

                    foreach($hr_ga as $hr) {

                        $curl = \Curl::to('https://api.fonnte.com/send')
                            ->withHeaders(['Authorization: ' . config('services.fonnte.token')])
                            ->withData([
                                'target' => FonnteMessenger::normalizePhone($hr->phoneNumber),
                                'message' =>
                                    "Hai *" .
                                    $hr->name .
                                    "*,\n\nPengajuan reimbursement nama *".$row->created_by."* dengan nomor *" .
                                    $row->no_reimbursement .
                                    "* sebesar *Rp " .
                                    number_format($row->nominal_pengajuan, 0, ',', '.') .
                                    "* telah diterima oleh Head Department.\n\nSaat ini sedang menunggu Proses Verifikasi Anda.\n\nTerima kasih.
                                     \n\nKlik untuk melihat detail pengajuan : " .
                                    url('/reimbursement-travel/' . $row->id),
                            ])
                            ->post();
                    }
                } 

                if ($bulkStatus === 1 && ($jab === 'Finance' || $jab === 'HR' || $jab === 'HR GA' || $jab === 'superadmin')) {
                    $curl = \Curl::to('https://api.fonnte.com/send')
                    ->withHeaders(['Authorization: ' . config('services.fonnte.token')])
                    ->withData([
                        'target' => FonnteMessenger::normalizePhone($user->phoneNumber),
                        'message' =>
                            "Hai *" .
                            $row->created_by .
                            "*,\n\nPengajuan reimbursement Anda dengan nomor *" .
                            $row->no_reimbursement .
                            "* sebesar *Rp " .
                            number_format($row->nominal_pengajuan, 0, ',', '.') .
                            "* telah diterima oleh *" .
                            auth()->user()->name .
                            " (HR GA)* .\n\nSaat ini sedang menunggu Proses Verifikasi oleh Finance Supervisor.\n\nTerima kasih.
                               \n\nKlik untuk melihat detail pengajuan : " .
                            url('/reimbursement-travel/' . $row->id),
                    ])
                    ->post();

                    $financeSupervisors = DB::select(DB::raw("SELECT * FROM users WHERE jabatan='Finance Supervisor'"));

                    foreach ($financeSupervisors as $fn) {

                        $curl = \Curl::to('https://api.fonnte.com/send')
                            ->withHeaders(['Authorization: ' . config('services.fonnte.token')])
                            ->withData([
                                'target' => FonnteMessenger::normalizePhone($fn->phoneNumber),
                                'message' =>
                                    "Hai *" .
                                    $fn->name .
                                    "*,\n\nPengajuan reimbursement nama *".$row->created_by."* dengan nomor *" .
                                    $row->no_reimbursement .
                                    "* sebesar *Rp " .
                                    number_format($row->nominal_pengajuan, 0, ',', '.') .
                                    "* telah diterima oleh HR GA.\n\nSaat ini sedang menunggu Proses Verifikasi Anda (Finance Supervisor).\n\nTerima kasih.
                                     \n\nKlik untuk melihat detail pengajuan : " .
                                    url('/reimbursement-travel/' . $row->id),
                            ])
                            ->post();

                    }
                }

                if ($bulkStatus === 2 && $jab === 'Finance Supervisor') {
                    $curl = \Curl::to('https://api.fonnte.com/send')
                    ->withHeaders(['Authorization: ' . config('services.fonnte.token')])
                    ->withData([
                        'target' => FonnteMessenger::normalizePhone($user->phoneNumber),
                        'message' =>
                            "Hai *" .
                            $row->created_by .
                            "*,\n\nPengajuan reimbursement Anda dengan nomor *" .
                            $row->no_reimbursement .
                            "* sebesar *Rp " .
                            number_format($row->nominal_pengajuan, 0, ',', '.') .
                            "* telah disetujui Finance Supervisor.\n\nMenunggu verifikasi Finance Manager untuk proses settlement.\n\nTerima kasih.
                    \n\nKlik untuk melihat detail pengajuan : " .
                            url('/reimbursement-travel/' . $row->id),
                    ])
                    ->post();

                    $fmUsers = User::financeManagerNotificationRecipients();
                    foreach ($fmUsers as $fn) {
                        if (empty($fn->phoneNumber)) {
                            continue;
                        }
                        $curl = \Curl::to('https://api.fonnte.com/send')
                            ->withHeaders(['Authorization: ' . config('services.fonnte.token')])
                            ->withData([
                                'target' => FonnteMessenger::normalizePhone($fn->phoneNumber),
                                'message' =>
                                    "Hai *" .
                                    $fn->name .
                                    "*,\n\nPengajuan reimbursement nama *".$row->created_by."* dengan nomor *" .
                                    $row->no_reimbursement .
                                    "* sebesar *Rp " .
                                    number_format($row->nominal_pengajuan, 0, ',', '.') .
                                    "* telah disetujui Finance Supervisor.\n\nMenunggu verifikasi Anda.\n\nTerima kasih.
                                     \n\nKlik untuk melihat detail pengajuan : " .
                                    url('/reimbursement-travel/' . $row->id),
                            ])
                            ->post();
                    }
                }

                if (($bulkStatus === 2 && ($jab === 'Owner' || $jab === 'superadmin')) || ($bulkStatus === 11 && ($jab === 'Finance Manager' || $jab === 'Owner' || $jab === 'superadmin')) || ($bulkStatus === 3 && ($jab === 'Owner' || $jab === 'superadmin'))) {
                    $curl = \Curl::to('https://api.fonnte.com/send')
                    ->withHeaders(['Authorization: ' . config('services.fonnte.token')])
                    ->withData([
                        'target' => FonnteMessenger::normalizePhone($user->phoneNumber),
                        'message' =>
                            "Hai *" .
                            $row->created_by .
                            "*,\n\nPengajuan reimbursement Anda dengan nomor *" .
                            $row->no_reimbursement .
                            "* sebesar *Rp " .
                            number_format($row->nominal_pengajuan, 0, ',', '.') .
                            "* telah disetujui oleh *" .
                            auth()->user()->name .
                            " (Finance)*.\n\nSaat ini sedang menunggu Proses Pencairan oleh Finance.\n\nTerima kasih.
                    \n\nKlik untuk melihat detail pengajuan : " .
                            url('/reimbursement-travel/' . $row->id),
                    ])
                    ->post();

                    $finance = DB::select(DB::raw("SELECT * FROM users WHERE jabatan='Owner'"));

                    foreach($finance as $fn) {

                        $curl = \Curl::to('https://api.fonnte.com/send')
                            ->withHeaders(['Authorization: ' . config('services.fonnte.token')])
                            ->withData([
                                'target' => FonnteMessenger::normalizePhone($fn->phoneNumber),
                                'message' =>
                                    "Hai *" .
                                    $fn->name .
                                    "*,\n\nPengajuan reimbursement nama *".$row->created_by."* dengan nomor *" .
                                    $row->no_reimbursement .
                                    "* sebesar *Rp " .
                                    number_format($row->nominal_pengajuan, 0, ',', '.') .
                                    "* telah disetujui oleh Finance.\n\nSilahkan lakukan proses Pencairan\n\nTerima kasih.
                                     \n\nKlik untuk melihat detail pengajuan : " .
                                    url('/reimbursement-travel/' . $row->id),
                            ])
                            ->post();

                    }
                } 
            }
        }

        return response()->json(['message' => 'Status updated & WA sent']);

    }
  
    public function storeItem(Request $request)
    {
        DB::beginTransaction();
        $id_max  = DB::select( DB::raw("SELECT max(id) AS id FROM reimbursement"))['0']->id + 1;
        if (isset($_POST['save'])) {
            $status = 0;
            $notif = 'Reimbursement Successfully Submitted';
        } else if (isset($_POST['save_draft'])) {
            $status = 10; // DRAFT
            $notif = 'redirect';
        } else if (isset($_POST['save_item'])) {
            $status = 10;
            $notif = 'redirect';
        }
        try {
            $total = 0;
            foreach ($request->reimburse as $key => $value) {
                $total += (int) round($this->normalizeTravelMoneyValue($value['total'] ?? ''));
            }            

            $id_reimb =  Request::segment(3);
            
            // foreach ($request->rates as $key => $value) {
            //     TravelTripRate::create([
            //         'reimbursement_id' => $id_reimb,
            //         'currency' => $value['code'],
            //         'rate' => str_replace(".", "", $value['rate']),
            //     ]);
            // }
            foreach ($request->reimburse as $key => $value) {
                $payload = [
                    'reimbursement_id' => $id_reimb,
                    'date' => $value['date'],
                    'purpose' => $value['purpose'],
                    'trip_type_id' => $this->normalizeTripTypeId($value['trip_type_id'] ?? null),
                    'hotel_condition_id' => $value['hotel_condition_id'],
                    'start_time' => $value['start_time'],
                    'end_time' => $value['end_time'],
                    'allowance' => $this->normalizeTravelMoneyValue($value['allowance'] ?? ''),
                    'total' => $this->normalizeTravelMoneyValue($value['total'] ?? ''),
                ];

                $dt = ReimbursementTravel::create($payload);
                foreach ($value['detail'] as $k => $v) {
                    if (isset($v['cost_type_id'])) {
                        
                    $payloadDetail = [
                        'reimbursement_travel_id' => $dt->id,
                        'destination' => $v['destination'],
                        'payment_type' => $v['payment_type'],
                        'cost_type_id' => $v['cost_type_id'],
                        'currency' => !empty($v['currency']) ? strtoupper(trim((string) $v['currency'])) : 'IDR',
                        'amount' => $this->normalizeTravelAmountInteger($v['amount'] ?? ''),
                        'idr_rate' => $this->normalizeTravelMoneyValue($v['idr_rate'] ?? ''),
                        'tax' => $this->normalizeTravelMoneyValue($v['tax'] ?? '0'),
                    ];
                    $uploadFiles = [];
                    $proofFile = $request->file('reimburse.'.$key.'.detail.'.$k.'.proof');
                    if ($proofFile instanceof UploadedFile) {
                        $uploadFiles[] = $proofFile;
                    }
                    $mainFile = $request->file('reimburse.'.$key.'.detail.'.$k.'.file');
                    if ($mainFile instanceof UploadedFile) {
                        $uploadFiles[] = $mainFile;
                    }

                    if (!empty($uploadFiles)) {
                        $firstStored = $this->storeTravelEvidenceFile($uploadFiles[0]);
                        if ($firstStored !== '') {
                            $payloadDetail['evidence'] = $firstStored;
                        }
                    }
                    $da = ReimbursementTravelDetail::create($payloadDetail);

                    if (!empty($uploadFiles)) {
                        $this->appendUploadedAttachments(
                            (int) $id_reimb,
                            'travel',
                            'reimbursement_travel_details',
                            (int) $da->id,
                            $uploadFiles
                        );
                    }
                    }
                }
            }

            $id_main = DB::select( DB::raw("SELECT max(id) as id_main FROM reimbursement"))['0']->id_main;
            $id_max = DB::select( DB::raw("SELECT max(id) as id_max FROM reimbursement_travel WHERE reimbursement_id='$id_main'"))['0']->id_max;
            $travel_type = DB::select( DB::raw("SELECT travel_type FROM reimbursement WHERE id='$id_main'"))['0']->travel_type;

            if ($travel_type=='Domestic') {
                $allowance = DB::select( DB::raw("SELECT sum(allowance) AS total FROM reimbursement_travel WHERE reimbursement_id='$id_main'"))['0']->total;
            } else {
                // $allowance_ = DB::select( DB::raw("SELECT sum(allowance) AS total FROM reimbursement_travel WHERE reimbursement_id='$id_main'"))['0']->total;
                // $rate = DB::select( DB::raw("SELECT rate FROM travel_trip_rates WHERE reimbursement_id='$id_main' AND currency='USD'"))['0']->rate;
                // $allowance = $allowance_ * $rate; 
                $allowance = DB::select( DB::raw("SELECT sum(allowance) AS total FROM reimbursement_travel WHERE reimbursement_id='$id_main'"))['0']->total;

            }


            $total_bdc  = DB::select( DB::raw("SELECT sum(idr_rate) AS total FROM reimbursement_travel_details WHERE reimbursement_id='$id_max' AND payment_type='BDC'"))['0']->total;
            $allowance_bdc = 0;
            $simcard_bdc  = DB::select( DB::raw("SELECT sum(idr_rate) AS total FROM reimbursement_travel_details WHERE reimbursement_id='$id_max' AND payment_type='BDC' AND cost_type_id = 8"))['0']->total;
            $flight_bdc  = DB::select( DB::raw("SELECT sum(idr_rate) AS total FROM reimbursement_travel_details WHERE reimbursement_id='$id_max' AND payment_type='BDC' AND cost_type_id = 4"))['0']->total;
            $rentalcar_bdc  = DB::select( DB::raw("SELECT sum(idr_rate) AS total FROM reimbursement_travel_details WHERE reimbursement_id='$id_max' AND payment_type='BDC' AND cost_type_id = 3"))['0']->total;
            $hotel_bdc  = DB::select( DB::raw("SELECT sum(idr_rate) AS total FROM reimbursement_travel_details WHERE reimbursement_id='$id_max' AND payment_type='BDC' AND cost_type_id = 1"))['0']->total;
            $toll_bdc  = DB::select( DB::raw("SELECT sum(idr_rate) AS total FROM reimbursement_travel_details WHERE reimbursement_id='$id_max' AND payment_type='BDC' AND cost_type_id = 5"))['0']->total;
            $gasoline_bdc  = DB::select( DB::raw("SELECT sum(idr_rate) AS total FROM reimbursement_travel_details WHERE reimbursement_id='$id_max' AND payment_type='BDC' AND cost_type_id = 7"))['0']->total;
            $taxi_bdc  = DB::select( DB::raw("SELECT sum(idr_rate) AS total FROM reimbursement_travel_details WHERE reimbursement_id='$id_max' AND payment_type='BDC' AND cost_type_id = 2"))['0']->total;
            $train_bdc  = DB::select( DB::raw("SELECT sum(idr_rate) AS total FROM reimbursement_travel_details WHERE reimbursement_id='$id_max' AND payment_type='BDC' AND cost_type_id = 6"))['0']->total;
            $tax_bdc  = DB::select( DB::raw("SELECT sum(tax) AS total FROM reimbursement_travel_details WHERE reimbursement_id='$id_max' AND payment_type='BDC'"))['0']->total;
            $others_bdc  = DB::select( DB::raw("SELECT sum(idr_rate) AS total FROM reimbursement_travel_details WHERE reimbursement_id='$id_max' AND payment_type='BDC' AND cost_type_id = 9"))['0']->total;

            $total_cash  = DB::select( DB::raw("SELECT sum(idr_rate) AS total FROM reimbursement_travel_details WHERE reimbursement_id='$id_max' AND payment_type='Cash'"))['0']->total;
            $allowance_cash = $allowance;
            $simcard_cash  = DB::select( DB::raw("SELECT sum(idr_rate) AS total FROM reimbursement_travel_details WHERE reimbursement_id='$id_max' AND payment_type='Cash' AND cost_type_id = 8"))['0']->total;
            $flight_cash  = DB::select( DB::raw("SELECT sum(idr_rate) AS total FROM reimbursement_travel_details WHERE reimbursement_id='$id_max' AND payment_type='Cash' AND cost_type_id = 4"))['0']->total;
            $rentalcar_cash  = DB::select( DB::raw("SELECT sum(idr_rate) AS total FROM reimbursement_travel_details WHERE reimbursement_id='$id_max' AND payment_type='Cash' AND cost_type_id = 3"))['0']->total;
            $hotel_cash  = DB::select( DB::raw("SELECT sum(idr_rate) AS total FROM reimbursement_travel_details WHERE reimbursement_id='$id_max' AND payment_type='Cash' AND cost_type_id = 1"))['0']->total;
            $toll_cash  = DB::select( DB::raw("SELECT sum(idr_rate) AS total FROM reimbursement_travel_details WHERE reimbursement_id='$id_max' AND payment_type='Cash' AND cost_type_id = 5"))['0']->total;
            $gasoline_cash  = DB::select( DB::raw("SELECT sum(idr_rate) AS total FROM reimbursement_travel_details WHERE reimbursement_id='$id_max' AND payment_type='Cash' AND cost_type_id = 7"))['0']->total;
            $taxi_cash  = DB::select( DB::raw("SELECT sum(idr_rate) AS total FROM reimbursement_travel_details WHERE reimbursement_id='$id_max' AND payment_type='Cash' AND cost_type_id = 2"))['0']->total;
            $train_cash  = DB::select( DB::raw("SELECT sum(idr_rate) AS total FROM reimbursement_travel_details WHERE reimbursement_id='$id_max' AND payment_type='Cash' AND cost_type_id = 6"))['0']->total;
            $tax_cash  = DB::select( DB::raw("SELECT sum(tax) AS total FROM reimbursement_travel_details WHERE reimbursement_id='$id_max' AND payment_type='Cash'"))['0']->total;
            $others_cash  = DB::select( DB::raw("SELECT sum(idr_rate) AS total FROM reimbursement_travel_details WHERE reimbursement_id='$id_max' AND payment_type='Cash' AND cost_type_id = 9"))['0']->total;

            $form_data = array(
                'total_bdc'        =>  $total_bdc ?? 0,
                'allowance_bdc'        =>  $allowance_bdc,
                'simcard_bdc'        =>  $simcard_bdc ?? 0,
                'flight_bdc'        =>  $flight_bdc ?? 0,
                'rentalcar_bdc'        =>  $rentalcar_bdc ?? 0,
                'hotel_bdc'        =>  $hotel_bdc ?? 0,
                'toll_bdc'        =>  $toll_bdc ?? 0,
                'gasoline_bdc'        =>  $gasoline_bdc ?? 0,
                'taxi_bdc'        =>  $taxi_bdc ?? 0,
                'train_bdc'        =>  $train_bdc ?? 0,
                'tax_bdc'        =>  $tax_bdc ?? 0,
                'others_bdc'        =>  $others_bdc ?? 0,
                'total_cash'        =>  $total_cash ?? 0,
                'allowance_cash'        =>  $allowance_cash,
                'simcard_cash'        =>  $simcard_cash ?? 0,
                'flight_cash'        =>  $flight_cash ?? 0,
                'rentalcar_cash'        =>  $rentalcar_cash ?? 0,
                'hotel_cash'        =>  $hotel_cash ?? 0,
                'toll_cash'        =>  $toll_cash ?? 0,
                'gasoline_cash'        =>  $gasoline_cash ?? 0,
                'taxi_cash'        =>  $taxi_cash ?? 0,
                'train_cash'        =>  $train_cash ?? 0,
                'tax_cash'        =>  $tax_cash ?? 0,
                'others_cash'        =>  $others_cash ?? 0,
            ); 
        
            Reimbursement::whereId($id_main)->update($form_data);

            $user = \App\User::where('id', $id_reimb_user)->first();
            if ($status != 10) {
                $curl = \Curl::to('https://api.fonnte.com/send')
                    ->withHeaders(['Authorization: ' . config('services.fonnte.token')])
                    ->withData([
                        'target' => FonnteMessenger::normalizePhone($user->phoneNumber),
                        'message' =>
                            "Hai *" .
                            $user->name .
                            "*,\n\nPengajuan reimbursement Anda dengan nomor *" .
                            $data->no_reimbursement .
                            "* sebesar *Rp " .
                            number_format($data->nominal_pengajuan, 0, ',', '.') .
                            "* telah diterima.\n\nSaat ini sedang menunggu Proses Verifikasi oleh Head Department.\n\nTerima kasih.
                            \n\nKlik untuk melihat detail pengajuan : " .
                            url('/reimbursement-travel/' . $id_reimb),
                    ])->post();
                
                $id_approval  = $user->id_approval;
                $approval = DB::select(DB::raw("SELECT * FROM users WHERE id='$id_approval'"));

                if (!empty($approval)) {
                    $curl = \Curl::to('https://api.fonnte.com/send')
                        ->withHeaders(['Authorization: ' . config('services.fonnte.token')])
                        ->withData([
                            'target' => FonnteMessenger::normalizePhone($approval[0]->phoneNumber),
                            'message' =>
                                "Hai *" .
                                $approval[0]->name .
                                "*,\n\nPengajuan reimbursement nama *".$user->name."* dengan nomor *" .
                                $data->no_reimbursement .
                                "* sebesar *Rp " .
                                number_format($data->nominal_pengajuan, 0, ',', '.') .
                                "* telah diajukan kembali.\n\nSaat ini sedang menunggu Proses *Verifikasi Anda*.\n\nTerima kasih.\n\nKlik untuk melihat detail pengajuan : " .
                                url('/reimbursement-travel/' . $id_reimb),
                        ])->post();
                }
            }

            DB::commit();

            $id_travel = DB::select(DB::raw("SELECT id FROM reimbursement_travel WHERE reimbursement_id='$id_main'"))['0']->id;

            if ($notif!='redirect') {
                return redirect()->route('reimbursement-travel.index')->with(['success' => $notif]);    
            } else {
                return redirect('reimbursement-travel/add-item/'.$id_main.'/');
            }

            

        } catch(\Exception $e) {
            // return var_dump($e);
            dd($e->getMessage() . " at line ". $e->getLine());
            DB::rollback();
            return redirect()->back()->withErrors(['Error '.$e->getMessage()]);

        } catch(\Throwable $e) {
            // return var_dump($e);
            dd($e->getMessage() . " at line ". $e->getLine());

            DB::rollback();
            return redirect()->back()->withErrors(['Error '.$e->getMessage()]);
        }
    }

    public function updateCurrency(Request $request)
    {
        $id_rate = (int) $request->id_rate;
        $currency = strtoupper(trim((string) $request->currency));
        $rate = $this->normalizeExchangeRateValue($request->rate);
        $reim_id = $request->reim_id;

        if ($currency === '') {
            return response()->json(['message' => 'Currency tidak boleh kosong.', 'id_rate' => 0], 422);
        }

        if ($currency === 'IDR') {
            $rate = '1.00';
        }

        if ($id_rate > 0) {
            $row = TravelTripRate::where('id', $id_rate)->where('reimbursement_id', $reim_id)->first();
            if (!$row) {
                return response()->json(['message' => 'Baris kurs tidak ditemukan.', 'id_rate' => 0], 404);
            }
            $row->update([
                'currency' => $currency,
                'rate' => $rate,
            ]);

            return response()->json(['message' => 'Data berhasil diupdate.', 'id_rate' => $row->id]);
        }

        // Baris baru (id_rate = 0): satu baris per mata uang per reimbursement — upsert agar UI tidak "gagal diam-diam"
        $existing = TravelTripRate::where('reimbursement_id', $reim_id)->where('currency', $currency)->first();
        if ($existing) {
            $existing->update(['rate' => $rate]);

            return response()->json(['message' => 'Data berhasil diupdate.', 'id_rate' => $existing->id]);
        }

        $created = TravelTripRate::create([
            'reimbursement_id' => $reim_id,
            'currency' => $currency,
            'rate' => $rate,
        ]);

        return response()->json(['message' => 'Data berhasil disimpan.', 'id_rate' => $created->id]);
    }

    public function getCurrencyOptions(Request $request)
    {
        $selected = strtoupper(trim((string) $request->selected));
        $reim_id = $request->reim_id;

        $codes = [];
        foreach (TravelTripRate::where('reimbursement_id', $reim_id)->get() as $item) {
            $code = strtoupper(trim((string) $item->currency));
            if ($code !== '') {
                $codes[$code] = true;
            }
        }

        $extra = trim((string) $request->input('currencies', ''));
        if ($extra !== '') {
            foreach (preg_split('/\s*,\s*/', $extra) as $part) {
                $code = strtoupper(trim($part));
                if ($code !== '') {
                    $codes[$code] = true;
                }
            }
        }

        $options = '<option value="">Pilih...</option>';
        foreach (array_keys($codes) as $code) {
            $sel = $code === $selected ? 'selected' : '';
            $options .= "<option value=\"{$code}\" {$sel}>{$code}</option>";
        }

        return response()->json(['options' => $options]);
    }

    public function deleteCurrencyOptions(Request $request)
    {
        $id_rate = (int) $request->id_rate;
        $currency = $request->currency;
        $rate = $this->normalizeExchangeRateValue($request->rate);
        $reim_id = $request->reim_id;

        if ($id_rate > 0) {
            $deletedById = TravelTripRate::where('id', $id_rate)
                ->where('reimbursement_id', $reim_id)
                ->delete();

            if ($deletedById) {
                return response()->json(['status' => 'success', 'message' => 'Data berhasil dihapus.']);
            }
        }

        // Cek dan hapus jika data ditemukan
        $deleted = TravelTripRate::where('reimbursement_id', $reim_id)
            ->where('currency', $currency)
            ->where('rate', $rate)
            ->delete();

        if ($deleted) {
            return response()->json(['status' => 'success', 'message' => 'Data berhasil dihapus.']);
        } else {
            return response()->json(['status' => 'error', 'message' => 'Data tidak ditemukan atau sudah dihapus.']);
        }
    }

}
