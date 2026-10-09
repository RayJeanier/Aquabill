<?php

session_start();
require "../database/database.php";
require "../includes/passwords.php";

// The login box accepts either the user code (SVOB-CONS-...) or the username a
// consumer chose in Settings. Both are matched ignoring letter case and stray spaces
// that phone keyboards add. Password is trimmed like the Android app does.
$login    = trim($_POST["user_code"] ?? "");
$password = trim($_POST["password"] ?? "");

function login_failed(string $message): never
{
    header("Location: ../index.html?error=" . urlencode($message));
    exit;
}

if ($login === "" || $password === "") {
    login_failed("Please enter your User ID or username and password.");
}

$stmt = $pdo->prepare("
    SELECT * FROM users
    WHERE UPPER(TRIM(user_code)) = :user_code
       OR LOWER(username) = :username
    LIMIT 1
");
$stmt->execute([
    "user_code" => strtoupper($login),
    "username"  => strtolower($login),
]);

$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Same message for unknown user and wrong password, so the login form
// doesn't reveal which user codes exist
if (!$user || !verify_password($password, $user["password"])) {
    login_failed("Invalid User ID / username or password.");
}

// Consumers sign in on the same page and go to their own side
if ($user["role"] === "consumer") {
    $stmt = $pdo->prepare("SELECT name, status FROM consumers WHERE user_code = :user_code");
    $stmt->execute(["user_code" => $user["user_code"]]);
    $consumer = $stmt->fetch();

    if (!$consumer) {
        login_failed("No consumer profile found for this account.");
    }

    if ($consumer["status"] !== "Active") {
        login_failed("This account is inactive. Please contact the San Vicente water office.");
    }

    session_regenerate_id(true);
    $_SESSION["consumer"] = [
        "user_code" => $user["user_code"],
        "name"      => $consumer["name"],
    ];

    header("Location: ../user/home.php");
    exit;
}

if ($user["role"] !== "admin") {
    login_failed("This account does not have access.");
}

session_regenerate_id(true);
unset($user["password"]);   // never keep the password (or its hash) in the session
$_SESSION["user"] = $user;

header("Location: ../dashboard.php");
exit;
