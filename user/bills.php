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
            <div class="summary-tile highlight has-watermark">
                <span class="card-watermark" aria-hidden="true"><?= $dropIcon ?></span>
                <span class="tile-icon"><?= nav_icon("wallet") ?></span>
                <span class="stat-label">Unpaid balance</span>
                <span class="stat-value">₱<?= number_format($account["balance"], 2) ?></span>
            </div>
            <div class="summary-tile">
                <span class="tile-icon green"><?= nav_icon("check") ?></span>
                <span class="stat-label">Total paid</span>
                <span class="stat-value">₱<?= number_format($account["total_paid"], 2) ?></span>
            </div>
            <div class="summary-tile">
                <span class="tile-icon amber"><?= nav_icon("bill") ?></span>
                <span class="stat-label">Unpaid bills</span>
                <span class="stat-value"><?= count($account["unpaid_bills"]) ?></span>
            </div>
        </div>

        <!-- BILL LIST -->
        <section class="card" aria-labelledby="billsTitle">
            <?= section_title("All bills", "list", "blue", "billsTitle") ?>

            <?php if (!$account["bills"]): ?>
                <?= empty_state("bill", "No bills yet", "Your first bill will appear after your meter is read.") ?>
            <?php endif; ?>

            <?php foreach ($account["bills"] as $b): ?>
                <?php [$statusIcon, $statusTone] = bill_status_icon($b["status"]); ?>
                <div class="list-row">
                    <div class="row-main">
                        <?= row_icon($statusIcon, $statusTone) ?>
                        <div>
                            <?= $b["period"] ?>
                            <small>
                                <?= format_number($b["usage"]) ?> m³ ·
                                <?= $b["unpaid"] > 0 ? "Due " . $b["due_on"]->format("M d, Y") : "Read " . $b["read_on"]->format("M d, Y") ?>
                                <?php if ($b["paid"] > 0 && $b["unpaid"] > 0): ?>
                                    · ₱<?= number_format($b["paid"], 2) ?> paid
                                <?php endif; ?>
                            </small>
                        </div>
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
            <?= $account["balance"] > 0
                ? section_title("Unpaid balance", "alert", "amber", "statusTitle")
                : section_title("All paid up", "check", "green", "statusTitle") ?>

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
            <?= section_title("How billing works", "info", "teal", "howTitle") ?>

            <ol class="steps">
                <li>
                    <span class="step-num">1</span>
                    <span><strong>Your meter is read</strong> and a new bill is made for that month.</span>
                </li>
                <li>
                    <span class="step-num">2</span>
                    <span><strong>Pay within <?= (int) get_pricing()["due_days"] ?> days</strong> of the reading at the San Vicente water office.</span>
                </li>
                <li>
                    <span class="step-num">3</span>
                    <span><strong>Payments go to your oldest bill first</strong>, then to newer ones.</span>
                </li>
            </ol>
        </section>

    </div>

</div>

<?php include "includes/footer.php"; ?>
