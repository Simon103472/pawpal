<?php
// Algemene hulpfuncties die op meerdere pagina's nodig zijn.

/**
 * Maakt tekst veilig om in HTML te tonen (TE-04).
 *
 * Waarom: een gebruiker kan tekst met HTML of JavaScript invullen.
 * Deze functie zet tekens zoals < en > om, zodat de browser de tekst
 * alleen laat zien en niet uitvoert (bescherming tegen XSS).
 * Invoer: de tekst die getoond moet worden (mag ook leeg of null zijn).
 * Uitvoer: dezelfde tekst, maar veilig voor HTML.
 */
function e(?string $tekst): string
{
    return htmlspecialchars($tekst ?? '', ENT_QUOTES, 'UTF-8');
}
