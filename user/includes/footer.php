    </div>

    <a href="action/logout.php" class="btn-outline mobile-only">Log Out</a>

</main>

<?php
// These are set in includes/header.php, which every page includes before this file.
/** @var array  $userNav */
/** @var array  $navIcons */
/** @var string $currentUserPage */
?>

<?php if (count($userNav) > 1): ?>
<!-- BOTTOM TAB BAR (phone, only when there is more than one page) -->
<nav class="tabbar">
    <?php foreach ($userNav as $file => $item): ?>
        <a href="<?= $file ?>" class="<?= $currentUserPage === $file ? 'active' : '' ?>">
            <svg viewBox="0 0 24 24" aria-hidden="true"><?= $navIcons[$item["icon"]] ?? $navIcons["home"] ?></svg>
            <?= $item["label"] ?>
        </a>
    <?php endforeach; ?>
</nav>
<?php endif; ?>

</body>
</html>
