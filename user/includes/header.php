<?php

// Shared layout for consumer pages. Usage:
//
//   $pageTitle    = "Payments";                 // browser tab
//   $pageHeading  = "Payment history";          // big heading on the page
//   $pageSubtitle = "All your past payments";   // optional
//   include "includes/header.php";
//   ... page content ...
//   include "includes/footer.php";
//
// The page must already have required includes/auth.php.

// Menu for the consumer side. Add new pages here and they appear in the
// top bar (computer) and the bottom tab bar (phone) automatically.
$userNav = [
    "home.php" => ["label" => "Home", "icon" => "home"],
];

$navIcons = [
    "home"    => '<path d="M3 10.5 12 3l9 7.5"/><path d="M5 9.5V20h14V9.5"/><path d="M10 20v-6h4v6"/>',
    "bill"    => '<path d="M6 3h12v18l-3-2-3 2-3-2-3 2z"/><path d="M9 8h6M9 12h6"/>',
    "payment" => '<rect x="3" y="6" width="18" height="12" rx="2"/><path d="M3 10h18M7 15h3"/>',
    "wrench"  => '<path d="M14.7 6.3a4 4 0 0 0-5.4 5.4L3 18l3 3 6.3-6.3a4 4 0 0 0 5.4-5.4l-2.5 2.5-2.4-.6-.6-2.4z"/>',
    "user"    => '<circle cx="12" cy="8" r="4"/><path d="M4 21a8 8 0 0 1 16 0"/>',
];

$currentUserPage = basename($_SERVER["PHP_SELF"]);
$consumerName    = $_SESSION["consumer"]["name"] ?? "";
$consumerCode    = $_SESSION["consumer"]["user_code"] ?? "";
$initial         = preg_match('/\S/u', $consumerName, $m) ? strtoupper($m[0]) : "?";
$pageTitle       = $pageTitle ?? "My Account";
$pageHeading     = $pageHeading ?? $pageTitle;
$pageSubtitle    = $pageSubtitle ?? "";

$dropIcon = '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 2.5c-.3 0-.6.15-.78.4C9.6 5.1 5.5 10.9 5.5 14.5a6.5 6.5 0 0 0 13 0c0-3.6-4.1-9.4-5.72-11.6A.97.97 0 0 0 12 2.5Z"/></svg>';
$logoutIcon = '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M15 4h3a2 2 0 0 1 2 2v12a2 2 0 0 1-2 2h-3"/><path d="M10 17l-5-5 5-5"/><path d="M5 12h11"/></svg>';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <meta name="theme-color" content="#c4e8f4">
    <title><?= htmlspecialchars($pageTitle) ?> - AquaBill</title>

    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/user.css">
</head>

<body class="<?= count($userNav) > 1 ? 'has-tabbar' : '' ?>">

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
            <span class="avatar" aria-hidden="true"><?= htmlspecialchars($initial) ?></span>
            <span class="topbar-name">
                <?= htmlspecialchars($consumerName) ?>
                <small><?= htmlspecialchars($consumerCode) ?></small>
            </span>
            <a href="action/logout.php" class="icon-btn" aria-label="Log out" title="Log out"><?= $logoutIcon ?></a>
        </div>

    </div>
</header>

<!-- HERO (phone) -->
<header class="hero">

    <a href="action/logout.php" class="icon-btn hero-logout" aria-label="Log out" title="Log out"><?= $logoutIcon ?></a>

    <div class="logo-wrap">
        <div class="logo-tile"><?= $dropIcon ?></div>
    </div>

    <p class="app-name">AquaBill</p>
    <p class="tagline">Smart water bills, simplified</p>

</header>

<main class="sheet">

    <div class="sheet-handle" aria-hidden="true"></div>

    <div class="page-head">
        <h1><?= htmlspecialchars($pageHeading) ?></h1>
        <?php if ($pageSubtitle !== ""): ?>
            <p class="page-subtitle"><?= htmlspecialchars($pageSubtitle) ?></p>
        <?php endif; ?>
    </div>

    <div class="page-body">
