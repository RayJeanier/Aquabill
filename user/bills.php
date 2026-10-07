<?php
require "includes/auth.php";
require "../database/database.php";
require_once "includes/account.php";

$account = get_consumer_account($pdo, $_SESSION["consumer"]["user_code"]);

$pageTitle    = "Bills";
$pageHeading  = "My Bills";
$pageSubtitle = "Paid and unpaid water bills";

include "includes/header.php";
?>

<div class="content-grid">

    <div class="content-col">

        <!-- BALANCE SUMMARY -->
        <div class="summary">
            <div class="summary-tile highlight">
                <span class="stat-label">Unpaid balance</span>
                <span class="stat-value">₱<?= number_format($account["balance"], 2) ?></span>
            </div>
            <div class="summary-tile">
                <span class="stat-label">Total paid</span>
                <span class="stat-value">₱<?= number_format($account["total_paid"], 2) ?></span>
            </div>
            <div class="summary-tile">
                <span class="stat-label">Unpaid bills</span>
                <span class="stat-value"><?= count($account["unpaid_bills"]) ?></span>
            </div>
        </div>

        <!-- BILL LIST -->
        <section class="card" aria-labelledby="billsTitle">
            <h2 class="section-title" id="billsTitle">All bills</h2>

            <?php if (!$account["bills"]): ?>
                <p class="empty-text">No bills yet. Your first bill will appear after your meter is read.</p>
            <?php endif; ?>

            <?php foreach ($account["bills"] as $b): ?>
                <div class="list-row">
                    <div>
                        <?= $b["period"] ?>
                        <small>
                            <?= format_number($b["usage"]) ?> m³ ·
                            <?= $b["unpaid"] > 0 ? "Due " . $b["due_on"]->format("M d, Y") : "Read " . $b["read_on"]->format("M d, Y") ?>
                        </small>
                    </div>
                    <div class="row-end">
                        <span class="amount">₱<?= number_format($b["amount"], 2) ?></span>
                        <span class="pill <?= status_class($b["status"]) ?>">
                            <?= $b["status"] === "Partially paid" ? "₱" . number_format($b["unpaid"], 2) . " left" : $b["status"] ?>
                        </span>
                    </div>
                </div>
            <?php endforeach; ?>
        </section>

    </div>

    <div class="content-col">

        <!-- BALANCE STATUS -->
        <section class="card" aria-labelledby="statusTitle">
            <h2 class="section-title" id="statusTitle">
                <?= $account["balance"] > 0 ? "Unpaid balance" : "All paid up" ?>
            </h2>

            <?php if ($account["balance"] > 0): ?>
                <p class="empty-text">
                    You have <?= count($account["unpaid_bills"]) ?> unpaid bill<?= count($account["unpaid_bills"]) === 1 ? "" : "s" ?>
                    totaling <strong>₱<?= number_format($account["balance"], 2) ?></strong>.
                    <?php if ($account["next_due"]): ?>
                        The oldest is due <?= $account["next_due"]->format("F d, Y") ?>.
                    <?php endif; ?>
                    Please pay at the San Vicente water office.
                </p>
            <?php else: ?>
                <p class="empty-text">You have no unpaid bills. Thank you for paying on time!</p>
            <?php endif; ?>

            <?php if ($account["credit"] > 0): ?>
                <p class="note">You have ₱<?= number_format($account["credit"], 2) ?> advance credit that will go toward your next bill.</p>
            <?php endif; ?>
        </section>

        <section class="card" aria-labelledby="howTitle">
            <h2 class="section-title" id="howTitle">How billing works</h2>
            <p class="empty-text">
                A new bill is made each time your meter is read. Bills are due
                <?= (int) get_pricing()["due_days"] ?> days after the reading.
                Payments are applied to your oldest unpaid bill first.
            </p>
        </section>

    </div>

</div>

<?php include "includes/footer.php"; ?>
