<?php
/**
 * Piniginės puslapis
 * Sukurta: 2025-11-27
 * Autorius: Rokas Kaziulis
 */

session_start();
require_once 'includes/db.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';

// Patikrinti, ar vartotojas prisijungęs
require_login();

$user = get_current_user();
$transactions = get_user_transactions($user['id']);

$page_title = 'Piniginė';
include 'includes/header.php';
?>

<h2>Jūsų piniginė</h2>

<!-- Balanso kortelė -->
<div class="wallet-card">
    <h2>Dabartinis balansas</h2>
    <div class="wallet-balance">
        <?php echo format_money($user['balansas']); ?>
    </div>
    <p style="opacity: 0.9;">Galite naudoti šias lėšas statymams aukcionuose</p>
</div>

<!-- Statistika -->
<div class="auction-meta" style="margin-bottom: 30px;">
    <?php
    // Skaičiuoti statistiką
    $total_deposits = 0;
    $total_bids = 0;
    $total_refunds = 0;

    foreach ($transactions as $trans) {
        if ($trans['tipas'] === 'papildymas') {
            $total_deposits += $trans['suma'];
        } elseif ($trans['tipas'] === 'statymas') {
            $total_bids += abs($trans['suma']);
        } elseif ($trans['tipas'] === 'grazinimas') {
            $total_refunds += $trans['suma'];
        }
    }
    ?>

    <div class="meta-item">
        <div class="meta-label">Iš viso papildyta</div>
        <div class="meta-value transaction-positive"><?php echo format_money($total_deposits); ?></div>
    </div>

    <div class="meta-item">
        <div class="meta-label">Iš viso pastatyta</div>
        <div class="meta-value transaction-negative"><?php echo format_money($total_bids); ?></div>
    </div>

    <div class="meta-item">
        <div class="meta-label">Grąžinta lėšų</div>
        <div class="meta-value transaction-positive"><?php echo format_money($total_refunds); ?></div>
    </div>

    <div class="meta-item">
        <div class="meta-label">Transakcijų skaičius</div>
        <div class="meta-value"><?php echo count($transactions); ?></div>
    </div>
</div>

<!-- Transakcijų istorija -->
<h3 style="margin-bottom: 20px;">Transakcijų istorija</h3>

<?php if (empty($transactions)): ?>
    <div class="alert alert-info">
        Jūsų transakcijų istorija tuščia.
    </div>
<?php else: ?>
    <table class="transactions-table">
        <thead>
            <tr>
                <th>Data</th>
                <th>Tipas</th>
                <th>Aprašymas</th>
                <th style="text-align: right;">Suma</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($transactions as $trans):
                $suma_class = $trans['suma'] >= 0 ? 'transaction-positive' : 'transaction-negative';

                $tipas_map = [
                    'papildymas' => '💰 Papildymas',
                    'statymas' => '📉 Statymas',
                    'grazinimas' => '🔄 Grąžinimas',
                    'laimejimas' => '🎉 Laimėjimas'
                ];
                $tipas_text = $tipas_map[$trans['tipas']] ?? $trans['tipas'];
                ?>
                <tr>
                    <td><?php echo format_datetime($trans['data_laikas']); ?></td>
                    <td><?php echo $tipas_text; ?></td>
                    <td><?php echo htmlspecialchars($trans['aprasymas']); ?></td>
                    <td style="text-align: right;" class="<?php echo $suma_class; ?>">
                        <?php
                        if ($trans['suma'] >= 0) {
                            echo '+' . format_money($trans['suma']);
                        } else {
                            echo format_money($trans['suma']);
                        }
                        ?>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
<?php endif; ?>

<div class="alert alert-info mt-20">
    <strong>ℹ️ Informacija apie piniginę:</strong>
    <ul style="margin-left: 20px; margin-top: 10px;">
        <li>Pradinis balansas po registracijos: 1000 €</li>
        <li>Papildyti balansą gali tik buhalteris arba administratorius</li>
        <li>Kai būnate pralenktas aukcione, lėšos automatiškai grąžinamos</li>
        <li>Statymo metu lėšos iškart nuskaičiuojamos iš balanso</li>
        <li>Visos transakcijos yra fiksuojamos ir matomos šioje lentelėje</li>
    </ul>
</div>

<?php include 'includes/footer.php'; ?>
