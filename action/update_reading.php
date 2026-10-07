<?php

require '../includes/auth.php';
include '../database/database.php';
require '../includes/billing.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../meter-readings.php");
    exit;
}

$id = $_POST['id'];
$previous = $_POST['previous_reading'];
$current = $_POST['current_reading'];

/* Calculate usage */
$usage = $current - $previous;

if ($usage < 0) {
    die("Current reading cannot be less than previous reading.");
}

try {

    $stmt = $pdo->prepare("
        UPDATE readings
        SET
            previous_reading = :previous,
            current_reading = :current,
            total_usage = :usage,
            amount = :amount
        WHERE id = :id
    ");

    $stmt->execute([
        ':previous' => $previous,
        ':current' => $current,
        ':usage' => (int) round($usage),
        ':amount' => compute_bill($usage),
        ':id' => $id
    ]);

    header("Location: ../meter-readings.php?updated=1");
    exit;

} catch (PDOException $e) {
    die("Database Error: " . $e->getMessage());
}