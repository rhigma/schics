<?php
require_once __DIR__ . '/auth.php';
$schoolName   = schics_school_name();
$currentLevel = schics_current_level();
$canEdit      = $currentLevel >= SCHICS_LEVEL_EDIT;
$canAdmin     = $currentLevel >= SCHICS_LEVEL_ADMIN;
$currentPage  = basename($_SERVER['PHP_SELF'] ?? '');
$active = fn(string $page) => $currentPage === $page ? ' class="active"' : '';
?>
<header class="topbar">
    <div class="topbar-inner">
        <a class="brand" href="index.php">
            <span class="brand-mark">📚</span>
            <span class="brand-text"><?= htmlspecialchars($schoolName) ?></span>
        </a>
        <ul class="topnav">
            <li><a href="index.php"<?= $active('index.php') ?>>Startseite</a></li>
            <li><a href="suchen.php"<?= $active('suchen.php') ?>>Suchen</a></li>
            <li><a href="dashboard.php"<?= $active('dashboard.php') ?>>Quer-/Längsschnitte</a></li>
            <?php if ($canEdit): ?>
                <li><a href="admin.php"<?= $active('admin.php') ?>>Neuer Eintrag</a></li>
                <li><a href="sortieren.php"<?= $active('sortieren.php') ?>>Sortieren</a></li>
            <?php endif; ?>
            <?php if ($canAdmin): ?>
                <li><a href="einstellungen.php"<?= $active('einstellungen.php') ?>>Einstellungen</a></li>
            <?php endif; ?>
        </ul>
        <div class="topbar-right">
            <?php if ($currentLevel > SCHICS_LEVEL_NONE): ?>
                <span class="level-badge"><?= htmlspecialchars(schics_level_label($currentLevel)) ?></span>
                <a class="topbar-link" href="logout.php">Abmelden</a>
            <?php else: ?>
                <a class="topbar-link" href="login.php">Anmelden</a>
            <?php endif; ?>
        </div>
    </div>
</header>
