<?php
// Databaseverbinding van PawPal (TE-02).
require_once __DIR__ . '/config.php';

/**
 * Geeft de verbinding met de database terug.
 *
 * Waarom: iedere pagina die gegevens nodig heeft, roept db() aan.
 * Zo staat de code voor de verbinding maar op een plek.
 * Invoer: geen.
 * Uitvoer: een PDO-object waarmee we query's kunnen uitvoeren.
 * Bij een fout: de echte foutmelding gaat naar het logbestand van de
 * server en de bezoeker krijgt alleen een algemene melding te zien.
 */
function db(): PDO
{
    // 'static' onthoudt de verbinding, zodat we per pagina
    // maar een keer verbinding maken.
    static $pdo = null;

    if ($pdo === null) {
        $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAAM . ';charset=utf8mb4';

        try {
            $pdo = new PDO($dsn, DB_GEBRUIKER, DB_WACHTWOORD, [
                // Bij een databasefout een exception geven in plaats van stil doorgaan
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                // Rijen teruggeven als array met kolomnamen, bijvoorbeeld $rij['naam']
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                // Echte prepared statements van MySQL gebruiken (tegen SQL-injectie)
                PDO::ATTR_EMULATE_PREPARES   => false,
            ]);
        } catch (PDOException $fout) {
            error_log('PawPal databasefout: ' . $fout->getMessage());
            http_response_code(500);
            exit('Er ging iets mis met de database. Probeer het later opnieuw.');
        }
    }

    return $pdo;
}
