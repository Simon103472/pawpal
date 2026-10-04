<?php
// Capaciteit beheren: per datum, dienst, locatie en diersoort het aantal plekken instellen (FE-04).
require_once __DIR__ . '/../includes/auth.php';
$gebruiker = vereis_rol('medewerker');

/**
 * Slaat het aantal plekken op voor een combinatie van datum, dienst, locatie en diersoort.
 *
 * Bestaat de combinatie al, dan wordt het aantal aangepast. Anders komt er een nieuwe rij.
 * Invoer: de vier keuzes en het maximale aantal plekken.
 * Uitvoer: null als het gelukt is, anders een foutmelding voor de gebruiker.
 */
function sla_capaciteit_op(string $datum, string $dienst, string $locatie, string $diersoort, int $max_plaatsen): ?string
{
    $pdo = db();

    try {
        // Een transactie: alle stappen hieronder lukken samen, of geen enkele
        $pdo->beginTransaction();

        // FOR UPDATE zet de rij op slot tot de transactie klaar is. Zo kan er
        // niet tegelijk een reservering bijkomen terwijl wij het aantal aanpassen.
        $stmt = $pdo->prepare(
            'SELECT id FROM capaciteit
             WHERE datum = ? AND dienst = ? AND locatie = ? AND diersoort = ?
             FOR UPDATE'
        );
        $stmt->execute([$datum, $dienst, $locatie, $diersoort]);
        $capaciteit_id = (int) $stmt->fetchColumn();

        if ($capaciteit_id > 0) {
            // Het aantal mag niet lager worden dan wat al gereserveerd is
            $bezet = bezette_plaatsen($capaciteit_id);
            if ($max_plaatsen < $bezet) {
                $pdo->rollBack();
                return 'Het aantal plekken kan niet lager zijn dan het aantal bezette plekken (' . $bezet . ').';
            }

            $stmt = $pdo->prepare('UPDATE capaciteit SET max_plaatsen = ? WHERE id = ?');
            $stmt->execute([$max_plaatsen, $capaciteit_id]);
        } else {
            $stmt = $pdo->prepare(
                'INSERT INTO capaciteit (datum, dienst, locatie, diersoort, max_plaatsen) VALUES (?, ?, ?, ?, ?)'
            );
            $stmt->execute([$datum, $dienst, $locatie, $diersoort, $max_plaatsen]);
        }

        $pdo->commit();
        return null;
    } catch (PDOException $fout) {
        // Ging er iets mis, dan draaien we alles terug
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log('PawPal capaciteit opslaan: ' . $fout->getMessage());
        return 'Opslaan is niet gelukt. Probeer het opnieuw.';
    }
}

$fouten = [];
$formulier = ['datum' => '', 'dienst' => '', 'locatie' => $gebruiker['locatie'], 'diersoort' => '', 'max_plaatsen' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    controleer_csrf();

    $formulier = [
        'datum'        => trim((string) ($_POST['datum'] ?? '')),
        'dienst'       => (string) ($_POST['dienst'] ?? ''),
        'locatie'      => (string) ($_POST['locatie'] ?? ''),
        'diersoort'    => (string) ($_POST['diersoort'] ?? ''),
        'max_plaatsen' => trim((string) ($_POST['max_plaatsen'] ?? '')),
    ];

    if (!geldige_datum($formulier['datum'])) {
        $fouten[] = 'Vul een geldige datum in.';
    } elseif ($formulier['datum'] < date('Y-m-d')) {
        $fouten[] = 'De datum mag niet in het verleden liggen.';
    }

    if (!in_array($formulier['dienst'], DIENSTEN, true)) {
        $fouten[] = 'Kies een dienst uit de lijst.';
    }

    if (!in_array($formulier['locatie'], LOCATIES, true)) {
        $fouten[] = 'Kies een locatie uit de lijst.';
    }

    if (!in_array($formulier['diersoort'], DIERSOORTEN, true)) {
        $fouten[] = 'Kies een diersoort uit de lijst.';
    }

    // ctype_digit() is alleen true als de tekst uit cijfers bestaat:
    // dus geen min-teken, geen komma en geen letters
    if (!ctype_digit($formulier['max_plaatsen']) || (int) $formulier['max_plaatsen'] > 100) {
        $fouten[] = 'Vul bij het aantal plekken een heel getal in van 0 tot en met 100.';
    }

    if (!$fouten) {
        $fout = sla_capaciteit_op(
            $formulier['datum'],
            $formulier['dienst'],
            $formulier['locatie'],
            $formulier['diersoort'],
            (int) $formulier['max_plaatsen']
        );

        if ($fout === null) {
            zet_melding('De capaciteit is opgeslagen.');
            doorsturen('/medewerker/capaciteit.php');
        }
        $fouten[] = $fout;
    }
}

// ?bewerk=5 in de URL: het formulier vullen met een bestaande rij
$bewerk_id = (int) ($_GET['bewerk'] ?? 0);
if ($bewerk_id > 0 && !$fouten) {
    $stmt = db()->prepare('SELECT * FROM capaciteit WHERE id = ?');
    $stmt->execute([$bewerk_id]);
    $rij = $stmt->fetch();
    if ($rij) {
        $formulier = [
            'datum'        => $rij['datum'],
            'dienst'       => $rij['dienst'],
            'locatie'      => $rij['locatie'],
            'diersoort'    => $rij['diersoort'],
            'max_plaatsen' => (string) $rij['max_plaatsen'],
        ];
    }
}

$overzicht = capaciteit_overzicht();

$paginatitel = 'Capaciteit';
require_once __DIR__ . '/../includes/header.php';
?>

<h1>Capaciteit</h1>

<h2>Overzicht vanaf vandaag</h2>

<?php if (!$overzicht): ?>
    <p class="melding">Er is nog geen capaciteit ingesteld voor vandaag of later.</p>
<?php else: ?>
    <div class="tabel-omslag">
        <table class="tabel">
            <thead>
                <tr>
                    <th>Datum</th>
                    <th>Dienst</th>
                    <th>Locatie</th>
                    <th>Diersoort</th>
                    <th>Plekken</th>
                    <th>Bezet</th>
                    <th>Vrij</th>
                    <th>Actie</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($overzicht as $rij): ?>
                    <tr>
                        <td><?= e(datum_nl($rij['datum'])) ?></td>
                        <td><?= e($rij['dienst']) ?></td>
                        <td><?= e($rij['locatie']) ?></td>
                        <td><?= e($rij['diersoort']) ?></td>
                        <td><?= e((string) $rij['max_plaatsen']) ?></td>
                        <td><?= e((string) $rij['bezet']) ?></td>
                        <td><?= $rij['vrij'] > 0 ? e((string) $rij['vrij']) : '<strong>vol</strong>' ?></td>
                        <td><a href="<?= BASIS_URL ?>/medewerker/capaciteit.php?bewerk=<?= e((string) $rij['id']) ?>#formulier">Wijzigen</a></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>

<h2 id="formulier">Capaciteit instellen</h2>
<p>Bestaat de combinatie van datum, dienst, locatie en diersoort al, dan wordt het aantal plekken aangepast.</p>

<?php if ($fouten): ?>
    <div class="melding melding-fout" role="alert">
        <ul>
            <?php foreach ($fouten as $fout): ?>
                <li><?= e($fout) ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<form class="formulier" method="post" action="<?= BASIS_URL ?>/medewerker/capaciteit.php#formulier">
    <?= csrf_veld() ?>

    <label for="datum">Datum</label>
    <input type="date" id="datum" name="datum" value="<?= e($formulier['datum']) ?>" min="<?= date('Y-m-d') ?>" required>

    <label for="dienst">Dienst</label>
    <select id="dienst" name="dienst" required>
        <option value="">Kies een dienst</option>
        <?php foreach (DIENSTEN as $dienst): ?>
            <option value="<?= e($dienst) ?>" <?= $formulier['dienst'] === $dienst ? 'selected' : '' ?>><?= e($dienst) ?></option>
        <?php endforeach; ?>
    </select>

    <label for="locatie">Locatie</label>
    <select id="locatie" name="locatie" required>
        <?php foreach (LOCATIES as $locatie): ?>
            <option value="<?= e($locatie) ?>" <?= $formulier['locatie'] === $locatie ? 'selected' : '' ?>><?= e($locatie) ?></option>
        <?php endforeach; ?>
    </select>

    <label for="diersoort">Diersoort</label>
    <select id="diersoort" name="diersoort" required>
        <option value="">Kies een diersoort</option>
        <?php foreach (DIERSOORTEN as $soort): ?>
            <option value="<?= e($soort) ?>" <?= $formulier['diersoort'] === $soort ? 'selected' : '' ?>><?= e($soort) ?></option>
        <?php endforeach; ?>
    </select>

    <label for="max_plaatsen">Aantal plekken</label>
    <input type="number" id="max_plaatsen" name="max_plaatsen" value="<?= e($formulier['max_plaatsen']) ?>" min="0" max="100" step="1" required>

    <button class="knop" type="submit">Opslaan</button>
</form>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
