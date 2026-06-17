<?php

namespace App\Console\Commands;

use App\Reimbursement;
use App\Support\ActivityLogger;
use Illuminate\Console\Command;

class RepairReimbursementTicketNumbers extends Command
{
    protected $signature = 'reimbursement:repair-ticket-numbers
                            {--dry-run : List mismatches without updating}
                            {--type= : Limit to reimbursement_type (1=driver, 2=travel, 3=entertainment)}';

    protected $description = 'Repair no_reimbursement values that do not match reimbursement.id';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $typeFilter = $this->option('type');
        $query = Reimbursement::query()->orderBy('id');

        if ($typeFilter !== null && $typeFilter !== '') {
            $query->where('reimbursement_type', (int) $typeFilter);
        }

        $mismatches = [];
        $query->chunkById(200, function ($rows) use (&$mismatches) {
            foreach ($rows as $row) {
                $expected = $row->expectedTicketNumber();
                if ($expected === null || $row->no_reimbursement === 'PENDING') {
                    continue;
                }
                if ($row->no_reimbursement !== $expected) {
                    $mismatches[] = $row;
                }
            }
        });

        if (count($mismatches) === 0) {
            $this->info('No ticket number mismatches found.');

            return 0;
        }

        $this->warn(sprintf('Found %d mismatch(es).', count($mismatches)));

        $headers = ['id', 'type', 'current', 'expected'];
        $tableRows = [];
        foreach ($mismatches as $row) {
            $tableRows[] = [
                $row->id,
                $row->reimbursement_type,
                $row->no_reimbursement,
                $row->expectedTicketNumber(),
            ];
        }
        $this->table($headers, $tableRows);

        if ($dryRun) {
            $this->comment('Dry run only — no rows updated.');

            return 0;
        }

        if (!$this->confirm('Apply repairs to all listed rows?', true)) {
            $this->comment('Aborted.');

            return 1;
        }

        $repaired = 0;
        foreach ($mismatches as $row) {
            $previous = $row->no_reimbursement;
            $corrected = $row->syncTicketNumber();
            if ($corrected === null) {
                continue;
            }

            $module = 'reimbursement-driver';
            if ((int) $row->reimbursement_type === 2) {
                $module = 'reimbursement-travel';
            } elseif ((int) $row->reimbursement_type === 3) {
                $module = 'reimbursement-entertaiment';
            }

            ActivityLogger::log(
                $module,
                'repair_ticket_number',
                'Nomor inquiry diperbaiki agar sesuai id database',
                $corrected,
                'reimbursement',
                $row->id,
                ['previous_no_reimbursement' => $previous, 'corrected_no_reimbursement' => $corrected],
                'System',
                'system'
            );

            $repaired++;
        }

        $this->info(sprintf('Repaired %d row(s).', $repaired));

        return 0;
    }
}
