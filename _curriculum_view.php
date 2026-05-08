<?php
/**
 * Curriculum-Sheet als Leseansicht. $values muss alle Felder enthalten.
 * Für die Bearbeitung siehe _curriculum_form.php.
 */
require_once __DIR__ . '/helpers.php';
$cells = schics_curriculum_cells();
?>
<div class="curriculum-sheet">
    <div class="curriculum-sheet__head">
        <div><label>Fach:</label><?= htmlspecialchars((string)($values['fach'] ?? '')) ?></div>
        <div><label>Jahrgang:</label><?= htmlspecialchars((string)($values['jahrgang'] ?? '')) ?></div>
        <div class="curriculum-sheet__head-thema"><label>Thema:</label><?= htmlspecialchars((string)($values['thema'] ?? '')) ?></div>
        <div><label>Umfang:</label><?= htmlspecialchars((string)($values['umfang'] ?? '')) ?></div>
        <div><label>Stand:</label><?= htmlspecialchars((string)($values['stand'] ?? '')) ?></div>
    </div>
    <?php foreach ($cells as [$pos, $col, $title, $field]): ?>
        <div class="curriculum-cell curriculum-cell--<?= $col ?> cell-<?= $pos ?>">
            <h3 class="curriculum-cell__title"><?= htmlspecialchars($title) ?></h3>
            <p class="curriculum-cell__body"><?= htmlspecialchars((string)($values[$field] ?? '')) ?></p>
            <span class="curriculum-cell__tag"><?= strtoupper($col) ?></span>
        </div>
    <?php endforeach; ?>
</div>
