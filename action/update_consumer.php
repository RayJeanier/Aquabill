<?php

require '../includes/auth.php';
include '../database/database.php';
require '../includes/contact.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $user_code = $_POST['user_code'];
    $name = $_POST['name'];
    $address = $_POST['address'];
    $meter_no = $_POST['meter_no'];
    $status = $_POST['status'];

    // Optional - empty clears it
    [$contact_number, $contactError] = clean_contact_number($_POST['contact_number'] ?? '');
    [$email, $emailError] = clean_email($_POST['email'] ?? '');

    if ($contactError || $emailError) {
        header("Location: ../consumers.php?error=" . urlencode($contactError ?? $emailError));
        exit;
    }

    try {

        $stmt = $pdo->prepare("
            UPDATE consumers
            SET name = :name,
                address = :address,
                meter_no = :meter_no,
                status = :status,
                contact_number = :contact_number,
                email = :email
            WHERE user_code = :user_code
        ");

        $stmt->execute([
            ':name' => $name,
            ':address' => $address,
            ':meter_no' => $meter_no,
            ':status' => $status,
            ':contact_number' => $contact_number,
            ':email' => $email,
            ':user_code' => $user_code
        ]);

        header("Location: ../consumers.php?updated=1");
        exit;

    } catch (Exception $e) {
        die("Error: " . $e->getMessage());
    }
}
