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

    /**
     * Master switch for Travel & Entertainment's OCR-derived No. Invoice/Receipt
     * extraction + duplicate check. Off means no Gemini call is ever made (no
     * client-side upload preview call, no server-side extraction on save) --
     * rows simply save with no invoice number and no duplicate check, same as
     * before this feature existed. Default off so a fresh deploy never spends
     * OCR quota until an admin deliberately turns it on.
     */
    public static function isTravelEntertainmentOcrCheckEnabled(): bool
    {
        return static::get('travel_entertainment_ocr_check_enabled', '0') === '1';
    }

    /**
     * Travel-only OCR switch. Travel and Entertainment used to share one
     * toggle; they are separate now (Sep 2026 request) so one module can run
     * OCR while the other doesn't. The old combined key is the default for
     * both, so an installation that already had it on keeps working until an
     * admin saves the settings page and writes the per-module keys.
     */
    public static function isTravelOcrCheckEnabled(): bool
    {
        return static::get('travel_ocr_check_enabled', static::get('travel_entertainment_ocr_check_enabled', '0')) === '1';
    }

    /** Entertainment-only OCR switch -- see isTravelOcrCheckEnabled(). */
    public static function isEntertainmentOcrCheckEnabled(): bool
    {
        return static::get('entertainment_ocr_check_enabled', static::get('travel_entertainment_ocr_check_enabled', '0')) === '1';
    }
}
