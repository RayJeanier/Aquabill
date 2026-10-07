<?php

require "../includes/auth.php";
require "../database/database.php";
require "../includes/billing.php";

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: ../payments.php");
    exit;
}

$user_code      = trim($_POST["user_code"] ?? "");
$cubic_used     = filter_var($_POST["cubic_used"] ?? "", FILTER_VALIDATE_INT, ["options" => ["min_range" => 0]]);
$payment_method = $_POST["payment_method"] ?? "";

function back_with_error(string $message): never
{
    header("Location: ../payments.php?error=" . urlencode($message));
    exit;
}

if ($user_code === "") {
    back_with_error("Please select a consumer.");
}

if ($cubic_used === false) {
    back_with_error("Cubic meters used must be a whole number of 0 or more.");
}

if (!in_array($payment_method, ["Cash", "GCash"], true)) {
    back_with_error("Invalid payment method.");
}

try {

    $stmt = $pdo->prepare("SELECT name FROM consumers WHERE user_code = :user_code");
    $stmt->execute([":user_code" => $user_code]);
    $consumer = $stmt->fetch();

    if (!$consumer) {
        back_with_error("Consumer not found.");
    }

    // Amount is always computed on the server, never trusted from the form
    $amount = compute_bill($cubic_used);

    $stmt = $pdo->prepare("
        INSERT INTO payments
        (user_code, consumer_name, cubic_used, amount, payment_method, payment_date)
        VALUES
        (:user_code, :consumer_name, :cubic_used, :amount, :payment_method, NOW())
    ");

    $stmt->execute([
        ":user_code"      => $user_code,
        ":consumer_name"  => $consumer["name"],
        ":cubic_used"     => $cubic_used,
        ":amount"         => $amount,
        ":payment_method" => $payment_method,
    ]);

    header("Location: ../payments.php?success=1");
    exit;

} catch (PDOException $e) {
    back_with_error("Database error: " . $e->getMessage());
}
