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

// GitHub-Repo, aus dem das Selbst-Update gezogen wird. An einer Stelle,
// damit update.php und der Versions-Check in einstellungen.php denselben
// Wert nutzen.
function schics_update_repo(): string {
    return 'rhigma/schics';
}

// Liest den HEAD-Commit des main-Branches via GitHub-API. Gibt assoziatives
// Array mit sha/message/date zurück oder null, wenn der Lookup scheitert
// (kein curl, kein Netz, Rate-Limit, …) — Aufrufer behandelt null als
// „Status unbekannt".
function schics_remote_head(): ?array {
    if (!extension_loaded('curl')) return null;
    $url = 'https://api.github.com/repos/' . schics_update_repo() . '/branches/main';
    $ch  = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_CONNECTTIMEOUT => 5,
        CURLOPT_TIMEOUT        => 10,
        CURLOPT_USERAGENT      => 'schics-update/1.0',
        CURLOPT_HTTPHEADER     => ['Accept: application/vnd.github+json'],
    ]);
    $body = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    if ($body === false || $code !== 200) return null;
    $data = json_decode((string)$body, true);
    if (!is_array($data) || !isset($data['commit']['sha'])) return null;
    return [
        'sha'     => (string)$data['commit']['sha'],
        'message' => (string)($data['commit']['commit']['message']         ?? ''),
        'date'    => (string)($data['commit']['commit']['author']['date']  ?? ''),
    ];
}

// Felder, die die Suche auf der Startseite durchsucht.
// Schlüssel = DB-Spalte, Wert = lesbares Label für die Snippet-Anzeige.
function schics_search_fields(): array {
    return [
        'thema'                => 'Thema',
        'kompetenzen'          => 'Kompetenzen',
        'sprachbildung'        => 'Sprachbildung',
        'medienbildung'        => 'Medienbildung',
        'methoden'             => 'Methoden',
        'kooperationen'        => 'Kooperationen',
        'übergreifende_themen' => 'Übergreifende Themen',
        'fächerverbindung'     => 'Fächerverbindung',
        'heterogenität'        => 'Heterogenität',
        'schulprofil'          => 'Schulprofil',
        'lebensweltbezug'      => 'Lebensweltbezug',
        'leistungsbewertung'   => 'Leistungsbewertung',
    ];
}

// Liefert ein HTML-Snippet rund um den ersten Vorkommen von $term in $text.
// $contextWords Wörter davor und danach, Treffer in <mark>. Gibt null zurück,
// wenn der Begriff nicht gefunden wurde. Inhalt wird escaped, das Markup
// sicher konstruiert. Nutzt mb_*-Funktionen, fällt auf byte-basierte
// stripos/substr zurück, wenn mbstring fehlt (lokal ohne Extension, Strato
// liefert sie mit).
function schics_snippet(string $text, string $term, int $contextWords = 8): ?string {
    if ($term === '' || $text === '') return null;
    $hasMb = function_exists('mb_stripos');
    $pos = $hasMb ? mb_stripos($text, $term) : stripos($text, $term);
    if ($pos === false) return null;

    $termLen = $hasMb ? mb_strlen($term)              : strlen($term);
    $before  = $hasMb ? mb_substr($text, 0, $pos)     : substr($text, 0, $pos);
    $match   = $hasMb ? mb_substr($text, $pos, $termLen) : substr($text, $pos, $termLen);
    $after   = $hasMb ? mb_substr($text, $pos + $termLen) : substr($text, $pos + $termLen);

    $beforeWords = preg_split('/\s+/u', trim($before)) ?: [];
    $afterWords  = preg_split('/\s+/u', trim($after))  ?: [];
    if ($beforeWords === ['']) $beforeWords = [];
    if ($afterWords  === ['']) $afterWords  = [];

    $prefix      = count($beforeWords) > $contextWords ? '… ' : '';
    $suffix      = count($afterWords)  > $contextWords ? ' …' : '';
    $beforeSnip  = implode(' ', array_slice($beforeWords, -$contextWords));
    $afterSnip   = implode(' ', array_slice($afterWords, 0, $contextWords));

    $html  = $prefix !== '' ? $prefix : '';
    if ($beforeSnip !== '') $html .= htmlspecialchars($beforeSnip) . ' ';
    $html .= '<mark>' . htmlspecialchars($match) . '</mark>';
    if ($afterSnip  !== '') $html .= ' ' . htmlspecialchars($afterSnip);
    $html .= $suffix;
    return $html;
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

// Übergreifende Themen aus Rahmenlehrplan Teil B. Pro Thema ein
// kurzes Label für Toggle-Buttons und eine Liste von Such-Patterns,
// die im Feld "übergreifende_themen" eines SchiCs gefunden werden
// sollen (case-insensitiv, Substring). Reihenfolge wie im RLP.
function schics_uebergreifende_themen(): array {
    return [
        ['id' => 'berufs',         'label' => 'Berufs- und Studienorientierung',           'patterns' => ['Berufs', 'Studienorient']],
        ['id' => 'vielfalt',       'label' => 'Akzeptanz von Vielfalt',                    'patterns' => ['Vielfalt', 'Diversity']],
        ['id' => 'demokratie',     'label' => 'Demokratiebildung',                         'patterns' => ['Demokratie']],
        ['id' => 'europa',         'label' => 'Europabildung',                             'patterns' => ['Europa']],
        ['id' => 'gesundheit',     'label' => 'Gesundheitsförderung',                      'patterns' => ['Gesundheit']],
        ['id' => 'gewalt',         'label' => 'Gewaltprävention',                          'patterns' => ['Gewalt']],
        ['id' => 'gleichstellung', 'label' => 'Gleichstellung der Geschlechter',           'patterns' => ['Gleichstellung', 'Gleichberechtigung', 'Gender']],
        ['id' => 'interkulturell', 'label' => 'Interkulturelle Bildung',                   'patterns' => ['Interkultur']],
        ['id' => 'kultur',         'label' => 'Kulturelle Bildung',                        'patterns' => ['Kulturelle Bildung']],
        ['id' => 'mobilitaet',     'label' => 'Mobilitäts- und Verkehrsbildung',           'patterns' => ['Mobilität', 'Verkehr']],
        ['id' => 'nachhaltig',     'label' => 'Nachhaltige Entwicklung / Globales Lernen', 'patterns' => ['Nachhaltig', 'globalen Zusammenh', 'globales Lernen']],
        ['id' => 'sexual',         'label' => 'Sexualerziehung',                           'patterns' => ['Sexual', 'sexuell']],
        ['id' => 'verbraucher',    'label' => 'Verbraucherbildung',                        'patterns' => ['Verbraucher']],
    ];
}

// Liefert die IDs derjenigen übergreifenden Themen, deren Patterns
// im Text vorkommen. Nutzt mb_stripos, fällt auf stripos zurück.
function schics_themen_in_text(string $text): array {
    if ($text === '') return [];
    $hasMb  = function_exists('mb_stripos');
    $found  = [];
    foreach (schics_uebergreifende_themen() as $thema) {
        foreach ($thema['patterns'] as $p) {
            $pos = $hasMb ? mb_stripos($text, $p) : stripos($text, $p);
            if ($pos !== false) {
                $found[] = $thema['id'];
                break;
            }
        }
    }
    return $found;
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
