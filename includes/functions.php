<?php
// Algemene hulpfuncties die op meerdere pagina's nodig zijn.
require_once __DIR__ . '/db.php';

// De diersoorten die de opvang kent. Dezelfde waarden staan in de database (ENUM).
const DIERSOORTEN = ['hond', 'kat', 'konijn'];

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

/**
 * Stuurt de bezoeker door naar een andere pagina van PawPal.
 *
 * Invoer: het pad binnen de site, bijvoorbeeld '/login.php'.
 * Uitvoer: geen. Het script stopt direct na het doorsturen (exit),
 * zodat de rest van de pagina niet meer wordt uitgevoerd.
 */
function doorsturen(string $pad): void
{
    header('Location: ' . BASIS_URL . $pad);
    exit;
}

/**
 * Bewaart een melding in de sessie voor de volgende pagina.
 *
 * Waarom: na het opslaan sturen we de gebruiker door naar een andere pagina.
 * Daar laat header.php de melding een keer zien en haalt hem daarna weg.
 * Invoer: de tekst en de soort ('succes' of 'fout').
 */
function zet_melding(string $tekst, string $soort = 'succes'): void
{
    $_SESSION['melding'] = ['tekst' => $tekst, 'soort' => $soort];
}

/**
 * Controleert of een tekst een bestaande datum is in de vorm jjjj-mm-dd.
 *
 * Invoer: de tekst uit een datumveld, bijvoorbeeld '2026-10-03'.
 * Uitvoer: true bij een echte datum, false bij bijvoorbeeld '2026-02-31' of 'abc'.
 */
function geldige_datum(string $datum): bool
{
    $d = DateTime::createFromFormat('Y-m-d', $datum);

    // PHP maakt van 31 februari stilletjes 3 maart; daarom vergelijken we met de invoer
    return $d !== false && $d->format('Y-m-d') === $datum;
}

/**
 * Zet een datum uit de database (jjjj-mm-dd) om naar Nederlandse notatie (dd-mm-jjjj).
 */
function datum_nl(?string $datum): string
{
    if ($datum === null || $datum === '') {
        return '';
    }

    return date('d-m-Y', strtotime($datum));
}

/**
 * Haalt een dier op, maar alleen als het van deze eigenaar is (FE-01, FE-07).
 *
 * Waarom: het nummer van een dier staat in de URL of in een formulier en kan
 * dus door de gebruiker worden veranderd. Door altijd ook op eigenaar_id te
 * zoeken, kan een eigenaar nooit bij het dier van iemand anders komen.
 * Invoer: het id van het dier en het id van de ingelogde eigenaar.
 * Uitvoer: de rij van het dier, of null als het dier niet bestaat
 * of van iemand anders is.
 */
function eigen_dier(int $dier_id, int $eigenaar_id): ?array
{
    $stmt = db()->prepare('SELECT * FROM dieren WHERE id = ? AND eigenaar_id = ?');
    $stmt->execute([$dier_id, $eigenaar_id]);
    $dier = $stmt->fetch();

    return $dier ?: null;
}
