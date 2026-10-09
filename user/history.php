<?php
require "includes/auth.php";
require "../database/database.php";
require_once "includes/account.php";

$userCode = $_SESSION["consumer"]["user_code"];
$account  = get_consumer_account($pdo, $userCode);

$stmt = $pdo->prepare("
    SELECT amount, payment_method, payment_date
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
            <?= section_title("Payments", "wallet", "green", "paymentsTitle") ?>

            <?php if (!$payments): ?>
                <?= empty_state("wallet", "No payments yet", "Payments made at the water office will appear here.") ?>
            <?php endif; ?>

            <?php $lastMonth = null; ?>
            <?php foreach ($payments as $p): ?>
                <?php $month = date("F Y", strtotime($p["payment_date"])); ?>

                <?php if ($month !== $lastMonth): ?>
                    <p class="group-label"><?= $month ?></p>
                    <?php $lastMonth = $month; ?>
                <?php endif; ?>

                <div class="list-row">
                    <div class="row-main">
                        <?= row_icon("check", "green") ?>
                        <div>
                            <?= date("F d, Y", strtotime($p["payment_date"])) ?>
                            <small><?= htmlspecialchars($p["payment_method"]) ?> payment · <?= date("g:i A", strtotime($p["payment_date"])) ?></small>
                        </div>
                    </div>
                    <span class="amount">₱<?= number_format($p["amount"], 2) ?></span>
                </div>
            <?php endforeach; ?>
        </section>

    </div>

    <div class="content-col">

        <section class="card" aria-labelledby="totalsTitle">
            <?= section_title("Totals", "sigma", "violet", "totalsTitle") ?>

            <div class="list-row">
                <span class="row-main"><?= row_icon("list", "blue") ?><span class="label">Payments made</span></span>
                <strong class="amount"><?= count($payments) ?></strong>
            </div>
            <div class="list-row">
                <span class="row-main"><?= row_icon("check", "green") ?><span class="label">Total paid</span></span>
                <strong class="amount">₱<?= number_format($account["total_paid"], 2) ?></strong>
            </div>
            <div class="list-row">
                <span class="row-main"><?= row_icon("alert", $account["balance"] > 0 ? "amber" : "green") ?><span class="label">Unpaid balance</span></span>
                <strong class="amount">₱<?= number_format($account["balance"], 2) ?></strong>
            </div>

            <a href="bills.php" class="link-more">View bills →</a>
        </section>

    </div>

</div>

<?php include "includes/footer.php"; ?>
