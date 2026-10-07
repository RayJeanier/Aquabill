<?php

// Include at the top of every admin page and action.
// Sends anyone who isn't a logged-in admin back to the login page.

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION["user"]) || $_SESSION["user"]["role"] !== "admin") {
    $inActionDir = basename(dirname($_SERVER["SCRIPT_FILENAME"])) === "action";
    header("Location: " . ($inActionDir ? "../" : "") . "index.html");
    exit;
}
