<?php
require "includes/auth.php";
require "database/database.php";
require_once "includes/consumer_defaults.php";
require_once "includes/icons.php";
require_once "includes/qr.php";
require_once "includes/reset_password.php";

$sql = "
    SELECT
        c.id,
        c.name,
        c.address,
        c.meter_no,
        c.status,
        c.user_code,
        c.qr_code,
        u.role
    FROM consumers c
    INNER JOIN users u
        ON c.user_code = u.user_code
    ORDER BY c.id DESC
";

$stmt = $pdo->prepare($sql);
$stmt->execute();

$consumers = $stmt->fetchAll();

$activeCount = count(array_filter($consumers, fn ($c) => $c["status"] === "Active"));
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <link rel="icon" href="img/favicon.svg" type="image/svg+xml">
    <link rel="apple-touch-icon" href="img/logo.png">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Consumers - AquaBill</title>

    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="css/app.css">
    <script src="js/admin.js" defer></script>
    <script src="<?= QR_LIBRARY_URL ?>"></script>
    <script src="js/qr.js"></script>
</head>

<body>

<div class="dashboard">

    <?php include "includes/sidebar.php"; ?>

    <!-- MAIN CONTENT -->
    <div class="main">

        <div class="header">
            <div>
                <h1>Consumers</h1>
                <p><?= count($consumers) ?> consumers · <?= $activeCount ?> active</p>
            </div>

            <div class="header-actions">
                <a href="qr-print.php" target="_blank" class="btn btn-secondary">
                    <?= icon("printer") ?> Print All QR Codes
                </a>
                <button type="button" class="btn" data-modal-open="addModal">
                    <?= icon("plus") ?> Add Consumer
                </button>
            </div>
        </div>

        <?php if (isset($_GET["success"])): ?>
            <div class="alert success">
                <?= icon("check-circle") ?>
                <span>
                    Consumer added. User Code: <strong><?= htmlspecialchars($_GET["user_code"] ?? "") ?></strong>
                    · Default password: <strong><?= htmlspecialchars(DEFAULT_CONSUMER_PASSWORD) ?></strong>
                </span>
            </div>
        <?php elseif (isset($_GET["updated"])): ?>
            <div class="alert success"><?= icon("check-circle") ?> Consumer updated.</div>
        <?php elseif (isset($_GET["deleted"])): ?>
            <div class="alert success"><?= icon("check-circle") ?> Consumer deleted.</div>
        <?php elseif (isset($_GET["error"])): ?>
            <div class="alert error"><?= icon("alert") ?> <?= htmlspecialchars($_GET["error"]) ?></div>
        <?php endif; ?>

        <?php reset_password_notice(); ?>

        <!-- TABLE -->
        <div class="table-container">

            <div class="filters">
                <label class="input-icon">
                    <span class="sr-only">Search consumers</span>
                    <?= icon("search") ?>
                    <input type="search" id="search" placeholder="Search name, address, meter or user code">
                </label>

                <select id="statusFilter" aria-label="Filter by status">
                    <option value="">All statuses</option>
                    <option value="Active">Active</option>
                    <option value="Inactive">Inactive</option>
                </select>
            </div>

            <table>

                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Address</th>
                        <th>Meter No.</th>
                        <th>Status</th>
                        <th>User Code</th>
                        <th class="cell-actions"><span class="sr-only">Actions</span></th>
                    </tr>
                </thead>

                <tbody id="consumerRows">

                <?php if (!$consumers): ?>
                    <tr><td colspan="6" class="empty">No consumers yet.</td></tr>
                <?php endif; ?>

                <?php foreach ($consumers as $row): ?>

                    <tr data-status="<?= htmlspecialchars($row['status']) ?>">

                        <td><strong><?= htmlspecialchars($row['name']) ?></strong></td>
                        <td class="muted"><?= htmlspecialchars($row['address']) ?></td>
                        <td><?= htmlspecialchars($row['meter_no']) ?></td>

                        <td>
                            <span class="badge dot <?= strtolower($row['status']) ?>">
                                <?= htmlspecialchars($row['status']) ?>
                            </span>
                        </td>

                        <td class="code"><?= htmlspecialchars($row['user_code']) ?></td>

                        <td class="cell-actions">
                            <button type="button" class="icon-btn show-qr"
                                aria-label="QR code for <?= htmlspecialchars($row['name']) ?>"
                                data-tooltip="QR code"
                                data-qr-value="<?= htmlspecialchars($row['qr_code'] ?: consumer_qr_value($row['user_code'])) ?>"
                                data-user-code="<?= htmlspecialchars($row['user_code']) ?>"
                                data-name="<?= htmlspecialchars($row['name']) ?>"
                                data-address="<?= htmlspecialchars($row['address']) ?>"
                                data-meter="<?= htmlspecialchars($row['meter_no']) ?>">
                                <?= icon("qr") ?>
                            </button>

                            <details class="dropdown">
                                <summary class="icon-btn" aria-label="Actions for <?= htmlspecialchars($row['name']) ?>">
                                    <?= icon("more") ?>
                                </summary>

                                <div class="dropdown-menu">
                                    <button type="button" class="dropdown-item edit-consumer"
                                        data-user-code="<?= htmlspecialchars($row['user_code']) ?>"
                                        data-name="<?= htmlspecialchars($row['name']) ?>"
                                        data-address="<?= htmlspecialchars($row['address']) ?>"
                                        data-meter="<?= htmlspecialchars($row['meter_no']) ?>"
                                        data-status="<?= htmlspecialchars($row['status']) ?>">
                                        <?= icon("pencil") ?> Edit details
                                    </button>

                                    <a href="qr-print.php?user_code=<?= urlencode($row['user_code']) ?>" target="_blank" class="dropdown-item">
                                        <?= icon("printer") ?> Print QR code
                                    </a>

                                    <button type="button" class="dropdown-item reset-password"
                                        data-user-code="<?= htmlspecialchars($row['user_code']) ?>"
                                        data-name="<?= htmlspecialchars($row['name']) ?>">
                                        <?= icon("key") ?> Reset password
                                    </button>

                                    <div class="dropdown-divider"></div>

                                    <form method="POST" action="action/delete_consumer.php"
                                          data-confirm="Delete <?= htmlspecialchars($row['name']) ?>? This also removes their login.">
                                        <input type="hidden" name="user_code" value="<?= htmlspecialchars($row['user_code']) ?>">
                                        <button type="submit" class="dropdown-item danger"><?= icon("trash") ?> Delete consumer</button>
                                    </form>
                                </div>
                            </details>
                        </td>

                    </tr>

                <?php endforeach; ?>

                <tr id="noMatches" hidden><td colspan="6" class="empty">No consumers match your search.</td></tr>

                </tbody>

            </table>

        </div>

    </div>

</div>

<!-- ADD MODAL -->
<div id="addModal" class="modal" role="dialog" aria-modal="true" aria-labelledby="addTitle">
    <div class="modal-card">

        <div class="modal-head">
            <h2 id="addTitle">Add Consumer</h2>
            <button type="button" class="icon-btn" data-modal-close aria-label="Close"><?= icon("x") ?></button>
        </div>

        <form class="form modal-body" method="POST" action="action/add_consumer.php">

            <label>
                Full Name
                <input type="text" name="name" required>
            </label>

            <label>
                Address
                <input type="text" name="address" required>
            </label>

            <label>
                Meter Number
                <input type="text" name="meter_no" required>
            </label>

            <p class="hint">A user code and QR code are generated automatically. Default password: <?= htmlspecialchars(DEFAULT_CONSUMER_PASSWORD) ?></p>

            <div class="modal-foot">
                <button type="button" class="btn btn-secondary" data-modal-close>Cancel</button>
                <button type="submit" class="btn"><?= icon("plus") ?> Add Consumer</button>
            </div>

        </form>

    </div>
</div>

<!-- RESET PASSWORD MODAL -->
<?php reset_password_modal("consumers.php"); ?>

<!-- QR CODE MODAL -->
<div id="qrModal" class="modal" role="dialog" aria-modal="true" aria-labelledby="qrTitle">
    <div class="modal-card">

        <div class="modal-head">
            <h2 id="qrTitle">Consumer QR Code</h2>
            <button type="button" class="icon-btn" data-modal-close aria-label="Close"><?= icon("x") ?></button>
        </div>

        <div class="modal-body qr-card">
            <div class="qr-box" id="qrBox"></div>

            <div class="qr-info">
                <strong id="qrName"></strong>
                <span class="code" id="qrCode"></span>
                <span class="muted" id="qrDetails"></span>
            </div>

            <div class="modal-foot qr-actions">
                <a href="#" target="_blank" class="btn btn-secondary" id="qrPrint"><?= icon("printer") ?> Print</a>
                <button type="button" class="btn" id="qrDownload"><?= icon("download") ?> Download PNG</button>
            </div>
        </div>

    </div>
</div>

<!-- EDIT MODAL -->
<div id="editModal" class="modal" role="dialog" aria-modal="true" aria-labelledby="editTitle">
    <div class="modal-card">

        <div class="modal-head">
            <h2 id="editTitle">Edit Consumer</h2>
            <button type="button" class="icon-btn" data-modal-close aria-label="Close"><?= icon("x") ?></button>
        </div>

        <form class="form modal-body" method="POST" action="action/update_consumer.php">

            <input type="hidden" name="user_code" id="edit_user_code">

            <label>
                Full Name
                <input type="text" name="name" id="edit_name" required>
            </label>

            <label>
                Address
                <input type="text" name="address" id="edit_address" required>
            </label>

            <div class="form-row">
                <label>
                    Meter Number
                    <input type="text" name="meter_no" id="edit_meter_no" required>
                </label>

                <label>
                    Status
                    <select name="status" id="edit_status" required>
                        <option value="Active">Active</option>
                        <option value="Inactive">Inactive</option>
                    </select>
                </label>
            </div>

            <div class="modal-foot">
                <button type="button" class="btn btn-secondary" data-modal-close>Cancel</button>
                <button type="submit" class="btn"><?= icon("save") ?> Save Changes</button>
            </div>

        </form>

    </div>
</div>

<script>

/* Fill the edit modal from the row's data attributes */
document.querySelectorAll(".edit-consumer").forEach((button) => {
    button.addEventListener("click", () => {
        document.getElementById("edit_user_code").value = button.dataset.userCode;
        document.getElementById("edit_name").value = button.dataset.name;
        document.getElementById("edit_address").value = button.dataset.address;
        document.getElementById("edit_meter_no").value = button.dataset.meter;
        document.getElementById("edit_status").value = button.dataset.status;
        openModal("editModal");
    });
});

/* QR code modal */
let currentQr = null;

function showQr(button) {
    currentQr = button.dataset;

    document.getElementById("qrBox").innerHTML = qrSvg(currentQr.qrValue);
    document.getElementById("qrName").textContent = currentQr.name;
    document.getElementById("qrCode").textContent = currentQr.userCode;
    document.getElementById("qrDetails").textContent = currentQr.address + " · Meter no. " + currentQr.meter;
    document.getElementById("qrPrint").href = "qr-print.php?user_code=" + encodeURIComponent(currentQr.userCode);

    openModal("qrModal");
}

document.querySelectorAll(".show-qr").forEach((button) => {
    button.addEventListener("click", () => showQr(button));
});

document.getElementById("qrDownload").addEventListener("click", () => {
    if (currentQr) {
        downloadQrPng(currentQr.qrValue, currentQr.name, "QR-" + currentQr.userCode + ".png");
    }
});

/* A consumer was just added: show their new QR code right away */
<?php if (isset($_GET["success"], $_GET["user_code"])): ?>
window.addEventListener("load", () => {
    const newConsumer = document.querySelector('.show-qr[data-user-code="<?= htmlspecialchars($_GET["user_code"], ENT_QUOTES) ?>"]');
    if (newConsumer) showQr(newConsumer);
});
<?php endif; ?>

/* Search + status filter (client side) */
const search = document.getElementById("search");
const statusFilter = document.getElementById("statusFilter");
const rows = document.querySelectorAll("#consumerRows tr[data-status]");
const noMatches = document.getElementById("noMatches");

function applyFilters() {
    const term = search.value.trim().toLowerCase();
    const status = statusFilter.value;
    let shown = 0;

    rows.forEach((row) => {
        const matches = (!term || row.textContent.toLowerCase().includes(term))
            && (!status || row.dataset.status === status);
        row.hidden = !matches;
        if (matches) shown++;
    });

    noMatches.hidden = shown > 0 || rows.length === 0;
}

search.addEventListener("input", applyFilters);
statusFilter.addEventListener("change", applyFilters);

</script>

</body>
</html>
