<?php

// Database connection.
//
// On Render (or any host) the settings come from environment variables:
//   DB_HOST, DB_PORT, DB_NAME, DB_USER, DB_PASS
// On this computer they come from database/config.local.php (not in git).
// No passwords are stored in this file, so it is safe to commit.

$local = is_file(__DIR__ . "/config.local.php") ? require __DIR__ . "/config.local.php" : [];

$host = getenv("DB_HOST") ?: ($local["host"] ?? "");
$port = getenv("DB_PORT") ?: ($local["port"] ?? "5432");
$db   = getenv("DB_NAME") ?: ($local["name"] ?? "postgres");
$user = getenv("DB_USER") ?: ($local["user"] ?? "");
$pass = getenv("DB_PASS") ?: ($local["pass"] ?? "");

try {
    $pdo = new PDO(
        "pgsql:host=$host;port=$port;dbname=$db;sslmode=require",
        $user,
        $pass,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]
    );

} catch (PDOException $e) {
    // Log the details for the host's logs; show visitors a plain message
    error_log("Database connection failed: " . $e->getMessage());
    http_response_code(500);
    exit("Sorry, the service is temporarily unavailable. Please try again in a moment.");
}
