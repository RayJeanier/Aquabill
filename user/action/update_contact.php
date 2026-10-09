<?php

// Consumer adds or updates their own contact information (Settings page).
// Both fields are optional; leaving one empty removes it.

require "../includes/auth.php";
require "../../database/database.php";
require "../../includes/contact.php";

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: ../settings.php");
    exit;
}

[$contactNumber, $contactError] = clean_contact_number($_POST["contact_number"] ?? "");
[$email, $emailError] = clean_email($_POST["email"] ?? "");

if ($contactError || $emailError) {
    header("Location: ../settings.php?error=" . urlencode($contactError ?? $emailError));
    exit;
}

try {

    $stmt = $pdo->prepare("
        UPDATE consumers
        SET contact_number = :contact_number,
            email = :email
        WHERE user_code = :user_code
    ");

    $stmt->execute([
        ":contact_number" => $contactNumber,
        ":email"          => $email,
        ":user_code"      => $_SESSION["consumer"]["user_code"],
    ]);

    header("Location: ../settings.php?contact_saved=1");
    exit;

} catch (PDOException $e) {
    header("Location: ../settings.php?error=" . urlencode("Could not save your contact information. Please try again."));
    exit;
}
