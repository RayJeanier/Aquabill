<?php

require "../includes/auth.php";
require "../database/database.php";

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: ../payments.php");
    exit;
}

$user_code = trim($_POST["user_code"] ?? "");
$amount    = filter_var($_POST["amount"] ?? "", FILTER_VALIDATE_FLOAT);

function back_with_error(string $message): never
{
    header("Location: ../payments.php?error=" . urlencode($message));
    exit;
}

if ($user_code === "") {
    back_with_error("Please select a consumer.");
}

if ($amount === false || $amount <= 0) {
    back_with_error("Amount paid must be greater than ₱0.00.");
}

$amount = round($amount, 2);

try {

    $stmt = $pdo->prepare("SELECT name FROM consumers WHERE user_code = :user_code");
    $stmt->execute([":user_code" => $user_code]);
    $consumer = $stmt->fetch();

    if (!$consumer) {
        back_with_error("Consumer not found.");
    }

    // All payments are cash at the office. cubic_used is required by the
    // payments table but no longer entered, so it is stored as 0.
    $stmt = $pdo->prepare("
        INSERT INTO payments
        (user_code, consumer_name, cubic_used, amount, payment_method, payment_date)
        VALUES
        (:user_code, :consumer_name, 0, :amount, 'Cash', NOW())
    ");

    $stmt->execute([
        ":user_code"     => $user_code,
        ":consumer_name" => $consumer["name"],
        ":amount"        => $amount,
    ]);

    header("Location: ../payments.php?success=1");
    exit;

} catch (PDOException $e) {
    back_with_error("Database error: " . $e->getMessage());
}
