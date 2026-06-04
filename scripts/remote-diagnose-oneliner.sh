#!/usr/bin/env bash
set -euo pipefail
cd /var/www/html/uudp
echo "=== CONNECTED $(hostname) ==="
echo "=== Code ==="
wc -l app/Console/Commands/SendApprovalDelayReminder.php
grep -c ApprovalReminderService app/Console/Commands/SendApprovalDelayReminder.php 2>/dev/null || echo "0"
echo "=== ENV ==="
grep -E '^(QUEUE_CONNECTION|APPROVAL_REMINDER_|FONNTE_)' .env 2>/dev/null | sed 's/FONNTE_TOKEN=.*/FONNTE_TOKEN=***/' || true
echo "=== Migrate status ==="
php artisan migrate:status 2>/dev/null | grep -E 'approval_reminder|queue|jobs' || true
echo "=== Cron (www-data) ==="
sudo crontab -u www-data -l 2>/dev/null | grep schedule || echo "(no www-data cron)"
crontab -l 2>/dev/null | grep schedule || echo "(no user cron)"
echo "=== Queue worker ==="
ps aux | grep '[q]ueue:work' || echo "(no worker)"
echo "=== Dry run ==="
php artisan reimbursement:send-approval-delay-reminder --dry-run 2>&1 || true
echo "=== Jobs queue count ==="
php artisan tinker --execute="echo 'jobs='.(Schema::hasTable('jobs')?DB::table('jobs')->count():'no_table');" 2>/dev/null || true
echo "=== Last reminder logs ==="
php artisan tinker --execute="if(Schema::hasTable('approval_reminder_logs')){foreach(DB::table('approval_reminder_logs')->orderBy('id','desc')->limit(3)->get() as \$r) echo json_encode(\$r).PHP_EOL;}" 2>/dev/null || true
echo "=== DONE ==="
