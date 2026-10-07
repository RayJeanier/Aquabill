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

    $stmt = $pdo->prepare("SELECT name FROM consumers WHERE user_code = :user_code");
    $stmt->execute([":user_code" => $userCode]);
    $name = $stmt->fetchColumn();

    if ($name === false) {
        header("Location: ../service.php?error=" . urlencode("Account not found."));
        exit;
    }

    $stmt = $pdo->prepare("
        INSERT INTO maintenance_requests
        (user_code, consumer_name, request_type, description, status, created_at)
        VALUES
        (:user_code, :consumer_name, :request_type, :description, 'Open', NOW())
    ");

    $stmt->execute([
        ":user_code"     => $userCode,
        ":consumer_name" => $name,
        ":request_type"  => $requestType,
        ":description"   => $description,
    ]);

    header("Location: ../service.php?sent=1");
    exit;

} catch (PDOException $e) {
    header("Location: ../service.php?error=" . urlencode("Could not send your request. Please try again."));
    exit;
}
