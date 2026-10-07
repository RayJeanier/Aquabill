<?php
require "includes/auth.php";
require "../database/database.php";
require "../includes/billing.php";

$userCode = $_SESSION["consumer"]["user_code"];

$stmt = $pdo->prepare("SELECT name, address, meter_no FROM consumers WHERE user_code = :user_code");
$stmt->execute([":user_code" => $userCode]);
$consumer = $stmt->fetch();

// Consumer was deleted while logged in
if (!$consumer) {
    unset($_SESSION["consumer"]);
    header("Location: ../index.html");
    exit;
}

$stmt = $pdo->prepare("
    SELECT previous_reading, current_reading, reading_date
    FROM readings
    WHERE user_code = :user_code
    ORDER BY id DESC
    LIMIT 1
");
$stmt->execute([":user_code" => $userCode]);
$latestReading = $stmt->fetch();

$stmt = $pdo->prepare("
    SELECT amount, cubic_used, payment_method, payment_date
    FROM payments
    WHERE user_code = :user_code
    ORDER BY payment_date DESC
    LIMIT 5
");
$stmt->execute([":user_code" => $userCode]);
$payments = $stmt->fetchAll();

$stmt = $pdo->prepare("
    SELECT request_type, status, created_at
    FROM maintenance_requests
    WHERE user_code = :user_code
    ORDER BY created_at DESC
    LIMIT 5
");
$stmt->execute([":user_code" => $userCode]);
$requests = $stmt->fetchAll();

$usage = $latestReading ? max(0, $latestReading["current_reading"] - $latestReading["previous_reading"]) : null;
$bill  = $usage !== null ? compute_bill($usage) : null;

$firstName = explode(" ", trim($consumer["name"]))[0];

// 12.50 -> "12.5", 300.00 -> "300"
function format_reading(string|float $value): string
{
    return rtrim(rtrim(number_format((float) $value, 2), "0"), ".");
}

$pageTitle    = "My Account";
$pageHeading  = "Welcome back, " . $firstName;
$pageSubtitle = "Here's an overview of your water account";

include "includes/header.php";
?>

<span class="account-chip mobile-only"><?= htmlspecialchars($userCode) ?></span>

<div class="content-grid">

    <!-- MAIN COLUMN -->
    <div class="content-col">

        <!-- LATEST BILL -->
        <section class="bill-card">
            <small>Latest bill</small>

            <?php if ($bill === null): ?>
                <p class="bill-amount">—</p>
                <p class="bill-note">No meter reading on file yet.</p>
            <?php else: ?>
                <p class="bill-amount">₱<?= number_format($bill, 2) ?></p>

                <div class="bill-stats">
                    <div>
                        <span>Usage</span>
                        <strong><?= format_reading($usage) ?> m³</strong>
                    </div>
                    <div>
                        <span>Reading</span>
                        <strong><?= format_reading($latestReading["current_reading"]) ?></strong>
                    </div>
                    <div>
                        <span>Read on</span>
                        <strong><?= date("M d", strtotime($latestReading["reading_date"])) ?></strong>
                    </div>
                </div>
            <?php endif; ?>
        </section>

        <!-- PAYMENT HISTORY -->
        <section class="panel">
            <h2 class="panel-title">Payment history</h2>

            <div class="list-box">
                <?php if (!$payments): ?>
                    <p class="empty-text">No payments recorded yet.</p>
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
            </div>
        </section>

    </div>

    <!-- SIDE COLUMN -->
    <div class="content-col">

        <!-- MAINTENANCE -->
        <section class="panel">
            <h2 class="panel-title">Maintenance requests</h2>

            <div class="list-box">
                <?php if (!$requests): ?>
                    <p class="empty-text">No maintenance requests.</p>
                <?php endif; ?>

                <?php foreach ($requests as $r): ?>
                    <div class="list-row">
                        <div>
                            <?= htmlspecialchars($r["request_type"]) ?>
                            <small><?= date("F d, Y", strtotime($r["created_at"])) ?></small>
                        </div>
                        <span class="pill <?= strtolower(str_replace(" ", "-", $r["status"])) ?>"><?= htmlspecialchars($r["status"]) ?></span>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>

        <!-- ACCOUNT -->
        <section class="panel">
            <h2 class="panel-title">Account details</h2>

            <div class="list-box">
                <div class="list-row">
                    <span class="label">Name</span>
                    <strong><?= htmlspecialchars($consumer["name"]) ?></strong>
                </div>
                <div class="list-row">
                    <span class="label">Account No.</span>
                    <strong><?= htmlspecialchars($userCode) ?></strong>
                </div>
                <div class="list-row">
                    <span class="label">Address</span>
                    <strong><?= htmlspecialchars($consumer["address"]) ?></strong>
                </div>
                <div class="list-row">
                    <span class="label">Meter No.</span>
                    <strong><?= htmlspecialchars($consumer["meter_no"]) ?></strong>
                </div>
            </div>
        </section>

    </div>

</div>

<?php include "includes/footer.php"; ?>
