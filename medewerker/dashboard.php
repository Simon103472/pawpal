<?php
// Dashboard van de opvangmedewerker. Alleen bereikbaar met de rol 'medewerker'.
require_once __DIR__ . '/../includes/auth.php';
$gebruiker = vereis_rol('medewerker');

// Hoeveel aanvragen wachten nog op een beoordeling?
$aantal_open = (int) db()->query("SELECT COUNT(*) FROM reserveringen WHERE status = 'aangevraagd'")->fetchColumn();

$paginatitel = 'Dashboard';
require_once __DIR__ . '/../includes/header.php';
?>

<h1>Welkom, <?= e($gebruiker['naam']) ?></h1>
<p class="inleiding">Je bent ingelogd als opvangmedewerker van locatie <?= e($gebruiker['locatie']) ?>.</p>

<div class="kaarten">
    <section class="kaart">
        <h2>Aanvragen</h2>
        <p>Open aanvragen: <strong><?= e((string) $aantal_open) ?></strong></p>
        <p class="kaart-acties"><a href="<?= BASIS_URL ?>/medewerker/aanvragen.php">Aanvragen beoordelen</a></p>
    </section>
    <section class="kaart">
        <h2>Capaciteit</h2>
        <p>Stel per datum, dienst, locatie en diersoort het aantal plekken in.</p>
        <p class="kaart-acties"><a href="<?= BASIS_URL ?>/medewerker/capaciteit.php">Capaciteit beheren</a></p>
    </section>
    <section class="kaart">
        <h2>Dagplanning</h2>
        <p>Bekijk welke dieren er op een dag komen, per locatie en dienst.</p>
        <p class="kaart-acties"><a href="<?= BASIS_URL ?>/medewerker/dagplanning.php">Dagplanning bekijken</a></p>
    </section>
    <section class="kaart">
        <h2>Wijzigingslog</h2>
        <p>Bekijk wie wanneer verzorgingsgegevens heeft aangepast.</p>
        <p class="kaart-acties"><a href="<?= BASIS_URL ?>/medewerker/wijzigingslog.php">Wijzigingslog bekijken</a></p>
    </section>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
