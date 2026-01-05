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

// Gauti pasibaigusius aukcionus (parduoti)
$stmt = $conn->prepare("SELECT a.*,
                        (SELECT COUNT(*) FROM statymai WHERE aukciono_id = a.id) as bid_count,
                        (SELECT s.vartotojo_id FROM statymai s WHERE s.aukciono_id = a.id ORDER BY s.suma DESC, s.data_laikas ASC LIMIT 1) as winner_id,
                        (SELECT v.vardas FROM statymai s JOIN vartotojai v ON s.vartotojo_id = v.id WHERE s.aukciono_id = a.id ORDER BY s.suma DESC, s.data_laikas ASC LIMIT 1) as winner_name
                        FROM aukcionai a
                        WHERE a.vartotojo_id = ? AND a.busena = 'pasibaiges'
                        ORDER BY a.pabaigos_laikas DESC
                        LIMIT 10");
$stmt->bind_param("i", $profile_user_id);
$stmt->execute();
$sold_auctions = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Gauti laimėtus aukcionus (nupirkti)
$stmt = $conn->prepare("SELECT a.*, v.vardas as seller_name, v.id as seller_id, s.suma as win_price
                        FROM aukcionai a
                        JOIN vartotojai v ON a.vartotojo_id = v.id
                        JOIN (
                            SELECT s1.aukciono_id, s1.vartotojo_id, s1.suma
                            FROM statymai s1
                            WHERE s1.data_laikas = (
                                SELECT MAX(s2.data_laikas)
                                FROM statymai s2
                                WHERE s2.aukciono_id = s1.aukciono_id
                                AND s2.suma = (SELECT MAX(s3.suma) FROM statymai s3 WHERE s3.aukciono_id = s1.aukciono_id)
                            )
                        ) s ON a.id = s.aukciono_id
                        WHERE a.busena = 'pasibaiges' AND s.vartotojo_id = ?
                        ORDER BY a.pabaigos_laikas DESC
                        LIMIT 10");
$stmt->bind_param("i", $profile_user_id);
$stmt->execute();
$won_auctions = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$page_title = htmlspecialchars($profile_user['vardas']) . ' - Profilis';
include 'includes/header.php';
?>

<div style="margin-bottom: 20px;">
    <a href="index.php" style="color: #3498db; text-decoration: none;">
        ← Grįžti į aukcionų sąrašą
    </a>
</div>

<h2>Vartotojo profilis</h2>

<!-- Profilio kortelė -->
<div class="wallet-card">
    <div style="display: flex; justify-content: space-between; align-items: start; flex-wrap: wrap; gap: 20px;">
        <div>
            <h2><?php echo htmlspecialchars($profile_user['vardas']); ?></h2>
            <p>
                <strong>Vartotojo ID:</strong> <?php echo $profile_user['id']; ?><br>
                <strong>Role:</strong>
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
                    Siusti zinute
                </a>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- Statistika -->
<div class="auction-meta" style="margin-top: 30px;">
    <div class="meta-item">
        <div class="meta-label">Vidutinis ivertinimas</div>
        <div class="meta-value" style="font-size: 1.5rem;">
            <?php
            if ($rating_stats['bendras_skaicius'] > 0) {
                echo number_format($rating_stats['vidutinis_ivertinimas'], 2) . ' / 5.00';
            } else {
                echo 'Nera ivertinimu';
            }
            ?>
        </div>
    </div>

    <div class="meta-item">
        <div class="meta-label">Is viso atsiliepimu</div>
        <div class="meta-value"><?php echo $rating_stats['bendras_skaicius']; ?></div>
        <div style="margin-top: 10px; font-size: 0.9rem;">
            <span>Teigiamu: <?php echo $rating_stats['teigiamu']; ?></span><br>
            <span>Neigiamu: <?php echo $rating_stats['neigiamu']; ?></span><br>
            <span>Neutraliu: <?php echo $rating_stats['neutraliu']; ?></span>
        </div>
    </div>

    <div class="meta-item">
        <div class="meta-label">Parduota prekiu</div>
        <div class="meta-value"><?php echo $sales_count; ?></div>
    </div>

    <div class="meta-item">
        <div class="meta-label">Nupirkta prekiu</div>
        <div class="meta-value"><?php echo $purchases_count; ?></div>
    </div>
</div>

<!-- Aktyvūs aukcionai -->
<?php if (!empty($active_auctions)): ?>
    <div class="admin-section" style="margin-top: 30px;">
        <h3>Aktyvus aukcionai (<?php echo count($active_auctions); ?>)</h3>

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

<!-- Parduoti aukcionai -->
<?php if (!empty($sold_auctions) && $is_own_profile): ?>
    <div class="admin-section" style="margin-top: 30px;">
        <h3>Parduoti aukcionai (<?php echo count($sold_auctions); ?>)</h3>

        <table class="users-table">
            <thead>
                <tr>
                    <th>Aukcionas</th>
                    <th>Galutinė kaina</th>
                    <th>Statymų</th>
                    <th>Laimėtojas</th>
                    <th>Pabaigė</th>
                    <th>Veiksmai</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($sold_auctions as $auction): ?>
                    <tr>
                        <td>
                            <a href="auction.php?id=<?php echo $auction['id']; ?>">
                                <strong><?php echo htmlspecialchars($auction['pavadinimas']); ?></strong>
                            </a>
                        </td>
                        <td><strong><?php echo format_money($auction['dabartine_kaina']); ?></strong></td>
                        <td><?php echo $auction['bid_count']; ?></td>
                        <td>
                            <?php if ($auction['winner_id']): ?>
                                <a href="profile.php?id=<?php echo $auction['winner_id']; ?>">
                                    <?php echo htmlspecialchars($auction['winner_name']); ?>
                                </a>
                            <?php else: ?>
                                <em>Nėra statymų</em>
                            <?php endif; ?>
                        </td>
                        <td><?php echo format_datetime($auction['pabaigos_laikas']); ?></td>
                        <td>
                            <?php if ($auction['winner_id']): ?>
                                <a href="messages.php?tab=new&to=<?php echo $auction['winner_id']; ?>&subject=Dėl parduoto aukciono: <?php echo urlencode($auction['pavadinimas']); ?>&auction=<?php echo $auction['id']; ?>"
                                   class="btn btn-primary"
                                   style="padding: 5px 10px;">
                                    Rasyti pirkejui
                                </a>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>

<!-- Laimėti aukcionai -->
<?php if (!empty($won_auctions) && $is_own_profile): ?>
    <div class="admin-section" style="margin-top: 30px;">
        <h3>Laimeti aukcionai (<?php echo count($won_auctions); ?>)</h3>

        <table class="users-table">
            <thead>
                <tr>
                    <th>Aukcionas</th>
                    <th>Laimėta kaina</th>
                    <th>Pardavėjas</th>
                    <th>Pabaigė</th>
                    <th>Veiksmai</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($won_auctions as $auction): ?>
                    <tr>
                        <td>
                            <a href="auction.php?id=<?php echo $auction['id']; ?>">
                                <strong><?php echo htmlspecialchars($auction['pavadinimas']); ?></strong>
                            </a>
                        </td>
                        <td><strong style="color: #27ae60;"><?php echo format_money($auction['win_price']); ?></strong></td>
                        <td>
                            <a href="profile.php?id=<?php echo $auction['seller_id']; ?>">
                                <?php echo htmlspecialchars($auction['seller_name']); ?>
                            </a>
                        </td>
                        <td><?php echo format_datetime($auction['pabaigos_laikas']); ?></td>
                        <td>
                            <a href="messages.php?tab=new&to=<?php echo $auction['seller_id']; ?>&subject=Dėl laimėto aukciono: <?php echo urlencode($auction['pavadinimas']); ?>&auction=<?php echo $auction['id']; ?>"
                               class="btn btn-success"
                               style="padding: 5px 10px;">
                                Rasyti pardavejui
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>

<!-- Atsiliepimų pridėjimo forma (jei ne savo profilis) -->
<?php if (!$is_own_profile): ?>
    <div class="admin-section" style="margin-top: 30px;">
        <h3>Palikti atsiliepima</h3>

        <form method="POST" action="">
            <div class="form-group">
                <label for="rating">Įvertinimas (1-5 žvaigždutės) <span style="color: red;">*</span></label>
                <select id="rating" name="rating" class="form-control" required>
                    <option value="">Pasirinkite...</option>
                    <option value="5">5 - Puiku</option>
                    <option value="4">4 - Gerai</option>
                    <option value="3">3 - Vidutiniskai</option>
                    <option value="2">2 - Prastai</option>
                    <option value="1">1 - Labai blogai</option>
                </select>
            </div>

            <div class="form-group">
                <label for="type">Atsiliepimo tipas <span style="color: red;">*</span></label>
                <select id="type" name="type" class="form-control" required>
                    <option value="">Pasirinkite...</option>
                    <option value="teigiamas">Teigiamas</option>
                    <option value="neutralus">Neutralus</option>
                    <option value="neigiamas">Neigiamas</option>
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
    <h3>Atsiliepimai (<?php echo count($feedback_list); ?>)</h3>

    <?php if (empty($feedback_list)): ?>
        <p style="color: #999;">Šis vartotojas dar neturi atsiliepimų.</p>
    <?php else: ?>
        <div style="display: flex; flex-direction: column; gap: 15px;">
            <?php foreach ($feedback_list as $feedback): ?>
                <div class="comment" style="border-left: 3px solid #999;">
                    <div class="comment-header">
                            <div>
                            <strong><?php echo htmlspecialchars($feedback['nuo_vartotojo_vardas']); ?></strong>
                            <span style="margin-left: 10px;">
                                (<?php echo $feedback['ivertinimas']; ?>/5)
                            </span>
                            <span class="auction-status" style="margin-left: 10px;">
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
