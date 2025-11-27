<?php
/**
 * Aukciono detalių ir statymo puslapis
 * Sukurta: 2025-11-27
 * Autorius: Rokas Kaziulis
 */

session_start();
require_once 'includes/db.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';

// Patikrinti, ar vartotojas prisijungęs
require_login();

// Gauti aukciono ID
if (!isset($_GET['id'])) {
    $_SESSION['error'] = 'Aukcionas nerastas.';
    header("Location: index.php");
    exit();
}

$auction_id = intval($_GET['id']);
$auction = get_auction($auction_id);

// Patikrinti, ar aukcionas egzistuoja
if (!$auction) {
    $_SESSION['error'] = 'Aukcionas nerastas.';
    header("Location: index.php");
    exit();
}

$user = get_logged_in_user();
$is_owner = $auction['savininko_id'] == $user['id'];

// Patikrinti, ar vartotojas gali matyti paslėptą aukcioną
if ($auction['pasleptas'] && !$is_owner && !has_role('admin')) {
    $_SESSION['error'] = 'Neturite teisės peržiūrėti šio aukciono.';
    header("Location: index.php");
    exit();
}

// Apdoroti statymą
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['place_bid'])) {
    $bid_amount = floatval($_POST['bid_amount']);

    $result = place_bid($auction_id, $user['id'], $bid_amount);

    if ($result['success']) {
        $_SESSION['success'] = $result['message'];
        header("Location: auction.php?id=" . $auction_id);
        exit();
    } else {
        $_SESSION['error'] = $result['message'];
    }
}

// Apdoroti komentaro pridėjimą
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_comment'])) {
    $comment_text = clean_input($_POST['comment_text']);

    $result = add_comment($auction_id, $user['id'], $comment_text);

    if ($result['success']) {
        $_SESSION['success'] = $result['message'];
        header("Location: auction.php?id=" . $auction_id);
        exit();
    } else {
        $_SESSION['error'] = $result['message'];
    }
}

// Gauti statymus ir komentarus
$bids = get_auction_bids($auction_id);
$comments = get_auction_comments($auction_id);
$highest_bid = get_highest_bid($auction_id);

$page_title = htmlspecialchars($auction['pavadinimas']);
include 'includes/header.php';
?>

<div style="margin-bottom: 20px;">
    <a href="index.php" class="btn btn-secondary">← Grįžti į sąrašą</a>
</div>

<!-- Aukciono detalės -->
<div class="auction-details">
    <?php if ($auction['pasleptas']): ?>
        <div class="alert alert-warning">
            🔒 Šis aukcionas yra paslėptas. Jį mato tik savininkas ir administratorius.
        </div>
    <?php endif; ?>

    <h2><?php echo htmlspecialchars($auction['pavadinimas']); ?></h2>

    <div style="color: #666; margin-bottom: 20px;">
        <strong>Savininkas:</strong> <?php echo htmlspecialchars($auction['savininkas']); ?>
        <?php if ($is_owner): ?>
            <span style="background: #3498db; color: white; padding: 3px 8px; border-radius: 3px; margin-left: 10px;">Jūsų aukcionas</span>
        <?php endif; ?>
    </div>

    <div style="background: white; padding: 20px; border-radius: 8px; margin-bottom: 20px;">
        <h4 style="color: #667eea; margin-bottom: 10px;">Aprašymas:</h4>
        <p style="line-height: 1.8; white-space: pre-wrap;"><?php echo htmlspecialchars($auction['aprasymas']); ?></p>
    </div>

    <div class="auction-meta">
        <div class="meta-item">
            <div class="meta-label">Pradinė kaina</div>
            <div class="meta-value"><?php echo format_money($auction['pradine_kaina']); ?></div>
        </div>

        <div class="meta-item">
            <div class="meta-label">Dabartinė kaina</div>
            <div class="meta-value" style="color: #27ae60; font-size: 1.5rem;">
                <?php echo format_money($auction['dabartine_kaina']); ?>
            </div>
        </div>

        <div class="meta-item">
            <div class="meta-label">Statymo žingsnis</div>
            <div class="meta-value"><?php echo format_money($auction['bid_step']); ?></div>
        </div>

        <div class="meta-item">
            <div class="meta-label">Statymų skaičius</div>
            <div class="meta-value"><?php echo count($bids); ?></div>
        </div>

        <div class="meta-item">
            <div class="meta-label">Pradžios laikas</div>
            <div class="meta-value"><?php echo format_datetime($auction['pradzios_laikas']); ?></div>
        </div>

        <div class="meta-item">
            <div class="meta-label">Pabaigos laikas</div>
            <div class="meta-value"><?php echo format_datetime($auction['pabaigos_laikas']); ?></div>
        </div>
    </div>

    <?php if (is_auction_active($auction)): ?>
        <div style="background: #d4edda; padding: 15px; border-radius: 8px; margin-top: 20px; text-align: center;">
            <strong style="color: #155724; font-size: 1.1rem;">
                ⏰ Aukcionas aktyvus!
            </strong>
            <div class="auction-timer" data-endtime="<?php echo $auction['pabaigos_laikas']; ?>" style="margin-top: 10px; font-size: 1.3rem; color: #155724;"></div>
        </div>
    <?php elseif (is_auction_upcoming($auction)): ?>
        <div style="background: #fff3cd; padding: 15px; border-radius: 8px; margin-top: 20px; text-align: center;">
            <strong style="color: #856404;">
                ⏳ Aukcionas prasidės: <?php echo format_datetime($auction['pradzios_laikas']); ?>
            </strong>
        </div>
    <?php else: ?>
        <div style="background: #f8d7da; padding: 15px; border-radius: 8px; margin-top: 20px; text-align: center;">
            <strong style="color: #721c24;">
                ⛔ Aukcionas pasibaigė
            </strong>
            <?php if ($highest_bid): ?>
                <div style="margin-top: 10px;">
                    Laimėtojas: <strong><?php echo htmlspecialchars($highest_bid['vardas']); ?></strong>
                    su statymu <?php echo format_money($highest_bid['suma']); ?>
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</div>

<!-- Statymo forma -->
<?php if (is_auction_active($auction) && !$is_owner): ?>
    <div class="bid-section">
        <h3>Padaryti statymą</h3>

        <div style="background: #f8f9fa; padding: 15px; border-radius: 5px; margin-bottom: 20px;">
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                <div>
                    <strong>Jūsų balansas:</strong>
                    <span style="color: #27ae60; font-size: 1.2rem; font-weight: bold;">
                        <?php echo format_money($user['balansas']); ?>
                    </span>
                </div>
                <div>
                    <strong>Minimali statymo suma:</strong>
                    <span style="color: #e74c3c; font-size: 1.2rem; font-weight: bold;">
                        <?php echo format_money($auction['dabartine_kaina'] + $auction['bid_step']); ?>
                    </span>
                </div>
            </div>
        </div>

        <form method="POST" action="" id="bidForm">
            <input type="hidden" id="min_bid" value="<?php echo $auction['dabartine_kaina'] + $auction['bid_step']; ?>">
            <input type="hidden" id="current_price" value="<?php echo $auction['dabartine_kaina']; ?>">
            <input type="hidden" id="bid_step_value" value="<?php echo $auction['bid_step']; ?>">

            <div class="form-group">
                <label for="bid_amount">Jūsų statymo suma (€)</label>
                <input
                    type="number"
                    id="bid_amount"
                    name="bid_amount"
                    class="form-control"
                    step="0.01"
                    min="<?php echo $auction['dabartine_kaina'] + $auction['bid_step']; ?>"
                    value="<?php echo number_format($auction['dabartine_kaina'] + $auction['bid_step'], 2, '.', ''); ?>"
                    required>
            </div>

            <button type="submit" name="place_bid" class="btn btn-success btn-block">
                Statyti
            </button>
        </form>
    </div>
<?php elseif ($is_owner): ?>
    <div class="alert alert-info">
        <strong>ℹ️ Informacija:</strong> Negalite statyti savo aukcione.
    </div>
<?php endif; ?>

<!-- Statymų istorija -->
<div class="bid-history">
    <h3>Statymų istorija (<?php echo count($bids); ?>)</h3>

    <?php if (empty($bids)): ?>
        <p style="color: #999;">Kol kas nėra statymų.</p>
    <?php else: ?>
        <?php foreach ($bids as $index => $bid): ?>
            <div class="bid-item">
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <div>
                        <div class="bid-user">
                            <?php echo htmlspecialchars($bid['vardas']); ?>
                            <?php if ($index === 0): ?>
                                <span style="background: #27ae60; color: white; padding: 3px 8px; border-radius: 3px; margin-left: 10px; font-size: 0.85rem;">
                                    Aukščiausias
                                </span>
                            <?php endif; ?>
                        </div>
                        <div class="bid-time"><?php echo format_datetime($bid['data_laikas']); ?></div>
                    </div>
                    <div class="bid-amount">
                        <?php echo format_money($bid['suma']); ?>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<!-- Komentarai -->
<div class="comments-section">
    <h3>Komentarai (<?php echo count($comments); ?>)</h3>

    <!-- Komentaro pridėjimo forma -->
    <form method="POST" action="" style="margin-bottom: 30px;">
        <div class="form-group">
            <textarea
                name="comment_text"
                class="form-control"
                placeholder="Parašykite komentarą..."
                rows="3"
                required></textarea>
        </div>
        <button type="submit" name="add_comment" class="btn btn-primary">
            Pridėti komentarą
        </button>
    </form>

    <!-- Komentarų sąrašas -->
    <?php if (empty($comments)): ?>
        <p style="color: #999;">Komentarų dar nėra.</p>
    <?php else: ?>
        <?php foreach ($comments as $comment): ?>
            <div class="comment">
                <div class="comment-header">
                    <span class="comment-author"><?php echo htmlspecialchars($comment['vardas']); ?></span>
                    <span class="comment-time"><?php echo format_datetime($comment['data_laikas']); ?></span>
                </div>
                <div class="comment-text"><?php echo nl2br(htmlspecialchars($comment['tekstas'])); ?></div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<?php include 'includes/footer.php'; ?>
