<?php

require '../includes/auth.php';
include '../database/database.php';
require '../includes/billing.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $user_code = $_POST['user_code'];
    $previous = (float) $_POST['previous_reading'];
    $current = (float) $_POST['current_reading'];

    try {

        // validation
        if ($current < $previous) {
            throw new Exception("Invalid meter reading: current cannot be less than previous");
        }

        $stmt = $pdo->prepare("SELECT name FROM consumers WHERE user_code = :user_code");
        $stmt->execute([':user_code' => $user_code]);
        $consumer = $stmt->fetch();

        if (!$consumer) {
            throw new Exception("Consumer not found");
        }

        // calculation
        $usage = $current - $previous;
        $amount = compute_bill($usage);

        // INSERT INTO SUPABASE
        $stmt = $pdo->prepare("
            INSERT INTO readings (
                user_code,
                consumer_name,
                previous_reading,
                current_reading,
                total_usage,
                amount,
                reading_date
            )
            VALUES (
                :user_code,
                :consumer_name,
                :previous,
                :current,
                :usage,
                :amount,
                NOW()
            )
        ");

        $stmt->execute([
            ':user_code' => $user_code,
            ':consumer_name' => $consumer['name'],
            ':previous' => $previous,
            ':current' => $current,
            ':usage' => (int) round($usage),
            ':amount' => $amount
        ]);

        // redirect
        header("Location: ../meter-readings.php?success=1");
        exit;

    } catch (Exception $e) {
        die("Error: " . $e->getMessage());
    }
}
