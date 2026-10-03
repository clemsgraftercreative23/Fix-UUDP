<?php

namespace App\Http\Controllers;

use App\AppSetting;
use App\Support\JabatanClassifier;
use Illuminate\Http\Request;

class SystemSettingController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    private function guardAdminOnly()
    {
        if (!JabatanClassifier::isAdmin(auth()->user()->jabatan ?? null)) {
            abort(403);
        }
    }

    public function index()
    {
        $this->guardAdminOnly();

        return view('settings.feature-toggle', [
            'driverOcrEnabled' => AppSetting::isDriverOcrCheckEnabled(),
            'travelOcrEnabled' => AppSetting::isTravelOcrCheckEnabled(),
            'entertainmentOcrEnabled' => AppSetting::isEntertainmentOcrCheckEnabled(),
        ]);
    }

    public function update(Request $request)
    {
        $this->guardAdminOnly();

        AppSetting::set(
            'driver_ocr_invoice_check_enabled',
            $request->input('driver_ocr_invoice_check_enabled') == '1' ? '1' : '0',
            auth()->id()
        );
        // Travel and Entertainment have their own switch now. The legacy
        // combined key is kept in sync with Travel so anything still reading
        // it (and the fallback in AppSetting) stays consistent.
        $travel = $request->input('travel_ocr_check_enabled') == '1' ? '1' : '0';
        $entertainment = $request->input('entertainment_ocr_check_enabled') == '1' ? '1' : '0';
        AppSetting::set('travel_ocr_check_enabled', $travel, auth()->id());
        AppSetting::set('entertainment_ocr_check_enabled', $entertainment, auth()->id());
        AppSetting::set('travel_entertainment_ocr_check_enabled', $travel, auth()->id());

        return redirect()->back()->with(['success' => 'Pengaturan berhasil disimpan.']);
    }
}
