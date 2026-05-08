<?php
/**
 * Eingebettete Rahmenlehrplan-Vorschau für die Edit-Formulare.
 * Teil A und B werden immer angezeigt. Teil C richtet sich nach $fachAktuell
 * und wird per JS bei Fach-Wechsel im darüberliegenden Formular ausgetauscht.
 */
require_once __DIR__ . '/helpers.php';

$rlp     = schics_rlp_files();
$mapC    = $rlp['teil_c'];
$initial = $fachAktuell ?? '';
$initialC = $mapC[$initial] ?? '';
?>
<section class="section rlp-panel" aria-label="Rahmenlehrpläne">
    <h2 class="section-title">Rahmenlehrplan</h2>
    <p class="text-muted" style="margin-top:-.5rem;">
        Teil A und B sind allgemein. Teil C wechselt mit dem oben gewählten Fach.
    </p>
    <div class="rlp-grid">
        <div class="rlp-card">
            <div class="rlp-card__head">
                <strong>Teil A</strong>
                <a href="<?= htmlspecialchars($rlp['teil_a']) ?>" target="_blank" rel="noopener">In neuem Tab öffnen</a>
            </div>
            <embed src="<?= htmlspecialchars($rlp['teil_a']) ?>#view=FitH" type="application/pdf" class="rlp-embed">
        </div>
        <div class="rlp-card">
            <div class="rlp-card__head">
                <strong>Teil B</strong>
                <a href="<?= htmlspecialchars($rlp['teil_b']) ?>" target="_blank" rel="noopener">In neuem Tab öffnen</a>
            </div>
            <embed src="<?= htmlspecialchars($rlp['teil_b']) ?>#view=FitH" type="application/pdf" class="rlp-embed">
        </div>
        <div class="rlp-card" id="rlp-card-c" data-map='<?= htmlspecialchars(json_encode($mapC, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), ENT_QUOTES) ?>'<?= $initialC === '' ? ' hidden' : '' ?>>
            <div class="rlp-card__head">
                <strong>Teil C <span id="rlp-c-fach" class="text-muted"><?= $initial !== '' ? '– ' . htmlspecialchars($initial) : '' ?></span></strong>
                <a id="rlp-c-link" href="<?= htmlspecialchars($initialC) ?>" target="_blank" rel="noopener">In neuem Tab öffnen</a>
            </div>
            <embed id="rlp-c-embed" src="<?= htmlspecialchars($initialC !== '' ? $initialC . '#view=FitH' : '') ?>" type="application/pdf" class="rlp-embed">
        </div>
        <div class="rlp-card rlp-card--empty" id="rlp-card-c-empty"<?= $initial !== '' && $initialC === '' ? '' : ' hidden' ?>>
            <p>Für <strong id="rlp-c-empty-fach"><?= htmlspecialchars($initial) ?></strong> ist im RLP-Ordner kein Teil C hinterlegt.</p>
        </div>
    </div>
</section>
<script>
(() => {
    const fachSelect = document.getElementById('cs-fach');
    const cardC      = document.getElementById('rlp-card-c');
    const cardEmpty  = document.getElementById('rlp-card-c-empty');
    if (!fachSelect || !cardC) return;

    const map     = JSON.parse(cardC.dataset.map || '{}');
    const embed   = document.getElementById('rlp-c-embed');
    const link    = document.getElementById('rlp-c-link');
    const fachLbl = document.getElementById('rlp-c-fach');
    const emptyFa = document.getElementById('rlp-c-empty-fach');

    fachSelect.addEventListener('change', () => {
        const fach = fachSelect.value;
        const pdf  = map[fach] || '';
        if (fach === '') {
            cardC.hidden = true;
            cardEmpty.hidden = true;
            embed.removeAttribute('src');
            return;
        }
        if (pdf) {
            cardC.hidden = false;
            cardEmpty.hidden = true;
            embed.src = pdf + '#view=FitH';
            link.href = pdf;
            fachLbl.textContent = '– ' + fach;
        } else {
            cardC.hidden = true;
            cardEmpty.hidden = false;
            emptyFa.textContent = fach;
            embed.removeAttribute('src');
        }
    });
})();
</script>
