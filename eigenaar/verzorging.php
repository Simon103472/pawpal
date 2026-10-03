<?php
// Verzorgingsgegevens van een dier bekijken en aanpassen (FE-02).
// De pagina wordt geopend met het nummer van het dier in de URL: verzorging.php?dier=1
require_once __DIR__ . '/../includes/auth.php';
$gebruiker = vereis_rol('eigenaar');

// Is dit dier van de ingelogde eigenaar? Zo niet: terug naar het overzicht (FE-07)
$dier = eigen_dier((int) ($_GET['dier'] ?? 0), $gebruiker['id']);
if ($dier === null) {
    zet_melding('Dit dier is niet gevonden.', 'fout');
    doorsturen('/eigenaar/dieren.php');
}

// De bestaande verzorgingsgegevens ophalen (er is hooguit een rij per dier)
$stmt = db()->prepare('SELECT * FROM verzorging WHERE dier_id = ?');
$stmt->execute([$dier['id']]);
$verzorging = $stmt->fetch();

$fouten = [];
$formulier = [
    'voeding'        => $verzorging['voeding'] ?? '',
    'contactpersoon' => $verzorging['contactpersoon'] ?? '',
    'bijzonderheden' => $verzorging['bijzonderheden'] ?? '',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    controleer_csrf();

    $formulier = [
        'voeding'        => trim((string) ($_POST['voeding'] ?? '')),
        'contactpersoon' => trim((string) ($_POST['contactpersoon'] ?? '')),
        'bijzonderheden' => trim((string) ($_POST['bijzonderheden'] ?? '')),
    ];

    if ($formulier['voeding'] === '') {
        $fouten[] = 'Vul in welke voeding het dier krijgt.';
    } elseif (mb_strlen($formulier['voeding']) > 1000) {
        $fouten[] = 'De voeding mag maximaal 1000 tekens zijn.';
    }

    if ($formulier['contactpersoon'] === '') {
        $fouten[] = 'Vul een contactpersoon in.';
    } elseif (mb_strlen($formulier['contactpersoon']) > 150) {
        $fouten[] = 'De contactpersoon mag maximaal 150 tekens zijn.';
    }

    if (mb_strlen($formulier['bijzonderheden']) > 1000) {
        $fouten[] = 'De bijzonderheden mogen maximaal 1000 tekens zijn.';
    }

    // Bij een fout slaan we niets op: de oude gegevens blijven in de database staan
    if (!$fouten) {
        $bijzonderheden = $formulier['bijzonderheden'] !== '' ? $formulier['bijzonderheden'] : null;

        if ($verzorging) {
            $stmt = db()->prepare(
                'UPDATE verzorging SET voeding = ?, contactpersoon = ?, bijzonderheden = ? WHERE dier_id = ?'
            );
            $stmt->execute([$formulier['voeding'], $formulier['contactpersoon'], $bijzonderheden, $dier['id']]);
        } else {
            $stmt = db()->prepare(
                'INSERT INTO verzorging (dier_id, voeding, contactpersoon, bijzonderheden) VALUES (?, ?, ?, ?)'
            );
            $stmt->execute([$dier['id'], $formulier['voeding'], $formulier['contactpersoon'], $bijzonderheden]);
        }

        zet_melding('De verzorgingsgegevens van ' . $dier['naam'] . ' zijn opgeslagen.');
        doorsturen('/eigenaar/verzorging.php?dier=' . $dier['id']);
    }
}

$paginatitel = 'Verzorging van ' . $dier['naam'];
require_once __DIR__ . '/../includes/header.php';
?>

<h1>Verzorging van <?= e($dier['naam']) ?></h1>
<p><a href="<?= BASIS_URL ?>/eigenaar/dieren.php">Terug naar mijn dieren</a></p>

<?php if (!$verzorging && !$fouten): ?>
    <p class="melding">Voor <?= e($dier['naam']) ?> zijn nog geen verzorgingsgegevens ingevuld.</p>
<?php endif; ?>

<?php if ($fouten): ?>
    <div class="melding melding-fout" role="alert">
        <ul>
            <?php foreach ($fouten as $fout): ?>
                <li><?= e($fout) ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<form class="formulier" method="post" action="<?= BASIS_URL ?>/eigenaar/verzorging.php?dier=<?= e((string) $dier['id']) ?>">
    <?= csrf_veld() ?>

    <label for="voeding">Voeding</label>
    <textarea id="voeding" name="voeding" rows="3" maxlength="1000" required><?= e($formulier['voeding']) ?></textarea>

    <label for="contactpersoon">Contactpersoon (naam en telefoonnummer)</label>
    <input type="text" id="contactpersoon" name="contactpersoon" value="<?= e($formulier['contactpersoon']) ?>" maxlength="150" required>

    <label for="bijzonderheden">Bijzonderheden (niet verplicht)</label>
    <textarea id="bijzonderheden" name="bijzonderheden" rows="3" maxlength="1000"><?= e($formulier['bijzonderheden']) ?></textarea>

    <button class="knop" type="submit">Opslaan</button>
</form>

<?php if ($verzorging): ?>
    <p>Laatst bijgewerkt: <?= e(date('d-m-Y H:i', strtotime($verzorging['bijgewerkt_op']))) ?></p>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
