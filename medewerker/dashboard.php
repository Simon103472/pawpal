<?php
// Dashboard van de opvangmedewerker. Alleen bereikbaar met de rol 'medewerker'.
require_once __DIR__ . '/../includes/auth.php';
$gebruiker = vereis_rol('medewerker');

$paginatitel = 'Dashboard';
require_once __DIR__ . '/../includes/header.php';
?>

<h1>Welkom, <?= e($gebruiker['naam']) ?></h1>
<p class="inleiding">Je bent ingelogd als opvangmedewerker van locatie <?= e($gebruiker['locatie']) ?>.</p>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
