<?php
// Reserveringsaanvragen bekijken, goedkeuren en afwijzen (FE-05).
// Alle medewerkers mogen aanvragen van beide locaties beoordelen.
require_once __DIR__ . '/../includes/auth.php';
$gebruiker = vereis_rol('medewerker');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    controleer_csrf();

    $reservering_id = (int) ($_POST['reservering_id'] ?? 0);
    $actie = $_POST['actie'] ?? '';

    // De knop bepaalt de nieuwe status. Een andere waarde dan deze twee weigeren we.
    $nieuwe_status = null;
    if ($actie === 'goedkeuren') {
        $nieuwe_status = 'goedgekeurd';
    } elseif ($actie === 'afwijzen') {
        $nieuwe_status = 'afgewezen';
    }

    if ($nieuwe_status === null || $reservering_id <= 0) {
        zet_melding('Deze actie is ongeldig.', 'fout');
    } else {
        // "AND status = 'aangevraagd'": alleen een open aanvraag kan worden beoordeeld.
        // Klikken twee medewerkers tegelijk, dan past alleen de eerste de status aan.
        $stmt = db()->prepare("UPDATE reserveringen SET status = ? WHERE id = ? AND status = 'aangevraagd'");
        $stmt->execute([$nieuwe_status, $reservering_id]);

        // rowCount() geeft het aantal rijen dat echt is aangepast
        if ($stmt->rowCount() === 1) {
            zet_melding('De aanvraag is ' . $nieuwe_status . '.');
        } else {
            zet_melding('Deze aanvraag is al beoordeeld of bestaat niet meer.', 'fout');
        }
    }

    doorsturen('/medewerker/aanvragen.php');
}

// Alle reserveringen ophalen met het dier, de eigenaar en het opvangmoment.
// Verzorgingsgegevens halen we hier bewust niet op (FE-07).
$stmt = db()->query(
    "SELECT r.id, r.status, r.aangemaakt_op,
            d.naam AS dier_naam, d.diersoort,
            g.naam AS eigenaar_naam,
            c.datum, c.dienst, c.locatie
     FROM reserveringen r
     JOIN dieren d ON d.id = r.dier_id
     JOIN gebruikers g ON g.id = d.eigenaar_id
     JOIN capaciteit c ON c.id = r.capaciteit_id
     ORDER BY c.datum, c.dienst, c.locatie, r.aangemaakt_op"
);

// De lijst splitsen: open aanvragen en aanvragen die al beoordeeld zijn
$open = [];
$beoordeeld = [];
foreach ($stmt->fetchAll() as $rij) {
    if ($rij['status'] === 'aangevraagd') {
        $open[] = $rij;
    } else {
        $beoordeeld[] = $rij;
    }
}

$paginatitel = 'Aanvragen';
require_once __DIR__ . '/../includes/header.php';
?>

<h1>Aanvragen</h1>

<h2>Open aanvragen</h2>

<?php if (!$open): ?>
    <p class="melding">Er zijn geen open aanvragen.</p>
<?php else: ?>
    <div class="tabel-omslag">
        <table class="tabel">
            <thead>
                <tr>
                    <th>Datum</th>
                    <th>Dienst</th>
                    <th>Locatie</th>
                    <th>Dier</th>
                    <th>Eigenaar</th>
                    <th>Status</th>
                    <th>Actie</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($open as $rij): ?>
                    <tr>
                        <td><?= e(datum_nl($rij['datum'])) ?></td>
                        <td><?= e($rij['dienst']) ?></td>
                        <td><?= e($rij['locatie']) ?></td>
                        <td><?= e($rij['dier_naam']) ?> (<?= e($rij['diersoort']) ?>)</td>
                        <td><?= e($rij['eigenaar_naam']) ?></td>
                        <td><span class="status status-<?= e($rij['status']) ?>"><?= e(ucfirst($rij['status'])) ?></span></td>
                        <td>
                            <!-- Een formulier per rij; de aangeklikte knop stuurt zijn eigen waarde mee als "actie" -->
                            <form class="rij-acties" method="post" action="<?= BASIS_URL ?>/medewerker/aanvragen.php">
                                <?= csrf_veld() ?>
                                <input type="hidden" name="reservering_id" value="<?= e((string) $rij['id']) ?>">
                                <button class="knop knop-klein" type="submit" name="actie" value="goedkeuren">Goedkeuren</button>
                                <button class="knop knop-klein knop-gevaar" type="submit" name="actie" value="afwijzen">Afwijzen</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>

<h2>Beoordeelde aanvragen</h2>

<?php if (!$beoordeeld): ?>
    <p class="melding">Er zijn nog geen beoordeelde aanvragen.</p>
<?php else: ?>
    <div class="tabel-omslag">
        <table class="tabel">
            <thead>
                <tr>
                    <th>Datum</th>
                    <th>Dienst</th>
                    <th>Locatie</th>
                    <th>Dier</th>
                    <th>Eigenaar</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($beoordeeld as $rij): ?>
                    <tr>
                        <td><?= e(datum_nl($rij['datum'])) ?></td>
                        <td><?= e($rij['dienst']) ?></td>
                        <td><?= e($rij['locatie']) ?></td>
                        <td><?= e($rij['dier_naam']) ?> (<?= e($rij['diersoort']) ?>)</td>
                        <td><?= e($rij['eigenaar_naam']) ?></td>
                        <td><span class="status status-<?= e($rij['status']) ?>"><?= e(ucfirst($rij['status'])) ?></span></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
