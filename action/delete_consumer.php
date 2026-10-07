<?php

require '../includes/auth.php';
include '../database/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['user_code'])) {
    die("Missing user_code");
}

$user_code = $_POST['user_code'];

try {

    $pdo->beginTransaction();

    // Delete from consumers
    $stmt = $pdo->prepare("
        DELETE FROM consumers
        WHERE user_code = :user_code
    ");

    $stmt->execute([
        ':user_code' => $user_code
    ]);

    // Delete from users
    $stmt = $pdo->prepare("
        DELETE FROM users
        WHERE user_code = :user_code
    ");

    $stmt->execute([
        ':user_code' => $user_code
    ]);

    $pdo->commit();

    header("Location: ../consumers.php?deleted=1");
    exit;

} catch (Exception $e) {

    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    die("Error: " . $e->getMessage());
}