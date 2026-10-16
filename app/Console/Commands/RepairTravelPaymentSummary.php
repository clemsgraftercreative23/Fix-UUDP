<?php

namespace App\Console\Commands;

use App\Reimbursement;
use App\Support\TravelPaymentSummary;
use Illuminate\Console\Command;

class RepairTravelPaymentSummary extends Command
{
    protected $signature = 'reimbursement:repair-travel-summary
                            {--dry-run : List rows that would be updated without writing}
                            {--id= : Repair a single reimbursement id}';

    protected $description = "Re-sync the travel Checker's Sheet columns (total/hotel/taxi/... BDC & Cash) from the detail rows each claim shows";

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $idFilter = $this->option('id');

        $query = Reimbursement::query()
            ->where('reimbursement_type', 2)
            ->orderBy('id');

        if ($idFilter !== null && $idFilter !== '') {
            $query->where('id', (int) $idFilter);
        }

        $repaired = 0;
        $query->chunkById(200, function ($rows) use ($dryRun, &$repaired) {
            foreach ($rows as $row) {
                $computed = TravelPaymentSummary::compute((int) $row->id);

                $changes = [];
                foreach ($computed as $column => $value) {
                    if (abs((float) ($row->{$column} ?? 0) - $value) > 0.005) {
                        $changes[] = sprintf('%s %s -> %s', $column, (float) ($row->{$column} ?? 0), $value);
                    }
                }

                if (empty($changes)) {
                    continue;
                }

                $this->line(sprintf('id=%d no=%s status=%s: %s', $row->id, $row->no_reimbursement, $row->status, implode(', ', $changes)));

                if (!$dryRun) {
                    Reimbursement::whereId($row->id)->update($computed);
                }

                $repaired++;
            }
        });

        if ($repaired === 0) {
            $this->info('No travel reimbursement summaries needed repair.');

            return 0;
        }

        $this->info($dryRun
            ? sprintf('Dry run: %d row(s) would be updated.', $repaired)
            : sprintf('Repaired %d row(s).', $repaired));

        return 0;
    }
}
