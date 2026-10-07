<?php
require "includes/auth.php";
require "database/database.php";

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
    SELECT request_type, consumer_name, status
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

    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/dashboard.css">
</head>
<body>
<div class="dashboard">

    <?php include "includes/sidebar.php"; ?>

    <!-- MAIN CONTENT -->
    <div class="main">

        <!-- HEADER -->
        <div class="dashboard-header">
            <h1><?= $greeting ?>, Admin</h1>
            <p>Here's what's happening today, <?= date("F j, Y") ?></p>
        </div>

        <!-- STATS CARDS -->
        <div class="cards">

            <div class="card">
                <h3>Total Consumers</h3>
                <p><?= number_format($totalConsumers) ?></p>
            </div>

            <div class="card">
                <h3>Revenue This Month</h3>
                <p>₱<?= number_format($revenueThisMonth, 2) ?></p>
            </div>

            <div class="card">
                <h3>Payments This Month</h3>
                <p><?= number_format($paymentsThisMonth) ?></p>
            </div>

            <div class="card">
                <h3>Open Requests</h3>
                <p><?= number_format($openRequests) ?></p>
            </div>

        </div>

        <!-- CONTENT GRID -->
        <div class="dashboard-grid">

            <!-- LEFT: RECENT PAYMENTS -->
            <div class="panel">

                <h2>Recent Payments</h2>

                <table>
                    <?php if (!$recentPayments): ?>
                        <tr><td>No payments yet.</td></tr>
                    <?php endif; ?>

                    <?php foreach ($recentPayments as $p): ?>
                        <tr>
                            <td><?= htmlspecialchars($p["consumer_name"]) ?></td>
                            <td><?= htmlspecialchars($p["payment_method"]) ?></td>
                            <td><?= date("M d", strtotime($p["payment_date"])) ?></td>
                            <td style="text-align:right">₱<?= number_format($p["amount"], 2) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </table>

            </div>

            <!-- RIGHT: MAINTENANCE -->
            <div class="panel">

                <h2>Maintenance</h2>

                <?php if (!$pendingRequests): ?>
                    <div class="item">No open requests.</div>
                <?php endif; ?>

                <?php foreach ($pendingRequests as $r): ?>
                    <div class="item">
                        <strong><?= htmlspecialchars($r["request_type"]) ?></strong>
                        · <?= htmlspecialchars($r["consumer_name"]) ?>
                        <br><small><?= htmlspecialchars($r["status"]) ?></small>
                    </div>
                <?php endforeach; ?>

            </div>

        </div>

    </div>

</div>
</body>
</html>
