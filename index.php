<?php
// Startpagina van PawPal.
$paginatitel = 'Home';
require_once __DIR__ . '/includes/header.php';
?>

<h1>Welkom bij PawPal</h1>
<p class="inleiding">De planner van Dierenopvangservice Happy Tails voor dierenprofielen, verzorgingsgegevens en opvangreserveringen.</p>

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
