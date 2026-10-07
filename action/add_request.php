<?php

require "../includes/auth.php";
require "../database/database.php";

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: ../maintenance.php");
    exit;
}

$user_code    = trim($_POST["user_code"] ?? "");
$request_type = trim($_POST["request_type"] ?? "");
$description  = trim($_POST["description"] ?? "");

if ($user_code === "" || $request_type === "" || $description === "") {
    header("Location: ../new-request.php?error=" . urlencode("All fields are required."));
    exit;
}

try {

    $stmt = $pdo->prepare("SELECT name FROM consumers WHERE user_code = :user_code");
    $stmt->execute([":user_code" => $user_code]);
    $consumer = $stmt->fetch();

    if (!$consumer) {
        header("Location: ../new-request.php?error=" . urlencode("Consumer not found."));
        exit;
    }

    $stmt = $pdo->prepare("
        INSERT INTO maintenance_requests
        (user_code, consumer_name, request_type, description, status, created_at)
        VALUES
        (:user_code, :consumer_name, :request_type, :description, 'Open', NOW())
    ");

    $stmt->execute([
        ":user_code"     => $user_code,
        ":consumer_name" => $consumer["name"],
        ":request_type"  => $request_type,
        ":description"   => $description,
    ]);

    header("Location: ../maintenance.php?created=1");
    exit;

} catch (PDOException $e) {
    die("Database Error: " . $e->getMessage());
}
