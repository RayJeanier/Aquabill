<?php
require "includes/auth.php";
require_once "includes/icons.php";
require "database/database.php";
require_once "user/includes/account.php";

/* Active consumers with their unpaid balance (used to prefill the amount) */
$consumers = $pdo->query("
    SELECT user_code, name
    FROM consumers
    WHERE status = 'Active'
    ORDER BY name
")->fetchAll();

foreach ($consumers as &$c) {
    $c["balance"] = get_consumer_account($pdo, $c["user_code"])["balance"];
}
unset($c);

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
                <h1>Encode Payment</h1>
                <p>Record a consumer's cash payment.</p>
            </div>
        </div>

        <?php if (isset($_GET["success"])): ?>
            <div class="alert success"><?= icon("check-circle") ?> Payment recorded successfully.</div>
        <?php elseif (isset($_GET["error"])): ?>
            <div class="alert error"><?= icon("alert") ?> <?= htmlspecialchars($_GET["error"]) ?></div>
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
                                    data-balance="<?= number_format($c["balance"], 2, ".", "") ?>"
                                >
                                    <?= htmlspecialchars($c["name"]) ?> - <?= htmlspecialchars($c["user_code"]) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </label>

                    <div class="amount-box">
                        <small>Unpaid Balance</small>
                        <strong id="balance">₱0.00</strong>
                    </div>

                    <label>
                        Amount Paid (₱)
                        <input type="number" name="amount" id="amount" required min="0.01" step="0.01" placeholder="0.00">
                        <span class="hint" id="amountHint">Payment method: Cash</span>
                    </label>

                    <button type="submit" class="btn"><?= icon("check") ?> Encode Payment</button>

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

const consumer = document.getElementById("consumer");
const balance = document.getElementById("balance");
const amount = document.getElementById("amount");
const amountHint = document.getElementById("amountHint");

function peso(value) {
    return "₱" + value.toLocaleString("en-PH", { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}

function updateHint() {
    const owed = parseFloat(consumer.selectedOptions[0].dataset.balance || 0);
    const paid = parseFloat(amount.value) || 0;

    if (!consumer.value || paid <= 0) {
        amountHint.textContent = "Payment method: Cash";
    } else if (paid < owed) {
        amountHint.textContent = "Cash · " + peso(owed - paid) + " will remain unpaid";
    } else if (paid > owed) {
        amountHint.textContent = "Cash · " + peso(paid - owed) + " will be kept as advance credit";
    } else {
        amountHint.textContent = "Cash · pays the full balance";
    }
}

// Picking a consumer shows their balance and fills it in as the amount (editable)
consumer.addEventListener("change", () => {
    const owed = parseFloat(consumer.selectedOptions[0].dataset.balance || 0);

    balance.textContent = peso(owed);
    amount.value = owed > 0 ? owed.toFixed(2) : "";
    updateHint();
});

amount.addEventListener("input", updateHint);

</script>

</body>
</html>
