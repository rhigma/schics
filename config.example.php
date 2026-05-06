<?php
// Diese Datei ist OPTIONAL. Wird sie nicht vorhanden gefunden, gelten die
// in db.php hinterlegten Voreinstellungen — das genügt für ein normales
// Strato-Deployment.
//
// Wenn du etwas überschreiben willst (z. B. den DB-Pfad, weil du die
// SQLite-Datei außerhalb des Projektordners ablegen möchtest), kopiere
// diese Datei nach `config.php` und passe die gewünschten Werte an.
//
// Schulname und Passwörter werden NICHT hier gepflegt, sondern beim
// ersten Aufruf der App im Setup-Wizard angelegt und später unter
// "Einstellungen" geändert. Sie liegen in der SQLite-Datenbank.

return [
    // Pfad zur SQLite-Datei. Standard ist `data/curricula.db` neben dieser
    // Datei.
    // 'db_path' => __DIR__ . '/data/curricula.db',
];
