<?php
// Startpagina van PawPal.
$paginatitel = 'Home';
require_once __DIR__ . '/includes/header.php';

// header.php heeft de sessie al gestart, dus we weten of er iemand is ingelogd
$gebruiker = huidige_gebruiker();
?>

<section class="held">
    <h1>Welkom bij PawPal</h1>
    <p class="inleiding">De planner van Dierenopvangservice Happy Tails voor dierenprofielen, verzorgingsgegevens en opvangreserveringen.</p>
    <?php if ($gebruiker === null): ?>
        <a class="knop knop-accent" href="<?= BASIS_URL ?>/login.php">Inloggen</a>
    <?php else: ?>
        <a class="knop knop-accent" href="<?= BASIS_URL . dashboard_pad($gebruiker['rol']) ?>">Naar mijn dashboard</a>
    <?php endif; ?>
</section>

<div class="kaarten">
    <section class="kaart">
        <h2>Voor huisdiereigenaren</h2>
        <p>Beheer de profielen van je dieren, leg voeding en bijzonderheden vast en vraag een opvangplek aan.</p>
    </section>
    <section class="kaart">
        <h2>Voor opvangmedewerkers</h2>
        <p>Stel de capaciteit in, beoordeel aanvragen en bekijk de dagplanning per locatie en dienst.</p>
    </section>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
