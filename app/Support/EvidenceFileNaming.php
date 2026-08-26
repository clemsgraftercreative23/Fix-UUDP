<?php

namespace App\Support;

/**
 * Decides what extension an uploaded evidence/bukti file gets saved with.
 *
 * Bug history: every upload path used to trust the extension the browser
 * put on the client filename (`getClientOriginalExtension()`) verbatim, with
 * no whitelist. A camera/phone upload can hand back almost anything
 * (`.jfif`, `.svg`, `.ico`, no extension, `.heic`, ...); once that extension
 * lands on disk, the web server serves it directly (no controller in
 * front of these static files) and picks the browser's Content-Type from
 * that extension alone. An extension the server's default MIME table
 * doesn't recognize as inline-renderable makes the browser download the
 * file instead of previewing it -- even though every view links to it the
 * same way. Resolving the extension from the file's real (sniffed) MIME
 * type first, and only falling back to a whitelisted client extension,
 * keeps every saved file's extension one the browser can always preview.
 */
class EvidenceFileNaming
{
    /** Extensions we know every mainstream browser previews inline. */
    private const SAFE_EXTENSIONS = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'pdf'];

    /** Real (content-sniffed) MIME type -> canonical safe extension. */
    private const MIME_EXTENSION_MAP = [
        'image/jpeg' => 'jpg',
        'image/pjpeg' => 'jpg',
        'image/png' => 'png',
        'image/gif' => 'gif',
        'image/webp' => 'webp',
        'application/pdf' => 'pdf',
    ];

    /**
     * @param string $realMimeType the sniffed MIME type (e.g. $file->getMimeType()), not the client-reported one
     * @param string $clientExtension the extension from the client-supplied filename, used only as a fallback hint
     */
    public static function resolveExtension(string $realMimeType, string $clientExtension): string
    {
        $realMimeType = strtolower(trim($realMimeType));
        if (isset(self::MIME_EXTENSION_MAP[$realMimeType])) {
            return self::MIME_EXTENSION_MAP[$realMimeType];
        }

        $clientExtension = strtolower(trim($clientExtension));
        if ($clientExtension === 'jpeg') {
            $clientExtension = 'jpg';
        }
        if (in_array($clientExtension, self::SAFE_EXTENSIONS, true)) {
            return $clientExtension;
        }

        return 'jpg';
    }

    public static function generateFilename(string $realMimeType, string $clientExtension): string
    {
        return uniqid('bukti_', true) . '.' . self::resolveExtension($realMimeType, $clientExtension);
    }
}
