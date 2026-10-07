<?php
require "includes/auth.php";
require "database/database.php";
require "includes/billing.php";

$pricing = get_pricing();

/* Consumers with the usage from their latest meter reading (used to prefill the form) */
$consumers = $pdo->query("
    SELECT
        c.user_code,
        c.name,
        r.current_reading - r.previous_reading AS last_usage
    FROM consumers c
    LEFT JOIN LATERAL (
        SELECT previous_reading, current_reading
        FROM readings
        WHERE readings.user_code = c.user_code
        ORDER BY id DESC
        LIMIT 1
    ) r ON true
    WHERE c.status = 'Active'
    ORDER BY c.name
")->fetchAll();

$payments = $pdo->query("
    SELECT *
    FROM payments
    ORDER BY payment_date DESC
    LIMIT 5
")->fetchAll();
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Payments - AquaBill</title>

    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/app.css">
</head>

<body>

<div class="dashboard">

    <?php include "includes/sidebar.php"; ?>

    <div class="main">

        <div class="header">
            <div>
                <h1>Encode Payment</h1>
                <p>Record a consumer's water bill payment.</p>
            </div>
        </div>

        <?php if (isset($_GET["success"])): ?>
            <div class="alert success">Payment recorded successfully.</div>
        <?php elseif (isset($_GET["error"])): ?>
            <div class="alert error"><?= htmlspecialchars($_GET["error"]) ?></div>
        <?php endif; ?>

        <div class="grid-2">

            <!-- PAYMENT FORM -->
            <div class="panel">

                <h2>Payment Details</h2>

                <form class="form" action="action/add_payment.php" method="POST">

                    <label>
                        Consumer
                        <select name="user_code" id="consumer" required>
                            <option value="">Select Consumer</option>

                            <?php foreach ($consumers as $c): ?>
                                <option
                                    value="<?= htmlspecialchars($c["user_code"]) ?>"
                                    data-usage="<?= $c["last_usage"] !== null ? (int) $c["last_usage"] : "" ?>"
                                >
                                    <?= htmlspecialchars($c["name"]) ?> - <?= htmlspecialchars($c["user_code"]) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </label>

                    <label>
                        Cubic Meters Used
                        <input type="number" name="cubic_used" id="cubic" required min="0" step="1" placeholder="0">
                        <span class="hint" id="usageHint">Filled in from the consumer's latest meter reading when available.</span>
                    </label>

                    <label>
                        Payment Method
                        <select name="payment_method" required>
                            <option value="Cash">Cash</option>
                            <option value="GCash">GCash</option>
                        </select>
                    </label>

                    <div class="amount-box">
                        <small>Amount Due</small>
                        <strong id="amount">₱<?= number_format($pricing["minimum_charge"], 2) ?></strong>
                    </div>

                    <button type="submit" class="btn">Encode Payment</button>

                </form>

            </div>

            <!-- RECENT TRANSACTIONS -->
            <div class="panel">

                <h2>Recent Transactions</h2>

                <?php if (!$payments): ?>
                    <p class="hint">No payments recorded yet.</p>
                <?php endif; ?>

                <?php foreach ($payments as $p): ?>
                    <div class="transaction">
                        <div>
                            <?= htmlspecialchars($p["consumer_name"]) ?>
                            <small>
                                <?= htmlspecialchars($p["payment_method"]) ?>
                                · <?= date("M d, Y g:i A", strtotime($p["payment_date"])) ?>
                            </small>
                        </div>
                        <div class="price">₱<?= number_format($p["amount"], 2) ?></div>
                    </div>
                <?php endforeach; ?>

                <a href="payment-records.php" class="panel-link">View all payment records →</a>

            </div>

        </div>

    </div>

</div>

<script>

const pricing = <?= json_encode($pricing) ?>;

const consumer = document.getElementById("consumer");
const cubic = document.getElementById("cubic");
const amount = document.getElementById("amount");
const usageHint = document.getElementById("usageHint");

function computeBill(used) {
    const excess = Math.max(0, used - pricing.minimum_cubic);
    return pricing.minimum_charge + (excess * pricing.excess_rate);
}

function updateAmount() {
    const used = parseInt(cubic.value) || 0;
    amount.textContent = "₱" + computeBill(used).toLocaleString("en-PH", {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2
    });
}

consumer.addEventListener("change", () => {
    const usage = consumer.selectedOptions[0].dataset.usage;

    if (usage !== undefined && usage !== "") {
        cubic.value = usage;
        usageHint.textContent = "Usage from latest meter reading: " + usage + " m³";
    } else {
        cubic.value = "";
        usageHint.textContent = consumer.value ? "No meter reading on file - enter usage manually." : "";
    }

    updateAmount();
});

cubic.addEventListener("input", updateAmount);

</script>

</body>
</html>
