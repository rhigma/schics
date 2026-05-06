<?php
require_once __DIR__ . '/auth.php';
schics_require_level(SCHICS_LEVEL_READ);
$fächer = schics_faecher();
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Curriculum-Suche – <?= htmlspecialchars(schics_school_name()) ?></title>
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
            <form id="sucheForm" class="row g-3">
                <div class="col-md-4">
                    <label for="fach" class="form-label">Fach</label>
                    <select name="fach" id="fach" class="form-select">
                        <option value="">Alle Fächer</option>
                        <?php foreach ($fächer as $fach):
                            $selected = ($fach === ($_GET['fach'] ?? '')) ? 'selected' : '';
                        ?>
                            <option value="<?= htmlspecialchars($fach) ?>" <?= $selected ?>><?= htmlspecialchars($fach) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="jahrgang" class="form-label">Jahrgang</label>
                    <input type="text" name="jahrgang" id="jahrgang" class="form-control"
                           value="<?= htmlspecialchars($_GET['jahrgang'] ?? '') ?>"
                           placeholder="z. B. 1-4 oder 5,6"
                           title="Zulässig: 1-4, 5,6, >=3, <=6, 4">
                </div>
                <div class="col-md-5">
                    <label for="suchbegriff" class="form-label">Suchbegriff</label>
                    <input type="text" name="suchbegriff" id="suchbegriff" class="form-control"
                           value="<?= htmlspecialchars($_GET['suchbegriff'] ?? '') ?>"
                           placeholder="z. B. Demokratie">
                </div>
            </form>
        </section>

        <div id="ergebnisse"></div>
    </main>

    <script>
    function sucheStarten() {
        const form = document.getElementById('sucheForm');
        const formData = new FormData(form);
        fetch('ajax_suche.php', { method: 'POST', body: formData })
            .then(res => res.text())
            .then(html => { document.getElementById('ergebnisse').innerHTML = html; });
    }
    document.querySelectorAll('#sucheForm input, #sucheForm select').forEach(el => {
        el.addEventListener('input', () => { sucheStarten(); updateURL(); });
    });
    sucheStarten();
    function updateURL() {
        const params = new URLSearchParams(new FormData(document.getElementById('sucheForm')));
        history.replaceState(null, '', '?' + params.toString());
    }
    </script>
</body>
</html>
