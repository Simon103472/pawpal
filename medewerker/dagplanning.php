<?php
// Dagplanning: de goedgekeurde reserveringen van een dag, te filteren op locatie en dienst (FE-06).
require_once __DIR__ . '/../includes/auth.php';
$gebruiker = vereis_rol('medewerker');

// De filters komen uit de URL, bijvoorbeeld dagplanning.php?datum=2026-10-08&locatie=Noord&dienst=ochtend
// Zonder keuze: vandaag en de eigen locatie van de medewerker.
$datum = (string) ($_GET['datum'] ?? date('Y-m-d'));
$locatie = (string) ($_GET['locatie'] ?? $gebruiker['locatie']);
$dienst = (string) ($_GET['dienst'] ?? '');

$fouten = [];
if (!geldige_datum($datum)) {
    $fouten[] = 'Vul een geldige datum in.';
    $datum = date('Y-m-d');
}

// Een onbekende waarde behandelen we als "geen filter" (lege tekst = alles tonen)
if (!in_array($locatie, LOCATIES, true)) {
    $locatie = '';
}
if (!in_array($dienst, DIENSTEN, true)) {
    $dienst = '';
}

// De query opbouwen. Elk gekozen filter voegt een voorwaarde met een vraagteken toe;
// de waarde zelf gaat apart mee in execute() (prepared statement).
$sql = "SELECT d.id AS dier_id, d.naam AS dier_naam, d.diersoort,
               g.naam AS eigenaar_naam,
               c.datum, c.dienst, c.locatie
        FROM reserveringen r
        JOIN dieren d ON d.id = r.dier_id
        JOIN gebruikers g ON g.id = d.eigenaar_id
        JOIN capaciteit c ON c.id = r.capaciteit_id
        WHERE r.status = 'goedgekeurd' AND c.datum = ?";
$waarden = [$datum];

if ($locatie !== '') {
    $sql .= ' AND c.locatie = ?';
    $waarden[] = $locatie;
}
if ($dienst !== '') {
    $sql .= ' AND c.dienst = ?';
    $waarden[] = $dienst;
}

$sql .= ' ORDER BY c.dienst, c.locatie, d.naam';

$stmt = db()->prepare($sql);
$stmt->execute($waarden);
$planning = $stmt->fetchAll();

$paginatitel = 'Dagplanning';
require_once __DIR__ . '/../includes/header.php';
?>

<h1>Dagplanning</h1>
<p>De dagplanning toont de goedgekeurde reserveringen van de gekozen dag.</p>

<?php if ($fouten): ?>
    <div class="melding melding-fout" role="alert">
        <ul>
            <?php foreach ($fouten as $fout): ?>
                <li><?= e($fout) ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<!-- method="get": de filters komen in de URL te staan; er wordt niets opgeslagen -->
<form class="formulier" method="get" action="<?= BASIS_URL ?>/medewerker/dagplanning.php">
    <label for="datum">Datum</label>
    <input type="date" id="datum" name="datum" value="<?= e($datum) ?>" required>

    <label for="locatie">Locatie</label>
    <select id="locatie" name="locatie">
        <option value="">Alle locaties</option>
        <?php foreach (LOCATIES as $keuze): ?>
            <option value="<?= e($keuze) ?>" <?= $locatie === $keuze ? 'selected' : '' ?>><?= e($keuze) ?></option>
        <?php endforeach; ?>
    </select>

    <label for="dienst">Dienst</label>
    <select id="dienst" name="dienst">
        <option value="">Alle diensten</option>
        <?php foreach (DIENSTEN as $keuze): ?>
            <option value="<?= e($keuze) ?>" <?= $dienst === $keuze ? 'selected' : '' ?>><?= e($keuze) ?></option>
        <?php endforeach; ?>
    </select>

    <button class="knop" type="submit">Filteren</button>
</form>

<h2>Planning van <?= e(datum_nl($datum)) ?></h2>

<?php if (!$planning): ?>
    <p class="melding">Er zijn geen goedgekeurde reserveringen voor deze dag en deze filters.</p>
<?php else: ?>
    <div class="tabel-omslag">
        <table class="tabel">
            <thead>
                <tr>
                    <th>Dienst</th>
                    <th>Locatie</th>
                    <th>Dier</th>
                    <th>Eigenaar</th>
                    <th>Verzorging</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($planning as $rij): ?>
                    <tr>
                        <td><?= e($rij['dienst']) ?></td>
                        <td><?= e($rij['locatie']) ?></td>
                        <td><?= e($rij['dier_naam']) ?> (<?= e($rij['diersoort']) ?>)</td>
                        <td><?= e($rij['eigenaar_naam']) ?></td>
                        <td>
                            <?php if (medewerker_mag_verzorging_zien($rij['dier_id'], $gebruiker['locatie'])): ?>
                                <a href="<?= BASIS_URL ?>/medewerker/verzorging.php?dier=<?= e((string) $rij['dier_id']) ?>">Bekijken</a>
                            <?php else: ?>
                                Geen toegang
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
