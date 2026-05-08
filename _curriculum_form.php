<?php
/**
 * Curriculum-Sheet als Formular. Caller wrappt in <form> und stellt
 * $values bereit (assoziativ, Schlüssel = curricula-Spaltennamen).
 */
[$jgMin, $jgMax] = schics_jahrgang_range();
$val = static function (string $key, string $default = '') use ($values): string {
    return htmlspecialchars((string)($values[$key] ?? $default));
};
require_once __DIR__ . '/helpers.php';
$cells = schics_curriculum_cells();
?>
<div class="curriculum-sheet curriculum-sheet--edit">
    <div class="curriculum-sheet__head">
        <div>
            <label for="cs-fach">Fach:</label>
            <select id="cs-fach" name="fach" required>
                <option value="">– wählen –</option>
                <?php foreach (schics_faecher() as $fach): ?>
                    <option value="<?= htmlspecialchars($fach) ?>" <?= ($values['fach'] ?? '') === $fach ? 'selected' : '' ?>><?= htmlspecialchars($fach) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label for="cs-jg">Jahrgang:</label>
            <input id="cs-jg" type="number" name="jahrgang" min="<?= (int)$jgMin ?>" max="<?= (int)$jgMax ?>" value="<?= $val('jahrgang') ?>" required>
        </div>
        <div class="curriculum-sheet__head-thema">
            <label for="cs-thema">Thema:</label>
            <input id="cs-thema" type="text" name="thema" value="<?= $val('thema') ?>" required>
        </div>
        <div>
            <label for="cs-umfang">Umfang:</label>
            <input id="cs-umfang" type="text" name="umfang" value="<?= $val('umfang') ?>">
        </div>
        <div>
            <label for="cs-stand">Stand:</label>
            <input id="cs-stand" type="date" name="stand" value="<?= $val('stand', date('Y-m-d')) ?>" required>
        </div>
    </div>

    <?php foreach ($cells as [$pos, $col, $title, $field]): ?>
        <div class="curriculum-cell curriculum-cell--<?= $col ?> curriculum-cell--edit cell-<?= $pos ?>">
            <h3 class="curriculum-cell__title"><label for="cs-<?= $pos ?>"><?= htmlspecialchars($title) ?></label></h3>
            <textarea id="cs-<?= $pos ?>" class="curriculum-cell__field" name="<?= $field ?>" data-shrink><?= $val($field) ?></textarea>
            <span class="curriculum-cell__tag"><?= strtoupper($col) ?></span>
        </div>
    <?php endforeach; ?>
</div>
