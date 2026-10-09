<?php

// Called by the login page (index.html) when it opens.
// If someone is still logged in - e.g. they pressed Back after logging in - they're
// logged out here, so pressing Forward can't show their account again.
// Works for both admins and consumers.

require "../includes/no_cache.php";

header("Content-Type: application/json");

// Only accept POST, so a link or image can't log someone out
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    http_response_code(405);
    echo json_encode(["loggedOut" => false]);
    exit;
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$wasLoggedIn = isset($_SESSION["user"]) || isset($_SESSION["consumer"]);

if ($wasLoggedIn) {
    $_SESSION = [];
    session_regenerate_id(true);
    session_destroy();
}

echo json_encode(["loggedOut" => $wasLoggedIn]);
