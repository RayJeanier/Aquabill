<?php
require "includes/auth.php";
require "database/database.php";
require "includes/billing.php";

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

?>
<!DOCTYPE html>

<html>
<head>

    <meta charset="UTF-8">
    <title>Meter Readings - AquaBill</title>

    <link 
        href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap"
        rel="stylesheet"
    >

    <link rel="stylesheet" href="css/meter-reading.css">

</head>


<body>

<div class="dashboard">


<!-- SIDEBAR -->
<?php include "includes/sidebar.php"; ?>


<!-- MAIN CONTENT -->
<div class="main">


<!-- HEADER -->
<div class="header">

    <h1>
        Meter Readings
    </h1>


    <button 
        class="btn"
        onclick="openModal()"
    >
        + Add Reading
    </button>


</div>



<!-- ADD READING MODAL -->
<div 
    id="modal"
    class="modal"
>

<div class="modal-content">


<h2>
    Add Meter Reading
</h2>


<form 
    method="POST"
    action="action/add_reading.php"
>


<!-- CONSUMER -->
<select
    name="user_code"
    id="consumer"
    required
>

<option value="">
    Select Consumer
</option>


<?php foreach($consumers as $c): ?>


<option
    value="<?= htmlspecialchars($c['user_code']) ?>"
    data-last="<?= $c['last_reading'] ?? '' ?>"
>

<?= htmlspecialchars($c['name']) ?>

(<?= htmlspecialchars($c['user_code']) ?>)

</option>


<?php endforeach; ?>


</select>



<!-- PREVIOUS -->
<input 
    type="number"
    name="previous_reading"
    id="previous"
    placeholder="Previous Reading"
    required
    oninput="calculate()"
>



<!-- CURRENT -->
<input 
    type="number"
    name="current_reading"
    id="current"
    placeholder="Current Reading"
    required
    oninput="calculate()"
>



<!-- PREVIEW -->
<div class="preview">

    <p>
        Usage:
        <span id="usage">
            0
        </span>
    </p>


    <p>
        Amount:
        ₱<span id="amount"><?= number_format($pricing['minimum_charge'], 2) ?></span>
    </p>

</div>



<button type="submit">

    Save Reading

</button>


<button 
    type="button"
    onclick="closeModal()"
>

    Cancel

</button>


</form>


</div>
</div>
<!-- EDIT READING MODAL -->
<div id="editReadingModal" class="modal">

    <div class="modal-content">

        <h2>Edit Meter Reading</h2>

        <form method="POST" action="action/update_reading.php">

            <input 
                type="hidden"
                name="id"
                id="edit_id"
            >

            <!-- Previous Reading -->
            <input 
                type="number"
                name="previous_reading"
                id="edit_previous"
                placeholder="Previous Reading"
                required
            >

            <!-- Current Reading -->
            <input 
                type="number"
                name="current_reading"
                id="edit_current"
                placeholder="Current Reading"
                required
            >

            <button type="submit">
                Update Reading
            </button>

            <button 
                type="button"
                onclick="closeEditModal()"
            >
                Cancel
            </button>

        </form>

    </div>

</div>



<!-- READINGS DISPLAY -->
<div class="table-container">

<table>

    <thead>

        <tr>

            <th>Consumer</th>
            <th>User Code</th>
            <th>Previous</th>
            <th>Current</th>
            <th>Usage</th>
            <th>Amount</th>
            <th>Date</th>
            <th>Action</th>

        </tr>

    </thead>


    <tbody>


<?php foreach($latestReadings as $r): ?>


<?php

$previous = $r['previous_reading'] ?? 0;
$current = $r['current_reading'] ?? 0;

$usage = $current - $previous;

if($usage < 0){
    $usage = 0;
}

$amount = compute_bill($usage, $pricing);

?>


<tr>

    <!-- Consumer Name -->
    <td>
        <?= htmlspecialchars($r['name']) ?>
    </td>


    <!-- User Code -->
    <td>
        <strong>
            <?= htmlspecialchars($r['user_code']) ?>
        </strong>
    </td>


    <!-- Previous -->
    <td>
        <?= $r['previous_reading'] ?? "—" ?>
    </td>


    <!-- Current -->
    <td>
        <?= $r['current_reading'] ?? "—" ?>
    </td>


    <!-- Usage -->
    <td>
        <?= $usage ?>
    </td>


    <!-- Amount -->
    <td>
        ₱<?= number_format($amount, 2) ?>
    </td>


    <!-- Date -->
    <td>
        <?= $r['reading_date'] ?? "—" ?>
    </td>


    <!-- Action -->
    <td class="actions">

        <?php if($r['id']): ?>

        <button 
            class="btn-edit"

            onclick="openEditModal(
                '<?= $r['id'] ?>',
                '<?= $r['previous_reading'] ?>',
                '<?= $r['current_reading'] ?>'
            )"
        >
            Edit
        </button>

        <?php else: ?>

            No Reading

        <?php endif; ?>

    </td>

</tr>


<?php endforeach; ?>


    </tbody>

</table>

</div>


</div> <!-- END MAIN -->

</div> <!-- END DASHBOARD -->
<script>

// =============================
// GET MODAL ELEMENTS
// =============================

const addModal = document.getElementById("modal");
const editModal = document.getElementById("editReadingModal");


// =============================
// ADD READING MODAL
// =============================

function openModal() {
    addModal.style.display = "flex";
}

function closeModal() {
    addModal.style.display = "none";
}


// =============================
// EDIT READING MODAL
// =============================

function openEditModal(id, previous, current) {

    document.getElementById("edit_id").value = id;

    document.getElementById("edit_previous").value = previous;

    document.getElementById("edit_current").value = current;

    editModal.style.display = "flex";
}


function closeEditModal() {

    editModal.style.display = "none";

}


// =============================
// BILL CALCULATION PREVIEW
// Rates come from the Pricing page (config/pricing.json)
// =============================

const pricing = <?= json_encode($pricing) ?>;

// Prefill "previous" with the consumer's last current reading
document.getElementById("consumer").addEventListener("change", function () {
    const last = this.selectedOptions[0].dataset.last;
    document.getElementById("previous").value = last ?? "";
    calculate();
});

function calculate() {

    let previous = parseFloat(
        document.getElementById("previous").value || 0
    );

    let current = parseFloat(
        document.getElementById("current").value || 0
    );

    let usage = current - previous;

    if (usage < 0) {
        usage = 0;
    }

    let excess = Math.max(0, usage - pricing.minimum_cubic);

    let amount = pricing.minimum_charge + (excess * pricing.excess_rate);


    document.getElementById("usage").innerText = usage;

    document.getElementById("amount").innerText =
        amount.toFixed(2);

}


// =============================
// CLOSE MODALS WHEN CLICKING
// OUTSIDE THE POPUP
// =============================

window.onclick = function(event) {

    if (event.target === addModal) {
        closeModal();
    }

    if (event.target === editModal) {
        closeEditModal();
    }

};

</script>


</body>
</html>