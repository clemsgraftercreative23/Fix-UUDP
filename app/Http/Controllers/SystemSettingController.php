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
        ]);
    }

    public function update(Request $request)
    {
        $this->guardAdminOnly();

        AppSetting::set(
            'driver_ocr_invoice_check_enabled',
            $request->boolean('driver_ocr_invoice_check_enabled') ? '1' : '0',
            auth()->id()
        );

        return redirect()->back()->with(['success' => 'Pengaturan berhasil disimpan.']);
    }
}
