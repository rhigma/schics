<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/helpers.php';
schics_require_level(SCHICS_LEVEL_READ);

$pdo             = schics_db();
$fächer          = schics_faecher();
[$jgMin, $jgMax] = schics_jahrgang_range();
$jahrgaenge      = range($jgMin, $jgMax);

// Aktuelle Versionen aller SchiCs laden — eine Zeile pro schic_id (höchste id).
$stmt = $pdo->query('
    SELECT c1.schic_id, c1.fach, c1.jahrgang, c1.thema, c1."übergreifende_themen" AS uebergreifende_themen
    FROM curricula c1
    INNER JOIN (
        SELECT schic_id, MAX(id) AS max_id
        FROM curricula
        GROUP BY schic_id
    ) c2 ON c1.id = c2.max_id
    ORDER BY c1.reihenfolge ASC, c1.thema ASC
');
$alle = $stmt->fetchAll(PDO::FETCH_ASSOC);

$zellen = [];
foreach ($alle as $e) {
    $e['themen']                                = schics_themen_in_text((string)($e['uebergreifende_themen'] ?? ''));
    $zellen[$e['fach']][(int)$e['jahrgang']][]  = $e;
}
$themenListe = schics_uebergreifende_themen();
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Übersicht – <?= htmlspecialchars(schics_school_name()) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/style.css" rel="stylesheet">
</head>
<body>
    <?php include __DIR__ . '/nav.php'; ?>
    <main class="container-app">
        <div class="page-header">
            <h1>Schulinterne Curricula</h1>
        </div>

        <section class="section">
            <div class="overview-scroll">
            <table class="overview-grid">
                <thead>
                    <tr>
                        <th class="overview-corner">Fach \ Jg.</th>
                        <?php foreach ($jahrgaenge as $jg): ?>
                            <th class="overview-jg"><?= (int)$jg ?></th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($fächer as $fach): ?>
                        <tr>
                            <th class="overview-fach"><a href="suchen.php?fach=<?= urlencode($fach) ?>"><?= htmlspecialchars($fach) ?></a></th>
                            <?php foreach ($jahrgaenge as $jg):
                                $eintraege = $zellen[$fach][(int)$jg] ?? [];
                            ?>
                                <td class="overview-cell">
                                    <?php if (!$eintraege): ?>
                                        <span class="overview-empty" aria-hidden="true"></span>
                                    <?php else: ?>
                                        <div class="overview-chips">
                                            <?php foreach ($eintraege as $e): ?>
                                                <a class="overview-chip"
                                                   href="detail.php?schic_id=<?= (int)$e['schic_id'] ?>"
                                                   data-tooltip="<?= htmlspecialchars($e['thema']) ?>"
                                                   data-themen="<?= htmlspecialchars(implode(' ', $e['themen'])) ?>"
                                                   aria-label="<?= htmlspecialchars($e['thema']) ?>"></a>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endif; ?>
                                </td>
                            <?php endforeach; ?>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            </div>

            <div class="theme-filter" role="group" aria-label="Übergreifende Themen hervorheben">
                <span class="theme-filter-label">Übergreifende Themen hervorheben:</span>
                <?php foreach ($themenListe as $thema): ?>
                    <button type="button" class="theme-toggle" data-thema="<?= htmlspecialchars($thema['id']) ?>" aria-pressed="false"><?= htmlspecialchars($thema['label']) ?></button>
                <?php endforeach; ?>
            </div>
        </section>
    </main>

    <script>
    (function () {
        const grid    = document.querySelector('.overview-grid');
        const buttons = document.querySelectorAll('.theme-toggle[data-thema]');
        const chips   = document.querySelectorAll('.overview-chip');
        let active    = null;

        function apply() {
            if (!active) {
                grid.classList.remove('is-filtering');
                chips.forEach(c => c.classList.remove('is-match', 'is-dim'));
                return;
            }
            grid.classList.add('is-filtering');
            chips.forEach(c => {
                const themen = (c.dataset.themen || '').split(' ').filter(Boolean);
                const match  = themen.includes(active);
                c.classList.toggle('is-match', match);
                c.classList.toggle('is-dim',   !match);
            });
        }

        buttons.forEach(b => {
            b.addEventListener('click', () => {
                const t = b.dataset.thema;
                if (active === t) {
                    active = null;
                } else {
                    active = t;
                }
                buttons.forEach(other => {
                    const on = other.dataset.thema === active;
                    other.classList.toggle('is-active', on);
                    other.setAttribute('aria-pressed', on ? 'true' : 'false');
                });
                apply();
            });
        });
    })();
    </script>
</body>
</html>
