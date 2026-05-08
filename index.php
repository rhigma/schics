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

$zellen      = [];
$themenCount = [];
foreach ($alle as $e) {
    $e['themen']                                = schics_themen_in_text((string)($e['uebergreifende_themen'] ?? ''));
    $zellen[$e['fach']][(int)$e['jahrgang']][]  = $e;
    foreach ($e['themen'] as $tid) {
        $themenCount[$tid] = ($themenCount[$tid] ?? 0) + 1;
    }
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
                                            <?php foreach ($eintraege as $e):
                                                $ut = trim((string)($e['uebergreifende_themen'] ?? ''));
                                            ?>
                                                <a class="overview-chip"
                                                   href="detail.php?schic_id=<?= (int)$e['schic_id'] ?>"
                                                   data-themen="<?= htmlspecialchars(implode(' ', $e['themen'])) ?>"
                                                   aria-label="<?= htmlspecialchars($e['thema']) ?>">
                                                    <span class="chip-tooltip" role="tooltip">
                                                        <span class="chip-tooltip__thema"><?= htmlspecialchars($e['thema']) ?></span>
                                                        <?php if ($ut !== ''): ?>
                                                            <span class="chip-tooltip__label">Übergreifende Themen</span>
                                                            <span class="chip-tooltip__inhalt"><?= nl2br(htmlspecialchars($ut)) ?></span>
                                                        <?php endif; ?>
                                                    </span>
                                                </a>
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

            <div class="theme-filter">
                <p class="theme-filter-label">Übergreifende Themen (Rahmenlehrplan Teil B) — anklicken, um SchiCs hervorzuheben, die das Thema aufgreifen.</p>
                <div class="theme-pills" role="group" aria-label="Übergreifende Themen hervorheben">
                    <?php foreach ($themenListe as $thema):
                        $count = $themenCount[$thema['id']] ?? 0;
                    ?>
                        <button type="button"
                                class="theme-pill<?= $count === 0 ? ' theme-pill--zero' : '' ?>"
                                data-thema="<?= htmlspecialchars($thema['id']) ?>"
                                aria-pressed="false">
                            <span class="theme-pill-dot" aria-hidden="true"></span>
                            <span class="theme-pill-label"><?= htmlspecialchars($thema['label']) ?></span>
                            <span class="theme-pill-count"><?= (int)$count ?></span>
                        </button>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>
    </main>

    <script>
    (function () {
        const grid    = document.querySelector('.overview-grid');
        const pills   = document.querySelectorAll('.theme-pill[data-thema]');
        const chips   = document.querySelectorAll('.overview-chip');
        if (!grid) return;

        function positionTooltip(chip) {
            const tt = chip.querySelector('.chip-tooltip');
            if (!tt) return;
            tt.style.left = '';
            tt.style.right = '';
            tt.style.top = '';
            tt.style.bottom = '';
            tt.style.marginTop = '';
            tt.style.marginBottom = '';
            tt.style.transform = '';

            const chipRect = chip.getBoundingClientRect();
            const ttRect   = tt.getBoundingClientRect();
            const margin   = 8;
            const vw       = window.innerWidth;
            const vh       = window.innerHeight;

            const spaceAbove = chipRect.top;
            const spaceBelow = vh - chipRect.bottom;
            if (spaceAbove >= ttRect.height + 12 || spaceAbove >= spaceBelow) {
                tt.style.bottom = '100%';
                tt.style.marginBottom = '6px';
                tt.style.top = 'auto';
            } else {
                tt.style.top = '100%';
                tt.style.marginTop = '6px';
                tt.style.bottom = 'auto';
            }

            const chipCenter = chipRect.left + chipRect.width / 2;
            const halfTt     = ttRect.width / 2;
            if (chipCenter - halfTt < margin) {
                tt.style.left  = (margin - chipRect.left) + 'px';
                tt.style.right = 'auto';
                tt.style.transform = 'none';
            } else if (chipCenter + halfTt > vw - margin) {
                tt.style.left  = 'auto';
                tt.style.right = (margin - (vw - chipRect.right)) + 'px';
                tt.style.transform = 'none';
            } else {
                tt.style.left  = '50%';
                tt.style.right = 'auto';
                tt.style.transform = 'translateX(-50%)';
            }
        }

        chips.forEach(c => {
            if (!c.querySelector('.chip-tooltip')) return;
            c.addEventListener('mouseenter', () => positionTooltip(c));
            c.addEventListener('focus',      () => positionTooltip(c));
        });

        if (!pills.length) return;
        let active = null;

        function apply() {
            if (!active) {
                grid.classList.remove('is-filtering');
                chips.forEach(c => c.classList.remove('is-match', 'is-dim'));
                return;
            }
            grid.classList.add('is-filtering');
            chips.forEach(c => {
                const themen = (c.dataset.themen || '').split(' ').filter(Boolean);
                const match  = themen.indexOf(active) !== -1;
                c.classList.toggle('is-match', match);
                c.classList.toggle('is-dim',   !match);
            });
        }

        pills.forEach(p => {
            p.addEventListener('click', function () {
                const t = this.dataset.thema;
                active = (active === t) ? null : t;
                pills.forEach(other => {
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
