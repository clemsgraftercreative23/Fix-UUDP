<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class BackfillActivityLogs extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'activity-log:backfill-notif {--limit=5000 : Maximum notif rows to process}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Backfill activity_logs from existing notif records';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        if (!Schema::hasTable('activity_logs')) {
            $this->error('Table activity_logs tidak ditemukan. Jalankan migration terlebih dahulu.');
            return 1;
        }

        if (!Schema::hasTable('notif')) {
            $this->error('Table notif tidak ditemukan. Backfill dihentikan.');
            return 1;
        }

        $limit = (int) $this->option('limit');
        if ($limit <= 0) {
            $limit = 5000;
        }

        $rows = DB::table('notif')
            ->select('id', 'data', 'created_at', 'created_by')
            ->orderBy('id', 'ASC')
            ->limit($limit)
            ->get();

        $inserted = 0;
        foreach ($rows as $row) {
            $description = trim((string) $row->data);
            if ($description === '') {
                continue;
            }

            $module = 'backfill-notif';
            $action = 'log';
            if (stripos($description, 'pengajuan') !== false) {
                $module = 'pengajuan';
            }
            if (stripos($description, 'disetujui') !== false) {
                $action = 'approve';
            } elseif (stripos($description, 'melakukan pengajuan') !== false) {
                $action = 'create';
            } elseif (stripos($description, 'perbaharui') !== false || stripos($description, 'update') !== false) {
                $action = 'update';
            }

            $referenceNo = null;
            if (preg_match('/(ID|Nomor Pengajuan)\s*([A-Za-z0-9\-\/]+)/i', $description, $matches)) {
                $referenceNo = $matches[2];
            }

            $exists = DB::table('activity_logs')
                ->where('module', $module)
                ->where('action', $action)
                ->where('description', $description)
                ->where('actor_name', $row->created_by)
                ->where('created_at', $row->created_at)
                ->exists();

            if ($exists) {
                continue;
            }

            DB::table('activity_logs')->insert([
                'actor_user_id' => null,
                'actor_name' => $row->created_by,
                'actor_role' => null,
                'module' => $module,
                'action' => $action,
                'reference_no' => $referenceNo,
                'subject_type' => 'notif',
                'subject_id' => (int) $row->id,
                'description' => $description,
                'meta_json' => json_encode(['source' => 'notif_backfill']),
                'created_at' => $row->created_at ?: now(),
                'updated_at' => $row->created_at ?: now(),
            ]);
            $inserted++;
        }

        $this->info('Backfill selesai. Total inserted: ' . $inserted);

        return 0;
    }
}
