<?php

function bvassist_upload_dir(): string
{
    return dirname(__DIR__) . DIRECTORY_SEPARATOR . 'uploads';
}

function bvassist_upload_url_from_path(?string $path): string
{
    $path = trim((string) $path);
    if ($path === '') {
        return '';
    }

    $normalized = str_replace('\\', '/', $path);
    $normalized = ltrim($normalized, '/');

    if (str_starts_with($normalized, 'uploads/')) {
        return '../' . $normalized;
    }

    return '../uploads/' . basename($normalized);
}

function bvassist_upload_error_message(int $code): string
{
    return match ($code) {
        UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => 'The uploaded file is too large.',
        UPLOAD_ERR_PARTIAL => 'The file was only partially uploaded.',
        UPLOAD_ERR_NO_FILE => 'Please choose a file to upload.',
        UPLOAD_ERR_NO_TMP_DIR => 'Server is missing a temporary upload folder.',
        UPLOAD_ERR_CANT_WRITE => 'Server could not save the uploaded file.',
        UPLOAD_ERR_EXTENSION => 'A PHP extension stopped the upload.',
        default => 'Unknown upload error.',
    };
}

function bvassist_store_upload(array $file, array $allowedExtensions = ['pdf', 'jpg', 'jpeg', 'png', 'gif', 'webp']): array
{
    if (!isset($file['error']) || is_array($file['error'])) {
        return ['ok' => false, 'error' => 'Invalid upload data.'];
    }

    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['ok' => false, 'error' => bvassist_upload_error_message((int) $file['error'])];
    }

    if (empty($file['tmp_name']) || !is_uploaded_file($file['tmp_name'])) {
        return ['ok' => false, 'error' => 'Upload failed because the file was not received correctly.'];
    }

    $originalName = (string) ($file['name'] ?? 'file');
    $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
    if ($extension === '' || !in_array($extension, $allowedExtensions, true)) {
        return ['ok' => false, 'error' => 'Only PDF and image files are allowed.'];
    }

    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mimeType = $finfo ? finfo_file($finfo, $file['tmp_name']) : false;
    if ($finfo) {
        finfo_close($finfo);
    }

    $allowedMimes = [
        'application/pdf',
        'image/jpeg',
        'image/png',
        'image/gif',
        'image/webp',
    ];

    if ($mimeType === false || (!str_starts_with($mimeType, 'image/') && !in_array($mimeType, $allowedMimes, true))) {
        return ['ok' => false, 'error' => 'Only PDF and image files are allowed.'];
    }

    $baseName = pathinfo($originalName, PATHINFO_FILENAME);
    $baseName = preg_replace('/[^A-Za-z0-9._-]+/', '_', $baseName);
    $baseName = trim($baseName, '._-');
    if ($baseName === '') {
        $baseName = 'upload';
    }

    $storedFileName = time() . '_' . $baseName . '_' . bin2hex(random_bytes(3)) . '.' . $extension;
    $uploadDir = bvassist_upload_dir();
    $targetPath = $uploadDir . DIRECTORY_SEPARATOR . $storedFileName;

    if (!is_dir($uploadDir) && !mkdir($uploadDir, 0775, true) && !is_dir($uploadDir)) {
        return ['ok' => false, 'error' => 'Uploads folder could not be created.'];
    }

    if (!move_uploaded_file($file['tmp_name'], $targetPath)) {
        return ['ok' => false, 'error' => 'Unable to save the uploaded file.'];
    }

    return [
        'ok' => true,
        'file_name' => $storedFileName,
        'file_path' => 'uploads/' . $storedFileName,
    ];
}

function bvassist_redirect_back(string $defaultPath, string $status, string $message): void
{
    $target = $_SERVER['HTTP_REFERER'] ?? $defaultPath;
    $target = trim((string) $target);

    if ($target === '') {
        $target = $defaultPath;
    }

    $separator = str_contains($target, '?') ? '&' : '?';
    $query = http_build_query([
        'upload_status' => $status,
        'upload_message' => $message,
    ]);

    header('Location: ' . $target . $separator . $query);
    exit();
}

