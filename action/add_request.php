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

    $stmt = $pdo->prepare("SELECT name, address, meter_no FROM consumers WHERE user_code = :user_code");
    $stmt->execute([":user_code" => $user_code]);
    $consumer = $stmt->fetch();

    if (!$consumer) {
        header("Location: ../new-request.php?error=" . urlencode("Consumer not found."));
        exit;
    }

    // Address and meter number are saved with the request so the plumber knows where to go
    $stmt = $pdo->prepare("
        INSERT INTO maintenance_requests
        (user_code, consumer_name, address, meter_no, request_type, description, status, created_at)
        VALUES
        (:user_code, :consumer_name, :address, :meter_no, :request_type, :description, 'Open', NOW())
    ");

    $stmt->execute([
        ":user_code"     => $user_code,
        ":consumer_name" => $consumer["name"],
        ":address"       => $consumer["address"],
        ":meter_no"      => $consumer["meter_no"],
        ":request_type"  => $request_type,
        ":description"   => $description,
    ]);

    header("Location: ../maintenance.php?created=1");
    exit;

} catch (PDOException $e) {
    die("Database Error: " . $e->getMessage());
}
