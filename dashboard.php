<?php
require "includes/auth.php";
require "database/database.php";
require_once "includes/icons.php";

date_default_timezone_set("Asia/Manila");

$totalConsumers = $pdo->query("SELECT COUNT(*) FROM consumers")->fetchColumn();

$revenueThisMonth = $pdo->query("
    SELECT COALESCE(SUM(amount), 0)
    FROM payments
    WHERE payment_date >= date_trunc('month', NOW())
")->fetchColumn();

$paymentsThisMonth = $pdo->query("
    SELECT COUNT(*)
    FROM payments
    WHERE payment_date >= date_trunc('month', NOW())
")->fetchColumn();

$openRequests = $pdo->query("
    SELECT COUNT(*)
    FROM maintenance_requests
    WHERE status IN ('Open', 'In Progress')
")->fetchColumn();

$recentPayments = $pdo->query("
    SELECT consumer_name, amount, payment_method, payment_date
    FROM payments
    ORDER BY payment_date DESC
    LIMIT 5
")->fetchAll();

$pendingRequests = $pdo->query("
    SELECT request_type, consumer_name, status, created_at
    FROM maintenance_requests
    WHERE status IN ('Open', 'In Progress')
    ORDER BY created_at DESC
    LIMIT 5
")->fetchAll();

$hour = (int) date("G");
$greeting = $hour < 12 ? "Good morning" : ($hour < 18 ? "Good afternoon" : "Good evening");
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>AquaBill Admin Dashboard</title>

    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/app.css">
    <script src="js/admin.js" defer></script>
</head>
<body>
<div class="dashboard">

    <?php include "includes/sidebar.php"; ?>

    <!-- MAIN CONTENT -->
    <div class="main">

        <!-- HEADER -->
        <div class="header">
            <div>
                <h1><?= $greeting ?>, Admin</h1>
                <p>Here's what's happening today, <?= date("F j, Y") ?></p>
            </div>

            <div class="header-actions">
                <a href="payments.php" class="btn btn-secondary"><?= icon("wallet") ?> Encode Payment</a>
                <a href="meter-readings.php" class="btn"><?= icon("gauge") ?> Meter Readings</a>
            </div>
        </div>

        <!-- STATS CARDS -->
        <div class="stats">

            <div class="stat-card">
                <span class="stat-icon"><?= icon("users") ?></span>
                <div>
                    <h5>Total Consumers</h5>
                    <p><?= number_format($totalConsumers) ?></p>
                </div>
            </div>

            <div class="stat-card">
                <span class="stat-icon green"><?= icon("trend") ?></span>
                <div>
                    <h5>Revenue This Month</h5>
                    <p>₱<?= number_format($revenueThisMonth, 2) ?></p>
                </div>
            </div>

            <div class="stat-card">
                <span class="stat-icon violet"><?= icon("receipt") ?></span>
                <div>
                    <h5>Payments This Month</h5>
                    <p><?= number_format($paymentsThisMonth) ?></p>
                </div>
            </div>

            <div class="stat-card">
                <span class="stat-icon amber"><?= icon("wrench") ?></span>
                <div>
                    <h5>Open Requests</h5>
                    <p><?= number_format($openRequests) ?></p>
                </div>
            </div>

        </div>

        <!-- CONTENT GRID -->
        <div class="grid-2">

            <!-- LEFT: RECENT PAYMENTS -->
            <div class="panel">

                <div class="panel-head">
                    <h2>Recent Payments</h2>
                    <a href="payment-records.php" class="icon-btn" aria-label="View all payment records" data-tooltip="View all"><?= icon("arrow") ?></a>
                </div>

                <?php if (!$recentPayments): ?>
                    <p class="hint">No payments yet.</p>
                <?php endif; ?>

                <?php foreach ($recentPayments as $p): ?>
                    <div class="transaction">
                        <div>
                            <?= htmlspecialchars($p["consumer_name"]) ?>
                            <small><?= htmlspecialchars($p["payment_method"]) ?> · <?= date("M d, Y g:i A", strtotime($p["payment_date"])) ?></small>
                        </div>
                        <div class="price">₱<?= number_format($p["amount"], 2) ?></div>
                    </div>
                <?php endforeach; ?>

            </div>

            <!-- RIGHT: MAINTENANCE -->
            <div class="panel">

                <div class="panel-head">
                    <h2>Maintenance</h2>
                    <a href="maintenance.php" class="icon-btn" aria-label="View all maintenance requests" data-tooltip="View all"><?= icon("arrow") ?></a>
                </div>

                <?php if (!$pendingRequests): ?>
                    <p class="hint">No open requests.</p>
                <?php endif; ?>

                <?php foreach ($pendingRequests as $r): ?>
                    <div class="list-item">
                        <div>
                            <strong><?= htmlspecialchars($r["request_type"]) ?></strong>
                            <small><?= htmlspecialchars($r["consumer_name"]) ?> · <?= date("M d", strtotime($r["created_at"])) ?></small>
                        </div>
                        <span class="badge dot <?= strtolower(str_replace(" ", "-", $r["status"])) ?>"><?= htmlspecialchars($r["status"]) ?></span>
                    </div>
                <?php endforeach; ?>

            </div>

        </div>

    </div>

</div>
</body>
</html>
