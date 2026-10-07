<?php
require "includes/auth.php";
require "database/database.php";
require_once "includes/consumer_defaults.php";

if(isset($_GET['success'])) {
    $message = "Consumer added successfully.\n\nUser Code: " . ($_GET['user_code'] ?? '') . "\nDefault Password: " . DEFAULT_CONSUMER_PASSWORD;

    echo "<script>alert(" . json_encode($message) . ");</script>";
}

$sql = "
    SELECT 
        c.id,
        c.name,
        c.address,
        c.meter_no,
        c.status,
        c.user_code,
        u.role
    FROM consumers c
    INNER JOIN users u
        ON c.user_code = u.user_code
    ORDER BY c.id DESC
";

$stmt = $pdo->prepare($sql);
$stmt->execute();

$consumers = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Consumers - AquaBill</title>

    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/consumers.css">
</head>

<body>

<div class="dashboard">

    <?php include "includes/sidebar.php"; ?>

    <!-- MAIN CONTENT -->
    <div class="main">

        <div class="header">

            <h1>Consumers</h1>

            <button type="button" class="btn" id="openModal">
                + Add Consumer
            </button>

        </div>

        <!-- ADD MODAL -->
        <div id="modal" class="modal">

            <div class="modal-content">

                <h2>Add Consumer</h2>

                <form method="POST" action="action/add_consumer.php">

                    <input type="text" name="name" placeholder="Full Name" required>
                    <input type="text" name="address" placeholder="Address" required>
                    <input type="text" name="meter_no" placeholder="Meter Number" required>

                    <button type="submit">Save</button>
                    <button type="button" onclick="closeModal()">Cancel</button>

                </form>

            </div>

        </div>

        <!-- EDIT MODAL -->
        <div id="editModal" class="modal">

            <div class="modal-content">

                <h2>Edit Consumer</h2>

                <form method="POST" action="action/update_consumer.php">

                    <input type="hidden" name="user_code" id="edit_user_code">

                    <input type="text" name="name" id="edit_name" required>
                    <input type="text" name="address" id="edit_address" required>
                    <input type="text" name="meter_no" id="edit_meter_no" required>
                    <div class="form-group">
                        <label>Status</label>
                        <select name="status" id="edit_status" required>
                            <option value="Active">Active</option>
                            <option value="Inactive">Inactive</option>
                        </select>
                    </div>

                    <div class="modal-buttons">

                        <button type="submit" class="btn-save">Save</button>

                        <button type="button" class="btn-cancel" onclick="closeEditModal()">
                            Cancel
                        </button>

                    </div>

                </form>

            </div>

        </div>

        <!-- TABLE -->
        <div class="table-container">

            <table>

                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Address</th>
                        <th>Meter No.</th>
                        <th>Status</th>
                        <th>User Code</th>
                        <th>Action</th>
                    </tr>
                </thead>

                <tbody>

                <?php foreach ($consumers as $row): ?>

                    <tr>

                        <td><?= htmlspecialchars($row['name']) ?></td>
                        <td><?= htmlspecialchars($row['address']) ?></td>
                        <td><?= htmlspecialchars($row['meter_no']) ?></td>

                        <td>
                            <span class="status <?= strtolower($row['status']) ?>">
                                <?= htmlspecialchars($row['status']) ?>
                            </span>
                        </td>

                        <td>
                            <strong><?= htmlspecialchars($row['user_code']) ?></strong>
                        </td>

                        <td class="actions">

                            <button type="button"
                                class="btn-edit"
                                onclick="openEditModal(
                                    '<?= $row['user_code'] ?>',
                                    '<?= htmlspecialchars($row['name'], ENT_QUOTES) ?>',
                                    '<?= htmlspecialchars($row['address'], ENT_QUOTES) ?>',
                                    '<?= htmlspecialchars($row['meter_no'], ENT_QUOTES) ?>',
                                    '<?= $row['status'] ?>'
                                )">
                                Edit
                            </button>

                            <form method="POST" action="action/delete_consumer.php"
                                  onsubmit="return confirm('Delete this consumer?')">
                                <input type="hidden" name="user_code" value="<?= htmlspecialchars($row['user_code']) ?>">
                                <button type="submit" class="btn-delete">Delete</button>
                            </form>

                        </td>

                    </tr>

                <?php endforeach; ?>

                </tbody>

            </table>

        </div>

    </div>

</div>

<!-- JS -->
<script>

const modal = document.getElementById('modal');
const editModal = document.getElementById('editModal');
const openModal = document.getElementById('openModal');

/* OPEN ADD */
openModal.addEventListener('click', () => {
    modal.style.display = 'flex';
});

/* CLOSE ADD */
function closeModal(){
    modal.style.display = 'none';
}

/* OPEN EDIT */
function openEditModal(user_code, name, address, meter_no, status){

    document.getElementById('edit_user_code').value = user_code;
    document.getElementById('edit_name').value = name;
    document.getElementById('edit_address').value = address;
    document.getElementById('edit_meter_no').value = meter_no;
    document.getElementById('edit_status').value = status;

    editModal.style.display = 'flex';
}

/* CLOSE EDIT */
function closeEditModal(){
    editModal.style.display = 'none';
}

/* CLICK OUTSIDE CLOSE */
window.onclick = function(e){
    if(e.target === modal) modal.style.display = 'none';
    if(e.target === editModal) editModal.style.display = 'none';
}

</script>

</body>
</html>