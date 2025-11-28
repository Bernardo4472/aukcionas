<?php
/**
 * IP valdymo puslapis (tik administratoriams)
 * Sukurta: 2025-11-28
 * Autorius: Rokas Kaziulis
 */

session_start();
require_once 'includes/db.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';
require_once 'includes/extended_functions.php';

// Patikrinti, ar vartotojas yra admin
require_role('admin');

// Apdoroti IP blokavimą
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['block_ip'])) {
    $ip_address = clean_input($_POST['ip_address']);
    $reason = clean_input($_POST['reason']);
    $duration_days = intval($_POST['duration_days']);

    $expires = null;
    if ($duration_days > 0) {
        $expires = date('Y-m-d H:i:s', strtotime("+{$duration_days} days"));
    }

    $stmt = $conn->prepare("INSERT INTO ip_blokavimai (ip_adresas, priezastis, užblokavo_admin_id, galioja_iki, aktyvus)
                            VALUES (?, ?, ?, ?, TRUE)");
    $stmt->bind_param("ssis", $ip_address, $reason, $_SESSION['user_id'], $expires);

    if ($stmt->execute()) {
        log_audit($_SESSION['user_id'], 'IP blokavimas', 'Užblokuotas IP: ' . $ip_address);
        $_SESSION['success'] = 'IP adresas sėkmingai užblokuotas.';
    } else {
        $_SESSION['error'] = 'Klaida blokuojant IP adresą.';
    }

    header("Location: ip_management.php");
    exit();
}

// Apdoroti IP atblokavimą
if (isset($_GET['unblock'])) {
    $block_id = intval($_GET['unblock']);

    $stmt = $conn->prepare("UPDATE ip_blokavimai SET aktyvus = FALSE WHERE id = ?");
    $stmt->bind_param("i", $block_id);

    if ($stmt->execute()) {
        log_audit($_SESSION['user_id'], 'IP atblokavimas', 'Atblokuotas IP blokavimas #' . $block_id);
        $_SESSION['success'] = 'IP adresas atblokuotas.';
    } else {
        $_SESSION['error'] = 'Klaida atblokuojant IP adresą.';
    }

    header("Location: ip_management.php");
    exit();
}

// Gauti užblokuotus IP
$blocked_ips = $conn->query("SELECT b.*, v.vardas as admin_vardas
                             FROM ip_blokavimai b
                             LEFT JOIN vartotojai v ON b.užblokavo_admin_id = v.id
                             WHERE b.aktyvus = TRUE
                             ORDER BY b.blokavimo_data DESC")->fetch_all(MYSQLI_ASSOC);

// Gauti IP veiklos statistiką
$ip_stats_query = "SELECT ip_adresas,
                   COUNT(*) as veiksmu_skaicius,
                   COUNT(DISTINCT vartotojo_id) as vartotoju_skaicius,
                   MAX(data_laikas) as paskutinis_veiksmas
                   FROM ip_veikla
                   GROUP BY ip_adresas
                   ORDER BY veiksmu_skaicius DESC
                   LIMIT 50";
$ip_stats = $conn->query($ip_stats_query)->fetch_all(MYSQLI_ASSOC);

// Jei pasirinktas konkretus IP, gauti detalią veiklą
$selected_ip = isset($_GET['ip']) ? clean_input($_GET['ip']) : null;
$ip_activity = [];

if ($selected_ip) {
    $stmt = $conn->prepare("SELECT v.*, vart.vardas
                            FROM ip_veikla v
                            LEFT JOIN vartotojai vart ON v.vartotojo_id = vart.id
                            WHERE v.ip_adresas = ?
                            ORDER BY v.data_laikas DESC
                            LIMIT 100");
    $stmt->bind_param("s", $selected_ip);
    $stmt->execute();
    $ip_activity = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

    // Gauti visus vartotojus iš šio IP
    $stmt = $conn->prepare("SELECT DISTINCT v.id, v.vardas, v.el_pastas, v.registracijos_data
                            FROM vartotojai v
                            JOIN ip_veikla iv ON v.id = iv.vartotojo_id
                            WHERE iv.ip_adresas = ?");
    $stmt->bind_param("s", $selected_ip);
    $stmt->execute();
    $ip_users = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

$page_title = 'IP Valdymas';
include 'includes/header.php';
?>

<h2>🌐 IP Adresų Valdymas</h2>

<!-- IP blokavimo forma -->
<div class="admin-section">
    <h3>🚫 Užblokuoti IP adresą</h3>

    <form method="POST" action="" style="max-width: 600px;">
        <div class="form-group">
            <label for="ip_address">IP Adresas <span style="color: red;">*</span></label>
            <input
                type="text"
                id="ip_address"
                name="ip_address"
                class="form-control"
                placeholder="pvz.: 192.168.1.1"
                required
                pattern="^(?:[0-9]{1,3}\.){3}[0-9]{1,3}$|^([0-9a-fA-F]{0,4}:){7}[0-9a-fA-F]{0,4}$"
                value="<?php echo $selected_ip ? htmlspecialchars($selected_ip) : ''; ?>">
        </div>

        <div class="form-group">
            <label for="reason">Priežastis <span style="color: red;">*</span></label>
            <textarea
                id="reason"
                name="reason"
                class="form-control"
                required
                rows="3"
                placeholder="Aprašykite blokavimo priežastį..."></textarea>
        </div>

        <div class="form-group">
            <label for="duration_days">Blokavimo trukmė (dienomis)</label>
            <input
                type="number"
                id="duration_days"
                name="duration_days"
                class="form-control"
                value="30"
                min="0"
                max="3650">
            <small style="color: #666;">0 = Blokuoti visam laikui</small>
        </div>

        <button type="submit" name="block_ip" class="btn btn-danger">
            🚫 Užblokuoti IP
        </button>
    </form>
</div>

<!-- Užblokuoti IP adresai -->
<div class="admin-section">
    <h3>📋 Užblokuoti IP adresai (<?php echo count($blocked_ips); ?>)</h3>

    <?php if (empty($blocked_ips)): ?>
        <p style="color: #999;">Nėra užblokuotų IP adresų.</p>
    <?php else: ?>
        <table class="users-table">
            <thead>
                <tr>
                    <th>IP Adresas</th>
                    <th>Priežastis</th>
                    <th>Užblokavo</th>
                    <th>Blokavimo data</th>
                    <th>Galioja iki</th>
                    <th>Veiksmai</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($blocked_ips as $block): ?>
                    <tr>
                        <td>
                            <a href="ip_management.php?ip=<?php echo urlencode($block['ip_adresas']); ?>">
                                <strong><?php echo htmlspecialchars($block['ip_adresas']); ?></strong>
                            </a>
                        </td>
                        <td><?php echo htmlspecialchars($block['priezastis']); ?></td>
                        <td><?php echo htmlspecialchars($block['admin_vardas'] ?? 'Sistema'); ?></td>
                        <td><?php echo format_datetime($block['blokavimo_data']); ?></td>
                        <td>
                            <?php
                            if ($block['galioja_iki']) {
                                echo format_datetime($block['galioja_iki']);
                                if (strtotime($block['galioja_iki']) < time()) {
                                    echo ' <span style="color: #e74c3c;">(Pasibaigęs)</span>';
                                }
                            } else {
                                echo '<strong>Visam laikui</strong>';
                            }
                            ?>
                        </td>
                        <td>
                            <a href="ip_management.php?unblock=<?php echo $block['id']; ?>"
                               class="btn btn-success"
                               style="padding: 5px 10px;"
                               onclick="return confirm('Ar tikrai norite atblokuoti šį IP adresą?')">
                                ✓ Atblokuoti
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<!-- IP statistika -->
<div class="admin-section">
    <h3>📊 IP Veiklos Statistika (Top 50)</h3>

    <table class="users-table">
        <thead>
            <tr>
                <th>IP Adresas</th>
                <th>Veiksmų skaičius</th>
                <th>Vartotojų skaičius</th>
                <th>Paskutinis veiksmas</th>
                <th>Veiksmai</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($ip_stats as $stat): ?>
                <tr>
                    <td>
                        <a href="ip_management.php?ip=<?php echo urlencode($stat['ip_adresas']); ?>">
                            <strong><?php echo htmlspecialchars($stat['ip_adresas']); ?></strong>
                        </a>
                    </td>
                    <td><?php echo $stat['veiksmu_skaicius']; ?></td>
                    <td><?php echo $stat['vartotoju_skaicius']; ?></td>
                    <td><?php echo format_datetime($stat['paskutinis_veiksmas']); ?></td>
                    <td>
                        <a href="ip_management.php?ip=<?php echo urlencode($stat['ip_adresas']); ?>"
                           class="btn btn-primary"
                           style="padding: 5px 10px;">
                            👁️ Peržiūrėti
                        </a>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<!-- Detali IP veikla -->
<?php if ($selected_ip): ?>
    <div class="admin-section">
        <h3>🔍 Detali veikla IP: <?php echo htmlspecialchars($selected_ip); ?></h3>

        <!-- Vartotojai iš šio IP -->
        <?php if (!empty($ip_users)): ?>
            <div style="background: #fff3cd; padding: 15px; border-radius: 5px; margin-bottom: 20px;">
                <h4 style="margin-bottom: 10px;">⚠️ Vartotojai iš šio IP (<?php echo count($ip_users); ?>):</h4>
                <ul style="margin-left: 20px;">
                    <?php foreach ($ip_users as $u): ?>
                        <li>
                            <a href="profile.php?id=<?php echo $u['id']; ?>">
                                <strong><?php echo htmlspecialchars($u['vardas']); ?></strong>
                            </a>
                            (<?php echo htmlspecialchars($u['el_pastas']); ?>)
                            - Registruotas: <?php echo format_datetime($u['registracijos_data']); ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <!-- Veiklos žurnalas -->
        <h4>Paskutiniai 100 veiksmų:</h4>

        <?php if (empty($ip_activity)): ?>
            <p style="color: #999;">Nėra užfiksuotos veiklos.</p>
        <?php else: ?>
            <table class="transactions-table">
                <thead>
                    <tr>
                        <th>Data</th>
                        <th>Vartotojas</th>
                        <th>Veiksmas</th>
                        <th>Aprašymas</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($ip_activity as $activity): ?>
                        <tr>
                            <td><?php echo format_datetime($activity['data_laikas']); ?></td>
                            <td>
                                <?php if ($activity['vartotojo_id']): ?>
                                    <a href="profile.php?id=<?php echo $activity['vartotojo_id']; ?>">
                                        <?php echo htmlspecialchars($activity['vardas']); ?>
                                    </a>
                                <?php else: ?>
                                    <em>Svečias</em>
                                <?php endif; ?>
                            </td>
                            <td><strong><?php echo htmlspecialchars($activity['veiksmas']); ?></strong></td>
                            <td><?php echo htmlspecialchars($activity['aprasymas']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>

        <div style="margin-top: 20px;">
            <a href="ip_management.php" class="btn btn-secondary">← Grįžti į sąrašą</a>
        </div>
    </div>
<?php endif; ?>

<?php include 'includes/footer.php'; ?>
