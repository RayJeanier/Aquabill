<?php
require "includes/auth.php";
require_once "includes/icons.php";
require "database/database.php";

// Staff roles the admin can create (must match the user_role enum in the database)
const STAFF_ROLES = [
    "reader"  => "Meter Reader",
    "plumber" => "Plumber",
    "admin"   => "Admin",
];

const ROLE_ICONS = [
    "reader"  => ["gauge",  ""],
    "plumber" => ["wrench", "amber"],
    "admin"   => ["shield", "violet"],
];

$filter = $_GET["role"] ?? "";

if (array_key_exists($filter, STAFF_ROLES)) {
    $stmt = $pdo->prepare("SELECT id, user_code, role FROM users WHERE role = :role ORDER BY id DESC");
    $stmt->execute([":role" => $filter]);
} else {
    $filter = "";
    $stmt = $pdo->query("SELECT id, user_code, role FROM users WHERE role IN ('admin', 'reader', 'plumber') ORDER BY id DESC");
}

$staff = $stmt->fetchAll();

$counts = array_fill_keys(array_keys(STAFF_ROLES), 0);

foreach ($pdo->query("SELECT role, COUNT(*) AS total FROM users WHERE role IN ('admin', 'reader', 'plumber') GROUP BY role") as $row) {
    $counts[$row["role"]] = (int) $row["total"];
}

$currentAdmin = $_SESSION["user"]["user_code"] ?? "";
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Staff - AquaBill</title>

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
                <h1>Staff</h1>
                <p>Add meter readers, plumbers and admins.</p>
            </div>
        </div>

        <?php if (isset($_GET["created"])): ?>
            <div class="alert success"><?= icon("check-circle") ?> 
                <?= htmlspecialchars(STAFF_ROLES[$_GET["role_added"] ?? ""] ?? "Staff") ?> account created.
                User Code: <strong><?= htmlspecialchars($_GET["created"]) ?></strong>
                - give this code and the password you set to the new staff member.
            </div>
        <?php elseif (isset($_GET["error"])): ?>
            <div class="alert error"><?= icon("alert") ?> <?= htmlspecialchars($_GET["error"]) ?></div>
        <?php endif; ?>

        <div class="stats">
            <?php foreach (STAFF_ROLES as $role => $label): ?>
                <div class="stat-card">
                    <span class="stat-icon <?= ROLE_ICONS[$role][1] ?>"><?= icon(ROLE_ICONS[$role][0]) ?></span>
                    <div>
                        <h5><?= $label ?>s</h5>
                        <p><?= $counts[$role] ?></p>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="grid-2">

            <!-- STAFF LIST -->
            <div class="table-container">

                <form class="filters compact" method="GET">
                    <select name="role" onchange="this.form.submit()" aria-label="Filter by role">
                        <option value="">All Staff</option>
                        <?php foreach (STAFF_ROLES as $role => $label): ?>
                            <option value="<?= $role ?>" <?= $filter === $role ? "selected" : "" ?>><?= $label ?>s</option>
                        <?php endforeach; ?>
                    </select>
                </form>

                <table>
                    <thead>
                        <tr>
                            <th>User Code</th>
                            <th>Role</th>
                        </tr>
                    </thead>

                    <tbody>
                    <?php if (!$staff): ?>
                        <tr><td colspan="2" class="empty">No staff found.</td></tr>
                    <?php endif; ?>

                    <?php foreach ($staff as $s): ?>
                        <tr>
                            <td class="code">
                                <?= htmlspecialchars($s["user_code"]) ?>
                                <?php if ($s["user_code"] === $currentAdmin): ?>
                                    <span class="hint">(you)</span>
                                <?php endif; ?>
                            </td>
                            <td><span class="badge dot <?= htmlspecialchars($s["role"]) ?>"><?= STAFF_ROLES[$s["role"]] ?? htmlspecialchars($s["role"]) ?></span></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>

            </div>

            <!-- ADD STAFF -->
            <div class="panel">

                <h2>Add Staff</h2>

                <form class="form" method="POST" action="action/add_staff.php">

                    <label>
                        Role
                        <select name="role" required>
                            <?php foreach (STAFF_ROLES as $role => $label): ?>
                                <option value="<?= $role ?>"><?= $label ?></option>
                            <?php endforeach; ?>
                        </select>
                    </label>

                    <label>
                        Password
                        <input type="password" name="password" minlength="6" required autocomplete="new-password">
                        <span class="hint">At least 6 characters.</span>
                    </label>

                    <label>
                        Confirm Password
                        <input type="password" name="confirm_password" minlength="6" required autocomplete="new-password">
                    </label>

                    <button type="submit" class="btn"><?= icon("plus") ?> Create Account</button>

                    <p class="hint">A user code is generated automatically (e.g. SVOB-READ-XXXXXX).</p>

                </form>

            </div>

        </div>

    </div>

</div>

</body>
</html>
