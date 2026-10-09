<?php
require "includes/auth.php";
require "../database/database.php";

$userCode = $_SESSION["consumer"]["user_code"];

$stmt = $pdo->prepare("
    SELECT request_type, status, created_at
    FROM maintenance_requests
    WHERE user_code = :user_code
    ORDER BY created_at DESC
");
$stmt->execute([":user_code" => $userCode]);
$requests = $stmt->fetchAll();

// Sent with each request (see action/add_request.php)
$stmt = $pdo->prepare("SELECT address, meter_no FROM consumers WHERE user_code = :user_code");
$stmt->execute([":user_code" => $userCode]);
$location = $stmt->fetch() ?: ["address" => null, "meter_no" => null];

// Keep in sync with action/add_request.php
$requestTypes = [
    "Leak Report",
    "Meter Issue",
    "No Water",
    "Low Water Pressure",
    "Water Quality",
    "Other",
];

$requestStatusClass = [
    "Open"        => "critical",
    "In Progress" => "warning",
    "Resolved"    => "good",
];

$pageTitle    = "Service";
$pageHeading  = "Request Repair";
$pageSubtitle = "Report a leak, meter problem or water issue";

include "includes/header.php";
?>

<div class="content-grid">

    <div class="content-col">

        <?php if (isset($_GET["sent"])): ?>
            <div class="alert success">Your request was sent. The water office will look into it soon.</div>
        <?php elseif (isset($_GET["error"])): ?>
            <div class="alert error"><?= htmlspecialchars($_GET["error"]) ?></div>
        <?php endif; ?>

        <!-- REQUEST FORM -->
        <section class="card" aria-labelledby="formTitle">
            <?= section_title("New request", "wrench", "amber", "formTitle") ?>

            <form class="form" method="POST" action="action/add_request.php">

                <div class="field">
                    <span>Service location</span>
                    <div class="info-box">
                        <?= row_icon("pin", "teal") ?>
                        <div>
                            <strong><?= htmlspecialchars($location["address"] ?: "No address on file") ?></strong>
                            <small>Meter no. <?= htmlspecialchars($location["meter_no"] ?: "—") ?> · sent with your request</small>
                        </div>
                    </div>
                </div>

                <fieldset class="field">
                    <legend class="sr-only">What's the problem?</legend>
                    <span aria-hidden="true">What's the problem?</span>
                    <div class="type-options">
                        <?php foreach ($requestTypes as $i => $type): ?>
                            <label class="type-option">
                                <input type="radio" name="request_type" value="<?= $type ?>" <?= $i === 0 ? "checked" : "" ?> required>
                                <span><?= $type ?></span>
                            </label>
                        <?php endforeach; ?>
                    </div>
                </fieldset>

                <label class="field">
                    <span>Details</span>
                    <textarea name="description" required maxlength="1000"
                        placeholder="Describe the problem and where it is (e.g. leaking pipe beside the meter, front gate)"></textarea>
                </label>

                <button type="submit" class="btn-primary" style="margin-top:4px">
                    Send Request
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M5 12h14M13 6l6 6-6 6"/></svg>
                </button>

            </form>
        </section>

    </div>

    <div class="content-col">

        <!-- MY REQUESTS -->
        <section class="card" aria-labelledby="myRequestsTitle">
            <?= section_title("My requests", "list", "blue", "myRequestsTitle") ?>

            <?php if (!$requests): ?>
                <?= empty_state("wrench", "No requests yet", "Problems you report will show here with their status.") ?>
            <?php endif; ?>

            <?php foreach ($requests as $r): ?>
                <?php
                $tone = ["critical" => "red", "warning" => "amber", "good" => "green"][$requestStatusClass[$r["status"]] ?? ""] ?? "blue";
                $icon = ["Open" => "alert", "In Progress" => "clock", "Resolved" => "check"][$r["status"]] ?? "wrench";
                ?>
                <div class="list-row">
                    <div class="row-main">
                        <?= row_icon($icon, $tone) ?>
                        <div>
                            <?= htmlspecialchars($r["request_type"]) ?>
                            <small><?= date("M d, Y", strtotime($r["created_at"])) ?></small>
                        </div>
                    </div>
                    <span class="pill <?= $requestStatusClass[$r["status"]] ?? "neutral" ?>"><?= htmlspecialchars($r["status"]) ?></span>
                </div>
            <?php endforeach; ?>
        </section>

    </div>

</div>

<?php include "includes/footer.php"; ?>
