<?php
require 'config.php';

header('Content-Type: application/json');

try {
    if (!isset($_FILES['file'])) {
        throw new Exception('No file uploaded');
    }
    
    $file = $_FILES['file'];
    if ($file['error'] !== UPLOAD_ERR_OK) {
        throw new Exception('Upload error: '.$file['error']);
    }
    
    $trackingId = uniqid('job_', true);
    
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $tempPath = UPLOAD_DIR.'/'.$trackingId.'.'.$extension;
    
    if (!move_uploaded_file($file['tmp_name'], $tempPath)) {
        throw new Exception('Failed to store uploaded file');
    }
    
    $jobData = [
        'tracking_id' => $trackingId,
        'file_path' => $tempPath,
        'status' => 'queued',
        'created_at' => time()
    ];
    
    $redis->rpush('file_queue', json_encode($jobData));
    $redis->hset('jobs', $trackingId, json_encode($jobData));
    
    echo json_encode([
        'status' => 'success',
        'tracking_id' => $trackingId
    ]);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}