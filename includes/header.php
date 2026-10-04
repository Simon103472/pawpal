<?php
// Bovenkant van iedere pagina: begin van de HTML, de kop en de navigatie.
// Een pagina zet eerst $paginatitel en laadt daarna dit bestand.
require_once __DIR__ . '/auth.php';

$paginatitel = $paginatitel ?? 'PawPal';
$ingelogd = huidige_gebruiker();
?>
<!DOCTYPE html>
<html lang="nl">
<head>
    <meta charset="UTF-8">
    <!-- Zorgt dat de pagina op een telefoon op de juiste breedte wordt getoond -->
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($paginatitel) ?> - PawPal</title>
    <link rel="stylesheet" href="<?= BASIS_URL ?>/assets/css/style.css">
</head>
<body>
<header class="kop">
    <div class="container kop-inhoud">
        <a class="logo" href="<?= BASIS_URL ?>/index.php">PawPal</a>
        <nav class="navigatie" aria-label="Hoofdmenu">
            <?php if ($ingelogd === null): ?>
                <a href="<?= BASIS_URL ?>/index.php">Home</a>
                <a href="<?= BASIS_URL ?>/login.php">Inloggen</a>
            <?php else: ?>
                <a href="<?= BASIS_URL . dashboard_pad($ingelogd['rol']) ?>">Dashboard</a>
                <?php if ($ingelogd['rol'] === 'eigenaar'): ?>
                    <a href="<?= BASIS_URL ?>/eigenaar/dieren.php">Mijn dieren</a>
                    <a href="<?= BASIS_URL ?>/eigenaar/reserveringen.php">Reserveringen</a>
                <?php else: ?>
                    <a href="<?= BASIS_URL ?>/medewerker/capaciteit.php">Capaciteit</a>
                <?php endif; ?>
                <span class="navigatie-naam"><?= e($ingelogd['naam']) ?></span>
                <form method="post" action="<?= BASIS_URL ?>/logout.php">
                    <?= csrf_veld() ?>
                    <button class="navigatie-knop" type="submit">Uitloggen</button>
                </form>
            <?php endif; ?>
        </nav>
    </div>
</header>
<main class="container">
<?php
// Een melding van de vorige pagina (bijvoorbeeld "Het dier is toegevoegd") een keer tonen
if (isset($_SESSION['melding'])):
    $melding = $_SESSION['melding'];
    unset($_SESSION['melding']);
?>
    <p class="melding <?= $melding['soort'] === 'fout' ? 'melding-fout' : 'melding-succes' ?>" role="status"><?= e($melding['tekst']) ?></p>
<?php endif; ?>
