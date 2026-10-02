<?php
// Uitloggen. Dit gebeurt met een POST-formulier en CSRF-token (de knop in het menu),
// zodat een andere website een gebruiker niet ongemerkt kan uitloggen.
require_once __DIR__ . '/includes/auth.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    controleer_csrf();
    uitloggen();
    doorsturen('/login.php');
}

// Wie deze pagina gewoon opent in de browser, gaat terug naar de startpagina
doorsturen('/index.php');
