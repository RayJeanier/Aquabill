<?php

$host = "db.stjcqrzvctzxtxnqwnfe.supabase.co";
$db   = "postgres";
$user = "postgres";
$pass = "sIP4Fw5AUd4zu3rs";
$port = "5432";

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
    echo "Connection failed: " . $e->getMessage();
}
//SVOB-AquaBill-sIP4Fw5AUd4zu3rs