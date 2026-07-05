<?php

declare(strict_types=1);

namespace App\Core;

use Exception;

/**
 * Secure file upload handling: extension + real MIME sniffing,
 * size caps, random filenames (no user-controlled path/name),
 * stored outside of executable-script directories where possible.
 */
class Upload
{
    private const ALLOWED_IMAGE_MIME = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
        'image/gif' => 'gif',
    ];

    private const ALLOWED_DOC_MIME = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
        'application/pdf' => 'pdf',
    ];

    /**
     * @return string relative path under storage/uploads/{folder}
     */
    public static function image(array $file, string $folder, int $maxSizeBytes = 2 * 1024 * 1024): string
    {
        return self::handle($file, $folder, self::ALLOWED_IMAGE_MIME, $maxSizeBytes);
    }

    public static function document(array $file, string $folder, int $maxSizeBytes = 5 * 1024 * 1024): string
    {
        return self::handle($file, $folder, self::ALLOWED_DOC_MIME, $maxSizeBytes);
    }

    private static function handle(array $file, string $folder, array $allowedMime, int $maxSize): string
    {
        if (!isset($file['tmp_name']) || $file['error'] !== UPLOAD_ERR_OK) {
            throw new Exception('Upload failed. Please try again.');
        }

        if (!is_uploaded_file($file['tmp_name'])) {
            throw new Exception('Invalid upload.');
        }

        if ($file['size'] > $maxSize) {
            throw new Exception('File exceeds the maximum allowed size of ' . round($maxSize / 1024 / 1024, 1) . 'MB.');
        }

        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        if (!isset($allowedMime[$mime])) {
            throw new Exception('Unsupported file type.');
        }

        $folder = trim(preg_replace('/[^a-z0-9_\-]/i', '', $folder), '/');
        $targetDir = STORAGE_PATH . '/uploads/' . $folder;
        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0755, true);
        }

        $filename = bin2hex(random_bytes(16)) . '.' . $allowedMime[$mime];
        $targetPath = $targetDir . '/' . $filename;

        if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
            throw new Exception('Could not save uploaded file.');
        }

        chmod($targetPath, 0644);

        return $folder . '/' . $filename;
    }
}
