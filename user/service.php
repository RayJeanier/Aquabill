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
            <h2 class="section-title" id="formTitle">New request</h2>

            <form class="form" method="POST" action="action/add_request.php">

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
            <h2 class="section-title" id="myRequestsTitle">My requests</h2>

            <?php if (!$requests): ?>
                <p class="empty-text">You haven't sent any requests yet.</p>
            <?php endif; ?>

            <?php foreach ($requests as $r): ?>
                <div class="list-row">
                    <div>
                        <?= htmlspecialchars($r["request_type"]) ?>
                        <small><?= date("M d, Y", strtotime($r["created_at"])) ?></small>
                    </div>
                    <span class="pill <?= $requestStatusClass[$r["status"]] ?? "neutral" ?>"><?= htmlspecialchars($r["status"]) ?></span>
                </div>
            <?php endforeach; ?>
        </section>

    </div>

</div>

<?php include "includes/footer.php"; ?>
