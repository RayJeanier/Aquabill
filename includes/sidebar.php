<?php

require_once __DIR__ . "/icons.php";

$currentPage = basename($_SERVER["PHP_SELF"]);

// Sub-pages that should highlight their parent menu item
$activeAliases = [
    "new-request.php" => "maintenance.php",
];

$activePage = $activeAliases[$currentPage] ?? $currentPage;

$menuItems = [
    "dashboard.php"       => ["Dashboard",       "dashboard"],
    "consumers.php"       => ["Consumers",       "users"],
    "meter-readings.php"  => ["Meter Readings",  "gauge"],
    "payments.php"        => ["Payments",        "wallet"],
    "payment-records.php" => ["Payment Records", "receipt"],
    "maintenance.php"     => ["Maintenance",     "wrench"],
    "pricing.php"         => ["Pricing",         "tag"],
    "staff.php"           => ["Staff",           "shield"],
];

$adminCode = $_SESSION["user"]["user_code"] ?? "Admin";
?>
<aside class="sidebar">

    <a href="dashboard.php" class="brand">
        <span class="brand-tile"><?= icon("droplet") ?></span>
        AquaBill
    </a>

    <nav class="menu" aria-label="Admin">
        <?php foreach ($menuItems as $file => [$label, $iconName]): ?>
            <a href="<?= $file ?>"
               class="<?= $activePage === $file ? 'active' : '' ?>"
               <?= $activePage === $file ? 'aria-current="page"' : '' ?>>
                <?= icon($iconName) ?>
                <span><?= $label ?></span>
            </a>
        <?php endforeach; ?>
    </nav>

    <!-- ACCOUNT MENU -->
    <details class="dropdown account-menu">
        <summary class="account-btn">
            <span class="avatar" aria-hidden="true">A</span>
            <span class="account-text">
                Admin
                <small><?= htmlspecialchars($adminCode) ?></small>
            </span>
            <?= icon("chevron", "icon chevron") ?>
        </summary>

        <div class="dropdown-menu up">
            <a href="staff.php" class="dropdown-item"><?= icon("shield") ?> Staff accounts</a>
            <a href="pricing.php" class="dropdown-item"><?= icon("tag") ?> Water rates</a>
            <div class="dropdown-divider"></div>
            <a href="action/logout.php" class="dropdown-item danger"><?= icon("logout") ?> Log out</a>
        </div>
    </details>

</aside>
