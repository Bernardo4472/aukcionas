<?php
/**
 * Buhalterio skydelis
 * Sukurta: 2025-11-27
 * Autorius: Rokas Kaziulis
 */

session_start();
require_once 'includes/db.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';

// Patikrinti, ar vartotojas turi buhalterio arba admin teises
if (!has_any_role(['accountant', 'admin'])) {
    $_SESSION['error'] = 'Neturite teisės pasiekti šio puslapio.';
    header("Location: index.php");
    exit();
}

// Apdoroti balanso papildymą
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_balance'])) {
    $target_user_id = intval($_POST['user_id']);
    $amount = floatval($_POST['amount']);
    $description = clean_input($_POST['description']);

    if ($amount > 0) {
        $result = add_balance($target_user_id, $amount, $description);

        if ($result['success']) {
            log_audit($_SESSION['user_id'], 'Balanso papildymas', 'Papildytas vartotojo #' . $target_user_id . ' balansas ' . format_money($amount));
            $_SESSION['success'] = $result['message'];
        } else {
            $_SESSION['error'] = $result['message'];
        }

        header("Location: accountant.php");
        exit();
    } else {
        $_SESSION['error'] = 'Suma turi būti teigiama.';
    }
}

// Gauti visus vartotojus
$users_query = "SELECT * FROM vartotojai ORDER BY registracijos_data DESC";
$users_result = $conn->query($users_query);
$users = $users_result->fetch_all(MYSQLI_ASSOC);

// Gauti visas transakcijas
$all_transactions_query = "SELECT t.*, v.vardas, v.el_pastas
                           FROM transakcijos t
                           JOIN vartotojai v ON t.vartotojo_id = v.id
                           ORDER BY t.data_laikas DESC
                           LIMIT 100";
$all_transactions_result = $conn->query($all_transactions_query);
$all_transactions = $all_transactions_result->fetch_all(MYSQLI_ASSOC);

$page_title = 'Buhalterija';
include 'includes/header.php';
?>

<h2>💼 Buhalterio skydelis</h2>

<div class="admin-panel">
    <!-- Vartotojų balansai -->
    <div class="admin-section">
        <h3>Vartotojų balansai</h3>

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
                            <span class="role-badge role-<?php echo $u['role']; ?>">
                                <?php
                                $roles = [
                                    'admin' => 'Administratorius',
                                    'moderator' => 'Moderatorius',
                                    'accountant' => 'Buhalteris',
                                    'user' => 'Vartotojas'
                                ];
                                echo $roles[$u['role']];
                                ?>
                            </span>
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
                                onclick="openAddBalanceModal(<?php echo $u['id']; ?>, '<?php echo htmlspecialchars($u['vardas']); ?>')">
                                Papildyti
                            </button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- Visos transakcijos -->
    <div class="admin-section">
        <h3>Paskutinės 100 transakcijų</h3>

        <?php if (empty($all_transactions)): ?>
            <p style="color: #999;">Transakcijų nėra.</p>
        <?php else: ?>
            <table class="transactions-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Data</th>
                        <th>Vartotojas</th>
                        <th>Tipas</th>
                        <th>Aprašymas</th>
                        <th style="text-align: right;">Suma</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($all_transactions as $trans):
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
                            <td><?php echo $trans['id']; ?></td>
                            <td><?php echo format_datetime($trans['data_laikas']); ?></td>
                            <td><?php echo htmlspecialchars($trans['vardas']); ?></td>
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
    </div>
</div>

<!-- Balanso papildymo modalas -->
<div id="addBalanceModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 9999;">
    <div style="background: white; max-width: 500px; margin: 100px auto; padding: 30px; border-radius: 10px;">
        <h3 id="modalTitle" style="margin-bottom: 20px;">Papildyti balansą</h3>

        <form method="POST" action="" id="addBalanceForm">
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
                    value="Balanso papildymas per buhalterį"
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
