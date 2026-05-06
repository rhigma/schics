<?php
require_once __DIR__ . '/auth.php';
schics_require_level(SCHICS_LEVEL_READ);
?>
<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Quer-/Längsschnitte – <?= htmlspecialchars(schics_school_name()) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="assets/style.css" rel="stylesheet">
</head>
<body>
    <?php include __DIR__ . '/nav.php'; ?>
    <main class="container-app">
        <div class="page-header">
            <div>
                <h1>Quer- und Längsschnitte</h1>
                <p class="text-muted" style="margin:0;">Inhalte über alle SchiCs hinweg auswerten — etwa um zu sehen, in welchen Fächern und Jahrgängen Sprachbildung verankert ist.</p>
            </div>
        </div>

        <section class="section">
            <form class="row g-3" onsubmit="return false;">
                <div class="col-md-3">
                    <label class="form-label">Datenfeld</label>
                    <select name="feld" class="form-select" required>
                        <option value="">– bitte wählen –</option>
                        <option value="sprachbildung">Sprachbildung</option>
                        <option value="medienbildung">Medienbildung</option>
                        <option value="methoden">Methoden und Arbeitstechniken</option>
                        <option value="kompetenzen">Kompetenzen und Konkretisierung</option>
                        <option value="kooperationen">Kooperationen / Lernorte</option>
                        <option value="übergreifende_themen">Übergreifende Themen</option>
                        <option value="leistungsbewertung">Lernberatung &amp; Leistungsbewertung</option>
                        <option value="fächerverbindung">Fächerverbindung</option>
                        <option value="heterogenität">Heterogenität / Inklusion</option>
                        <option value="schulprofil">Schulprofil / Schwerpunktsetzung</option>
                        <option value="lebensweltbezug">Lebensweltbezug</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Enthält</label>
                    <input type="text" name="suchwert" class="form-control" placeholder="optional">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Fach</label>
                    <input type="text" name="fach" class="form-control" placeholder="optional">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Jahrgang</label>
                    <input type="number" name="jahrgang" min="1" max="10" class="form-control" placeholder="optional">
                </div>
            </form>
        </section>

        <div id="ergebnisse"></div>
    </main>

    <script>
    function laden() {
        const feld     = document.querySelector('[name="feld"]').value;
        const suchwert = document.querySelector('[name="suchwert"]').value;
        const fach     = document.querySelector('[name="fach"]').value;
        const jahrgang = document.querySelector('[name="jahrgang"]').value;
        const erg      = document.getElementById('ergebnisse');
        if (!feld) { erg.innerHTML = ''; return; }
        const params = new URLSearchParams({ feld, suchwert, fach, jahrgang });
        fetch('dashboard_data.php?' + params.toString())
            .then(res => res.text())
            .then(html => { erg.innerHTML = html; })
            .catch(() => { erg.innerHTML = "<div class='alert alert-danger'>Fehler beim Laden.</div>"; });
    }
    document.addEventListener('DOMContentLoaded', () => {
        document.querySelectorAll('select, input').forEach(el => {
            el.addEventListener('input', laden);
            el.addEventListener('change', laden);
        });
    });
    </script>
</body>
</html>
