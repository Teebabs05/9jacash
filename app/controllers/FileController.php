<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Controller;

/**
 * Serves files stored under storage/uploads (outside the public webroot)
 * so receipts / KYC docs / task proofs are never directly link-guessable.
 * Avatars are treated as low-sensitivity and served to any logged-in user.
 */
class FileController extends Controller
{
    private const PRIVATE_FOLDERS = ['receipts', 'kyc', 'proofs'];

    public function serve(string $type, string $filename): void
    {
        $type = preg_replace('/[^a-z]/', '', $type);
        $filename = basename($filename);

        if (!preg_match('/^[a-f0-9]{32}\.(jpg|jpeg|png|webp|gif|pdf)$/i', $filename)) {
            http_response_code(404);
            exit('Not found');
        }

        $path = STORAGE_PATH . "/uploads/{$type}/{$filename}";
        if (!is_file($path)) {
            http_response_code(404);
            exit('Not found');
        }

        if (in_array($type, self::PRIVATE_FOLDERS, true)) {
            $this->authorizeAccess($type, $filename);
        }

        $mime = mime_content_type($path) ?: 'application/octet-stream';
        header('Content-Type: ' . $mime);
        header('Content-Length: ' . filesize($path));
        header('Cache-Control: private, max-age=3600');
        readfile($path);
        exit;
    }

    private function authorizeAccess(string $type, string $filename): void
    {
        $user = current_user();
        if (!$user) {
            http_response_code(403);
            exit('Forbidden');
        }
        if (($user['role'] ?? '') === 'admin') {
            return;
        }

        $relativePath = "{$type}/{$filename}";
        $owned = match ($type) {
            'receipts' => db()->fetch('SELECT id FROM deposits WHERE receipt_path = :p AND user_id = :u', ['p' => $relativePath, 'u' => $user['id']]) !== false,
            'kyc' => db()->fetch('SELECT id FROM kyc_submissions WHERE (document_path = :p OR selfie_path = :p) AND user_id = :u', ['p' => $relativePath, 'u' => $user['id']]) !== false,
            'proofs' => db()->fetch('SELECT id FROM task_submissions WHERE proof_file = :p AND user_id = :u', ['p' => $relativePath, 'u' => $user['id']]) !== false,
            default => false,
        };

        if (!$owned) {
            http_response_code(403);
            exit('Forbidden');
        }
    }
}
