<?php
// Opvang reserveren en eigen reserveringen bekijken (FE-03).
// Stap 1: de eigenaar kiest een dier. Stap 2: hij kiest een opvangmoment met vrije plekken.
require_once __DIR__ . '/../includes/auth.php';
$gebruiker = vereis_rol('eigenaar');

/**
 * Maakt een reservering voor een dier, maar alleen als er nog plek is.
 *
 * Waarom een transactie met FOR UPDATE: als twee eigenaren tegelijk de laatste
 * plek willen, moet de tweede wachten tot de eerste klaar is. Daarna ziet de
 * tweede dat de opvang vol is. Zo kan er nooit een plek te veel worden vergeven.
 * Invoer: de rij van het dier (al gecontroleerd op eigenaar) en het id van de capaciteitsrij.
 * Uitvoer: null als de reservering is opgeslagen, anders een foutmelding.
 */
function maak_reservering(array $dier, int $capaciteit_id): ?string
{
    $pdo = db();

    try {
        $pdo->beginTransaction();

        // De capaciteitsrij op slot zetten tot de transactie klaar is
        $stmt = $pdo->prepare('SELECT * FROM capaciteit WHERE id = ? FOR UPDATE');
        $stmt->execute([$capaciteit_id]);
        $capaciteit = $stmt->fetch();

        $fout = null;

        if (!$capaciteit || $capaciteit['diersoort'] !== $dier['diersoort'] || $capaciteit['datum'] < date('Y-m-d')) {
            $fout = 'Dit opvangmoment is niet beschikbaar voor dit dier.';
        } elseif (bezette_plaatsen($capaciteit_id) >= $capaciteit['max_plaatsen']) {
            $fout = 'Er is geen vrije plaats meer op dit opvangmoment. Kies een ander moment.';
        } else {
            // Heeft dit dier op dezelfde datum en dienst al een reservering (op welke locatie dan ook)?
            // Een afgewezen reservering op precies dit moment telt ook: die kan niet opnieuw worden aangevraagd.
            $stmt = $pdo->prepare(
                "SELECT r.status
                 FROM reserveringen r
                 JOIN capaciteit c ON c.id = r.capaciteit_id
                 WHERE r.dier_id = ?
                   AND c.datum = ? AND c.dienst = ?
                   AND (r.status IN ('aangevraagd', 'goedgekeurd') OR r.capaciteit_id = ?)
                 LIMIT 1"
            );
            $stmt->execute([$dier['id'], $capaciteit['datum'], $capaciteit['dienst'], $capaciteit_id]);
            $bestaande_status = $stmt->fetchColumn();

            if ($bestaande_status === 'afgewezen') {
                $fout = 'De aanvraag voor dit opvangmoment is eerder afgewezen. Kies een ander moment.';
            } elseif ($bestaande_status !== false) {
                $fout = $dier['naam'] . ' heeft op deze datum en dienst al een reservering.';
            }
        }

        if ($fout !== null) {
            $pdo->rollBack();
            return $fout;
        }

        // De status wordt vanzelf 'aangevraagd' (standaardwaarde in de database)
        $stmt = $pdo->prepare('INSERT INTO reserveringen (dier_id, capaciteit_id) VALUES (?, ?)');
        $stmt->execute([$dier['id'], $capaciteit_id]);

        $pdo->commit();
        return null;
    } catch (PDOException $fout) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log('PawPal reserveren: ' . $fout->getMessage());
        return 'De reservering is niet opgeslagen. Probeer het opnieuw.';
    }
}

$fouten = [];

// Het gekozen dier komt uit het formulier (POST) of uit de URL (?dier=1).
// eigen_dier() geeft alleen een dier terug dat van deze eigenaar is.
$dier_id = (int) ($_POST['dier_id'] ?? $_GET['dier'] ?? 0);
$gekozen_dier = null;
if ($dier_id > 0) {
    $gekozen_dier = eigen_dier($dier_id, $gebruiker['id']);
    if ($gekozen_dier === null) {
        zet_melding('Dit dier is niet gevonden.', 'fout');
        doorsturen('/eigenaar/reserveringen.php');
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    controleer_csrf();

    $capaciteit_id = (int) ($_POST['capaciteit_id'] ?? 0);

    if ($gekozen_dier === null) {
        $fouten[] = 'Kies eerst een dier.';
    } elseif ($capaciteit_id <= 0) {
        $fouten[] = 'Kies een opvangmoment.';
    } else {
        $fout = maak_reservering($gekozen_dier, $capaciteit_id);

        if ($fout === null) {
            zet_melding('De reservering voor ' . $gekozen_dier['naam'] . ' is aangevraagd.');
            doorsturen('/eigenaar/reserveringen.php');
        }
        $fouten[] = $fout;
    }
}

// De dieren van de eigenaar, voor de keuzelijst
$stmt = db()->prepare('SELECT id, naam, diersoort FROM dieren WHERE eigenaar_id = ? ORDER BY naam');
$stmt->execute([$gebruiker['id']]);
$dieren = $stmt->fetchAll();

// De opvangmomenten voor de diersoort van het gekozen dier. Volle momenten laten we weg.
$momenten = [];
if ($gekozen_dier !== null) {
    foreach (capaciteit_overzicht($gekozen_dier['diersoort']) as $rij) {
        if ($rij['vrij'] > 0) {
            $momenten[] = $rij;
        }
    }
}

// De reserveringen van de dieren van deze eigenaar (via d.eigenaar_id)
$stmt = db()->prepare(
    'SELECT r.status, d.naam AS dier_naam, c.datum, c.dienst, c.locatie
     FROM reserveringen r
     JOIN dieren d ON d.id = r.dier_id
     JOIN capaciteit c ON c.id = r.capaciteit_id
     WHERE d.eigenaar_id = ?
     ORDER BY c.datum DESC, c.dienst'
);
$stmt->execute([$gebruiker['id']]);
$reserveringen = $stmt->fetchAll();

$paginatitel = 'Reserveringen';
require_once __DIR__ . '/../includes/header.php';
?>

<h1>Reserveringen</h1>

<h2 id="formulier">Opvang reserveren</h2>

<?php if ($fouten): ?>
    <div class="melding melding-fout" role="alert">
        <ul>
            <?php foreach ($fouten as $fout): ?>
                <li><?= e($fout) ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<?php if (!$dieren): ?>
    <p class="melding">Je hebt nog geen dieren. Voeg eerst een dier toe voordat je kunt reserveren.</p>
    <p><a class="knop" href="<?= BASIS_URL ?>/eigenaar/dieren.php#formulier">Dier toevoegen</a></p>
<?php else: ?>

    <!-- Stap 1: een dier kiezen. method="get" zet de keuze in de URL (?dier=1); er wordt niets opgeslagen. -->
    <form class="formulier" method="get" action="<?= BASIS_URL ?>/eigenaar/reserveringen.php">
        <label for="dier">Stap 1: kies een dier</label>
        <select id="dier" name="dier" required>
            <option value="">Kies een dier</option>
            <?php foreach ($dieren as $dier): ?>
                <option value="<?= e((string) $dier['id']) ?>" <?= $gekozen_dier !== null && $gekozen_dier['id'] === $dier['id'] ? 'selected' : '' ?>>
                    <?= e($dier['naam']) ?> (<?= e($dier['diersoort']) ?>)
                </option>
            <?php endforeach; ?>
        </select>
        <button class="knop knop-licht" type="submit">Toon opvangmomenten</button>
    </form>

    <?php if ($gekozen_dier !== null): ?>
        <?php if (!$momenten): ?>
            <p class="melding">Er zijn op dit moment geen vrije opvangplekken voor een <?= e($gekozen_dier['diersoort']) ?>.</p>
        <?php else: ?>
            <!-- Stap 2: een opvangmoment kiezen en de reservering versturen -->
            <form class="formulier" method="post" action="<?= BASIS_URL ?>/eigenaar/reserveringen.php#formulier">
                <?= csrf_veld() ?>
                <input type="hidden" name="dier_id" value="<?= e((string) $gekozen_dier['id']) ?>">

                <label for="capaciteit_id">Stap 2: kies een opvangmoment voor <?= e($gekozen_dier['naam']) ?></label>
                <select id="capaciteit_id" name="capaciteit_id" required>
                    <option value="">Kies datum, dienst en locatie</option>
                    <?php foreach ($momenten as $moment): ?>
                        <option value="<?= e((string) $moment['id']) ?>">
                            <?= e(datum_nl($moment['datum'])) ?>, <?= e($moment['dienst']) ?>, locatie <?= e($moment['locatie']) ?> (<?= e((string) $moment['vrij']) ?> vrij)
                        </option>
                    <?php endforeach; ?>
                </select>

                <button class="knop" type="submit">Reservering aanvragen</button>
            </form>
        <?php endif; ?>
    <?php endif; ?>

<?php endif; ?>

<h2>Mijn reserveringen</h2>

<?php if (!$reserveringen): ?>
    <p class="melding">Je hebt nog geen reserveringen.</p>
<?php else: ?>
    <div class="tabel-omslag">
        <table class="tabel">
            <thead>
                <tr>
                    <th>Dier</th>
                    <th>Datum</th>
                    <th>Dienst</th>
                    <th>Locatie</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($reserveringen as $reservering): ?>
                    <tr>
                        <td data-label="Dier"><?= e($reservering['dier_naam']) ?></td>
                        <td data-label="Datum"><?= e(datum_nl($reservering['datum'])) ?></td>
                        <td data-label="Dienst"><?= e($reservering['dienst']) ?></td>
                        <td data-label="Locatie"><?= e($reservering['locatie']) ?></td>
                        <td data-label="Status"><span class="status status-<?= e($reservering['status']) ?>"><?= e(ucfirst($reservering['status'])) ?></span></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
