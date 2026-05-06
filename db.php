<?php
// Zentrale DB-Verbindung + Schema-Initialisierung für SQLite.

function schics_config(): array {
    static $config = null;
    if ($config !== null) {
        return $config;
    }
    // config.php ist OPTIONAL. Falls vorhanden, kann sie z. B. db_path
    // überschreiben. Schule und Passwörter werden in der DB gepflegt.
    $defaults = [
        'db_path' => __DIR__ . '/data/curricula.db',
    ];
    $configFile = __DIR__ . '/config.php';
    if (is_file($configFile)) {
        $loaded = require $configFile;
        if (is_array($loaded)) {
            $defaults = array_merge($defaults, $loaded);
        }
    }
    $config = $defaults;
    return $config;
}

function schics_db(): PDO {
    static $pdo = null;
    if ($pdo !== null) {
        return $pdo;
    }
    $config  = schics_config();
    $dbPath  = $config['db_path'];
    $dataDir = dirname($dbPath);

    if (!is_dir($dataDir)) {
        @mkdir($dataDir, 0775, true);
    }
    if (!is_dir($dataDir) || !is_writable($dataDir)) {
        http_response_code(500);
        echo 'Datenverzeichnis ist nicht beschreibbar: ' . htmlspecialchars($dataDir);
        exit;
    }

    $pdo = new PDO('sqlite:' . $dbPath);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->exec('PRAGMA foreign_keys = ON');
    $pdo->exec('PRAGMA journal_mode = WAL');

    schics_db_init($pdo);

    return $pdo;
}

function schics_db_init(PDO $pdo): void {
    $pdo->exec(<<<SQL
        CREATE TABLE IF NOT EXISTS curricula (
            id                   INTEGER PRIMARY KEY AUTOINCREMENT,
            schic_id             INTEGER NOT NULL,
            version              TEXT NOT NULL,
            status               TEXT NOT NULL,
            stand                TEXT NOT NULL,
            fach                 TEXT NOT NULL,
            jahrgang             INTEGER NOT NULL,
            thema                TEXT NOT NULL,
            umfang               TEXT DEFAULT '',
            reihenfolge          INTEGER DEFAULT 0,
            "fächerverbindung"   TEXT DEFAULT '',
            "heterogenität"      TEXT DEFAULT '',
            schulprofil          TEXT DEFAULT '',
            lebensweltbezug      TEXT DEFAULT '',
            kompetenzen          TEXT DEFAULT '',
            kooperationen        TEXT DEFAULT '',
            leistungsbewertung   TEXT DEFAULT '',
            medienbildung        TEXT DEFAULT '',
            methoden             TEXT DEFAULT '',
            sprachbildung        TEXT DEFAULT '',
            "übergreifende_themen" TEXT DEFAULT '',
            "änderungskommentar" TEXT DEFAULT '',
            bearbeitet_von       TEXT DEFAULT '',
            erstellt_am          TEXT NOT NULL DEFAULT CURRENT_TIMESTAMP
        )
    SQL);
    $pdo->exec('CREATE INDEX IF NOT EXISTS idx_curricula_schic_id ON curricula(schic_id)');
    $pdo->exec('CREATE INDEX IF NOT EXISTS idx_curricula_fach_jahrgang ON curricula(fach, jahrgang)');

    // Settings = einfache Key/Value-Tabelle für Schulname und Passwörter.
    $pdo->exec(<<<SQL
        CREATE TABLE IF NOT EXISTS settings (
            key   TEXT PRIMARY KEY,
            value TEXT NOT NULL
        )
    SQL);

    schics_db_migrate($pdo);
}

// Idempotente Schema-Anpassungen: Spalten entfernen / Daten korrigieren,
// wenn die DB einen älteren Stand hat. Wird bei jedem Verbindungsaufbau
// nach den CREATE-Statements aufgerufen — das ist günstig genug.
function schics_db_migrate(PDO $pdo): void {
    $cols = $pdo->query('PRAGMA table_info(curricula)')->fetchAll(PDO::FETCH_ASSOC);
    $names = array_column($cols, 'name');

    if (in_array('gremium_beschluss', $names, true)) {
        $pdo->exec('ALTER TABLE curricula DROP COLUMN gremium_beschluss');
    }
    if (in_array('gremium_datum', $names, true)) {
        $pdo->exec('ALTER TABLE curricula DROP COLUMN gremium_datum');
    }

    // Alte Gremium-Werte fallen vorsichtshalber auf Entwurf zurück (binärer Status).
    $pdo->exec("UPDATE curricula SET status = 'Entwurf' WHERE status NOT IN ('Entwurf','Beschlossen')");
}

function schics_setting(string $key, ?string $default = null): ?string {
    $pdo  = schics_db();
    $stmt = $pdo->prepare('SELECT value FROM settings WHERE key = :k');
    $stmt->execute([':k' => $key]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($row === false) return $default;
    return $row['value'];
}

function schics_set_setting(string $key, string $value): void {
    $pdo  = schics_db();
    $stmt = $pdo->prepare('INSERT INTO settings(key, value) VALUES(:k, :v)
                           ON CONFLICT(key) DO UPDATE SET value = excluded.value');
    $stmt->execute([':k' => $key, ':v' => $value]);
}

function schics_setup_done(): bool {
    // Setup gilt als abgeschlossen, wenn mindestens edit_password und
    // admin_password gesetzt sind. read_password darf leer bleiben.
    $edit  = schics_setting('edit_password');
    $admin = schics_setting('admin_password');
    return $edit !== null && $edit !== '' && $admin !== null && $admin !== '';
}

function schics_school_name(): string {
    return schics_setting('school_name', 'Schulinterne Curricula') ?? 'Schulinterne Curricula';
}

function schics_content_fields(): array {
    return [
        'sprachbildung', 'medienbildung', 'methoden', 'kompetenzen', 'kooperationen',
        'übergreifende_themen', 'leistungsbewertung', 'fächerverbindung',
        'heterogenität', 'schulprofil', 'lebensweltbezug',
    ];
}

function schics_faecher(): array {
    return [
        'Deutsch', 'Mathematik', 'Englisch', 'Sachunterricht',
        'Gesellschaftswissenschaften', 'Naturwissenschaften',
        'Musik', 'Kunst', 'Sport',
    ];
}

function schics_quote_ident(string $name): string {
    return '"' . str_replace('"', '""', $name) . '"';
}
