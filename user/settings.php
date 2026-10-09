<?php
require "includes/auth.php";
require "../database/database.php";
require_once "includes/account.php";

$userCode = $_SESSION["consumer"]["user_code"];

$stmt = $pdo->prepare("SELECT name, address, meter_no, contact_number, email FROM consumers WHERE user_code = :user_code");
$stmt->execute([":user_code" => $userCode]);
$consumer = $stmt->fetch() ?: ["name" => "", "address" => "", "meter_no" => "", "contact_number" => null, "email" => null];

$stmt = $pdo->prepare("SELECT username FROM users WHERE user_code = :user_code");
$stmt->execute([":user_code" => $userCode]);
$username = $stmt->fetchColumn() ?: "";

$pageTitle    = "Settings";
$pageHeading  = "Settings";
$pageSubtitle = "Your account and password";

include "includes/header.php";
?>

<div class="content-grid">

    <div class="content-col">

        <?php if (isset($_GET["changed"])): ?>
            <div class="alert success">Your password has been changed. Use the new password next time you log in.</div>
        <?php elseif (isset($_GET["username_saved"])): ?>
            <div class="alert success">Username saved. You can now log in with <strong><?= htmlspecialchars($username) ?></strong> or your User ID.</div>
        <?php elseif (isset($_GET["username_removed"])): ?>
            <div class="alert success">Username removed. Log in with your User ID from now on.</div>
        <?php elseif (isset($_GET["contact_saved"])): ?>
            <div class="alert success">Contact information saved. The water office can now reach you there.</div>
        <?php elseif (isset($_GET["error"])): ?>
            <div class="alert error"><?= htmlspecialchars($_GET["error"]) ?></div>
        <?php endif; ?>

        <!-- CONTACT INFORMATION -->
        <section class="card" aria-labelledby="contactTitle">
            <div class="settings-title">
                <span class="settings-icon teal-tile"><?= nav_icon("phone") ?></span>
                <div>
                    <h2 class="section-title" id="contactTitle">Contact information</h2>
                    <p class="card-sub">
                        <?php if (!$consumer["contact_number"] && !$consumer["email"]): ?>
                            Add a number or email so the water office can reach you about bills and repairs.
                        <?php else: ?>
                            The water office uses this to reach you about bills and repairs.
                        <?php endif; ?>
                    </p>
                </div>
            </div>

            <form class="form" method="POST" action="action/update_contact.php">

                <label class="field">
                    <span>Mobile number <span class="optional">(optional)</span></span>
                    <input type="tel" name="contact_number" inputmode="tel" autocomplete="tel"
                           value="<?= htmlspecialchars($consumer["contact_number"] ?? "") ?>"
                           placeholder="0917 123 4567">
                </label>

                <label class="field">
                    <span>Email <span class="optional">(optional)</span></span>
                    <input type="email" name="email" autocomplete="email" autocapitalize="none" spellcheck="false"
                           value="<?= htmlspecialchars($consumer["email"] ?? "") ?>"
                           placeholder="name@example.com">
                    <small class="field-hint">Leave a box empty to remove it.</small>
                </label>

                <button type="submit" class="btn-primary" style="margin-top:4px">
                    Save Contact Information
                </button>

            </form>
        </section>

        <!-- USERNAME -->
        <section class="card" aria-labelledby="usernameTitle">
            <div class="settings-title">
                <span class="settings-icon"><?= nav_icon("user") ?></span>
                <div>
                    <h2 class="section-title" id="usernameTitle">Username</h2>
                    <p class="card-sub">
                        <?php if ($username !== ""): ?>
                            You can log in with <strong><?= htmlspecialchars($username) ?></strong> or your User ID.
                        <?php else: ?>
                            Set a username that's easier to remember than your User ID. Your User ID will still work.
                        <?php endif; ?>
                    </p>
                </div>
            </div>

            <form class="form" method="POST" action="action/change_username.php">

                <label class="field">
                    <span><?= $username !== "" ? "New username" : "Username" ?></span>
                    <input type="text" name="username" required
                           minlength="4" maxlength="20" pattern="[A-Za-z0-9._]{4,20}"
                           value="<?= htmlspecialchars($username) ?>"
                           autocomplete="username" autocapitalize="none" spellcheck="false"
                           title="4-20 letters, numbers, dots or underscores">
                    <small class="field-hint">4–20 letters, numbers, dots (.) or underscores (_). No spaces.</small>
                </label>

                <label class="field">
                    <span>Current password</span>
                    <input type="password" name="current_password" class="pw" required autocomplete="current-password">
                    <small class="field-hint">Needed to confirm it's you.</small>
                </label>

                <button type="submit" name="action" value="save" class="btn-primary" style="margin-top:4px">
                    <?= $username !== "" ? "Change Username" : "Set Username" ?>
                </button>

                <?php if ($username !== ""): ?>
                    <button type="submit" name="action" value="remove" class="btn-link-danger" formnovalidate
                            onclick="return confirm('Remove your username? You will log in with your User ID only.')">
                        Remove username
                    </button>
                <?php endif; ?>

            </form>
        </section>

        <!-- CHANGE PASSWORD -->
        <section class="card" aria-labelledby="passwordTitle">
            <div class="settings-title">
                <span class="settings-icon"><?= nav_icon("lock") ?></span>
                <div>
                    <h2 class="section-title" id="passwordTitle">Change password</h2>
                    <p class="card-sub">Use at least 6 characters. Don't share your password with anyone.</p>
                </div>
            </div>

            <form class="form" method="POST" action="action/change_password.php">

                <label class="field">
                    <span>Current password</span>
                    <input type="password" name="current_password" class="pw" required autocomplete="current-password">
                </label>

                <label class="field">
                    <span>New password</span>
                    <input type="password" name="new_password" class="pw" minlength="6" required autocomplete="new-password">
                </label>

                <label class="field">
                    <span>Confirm new password</span>
                    <input type="password" name="confirm_password" class="pw" minlength="6" required autocomplete="new-password">
                </label>

                <label class="check-row">
                    <input type="checkbox" id="showPasswords">
                    <span>Show passwords</span>
                </label>

                <button type="submit" class="btn-primary" style="margin-top:4px">
                    Change Password
                </button>

            </form>
        </section>

    </div>

    <div class="content-col">

        <!-- ACCOUNT -->
        <section class="card" aria-labelledby="accountTitle">
            <h2 class="sr-only" id="accountTitle">Account details</h2>

            <!-- profile header -->
            <div class="profile-head">
                <span class="profile-avatar" aria-hidden="true"><?= htmlspecialchars($initial) ?></span>
                <div>
                    <strong><?= htmlspecialchars($consumer["name"]) ?></strong>
                    <span><?= htmlspecialchars($userCode) ?></span>
                </div>
            </div>

            <div class="list-row">
                <span class="row-main"><?= row_icon("id", "blue") ?><span class="label">User ID</span></span>
                <strong><?= htmlspecialchars($userCode) ?></strong>
            </div>
            <div class="list-row">
                <span class="row-main"><?= row_icon("at", "violet") ?><span class="label">Username</span></span>
                <strong><?= $username !== "" ? htmlspecialchars($username) : "Not set" ?></strong>
            </div>
            <div class="list-row">
                <span class="row-main"><?= row_icon("pin", "teal") ?><span class="label">Address</span></span>
                <strong><?= htmlspecialchars($consumer["address"]) ?></strong>
            </div>
            <div class="list-row">
                <span class="row-main"><?= row_icon("gauge", "amber") ?><span class="label">Meter No.</span></span>
                <strong><?= htmlspecialchars($consumer["meter_no"]) ?></strong>
            </div>
            <div class="list-row">
                <span class="row-main"><?= row_icon("phone", "green") ?><span class="label">Mobile</span></span>
                <strong><?= $consumer["contact_number"] ? htmlspecialchars($consumer["contact_number"]) : "Not set" ?></strong>
            </div>
            <div class="list-row">
                <span class="row-main"><?= row_icon("mail", "blue") ?><span class="label">Email</span></span>
                <strong class="break"><?= $consumer["email"] ? htmlspecialchars($consumer["email"]) : "Not set" ?></strong>
            </div>

            <p class="note">To change your name, address or meter number, please visit the San Vicente water office.</p>
        </section>

        <a href="action/logout.php" class="btn-outline">
            <?= nav_icon("logout") ?> Log Out
        </a>

    </div>

</div>

<script>
document.getElementById("showPasswords").addEventListener("change", (e) => {
    document.querySelectorAll(".pw").forEach((input) => {
        input.type = e.target.checked ? "text" : "password";
    });
});
</script>

<?php include "includes/footer.php"; ?>
