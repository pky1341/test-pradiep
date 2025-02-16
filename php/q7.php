<?php
// Configure PHP to use Redis as the session handler.
ini_set('session.save_handler', 'redis');
ini_set('session.save_path', 'tcp://redis1.example.com:6379,tcp://redis2.example.com:6379'); // Comma-separated list of Redis nodes in your cluster

// Set secure cookie parameters (adjust domain and secure flag as needed)
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_secure', 1); // Only if using HTTPS
ini_set('session.use_strict_mode', 1);
session_name('MYAPPSESSID');

// Start the session
session_start();
?>
