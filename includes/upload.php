<?php
// Secure image upload helpers.
require_once __DIR__ . '/functions.php';

const UPLOAD_DIR       = __DIR__ . '/../uploads';
const UPLOAD_MAX_BYTES = 2 * 1024 * 1024; // 2 MB
const UPLOAD_MAX_PIXELS = 5000;           // max width or height

// The extension is chosen from the REAL detected type, never from the visitor's filename.
const UPLOAD_ALLOWED = [
    'image/jpeg' => 'jpg',
    'image/png'  => 'png',
    'image/webp' => 'webp',
    'image/gif'  => 'gif',
];

/**
 * Validate and store an uploaded image.
 * Returns ['path' => 'uploads/xxxx.jpg'] on success, or ['error' => 'message'].
 */
function save_uploaded_image(array $file): array
{
    if (!isset($file['error']) || is_array($file['error'])) {
        return ['error' => 'Invalid upload.'];
    }

    switch ($file['error']) {
        case UPLOAD_ERR_OK:
            break;
        case UPLOAD_ERR_NO_FILE:
            return ['error' => 'No file was selected.'];
        case UPLOAD_ERR_INI_SIZE:
        case UPLOAD_ERR_FORM_SIZE:
            return ['error' => 'The image is too large (maximum 2 MB).'];
        default:
            return ['error' => 'The upload failed. Please try again.'];
    }

    // File size limit
    if ($file['size'] <= 0 || $file['size'] > UPLOAD_MAX_BYTES) {
        return ['error' => 'The image must be larger than 0 and at most 2 MB.'];
    }

    // The file must really come from an HTTP upload, not a path the visitor chose.
    if (!is_uploaded_file($file['tmp_name'])) {
        return ['error' => 'Invalid upload.'];
    }

    // 1) Check the REAL content type by reading the file's bytes.
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime  = $finfo->file($file['tmp_name']);
    if (!isset(UPLOAD_ALLOWED[$mime])) {
        return ['error' => 'Only JPG, PNG, WEBP or GIF images are allowed.'];
    }

    // 2) The file must also parse as a real image with sensible dimensions.
    $info = @getimagesize($file['tmp_name']);
    if ($info === false || ($info['mime'] ?? '') !== $mime) {
        return ['error' => 'The file is not a valid image.'];
    }
    if ($info[0] < 1 || $info[1] < 1 || $info[0] > UPLOAD_MAX_PIXELS || $info[1] > UPLOAD_MAX_PIXELS) {
        return ['error' => 'The image dimensions must not exceed ' . UPLOAD_MAX_PIXELS . ' pixels.'];
    }

    // 3) Safe, unique filename: random name + extension from the detected type.
    $name = bin2hex(random_bytes(16)) . '.' . UPLOAD_ALLOWED[$mime];
    $dest = UPLOAD_DIR . '/' . $name;

    if (!move_uploaded_file($file['tmp_name'], $dest)) {
        return ['error' => 'The image could not be saved.'];
    }
    @chmod($dest, 0644); // readable, never executable

    return ['path' => 'uploads/' . $name];
}

// Delete a file we stored earlier. Only paths that match our own naming pattern are accepted,
// so images/jez.jpg or any other path can never be deleted by mistake.
function delete_uploaded_image(?string $path): void
{
    if ($path !== null && preg_match('#^uploads/[a-f0-9]{32}\.(jpg|png|webp|gif)$#', $path)) {
        $full = UPLOAD_DIR . '/' . basename($path);
        if (is_file($full)) {
            @unlink($full);
        }
    }
}