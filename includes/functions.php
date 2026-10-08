<?php
// Algemene hulpfuncties die op meerdere pagina's nodig zijn.
require_once __DIR__ . '/db.php';

// De vaste keuzes van de opvang. Dezelfde waarden staan in de database (ENUM).
const DIERSOORTEN = ['hond', 'kat', 'konijn'];
const DIENSTEN = ['ochtend', 'middag'];
const LOCATIES = ['Noord', 'Zuid'];

/**
 * Vangt een fout op die nergens anders is afgehandeld, bijvoorbeeld een databasefout.
 *
 * Waarom: zonder deze functie kan PHP de technische foutmelding op het scherm
 * zetten, met namen van bestanden en tabellen. Nu gaat de echte fout naar het
 * logbestand en ziet de bezoeker alleen een algemene melding (TE-04).
 * Een transactie die nog bezig was, wordt door de database vanzelf teruggedraaid.
 */
function onverwachte_fout(Throwable $fout): void
{
    error_log('PawPal onverwachte fout: ' . $fout->getMessage());
    http_response_code(500);
    exit('Er ging iets mis. Probeer het later opnieuw.');
}

// PHP roept deze functie aan bij iedere fout (exception) die niet is opgevangen
set_exception_handler('onverwachte_fout');

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

/**
 * Telt hoeveel plekken van een capaciteitsrij bezet zijn (FE-04).
 *
 * Een plek is bezet bij de status 'aangevraagd' of 'goedgekeurd'.
 * Een afgewezen reservering telt niet mee: die plek is weer vrij.
 * Invoer: het id van de capaciteitsrij.
 * Uitvoer: het aantal bezette plekken.
 */
function bezette_plaatsen(int $capaciteit_id): int
{
    $stmt = db()->prepare(
        "SELECT COUNT(*) FROM reserveringen
         WHERE capaciteit_id = ? AND status IN ('aangevraagd', 'goedgekeurd')"
    );
    $stmt->execute([$capaciteit_id]);

    return (int) $stmt->fetchColumn();
}

/**
 * Haalt de capaciteit van vandaag en later op, met het aantal bezette en vrije plekken (FE-04).
 *
 * Invoer: een diersoort om op te filteren, of null voor alle diersoorten.
 * Uitvoer: een lijst met rijen. Iedere rij heeft de kolommen van capaciteit
 * plus 'bezet' en 'vrij' (vrij = max_plaatsen - bezet).
 */
function capaciteit_overzicht(?string $diersoort = null): array
{
    // LEFT JOIN: ook capaciteit zonder reserveringen komt in de lijst (bezet = 0)
    $sql = "SELECT c.*, COUNT(r.id) AS bezet
            FROM capaciteit c
            LEFT JOIN reserveringen r
                   ON r.capaciteit_id = c.id AND r.status IN ('aangevraagd', 'goedgekeurd')
            WHERE c.datum >= CURDATE()";
    $waarden = [];

    if ($diersoort !== null) {
        $sql .= ' AND c.diersoort = ?';
        $waarden[] = $diersoort;
    }

    $sql .= ' GROUP BY c.id ORDER BY c.datum, c.dienst, c.locatie, c.diersoort';

    $stmt = db()->prepare($sql);
    $stmt->execute($waarden);
    $rijen = $stmt->fetchAll();

    foreach ($rijen as $nummer => $rij) {
        $rijen[$nummer]['vrij'] = $rij['max_plaatsen'] - $rij['bezet'];
    }

    return $rijen;
}

/**
 * Schrijft een regel in de wijzigingslog (FE-08).
 *
 * Legt vast wie de wijziging deed, bij welk dier, welk veld het was en wat de
 * oude en de nieuwe waarde zijn. Het tijdstip vult de database zelf in.
 * De naam van het dier slaan we apart op, zodat de logregel leesbaar blijft
 * als het dier later wordt verwijderd.
 * Invoer: het id van de gebruiker, de rij van het dier, de naam van het veld
 * en de oude en nieuwe waarde (null betekent: leeg).
 */
function log_wijziging(int $gebruiker_id, array $dier, string $veld, ?string $oude_waarde, ?string $nieuwe_waarde): void
{
    $stmt = db()->prepare(
        'INSERT INTO wijzigingslog (gebruiker_id, dier_id, dier_naam, veld, oude_waarde, nieuwe_waarde)
         VALUES (?, ?, ?, ?, ?, ?)'
    );
    $stmt->execute([$gebruiker_id, $dier['id'], $dier['naam'], $veld, $oude_waarde, $nieuwe_waarde]);
}

/**
 * De toegangsregel voor gevoelige verzorgingsgegevens (FE-07).
 *
 * Een medewerker is "betrokken" bij een dier als dat dier een goedgekeurde
 * reservering heeft op de locatie van de medewerker, vandaag of later.
 * Invoer: het id van het dier en de locatie van de medewerker.
 * Uitvoer: true als de medewerker de verzorgingsgegevens mag zien, anders false.
 */
function medewerker_mag_verzorging_zien(int $dier_id, string $locatie): bool
{
    $stmt = db()->prepare(
        "SELECT COUNT(*)
         FROM reserveringen r
         JOIN capaciteit c ON c.id = r.capaciteit_id
         WHERE r.dier_id = ?
           AND r.status = 'goedgekeurd'
           AND c.locatie = ?
           AND c.datum >= CURDATE()"
    );
    $stmt->execute([$dier_id, $locatie]);

    return $stmt->fetchColumn() > 0;
}
