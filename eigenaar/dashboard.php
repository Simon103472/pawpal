<?php
// Dashboard van de huisdiereigenaar. Alleen bereikbaar met de rol 'eigenaar'.
require_once __DIR__ . '/../includes/auth.php';
$gebruiker = vereis_rol('eigenaar');

$paginatitel = 'Dashboard';
require_once __DIR__ . '/../includes/header.php';
?>

<h1>Welkom, <?= e($gebruiker['naam']) ?></h1>
<p class="inleiding">Je bent ingelogd als huisdiereigenaar.</p>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
