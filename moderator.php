<?php
/**
 * Moderatoriaus skydelis
 * Sukurta: 2025-11-27
 * Autorius: Rokas Kaziulis
 */

session_start();
require_once 'includes/db.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';

// Patikrinti, ar vartotojas turi moderatoriaus arba admin teises
if (!has_any_role(['moderator', 'admin'])) {
    $_SESSION['error'] = 'Neturite teisės pasiekti šio puslapio.';
    header("Location: index.php");
    exit();
}

// Apdoroti komentaro ištrynimą
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_comment'])) {
    $comment_id = intval($_POST['comment_id']);

    $stmt = $conn->prepare("DELETE FROM komentarai WHERE id = ?");
    $stmt->bind_param("i", $comment_id);

    if ($stmt->execute()) {
        log_audit($_SESSION['user_id'], 'Komentaro ištrynimas', 'Ištrintas komentaras #' . $comment_id);
        $_SESSION['success'] = 'Komentaras sėkmingai ištrintas.';
    } else {
        $_SESSION['error'] = 'Įvyko klaida trinant komentarą.';
    }

    header("Location: moderator.php");
    exit();
}

// Apdoroti aukciono ištrinimą
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_auction'])) {
    $auction_id = intval($_POST['auction_id']);

    // Patikrinti, ar aukcione yra statymų
    $check_bids = $conn->prepare("SELECT COUNT(*) as count FROM statymai WHERE aukciono_id = ?");
    $check_bids->bind_param("i", $auction_id);
    $check_bids->execute();
    $bid_count = $check_bids->get_result()->fetch_assoc()['count'];

    if ($bid_count > 0) {
        $_SESSION['error'] = 'Negalima ištrinti aukciono su statymais. Pirmiausia reikia grąžinti lėšas statytojams.';
    } else {
        $stmt = $conn->prepare("DELETE FROM aukcionai WHERE id = ?");
        $stmt->bind_param("i", $auction_id);

        if ($stmt->execute()) {
            log_audit($_SESSION['user_id'], 'Aukciono ištrynimas', 'Ištrintas aukcionas #' . $auction_id);
            $_SESSION['success'] = 'Aukcionas sėkmingai ištrintas.';
        } else {
            $_SESSION['error'] = 'Įvyko klaida trinant aukcioną.';
        }
    }

    header("Location: moderator.php");
    exit();
}

// Apdoroti rankinio grąžinimo užklausą
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['refund_bid'])) {
    $bid_id = intval($_POST['bid_id']);

    $result = refund_bid($bid_id, $_SESSION['user_id']);

    if ($result['success']) {
        $_SESSION['success'] = $result['message'];
    } else {
        $_SESSION['error'] = $result['message'];
    }

    header("Location: moderator.php?tab=refunds");
    exit();
}

// Gauti visus komentarus
$comments_query = "SELECT k.*, v.vardas, a.pavadinimas as aukciono_pavadinimas
                   FROM komentarai k
                   JOIN vartotojai v ON k.vartotojo_id = v.id
                   JOIN aukcionai a ON k.aukciono_id = a.id
                   ORDER BY k.data_laikas DESC
                   LIMIT 100";
$comments_result = $conn->query($comments_query);
$all_comments = $comments_result->fetch_all(MYSQLI_ASSOC);

// Gauti visus aukcionus
$auctions_query = "SELECT a.*, v.vardas as savininkas,
                   (SELECT COUNT(*) FROM statymai WHERE aukciono_id = a.id) as bid_count,
                   (SELECT COUNT(*) FROM komentarai WHERE aukciono_id = a.id) as comment_count
                   FROM aukcionai a
                   JOIN vartotojai v ON a.vartotojo_id = v.id
                   ORDER BY a.sukurimo_data DESC
                   LIMIT 50";
$auctions_result = $conn->query($auctions_query);
$auctions = $auctions_result->fetch_all(MYSQLI_ASSOC);

$page_title = 'Moderavimas';
include 'includes/header.php';
?>

<h2>🛡️ Moderatoriaus skydelis</h2>

<div class="admin-panel">
    <!-- Komentarų moderavimas -->
    <div class="admin-section">
        <h3>Komentarų moderavimas (paskutiniai 100)</h3>

        <?php if (empty($all_comments)): ?>
            <p style="color: #999;">Komentarų nėra.</p>
        <?php else: ?>
            <table class="users-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Data</th>
                        <th>Vartotojas</th>
                        <th>Aukcionas</th>
                        <th>Komentaras</th>
                        <th>Veiksmai</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($all_comments as $comment): ?>
                        <tr>
                            <td><?php echo $comment['id']; ?></td>
                            <td><?php echo format_datetime($comment['data_laikas']); ?></td>
                            <td><?php echo htmlspecialchars($comment['vardas']); ?></td>
                            <td>
                                <a href="auction.php?id=<?php echo $comment['aukciono_id']; ?>">
                                    <?php echo htmlspecialchars($comment['aukciono_pavadinimas']); ?>
                                </a>
                            </td>
                            <td style="max-width: 300px;">
                                <?php echo htmlspecialchars(substr($comment['tekstas'], 0, 100)); ?>
                                <?php echo strlen($comment['tekstas']) > 100 ? '...' : ''; ?>
                            </td>
                            <td>
                                <form method="POST" action="" style="display: inline;">
                                    <input type="hidden" name="comment_id" value="<?php echo $comment['id']; ?>">
                                    <button
                                        type="submit"
                                        name="delete_comment"
                                        class="btn btn-danger"
                                        style="padding: 5px 10px;"
                                        onclick="return confirmDelete('Ar tikrai norite ištrinti šį komentarą?')">
                                        Ištrinti
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>

    <!-- Aukcionų moderavimas -->
    <div class="admin-section">
        <h3>Aukcionų moderavimas (paskutiniai 50)</h3>

        <table class="users-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Pavadinimas</th>
                    <th>Savininkas</th>
                    <th>Kaina</th>
                    <th>Būsena</th>
                    <th>Statymai</th>
                    <th>Komentarai</th>
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
                            <span class="auction-status status-<?php echo $a['busena'] === 'aktyvus' ? 'active' : 'ended'; ?>">
                                <?php echo $a['busena'] === 'aktyvus' ? 'Aktyvus' : 'Pasibaigęs'; ?>
                            </span>
                        </td>
                        <td><?php echo $a['bid_count']; ?></td>
                        <td><?php echo $a['comment_count']; ?></td>
                        <td>
                            <?php if ($a['bid_count'] == 0): ?>
                                <form method="POST" action="" style="display: inline;">
                                    <input type="hidden" name="auction_id" value="<?php echo $a['id']; ?>">
                                    <button
                                        type="submit"
                                        name="delete_auction"
                                        class="btn btn-danger"
                                        style="padding: 5px 10px;"
                                        onclick="return confirmDelete('Ar tikrai norite ištrinti šį aukcioną?')">
                                        Ištrinti
                                    </button>
                                </form>
                            <?php else: ?>
                                <span style="color: #999; font-size: 0.9rem;">Su statymais</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <div class="alert alert-warning mt-20">
            <strong>⚠️ Dėmesio:</strong> Aukcionų su statymais ištrinti negalima. Tai apsaugo vartotojų lėšas nuo netikėto praradimo.
        </div>
    </div>

    <!-- Grąžinimų valdymas -->
    <div class="admin-section">
        <h3>💸 Rankinis pinigų grąžinimas</h3>

        <div class="alert alert-info" style="margin-bottom: 20px;">
            <strong>ℹ️ Informacija:</strong> Čia galite grąžinti pinigus statytojams už konkretų aukcioną. 
            Sistema automatiškai tikrina, ar grąžinimas jau buvo atliktas.
        </div>

        <form method="GET" action="" style="margin-bottom: 20px;">
            <input type="hidden" name="tab" value="refunds">
            <div style="display: flex; gap: 15px; align-items: center;">
                <div class="form-group" style="flex: 1; margin: 0;">
                    <label for="refund_auction_id">Aukciono ID</label>
                    <input
                        type="number"
                        id="refund_auction_id"
                        name="refund_auction_id"
                        class="form-control"
                        placeholder="Įveskite aukciono ID"
                        min="1"
                        value="<?php echo isset($_GET['refund_auction_id']) ? intval($_GET['refund_auction_id']) : ''; ?>">
                </div>
                <button type="submit" class="btn btn-primary" style="margin-top: 24px;">
                    🔍 Ieškoti statymų
                </button>
            </div>
        </form>

        <?php if (isset($_GET['refund_auction_id'])): ?>
            <?php
            $refund_auction_id = intval($_GET['refund_auction_id']);
            $refund_auction = get_auction($refund_auction_id);

            if ($refund_auction):
                $auction_bids = get_auction_bids_detailed($refund_auction_id);
            ?>
                <div style="background: #f8f9fa; padding: 15px; border-radius: 5px; margin-bottom: 20px;">
                    <h4 style="margin-top: 0;">
                        Aukcionas: <?php echo htmlspecialchars($refund_auction['pavadinimas']); ?>
                    </h4>
                    <p style="margin: 5px 0;">
                        <strong>Savininkas:</strong> <?php echo htmlspecialchars($refund_auction['savininkas']); ?><br>
                        <strong>Dabartinė kaina:</strong> <?php echo format_money($refund_auction['dabartine_kaina']); ?><br>
                        <strong>Būsena:</strong> <?php echo $refund_auction['busena']; ?>
                    </p>
                </div>

                <?php if (!empty($auction_bids)): ?>
                    <table class="users-table">
                        <thead>
                            <tr>
                                <th>Statymo ID</th>
                                <th>Data</th>
                                <th>Statytojas</th>
                                <th>El. paštas</th>
                                <th>Suma</th>
                                <th>Būsena</th>
                                <th>Veiksmai</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($auction_bids as $bid): ?>
                                <tr>
                                    <td><?php echo $bid['id']; ?></td>
                                    <td><?php echo format_datetime($bid['data_laikas']); ?></td>
                                    <td><?php echo htmlspecialchars($bid['vardas']); ?></td>
                                    <td><?php echo htmlspecialchars($bid['el_pastas']); ?></td>
                                    <td>
                                        <strong style="color: #e74c3c;">
                                            <?php echo format_money($bid['suma']); ?>
                                        </strong>
                                    </td>
                                    <td>
                                        <?php if ($bid['is_refunded'] > 0): ?>
                                            <span style="color: #27ae60; font-weight: bold;">✓ Grąžinta</span>
                                        <?php else: ?>
                                            <span style="color: #e67e22; font-weight: bold;">⏳ Negrąžinta</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php if ($bid['is_refunded'] == 0): ?>
                                            <form method="POST" action="" style="display: inline;">
                                                <input type="hidden" name="bid_id" value="<?php echo $bid['id']; ?>">
                                                <button
                                                    type="submit"
                                                    name="refund_bid"
                                                    class="btn btn-success"
                                                    style="padding: 5px 10px;"
                                                    onclick="return confirm('Ar tikrai norite grąžinti <?php echo format_money($bid['suma']); ?> vartotojui <?php echo htmlspecialchars($bid['vardas']); ?>?')">
                                                    💸 Grąžinti
                                                </button>
                                            </form>
                                        <?php else: ?>
                                            <span style="color: #999;">Jau grąžinta</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p style="color: #999;">Šiame aukcione nėra statymų.</p>
                <?php endif; ?>
            <?php else: ?>
                <div class="alert alert-warning">
                    Aukcionas su ID #<?php echo $refund_auction_id; ?> nerastas.
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<div class="alert alert-info">
    <strong>ℹ️ Moderatoriaus teisės:</strong>
    <ul style="margin-left: 20px; margin-top: 10px;">
        <li>Ištrinti netinkamus komentarus</li>
        <li>Ištrinti probleminius aukcionus (be statymų)</li>
        <li>Grąžinti pinigus statytojams už aukcionus</li>
        <li>Peržiūrėti visus aukcionus ir komentarus</li>
        <li>Visi veiksmai įrašomi į audito žurnalą</li>
    </ul>
</div>

<?php include 'includes/footer.php'; ?>
