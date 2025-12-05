<?php
/**
 * Administratoriaus skydelis
 * Sukurta: 2025-11-27
 * Autorius: Rokas Kaziulis
 */

session_start();
require_once 'includes/db.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';

// Patikrinti, ar vartotojas yra admin
require_role('admin');

// Apdoroti vartotojo rolės keitimą
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_role'])) {
    $user_id = intval($_POST['user_id']);
    $new_role = clean_input($_POST['new_role']);

    $allowed_roles = ['user', 'moderator', 'accountant', 'admin'];

    if (in_array($new_role, $allowed_roles)) {
        $stmt = $conn->prepare("UPDATE vartotojai SET role = ? WHERE id = ?");
        $stmt->bind_param("si", $new_role, $user_id);

        if ($stmt->execute()) {
            log_audit($_SESSION['user_id'], 'Rolės keitimas', 'Pakeista vartotojo #' . $user_id . ' rolė į: ' . $new_role);
            $_SESSION['success'] = 'Vartotojo rolė sėkmingai pakeista.';
        } else {
            $_SESSION['error'] = 'Įvyko klaida keičiant rolę.';
        }
    }

    header("Location: admin.php");
    exit();
}

// Apdoroti aukciono slėpimą/rodyimą
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toggle_hidden'])) {
    $auction_id = intval($_POST['auction_id']);

    $stmt = $conn->prepare("UPDATE aukcionai SET pasleptas = NOT pasleptas WHERE id = ?");
    $stmt->bind_param("i", $auction_id);

    if ($stmt->execute()) {
        log_audit($_SESSION['user_id'], 'Aukciono slėpimas', 'Pakeista aukciono #' . $auction_id . ' matomumo būsena');
        $_SESSION['success'] = 'Aukciono matomumas pakeistas.';
    } else {
        $_SESSION['error'] = 'Įvyko klaida.';
    }

    header("Location: admin.php");
    exit();
}

// Gauti statistiką
$stats = [];
$stats['total_users'] = $conn->query("SELECT COUNT(*) as count FROM vartotojai")->fetch_assoc()['count'];
$stats['total_auctions'] = $conn->query("SELECT COUNT(*) as count FROM aukcionai")->fetch_assoc()['count'];
$stats['active_auctions'] = $conn->query("SELECT COUNT(*) as count FROM aukcionai WHERE busena = 'aktyvus'")->fetch_assoc()['count'];
$stats['total_bids'] = $conn->query("SELECT COUNT(*) as count FROM statymai")->fetch_assoc()['count'];

// Gauti visus vartotojus
$users_query = "SELECT * FROM vartotojai ORDER BY registracijos_data DESC";
$users_result = $conn->query($users_query);
$users = $users_result->fetch_all(MYSQLI_ASSOC);

// Gauti visus aukcionus
$auctions_query = "SELECT a.*, v.vardas as savininkas
                   FROM aukcionai a
                   JOIN vartotojai v ON a.vartotojo_id = v.id
                   ORDER BY a.sukurimo_data DESC
                   LIMIT 50";
$auctions_result = $conn->query($auctions_query);
$auctions = $auctions_result->fetch_all(MYSQLI_ASSOC);

// Gauti audito žurnalą
$audit_query = "SELECT a.*, v.vardas
                FROM audit_log a
                LEFT JOIN vartotojai v ON a.vartotojo_id = v.id
                ORDER BY a.data_laikas DESC
                LIMIT 50";
$audit_result = $conn->query($audit_query);
$audit_logs = $audit_result->fetch_all(MYSQLI_ASSOC);

// Gauti vartotojų reitingus
require_once 'includes/extended_functions.php';
$users_ratings = [];
foreach ($users as $u) {
    $rating_stats = get_user_rating_stats($u['id']);
    $sales_count = get_user_sales_count($u['id']);
    $purchases_count = get_user_purchases_count($u['id']);

    $users_ratings[] = [
        'id' => $u['id'],
        'vardas' => $u['vardas'],
        'el_pastas' => $u['el_pastas'],
        'role' => $u['role'],
        'vidutinis_ivertinimas' => $rating_stats['vidutinis_ivertinimas'],
        'bendras_skaicius' => $rating_stats['bendras_skaicius'],
        'teigiamu' => $rating_stats['teigiamu'],
        'neigiamu' => $rating_stats['neigiamu'],
        'neutraliu' => $rating_stats['neutraliu'],
        'parduota' => $sales_count,
        'nupirkta' => $purchases_count
    ];
}

// Rūšiuoti pagal reitingą (nuo aukščiausio)
usort($users_ratings, function($a, $b) {
    if ($a['bendras_skaicius'] == 0 && $b['bendras_skaicius'] == 0) return 0;
    if ($a['bendras_skaicius'] == 0) return 1;
    if ($b['bendras_skaicius'] == 0) return -1;
    return $b['vidutinis_ivertinimas'] <=> $a['vidutinis_ivertinimas'];
});

$page_title = 'Administravimas';
include 'includes/header.php';
?>

<h2>⚙️ Administratoriaus skydelis</h2>

<!-- Greitos nuorodos -->
<div style="margin-bottom: 30px; padding: 20px; background: #f8f9fa; border-radius: 8px; border-left: 4px solid #3498db;">
    <h3 style="margin-top: 0; margin-bottom: 15px;">🛠️ Administratoriaus įrankiai</h3>
    <div style="display: flex; gap: 15px; flex-wrap: wrap;">
        <a href="ip_management.php" class="btn btn-primary" style="text-decoration: none;">
            🔒 IP adresų valdymas
        </a>
        <a href="messages.php" class="btn btn-secondary" style="text-decoration: none;">
            📬 Žinutės
        </a>
        <a href="wallet.php" class="btn btn-secondary" style="text-decoration: none;">
            💰 Piniginė
        </a>
    </div>
</div>

<!-- Statistika -->
<div class="auction-meta" style="margin-bottom: 30px;">
    <div class="meta-item">
        <div class="meta-label">Vartotojų</div>
        <div class="meta-value"><?php echo $stats['total_users']; ?></div>
    </div>

    <div class="meta-item">
        <div class="meta-label">Aukcionų</div>
        <div class="meta-value"><?php echo $stats['total_auctions']; ?></div>
    </div>

    <div class="meta-item">
        <div class="meta-label">Aktyvių aukcionų</div>
        <div class="meta-value" style="color: #27ae60;"><?php echo $stats['active_auctions']; ?></div>
    </div>

    <div class="meta-item">
        <div class="meta-label">Statymų</div>
        <div class="meta-value"><?php echo $stats['total_bids']; ?></div>
    </div>
</div>

<div class="admin-panel">
    <!-- Vartotojų valdymas -->
    <div class="admin-section">
        <h3>Vartotojų valdymas</h3>

        <table class="users-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Vardas</th>
                    <th>El. paštas</th>
                    <th>Rolė</th>
                    <th>Balansas</th>
                    <th>Registracija</th>
                    <th>Veiksmai</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $u): ?>
                    <tr>
                        <td><?php echo $u['id']; ?></td>
                        <td><?php echo htmlspecialchars($u['vardas']); ?></td>
                        <td><?php echo htmlspecialchars($u['el_pastas']); ?></td>
                        <td>
                            <form method="POST" action="" style="display: inline;">
                                <input type="hidden" name="user_id" value="<?php echo $u['id']; ?>">
                                <select name="new_role" class="form-control" style="display: inline; width: auto; padding: 5px;">
                                    <option value="user" <?php echo $u['role'] === 'user' ? 'selected' : ''; ?>>Vartotojas</option>
                                    <option value="moderator" <?php echo $u['role'] === 'moderator' ? 'selected' : ''; ?>>Moderatorius</option>
                                    <option value="accountant" <?php echo $u['role'] === 'accountant' ? 'selected' : ''; ?>>Buhalteris</option>
                                    <option value="admin" <?php echo $u['role'] === 'admin' ? 'selected' : ''; ?>>Administratorius</option>
                                </select>
                                <button type="submit" name="change_role" class="btn btn-primary" style="padding: 5px 10px;">
                                    Keisti
                                </button>
                            </form>
                        </td>
                        <td>
                            <strong style="color: <?php echo $u['balansas'] >= 0 ? '#27ae60' : '#e74c3c'; ?>;">
                                <?php echo format_money($u['balansas']); ?>
                            </strong>
                        </td>
                        <td><?php echo format_datetime($u['registracijos_data']); ?></td>
                        <td>
                            <button
                                class="btn btn-success"
                                style="padding: 5px 10px;"
                                onclick="openAddBalanceModal(<?php echo $u['id']; ?>, '<?php echo htmlspecialchars($u['vardas']); ?>')">
                                Papildyti
                            </button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- Vartotojų reitingai -->
    <div class="admin-section">
        <h3>⭐ Vartotojų reitingai ir statistika</h3>

        <table class="users-table">
            <thead>
                <tr>
                    <th>Vartotojas</th>
                    <th>⭐ Reitingas</th>
                    <th>📊 Atsiliepimai</th>
                    <th>✓ Teigiami</th>
                    <th>✗ Neigiami</th>
                    <th>○ Neutralūs</th>
                    <th>🏪 Parduota</th>
                    <th>🛒 Nupirkta</th>
                    <th>Veiksmai</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users_ratings as $ur): ?>
                    <tr>
                        <td>
                            <strong><?php echo htmlspecialchars($ur['vardas']); ?></strong><br>
                            <small style="color: #666;"><?php echo htmlspecialchars($ur['el_pastas']); ?></small>
                        </td>
                        <td>
                            <?php if ($ur['bendras_skaicius'] > 0): ?>
                                <strong style="color: #f39c12; font-size: 1.1rem;">
                                    <?php echo number_format($ur['vidutinis_ivertinimas'], 2); ?> / 5.00
                                </strong>
                            <?php else: ?>
                                <span style="color: #999;">Nėra įvertinimų</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <strong><?php echo $ur['bendras_skaicius']; ?></strong>
                        </td>
                        <td style="color: #27ae60;">
                            <strong><?php echo $ur['teigiamu']; ?></strong>
                        </td>
                        <td style="color: #e74c3c;">
                            <strong><?php echo $ur['neigiamu']; ?></strong>
                        </td>
                        <td style="color: #95a5a6;">
                            <?php echo $ur['neutraliu']; ?>
                        </td>
                        <td style="color: #27ae60;">
                            <?php echo $ur['parduota']; ?>
                        </td>
                        <td style="color: #3498db;">
                            <?php echo $ur['nupirkta']; ?>
                        </td>
                        <td>
                            <a href="profile.php?id=<?php echo $ur['id']; ?>"
                               class="btn btn-primary"
                               style="padding: 5px 10px;">
                                Profilis
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- Aukcionų valdymas -->
    <div class="admin-section">
        <h3>Aukcionų valdymas (paskutiniai 50)</h3>

        <table class="users-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Pavadinimas</th>
                    <th>Savininkas</th>
                    <th>Kaina</th>
                    <th>Būsena</th>
                    <th>Pabaigos laikas</th>
                    <th>Veiksmai</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($auctions as $a): ?>
                    <tr>
                        <td><?php echo $a['id']; ?></td>
                        <td>
                            <a href="auction.php?id=<?php echo $a['id']; ?>">
                                <?php echo htmlspecialchars($a['pavadinimas']); ?>
                            </a>
                        </td>
                        <td><?php echo htmlspecialchars($a['savininkas']); ?></td>
                        <td><?php echo format_money($a['dabartine_kaina']); ?></td>
                        <td>
                            <?php if ($a['pasleptas']): ?>
                                <span class="auction-status status-hidden">Paslėptas</span>
                            <?php else: ?>
                                <span class="auction-status status-<?php echo $a['busena'] === 'aktyvus' ? 'active' : 'ended'; ?>">
                                    <?php echo $a['busena'] === 'aktyvus' ? 'Aktyvus' : 'Pasibaigęs'; ?>
                                </span>
                            <?php endif; ?>
                        </td>
                        <td><?php echo format_datetime($a['pabaigos_laikas']); ?></td>
                        <td>
                            <form method="POST" action="" style="display: inline;">
                                <input type="hidden" name="auction_id" value="<?php echo $a['id']; ?>">
                                <button type="submit" name="toggle_hidden" class="btn btn-secondary" style="padding: 5px 10px;">
                                    <?php echo $a['pasleptas'] ? 'Rodyti' : 'Slėpti'; ?>
                                </button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- Audito žurnalas -->
    <div class="admin-section">
        <h3>Audito žurnalas (paskutiniai 50 įrašai)</h3>

        <table class="transactions-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Data</th>
                    <th>Vartotojas</th>
                    <th>Veiksmas</th>
                    <th>Aprašymas</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($audit_logs as $log): ?>
                    <tr>
                        <td><?php echo $log['id']; ?></td>
                        <td><?php echo format_datetime($log['data_laikas']); ?></td>
                        <td><?php echo $log['vardas'] ? htmlspecialchars($log['vardas']) : 'Sistema'; ?></td>
                        <td><strong><?php echo htmlspecialchars($log['veiksmas']); ?></strong></td>
                        <td><?php echo htmlspecialchars($log['aprasymas']); ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Balanso papildymo modalas -->
<div id="addBalanceModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 9999;">
    <div style="background: white; max-width: 500px; margin: 100px auto; padding: 30px; border-radius: 10px;">
        <h3 id="modalTitle" style="margin-bottom: 20px;">Papildyti balansą</h3>

        <form method="POST" action="accountant.php" id="addBalanceForm">
            <input type="hidden" id="modal_user_id" name="user_id">

            <div class="form-group">
                <label for="amount">Suma (€)</label>
                <input
                    type="number"
                    id="amount"
                    name="amount"
                    class="form-control"
                    step="0.01"
                    min="0.01"
                    max="10000"
                    required>
            </div>

            <div class="form-group">
                <label for="description">Aprašymas</label>
                <input
                    type="text"
                    id="description"
                    name="description"
                    class="form-control"
                    value="Balanso papildymas per administratorių"
                    required>
            </div>

            <div style="display: flex; gap: 15px; margin-top: 20px;">
                <button type="submit" name="add_balance" class="btn btn-success">
                    Papildyti
                </button>
                <button type="button" class="btn btn-secondary" onclick="closeAddBalanceModal()">
                    Atšaukti
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function openAddBalanceModal(userId, userName) {
    document.getElementById('modal_user_id').value = userId;
    document.getElementById('modalTitle').textContent = 'Papildyti balansą: ' + userName;
    document.getElementById('addBalanceModal').style.display = 'block';
}

function closeAddBalanceModal() {
    document.getElementById('addBalanceModal').style.display = 'none';
}
</script>

<?php include 'includes/footer.php'; ?>
