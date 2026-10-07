<?php

require '../includes/auth.php';
include '../database/database.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $user_code = $_POST['user_code'];
    $name = $_POST['name'];
    $address = $_POST['address'];
    $meter_no = $_POST['meter_no'];
    $status = $_POST['status']; // ✅ ADD THIS

    try {

        $stmt = $pdo->prepare("
            UPDATE consumers
            SET name = :name,
                address = :address,
                meter_no = :meter_no,
                status = :status
            WHERE user_code = :user_code
        ");

        $stmt->execute([
            ':name' => $name,
            ':address' => $address,
            ':meter_no' => $meter_no,
            ':status' => $status,   // ✅ ADD THIS
            ':user_code' => $user_code
        ]);

        header("Location: ../consumers.php?updated=1");
        exit;

    } catch (Exception $e) {
        die("Error: " . $e->getMessage());
    }
}