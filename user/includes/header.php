<?php

// Shared layout for consumer pages. Usage:
//
//   $pageTitle    = "Bills";                    // browser tab
//   (settings.php is reached from the gear icon in the header, not the tab bar)
//   $pageEyebrow  = "Good morning,";            // optional small line above the heading
//   $pageHeading  = "My Bills";                 // big heading
//   $pageSubtitle = "Paid and unpaid bills";    // optional
//   include "includes/header.php";
//   ... page content ...
//   include "includes/footer.php";
//
// The page must already have required includes/auth.php and the database.

require_once __DIR__ . "/account.php";

// Menu for the consumer side. Add new pages here and they appear in the
// top bar (computer) and the bottom tab bar (phone) automatically.
$userNav = [
    "home.php"    => ["label" => "Home",    "icon" => "home"],
    "bills.php"   => ["label" => "Bills",   "icon" => "bill"],
    "service.php" => ["label" => "Service", "icon" => "wrench"],
    "history.php" => ["label" => "History", "icon" => "history"],
];

$navIcons = [
    "home"    => '<path d="M3 10.5 12 3l9 7.5"/><path d="M5 9.5V20h14V9.5"/><path d="M10 20v-6h4v6"/>',
    "bill"    => '<path d="M14 3H7a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V8z"/><path d="M14 3v5h5"/><path d="M9 13h6M9 17h6"/>',
    "wrench"  => '<path d="M14.7 6.3a4 4 0 0 0-5.4 5.4L3 18l3 3 6.3-6.3a4 4 0 0 0 5.4-5.4l-2.5 2.5-2.4-.6-.6-2.4z"/>',
    "history" => '<rect x="4" y="3" width="16" height="18" rx="2"/><path d="M12 7v10M14.5 9h-3.25a1.75 1.75 0 0 0 0 3.5h1.5a1.75 1.75 0 0 1 0 3.5H9.5"/>',
    "settings"=> '<path d="M12.22 2h-.44a2 2 0 0 0-2 2v.18a2 2 0 0 1-1 1.73l-.43.25a2 2 0 0 1-2 0l-.15-.08a2 2 0 0 0-2.73.73l-.22.38a2 2 0 0 0 .73 2.73l.15.1a2 2 0 0 1 1 1.72v.51a2 2 0 0 1-1 1.74l-.15.09a2 2 0 0 0-.73 2.73l.22.38a2 2 0 0 0 2.73.73l.15-.08a2 2 0 0 1 2 0l.43.25a2 2 0 0 1 1 1.73V20a2 2 0 0 0 2 2h.44a2 2 0 0 0 2-2v-.18a2 2 0 0 1 1-1.73l.43-.25a2 2 0 0 1 2 0l.15.08a2 2 0 0 0 2.73-.73l.22-.39a2 2 0 0 0-.73-2.73l-.15-.08a2 2 0 0 1-1-1.74v-.5a2 2 0 0 1 1-1.74l.15-.09a2 2 0 0 0 .73-2.73l-.22-.38a2 2 0 0 0-2.73-.73l-.15.08a2 2 0 0 1-2 0l-.43-.25a2 2 0 0 1-1-1.73V4a2 2 0 0 0-2-2z"/><circle cx="12" cy="12" r="3"/>',
    "lock"    => '<rect x="3" y="11" width="18" height="11" rx="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/>',
    "user"    => '<circle cx="12" cy="8" r="4"/><path d="M4 21a8 8 0 0 1 16 0"/>',
    "eye"     => '<path d="M2 12s3.6-7 10-7 10 7 10 7-3.6 7-10 7S2 12 2 12Z"/><circle cx="12" cy="12" r="3"/>',
    "logout"  => '<path d="M15 4h3a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2h-3"/><path d="M10 17l-5-5 5-5"/><path d="M5 12h11"/>',

    // used for section titles, list rows and empty states
    "wallet"  => '<path d="M19 7V4a1 1 0 0 0-1-1H5a2 2 0 0 0 0 4h15a1 1 0 0 1 1 1v4h-3a2 2 0 0 0 0 4h3a1 1 0 0 0 1-1v-2a1 1 0 0 0-1-1"/><path d="M3 5v14a2 2 0 0 0 2 2h15a1 1 0 0 0 1-1v-4"/>',
    "check"   => '<path d="M20 6 9 17l-5-5"/>',
    "chart"   => '<path d="M3 3v18h18"/><path d="M7 16v-5M12 16V8M17 16v-3"/>',
    "pin"     => '<path d="M20 10c0 4.99-5.54 10.19-7.4 11.8a1 1 0 0 1-1.2 0C9.54 20.19 4 14.99 4 10a8 8 0 0 1 16 0"/><circle cx="12" cy="10" r="3"/>',
    "gauge"   => '<path d="m12 14 4-4"/><path d="M3.34 19a10 10 0 1 1 17.32 0"/>',
    "id"      => '<rect x="3" y="4" width="18" height="16" rx="2"/><circle cx="9" cy="11" r="2"/><path d="M15 9h3M15 13h3M6 16c.6-1.2 1.7-2 3-2s2.4.8 3 2"/>',
    "at"      => '<circle cx="12" cy="12" r="4"/><path d="M16 8v5a3 3 0 0 0 6 0v-1a10 10 0 1 0-4 8"/>',
    "clock"   => '<circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/>',
    "alert"   => '<circle cx="12" cy="12" r="10"/><path d="M12 8v4M12 16h.01"/>',
    "info"    => '<circle cx="12" cy="12" r="10"/><path d="M12 16v-4M12 8h.01"/>',
    "list"    => '<path d="M8 6h13M8 12h13M8 18h13M3 6h.01M3 12h.01M3 18h.01"/>',
    "sigma"   => '<path d="M18 7V4H6l6 8-6 8h12v-3"/>',
];

function nav_icon(string $name): string
{
    global $navIcons;
    return '<svg viewBox="0 0 24 24" aria-hidden="true">' . ($navIcons[$name] ?? "") . '</svg>';
}

// Colored icon circle at the start of a list row.
// Tones: blue, green, amber, red, teal, violet
function row_icon(string $name, string $tone = "blue"): string
{
    return '<span class="row-icon ' . $tone . '">' . nav_icon($name) . '</span>';
}

// Section heading with a small colored icon tile
function section_title(string $text, string $icon, string $tone = "blue", string $id = ""): string
{
    return '<h2 class="section-title with-icon"' . ($id !== "" ? ' id="' . $id . '"' : '') . '>'
        . '<span class="title-icon ' . $tone . '">' . nav_icon($icon) . '</span>'
        . htmlspecialchars($text)
        . '</h2>';
}

// Friendly empty state: icon, short title, one line of help
function empty_state(string $icon, string $title, string $text = ""): string
{
    return '<div class="empty-state">'
        . '<span class="empty-icon">' . nav_icon($icon) . '</span>'
        . '<strong>' . htmlspecialchars($title) . '</strong>'
        . ($text !== "" ? '<span>' . htmlspecialchars($text) . '</span>' : '')
        . '</div>';
}

// Soft static waves used to decorate the blue header and the page background
$wavesSvg = '<svg viewBox="0 0 1440 120" preserveAspectRatio="none" aria-hidden="true">'
    . '<path class="w1" d="M0 60 Q 180 20 360 60 T 720 60 T 1080 60 T 1440 60 V120 H0 Z"/>'
    . '<path class="w2" d="M0 78 Q 240 40 480 78 T 960 78 T 1440 78 V120 H0 Z"/>'
    . '</svg>';

$currentUserPage = basename($_SERVER["PHP_SELF"]);
$consumerName    = $_SESSION["consumer"]["name"] ?? "";
$consumerCode    = $_SESSION["consumer"]["user_code"] ?? "";
$initial         = preg_match('/\S/u', $consumerName, $m) ? strtoupper($m[0]) : "?";

$pageTitle    = $pageTitle ?? "My Account";
$pageHeading  = $pageHeading ?? $pageTitle;
$pageEyebrow  = $pageEyebrow ?? "";
$pageSubtitle = $pageSubtitle ?? "";

$dropIcon = '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 2.5c-.3 0-.6.15-.78.4C9.6 5.1 5.5 10.9 5.5 14.5a6.5 6.5 0 0 0 13 0c0-3.6-4.1-9.4-5.72-11.6A.97.97 0 0 0 12 2.5Z"/></svg>';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <link rel="icon" href="../img/favicon.svg" type="image/svg+xml">
    <link rel="apple-touch-icon" href="../img/logo.png">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="theme-color" content="#2a6f9c">
    <title><?= htmlspecialchars($pageTitle) ?> - AquaBill</title>

    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/user.css">
</head>

<body>

<!-- background waves (computer, decoration) -->
<div class="page-waves" aria-hidden="true"><?= $wavesSvg ?></div>

<!-- TOP BAR (computer) -->
<header class="topbar">
    <div class="topbar-inner">

        <a href="home.php" class="brand">
            <img class="brand-logo" src="../img/favicon.svg" alt="">
            AquaBill
        </a>

        <nav class="topnav">
            <?php foreach ($userNav as $file => $item): ?>
                <a href="<?= $file ?>" class="<?= $currentUserPage === $file ? 'active' : '' ?>"><?= $item["label"] ?></a>
            <?php endforeach; ?>
        </nav>

        <div class="topbar-user">
            <a href="settings.php" class="icon-btn <?= $currentUserPage === 'settings.php' ? 'active' : '' ?>" aria-label="Settings" title="Settings"><?= nav_icon("settings") ?></a>
            <span class="avatar" aria-hidden="true"><?= htmlspecialchars($initial) ?></span>
            <span class="topbar-name">
                <?= htmlspecialchars($consumerName) ?>
                <small><?= htmlspecialchars($consumerCode) ?></small>
            </span>
            <a href="action/logout.php" class="icon-btn" aria-label="Log out" title="Log out"><?= nav_icon("logout") ?></a>
        </div>

    </div>
</header>

<!-- HEADER (phone) -->
<header class="app-header">
    <!-- decoration: big faint water drop + waves along the bottom edge -->
    <span class="header-drop" aria-hidden="true"><?= $dropIcon ?></span>
    <span class="header-waves" aria-hidden="true"><?= $wavesSvg ?></span>

    <div class="app-header-text">
        <?php if ($pageEyebrow !== ""): ?>
            <p class="eyebrow"><?= htmlspecialchars($pageEyebrow) ?></p>
        <?php endif; ?>
        <p class="app-header-title"><?= htmlspecialchars($pageHeading) ?></p>
        <?php if ($pageSubtitle !== ""): ?>
            <p class="app-header-subtitle"><?= htmlspecialchars($pageSubtitle) ?></p>
        <?php endif; ?>
    </div>

    <div class="app-header-actions">
        <a href="settings.php" class="round-btn" aria-label="Settings"><?= nav_icon("settings") ?></a>
        <a href="action/logout.php" class="round-btn" aria-label="Log out"><?= nav_icon("logout") ?></a>
    </div>
</header>

<main class="page">

    <!-- PAGE HEADING (computer) -->
    <div class="page-head">
        <span class="head-drop" aria-hidden="true"><?= $dropIcon ?></span>
        <?php if ($pageEyebrow !== ""): ?>
            <p class="eyebrow"><?= htmlspecialchars($pageEyebrow) ?></p>
        <?php endif; ?>
        <h1><?= htmlspecialchars($pageHeading) ?></h1>
        <?php if ($pageSubtitle !== ""): ?>
            <p class="page-subtitle"><?= htmlspecialchars($pageSubtitle) ?></p>
        <?php endif; ?>
    </div>

    <div class="page-body">
