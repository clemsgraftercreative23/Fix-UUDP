#!/usr/bin/env bash
# Run on server: bash scripts/server-reminder-diagnose.sh
set -euo pipefail

APP_DIR="${APP_DIR:-/var/www/html/uudp}"
cd "$APP_DIR"

echo "=== Code version ==="
wc -l app/Console/Commands/SendApprovalDelayReminder.php
grep -c ApprovalReminderService app/Console/Commands/SendApprovalDelayReminder.php || echo "OLD command (no ApprovalReminderService)"

echo "=== ENV (secrets redacted) ==="
grep -E '^(QUEUE_CONNECTION|APPROVAL_REMINDER_|FONNTE_)' .env | sed 's/FONNTE_TOKEN=.*/FONNTE_TOKEN=***/' || true

echo "=== Migrations ==="
php artisan migrate:status 2>/dev/null | grep -E 'approval_reminder|jobs' || true

echo "=== Tables ==="
php artisan tinker --execute="
echo implode(', ', array_filter(
  \\Schema::getConnection()->getDoctrineSchemaManager()->listTableNames(),
  function (\$t) { return strpos(\$t, 'approval') !== false || \$t === 'jobs' || \$t === 'failed_jobs'; }
));
" 2>/dev/null || echo "(tinker skipped)"

echo "=== Scheduler ==="
php artisan schedule:list 2>/dev/null || true
echo "--- crontab ---"
crontab -l 2>/dev/null || echo "(no crontab for current user)"

echo "=== Queue worker ==="
ps aux | grep '[q]ueue:work' || echo "(no queue:work process)"

echo "=== Dry run ==="
php artisan reimbursement:send-approval-delay-reminder --dry-run

echo "=== Recent reminder logs ==="
php artisan tinker --execute="
\$rows = \\DB::table('approval_reminder_logs')->orderBy('id','desc')->limit(5)->get(['id','status','error_message','created_at']);
foreach (\$rows as \$r) { echo json_encode(\$r) . PHP_EOL; }
" 2>/dev/null || echo "(no approval_reminder_logs table)"

echo "=== Laravel log (reminder/queue) ==="
tail -50 storage/logs/laravel.log 2>/dev/null | grep -iE 'reminder|queue|Fonnte' || echo "(no matches)"
