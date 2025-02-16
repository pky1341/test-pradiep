<?php

function secureImageUpload($file, $uploadDir, $maxFileSize = 2097152, $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp']) {
    if (!isset($file) || !is_array($file) || $file['error'] !== UPLOAD_ERR_OK) {
        $errorMessage = isset($file['error']) ? getUploadErrorMessage($file['error']) : 'No file uploaded';
        return ['success' => false, 'message' => $errorMessage];
    }

    if ($file['size'] > $maxFileSize) {
        return ['success' => false, 'message' => 'File exceeds the maximum allowed size'];
    }

    if (!is_dir($uploadDir) && !mkdir($uploadDir, 0755, true)) {
        return ['success' => false, 'message' => 'Failed to create upload directory'];
    }

    if (!is_writable($uploadDir)) {
        return ['success' => false, 'message' => 'Upload directory is not writable'];
    }

    $reportedMimeType = $file['type'];
    $actualMimeType = mime_content_type($file['tmp_name']);
    
    if (!in_array($reportedMimeType, $allowedTypes) || !in_array($actualMimeType, $allowedTypes)) {
        return ['success' => false, 'message' => 'Invalid file type. Only images are allowed'];
    }

    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $validExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    
    if (!in_array(strtolower($extension), $validExtensions)) {
        return ['success' => false, 'message' => 'Invalid file extension'];
    }
    
    $newFilename = bin2hex(random_bytes(16)) . '.' . $extension;
    $destination = $uploadDir . $newFilename;

    $imageInfo = @getimagesize($file['tmp_name']);
    if ($imageInfo === false) {
        return ['success' => false, 'message' => 'Invalid image file'];
    }

    if (!move_uploaded_file($file['tmp_name'], $destination)) {
        return ['success' => false, 'message' => 'Failed to move uploaded file'];
    }

    chmod($destination, 0644);

    return [
        'success' => true,
        'path' => $destination,
        'filename' => $newFilename,
        'mime_type' => $actualMimeType,
        'size' => $file['size']
    ];
}


function getUploadErrorMessage($errorCode) {
    switch ($errorCode) {
        case UPLOAD_ERR_INI_SIZE:
            return 'The uploaded file exceeds the upload_max_filesize directive in php.ini';
        case UPLOAD_ERR_FORM_SIZE:
            return 'The uploaded file exceeds the MAX_FILE_SIZE directive in the HTML form';
        case UPLOAD_ERR_PARTIAL:
            return 'The uploaded file was only partially uploaded';
        case UPLOAD_ERR_NO_FILE:
            return 'No file was uploaded';
        case UPLOAD_ERR_NO_TMP_DIR:
            return 'Missing a temporary folder';
        case UPLOAD_ERR_CANT_WRITE:
            return 'Failed to write file to disk';
        case UPLOAD_ERR_EXTENSION:
            return 'A PHP extension stopped the file upload';
        default:
            return 'Unknown upload error';
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['image'])) {
    $uploadDir = __DIR__ . '/uploads/';
    $result = secureImageUpload($_FILES['image'], $uploadDir);
    
    if ($result['success']) {
        $displayPath = 'uploads/' . $result['filename'];
        header('Location: index.php?status=success&message=File uploaded successfully!&path=' . urlencode($displayPath));
    } else {
        header('Location: index.php?status=error&message=' . urlencode($result['message']));
    }
    exit;
} else {
    header('Location: index.php?status=error&message=Invalid request');
    exit;
}