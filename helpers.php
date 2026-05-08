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

// Pfade zu den Berliner/Brandenburger Rahmenlehrplänen im rlps/-Ordner.
// Teil A und B sind allgemeingültig, Teil C ist fachspezifisch.
// Für Fächer ohne passenden Teil C (z. B. Sport) liefert das Mapping null.
function schics_rlp_files(): array {
    return [
        'teil_a' => 'rlps/Teil_A_2015_11_16.pdf',
        'teil_b' => 'rlps/Teil_B_2015_11_10.pdf',
        'teil_c' => [
            'Deutsch'                      => 'rlps/rlp-deutsch_1-10-teil-c.pdf',
            'Mathematik'                   => 'rlps/rahmenlehrplan-teil-c_mathe-1-10.pdf',
            'Englisch'                     => 'rlps/Teil_C_Mod_Fremdsprachen_2015_11_16.pdf',
            'Sachunterricht'               => 'rlps/Teil_C_Sachunterricht_2015_11_16.pdf',
            'Gesellschaftswissenschaften'  => 'rlps/Teil_C_Gesellschaftswissenschaften_2015_11_10.pdf',
            'Naturwissenschaften'          => 'rlps/Teil_C_Nawi_5-6_2015_11_16.pdf',
            'Musik'                        => 'rlps/Teil_C_Musik_2015_11_16.pdf',
            'Kunst'                        => 'rlps/Teil_C_Kunst_2015_11_10.pdf',
        ],
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
