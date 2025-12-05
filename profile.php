<?php
/**
 * Vartotojo profilio puslapis
 * Sukurta: 2025-11-28
 * Autorius: Rokas Kaziulis
 */

session_start();
require_once 'includes/db.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';
require_once 'includes/extended_functions.php';

// Patikrinti, ar vartotojas prisijungęs
require_login();

// Gauti profilio vartotojo ID
$profile_user_id = isset($_GET['id']) ? intval($_GET['id']) : $_SESSION['user_id'];

// Gauti vartotojo informaciją
$stmt = $conn->prepare("SELECT * FROM vartotojai WHERE id = ?");
$stmt->bind_param("i", $profile_user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    $_SESSION['error'] = 'Vartotojas nerastas.';
    header("Location: index.php");
    exit();
}

$profile_user = $result->fetch_assoc();
$current_user = get_logged_in_user();
$is_own_profile = ($profile_user_id == $current_user['id']);

// Apdoroti atsiliepimo pridėjimą
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_feedback'])) {
    $rating = intval($_POST['rating']);
    $comment = clean_input($_POST['comment']);
    $type = clean_input($_POST['type']);
    $auction_id = !empty($_POST['auction_id']) ? intval($_POST['auction_id']) : null;

    $result = add_feedback($current_user['id'], $profile_user_id, $rating, $comment, $type, $auction_id);

    if ($result['success']) {
        $_SESSION['success'] = $result['message'];
    } else {
        $_SESSION['error'] = $result['message'];
    }

    header("Location: profile.php?id=" . $profile_user_id);
    exit();
}

// Gauti vartotojo statistiką
$rating_stats = get_user_rating_stats($profile_user_id);
$sales_count = get_user_sales_count($profile_user_id);
$purchases_count = get_user_purchases_count($profile_user_id);
$feedback_list = get_user_feedback($profile_user_id);

// Gauti aktyvius aukcionus
$stmt = $conn->prepare("SELECT * FROM aukcionai
                        WHERE vartotojo_id = ? AND busena = 'aktyvus'
                        AND pabaigos_laikas > NOW()
                        ORDER BY sukurimo_data DESC
                        LIMIT 10");
$stmt->bind_param("i", $profile_user_id);
$stmt->execute();
$active_auctions = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$page_title = htmlspecialchars($profile_user['vardas']) . ' - Profilis';
include 'includes/header.php';
?>

<div style="margin-bottom: 20px;">
    <a href="index.php" style="color: #3498db; text-decoration: none;">
        ← Grįžti į aukcionų sąrašą
    </a>
</div>

<h2>👤 Vartotojo profilis</h2>

<!-- Profilio kortelė -->
<div class="wallet-card" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
    <div style="display: flex; justify-content: space-between; align-items: start; flex-wrap: wrap; gap: 20px;">
        <div>
            <h2><?php echo htmlspecialchars($profile_user['vardas']); ?></h2>
            <p style="opacity: 0.9;">
                <strong>Vartotojo ID:</strong> <?php echo $profile_user['id']; ?><br>
                <strong>Rolė:</strong>
                <span class="role-badge role-<?php echo $profile_user['role']; ?>" style="margin-left: 5px;">
                    <?php
                    $roles = ['admin' => 'Administratorius', 'moderator' => 'Moderatorius',
                              'accountant' => 'Buhalteris', 'user' => 'Vartotojas'];
                    echo $roles[$profile_user['role']];
                    ?>
                </span><br>
                <strong>Narys nuo:</strong> <?php echo format_datetime($profile_user['registracijos_data']); ?>
            </p>
        </div>

        <?php if (!$is_own_profile): ?>
            <div>
                <a href="messages.php?tab=new&to=<?php echo $profile_user_id; ?>"
                   class="btn btn-success" style="display: inline-block; margin-bottom: 10px;">
                    📧 Siųsti žinutę
                </a>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Statistika -->
<div class="auction-meta" style="margin-top: 30px;">
    <div class="meta-item">
        <div class="meta-label">⭐ Vidutinis įvertinimas</div>
        <div class="meta-value" style="color: #f39c12; font-size: 2rem;">
            <?php
            if ($rating_stats['bendras_skaicius'] > 0) {
                echo number_format($rating_stats['vidutinis_ivertinimas'], 2) . ' / 5.00';
            } else {
                echo 'Nėra įvertinimų';
            }
            ?>
        </div>
    </div>

    <div class="meta-item">
        <div class="meta-label">📊 Iš viso atsiliepimų</div>
        <div class="meta-value"><?php echo $rating_stats['bendras_skaicius']; ?></div>
        <div style="margin-top: 10px; font-size: 0.9rem;">
            <span style="color: #27ae60;">✓ Teigiamų: <?php echo $rating_stats['teigiamu']; ?></span><br>
            <span style="color: #e74c3c;">✗ Neigiamų: <?php echo $rating_stats['neigiamu']; ?></span><br>
            <span style="color: #95a5a6;">○ Neutralių: <?php echo $rating_stats['neutraliu']; ?></span>
        </div>
    </div>

    <div class="meta-item">
        <div class="meta-label">🏪 Parduota prekių</div>
        <div class="meta-value" style="color: #27ae60;"><?php echo $sales_count; ?></div>
    </div>

    <div class="meta-item">
        <div class="meta-label">🛒 Nupirkta prekių</div>
        <div class="meta-value" style="color: #3498db;"><?php echo $purchases_count; ?></div>
    </div>
</div>

<!-- Aktyvūs aukcionai -->
<?php if (!empty($active_auctions)): ?>
    <div class="admin-section" style="margin-top: 30px;">
        <h3>🔨 Aktyvūs aukcionai (<?php echo count($active_auctions); ?>)</h3>

        <div class="auctions-grid">
            <?php foreach ($active_auctions as $auction): ?>
                <div class="auction-card">
                    <h3><?php echo htmlspecialchars($auction['pavadinimas']); ?></h3>
                    <div class="auction-info">
                        <div class="auction-info-item">
                            <span class="label">Dabartinė kaina:</span>
                            <span class="value price"><?php echo format_money($auction['dabartine_kaina']); ?></span>
                        </div>
                        <div class="auction-info-item">
                            <span class="label">Pabaigos laikas:</span>
                            <span class="value"><?php echo format_datetime($auction['pabaigos_laikas']); ?></span>
                        </div>
                    </div>
                    <a href="auction.php?id=<?php echo $auction['id']; ?>" class="btn btn-primary btn-block" style="margin-top: 10px;">
                        Peržiūrėti
                    </a>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
<?php endif; ?>

<!-- Atsiliepimų pridėjimo forma (jei ne savo profilis) -->
<?php if (!$is_own_profile): ?>
    <div class="admin-section" style="margin-top: 30px;">
        <h3>✍️ Palikti atsiliepimą</h3>

        <form method="POST" action="">
            <div class="form-group">
                <label for="rating">Įvertinimas (1-5 žvaigždutės) <span style="color: red;">*</span></label>
                <select id="rating" name="rating" class="form-control" required>
                    <option value="">Pasirinkite...</option>
                    <option value="5">⭐⭐⭐⭐⭐ 5 - Puiku</option>
                    <option value="4">⭐⭐⭐⭐ 4 - Gerai</option>
                    <option value="3">⭐⭐⭐ 3 - Vidutiniškai</option>
                    <option value="2">⭐⭐ 2 - Prastai</option>
                    <option value="1">⭐ 1 - Labai blogai</option>
                </select>
            </div>

            <div class="form-group">
                <label for="type">Atsiliepimo tipas <span style="color: red;">*</span></label>
                <select id="type" name="type" class="form-control" required>
                    <option value="">Pasirinkite...</option>
                    <option value="teigiamas">✅ Teigiamas</option>
                    <option value="neutralus">⚪ Neutralus</option>
                    <option value="neigiamas">❌ Neigiamas</option>
                </select>
            </div>

            <div class="form-group">
                <label for="comment">Komentaras <span style="color: red;">*</span></label>
                <textarea
                    id="comment"
                    name="comment"
                    class="form-control"
                    required
                    rows="4"
                    placeholder="Aprašykite savo patirtį..."></textarea>
            </div>

            <div class="form-group">
                <label for="auction_id">Aukciono ID (neprivaloma)</label>
                <input
                    type="number"
                    id="auction_id"
                    name="auction_id"
                    class="form-control"
                    placeholder="Jei atsiliepimas susijęs su konkrečiu aukciono">
            </div>

            <button type="submit" name="add_feedback" class="btn btn-success">
                Pateikti atsiliepimą
            </button>
        </form>
    </div>
<?php endif; ?>

<!-- Atsiliepimai -->
<div class="admin-section" style="margin-top: 30px;">
    <h3>💬 Atsiliepimai (<?php echo count($feedback_list); ?>)</h3>

    <?php if (empty($feedback_list)): ?>
        <p style="color: #999;">Šis vartotojas dar neturi atsiliepimų.</p>
    <?php else: ?>
        <div style="display: flex; flex-direction: column; gap: 15px;">
            <?php foreach ($feedback_list as $feedback): ?>
                <div class="comment" style="border-left: 4px solid <?php
                    echo $feedback['tipas'] === 'teigiamas' ? '#27ae60' :
                         ($feedback['tipas'] === 'neigiamas' ? '#e74c3c' : '#95a5a6');
                ?>;">
                    <div class="comment-header">
                        <div>
                            <strong><?php echo htmlspecialchars($feedback['nuo_vartotojo_vardas']); ?></strong>
                            <span style="margin-left: 10px;">
                                <?php
                                for ($i = 0; $i < $feedback['ivertinimas']; $i++) {
                                    echo '⭐';
                                }
                                ?>
                                (<?php echo $feedback['ivertinimas']; ?>/5)
                            </span>
                            <span class="auction-status" style="margin-left: 10px; <?php
                                echo 'background: ' . ($feedback['tipas'] === 'teigiamas' ? '#d4edda; color: #155724' :
                                     ($feedback['tipas'] === 'neigiamas' ? '#f8d7da; color: #721c24' : '#e2e3e5; color: #383d41'));
                            ?>;">
                                <?php echo ucfirst($feedback['tipas']); ?>
                            </span>
                        </div>
                        <span class="comment-time"><?php echo format_datetime($feedback['data_laikas']); ?></span>
                    </div>

                    <div class="comment-text">
                        <?php echo nl2br(htmlspecialchars($feedback['komentaras'])); ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>
