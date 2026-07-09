<?php

namespace App\Support;

use App\ReimbursementAttachment;
use App\ReimbursementTravelDetail;
use Illuminate\Support\Facades\Schema;

class TravelAttachmentResolver
{
    private const DETAIL_TYPE = 'reimbursement_travel_details';

    public static function tableReady(): bool
    {
        return Schema::hasTable('reimbursement_attachments');
    }

    /**
     * @return array<int, array{id:int, file_name:string, original_name:string}>
     */
    public static function rowsForDetail(
        int $reimbursementId,
        int $detailId,
        string $legacyEvidence = '',
        string $destination = '',
        int $costTypeId = 0
    ): array {
        $rows = self::queryByDetailId($detailId);

        $legacyEvidence = trim($legacyEvidence);
        if ($legacyEvidence !== '' && !self::containsFileName($rows, $legacyEvidence)) {
            if (self::fileExistsOnDisk($legacyEvidence)) {
                $rows[] = [
                    'id' => 0,
                    'file_name' => $legacyEvidence,
                    'original_name' => $legacyEvidence,
                ];
            }
        }

        if (!empty($rows) || $reimbursementId <= 0 || $detailId <= 0) {
            return $rows;
        }

        $detail = ReimbursementTravelDetail::query()
            ->where('id', $detailId)
            ->where('status', '1')
            ->first(['id', 'reimbursement_id', 'destination', 'cost_type_id', 'evidence']);

        if (!$detail) {
            return $rows;
        }

        $destination = $destination !== '' ? $destination : (string) ($detail->destination ?? '');
        $costTypeId = $costTypeId > 0 ? $costTypeId : (int) ($detail->cost_type_id ?? 0);
        $reimbursementId = $reimbursementId > 0 ? $reimbursementId : (int) ($detail->reimbursement_id ?? 0);

        $candidate = self::bestOrphanMatch(
            $reimbursementId,
            $destination,
            $costTypeId,
            self::activeDetailIds($reimbursementId),
            null,
            $detailId
        );

        if ($candidate) {
            $rows[] = [
                'id' => (int) $candidate->id,
                'file_name' => (string) $candidate->file_name,
                'original_name' => (string) ($candidate->original_name ?: $candidate->file_name),
            ];
        }

        return $rows;
    }

    public static function repairForReimbursement(int $reimbursementId): int
    {
        if (!self::tableReady() || $reimbursementId <= 0) {
            return 0;
        }

        $repaired = 0;
        $activeDetails = ReimbursementTravelDetail::query()
            ->where('reimbursement_id', $reimbursementId)
            ->where('status', '1')
            ->orderBy('id')
            ->get();

        if ($activeDetails->isEmpty()) {
            return 0;
        }

        $activeIds = $activeDetails->pluck('id')->map(function ($v) {
            return (int) $v;
        })->filter(function ($v) {
            return $v > 0;
        })->values();

        $allAttachments = ReimbursementAttachment::query()
            ->where('reimbursement_id', $reimbursementId)
            ->where('detail_type', self::DETAIL_TYPE)
            ->orderBy('id')
            ->get();

        $usedAttachmentIds = [];

        foreach ($activeDetails as $detail) {
            $detailId = (int) $detail->id;
            if ($detailId <= 0) {
                continue;
            }

            $legacyEvidence = trim((string) ($detail->evidence ?? ''));
            if ($legacyEvidence !== '' && self::fileExistsOnDisk($legacyEvidence)) {
                self::ensureAttachmentRow($reimbursementId, $detailId, $legacyEvidence);
            }

            $linkedCount = $allAttachments->where('detail_id', $detailId)->count();
            if ($linkedCount > 0) {
                continue;
            }

            if ($legacyEvidence !== '') {
                $orphan = $allAttachments->first(function ($att) use ($legacyEvidence, $detailId, $usedAttachmentIds) {
                    return (string) $att->file_name === $legacyEvidence
                        && (int) $att->detail_id !== $detailId
                        && !in_array((int) $att->id, $usedAttachmentIds, true);
                });
                if ($orphan) {
                    $orphan->detail_id = $detailId;
                    $orphan->save();
                    $usedAttachmentIds[] = (int) $orphan->id;
                    $repaired++;
                    continue;
                }
            }

            $availablePool = $allAttachments->reject(function ($att) use ($usedAttachmentIds) {
                return in_array((int) $att->id, $usedAttachmentIds, true);
            });

            $candidate = self::bestOrphanMatch(
                $reimbursementId,
                (string) ($detail->destination ?? ''),
                (int) ($detail->cost_type_id ?? 0),
                $activeIds,
                $availablePool,
                $detailId,
                (int) ($detail->reimbursement_travel_id ?? 0)
            );

            if (!$candidate) {
                continue;
            }

            $candidate->detail_id = $detailId;
            $candidate->save();
            $usedAttachmentIds[] = (int) $candidate->id;

            if ($legacyEvidence === '') {
                $detail->evidence = (string) $candidate->file_name;
                $detail->save();
            }

            $repaired++;
        }

        return $repaired;
    }

    /**
     * @param array<int, int>|null $activeIds
     * @param \Illuminate\Support\Collection<int, ReimbursementAttachment>|null $pool
     */
    public static function predictOrphanMatch(
        int $reimbursementId,
        string $destination,
        int $costTypeId,
        $activeIds,
        $pool,
        int $activeDetailId = 0,
        int $reimbursementTravelId = 0
    ): ?ReimbursementAttachment {
        return self::bestOrphanMatch(
            $reimbursementId,
            $destination,
            $costTypeId,
            collect($activeIds),
            $pool,
            $activeDetailId,
            $reimbursementTravelId
        );
    }

    private static function ensureAttachmentRow(int $reimbursementId, int $detailId, string $fileName): void
    {
        $exists = ReimbursementAttachment::query()
            ->where('detail_type', self::DETAIL_TYPE)
            ->where('detail_id', $detailId)
            ->where('file_name', $fileName)
            ->exists();

        if ($exists) {
            return;
        }

        ReimbursementAttachment::create([
            'reimbursement_id' => $reimbursementId,
            'module' => 'travel',
            'detail_type' => self::DETAIL_TYPE,
            'detail_id' => $detailId,
            'file_name' => $fileName,
            'original_name' => $fileName,
            'mime_type' => null,
            'file_size' => self::fileSizeOnDisk($fileName),
            'created_by' => auth()->id(),
        ]);
    }

    /**
     * @param \Illuminate\Support\Collection<int, int>|null $activeIds
     * @param \Illuminate\Support\Collection<int, ReimbursementAttachment>|null $pool
     */
    private static function bestOrphanMatch(
        int $reimbursementId,
        string $destination,
        int $costTypeId,
        $activeIds = null,
        $pool = null,
        int $activeDetailId = 0,
        int $reimbursementTravelId = 0
    ): ?ReimbursementAttachment {
        $activeIds = $activeIds ?? self::activeDetailIds($reimbursementId);

        if ($pool === null) {
            $pool = ReimbursementAttachment::query()
                ->where('reimbursement_id', $reimbursementId)
                ->where('detail_type', self::DETAIL_TYPE)
                ->orderBy('id')
                ->get();
        }

        $orphans = $pool->filter(function (ReimbursementAttachment $att) use ($activeIds) {
            $detailId = (int) $att->detail_id;
            if ($detailId <= 0) {
                return true;
            }
            if (!$activeIds->contains($detailId)) {
                return true;
            }

            return ReimbursementTravelDetail::query()
                ->where('id', $detailId)
                ->where('status', '1')
                ->doesntExist();
        });

        if ($orphans->isEmpty()) {
            return null;
        }

        $best = null;
        $bestScore = 0;

        foreach ($orphans as $att) {
            $score = self::scoreAttachmentMatch(
                $att,
                $destination,
                $costTypeId,
                $activeDetailId,
                $reimbursementTravelId
            );
            if ($score > $bestScore) {
                $bestScore = $score;
                $best = $att;
            }
        }

        return $bestScore >= 35 ? $best : null;
    }

    private static function scoreAttachmentMatch(
        ReimbursementAttachment $att,
        string $destination,
        int $costTypeId,
        int $activeDetailId = 0,
        int $reimbursementTravelId = 0
    ): int {
        $haystack = strtolower(
            (string) ($att->original_name ?? '') . ' ' . (string) ($att->file_name ?? '')
        );
        $destination = trim($destination);
        if ($destination === '') {
            return 0;
        }

        $score = 0;
        $destLower = strtolower($destination);

        if (strpos($haystack, $destLower) !== false) {
            $score += 80;
        }

        foreach (self::tokensFromText($destination) as $token) {
            if (strlen($token) < 3) {
                continue;
            }
            if (strpos($haystack, $token) !== false) {
                $score += 25;
            }
        }

        if (preg_match('/\bGA\s*(\d+)/i', $destination, $destFlight)) {
            $destNum = (string) $destFlight[1];
            if (preg_match('/\bGA\s*(\d+)/i', $haystack, $fileFlight)) {
                if ((string) $fileFlight[1] !== $destNum) {
                    return 0;
                }
                $score += 60;
            }
        }

        if ($costTypeId === 4 && (strpos($haystack, 'ga ') !== false || strpos($haystack, 'flight') !== false)) {
            $score += 15;
        }
        if ($costTypeId === 1 && (strpos($haystack, 'hotel') !== false || strpos($haystack, 'ramada') !== false)) {
            $score += 15;
        }

        $ext = strtolower(pathinfo((string) $att->file_name, PATHINFO_EXTENSION));
        if ($costTypeId === 4 && in_array($ext, ['jpg', 'jpeg', 'png', 'webp', 'gif'], true)) {
            $score += 45;
        }
        if ($costTypeId === 1 && $ext === 'pdf') {
            $score += 45;
        }
        if ($costTypeId === 1 && in_array($ext, ['jpg', 'jpeg', 'png', 'webp'], true)) {
            $score += 20;
        }

        $orphanDetailId = (int) $att->detail_id;
        if ($activeDetailId > 0 && $orphanDetailId > 0) {
            $diff = abs($activeDetailId - $orphanDetailId);
            if ($diff <= 5) {
                $score += max(0, 85 - ($diff * 15));
            }
        }

        if ($reimbursementTravelId > 0 && $orphanDetailId > 0) {
            $orphanTravelId = (int) ReimbursementTravelDetail::query()
                ->where('id', $orphanDetailId)
                ->value('reimbursement_travel_id');
            if ($orphanTravelId > 0 && $orphanTravelId === $reimbursementTravelId) {
                $score += 30;
            }
        }

        if (!self::fileExistsOnDisk((string) $att->file_name)) {
            $score = (int) floor($score / 2);
        }

        return $score;
    }

    /**
     * @return array<int, string>
     */
    private static function tokensFromText(string $text): array
    {
        $parts = preg_split('/[^a-zA-Z0-9]+/', strtolower($text), -1, PREG_SPLIT_NO_EMPTY);

        return is_array($parts) ? array_values(array_unique($parts)) : [];
    }

    /**
     * @return array<int, array{id:int, file_name:string, original_name:string}>
     */
    private static function queryByDetailId(int $detailId): array
    {
        if ($detailId <= 0 || !self::tableReady()) {
            return [];
        }

        return ReimbursementAttachment::query()
            ->where('detail_type', self::DETAIL_TYPE)
            ->where('detail_id', $detailId)
            ->orderBy('id')
            ->get(['id', 'file_name', 'original_name'])
            ->map(function (ReimbursementAttachment $row) {
                return [
                    'id' => (int) $row->id,
                    'file_name' => (string) $row->file_name,
                    'original_name' => (string) ($row->original_name ?: $row->file_name),
                ];
            })
            ->values()
            ->all();
    }

    /**
     * @return \Illuminate\Support\Collection<int, int>
     */
    private static function activeDetailIds(int $reimbursementId)
    {
        return ReimbursementTravelDetail::query()
            ->where('reimbursement_id', $reimbursementId)
            ->where('status', '1')
            ->pluck('id')
            ->map(function ($v) {
                return (int) $v;
            })
            ->filter(function ($v) {
                return $v > 0;
            })
            ->values();
    }

    /**
     * @param array<int, array{id:int, file_name:string, original_name:string}> $rows
     */
    private static function containsFileName(array $rows, string $fileName): bool
    {
        foreach ($rows as $row) {
            if (($row['file_name'] ?? '') === $fileName) {
                return true;
            }
        }

        return false;
    }

    private static function fileExistsOnDisk(string $fileName): bool
    {
        $fileName = trim($fileName);
        if ($fileName === '') {
            return false;
        }

        return is_file(public_path('images/file_bukti/' . $fileName));
    }

    private static function fileSizeOnDisk(string $fileName): int
    {
        $path = public_path('images/file_bukti/' . $fileName);

        return is_file($path) ? (int) (filesize($path) ?: 0) : 0;
    }
}
