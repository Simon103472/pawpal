<?php
// Bovenkant van iedere pagina: begin van de HTML, de kop en de navigatie.
// Een pagina zet eerst $paginatitel en laadt daarna dit bestand.
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/functions.php';

$paginatitel = $paginatitel ?? 'PawPal';
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
            <a href="<?= BASIS_URL ?>/index.php">Home</a>
        </nav>
    </div>
</header>
<main class="container">
