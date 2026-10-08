<?php
// Dashboard van de huisdiereigenaar. Alleen bereikbaar met de rol 'eigenaar'.
require_once __DIR__ . '/../includes/auth.php';
$gebruiker = vereis_rol('eigenaar');

// Alleen de dieren van de ingelogde eigenaar ophalen
$stmt = db()->prepare('SELECT id, naam, diersoort FROM dieren WHERE eigenaar_id = ? ORDER BY naam');
$stmt->execute([$gebruiker['id']]);
$dieren = $stmt->fetchAll();

// De reserveringen van deze eigenaar die vandaag of later zijn, geteld per status
$stmt = db()->prepare(
    "SELECT r.status, COUNT(*) AS aantal
     FROM reserveringen r
     JOIN dieren d ON d.id = r.dier_id
     JOIN capaciteit c ON c.id = r.capaciteit_id
     WHERE d.eigenaar_id = ? AND c.datum >= CURDATE()
     GROUP BY r.status"
);
$stmt->execute([$gebruiker['id']]);
// FETCH_KEY_PAIR maakt er een lijst van zoals ['aangevraagd' => 2, 'goedgekeurd' => 1]
$aantallen = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

$paginatitel = 'Dashboard';
require_once __DIR__ . '/../includes/header.php';
?>

<h1>Welkom, <?= e($gebruiker['naam']) ?></h1>
<p class="inleiding">Je bent ingelogd als huisdiereigenaar.</p>

<h2>Mijn dieren</h2>

<?php if (!$dieren): ?>
    <p class="melding">Je hebt nog geen dieren toegevoegd.</p>
    <p><a class="knop" href="<?= BASIS_URL ?>/eigenaar/dieren.php#formulier">Dier toevoegen</a></p>
<?php else: ?>
    <div class="kaarten">
        <?php foreach ($dieren as $dier): ?>
            <section class="kaart">
                <h3><?= e($dier['naam']) ?></h3>
                <p>Diersoort: <?= e($dier['diersoort']) ?></p>
                <p class="kaart-acties">
                    <a href="<?= BASIS_URL ?>/eigenaar/verzorging.php?dier=<?= e((string) $dier['id']) ?>">Verzorging</a>
                </p>
            </section>
        <?php endforeach; ?>
    </div>
    <p><a class="knop" href="<?= BASIS_URL ?>/eigenaar/dieren.php">Mijn dieren beheren</a></p>

    <h2>Mijn reserveringen</h2>
    <?php if (!$aantallen): ?>
        <p class="melding">Je hebt geen reserveringen voor vandaag of later.</p>
    <?php else: ?>
        <p>
            Reserveringen voor vandaag of later:
            <?= e((string) ($aantallen['aangevraagd'] ?? 0)) ?> aangevraagd,
            <?= e((string) ($aantallen['goedgekeurd'] ?? 0)) ?> goedgekeurd,
            <?= e((string) ($aantallen['afgewezen'] ?? 0)) ?> afgewezen.
        </p>
    <?php endif; ?>
    <p><a class="knop" href="<?= BASIS_URL ?>/eigenaar/reserveringen.php">Reserveringen bekijken en opvang reserveren</a></p>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
