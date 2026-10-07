<?php

require "../includes/auth.php";
require "../../database/database.php";

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: ../service.php");
    exit;
}

// Keep in sync with service.php
$allowedTypes = ["Leak Report", "Meter Issue", "No Water", "Low Water Pressure", "Water Quality", "Other"];

$requestType = $_POST["request_type"] ?? "";
$description = trim($_POST["description"] ?? "");

if (!in_array($requestType, $allowedTypes, true)) {
    header("Location: ../service.php?error=" . urlencode("Please choose what the problem is."));
    exit;
}

if ($description === "" || strlen($description) > 1000) {
    header("Location: ../service.php?error=" . urlencode("Please describe the problem (up to 1000 characters)."));
    exit;
}

$userCode = $_SESSION["consumer"]["user_code"];

try {

    $stmt = $pdo->prepare("SELECT name, address, meter_no FROM consumers WHERE user_code = :user_code");
    $stmt->execute([":user_code" => $userCode]);
    $consumer = $stmt->fetch();

    if (!$consumer) {
        header("Location: ../service.php?error=" . urlencode("Account not found."));
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
        ":user_code"     => $userCode,
        ":consumer_name" => $consumer["name"],
        ":address"       => $consumer["address"],
        ":meter_no"      => $consumer["meter_no"],
        ":request_type"  => $requestType,
        ":description"   => $description,
    ]);

    header("Location: ../service.php?sent=1");
    exit;

} catch (PDOException $e) {
    header("Location: ../service.php?error=" . urlencode("Could not send your request. Please try again."));
    exit;
}
