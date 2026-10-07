<?php
require "includes/auth.php";
require "../database/database.php";
require_once "includes/account.php";

$userCode = $_SESSION["consumer"]["user_code"];
$account  = get_consumer_account($pdo, $userCode);

$stmt = $pdo->prepare("
    SELECT amount, cubic_used, payment_method, payment_date
    FROM payments
    WHERE user_code = :user_code
    ORDER BY payment_date DESC
");
$stmt->execute([":user_code" => $userCode]);
$payments = $stmt->fetchAll();

$pageTitle    = "History";
$pageHeading  = "Payment History";
$pageSubtitle = "Every payment on your account";

include "includes/header.php";
?>

<div class="content-grid">

    <div class="content-col">

        <section class="card" aria-labelledby="paymentsTitle">
            <h2 class="section-title" id="paymentsTitle">Payments</h2>

            <?php if (!$payments): ?>
                <p class="empty-text">No payments yet.</p>
            <?php endif; ?>

            <?php foreach ($payments as $p): ?>
                <div class="list-row">
                    <div>
                        <?= date("F d, Y", strtotime($p["payment_date"])) ?>
                        <small><?= htmlspecialchars($p["payment_method"]) ?> · <?= (int) $p["cubic_used"] ?> m³</small>
                    </div>
                    <span class="amount">₱<?= number_format($p["amount"], 2) ?></span>
                </div>
            <?php endforeach; ?>
        </section>

    </div>

    <div class="content-col">

        <section class="card" aria-labelledby="totalsTitle">
            <h2 class="section-title" id="totalsTitle">Totals</h2>

            <div class="list-row">
                <span class="label">Payments made</span>
                <strong class="amount"><?= count($payments) ?></strong>
            </div>
            <div class="list-row">
                <span class="label">Total paid</span>
                <strong class="amount">₱<?= number_format($account["total_paid"], 2) ?></strong>
            </div>
            <div class="list-row">
                <span class="label">Unpaid balance</span>
                <strong class="amount">₱<?= number_format($account["balance"], 2) ?></strong>
            </div>

            <a href="bills.php" class="link-more">View bills →</a>
        </section>

    </div>

</div>

<?php include "includes/footer.php"; ?>
