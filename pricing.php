<?php
require "includes/auth.php";
require_once "includes/icons.php";
require "includes/billing.php";

$pricing = get_pricing();

$sampleUsages = [5, 10, 15, 20, 30, 50];
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Pricing - AquaBill</title>

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
                <h1>Pricing</h1>
                <p>Water rates used for meter readings and payments.</p>
            </div>
        </div>

        <?php if (isset($_GET["success"])): ?>
            <div class="alert success"><?= icon("check-circle") ?> Pricing updated.</div>
        <?php elseif (isset($_GET["error"])): ?>
            <div class="alert error"><?= icon("alert") ?> <?= htmlspecialchars($_GET["error"]) ?></div>
        <?php endif; ?>

        <div class="grid-2">

            <!-- RATE FORM -->
            <div class="panel">

                <h2>Water Rates</h2>

                <form class="form" method="POST" action="action/update_pricing.php">

                    <label>
                        Minimum Charge (₱)
                        <input type="number" name="minimum_charge" min="0" step="0.01" required value="<?= htmlspecialchars($pricing["minimum_charge"]) ?>">
                        <span class="hint">Flat amount every consumer pays.</span>
                    </label>

                    <label>
                        Cubic Meters Covered by Minimum
                        <input type="number" name="minimum_cubic" min="0" step="1" required value="<?= htmlspecialchars($pricing["minimum_cubic"]) ?>">
                    </label>

                    <label>
                        Rate per Excess Cubic Meter (₱)
                        <input type="number" name="excess_rate" min="0" step="0.01" required value="<?= htmlspecialchars($pricing["excess_rate"]) ?>">
                    </label>

                    <label>
                        Days Until Bill Is Due
                        <input type="number" name="due_days" min="1" step="1" required value="<?= htmlspecialchars($pricing["due_days"]) ?>">
                        <span class="hint">Counted from the meter reading date. Shown to consumers on their bills.</span>
                    </label>

                    <button type="submit" class="btn"><?= icon("save") ?> Save Rates</button>

                </form>

            </div>

            <!-- EXAMPLES -->
            <div class="panel">

                <h2>Sample Bills</h2>

                <table>
                    <thead>
                        <tr>
                            <th>Usage</th>
                            <th class="num">Bill</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($sampleUsages as $usage): ?>
                        <tr>
                            <td><?= $usage ?> m³</td>
                            <td class="num">₱<?= number_format(compute_bill($usage, $pricing), 2) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>

                <p class="hint" style="margin-top:12px">
                    First <?= (int) $pricing["minimum_cubic"] ?> m³ = ₱<?= number_format($pricing["minimum_charge"], 2) ?>,
                    then ₱<?= number_format($pricing["excess_rate"], 2) ?> per m³.
                </p>

            </div>

        </div>

    </div>

</div>

</body>
</html>
