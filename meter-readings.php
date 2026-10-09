<?php
require "includes/auth.php";
require "database/database.php";
require "includes/billing.php";
require_once "includes/icons.php";

$pricing = get_pricing();


/* Get consumers for dropdown, with their last current reading to prefill "previous" */
$stmt = $pdo->prepare("
    SELECT
        c.user_code,
        c.name,
        (
            SELECT r.current_reading
            FROM readings r
            WHERE r.user_code = c.user_code
            ORDER BY r.id DESC
            LIMIT 1
        ) AS last_reading
    FROM consumers c
    ORDER BY c.name ASC
");

$stmt->execute();
$consumers = $stmt->fetchAll();


/* Get latest reading per consumer */
$stmt2 = $pdo->prepare("
    SELECT
        c.user_code,
        c.name,
        r.id,
        r.previous_reading,
        r.current_reading,
        r.reading_date
    FROM consumers c
    LEFT JOIN LATERAL (
        SELECT *
        FROM readings r
        WHERE r.user_code = c.user_code
        ORDER BY r.id DESC
        LIMIT 1
    ) r ON true
    ORDER BY c.name ASC
");

$stmt2->execute();

$latestReadings = $stmt2->fetchAll();

// 12.50 -> "12.5", 300.00 -> "300"
function format_reading($value): string
{
    return $value === null ? "—" : rtrim(rtrim(number_format((float) $value, 2), "0"), ".");
}
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <link rel="icon" href="img/favicon.svg" type="image/svg+xml">
    <link rel="apple-touch-icon" href="img/logo.png">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Meter Readings - AquaBill</title>

    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/app.css">
    <script src="js/admin.js" defer></script>
</head>

<body>

<div class="dashboard">

    <?php include "includes/sidebar.php"; ?>

    <!-- MAIN CONTENT -->
    <div class="main">

        <div class="header">
            <div>
                <h1>Meter Readings</h1>
                <p>Latest reading for each consumer.</p>
            </div>

            <button type="button" class="btn" data-modal-open="addModal">
                <?= icon("plus") ?> Add Reading
            </button>
        </div>

        <?php if (isset($_GET["success"])): ?>
            <div class="alert success"><?= icon("check-circle") ?> Reading saved.</div>
        <?php elseif (isset($_GET["updated"])): ?>
            <div class="alert success"><?= icon("check-circle") ?> Reading updated.</div>
        <?php endif; ?>

        <!-- READINGS TABLE -->
        <div class="table-container">

            <div class="filters compact">
                <label class="input-icon">
                    <span class="sr-only">Search consumers</span>
                    <?= icon("search") ?>
                    <input type="search" id="search" placeholder="Search consumer">
                </label>
            </div>

            <table>

                <thead>
                    <tr>
                        <th>Consumer</th>
                        <th>User Code</th>
                        <th class="num">Previous</th>
                        <th class="num">Current</th>
                        <th class="num">Usage</th>
                        <th class="num">Amount</th>
                        <th>Date</th>
                        <th class="cell-actions"><span class="sr-only">Actions</span></th>
                    </tr>
                </thead>

                <tbody id="readingRows">

                <?php foreach ($latestReadings as $r): ?>

                    <?php
                    $usage  = max(0, ($r['current_reading'] ?? 0) - ($r['previous_reading'] ?? 0));
                    $amount = compute_bill($usage, $pricing);
                    ?>

                    <tr>
                        <td><strong><?= htmlspecialchars($r['name']) ?></strong></td>
                        <td class="code"><?= htmlspecialchars($r['user_code']) ?></td>
                        <td class="num"><?= format_reading($r['previous_reading']) ?></td>
                        <td class="num"><?= format_reading($r['current_reading']) ?></td>
                        <td class="num"><?= $r['id'] ? format_reading($usage) . " m³" : "—" ?></td>
                        <td class="num"><?= $r['id'] ? "₱" . number_format($amount, 2) : "—" ?></td>
                        <td class="muted"><?= $r['reading_date'] ? date("M d, Y", strtotime($r['reading_date'])) : "No reading yet" ?></td>

                        <td class="cell-actions">
                            <?php if ($r['id']): ?>
                                <button type="button" class="icon-btn edit-reading"
                                    aria-label="Edit reading for <?= htmlspecialchars($r['name']) ?>"
                                    data-tooltip="Edit reading"
                                    data-id="<?= (int) $r['id'] ?>"
                                    data-name="<?= htmlspecialchars($r['name']) ?>"
                                    data-previous="<?= htmlspecialchars($r['previous_reading']) ?>"
                                    data-current="<?= htmlspecialchars($r['current_reading']) ?>">
                                    <?= icon("pencil") ?>
                                </button>
                            <?php else: ?>
                                <button type="button" class="icon-btn add-for"
                                    aria-label="Add first reading for <?= htmlspecialchars($r['name']) ?>"
                                    data-tooltip="Add reading"
                                    data-user-code="<?= htmlspecialchars($r['user_code']) ?>">
                                    <?= icon("plus") ?>
                                </button>
                            <?php endif; ?>
                        </td>
                    </tr>

                <?php endforeach; ?>

                <?php if (!$latestReadings): ?>
                    <tr><td colspan="8" class="empty">No consumers yet.</td></tr>
                <?php endif; ?>

                </tbody>

            </table>

        </div>

    </div>

</div>

<!-- ADD READING MODAL -->
<div id="addModal" class="modal" role="dialog" aria-modal="true" aria-labelledby="addTitle">
    <div class="modal-card">

        <div class="modal-head">
            <h2 id="addTitle">Add Meter Reading</h2>
            <button type="button" class="icon-btn" data-modal-close aria-label="Close"><?= icon("x") ?></button>
        </div>

        <form class="form modal-body" method="POST" action="action/add_reading.php">

            <label>
                Consumer
                <select name="user_code" id="consumer" required>
                    <option value="">Select consumer</option>
                    <?php foreach ($consumers as $c): ?>
                        <option value="<?= htmlspecialchars($c['user_code']) ?>" data-last="<?= $c['last_reading'] ?? '' ?>">
                            <?= htmlspecialchars($c['name']) ?> (<?= htmlspecialchars($c['user_code']) ?>)
                        </option>
                    <?php endforeach; ?>
                </select>
            </label>

            <div class="form-row">
                <label>
                    Previous
                    <input type="number" name="previous_reading" id="previous" min="0" step="any" required>
                </label>

                <label>
                    Current
                    <input type="number" name="current_reading" id="current" min="0" step="any" required>
                </label>
            </div>

            <div class="preview">
                <div>Usage <strong><span id="usage">0</span> m³</strong></div>
                <div>Amount <strong>₱<span id="amount"><?= number_format($pricing['minimum_charge'], 2) ?></span></strong></div>
            </div>

            <div class="modal-foot">
                <button type="button" class="btn btn-secondary" data-modal-close>Cancel</button>
                <button type="submit" class="btn"><?= icon("save") ?> Save Reading</button>
            </div>

        </form>

    </div>
</div>

<!-- EDIT READING MODAL -->
<div id="editModal" class="modal" role="dialog" aria-modal="true" aria-labelledby="editTitle">
    <div class="modal-card">

        <div class="modal-head">
            <h2 id="editTitle">Edit Meter Reading</h2>
            <button type="button" class="icon-btn" data-modal-close aria-label="Close"><?= icon("x") ?></button>
        </div>

        <form class="form modal-body" method="POST" action="action/update_reading.php">

            <input type="hidden" name="id" id="edit_id">

            <p class="hint" id="edit_for"></p>

            <div class="form-row">
                <label>
                    Previous
                    <input type="number" name="previous_reading" id="edit_previous" min="0" step="any" required>
                </label>

                <label>
                    Current
                    <input type="number" name="current_reading" id="edit_current" min="0" step="any" required>
                </label>
            </div>

            <div class="modal-foot">
                <button type="button" class="btn btn-secondary" data-modal-close>Cancel</button>
                <button type="submit" class="btn"><?= icon("save") ?> Update Reading</button>
            </div>

        </form>

    </div>
</div>

<script>

// Rates come from the Pricing page (config/pricing.json)
const pricing = <?= json_encode($pricing) ?>;

const consumerSelect = document.getElementById("consumer");
const previousInput = document.getElementById("previous");
const currentInput = document.getElementById("current");

function calculate() {
    const usage = Math.max(0, (parseFloat(currentInput.value) || 0) - (parseFloat(previousInput.value) || 0));
    const excess = Math.max(0, usage - pricing.minimum_cubic);
    const amount = pricing.minimum_charge + (excess * pricing.excess_rate);

    document.getElementById("usage").textContent = +usage.toFixed(2);
    document.getElementById("amount").textContent = amount.toFixed(2);
}

// Prefill "previous" with the consumer's last current reading
consumerSelect.addEventListener("change", () => {
    previousInput.value = consumerSelect.selectedOptions[0].dataset.last ?? "";
    calculate();
});

previousInput.addEventListener("input", calculate);
currentInput.addEventListener("input", calculate);

// Row "+" opens the add modal with that consumer selected
document.querySelectorAll(".add-for").forEach((button) => {
    button.addEventListener("click", () => {
        consumerSelect.value = button.dataset.userCode;
        consumerSelect.dispatchEvent(new Event("change"));
        openModal("addModal");
    });
});

// Row pencil opens the edit modal
document.querySelectorAll(".edit-reading").forEach((button) => {
    button.addEventListener("click", () => {
        document.getElementById("edit_id").value = button.dataset.id;
        document.getElementById("edit_previous").value = button.dataset.previous;
        document.getElementById("edit_current").value = button.dataset.current;
        document.getElementById("edit_for").textContent = "Latest reading for " + button.dataset.name;
        openModal("editModal");
    });
});

// Search by consumer name or code
document.getElementById("search").addEventListener("input", (e) => {
    const term = e.target.value.trim().toLowerCase();
    document.querySelectorAll("#readingRows tr").forEach((row) => {
        row.hidden = term !== "" && !row.textContent.toLowerCase().includes(term);
    });
});

</script>

</body>
</html>
