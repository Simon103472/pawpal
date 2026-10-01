<?php
// Voorbeeld van het configuratiebestand.
// Kopieer dit bestand naar config.php en vul je eigen gegevens in.
// config.php staat in .gitignore en komt dus niet in GitHub.

// Gegevens van de database
define('DB_HOST', 'localhost');
define('DB_NAAM', 'pawpal');
define('DB_GEBRUIKER', 'jouw_database_gebruiker');
define('DB_WACHTWOORD', 'jouw_database_wachtwoord');

// De map waarin PawPal op de server staat, zonder schuine streep aan het eind.
// Lokaal in XAMPP is dat '/pawpal'. Staat de site direct in de hoofdmap
// van het domein (zoals op Plesk), gebruik dan een lege tekst: ''.
define('BASIS_URL', '/pawpal');
