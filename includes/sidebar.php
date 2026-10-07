<?php

$currentPage = basename($_SERVER["PHP_SELF"]);

// Sub-pages that should highlight their parent menu item
$activeAliases = [
    "new-request.php" => "maintenance.php",
];

$activePage = $activeAliases[$currentPage] ?? $currentPage;

$menuItems = [
    "dashboard.php"       => "Dashboard",
    "consumers.php"       => "Consumers",
    "pricing.php"         => "Pricing",
    "payments.php"        => "Payments",
    "payment-records.php" => "Payment Records",
    "maintenance.php"     => "Maintenance",
    "meter-readings.php"  => "Meter Readings",
];
?>
<div class="sidebar">

    <div class="brand">AquaBill</div>

    <nav class="menu">
        <?php foreach ($menuItems as $file => $label): ?>
            <a href="<?= $file ?>" class="<?= $activePage === $file ? 'active' : '' ?>"><?= $label ?></a>
        <?php endforeach; ?>
    </nav>

    <a href="action/logout.php" class="logout-btn">Logout</a>

</div>
