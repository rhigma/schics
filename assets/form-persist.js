// Persistiert Formular-Eingaben im LocalStorage:
//   1. "Bearbeitet von" wird beim Speichern gemerkt und beim nächsten Aufruf
//      vorausgefüllt, sofern das Feld leer ist.
//   2. Sobald die Lehrkraft im Formular tippt, wird der gesamte Form-Zustand
//      regelmäßig gesichert. Wird die Seite ohne Speichern verlassen und neu
//      aufgerufen, erscheint ein Banner mit Wiederherstellen / Verwerfen.
//
// Reine Frontend-Lösung — keine DB, kein Server-Code. Das Backend speichert
// erst beim regulären Submit.

(function () {
    const NAME_KEY        = 'schics_bearbeitet_von';
    const DRAFT_KEY       = 'schics_draft:' + window.location.pathname + window.location.search;
    const DEBOUNCE_MS     = 500;

    const form = document.querySelector('main form[method="post"]');
    if (!form) return;

    // ---------- Feature 1: bearbeitet_von merken ----------
    const nameField = form.querySelector('input[name="bearbeitet_von"]');
    if (nameField) {
        if (!nameField.value) {
            try {
                const saved = localStorage.getItem(NAME_KEY);
                if (saved) nameField.value = saved;
            } catch (_) { /* localStorage gesperrt — egal */ }
        }
        form.addEventListener('submit', () => {
            try {
                if (nameField.value) localStorage.setItem(NAME_KEY, nameField.value);
            } catch (_) {}
        });
    }

    // ---------- Feature 2: Auto-Save / Entwurfs-Backup ----------
    function fields() {
        return form.querySelectorAll('input[name], textarea[name], select[name]');
    }
    function isPersistable(el) {
        if (el.disabled) return false;
        const t = (el.type || '').toLowerCase();
        return t !== 'hidden' && t !== 'submit' && t !== 'button'
            && t !== 'password' && t !== 'file' && t !== 'reset';
    }
    function snapshot() {
        const out = {};
        fields().forEach(el => {
            if (!isPersistable(el)) return;
            const t = (el.type || '').toLowerCase();
            if (t === 'checkbox' || t === 'radio') {
                if (el.checked) out[el.name] = el.value;
            } else {
                out[el.name] = el.value;
            }
        });
        return out;
    }
    function applyData(data) {
        fields().forEach(el => {
            if (!isPersistable(el))   return;
            if (!(el.name in data))   return;
            const t = (el.type || '').toLowerCase();
            if (t === 'checkbox' || t === 'radio') {
                el.checked = (el.value === data[el.name]);
            } else {
                el.value = data[el.name];
            }
            el.dispatchEvent(new Event('input',  { bubbles: true }));
            el.dispatchEvent(new Event('change', { bubbles: true }));
        });
    }
    function save() {
        try {
            localStorage.setItem(DRAFT_KEY, JSON.stringify({ ts: Date.now(), data: snapshot() }));
        } catch (_) {}
    }
    function clearDraft() {
        try { localStorage.removeItem(DRAFT_KEY); } catch (_) {}
    }

    function showRestoreBanner(parsed) {
        const ts = new Date(parsed.ts);
        const when = ts.toLocaleString('de-DE', {
            day: '2-digit', month: '2-digit', year: 'numeric',
            hour: '2-digit', minute: '2-digit'
        });

        const banner = document.createElement('div');
        banner.className = 'alert alert-warning draft-banner';
        banner.style.display = 'flex';
        banner.style.flexWrap = 'wrap';
        banner.style.alignItems = 'center';
        banner.style.gap = '.5rem';

        const text = document.createElement('span');
        text.style.flex = '1 1 auto';
        text.innerHTML = '🕒 Nicht gespeicherter Entwurf von <strong></strong> gefunden.';
        text.querySelector('strong').textContent = when;

        const restoreBtn = document.createElement('button');
        restoreBtn.type = 'button';
        restoreBtn.className = 'btn btn-sm btn-primary';
        restoreBtn.textContent = 'Wiederherstellen';

        const discardBtn = document.createElement('button');
        discardBtn.type = 'button';
        discardBtn.className = 'btn btn-sm btn-outline-secondary';
        discardBtn.textContent = 'Verwerfen';

        restoreBtn.addEventListener('click', () => {
            applyData(parsed.data);
            banner.remove();
        });
        discardBtn.addEventListener('click', () => {
            clearDraft();
            banner.remove();
        });

        banner.appendChild(text);
        banner.appendChild(restoreBtn);
        banner.appendChild(discardBtn);
        form.parentNode.insertBefore(banner, form);
    }

    let draftJson = null;
    try { draftJson = localStorage.getItem(DRAFT_KEY); } catch (_) {}
    if (draftJson) {
        try {
            const parsed = JSON.parse(draftJson);
            if (parsed && parsed.data && typeof parsed.data === 'object') {
                showRestoreBanner(parsed);
            } else {
                clearDraft();
            }
        } catch (_) { clearDraft(); }
    }

    let saveTimer;
    function scheduleSave() {
        clearTimeout(saveTimer);
        saveTimer = setTimeout(save, DEBOUNCE_MS);
    }
    fields().forEach(el => {
        el.addEventListener('input',  scheduleSave);
        el.addEventListener('change', scheduleSave);
    });

    form.addEventListener('submit', () => {
        clearTimeout(saveTimer);
        clearDraft();
    });
})();
