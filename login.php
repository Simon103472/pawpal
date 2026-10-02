<?php
// Loginpagina voor eigenaren en medewerkers (TE-03).
require_once __DIR__ . '/includes/auth.php';

// Wie al is ingelogd, hoeft het formulier niet te zien
$gebruiker = huidige_gebruiker();
if ($gebruiker !== null) {
    doorsturen(dashboard_pad($gebruiker['rol']));
}

$fout = '';
$email = '';

// $_SERVER['REQUEST_METHOD'] is 'POST' als het formulier is verstuurd
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    controleer_csrf();

    // $_POST bevat de ingevulde velden van het formulier
    $email = trim((string) ($_POST['email'] ?? ''));
    $wachtwoord = (string) ($_POST['wachtwoord'] ?? '');

    if ($email === '' || $wachtwoord === '') {
        $fout = 'Vul je e-mailadres en wachtwoord in.';
    } elseif (inloggen($email, $wachtwoord)) {
        doorsturen(dashboard_pad(huidige_gebruiker()['rol']));
    } else {
        // Bewust een algemene melding: we verraden niet of het e-mailadres bestaat
        $fout = 'Inloggegevens zijn onjuist.';
    }
}

$paginatitel = 'Inloggen';
require_once __DIR__ . '/includes/header.php';
?>

<h1>Inloggen</h1>

<?php if ($fout !== ''): ?>
    <p class="melding melding-fout" role="alert"><?= e($fout) ?></p>
<?php endif; ?>

<form class="formulier" method="post" action="<?= BASIS_URL ?>/login.php">
    <?= csrf_veld() ?>

    <label for="email">E-mailadres</label>
    <input type="email" id="email" name="email" value="<?= e($email) ?>" required autocomplete="username">

    <label for="wachtwoord">Wachtwoord</label>
    <input type="password" id="wachtwoord" name="wachtwoord" required autocomplete="current-password">

    <button class="knop" type="submit">Inloggen</button>
</form>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
