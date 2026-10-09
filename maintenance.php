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

// Problem types in the order they appear on the request forms (admin + consumer).
// Any other type found in the database is added after these.
const KNOWN_REQUEST_TYPES = [
    "Leak Report",
    "Meter Issue",
    "No Water",
    "Low Water Pressure",
    "Water Quality",
    "Billing Concern",
    "Other",
];

/* How many requests of each problem type exist (for the dropdown) */
$typeCounts = [];
foreach ($pdo->query("SELECT request_type, COUNT(*) AS total FROM maintenance_requests GROUP BY request_type") as $row) {
    $typeCounts[$row["request_type"]] = (int) $row["total"];
}

// Every problem type from the forms (even with 0 requests), then any others found in the database
$requestTypes = array_values(array_unique(array_merge(KNOWN_REQUEST_TYPES, array_keys($typeCounts))));

/* Active filters */
$filter     = $_GET["status"] ?? "";
$typeFilter = $_GET["type"] ?? "";

if (!in_array($filter, REQUEST_STATUSES, true)) {
    $filter = "";
}

if (!in_array($typeFilter, $requestTypes, true)) {
    $typeFilter = "";
}

/* Requests matching both filters */
$where  = [];
$params = [];

if ($filter !== "") {
    $where[] = "status = :status";
    $params[":status"] = $filter;
}

if ($typeFilter !== "") {
    $where[] = "request_type = :type";
    $params[":type"] = $typeFilter;
}

$stmt = $pdo->prepare(
    "SELECT * FROM maintenance_requests"
    . ($where ? " WHERE " . implode(" AND ", $where) : "")
    . " ORDER BY created_at DESC"
);
$stmt->execute($params);
$requests = $stmt->fetchAll();

/* Status cards: follow the problem filter, so you see e.g. how many leak reports are open */
$counts = array_fill_keys(REQUEST_STATUSES, 0);
$totalRequests = 0;

$stmt = $pdo->prepare(
    "SELECT status, COUNT(*) AS total FROM maintenance_requests"
    . ($typeFilter !== "" ? " WHERE request_type = :type" : "")
    . " GROUP BY status"
);
$stmt->execute($typeFilter !== "" ? [":type" => $typeFilter] : []);

foreach ($stmt as $row) {
    // Total counts every request, even one with an unexpected status
    $totalRequests += (int) $row["total"];

    if (isset($counts[$row["status"]])) {
        $counts[$row["status"]] = (int) $row["total"];
    }
}

// Current filters as a query string, so changing a status brings you back to the same view
$activeFilters = http_build_query(array_filter(["status" => $filter, "type" => $typeFilter]));
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <link rel="icon" href="img/favicon.svg" type="image/svg+xml">
    <link rel="apple-touch-icon" href="img/logo.png">
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

            <div class="stat-card">
                <span class="stat-icon"><?= icon("wrench") ?></span>
                <div>
                    <h5>Total Requests</h5>
                    <p><?= $totalRequests ?></p>
                </div>
            </div>
        </div>

        <form class="filters compact" method="GET">
            <select name="status" onchange="this.form.submit()" aria-label="Filter by status">
                <option value="">All statuses</option>
                <?php foreach (REQUEST_STATUSES as $status): ?>
                    <option value="<?= $status ?>" <?= $filter === $status ? "selected" : "" ?>><?= $status ?></option>
                <?php endforeach; ?>
            </select>

            <select name="type" onchange="this.form.submit()" aria-label="Filter by problem">
                <option value="">All problems</option>
                <?php foreach ($requestTypes as $type): ?>
                    <option value="<?= htmlspecialchars($type) ?>" <?= $typeFilter === $type ? "selected" : "" ?>>
                        <?= htmlspecialchars($type) ?> (<?= $typeCounts[$type] ?? 0 ?>)
                    </option>
                <?php endforeach; ?>
            </select>

            <?php if ($filter !== "" || $typeFilter !== ""): ?>
                <a href="maintenance.php" class="icon-btn outlined" aria-label="Clear filters" data-tooltip="Clear filters"><?= icon("reset") ?></a>
            <?php endif; ?>
        </form>

        <?php if (!$requests): ?>
            <div class="table-container">
                <p class="empty">
                    <?php if ($filter !== "" || $typeFilter !== ""): ?>
                        No <?= htmlspecialchars(strtolower(trim($filter . " " . $typeFilter))) ?> requests found.
                        <a href="maintenance.php" class="panel-link">Show all requests</a>
                    <?php else: ?>
                        No maintenance requests found.
                    <?php endif; ?>
                </p>
            </div>
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
                                <input type="hidden" name="back" value="<?= htmlspecialchars($activeFilters) ?>">
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
