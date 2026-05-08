# Trim ungenutzte Schriften aus vendor/mpdf/mpdf/ttfonts/.
#
# Wir nutzen nur Latin-Schriften (deutsche SchiCs). mPDF liefert standard-
# mässig auch CJK, Tibetisch, Arabisch, Burmesisch, Hieroglyphen usw. mit —
# das bläht vendor/ um ~80 MB auf, ohne dass es jemand braucht.
#
# Skript ist idempotent. Nach jedem `composer install`/`composer update`
# einmal ausführen, bevor commitet wird.
#
# Behält:
#   - DejaVu* (Sans, Serif, Mono, Condensed)  → unsere Standardschrift
#   - Free*   (Sans, Serif, Mono)             → Latin-Fallback
#   - ocrb10                                  → von mPDF intern referenziert
$ErrorActionPreference = 'Stop'
$fontsDir = Join-Path $PSScriptRoot '..\vendor\mpdf\mpdf\ttfonts' | Resolve-Path

$keepPatterns = @('DejaVu*', 'Free*', 'ocrb10*')
$kept = @()
$removed = @()

Get-ChildItem -Path $fontsDir -File | ForEach-Object {
    $file = $_
    $shouldKeep = $false
    foreach ($pat in $keepPatterns) {
        if ($file.Name -like $pat) { $shouldKeep = $true; break }
    }
    if ($shouldKeep) {
        $kept += $file.Name
    } else {
        Remove-Item -LiteralPath $file.FullName -Force
        $removed += $file.Name
    }
}

Write-Output ("Behalten: {0} Dateien" -f $kept.Count)
Write-Output ("Entfernt: {0} Dateien" -f $removed.Count)
