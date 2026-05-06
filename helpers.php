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
