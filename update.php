<?php
// Selbst-Update: zieht den aktuellen main-Branch von GitHub als ZIP, sichert
// die DB und überschreibt alle Dateien außer dem data/-Ordner.
// Erreichbar nur via POST mit confirm=yes durch ADMIN-Level-Nutzer.
require_once __DIR__ . '/auth.php';
schics_require_level(SCHICS_LEVEL_ADMIN);

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || ($_POST['confirm'] ?? '') !== 'yes') {
    header('Location: einstellungen.php');
    exit;
}

@set_time_limit(120);
@ini_set('memory_limit', '128M');

$githubZipUrl = 'https://codeload.github.com/rhigma/schics/zip/refs/heads/main';
$projectRoot  = __DIR__;
$dataDir      = $projectRoot . '/data';
$backupDir    = $dataDir . '/backups';
$tmpZip       = $dataDir . '/_update.zip';
$tmpExtract   = $dataDir . '/_update_tmp';
$dbPath       = schics_config()['db_path'];

function schics_update_rrmdir(string $dir): void {
    if (!is_dir($dir)) return;
    foreach (scandir($dir) as $entry) {
        if ($entry === '.' || $entry === '..') continue;
        $path = $dir . '/' . $entry;
        is_dir($path) ? schics_update_rrmdir($path) : @unlink($path);
    }
    @rmdir($dir);
}

// Kopiert $src rekursiv über $dst. $skip ist eine Liste von Top-Level-Namen
// relativ zu $src, die ausgelassen werden (z. B. ['data']).
function schics_update_copy(string $src, string $dst, array $skip): int {
    $count = 0;
    if (!is_dir($dst) && !mkdir($dst, 0775, true) && !is_dir($dst)) {
        throw new RuntimeException('Zielverzeichnis nicht beschreibbar: ' . $dst);
    }
    foreach (scandir($src) as $entry) {
        if ($entry === '.' || $entry === '..') continue;
        if (in_array($entry, $skip, true)) continue;
        $sp = $src . '/' . $entry;
        $dp = $dst . '/' . $entry;
        if (is_dir($sp)) {
            $count += schics_update_copy($sp, $dp, []);
        } else {
            if (!copy($sp, $dp)) {
                throw new RuntimeException('Datei konnte nicht überschrieben werden: ' . $entry);
            }
            $count++;
        }
    }
    return $count;
}

try {
    if (!extension_loaded('curl'))   throw new RuntimeException('PHP-Erweiterung „curl" fehlt auf dem Server.');
    if (!class_exists('ZipArchive')) throw new RuntimeException('PHP-Erweiterung „zip" fehlt auf dem Server.');

    if (!is_dir($backupDir) && !mkdir($backupDir, 0775, true) && !is_dir($backupDir)) {
        throw new RuntimeException('Backup-Verzeichnis konnte nicht angelegt werden.');
    }

    // 1. DB sichern (max. 10 zeitgestempelte Kopien behalten)
    if (is_file($dbPath)) {
        $base = preg_replace('/\.db$/', '', basename($dbPath));
        $backupFile = $backupDir . '/' . $base . '_' . date('Ymd_His') . '.db';
        if (!@copy($dbPath, $backupFile)) {
            throw new RuntimeException('DB-Backup fehlgeschlagen (Schreibrechte auf data/backups/?).');
        }
        $existing = glob($backupDir . '/' . $base . '_*.db') ?: [];
        usort($existing, fn($a, $b) => filemtime($b) <=> filemtime($a));
        foreach (array_slice($existing, 10) as $old) @unlink($old);
    }

    // 2. ZIP von GitHub holen
    $fp = @fopen($tmpZip, 'wb');
    if (!$fp) throw new RuntimeException('Konnte temporäre Datei nicht öffnen: ' . $tmpZip);
    $ch = curl_init($githubZipUrl);
    curl_setopt_array($ch, [
        CURLOPT_FILE           => $fp,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_FAILONERROR    => true,
        CURLOPT_CONNECTTIMEOUT => 15,
        CURLOPT_TIMEOUT        => 60,
        CURLOPT_USERAGENT      => 'schics-update/1.0',
    ]);
    $ok  = curl_exec($ch);
    $err = curl_error($ch);
    curl_close($ch);
    fclose($fp);
    if (!$ok) throw new RuntimeException('Download von GitHub fehlgeschlagen: ' . $err);

    // 3. Entpacken
    if (is_dir($tmpExtract)) schics_update_rrmdir($tmpExtract);
    $zip = new ZipArchive();
    if ($zip->open($tmpZip) !== true) throw new RuntimeException('ZIP-Datei konnte nicht geöffnet werden.');
    if (!$zip->extractTo($tmpExtract)) {
        $zip->close();
        throw new RuntimeException('ZIP-Datei konnte nicht entpackt werden.');
    }
    $zip->close();

    // 4. Source-Root finden (GitHub packt in schics-<branch>/)
    $sub = glob($tmpExtract . '/*', GLOB_ONLYDIR);
    if (!$sub || count($sub) !== 1) {
        throw new RuntimeException('Unerwartete ZIP-Struktur.');
    }
    $sourceDir = $sub[0];

    // 5. Dateien kopieren — data/ bleibt unangetastet
    $count = schics_update_copy($sourceDir, $projectRoot, ['data']);

    // 6. Aufräumen
    schics_update_rrmdir($tmpExtract);
    @unlink($tmpZip);

    schics_flash('✅ Update erfolgreich. ' . $count . ' Dateien aktualisiert. Eine DB-Sicherung wurde unter data/backups/ abgelegt.');
} catch (Throwable $e) {
    @unlink($tmpZip);
    if (is_dir($tmpExtract)) schics_update_rrmdir($tmpExtract);
    schics_flash('❌ Update fehlgeschlagen: ' . $e->getMessage());
}

header('Location: einstellungen.php');
exit;
