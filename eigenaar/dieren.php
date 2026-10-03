<?php
// Dierenprofielen van de eigenaar: bekijken, toevoegen, wijzigen en verwijderen (FE-01).
require_once __DIR__ . '/../includes/auth.php';
$gebruiker = vereis_rol('eigenaar');

/**
 * Kijkt of een dier nog een reservering heeft die vandaag of later is
 * en niet is afgewezen. Zo'n dier mag niet worden verwijderd.
 */
function heeft_open_reservering(int $dier_id): bool
{
    $stmt = db()->prepare(
        "SELECT COUNT(*)
         FROM reserveringen r
         JOIN capaciteit c ON c.id = r.capaciteit_id
         WHERE r.dier_id = ?
           AND r.status IN ('aangevraagd', 'goedgekeurd')
           AND c.datum >= CURDATE()"
    );
    $stmt->execute([$dier_id]);

    return $stmt->fetchColumn() > 0;
}

$fouten = [];
// De waarden die in het formulier staan. id 0 betekent: een nieuw dier.
$formulier = ['id' => 0, 'naam' => '', 'diersoort' => '', 'ras' => '', 'geboortedatum' => ''];

// ---------- Een verstuurd formulier verwerken ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    controleer_csrf();

    $actie = $_POST['actie'] ?? '';
    $dier_id = (int) ($_POST['dier_id'] ?? 0);

    // Bij wijzigen en verwijderen: is dit dier wel van deze eigenaar?
    if ($dier_id > 0 && eigen_dier($dier_id, $gebruiker['id']) === null) {
        zet_melding('Dit dier is niet gevonden.', 'fout');
        doorsturen('/eigenaar/dieren.php');
    }

    if ($actie === 'verwijderen' && $dier_id > 0) {
        if (heeft_open_reservering($dier_id)) {
            zet_melding('Dit dier heeft nog een reservering en kan daarom niet worden verwijderd.', 'fout');
        } else {
            // De verzorging en oude reserveringen verdwijnen vanzelf mee (CASCADE in de database)
            $stmt = db()->prepare('DELETE FROM dieren WHERE id = ? AND eigenaar_id = ?');
            $stmt->execute([$dier_id, $gebruiker['id']]);
            zet_melding('Het dier is verwijderd.');
        }
        doorsturen('/eigenaar/dieren.php');
    }

    if ($actie === 'opslaan') {
        // trim() haalt spaties aan het begin en eind weg
        $formulier = [
            'id'            => $dier_id,
            'naam'          => trim((string) ($_POST['naam'] ?? '')),
            'diersoort'     => (string) ($_POST['diersoort'] ?? ''),
            'ras'           => trim((string) ($_POST['ras'] ?? '')),
            'geboortedatum' => trim((string) ($_POST['geboortedatum'] ?? '')),
        ];

        // Controle op de server: de browser-controle kan worden omzeild
        if ($formulier['naam'] === '') {
            $fouten[] = 'Vul de naam van het dier in.';
        } elseif (mb_strlen($formulier['naam']) > 100) {
            $fouten[] = 'De naam mag maximaal 100 tekens zijn.';
        }

        if (!in_array($formulier['diersoort'], DIERSOORTEN, true)) {
            $fouten[] = 'Kies een diersoort uit de lijst.';
        }

        if (mb_strlen($formulier['ras']) > 100) {
            $fouten[] = 'Het ras mag maximaal 100 tekens zijn.';
        }

        if ($formulier['geboortedatum'] !== '') {
            if (!geldige_datum($formulier['geboortedatum'])) {
                $fouten[] = 'Vul een geldige geboortedatum in.';
            } elseif ($formulier['geboortedatum'] > date('Y-m-d')) {
                $fouten[] = 'De geboortedatum mag niet in de toekomst liggen.';
            }
        }

        if (!$fouten) {
            // Lege keuzevelden slaan we op als NULL (geen waarde)
            $ras = $formulier['ras'] !== '' ? $formulier['ras'] : null;
            $geboortedatum = $formulier['geboortedatum'] !== '' ? $formulier['geboortedatum'] : null;

            if ($dier_id > 0) {
                $stmt = db()->prepare(
                    'UPDATE dieren SET naam = ?, diersoort = ?, ras = ?, geboortedatum = ?
                     WHERE id = ? AND eigenaar_id = ?'
                );
                $stmt->execute([$formulier['naam'], $formulier['diersoort'], $ras, $geboortedatum, $dier_id, $gebruiker['id']]);
                zet_melding('De gegevens van ' . $formulier['naam'] . ' zijn gewijzigd.');
            } else {
                $stmt = db()->prepare(
                    'INSERT INTO dieren (eigenaar_id, naam, diersoort, ras, geboortedatum) VALUES (?, ?, ?, ?, ?)'
                );
                $stmt->execute([$gebruiker['id'], $formulier['naam'], $formulier['diersoort'], $ras, $geboortedatum]);
                zet_melding($formulier['naam'] . ' is toegevoegd.');
            }
            doorsturen('/eigenaar/dieren.php');
        }
    }
}

// ---------- Gegevens ophalen voor de pagina ----------

// ?bewerk=5 in de URL: het formulier vullen met de gegevens van dat dier
$bewerk_id = (int) ($_GET['bewerk'] ?? 0);
if ($bewerk_id > 0 && !$fouten) {
    $dier = eigen_dier($bewerk_id, $gebruiker['id']);
    if ($dier === null) {
        zet_melding('Dit dier is niet gevonden.', 'fout');
        doorsturen('/eigenaar/dieren.php');
    }
    $formulier = [
        'id'            => $dier['id'],
        'naam'          => $dier['naam'],
        'diersoort'     => $dier['diersoort'],
        'ras'           => $dier['ras'] ?? '',
        'geboortedatum' => $dier['geboortedatum'] ?? '',
    ];
}

// ?verwijder=5 in de URL: eerst vragen of de eigenaar het zeker weet
$te_verwijderen = null;
$verwijder_id = (int) ($_GET['verwijder'] ?? 0);
if ($verwijder_id > 0) {
    $te_verwijderen = eigen_dier($verwijder_id, $gebruiker['id']);
    if ($te_verwijderen === null) {
        zet_melding('Dit dier is niet gevonden.', 'fout');
        doorsturen('/eigenaar/dieren.php');
    }
}

// Alleen de dieren van de ingelogde eigenaar ophalen
$stmt = db()->prepare('SELECT * FROM dieren WHERE eigenaar_id = ? ORDER BY naam');
$stmt->execute([$gebruiker['id']]);
$dieren = $stmt->fetchAll();

$paginatitel = 'Mijn dieren';
require_once __DIR__ . '/../includes/header.php';
?>

<h1>Mijn dieren</h1>

<?php if ($te_verwijderen !== null): ?>
    <div class="melding melding-let-op">
        <p>Weet je zeker dat je <strong><?= e($te_verwijderen['naam']) ?></strong> wilt verwijderen? De verzorgingsgegevens van dit dier worden ook verwijderd.</p>
        <form method="post" action="<?= BASIS_URL ?>/eigenaar/dieren.php">
            <?= csrf_veld() ?>
            <input type="hidden" name="actie" value="verwijderen">
            <input type="hidden" name="dier_id" value="<?= e((string) $te_verwijderen['id']) ?>">
            <button class="knop knop-gevaar" type="submit">Ja, verwijderen</button>
            <a class="knop knop-licht" href="<?= BASIS_URL ?>/eigenaar/dieren.php">Annuleren</a>
        </form>
    </div>
<?php endif; ?>

<?php if (!$dieren): ?>
    <p class="melding">Je hebt nog geen dieren. Voeg hieronder je eerste dier toe.</p>
<?php else: ?>
    <div class="kaarten">
        <?php foreach ($dieren as $dier): ?>
            <section class="kaart">
                <h2><?= e($dier['naam']) ?></h2>
                <p>
                    Diersoort: <?= e($dier['diersoort']) ?><br>
                    Ras: <?= $dier['ras'] !== null ? e($dier['ras']) : 'niet ingevuld' ?><br>
                    Geboortedatum: <?= $dier['geboortedatum'] !== null ? e(datum_nl($dier['geboortedatum'])) : 'niet ingevuld' ?>
                </p>
                <p class="kaart-acties">
                    <a href="<?= BASIS_URL ?>/eigenaar/verzorging.php?dier=<?= e((string) $dier['id']) ?>">Verzorging</a>
                    <a href="<?= BASIS_URL ?>/eigenaar/dieren.php?bewerk=<?= e((string) $dier['id']) ?>#formulier">Wijzigen</a>
                    <a href="<?= BASIS_URL ?>/eigenaar/dieren.php?verwijder=<?= e((string) $dier['id']) ?>">Verwijderen</a>
                </p>
            </section>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<h2 id="formulier"><?= $formulier['id'] > 0 ? 'Dier wijzigen' : 'Dier toevoegen' ?></h2>

<?php if ($fouten): ?>
    <div class="melding melding-fout" role="alert">
        <ul>
            <?php foreach ($fouten as $fout): ?>
                <li><?= e($fout) ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<form class="formulier" method="post" action="<?= BASIS_URL ?>/eigenaar/dieren.php#formulier">
    <?= csrf_veld() ?>
    <input type="hidden" name="actie" value="opslaan">
    <input type="hidden" name="dier_id" value="<?= e((string) $formulier['id']) ?>">

    <label for="naam">Naam</label>
    <input type="text" id="naam" name="naam" value="<?= e($formulier['naam']) ?>" maxlength="100" required>

    <label for="diersoort">Diersoort</label>
    <select id="diersoort" name="diersoort" required>
        <option value="">Kies een diersoort</option>
        <?php foreach (DIERSOORTEN as $soort): ?>
            <option value="<?= e($soort) ?>" <?= $formulier['diersoort'] === $soort ? 'selected' : '' ?>><?= e($soort) ?></option>
        <?php endforeach; ?>
    </select>

    <label for="ras">Ras (niet verplicht)</label>
    <input type="text" id="ras" name="ras" value="<?= e($formulier['ras']) ?>" maxlength="100">

    <label for="geboortedatum">Geboortedatum (niet verplicht)</label>
    <input type="date" id="geboortedatum" name="geboortedatum" value="<?= e($formulier['geboortedatum']) ?>" max="<?= date('Y-m-d') ?>">

    <button class="knop" type="submit">Opslaan</button>
    <?php if ($formulier['id'] > 0): ?>
        <a class="knop knop-licht" href="<?= BASIS_URL ?>/eigenaar/dieren.php">Annuleren</a>
    <?php endif; ?>
</form>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
