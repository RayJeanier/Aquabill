<?php

// Shared layout for consumer pages. Usage:
//
//   $pageTitle    = "Bills";                    // browser tab
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
    "bell"    => '<path d="M6 8a6 6 0 1 1 12 0c0 7 3 9 3 9H3s3-2 3-9"/><path d="M10.3 21a1.94 1.94 0 0 0 3.4 0"/>',
    "logout"  => '<path d="M15 4h3a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2h-3"/><path d="M10 17l-5-5 5-5"/><path d="M5 12h11"/>',
];

function nav_icon(string $name): string
{
    global $navIcons;
    return '<svg viewBox="0 0 24 24" aria-hidden="true">' . ($navIcons[$name] ?? "") . '</svg>';
}

$currentUserPage = basename($_SERVER["PHP_SELF"]);
$consumerName    = $_SESSION["consumer"]["name"] ?? "";
$consumerCode    = $_SESSION["consumer"]["user_code"] ?? "";
$initial         = preg_match('/\S/u', $consumerName, $m) ? strtoupper($m[0]) : "?";

$pageTitle    = $pageTitle ?? "My Account";
$pageHeading  = $pageHeading ?? $pageTitle;
$pageEyebrow  = $pageEyebrow ?? "";
$pageSubtitle = $pageSubtitle ?? "";

// The bell shows a dot while the consumer has an unpaid balance
$account   ??= get_consumer_account($pdo, $consumerCode);
$hasUnpaid   = $account["balance"] > 0;
$bellLabel   = $hasUnpaid ? "You have an unpaid balance" : "No unpaid bills";

$dropIcon = '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 2.5c-.3 0-.6.15-.78.4C9.6 5.1 5.5 10.9 5.5 14.5a6.5 6.5 0 0 0 13 0c0-3.6-4.1-9.4-5.72-11.6A.97.97 0 0 0 12 2.5Z"/></svg>';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="theme-color" content="#2a6f9c">
    <title><?= htmlspecialchars($pageTitle) ?> - AquaBill</title>

    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/user.css">
</head>

<body>

<!-- TOP BAR (computer) -->
<header class="topbar">
    <div class="topbar-inner">

        <a href="home.php" class="brand">
            <span class="brand-tile"><?= $dropIcon ?></span>
            AquaBill
        </a>

        <nav class="topnav">
            <?php foreach ($userNav as $file => $item): ?>
                <a href="<?= $file ?>" class="<?= $currentUserPage === $file ? 'active' : '' ?>"><?= $item["label"] ?></a>
            <?php endforeach; ?>
        </nav>

        <div class="topbar-user">
            <a href="bills.php" class="icon-btn <?= $hasUnpaid ? 'has-dot' : '' ?>" aria-label="<?= $bellLabel ?>" title="<?= $bellLabel ?>"><?= nav_icon("bell") ?></a>
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
    <div>
        <?php if ($pageEyebrow !== ""): ?>
            <p class="eyebrow"><?= htmlspecialchars($pageEyebrow) ?></p>
        <?php endif; ?>
        <p class="app-header-title"><?= htmlspecialchars($pageHeading) ?></p>
        <?php if ($pageSubtitle !== ""): ?>
            <p class="app-header-subtitle"><?= htmlspecialchars($pageSubtitle) ?></p>
        <?php endif; ?>
    </div>

    <div class="app-header-actions">
        <a href="bills.php" class="round-btn <?= $hasUnpaid ? 'has-dot' : '' ?>" aria-label="<?= $bellLabel ?>"><?= nav_icon("bell") ?></a>
        <a href="action/logout.php" class="round-btn" aria-label="Log out"><?= nav_icon("logout") ?></a>
    </div>
</header>

<main class="page">

    <!-- PAGE HEADING (computer) -->
    <div class="page-head">
        <?php if ($pageEyebrow !== ""): ?>
            <p class="eyebrow"><?= htmlspecialchars($pageEyebrow) ?></p>
        <?php endif; ?>
        <h1><?= htmlspecialchars($pageHeading) ?></h1>
        <?php if ($pageSubtitle !== ""): ?>
            <p class="page-subtitle"><?= htmlspecialchars($pageSubtitle) ?></p>
        <?php endif; ?>
    </div>

    <div class="page-body">
