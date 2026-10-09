<?php

require '../includes/auth.php';
include '../database/database.php';
require '../includes/consumer_defaults.php';
require '../includes/qr.php';
require '../includes/passwords.php';
require '../includes/contact.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $name = trim($_POST['name']);
    $address = trim($_POST['address']);
    $meter_no = trim($_POST['meter_no']);
    $password = hash_password(DEFAULT_CONSUMER_PASSWORD);

    // Optional - can be left empty and filled in later by the consumer
    [$contact_number, $contactError] = clean_contact_number($_POST['contact_number'] ?? '');
    [$email, $emailError] = clean_email($_POST['email'] ?? '');

    if ($contactError || $emailError) {
        header("Location: ../consumers.php?error=" . urlencode($contactError ?? $emailError));
        exit;
    }

    try {

        $pdo->beginTransaction();

        // Create user account
        // user_code will be generated automatically by your trigger
        $stmtUser = $pdo->prepare("
            INSERT INTO users
            (role, password)
            VALUES
            (:role, :password)
            RETURNING user_code
        ");

        $stmtUser->execute([
            ':role' => 'consumer',
            ':password' => $password
        ]);

        // Get generated user_code from trigger
        $user = $stmtUser->fetch();

        if (!$user || empty($user['user_code'])) {
            throw new Exception('Failed to generate user code.');
        }

        $user_code = $user['user_code'];

        // Create consumer profile, with its QR code value generated at the same time
        $stmtConsumer = $pdo->prepare("
            INSERT INTO consumers
            (user_code, name, address, meter_no, status, qr_code, contact_number, email)
            VALUES
            (:user_code, :name, :address, :meter_no, :status, :qr_code, :contact_number, :email)
        ");

        $stmtConsumer->execute([
            ':user_code' => $user_code,
            ':name' => $name,
            ':address' => $address,
            ':meter_no' => $meter_no,
            ':status' => 'Active',
            ':qr_code' => consumer_qr_value($user_code),
            ':contact_number' => $contact_number,
            ':email' => $email
        ]);

        $pdo->commit();

        header(
            "Location: ../consumers.php?success=1&user_code=" . urlencode($user_code)
        );
        exit;

    } catch (Exception $e) {

        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }

        die("Error: " . $e->getMessage());
    }
}