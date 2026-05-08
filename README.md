# SchiCs — Schulinterne Curricula

Eine schlanke PHP-Webapp zur Verwaltung versionierter schulinterner Curricula.
Daten werden in einer SQLite-Datei abgelegt; es gibt keinen externen
Datenbankserver. Geeignet für Shared Hosting (z. B. Strato).

## Funktionen

- Suche nach Fach, Jahrgang und Volltext.
- Detailansicht mit Verweisen auf die Berliner Rahmenlehrpläne A/B/C.
- Quer- und Längsschnitt-Auswertung über inhaltliche Felder.
- Versionierung: jede Speicherung erzeugt einen neuen Eintrag, der die
  Vorgängerversion nicht überschreibt.
- Drag-and-Drop-Sortierung pro Fach/Jahrgang.
- Drei Zugangsebenen mit getrennten Passwörtern.

## Drei Zugangsebenen

| Ebene | Wer | Was |
|---|---|---|
| **Lesen**       | Optional offen oder per Passwort | Suche, Detailansicht, Quer-/Längsschnitte |
| **Bearbeiten**  | Lehrkräfte                       | + neue SchiCs anlegen, neue Versionen, Reihenfolge sortieren |
| **Admin**       | Schulleitung / Verantwortliche   | + Schulname und Passwörter ändern |

Höhere Ebenen schließen niedrigere ein. Wer das Admin-Passwort kennt, kann auch
bearbeiten und lesen.

Das Lese-Passwort kann jederzeit unter „Einstellungen" leer gelassen werden,
wenn die Curricula z. B. später für die ganze Schulgemeinschaft offen
zugänglich sein sollen.

## Deployment in 4 Schritten

1. **ZIP entpacken.**
2. Per FTP nach `htdocs/` (Strato) hochladen. **Nicht mit hochladen:**
   die mit `php.ini` benannte Datei und der Ordner `data/sessions/` —
   beide sind nur fürs lokale Testen da.
3. Sicherstellen, dass `data/` schreibbar ist (im FTP-Programm `chmod 775`).
4. Die Seite einmal im Browser aufrufen — ein Einrichtungs-Wizard erscheint.
   Schulname und drei Passwörter eintragen, fertig.

Es gibt keinen Installer, keine `config.php`-Bearbeitung, keine SQL-Skripte.

### Später Passwörter ändern

Als Admin angemeldet → in der Navigation auf „Einstellungen" → neue Werte
speichern.

## Voraussetzungen

- PHP 8.0 oder neuer mit aktivierter `pdo_sqlite`-Extension (auf Strato
  standardmäßig aktiv).
- Apache mit `mod_authz_core` (Strato-Standard) — ein Fallback für ältere
  Apache-Versionen ist in den `.htaccess`-Dateien enthalten.

## Sicherheit

- `data/` ist über `.htaccess` für direkte Web-Zugriffe gesperrt; die
  SQLite-Datei kann nicht heruntergeladen werden.
- Schreibende Endpunkte sind nur nach Login zugänglich.
- Alle SQL-Abfragen verwenden Prepared Statements; Spaltennamen, die aus
  dem Request kommen, werden gegen eine Whitelist geprüft.

Es werden keine personenbezogenen Daten gespeichert — außer einem
freiwilligen Klarnamen im Feld „Bearbeitet von".

## Backup

Die gesamte Datenbank ist eine einzelne Datei: `data/curricula.db`.
Backup = Datei kopieren, Restore = Datei zurückspielen.

## Lokale Entwicklung

In diesem Repository liegen `php.ini` und (nach dem ersten Start) der
Ordner `data/sessions/` zur lokalen Konfiguration. Beides bitte **nicht**
auf Produktionssysteme hochladen.

```
php -c php.ini -S localhost:8000
```

Der eingebaute PHP-Server liest `.htaccess` nicht aus — die Schutzwirkung
muss auf einem echten Apache verifiziert werden.

## Datei-Überblick

| Datei | Zweck |
|---|---|
| `db.php`             | SQLite-Verbindung, Schema-Init, Settings-Helfer |
| `auth.php`           | Drei-Ebenen-Login |
| `setup.php`          | Einrichtungs-Wizard beim ersten Aufruf |
| `einstellungen.php`  | Schulname und Passwörter ändern (Admin) |
| `login.php` / `logout.php` | Anmelden / Abmelden |
| `nav.php`            | Gemeinsame Navigationsleiste |
| `index.php`          | Startseite: Übersichts-Raster Fach × Jahrgang |
| `suchen.php` / `ajax_suche.php` | Suche nach SchiCs |
| `detail.php`         | Detailansicht des aktuellen Stands |
| `alle_versionen.php` | Versionshistorie eines SchiCs |
| `dashboard.php` / `dashboard_data.php` | Querschnittsauswertung |
| `neuer_schic.php`    | Neuen SchiC-Eintrag anlegen (Bearbeiten) |
| `neue_version.php`   | Neue Version eines vorhandenen SchiCs |
| `sortieren.php` / `update_reihenfolge.php` | Reihenfolge bearbeiten |
| `config.example.php` | Optionale Überschreibungen (nur für Spezialfälle) |
| `data/`              | SQLite-Datei und `.htaccess` (auf dem Server) |
| `rlps/`              | PDFs der Berliner Rahmenlehrpläne |
