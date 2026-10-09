<?php
require "includes/auth.php";
require_once "includes/icons.php";
require "database/database.php";

$consumers = $pdo->query("
    SELECT user_code, name
    FROM consumers
    ORDER BY name
")->fetchAll();

$requestTypes = [
    "Leak Report",
    "Meter Issue",
    "No Water",
    "Low Water Pressure",
    "Water Quality",
    "Billing Concern",
    "Other",
];
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <link rel="icon" href="img/favicon.svg" type="image/svg+xml">
    <link rel="apple-touch-icon" href="img/logo.png">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>New Request - AquaBill</title>

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
                <h1>New Maintenance Request</h1>
                <p>Log a problem reported by a consumer.</p>
            </div>
        </div>

        <?php if (isset($_GET["error"])): ?>
            <div class="alert error"><?= icon("alert") ?> <?= htmlspecialchars($_GET["error"]) ?></div>
        <?php endif; ?>

        <div class="panel" style="max-width:640px">

            <form class="form" method="POST" action="action/add_request.php">

                <label>
                    Consumer
                    <select name="user_code" required>
                        <option value="">Select Consumer</option>
                        <?php foreach ($consumers as $c): ?>
                            <option value="<?= htmlspecialchars($c["user_code"]) ?>">
                                <?= htmlspecialchars($c["name"]) ?> - <?= htmlspecialchars($c["user_code"]) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </label>

                <label>
                    Request Type
                    <select name="request_type" required>
                        <?php foreach ($requestTypes as $type): ?>
                            <option value="<?= $type ?>"><?= $type ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>

                <label>
                    Description
                    <textarea name="description" required placeholder="Describe the problem and location"></textarea>
                </label>

                <div class="form-row">
                    <a href="maintenance.php" class="btn btn-secondary">Cancel</a>
                    <button type="submit" class="btn"><?= icon("check") ?> Submit Request</button>
                </div>

            </form>

        </div>

    </div>

</div>

</body>
</html>
