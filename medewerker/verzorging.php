<?php
// Verzorgingsgegevens van een dier bekijken als medewerker (FE-07).
// Alleen lezen: aanpassen kan alleen de eigenaar.
require_once __DIR__ . '/../includes/auth.php';
$gebruiker = vereis_rol('medewerker');

$dier_id = (int) ($_GET['dier'] ?? 0);

// De toegangsregel: alleen bij een goedgekeurde reservering op de eigen locatie, vandaag of later.
// Het nummer in de URL veranderen helpt dus niet: zonder zo'n reservering volgt deze melding.
if (!medewerker_mag_verzorging_zien($dier_id, $gebruiker['locatie'])) {
    zet_melding('Je hebt geen toegang tot de verzorgingsgegevens van dit dier.', 'fout');
    doorsturen('/medewerker/dagplanning.php');
}

// LEFT JOIN op verzorging: het dier komt ook terug als er nog niets is ingevuld
$stmt = db()->prepare(
    'SELECT d.naam, d.diersoort, d.ras, g.naam AS eigenaar_naam,
            v.voeding, v.contactpersoon, v.bijzonderheden, v.bijgewerkt_op
     FROM dieren d
     JOIN gebruikers g ON g.id = d.eigenaar_id
     LEFT JOIN verzorging v ON v.dier_id = d.id
     WHERE d.id = ?'
);
$stmt->execute([$dier_id]);
$dier = $stmt->fetch();

$paginatitel = 'Verzorging van ' . $dier['naam'];
require_once __DIR__ . '/../includes/header.php';
?>

<h1>Verzorging van <?= e($dier['naam']) ?></h1>
<p><a href="<?= BASIS_URL ?>/medewerker/dagplanning.php">Terug naar de dagplanning</a></p>

<section class="kaart kaart-breed">
    <h2><?= e($dier['naam']) ?> (<?= e($dier['diersoort']) ?>)</h2>
    <p>
        Ras: <?= $dier['ras'] !== null ? e($dier['ras']) : 'niet ingevuld' ?><br>
        Eigenaar: <?= e($dier['eigenaar_naam']) ?>
    </p>

    <?php if ($dier['bijgewerkt_op'] === null): ?>
        <p class="melding">De eigenaar heeft nog geen verzorgingsgegevens ingevuld.</p>
    <?php else: ?>
        <h3>Voeding</h3>
        <!-- nl2br() laat een nieuwe regel in de tekst ook als nieuwe regel zien -->
        <p><?= nl2br(e($dier['voeding'])) ?></p>

        <h3>Contactpersoon</h3>
        <p><?= e($dier['contactpersoon']) ?></p>

        <h3>Bijzonderheden</h3>
        <p><?= $dier['bijzonderheden'] !== null ? nl2br(e($dier['bijzonderheden'])) : 'Geen bijzonderheden' ?></p>

        <p>Laatst bijgewerkt: <?= e(date('d-m-Y H:i', strtotime($dier['bijgewerkt_op']))) ?></p>
    <?php endif; ?>
</section>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
