<?php
require_once __DIR__ . '/auth.php';
schics_require_level(SCHICS_LEVEL_EDIT);

$pdo      = schics_db();
$fach     = $_GET['fach'] ?? '';
$jahrgang = $_GET['jahrgang'] ?? '';
$fächer   = schics_faecher();
[$jgMin, $jgMax] = schics_jahrgang_range();
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Reihenfolge sortieren – <?= htmlspecialchars(schics_school_name()) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/style.css" rel="stylesheet">
</head>
<body>
    <?php include __DIR__ . '/nav.php'; ?>
    <main class="container-app">
        <div class="page-header">
            <div>
                <h1>Reihenfolge bearbeiten</h1>
                <p class="text-muted" style="margin:0;">Themen per Drag-and-Drop in die gewünschte Reihenfolge ziehen.</p>
            </div>
        </div>

        <section class="section">
            <form method="get" class="row g-3">
                <div class="col-md-5">
                    <label for="fach" class="form-label">Fach</label>
                    <select name="fach" id="fach" class="form-select">
                        <option value="">– bitte wählen –</option>
                        <?php foreach ($fächer as $f): ?>
                            <option value="<?= htmlspecialchars($f) ?>" <?= $fach === $f ? 'selected' : '' ?>><?= htmlspecialchars($f) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="jahrgang" class="form-label">Jahrgang</label>
                    <select name="jahrgang" id="jahrgang" class="form-select">
                        <option value="">–</option>
                        <?php for ($i = $jgMin; $i <= $jgMax; $i++): ?>
                            <option value="<?= $i ?>" <?= (string)$jahrgang === (string)$i ? 'selected' : '' ?>><?= $i ?></option>
                        <?php endfor; ?>
                    </select>
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <button class="btn btn-primary w-100">Anzeigen</button>
                </div>
            </form>
        </section>

        <?php if ($fach && $jahrgang !== ''):
            $stmt = $pdo->prepare('
                SELECT c1.schic_id, c1.thema, c1.reihenfolge
                FROM curricula c1
                INNER JOIN (
                    SELECT schic_id, MAX(id) AS max_id
                    FROM curricula
                    GROUP BY schic_id
                ) c2 ON c1.id = c2.max_id
                WHERE c1.fach = :fach AND c1.jahrgang = :jahrgang
                ORDER BY c1.reihenfolge ASC, c1.thema ASC
            ');
            $stmt->execute([':fach' => $fach, ':jahrgang' => (int)$jahrgang]);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        ?>
            <section class="section">
                <h2 class="section-title"><?= htmlspecialchars($fach) ?> · Jahrgang <?= htmlspecialchars((string)$jahrgang) ?></h2>
                <?php if ($rows): ?>
                    <ul id="sortable">
                        <?php foreach ($rows as $row): ?>
                            <li class="sortable-item" data-schic-id="<?= (int)$row['schic_id'] ?>"><?= htmlspecialchars($row['thema']) ?></li>
                        <?php endforeach; ?>
                    </ul>
                    <div style="margin-top:1rem; display:flex; align-items:center; gap:1rem;">
                        <button id="saveOrder" class="btn btn-primary">Reihenfolge speichern</button>
                        <span id="saveStatus" class="text-muted"></span>
                    </div>
                <?php else: ?>
                    <div class="alert alert-warning">Keine Einträge für diese Auswahl.</div>
                <?php endif; ?>
            </section>
        <?php endif; ?>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
    <script>
        const list = document.getElementById('sortable');
        if (list) {
            new Sortable(list, { animation: 150 });
            document.getElementById('saveOrder').addEventListener('click', () => {
                const order = [...list.querySelectorAll('li')].map((li, index) => ({
                    schic_id: parseInt(li.dataset.schicId, 10),
                    reihenfolge: index + 1
                }));
                const status = document.getElementById('saveStatus');
                status.textContent = 'Speichere…';
                fetch('update_reihenfolge.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                    body: JSON.stringify(order)
                }).then(res => {
                    status.textContent = res.ok ? 'Gespeichert.' : 'Fehler beim Speichern (Status ' + res.status + ').';
                }).catch(() => { status.textContent = 'Netzwerkfehler.'; });
            });
        }
    </script>
</body>
</html>
