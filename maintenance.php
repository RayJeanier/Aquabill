<?php
require "includes/auth.php";
require "database/database.php";

const REQUEST_STATUSES = ["Open", "In Progress", "Resolved"];

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

    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/app.css">
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
            <a href="new-request.php" class="btn">+ New Request</a>
        </div>

        <?php if (isset($_GET["created"])): ?>
            <div class="alert success">Maintenance request created.</div>
        <?php elseif (isset($_GET["updated"])): ?>
            <div class="alert success">Request status updated.</div>
        <?php endif; ?>

        <div class="stats">
            <?php foreach ($counts as $status => $total): ?>
                <div class="stat-card">
                    <h5><?= strtoupper($status) ?></h5>
                    <p><?= $total ?></p>
                </div>
            <?php endforeach; ?>
        </div>

        <form class="filters" method="GET">
            <select name="status" onchange="this.form.submit()">
                <option value="">All Statuses</option>
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
                            <small><?= htmlspecialchars($row["consumer_name"]) ?> · <?= htmlspecialchars($row["user_code"]) ?></small>
                        </div>

                        <span class="badge <?= strtolower(str_replace(" ", "-", $row["status"])) ?>">
                            <?= htmlspecialchars($row["status"]) ?>
                        </span>
                    </div>

                    <p><?= nl2br(htmlspecialchars($row["description"])) ?></p>

                    <div class="card-bottom">
                        <small><?= date("F d, Y", strtotime($row["created_at"])) ?></small>

                        <form method="POST" action="action/update_request_status.php">
                            <input type="hidden" name="id" value="<?= (int) $row["id"] ?>">
                            <select name="status" onchange="this.form.submit()" title="Change status">
                                <?php foreach (REQUEST_STATUSES as $status): ?>
                                    <option value="<?= $status ?>" <?= $row["status"] === $status ? "selected" : "" ?>><?= $status ?></option>
                                <?php endforeach; ?>
                            </select>
                        </form>
                    </div>

                </div>
            <?php endforeach; ?>

        </div>

    </div>

</div>

</body>
</html>
