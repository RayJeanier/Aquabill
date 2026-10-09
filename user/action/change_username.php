<?php

// Set, change or remove the consumer's optional login username.
// The user code (SVOB-CONS-...) never changes; the username is just another way to log in.

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

$userCode = $_SESSION["consumer"]["user_code"];
$action   = $_POST["action"] ?? "save";
$password = trim($_POST["current_password"] ?? "");

// Usernames are stored in lowercase; matching is case-insensitive anyway
$username = strtolower(trim($_POST["username"] ?? ""));

if ($password === "") {
    back_with_error("Please enter your current password to confirm.");
}

try {

    $stmt = $pdo->prepare("SELECT password FROM users WHERE user_code = :user_code AND role = 'consumer'");
    $stmt->execute([":user_code" => $userCode]);
    $stored = $stmt->fetchColumn();

    if ($stored === false || $password !== $stored) {
        back_with_error("Your current password is incorrect.");
    }

    // REMOVE
    if ($action === "remove") {
        $stmt = $pdo->prepare("UPDATE users SET username = NULL WHERE user_code = :user_code AND role = 'consumer'");
        $stmt->execute([":user_code" => $userCode]);

        header("Location: ../settings.php?username_removed=1");
        exit;
    }

    // SAVE
    // Letters, numbers, dots and underscores only - no hyphens, so a username can never
    // look like a user code (SVOB-CONS-...) and be confused with someone else's account.
    if (!preg_match('/^[a-z0-9._]{4,20}$/', $username)) {
        back_with_error("Username must be 4-20 letters, numbers, dots or underscores, with no spaces.");
    }

    if (str_starts_with($username, "svob")) {
        back_with_error("Username can't start with \"svob\". Please choose another one.");
    }

    $stmt = $pdo->prepare("
        SELECT 1 FROM users
        WHERE LOWER(username) = :username AND user_code <> :user_code
        LIMIT 1
    ");
    $stmt->execute([":username" => $username, ":user_code" => $userCode]);

    if ($stmt->fetchColumn()) {
        back_with_error("The username \"$username\" is already taken. Please choose another one.");
    }

    $stmt = $pdo->prepare("UPDATE users SET username = :username WHERE user_code = :user_code AND role = 'consumer'");
    $stmt->execute([":username" => $username, ":user_code" => $userCode]);

    header("Location: ../settings.php?username_saved=1");
    exit;

} catch (PDOException $e) {
    // 23505 = unique violation (someone took the name at the same moment)
    if ($e->getCode() === "23505") {
        back_with_error("The username \"$username\" is already taken. Please choose another one.");
    }

    back_with_error("Could not save your username. Please try again.");
}
