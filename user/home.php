<?php
require "includes/auth.php";
require "../database/database.php";
require_once "includes/account.php";

$userCode = $_SESSION["consumer"]["user_code"];
$account  = get_consumer_account($pdo, $userCode);
$current  = $account["current"];
$previous = $account["previous"];

$stmt = $pdo->prepare("
    SELECT amount, payment_method, payment_date
    FROM payments
    WHERE user_code = :user_code
    ORDER BY payment_date DESC
    LIMIT 3
");
$stmt->execute([":user_code" => $userCode]);
$recentPayments = $stmt->fetchAll();

/* Greeting in Philippine time */
$hour     = (int) (new DateTime("now", new DateTimeZone("Asia/Manila")))->format("G");
$greeting = $hour < 12 ? "Good morning," : ($hour < 18 ? "Good afternoon," : "Good evening,");

/* Usage compared with the previous bill */
$trend = null;

if ($current && $previous && $previous["usage"] > 0) {
    $change = ($current["usage"] - $previous["usage"]) / $previous["usage"] * 100;

    if (abs($change) < 0.5) {
        $trend = ["class" => "neutral", "text" => "Same usage as last bill", "icon" => '<path d="M5 12h14"/>'];
    } elseif ($change < 0) {
        $trend = ["class" => "good", "text" => round(abs($change)) . "% lower than last bill", "icon" => '<path d="m3 7 6 6 4-4 8 8"/><path d="M21 11v6h-6"/>'];
    } else {
        $trend = ["class" => "warning", "text" => round($change) . "% higher than last bill", "icon" => '<path d="m3 17 6-6 4 4 8-8"/><path d="M21 13V7h-6"/>'];
    }
}

/* Consumption chart: last 6 bills, oldest to newest */
$chartBills = array_reverse(array_slice($account["bills"], 0, 6));
$chartMax   = max(array_merge([0], array_column($chartBills, "usage")));

// Round the axis up to a clean number (10, 20, 50, 100, 200, 500 ...)
$axisMax = 10;
while ($axisMax < $chartMax) {
    $axisMax *= in_array(substr((string) $axisMax, 0, 1), ["1", "5"]) ? 2 : 2.5;
}

$amountParts = $current ? explode(".", number_format($current["amount"], 2)) : ["0", "00"];

$pageTitle   = "Home";
$pageEyebrow = $greeting;
$pageHeading = $_SESSION["consumer"]["name"];

include "includes/header.php";
?>

<div class="home-grid">

    <!-- CURRENT BILL -->
    <section class="card area-bill has-watermark" aria-label="Current bill">

        <span class="card-watermark" aria-hidden="true"><?= $dropIcon ?></span>

        <div class="bill-top">
            <div class="bill-id">
                <span class="drop-tile"><?= $dropIcon ?></span>
                <div>
                    <small>Current bill</small>
                    <strong><?= $current ? $current["period"] : "No bill yet" ?></strong>
                </div>
            </div>

            <?php if ($current): ?>
                <span class="pill <?= status_class($current["status"]) ?>"><?= due_label($current) ?></span>
            <?php endif; ?>
        </div>

        <?php if (!$current): ?>
            <p class="bill-empty">Your first bill will appear here after your meter is read.</p>
        <?php else: ?>

            <p class="bill-amount">₱<?= $amountParts[0] ?><span class="cents">.<?= $amountParts[1] ?></span></p>

            <?php if ($trend): ?>
                <p class="trend <?= $trend["class"] ?>">
                    <svg viewBox="0 0 24 24" aria-hidden="true"><?= $trend["icon"] ?></svg>
                    <?= $trend["text"] ?>
                </p>
            <?php endif; ?>

            <div class="bill-stats">
                <div>
                    <span class="stat-label">Usage</span>
                    <span class="stat-value"><?= format_number($current["usage"]) ?> m³</span>
                </div>
                <div>
                    <span class="stat-label">Reading</span>
                    <span class="stat-value"><?= $current["read_on"]->format("M d") ?></span>
                </div>
                <div>
                    <span class="stat-label">Due</span>
                    <span class="stat-value"><?= $current["due_on"]->format("M d") ?></span>
                </div>
            </div>

            <?php if ($account["balance"] > $current["unpaid"]): ?>
                <p class="note">Total unpaid balance including earlier bills: <strong>₱<?= number_format($account["balance"], 2) ?></strong></p>
            <?php elseif ($account["balance"] <= 0): ?>
                <p class="note">You're all paid up. Thank you!</p>
            <?php endif; ?>

            <a href="bills.php" class="btn-primary">
                View Bills
                <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6"/></svg>
            </a>

        <?php endif; ?>
    </section>

    <!-- QUICK ACTIONS -->
    <section class="area-actions" aria-labelledby="quickActionsTitle">
        <h2 class="section-title" id="quickActionsTitle">Quick actions</h2>

        <div class="quick-actions">
            <a href="bills.php" class="action-tile">
                <span class="action-icon bills"><?= nav_icon("bill") ?></span>
                <span class="action-text">
                    Bills
                    <small>Paid &amp; unpaid</small>
                </span>
            </a>
            <a href="service.php" class="action-tile">
                <span class="action-icon repair"><?= nav_icon("wrench") ?></span>
                <span class="action-text">
                    Repair
                    <small>Report a problem</small>
                </span>
            </a>
        </div>
    </section>

    <!-- CONSUMPTION -->
    <section class="card area-chart" aria-labelledby="consumptionTitle">
        <div class="card-head">
            <div>
                <?= section_title("Consumption", "chart", "blue", "consumptionTitle") ?>
                <p class="card-sub">Last <?= max(1, count($chartBills)) ?> bill<?= count($chartBills) === 1 ? "" : "s" ?></p>
            </div>
            <span class="chip">m³</span>
        </div>

        <?php if (!$chartBills): ?>
            <?= empty_state("gauge", "No meter readings yet", "Your usage will show here after your first reading.") ?>
        <?php else: ?>
            <div class="chart" aria-hidden="true">
                <div class="chart-grid">
                    <span style="top:0" data-label="<?= format_number($axisMax) ?>"></span>
                    <span style="top:50%" data-label="<?= format_number($axisMax / 2) ?>"></span>
                    <span style="top:100%" data-label="0"></span>
                </div>

                <div class="chart-cols">
                    <?php foreach ($chartBills as $i => $b): ?>
                        <?php $isLatest = $i === count($chartBills) - 1; ?>
                        <div class="chart-col <?= $isLatest ? 'latest' : '' ?>" tabindex="0" style="--h:<?= round($b["usage"] / $axisMax * 100, 2) ?>%">
                            <div class="bar-area">
                                <div class="bar" style="height:var(--h)"></div>
                                <?php if ($isLatest): ?>
                                    <span class="bar-value"><?= format_number($b["usage"]) ?></span>
                                <?php endif; ?>
                                <span class="chart-tip">
                                    <strong><?= $b["period"] ?></strong>
                                    <?= format_number($b["usage"]) ?> m³ · ₱<?= number_format($b["amount"], 2) ?>
                                </span>
                            </div>
                            <span class="bar-label"><?= $b["month"] ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Same data as a table for screen readers -->
            <table class="sr-only">
                <caption>Water consumption per bill</caption>
                <tr><th>Bill</th><th>Usage (m³)</th><th>Amount</th></tr>
                <?php foreach ($chartBills as $b): ?>
                    <tr><td><?= $b["period"] ?></td><td><?= format_number($b["usage"]) ?></td><td>₱<?= number_format($b["amount"], 2) ?></td></tr>
                <?php endforeach; ?>
            </table>
        <?php endif; ?>
    </section>

    <!-- RECENT PAYMENTS -->
    <section class="card area-recent" aria-labelledby="recentTitle">
        <?= section_title("Recent payments", "wallet", "green", "recentTitle") ?>

        <?php if (!$recentPayments): ?>
            <?= empty_state("wallet", "No payments yet", "Payments made at the water office will appear here.") ?>
        <?php endif; ?>

        <?php foreach ($recentPayments as $p): ?>
            <div class="list-row">
                <div class="row-main">
                    <?= row_icon("check", "green") ?>
                    <div>
                        <?= date("M d, Y", strtotime($p["payment_date"])) ?>
                        <small><?= htmlspecialchars($p["payment_method"]) ?> payment</small>
                    </div>
                </div>
                <span class="amount">₱<?= number_format($p["amount"], 2) ?></span>
            </div>
        <?php endforeach; ?>

        <a href="history.php" class="link-more">View all payments →</a>
    </section>

</div>

<?php include "includes/footer.php"; ?>
