<?php
require 'config.php';

header('Content-Type: application/json');

try {
    if (!isset($_GET['tracking_id'])) {
        throw new Exception('Missing tracking ID');
    }
    
    $trackingId = $_GET['tracking_id'];
    $jobData = $redis->hget('jobs', $trackingId);
    
    if (!$jobData) {
        throw new Exception('Invalid tracking ID');
    }
    
    $job = json_decode($jobData, true);
    
    unset($job['file_path']);
    
    echo json_encode($job);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}