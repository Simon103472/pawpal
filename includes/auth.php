<?php
// Inloggen, uitloggen, rollen en CSRF-beveiliging (TE-03, FE-07).
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/functions.php';

// De sessie starten. Een sessie onthoudt op de server wie er is ingelogd.
// De browser krijgt alleen een cookie met een sessienummer.
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'httponly' => true,                    // JavaScript kan de cookie niet lezen
        'samesite' => 'Lax',                   // cookie gaat niet mee vanaf andere websites
        'secure'   => !empty($_SERVER['HTTPS']), // op https alleen via https versturen
    ]);
    session_start();
}

/**
 * Controleert e-mail en wachtwoord en logt de gebruiker in.
 *
 * Invoer: het ingevulde e-mailadres en wachtwoord.
 * Uitvoer: true als het inloggen gelukt is, anders false.
 * Bij een fout: er wordt niets in de sessie gezet. De pagina toont dan
 * een algemene melding, zodat niemand kan zien of een e-mailadres bestaat.
 */
function inloggen(string $email, string $wachtwoord): bool
{
    // prepare() + execute(): de e-mail wordt apart van de query verstuurd,
    // zodat SQL-injectie niet mogelijk is.
    $stmt = db()->prepare('SELECT id, naam, wachtwoord_hash, rol, locatie FROM gebruikers WHERE email = ?');
    $stmt->execute([$email]);
    $gebruiker = $stmt->fetch();

    // password_verify() vergelijkt het ingevulde wachtwoord met de hash uit de database
    if (!$gebruiker || !password_verify($wachtwoord, $gebruiker['wachtwoord_hash'])) {
        return false;
    }

    // Nieuw sessienummer na inloggen: een eerder gestolen of
    // opgedrongen sessienummer is daarna niets meer waard.
    session_regenerate_id(true);

    // De wachtwoord-hash bewaren we niet in de sessie
    $_SESSION['gebruiker'] = [
        'id'      => $gebruiker['id'],
        'naam'    => $gebruiker['naam'],
        'rol'     => $gebruiker['rol'],
        'locatie' => $gebruiker['locatie'],
    ];

    return true;
}

/**
 * Logt de gebruiker uit: maakt de sessie leeg en verwijdert de sessiecookie.
 */
function uitloggen(): void
{
    $_SESSION = [];

    $cookie = session_get_cookie_params();
    setcookie(session_name(), '', time() - 3600, $cookie['path'], $cookie['domain'], $cookie['secure'], $cookie['httponly']);

    session_destroy();
}

/**
 * Geeft de ingelogde gebruiker terug.
 *
 * Uitvoer: een array met id, naam, rol en locatie, of null als niemand is ingelogd.
 */
function huidige_gebruiker(): ?array
{
    return $_SESSION['gebruiker'] ?? null;
}

/**
 * Geeft het pad van het dashboard dat bij een rol hoort.
 */
function dashboard_pad(string $rol): string
{
    return $rol === 'medewerker' ? '/medewerker/dashboard.php' : '/eigenaar/dashboard.php';
}

/**
 * Beveiligt een pagina: alleen een ingelogde gebruiker met de juiste rol mag verder.
 *
 * Invoer: de rol die nodig is ('eigenaar' of 'medewerker').
 * Uitvoer: de gegevens van de ingelogde gebruiker.
 * Bij een fout: wie niet is ingelogd gaat naar de loginpagina. Wie de
 * verkeerde rol heeft, gaat terug naar zijn eigen dashboard.
 * Roep deze functie aan bovenaan iedere beveiligde pagina, voor header.php.
 */
function vereis_rol(string $rol): array
{
    $gebruiker = huidige_gebruiker();

    if ($gebruiker === null) {
        doorsturen('/login.php');
    }

    if ($gebruiker['rol'] !== $rol) {
        doorsturen(dashboard_pad($gebruiker['rol']));
    }

    return $gebruiker;
}

/**
 * Geeft het CSRF-token van deze sessie terug (en maakt het de eerste keer aan).
 *
 * Waarom: een andere website kan een formulier naar PawPal laten versturen
 * terwijl de gebruiker is ingelogd. Die website kent dit geheime token niet,
 * dus zo'n nagemaakt formulier wordt geweigerd.
 */
function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        // random_bytes() maakt een willekeurige waarde die niet te raden is
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['csrf_token'];
}

/**
 * Geeft een verborgen formulierveld met het CSRF-token.
 * Zet dit in ieder formulier dat gegevens wijzigt.
 */
function csrf_veld(): string
{
    return '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">';
}

/**
 * Controleert het CSRF-token van een verstuurd formulier.
 *
 * Bij een fout: het script stopt met een melding en er wordt niets opgeslagen.
 * Roep deze functie aan voordat een POST-formulier wordt verwerkt.
 */
function controleer_csrf(): void
{
    $verstuurd = $_POST['csrf_token'] ?? '';

    // hash_equals() vergelijkt de twee waarden op een veilige manier
    if (!is_string($verstuurd) || !hash_equals(csrf_token(), $verstuurd)) {
        http_response_code(400);
        exit('Het formulier is verlopen of ongeldig. Ga terug en probeer het opnieuw.');
    }
}
