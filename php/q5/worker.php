<?php
require 'config.php';

$logger->info('Worker started');

while (true) {
    try {
        $jobJson = $redis->blpop('file_queue', 30);
        
        if (!$jobJson) {
            continue; 
        }
        
        $job = json_decode($jobJson[1], true);
        $trackingId = $job['tracking_id'];
        
        $job['status'] = 'processing';
        $job['started_at'] = time();
        $redis->hset('jobs', $trackingId, json_encode($job));
        
        try {
            $file = new SplFileObject($job['file_path']);
            $file->setFlags(SplFileObject::READ_AHEAD | SplFileObject::SKIP_EMPTY);
            
            $lineCount = 0;
            foreach ($file as $line) {
                $lineCount++;
                if ($lineCount % 1000 === 0) {
                    $redis->hset('jobs', $trackingId, json_encode([
                        'progress' => $lineCount,
                        'status' => 'processing'
                    ]));
                }
            }
            
            $newPath = PROCESSED_DIR.'/'.$trackingId.'.csv';
            rename($job['file_path'], $newPath);
            
            $redis->hset('jobs', $trackingId, json_encode([
                'status' => 'completed',
                'processed_at' => time(),
                'line_count' => $lineCount,
                'processed_path' => $newPath
            ]));
            
            $logger->info("Processed job $trackingId");
            
        } catch (Exception $e) {
            $redis->hset('jobs', $trackingId, json_encode([
                'status' => 'failed',
                'error' => $e->getMessage()
            ]));
            
            rename($job['file_path'], FAILED_DIR.'/'.$trackingId.'.csv');
            $logger->error("Failed job $trackingId: ".$e->getMessage());
        }
        
    } catch (Exception $e) {
        $logger->error("Worker error: ".$e->getMessage());
        sleep(5);
    }
}