#!/usr/bin/env bash
# Setup approval reminder automation on server.
# Usage: cd /var/www/html/uudp && sudo bash scripts/server-reminder-setup.sh
set -euo pipefail

APP_DIR="${APP_DIR:-/var/www/html/uudp}"
CRON_USER="${CRON_USER:-www-data}"

cd "$APP_DIR"

echo "=== Migrate (approval_reminders + jobs) ==="
php artisan migrate --force

echo "=== Clear config cache ==="
php artisan config:clear

echo "=== Ensure APPROVAL_REMINDER env (append if missing) ==="
ENV_FILE=".env"
touch "$ENV_FILE"
grep -q '^APPROVAL_REMINDER_INITIAL_DELAY_MINUTES=' "$ENV_FILE" || echo 'APPROVAL_REMINDER_INITIAL_DELAY_MINUTES=30' >> "$ENV_FILE"
grep -q '^APPROVAL_REMINDER_REPEAT_INTERVAL_MINUTES=' "$ENV_FILE" || echo 'APPROVAL_REMINDER_REPEAT_INTERVAL_MINUTES=30' >> "$ENV_FILE"
grep -q '^APPROVAL_REMINDER_MAX_DURATION_MINUTES=' "$ENV_FILE" || echo 'APPROVAL_REMINDER_MAX_DURATION_MINUTES=720' >> "$ENV_FILE"
grep -q '^QUEUE_CONNECTION=' "$ENV_FILE" || echo 'QUEUE_CONNECTION=database' >> "$ENV_FILE"

echo "=== Install cron (schedule:run every minute) ==="
CRON_LINE="* * * * * cd ${APP_DIR} && php artisan schedule:run >> /dev/null 2>&1"
(sudo crontab -u "$CRON_USER" -l 2>/dev/null | grep -v 'artisan schedule:run' || true; echo "$CRON_LINE") | sudo crontab -u "$CRON_USER" -

echo "=== Install supervisor (queue worker) ==="
if [ -f deploy/supervisor/uudp-queue.conf ]; then
  sudo cp deploy/supervisor/uudp-queue.conf /etc/supervisor/conf.d/uudp-queue.conf
  sudo supervisorctl reread 2>/dev/null && sudo supervisorctl update 2>/dev/null && sudo supervisorctl start uudp-queue 2>/dev/null || true
fi

echo "=== Dry run ==="
php artisan reimbursement:send-approval-delay-reminder --dry-run

echo "Done. Start queue worker via supervisor or run: php artisan queue:work"
