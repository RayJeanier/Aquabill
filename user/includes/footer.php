<?php
// These are set in includes/header.php, which every page includes before this file.
/** @var array  $userNav */
/** @var string $currentUserPage */
?>
    </div>

</main>

<!-- BOTTOM TAB BAR (phone) -->
<nav class="tabbar" aria-label="Main">
    <?php foreach ($userNav as $file => $item): ?>
        <a href="<?= $file ?>"
           class="<?= $currentUserPage === $file ? 'active' : '' ?>"
           <?= $currentUserPage === $file ? 'aria-current="page"' : '' ?>>
            <span class="tab-icon"><?= nav_icon($item["icon"]) ?></span>
            <?= $item["label"] ?>
        </a>
    <?php endforeach; ?>
</nav>

</body>
</html>
