<?php
// Kleine UI-Helfer, die mehrere Seiten teilen.

function schics_status_class(string $status): string {
    return match ($status) {
        'Entwurf'     => 'status--entwurf',
        'Beschlossen' => 'status--beschlossen',
        default       => '',
    };
}

function schics_status_badge(string $status): string {
    $cls  = schics_status_class($status);
    $text = htmlspecialchars($status);
    return "<span class=\"status $cls\">$text</span>";
}

// Reihenfolge und Metadaten der Curriculum-Sheet-Zellen.
// Tupel: [Grid-Position, A/B/C, Titel, DB-Spalte]
function schics_curriculum_cells(): array {
    return [
        ['fachv',    'a', 'Fächerverbindende Schwerpunkte',                      'fächerverbindung'],
        ['hetero',   'a', 'Heterogenität / Inklusion',                           'heterogenität'],
        ['schulp',   'a', 'Schulprofil / Pädagogische Schwerpunktsetzung',       'schulprofil'],
        ['sprach',   'b', 'Sprachbildung',                                       'sprachbildung'],
        ['leben',    'a', 'Lebensweltbezug',                                     'lebensweltbezug'],
        ['kompet',   'c', 'Kompetenzen und Konkretisierung',                     'kompetenzen'],
        ['uebergr',  'b', 'Übergreifende Themen',                                'übergreifende_themen'],
        ['kooper',   'a', 'Kooperationsangebote und außerschulische Lernorte',   'kooperationen'],
        ['lernber',  'a', 'Lernberatung, Leistungsdokumentation und -bewertung', 'leistungsbewertung'],
        ['medien',   'b', 'Medienbildung',                                       'medienbildung'],
        ['methoden', 'b', 'Methoden und Arbeitstechniken',                       'methoden'],
    ];
}

// Ein einzelnes Feld in der Detailansicht: Label oben, Wert unten.
// Mehrzeiliger Inhalt wird über nl2br dargestellt.
function schics_field(string $label, ?string $value, bool $multiline = false): string {
    $labelEsc = htmlspecialchars($label);
    $valueEsc = htmlspecialchars((string)($value ?? ''));
    if ($multiline) {
        $valueEsc = nl2br($valueEsc);
    }
    return "<div class=\"field\"><span class=\"field-label\">$labelEsc</span><p class=\"field-value\">$valueEsc</p></div>";
}
