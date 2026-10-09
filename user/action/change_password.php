<?php

require "../includes/auth.php";
require "../../database/database.php";

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: ../settings.php");
    exit;
}

function back_with_error(string $message): never
{
    header("Location: ../settings.php?error=" . urlencode($message));
    exit;
}

// Trimmed the same way as the login (action/login.php) so the new password works there
$current = trim($_POST["current_password"] ?? "");
$new     = trim($_POST["new_password"] ?? "");
$confirm = trim($_POST["confirm_password"] ?? "");

if ($current === "" || $new === "" || $confirm === "") {
    back_with_error("Please fill in all password fields.");
}

if (strlen($new) < 6) {
    back_with_error("New password must be at least 6 characters.");
}

if ($new !== $confirm) {
    back_with_error("New passwords do not match.");
}

if ($new === $current) {
    back_with_error("New password must be different from your current password.");
}

$userCode = $_SESSION["consumer"]["user_code"];

try {

    $stmt = $pdo->prepare("SELECT password FROM users WHERE user_code = :user_code AND role = 'consumer'");
    $stmt->execute([":user_code" => $userCode]);
    $stored = $stmt->fetchColumn();

    if ($stored === false || $current !== $stored) {
        back_with_error("Your current password is incorrect.");
    }

    $stmt = $pdo->prepare("UPDATE users SET password = :password WHERE user_code = :user_code AND role = 'consumer'");
    $stmt->execute([
        ":password"  => $new,
        ":user_code" => $userCode,
    ]);

    header("Location: ../settings.php?changed=1");
    exit;

} catch (PDOException $e) {
    back_with_error("Could not change your password. Please try again.");
}
