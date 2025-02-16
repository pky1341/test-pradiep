
<!-- Debugging & Optimization
You have the following PHP function, which is running slower than expected. Optimize it for
performance.
```php
function getUserPosts($userId) {
$db = new PDO('mysql:host=localhost;dbname=test', 'root', '');
$stmt = $db->prepare("SELECT * FROM posts WHERE user_id = ?");
$stmt->execute([$userId]);
return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
``` -->

<?php

class Database
{
    private static $instance = null;
    private $connection;

    private function __construct()
    {
        $this->connection = new PDO('mysql:host=localhost;dbname=test', 'root', '');
        $this->connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    }

    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new Database();
        }
        return self::$instance->connection;
    }
}

function getUserPosts(int $userId): array
{
    $db = Database::getInstance();

    try {
        //get specific columns value instead of SELECT *
        $stmt = $db->prepare("SELECT id, title, content, created_at FROM posts WHERE user_id = :user_id");
        $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $stmt->execute();

        // Fetch results
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        error_log("Database query error: " . $e->getMessage());
        return [];
    }
}
