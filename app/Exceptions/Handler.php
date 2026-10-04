<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\Exceptions\PostTooLargeException;
use Illuminate\Session\TokenMismatchException;

class Handler extends ExceptionHandler
{
    /**
     * A list of the exception types that are not reported.
     *
     * @var array
     */
    protected $dontReport = [
        //
    ];

    /**
     * A list of the inputs that are never flashed for validation exceptions.
     *
     * @var array
     */
    protected $dontFlash = [
        'password',
        'password_confirmation',
    ];

    /**
     * Report or log an exception.
     *
     * @param  \Exception  $exception
     * @return void
     */
    public function report(Exception $exception)
    {
        parent::report($exception);
    }

    /**
     * Render an exception into an HTTP response.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Exception  $exception
     * @return \Illuminate\Http\Response
     */
    public function render($request, Exception $exception)
    {
        if ($exception instanceof TokenMismatchException) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Sesi Anda telah berakhir karena terlalu lama tidak aktif. Silakan muat ulang halaman.',
                ], 419);
            }

            return redirect()
                ->back()
                ->withErrors(['Sesi Anda telah berakhir karena terlalu lama tidak aktif. Halaman sudah dimuat ulang, silakan coba lagi.']);
        }

        // Upload melebihi post_max_size PHP ditolak oleh middleware
        // ValidatePostSize SEBELUM request sampai ke controller, sehingga
        // pengecekan ukuran di controller tidak pernah jalan dan user hanya
        // melihat halaman "Whoops". Beri pesan yang terbaca, seperti
        // TokenMismatchException di atas.
        if ($exception instanceof PostTooLargeException) {
            $message = 'Ukuran file yang diunggah terlalu besar. '
                . 'Maksimal ' . $this->postMaxSizeLabel()
                . ' untuk satu kali pengiriman. '
                . 'Silakan unggah file yang lebih kecil.';

            if ($request->expectsJson()) {
                return response()->json(['message' => $message], 413);
            }

            return redirect()->back()->withErrors([$message]);
        }

        return parent::render($request, $exception);
    }

    /**
     * post_max_size PHP dalam bentuk yang mudah dibaca ("8MB").
     *
     * Dibaca dari konfigurasi agar pesan tidak pernah bertentangan dengan
     * batas yang sebenarnya berlaku di server.
     */
    private function postMaxSizeLabel(): string
    {
        $raw = trim((string) ini_get('post_max_size'));
        if ($raw === '') {
            return 'ukuran yang ditentukan server';
        }

        $unit = strtoupper(substr($raw, -1));
        $value = (float) $raw;
        $bytes = $value;
        if ($unit === 'G') {
            $bytes = $value * 1024 * 1024 * 1024;
        } elseif ($unit === 'M') {
            $bytes = $value * 1024 * 1024;
        } elseif ($unit === 'K') {
            $bytes = $value * 1024;
        }

        if ($bytes >= 1024 * 1024) {
            return rtrim(rtrim(number_format($bytes / 1048576, 1, '.', ''), '0'), '.') . 'MB';
        }

        return rtrim(rtrim(number_format($bytes / 1024, 1, '.', ''), '0'), '.') . 'KB';
    }
}
