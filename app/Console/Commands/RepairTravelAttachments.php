<?php

namespace App\Console\Commands;

use App\Reimbursement;
use App\ReimbursementAttachment;
use App\ReimbursementTravelDetail;
use App\Support\TravelAttachmentResolver;
use Illuminate\Console\Command;

class RepairTravelAttachments extends Command
{
    protected $signature = 'travel:repair-attachments
                            {reimbursement_id? : Reimbursement ID to repair (e.g. 1092)}
                            {--all : Repair all travel reimbursements}
                            {--dry-run : Show diagnostics only, do not relink}';

    protected $description = 'Relink orphaned travel reimbursement attachments to active detail rows';

    public function handle(): int
    {
        if (!TravelAttachmentResolver::tableReady()) {
            $this->error('Table reimbursement_attachments tidak ditemukan.');

            return 1;
        }

        $dryRun = (bool) $this->option('dry-run');
        $repairAll = (bool) $this->option('all');
        $reimbursementId = (int) ($this->argument('reimbursement_id') ?? 0);

        if (!$repairAll && $reimbursementId <= 0) {
            $this->error('Berikan reimbursement_id atau gunakan --all.');

            return 1;
        }

        $ids = $repairAll
            ? Reimbursement::query()
                ->where('reimbursement_type', 2)
                ->orderBy('id')
                ->pluck('id')
                ->map(function ($v) {
                    return (int) $v;
                })
                ->all()
            : [$reimbursementId];

        $totalRepaired = 0;

        foreach ($ids as $id) {
            $this->line('');
            $this->info("Reimbursement #{$id}");

            $this->printDiagnostics($id);

            if ($dryRun) {
                continue;
            }

            $repaired = TravelAttachmentResolver::repairForReimbursement($id);
            $totalRepaired += $repaired;
            $this->comment("  → {$repaired} lampiran diperbaiki.");
        }

        if ($dryRun) {
            $this->warn('Dry-run: tidak ada perubahan disimpan.');
        } else {
            $this->info("Selesai. Total lampiran diperbaiki: {$totalRepaired}");
        }

        return 0;
    }

    private function printDiagnostics(int $reimbursementId): void
    {
        $details = ReimbursementTravelDetail::query()
            ->where('reimbursement_id', $reimbursementId)
            ->where('status', '1')
            ->orderBy('id')
            ->get(['id', 'destination', 'cost_type_id', 'evidence']);

        if ($details->isEmpty()) {
            $this->warn('  Tidak ada detail aktif.');

            return;
        }

        $activeIds = $details->pluck('id')->map(function ($v) {
            return (int) $v;
        })->all();

        $attachments = ReimbursementAttachment::query()
            ->where('reimbursement_id', $reimbursementId)
            ->where('detail_type', 'reimbursement_travel_details')
            ->orderBy('id')
            ->get();

        $this->line('  Detail aktif:');
        foreach ($details as $detail) {
            $linked = $attachments->where('detail_id', (int) $detail->id)->count();
            $evidence = trim((string) ($detail->evidence ?? ''));
            $flag = $linked > 0 || $evidence !== '' ? 'OK' : 'KOSONG';
            $this->line(sprintf(
                '    [%s] #%d %s | evidence=%s | linked=%d',
                $flag,
                (int) $detail->id,
                (string) ($detail->destination ?? '-'),
                $evidence !== '' ? $evidence : '-',
                $linked
            ));
        }

        $orphans = $attachments->filter(function ($att) use ($activeIds) {
            return !in_array((int) $att->detail_id, $activeIds, true);
        });

        if ($orphans->isEmpty()) {
            $this->line('  Lampiran yatim: tidak ada');
        } else {
            $this->line('  Lampiran yatim:');
            foreach ($orphans as $att) {
                $this->line(sprintf(
                    '    #%d detail_id=%d file=%s original=%s',
                    (int) $att->id,
                    (int) $att->detail_id,
                    (string) $att->file_name,
                    (string) ($att->original_name ?: '-')
                ));
            }
        }
    }
}
