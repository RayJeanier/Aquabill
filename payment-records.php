<?php
require "includes/auth.php";
require_once "includes/icons.php";
require "database/database.php";

$search = trim($_GET["search"] ?? "");
$method = $_GET["method"] ?? "";
$from   = $_GET["from"] ?? "";
$to     = $_GET["to"] ?? "";

$where  = [];
$params = [];

if ($search !== "") {
    $where[] = "(consumer_name ILIKE :search_name OR user_code ILIKE :search_code)";
    $params[":search_name"] = "%" . $search . "%";
    $params[":search_code"] = "%" . $search . "%";
}

if (in_array($method, ["Cash", "GCash"], true)) {
    $where[] = "payment_method = :method";
    $params[":method"] = $method;
}

if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $from)) {
    $where[] = "payment_date >= CAST(:from AS date)";
    $params[":from"] = $from;
}

if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $to)) {
    $where[] = "payment_date < CAST(:to AS date) + 1";
    $params[":to"] = $to;
}

$sql = "SELECT * FROM payments"
    . ($where ? " WHERE " . implode(" AND ", $where) : "")
    . " ORDER BY payment_date DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$payments = $stmt->fetchAll();

$totalAmount = array_sum(array_column($payments, "amount"));
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Payment Records - AquaBill</title>

    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/app.css">
    <script src="js/admin.js" defer></script>
</head>

<body>

<div class="dashboard">

    <?php include "includes/sidebar.php"; ?>

    <div class="main">

        <div class="header">
            <div>
                <h1>Payment Records</h1>
                <p>All payments encoded in the system.</p>
            </div>
            <a href="payments.php" class="btn"><?= icon("plus") ?> Encode Payment</a>
        </div>

        <div class="stats">
            <div class="stat-card">
                <span class="stat-icon violet"><?= icon("receipt") ?></span>
                <div>
                    <h5>Transactions</h5>
                    <p><?= count($payments) ?></p>
                </div>
            </div>
            <div class="stat-card">
                <span class="stat-icon green"><?= icon("wallet") ?></span>
                <div>
                    <h5>Total Collected</h5>
                    <p>₱<?= number_format($totalAmount, 2) ?></p>
                </div>
            </div>
        </div>

        <div class="table-container">

            <form class="filters" method="GET">
                <label class="input-icon">
                    <span class="sr-only">Search</span>
                    <?= icon("search") ?>
                    <input type="search" name="search" placeholder="Search name or user code" value="<?= htmlspecialchars($search) ?>">
                </label>

                <select name="method" aria-label="Payment method">
                    <option value="">All Methods</option>
                    <option value="Cash" <?= $method === "Cash" ? "selected" : "" ?>>Cash</option>
                    <option value="GCash" <?= $method === "GCash" ? "selected" : "" ?>>GCash</option>
                </select>

                <input type="date" name="from" value="<?= htmlspecialchars($from) ?>" title="From date">
                <input type="date" name="to" value="<?= htmlspecialchars($to) ?>" title="To date">

                <button type="submit" class="btn"><?= icon("filter") ?> Filter</button>
                <a href="payment-records.php" class="icon-btn outlined" aria-label="Reset filters" data-tooltip="Reset filters"><?= icon("reset") ?></a>
            </form>

            <table>
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Consumer</th>
                        <th>User Code</th>
                        <th class="num">Amount</th>
                        <th>Method</th>
                    </tr>
                </thead>

                <tbody>
                <?php if (!$payments): ?>
                    <tr><td colspan="5" class="empty">No payments found.</td></tr>
                <?php endif; ?>

                <?php foreach ($payments as $p): ?>
                    <tr>
                        <td><?= date("M d, Y g:i A", strtotime($p["payment_date"])) ?></td>
                        <td><?= htmlspecialchars($p["consumer_name"]) ?></td>
                        <td><strong><?= htmlspecialchars($p["user_code"]) ?></strong></td>
                        <td class="num">₱<?= number_format($p["amount"], 2) ?></td>
                        <td><span class="badge <?= strtolower($p["payment_method"]) ?>"><?= htmlspecialchars($p["payment_method"]) ?></span></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>

                <?php if ($payments): ?>
                <tfoot>
                    <tr>
                        <td colspan="3">Total</td>
                        <td class="num">₱<?= number_format($totalAmount, 2) ?></td>
                        <td></td>
                    </tr>
                </tfoot>
                <?php endif; ?>
            </table>

        </div>

    </div>

</div>

</body>
</html>
