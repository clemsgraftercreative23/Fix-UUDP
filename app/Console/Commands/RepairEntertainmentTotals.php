<?php

namespace App\Console\Commands;

use App\Reimbursement;
use App\Support\EntertainmentTotal;
use Illuminate\Console\Command;

class RepairEntertainmentTotals extends Command
{
    protected $signature = 'reimbursement:repair-entertainment-totals
                            {--dry-run : List rows that would be updated without writing}
                            {--id= : Repair a single reimbursement id}';

    protected $description = 'Sync nominal_pengajuan / total_bdc / total_cash from entertainment line items';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $idFilter = $this->option('id');

        $query = Reimbursement::query()
            ->where('reimbursement_type', 3)
            ->orderBy('id');

        if ($idFilter !== null && $idFilter !== '') {
            $query->where('id', (int) $idFilter);
        }

        $repaired = 0;
        $query->chunkById(200, function ($rows) use ($dryRun, &$repaired) {
            foreach ($rows as $row) {
                $computed = EntertainmentTotal::computeForReimbursement((int) $row->id);
                $needsUpdate = (int) $row->nominal_pengajuan !== (int) $computed['nominal_pengajuan']
                    || (int) ($row->total_bdc ?? 0) !== (int) $computed['total_bdc']
                    || (int) ($row->total_cash ?? 0) !== (int) $computed['total_cash'];

                if (!$needsUpdate) {
                    continue;
                }

                $this->line(sprintf(
                    'id=%d no=%s nominal %d -> %d (bdc %d, cash %d)',
                    $row->id,
                    $row->no_reimbursement,
                    (int) $row->nominal_pengajuan,
                    (int) $computed['nominal_pengajuan'],
                    (int) $computed['total_bdc'],
                    (int) $computed['total_cash']
                ));

                if (!$dryRun) {
                    Reimbursement::whereId($row->id)->update($computed);
                }

                $repaired++;
            }
        });

        if ($repaired === 0) {
            $this->info('No entertainment reimbursement totals needed repair.');

            return 0;
        }

        $this->info($dryRun
            ? sprintf('Dry run: %d row(s) would be updated.', $repaired)
            : sprintf('Repaired %d row(s).', $repaired));

        return 0;
    }
}
