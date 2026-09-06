<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

/**
 * Minimal key/value store for admin-toggleable runtime settings. Values are
 * always stored as strings; callers normalize on read (see get()/set()).
 */
class AppSetting extends Model
{
    protected $table = 'app_settings';

    protected $guarded = [];

    public static function get(string $key, $default = null)
    {
        $value = static::where('key', $key)->value('value');

        return $value !== null ? $value : $default;
    }

    public static function set(string $key, $value, ?int $updatedBy = null): void
    {
        static::updateOrCreate(
            ['key' => $key],
            ['value' => (string) $value, 'updated_by' => $updatedBy]
        );
    }

    /** Whether Driver reimbursement's manual "No. Invoice/Receipt" has been switched to OCR-derived + duplicate-checked, like Travel/Entertainment. */
    public static function isDriverOcrCheckEnabled(): bool
    {
        return static::get('driver_ocr_invoice_check_enabled', '0') === '1';
    }
}
