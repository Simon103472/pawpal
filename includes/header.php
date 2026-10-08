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
    <!-- Het icoontje in het tabblad van de browser: hetzelfde pootje als het logo -->
    <link rel="icon" href="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 40 40'%3E%3Ccircle cx='20' cy='20' r='20' fill='%23D97706'/%3E%3Cellipse cx='20' cy='25.5' rx='7' ry='5.5' fill='%23fff'/%3E%3Cellipse cx='10.5' cy='19' rx='2.6' ry='3.4' fill='%23fff'/%3E%3Cellipse cx='16' cy='13.5' rx='2.6' ry='3.6' fill='%23fff'/%3E%3Cellipse cx='24' cy='13.5' rx='2.6' ry='3.6' fill='%23fff'/%3E%3Cellipse cx='29.5' cy='19' rx='2.6' ry='3.4' fill='%23fff'/%3E%3C/svg%3E">
</head>
<body>
<header class="kop">
    <div class="container kop-inhoud">
        <a class="logo" href="<?= BASIS_URL ?>/index.php">
            <!-- Het logo is een SVG-tekening: een oranje cirkel met een wit pootje
                 (een groot kussentje en vier tenen). Er is geen los afbeeldingsbestand nodig. -->
            <svg class="logo-beeld" viewBox="0 0 40 40" aria-hidden="true">
                <circle cx="20" cy="20" r="20" fill="#D97706"/>
                <ellipse cx="20" cy="25.5" rx="7" ry="5.5" fill="#FFFFFF"/>
                <ellipse cx="10.5" cy="19" rx="2.6" ry="3.4" fill="#FFFFFF"/>
                <ellipse cx="16" cy="13.5" rx="2.6" ry="3.6" fill="#FFFFFF"/>
                <ellipse cx="24" cy="13.5" rx="2.6" ry="3.6" fill="#FFFFFF"/>
                <ellipse cx="29.5" cy="19" rx="2.6" ry="3.4" fill="#FFFFFF"/>
            </svg>
            <span>Paw<span class="logo-accent">Pal</span></span>
        </a>
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
                    <a href="<?= BASIS_URL ?>/medewerker/aanvragen.php">Aanvragen</a>
                    <a href="<?= BASIS_URL ?>/medewerker/capaciteit.php">Capaciteit</a>
                    <a href="<?= BASIS_URL ?>/medewerker/dagplanning.php">Dagplanning</a>
                    <a href="<?= BASIS_URL ?>/medewerker/wijzigingslog.php">Wijzigingslog</a>
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
