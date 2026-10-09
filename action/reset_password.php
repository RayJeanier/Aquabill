<?php

// Admin resets a user's password (consumer or staff) when they've forgotten it.
// The new password is saved as a hash and shown to the admin once.

require "../includes/auth.php";
require "../database/database.php";
require "../includes/passwords.php";

$returnPage = in_array($_POST["return"] ?? "", ["consumers.php", "staff.php"], true) ? $_POST["return"] : "consumers.php";

function back_with_error(string $page, string $message): never
{
    header("Location: ../$page?error=" . urlencode($message));
    exit;
}

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: ../$returnPage");
    exit;
}

$userCode    = trim($_POST["user_code"] ?? "");
$newPassword = trim($_POST["new_password"] ?? "");   // trimmed like the login does

if ($userCode === "") {
    back_with_error($returnPage, "No account selected.");
}

if (strlen($newPassword) < PASSWORD_MIN_LENGTH) {
    back_with_error($returnPage, "New password must be at least " . PASSWORD_MIN_LENGTH . " characters.");
}

try {

    // Consumers show their name; staff accounts only have a code
    $stmt = $pdo->prepare("
        SELECT u.user_code, u.role, c.name
        FROM users u
        LEFT JOIN consumers c ON c.user_code = u.user_code
        WHERE u.user_code = :user_code
    ");
    $stmt->execute([":user_code" => $userCode]);
    $account = $stmt->fetch();

    if (!$account) {
        back_with_error($returnPage, "Account not found.");
    }

    $stmt = $pdo->prepare("UPDATE users SET password = :password WHERE user_code = :user_code");
    $stmt->execute([
        ":password"  => hash_password($newPassword),
        ":user_code" => $userCode,
    ]);

    // Shown once on the next page, then removed from the session
    $_SESSION["password_reset"] = [
        "user_code" => $account["user_code"],
        "name"      => $account["name"] ?: ucfirst((string) $account["role"]),
        "password"  => $newPassword,
    ];

    header("Location: ../$returnPage");
    exit;

} catch (PDOException $e) {
    back_with_error($returnPage, "Could not reset the password. Please try again.");
}
