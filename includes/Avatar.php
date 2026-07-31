<?php
/**
 * AeroBook – Avatar & File Upload Handler
 *
 * Secure file upload handler with:
 * - MIME type validation (not just extension)
 * - Random filename generation (collision prevention)
 * - Size limits
 * - Cleanup of old avatars
 * - WebP conversion for optimization
 */

class AeroUpload {
    private $uploadDir;
    private $allowedMimes;
    private $maxSize;
    private $errors = [];

    public function __construct() {
        $this->uploadDir = __DIR__ . '/../uploads/avatars';
        $this->allowedMimes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        $this->maxSize = defined('MAX_AVATAR_SIZE') ? MAX_AVATAR_SIZE : 2097152; // 2MB

        if (!is_dir($this->uploadDir)) {
            @mkdir($this->uploadDir, 0755, true);
        }

        // Create .htaccess to protect uploads
        $htaccess = $this->uploadDir . '/.htaccess';
        if (!file_exists($htaccess)) {
            @file_put_contents($htaccess, "Allow from all\n");
        }
    }

    /**
     * Upload an avatar file.
     *
     * @param array $file $_FILES['avatar']
     * @param int $userId Current user ID
     * @return string|false Filename on success, false on failure
     */
    public function uploadAvatar($file, $userId) {
        $this->errors = [];

        // Check for upload errors
        if ($file['error'] !== UPLOAD_ERR_OK) {
            $this->errors[] = $this->uploadError($file['error']);
            return false;
        }

        // Validate file size
        if ($file['size'] > $this->maxSize) {
            $this->errors[] = 'File size exceeds ' . ($this->maxSize / 1048576) . 'MB limit.';
            return false;
        }

        // Validate MIME type using finfo (more reliable than extension)
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        if (!in_array($mime, $this->allowedMimes)) {
            $this->errors[] = 'Invalid file type. Only JPG, PNG, GIF, and WebP are allowed.';
            return false;
        }

        // Validate image dimensions (reject very small images)
        $dimensions = getimagesize($file['tmp_name']);
        if (!$dimensions || $dimensions[0] < 64 || $dimensions[1] < 64) {
            $this->errors[] = 'Image must be at least 64×64 pixels.';
            return false;
        }

        // Generate unique filename
        $ext = $this->getExtension($mime);
        $filename = 'avatar_' . $userId . '_' . bin2hex(random_bytes(8)) . '.' . $ext;
        $destPath = $this->uploadDir . '/' . $filename;

        // Optional: Convert to WebP for smaller file sizes
        if ($ext !== 'webp' && function_exists('imagewebp')) {
            $webpFilename = 'avatar_' . $userId . '_' . bin2hex(random_bytes(8)) . '.webp';
            $webpPath = $this->uploadDir . '/' . $webpFilename;
            if ($this->convertToWebP($file['tmp_name'], $webpPath, $mime)) {
                $filename = $webpFilename;
                $destPath = $webpPath;
            } else {
                // Fall back to original format
                move_uploaded_file($file['tmp_name'], $destPath);
            }
        } else {
            move_uploaded_file($file['tmp_name'], $destPath);
        }

        // Delete old avatar
        $this->deleteOldAvatar($userId);

        // Remove old avatars for this user
        $this->cleanupUserAvatars($userId, $filename);

        return $filename;
    }

    /**
     * Convert image to WebP format for optimization.
     */
    private function convertToWebP($sourcePath, $destPath, $mime) {
        switch ($mime) {
            case 'image/jpeg': $img = imagecreatefromjpeg($sourcePath); break;
            case 'image/png': $img = imagecreatefrompng($sourcePath); break;
            case 'image/gif': $img = imagecreatefromgif($sourcePath); break;
            default: return false;
        }
        if (!$img) return false;

        // Resize to max 256px if larger
        $width = imagesx($img);
        $height = imagesy($img);
        if ($width > 256 || $height > 256) {
            $ratio = min(256 / $width, 256 / $height);
            $newWidth = round($width * $ratio);
            $newHeight = round($height * $ratio);
            $resized = imagecreatetruecolor($newWidth, $newHeight);
            imagecopyresampled($resized, $img, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
            imagedestroy($img);
            $img = $resized;
        }

        $result = imagewebp($img, $destPath, 80);
        imagedestroy($img);
        return $result;
    }

    /**
     * Delete old avatar file from the database record.
     */
    private function deleteOldAvatar($userId) {
        global $conn;
        $stmt = mysqli_prepare($conn, "SELECT avatar FROM users WHERE id = ?");
        mysqli_stmt_bind_param($stmt, "i", $userId);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        $user = mysqli_fetch_assoc($result);
        mysqli_stmt_close($stmt);

        if (!empty($user['avatar'])) {
            $oldPath = $this->uploadDir . '/' . $user['avatar'];
            if (file_exists($oldPath)) {
                @unlink($oldPath);
            }
        }
    }

    /**
     * Clean up old avatar files for a user, keeping only the current one.
     */
    private function cleanupUserAvatars($userId, $keepFilename) {
        $pattern = $this->uploadDir . '/avatar_' . $userId . '_*';
        foreach (glob($pattern) as $file) {
            if (basename($file) !== $keepFilename) {
                @unlink($file);
            }
        }
    }

    /**
     * Get file extension from MIME type.
     */
    private function getExtension($mime) {
        $map = [
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/gif' => 'gif',
            'image/webp' => 'webp',
        ];
        return $map[$mime] ?? 'jpg';
    }

    /**
     * Convert PHP upload error codes to messages.
     */
    private function uploadError($code) {
        $errors = [
            UPLOAD_ERR_INI_SIZE => 'File exceeds server upload limit.',
            UPLOAD_ERR_FORM_SIZE => 'File exceeds form size limit.',
            UPLOAD_ERR_PARTIAL => 'File was only partially uploaded.',
            UPLOAD_ERR_NO_FILE => 'No file was selected.',
            UPLOAD_ERR_NO_TMP_DIR => 'Server configuration error.',
            UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk.',
        ];
        return $errors[$code] ?? 'Unknown upload error.';
    }

    /**
     * Get all error messages.
     */
    public function getErrors() {
        return $this->errors;
    }

    /**
     * Get the avatar URL for display.
     */
    public static function avatarUrl($filename) {
        if (empty($filename)) {
            return BASE_URL . 'assets/img/default-avatar.svg';
        }
        return BASE_URL . 'uploads/avatars/' . $filename;
    }
}
