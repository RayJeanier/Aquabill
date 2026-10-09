<?php

// Admin "Reset password" (used when a consumer or staff member forgets theirs).
//
//   reset_password_notice()          - shows the new temporary password once, after a reset
//   reset_password_modal("staff.php") - the popup form; open it with a button like:
//       <button class="reset-password" data-user-code="..." data-name="...">

require_once __DIR__ . "/icons.php";
require_once __DIR__ . "/passwords.php";

function reset_password_notice(): void
{
    if (empty($_SESSION["password_reset"])) {
        return;
    }

    $reset = $_SESSION["password_reset"];
    unset($_SESSION["password_reset"]);   // show the temporary password only once
    ?>
    <div class="alert success reset-notice">
        <?= icon("key") ?>
        <span>
            Password reset for <strong><?= htmlspecialchars($reset["name"]) ?></strong>
            (<?= htmlspecialchars($reset["user_code"]) ?>).
            New password:
            <code class="temp-password" id="tempPassword"><?= htmlspecialchars($reset["password"]) ?></code>
            <button type="button" class="icon-btn" aria-label="Copy password" data-tooltip="Copy"
                    onclick="navigator.clipboard.writeText(document.getElementById('tempPassword').textContent); this.dataset.tooltip='Copied!'">
                <?= icon("copy") ?>
            </button>
            <br><small>Give it to them now - it won't be shown again. They can change it in Settings after logging in.</small>
        </span>
    </div>
    <?php
}

function reset_password_modal(string $returnPage): void
{
    ?>
    <div id="resetModal" class="modal" role="dialog" aria-modal="true" aria-labelledby="resetTitle">
        <div class="modal-card">

            <div class="modal-head">
                <h2 id="resetTitle">Reset Password</h2>
                <button type="button" class="icon-btn" data-modal-close aria-label="Close"><?= icon("x") ?></button>
            </div>

            <form class="form modal-body" method="POST" action="action/reset_password.php"
                  data-confirm="Reset this password? The old password will stop working immediately.">

                <input type="hidden" name="user_code" id="reset_user_code">
                <input type="hidden" name="return" value="<?= htmlspecialchars($returnPage) ?>">

                <p class="hint" id="reset_for"></p>

                <label>
                    New Password
                    <div class="input-action">
                        <input type="text" name="new_password" id="reset_password" required
                               minlength="<?= PASSWORD_MIN_LENGTH ?>" autocomplete="off" spellcheck="false">
                        <button type="button" class="icon-btn" id="reset_generate" aria-label="Generate a new password" data-tooltip="Generate new">
                            <?= icon("refresh") ?>
                        </button>
                    </div>
                    <span class="hint">A temporary password is filled in for you. You can type your own (at least <?= PASSWORD_MIN_LENGTH ?> characters).</span>
                </label>

                <div class="modal-foot">
                    <button type="button" class="btn btn-secondary" data-modal-close>Cancel</button>
                    <button type="submit" class="btn"><?= icon("key") ?> Reset Password</button>
                </div>

            </form>

        </div>
    </div>

    <script>
    (() => {
        const chars = "abcdefghjkmnpqrstuvwxyz23456789";
        const pick = () => chars[crypto.getRandomValues(new Uint32Array(1))[0] % chars.length];
        const tempPassword = () => Array.from({ length: 4 }, pick).join("") + "-" + Array.from({ length: 4 }, pick).join("");

        const field = document.getElementById("reset_password");

        document.getElementById("reset_generate").addEventListener("click", () => {
            field.value = tempPassword();
        });

        document.querySelectorAll(".reset-password").forEach((button) => {
            button.addEventListener("click", () => {
                document.getElementById("reset_user_code").value = button.dataset.userCode;
                document.getElementById("reset_for").textContent =
                    "Set a new password for " + button.dataset.name + " (" + button.dataset.userCode + ").";
                field.value = tempPassword();
                openModal("resetModal");
            });
        });
    })();
    </script>
    <?php
}
