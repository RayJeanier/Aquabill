<?php

// Include at the top of every consumer page.
// Sends anyone who isn't a logged-in consumer back to the shared login page.

require_once __DIR__ . "/session.php";

if (!isset($_SESSION["consumer"])) {
    $inActionDir = basename(dirname($_SERVER["SCRIPT_FILENAME"])) === "action";
    header("Location: " . ($inActionDir ? "../../" : "../") . "index.html");
    exit;
}
