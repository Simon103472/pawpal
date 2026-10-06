<?php
// Wijzigingslog: wie heeft wanneer welke verzorgingsgegevens aangepast (FE-08).
require_once __DIR__ . '/../includes/auth.php';
$gebruiker = vereis_rol('medewerker');

// De 100 nieuwste wijzigingen, met de naam van wie de wijziging deed
$stmt = db()->query(
    'SELECT w.aangemaakt_op, w.dier_id, w.dier_naam, w.veld, w.oude_waarde, w.nieuwe_waarde,
            g.naam AS gebruiker_naam
     FROM wijzigingslog w
     JOIN gebruikers g ON g.id = w.gebruiker_id
     ORDER BY w.aangemaakt_op DESC, w.id DESC
     LIMIT 100'
);
$regels = $stmt->fetchAll();

// De oude en nieuwe waarde zijn zelf ook verzorgingsgegevens. Daarom geldt dezelfde
// toegangsregel als bij de verzorging (FE-07). We onthouden het antwoord per dier,
// zodat we de regel niet voor iedere logregel opnieuw hoeven te controleren.
$toegang = [];
foreach ($regels as $regel) {
    $dier_id = $regel['dier_id'];
    if ($dier_id !== null && !isset($toegang[$dier_id])) {
        $toegang[$dier_id] = medewerker_mag_verzorging_zien($dier_id, $gebruiker['locatie']);
    }
}

$paginatitel = 'Wijzigingslog';
require_once __DIR__ . '/../includes/header.php';
?>

<h1>Wijzigingslog</h1>
<p>Hier staat wie wanneer welke verzorgingsgegevens heeft aangepast. De inhoud van een wijziging zie je alleen bij dieren met een goedgekeurde reservering op jouw locatie (<?= e($gebruiker['locatie']) ?>).</p>

<?php if (!$regels): ?>
    <p class="melding">Er zijn nog geen wijzigingen geregistreerd.</p>
<?php else: ?>
    <div class="tabel-omslag">
        <table class="tabel">
            <thead>
                <tr>
                    <th>Wanneer</th>
                    <th>Wie</th>
                    <th>Dier</th>
                    <th>Veld</th>
                    <th>Oude waarde</th>
                    <th>Nieuwe waarde</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($regels as $regel): ?>
                    <?php $mag_inhoud_zien = $regel['dier_id'] !== null && $toegang[$regel['dier_id']]; ?>
                    <tr>
                        <td><?= e(date('d-m-Y H:i', strtotime($regel['aangemaakt_op']))) ?></td>
                        <td><?= e($regel['gebruiker_naam']) ?></td>
                        <td><?= e($regel['dier_naam']) ?><?= $regel['dier_id'] === null ? ' (verwijderd)' : '' ?></td>
                        <td><?= e($regel['veld']) ?></td>
                        <?php if ($mag_inhoud_zien): ?>
                            <td class="tabel-tekst"><?= $regel['oude_waarde'] !== null ? nl2br(e($regel['oude_waarde'])) : '(leeg)' ?></td>
                            <td class="tabel-tekst"><?= $regel['nieuwe_waarde'] !== null ? nl2br(e($regel['nieuwe_waarde'])) : '(leeg)' ?></td>
                        <?php else: ?>
                            <td colspan="2">Afgeschermd</td>
                        <?php endif; ?>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
