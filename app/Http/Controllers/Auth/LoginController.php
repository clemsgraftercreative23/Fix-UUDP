<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\AuthenticatesUsers;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class LoginController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Login Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles authenticating users for the application and
    | redirecting them to your home screen. The controller uses a trait
    | to conveniently provide its functionality to your applications.
    |
    */

    use AuthenticatesUsers;

    /**
     * Where to redirect users after login.
     *
     * @var string
     */
    protected $redirectTo = '/otorisasi';

    /**
     * Create a new controller instance.
     *
     * @return void
     */
     public function username()
    {
        return 'username';
    }

    public function idKaryawan()
    {
        return 'idKaryawan';
    }
    public function __construct()
    {
      // $this->middleware('auth:web', ['except' => ['/']]);

        $this->middleware('guest')->except('logout');
    }

    /**
     * Wrong username/password: show a friendly Indonesian message instead of
     * the raw "auth.failed" translation key (which is missing for the "id"
     * locale configured in config/app.php).
     */
    protected function sendFailedLoginResponse(Request $request)
    {
        throw ValidationException::withMessages([
            'login' => ['Username atau password yang Anda masukkan salah. Silakan periksa kembali dan coba lagi.'],
        ]);
    }

    /**
     * Too many failed attempts: same friendly-message reasoning as
     * sendFailedLoginResponse() above, applied to the "auth.throttle" key.
     */
    protected function sendLockoutResponse(Request $request)
    {
        $seconds = $this->limiter()->availableIn(
            $this->throttleKey($request)
        );

        $wait = $seconds >= 60
            ? ceil($seconds / 60) . ' menit'
            : $seconds . ' detik';

        throw ValidationException::withMessages([
            'login' => ["Terlalu banyak percobaan login yang gagal. Silakan coba lagi dalam {$wait}."],
        ])->status(429);
    }
}
