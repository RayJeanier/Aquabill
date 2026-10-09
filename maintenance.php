<?php
require "includes/auth.php";
require "database/database.php";
require_once "includes/icons.php";

const REQUEST_STATUSES = ["Open", "In Progress", "Resolved"];

// Icon shown next to each status in the stat cards and status menu
const STATUS_ICONS = [
    "Open"        => ["alert", "red"],
    "In Progress" => ["clock", "amber"],
    "Resolved"    => ["check-circle", "green"],
];

$filter = $_GET["status"] ?? "";

if (in_array($filter, REQUEST_STATUSES, true)) {
    $stmt = $pdo->prepare("SELECT * FROM maintenance_requests WHERE status = :status ORDER BY created_at DESC");
    $stmt->execute([":status" => $filter]);
} else {
    $filter = "";
    $stmt = $pdo->query("SELECT * FROM maintenance_requests ORDER BY created_at DESC");
}

$requests = $stmt->fetchAll();

$counts = array_fill_keys(REQUEST_STATUSES, 0);

foreach ($pdo->query("SELECT status, COUNT(*) AS total FROM maintenance_requests GROUP BY status") as $row) {
    if (isset($counts[$row["status"]])) {
        $counts[$row["status"]] = (int) $row["total"];
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Maintenance - AquaBill</title>

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
                <h1>Maintenance Requests</h1>
                <p>View and manage maintenance requests.</p>
            </div>
            <a href="new-request.php" class="btn"><?= icon("plus") ?> New Request</a>
        </div>

        <?php if (isset($_GET["created"])): ?>
            <div class="alert success"><?= icon("check-circle") ?> Maintenance request created.</div>
        <?php elseif (isset($_GET["updated"])): ?>
            <div class="alert success"><?= icon("check-circle") ?> Request status updated.</div>
        <?php endif; ?>

        <div class="stats">
            <?php foreach ($counts as $status => $total): ?>
                <div class="stat-card">
                    <span class="stat-icon <?= STATUS_ICONS[$status][1] ?>"><?= icon(STATUS_ICONS[$status][0]) ?></span>
                    <div>
                        <h5><?= $status ?></h5>
                        <p><?= $total ?></p>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <form class="filters compact" method="GET">
            <select name="status" onchange="this.form.submit()" aria-label="Filter by status">
                <option value="">All statuses</option>
                <?php foreach (REQUEST_STATUSES as $status): ?>
                    <option value="<?= $status ?>" <?= $filter === $status ? "selected" : "" ?>><?= $status ?></option>
                <?php endforeach; ?>
            </select>
        </form>

        <?php if (!$requests): ?>
            <div class="table-container"><p class="empty">No maintenance requests found.</p></div>
        <?php endif; ?>

        <div class="request-grid">

            <?php foreach ($requests as $row): ?>
                <div class="request-card">

                    <div class="card-top">
                        <div>
                            <h3><?= htmlspecialchars($row["request_type"]) ?></h3>

                            <div class="request-meta">
                                <span><?= icon("users") ?> <?= htmlspecialchars($row["consumer_name"]) ?> · <?= htmlspecialchars($row["user_code"]) ?></span>
                                <?php if (!empty($row["address"]) || !empty($row["meter_no"])): ?>
                                    <span><?= icon("map-pin") ?> <?= htmlspecialchars(implode(" · ", array_filter([
                                        $row["address"] ?? "",
                                        !empty($row["meter_no"]) ? "Meter no. " . $row["meter_no"] : "",
                                    ]))) ?></span>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- STATUS: click the badge to change it -->
                        <details class="dropdown status-dropdown">
                            <summary class="badge dot <?= strtolower(str_replace(" ", "-", $row["status"])) ?>" aria-label="Status: <?= htmlspecialchars($row["status"]) ?>. Change status">
                                <?= htmlspecialchars($row["status"]) ?>
                                <?= icon("chevron") ?>
                            </summary>

                            <form class="dropdown-menu" method="POST" action="action/update_request_status.php">
                                <input type="hidden" name="id" value="<?= (int) $row["id"] ?>">
                                <div class="dropdown-label">Set status</div>
                                <?php foreach (REQUEST_STATUSES as $status): ?>
                                    <button type="submit" name="status" value="<?= $status ?>"
                                        class="dropdown-item <?= $row["status"] === $status ? 'selected' : '' ?>">
                                        <?= icon(STATUS_ICONS[$status][0]) ?>
                                        <?= $status ?>
                                        <?php if ($row["status"] === $status): ?>
                                            <?= icon("check", "icon check") ?>
                                        <?php endif; ?>
                                    </button>
                                <?php endforeach; ?>
                            </form>
                        </details>
                    </div>

                    <p><?= nl2br(htmlspecialchars($row["description"])) ?></p>

                    <div class="card-bottom">
                        <span><?= icon("calendar", "icon icon-sm") ?> <?= date("F d, Y", strtotime($row["created_at"])) ?></span>
                    </div>

                </div>
            <?php endforeach; ?>

        </div>

    </div>

</div>

</body>
</html>
