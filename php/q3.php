<!-- You need to process a large CSV file (5GB) and insert data into a database. How would you do this efficiently in PHP? -->

<?php

class CsvImportLogger
{
    private $logFile;
    private $startTime;
    private $lastProgressUpdate;
    private $totalRows;
    private $processedRows = 0;

    public function __construct($logFile, $totalRows = null)
    {
        $this->logFile = $logFile;
        $this->startTime = microtime(true);
        $this->lastProgressUpdate = $this->startTime;
        $this->totalRows = $totalRows;

        file_put_contents($this->logFile, "CSV Import started at " . date('Y-m-d H:i:s') . "\n");
    }

    public function logBatch($rowsProcessed, $insertedCount)
    {
        $this->processedRows += $rowsProcessed;
        $currentTime = microtime(true);
        $timeElapsed = $currentTime - $this->startTime;
        $timeSinceLastUpdate = $currentTime - $this->lastProgressUpdate;

        if ($timeSinceLastUpdate > 5 || $rowsProcessed >= 100000) {
            $rowsPerSecond = $this->processedRows / $timeElapsed;
            $message = sprintf(
                "Processed %d rows (%d inserted) in %.2f seconds (%.2f rows/sec)",
                $this->processedRows,
                $insertedCount,
                $timeElapsed,
                $rowsPerSecond
            );

            if ($this->totalRows) {
                $percentComplete = ($this->processedRows / $this->totalRows) * 100;
                $message .= sprintf(" - %.2f%% complete", $percentComplete);

                $totalTimeEstimate = ($this->totalRows / $rowsPerSecond);
                $timeRemaining = $totalTimeEstimate - $timeElapsed;
                $message .= sprintf(", estimated time remaining: %s", $this->formatTime($timeRemaining));
            }

            file_put_contents($this->logFile, $message . "\n", FILE_APPEND);
            $this->lastProgressUpdate = $currentTime;
        }
    }

    private function formatTime($seconds)
    {
        $hours = floor($seconds / 3600);
        $minutes = floor(($seconds % 3600) / 60);
        $secs = $seconds % 60;

        return sprintf("%02d:%02d:%02d", $hours, $minutes, $secs);
    }

    public function finish($success = true)
    {
        $endTime = microtime(true);
        $totalTime = $endTime - $this->startTime;

        $message = sprintf(
            "Import %s at %s. Processed %d rows in %s (%.2f rows/sec)",
            $success ? 'completed successfully' : 'failed',
            date('Y-m-d H:i:s'),
            $this->processedRows,
            $this->formatTime($totalTime),
            $this->processedRows / $totalTime
        );

        file_put_contents($this->logFile, $message . "\n", FILE_APPEND);
    }
}
?>

<?php

ini_set('memory_limit', '1G');
set_time_limit(0);

function countLines($file)
{
    $lineCount = 0;
    $handle = fopen($file, "r");
    while (!feof($handle)) {
        $line = fgets($handle);
        $lineCount++;
    }
    fclose($handle);
    return $lineCount;
}

function optimizeMysqlForBulkImport($pdo) {
    $pdo->exec('SET autocommit=0');
    $pdo->exec('SET unique_checks=0');
    $pdo->exec('SET foreign_key_checks=0');
    $pdo->exec('SET sql_log_bin=0');
}

function restoreMysqlDefaults($pdo) {
    $pdo->exec('SET autocommit=1');
    $pdo->exec('SET unique_checks=1');
    $pdo->exec('SET foreign_key_checks=1');
    $pdo->exec('SET sql_log_bin=1');
}

function processLargeCSV($filepath, $callback) {
    $handle = fopen($filepath, 'r');
    if (!$handle) {
        throw new Exception("Failed to open file: $filepath");
    }
    
    $header = fgetcsv($handle, 0, ',');
    
    $batchSize = 1000;
    $batch = [];
    $lineCount = 1;
    
    while (($data = fgetcsv($handle, 0, ',')) !== FALSE) {
        $lineCount++;
        
        $record = array_combine($header, $data);
        $batch[] = $record;
        
        if (count($batch) >= $batchSize) {
            $callback($batch, $lineCount);
            $batch = [];
        }
    }
    
    if (!empty($batch)) {
        $callback($batch, $lineCount);
    }
    
    fclose($handle);
    return $lineCount;
}

function batchInsert($pdo, $tableName, $records) {
    if (empty($records)) return 0;
    
    $columns = array_keys($records[0]);
    
    $placeholders = [];
    $values = [];
    
    foreach ($records as $record) {
        $rowPlaceholders = [];
        foreach ($record as $value) {
            $values[] = $value;
            $rowPlaceholders[] = '?';
        }
        $placeholders[] = '(' . implode(',', $rowPlaceholders) . ')';
    }
    
    $sql = sprintf(
        "INSERT INTO %s (%s) VALUES %s",
        $tableName,
        implode(',', $columns),
        implode(',', $placeholders)
    );
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($values);
    
    return $stmt->rowCount();
}

function importCsvToDatabase($csvFile, $tableName, $dbConfig)
{
    try {
        $dsn = "mysql:host={$dbConfig['host']};dbname={$dbConfig['dbname']};charset=utf8mb4";
        $pdo = new PDO($dsn, $dbConfig['user'], $dbConfig['password'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);

        $logFile = $csvFile . '.import.log';
        $totalRows = countLines($csvFile) - 1; // Subtract header row
        $logger = new CsvImportLogger($logFile, $totalRows);

        optimizeMysqlForBulkImport($pdo);

        $totalInserted = 0;

        processLargeCSV($csvFile, function ($batch, $lineCount) use ($pdo, $tableName, &$totalInserted, $logger) {
            try {
                $pdo->beginTransaction();
                $inserted = batchInsert($pdo, $tableName, $batch);
                $totalInserted += $inserted;
                $pdo->commit();

                $logger->logBatch(count($batch), $inserted);
            } catch (Exception $e) {
                $pdo->rollBack();
                $logger->logError("Error at line $lineCount: " . $e->getMessage());
            }
        });

        restoreMysqlDefaults($pdo);

        $logger->finish(true);

        return [
            'success' => true,
            'total_rows' => $totalRows,
            'inserted_rows' => $totalInserted
        ];
    } catch (Exception $e) {
        if (isset($logger)) {
            $logger->finish(false);
        }

        return [
            'success' => false,
            'error' => $e->getMessage()
        ];
    }
}

if (!count(debug_backtrace())) {
    if ($argc < 5) {
        exit(1);
    }

    $csvFile = $argv[1];
    $tableName = $argv[2];
    $dbConfigFile = $argv[3];

    if (!file_exists($csvFile)) {
        echo "Error: CSV file not found: $csvFile\n";
        exit(1);
    }

    if (!file_exists($dbConfigFile)) {
        echo "Error: Database config file not found: $dbConfigFile\n";
        exit(1);
    }

    $dbConfig = require $dbConfigFile;

    $result = importCsvToDatabase($csvFile, $tableName, $dbConfig);

    if ($result['success']) {
        echo "Import completed successfully. Inserted {$result['inserted_rows']} of {$result['total_rows']} rows.\n";
        exit(0);
    } else {
        echo "Import failed: {$result['error']}\n";
        exit(1);
    }
}
