<?php
declare(strict_types=1);

namespace App\Core;

/**
 * Validates and stores an uploaded image.
 *
 * Protections:
 *  - only JPEG, PNG and WebP, detected from the file contents (finfo), not the browser
 *  - extension must match the detected type
 *  - size limit (UPLOAD_MAX_BYTES) and dimension limit
 *  - the file must decode as an image (getimagesize)
 *  - if GD is available the image is re-encoded, which strips embedded payloads
 *  - random server-side filename, so no path traversal or overwriting
 *  - public/uploads/.htaccess stops Apache executing anything in that folder
 */
final class ImageUpload
{
    /**
     * @return string path relative to public/uploads (e.g. "products/ab12...jpg")
     * @throws \InvalidArgumentException with a user-facing Malay message
     */
    public static function store(array $file, string $folder): string
    {
        $config = config('upload');
        if (!in_array($folder, ['products', 'shops', 'categories'], true)) {
            throw new \LogicException('Invalid upload folder');
        }

        $error = $file['error'] ?? UPLOAD_ERR_NO_FILE;
        if ($error !== UPLOAD_ERR_OK) {
            throw new \InvalidArgumentException(match ($error) {
                UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => 'Saiz gambar terlalu besar.',
                UPLOAD_ERR_NO_FILE => 'Tiada gambar dipilih.',
                UPLOAD_ERR_PARTIAL => 'Muat naik tidak lengkap. Cuba lagi.',
                default => 'Muat naik gambar gagal. Cuba lagi.',
            });
        }
        $tmp = $file['tmp_name'] ?? '';
        if (!is_string($tmp) || !is_uploaded_file($tmp)) {
            throw new \InvalidArgumentException('Fail muat naik tidak sah.');
        }
        if (filesize($tmp) > $config['max_bytes']) {
            $mb = round($config['max_bytes'] / 1048576, 1);
            throw new \InvalidArgumentException("Saiz gambar mesti tidak melebihi {$mb} MB.");
        }

        $mime = (new \finfo(FILEINFO_MIME_TYPE))->file($tmp);
        if (!isset($config['mime_types'][$mime])) {
            throw new \InvalidArgumentException('Hanya gambar JPG, PNG atau WebP dibenarkan.');
        }
        $extension = $config['mime_types'][$mime];
        $originalExt = strtolower(pathinfo((string) ($file['name'] ?? ''), PATHINFO_EXTENSION));
        $allowedExt = $extension === 'jpg' ? ['jpg', 'jpeg'] : [$extension];
        if (!in_array($originalExt, $allowedExt, true)) {
            throw new \InvalidArgumentException('Sambungan fail tidak sepadan dengan jenis gambar.');
        }

        $size = @getimagesize($tmp);
        if ($size === false || $size[0] < 1 || $size[1] < 1) {
            throw new \InvalidArgumentException('Fail ini bukan gambar yang sah.');
        }
        if ($size[0] > $config['max_dimension'] || $size[1] > $config['max_dimension']) {
            throw new \InvalidArgumentException('Dimensi gambar terlalu besar (maksimum ' . $config['max_dimension'] . ' piksel).');
        }

        $name = bin2hex(random_bytes(16)) . '.' . $extension;
        $targetDir = BASE_PATH . '/public/uploads/' . $folder;
        if (!is_dir($targetDir) && !mkdir($targetDir, 0755, true)) {
            throw new \RuntimeException('Upload directory missing');
        }
        $target = $targetDir . '/' . $name;

        if (!self::reencode($tmp, $target, $mime)) {
            if (!move_uploaded_file($tmp, $target)) {
                throw new \RuntimeException('Could not store uploaded file');
            }
        }
        return $folder . '/' . $name;
    }

    private static function reencode(string $source, string $target, string $mime): bool
    {
        if (!function_exists('imagecreatefromstring')) {
            return false;
        }
        $image = @imagecreatefromstring((string) file_get_contents($source));
        if ($image === false) {
            throw new \InvalidArgumentException('Fail ini bukan gambar yang sah.');
        }
        $ok = match ($mime) {
            'image/png'  => (imagesavealpha($image, true) && imagepng($image, $target, 6)),
            'image/webp' => function_exists('imagewebp') ? imagewebp($image, $target, 85) : false,
            default      => imagejpeg($image, $target, 85),
        };
        imagedestroy($image);
        return $ok;
    }

    public static function delete(?string $relativePath): void
    {
        if (!$relativePath || !preg_match('#^(products|shops|categories)/[a-f0-9]{32}\.(jpg|png|webp)$#', $relativePath)) {
            return; // never delete anything outside our own generated filenames (e.g. demo images)
        }
        $file = BASE_PATH . '/public/uploads/' . $relativePath;
        if (is_file($file)) {
            unlink($file);
        }
    }
}
