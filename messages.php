<?php
/**
 * Žinučių puslapis
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

$user = get_logged_in_user();
$tab = isset($_GET['tab']) ? $_GET['tab'] : 'inbox';

// Apdoroti žinutės siuntimą
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_message'])) {
    $to_user_id = intval($_POST['to_user_id']);
    $subject = clean_input($_POST['subject']);
    $message = clean_input($_POST['message']);
    $auction_id = !empty($_POST['auction_id']) ? intval($_POST['auction_id']) : null;

    $result = send_message($user['id'], $to_user_id, $subject, $message, $auction_id);

    if ($result['success']) {
        $_SESSION['success'] = $result['message'];
    } else {
        $_SESSION['error'] = $result['message'];
    }

    header("Location: messages.php?tab=sent");
    exit();
}

// Pažymėti žinutę kaip perskaitytą
if (isset($_GET['read']) && $tab === 'inbox') {
    $message_id = intval($_GET['read']);
    mark_message_read($message_id, $user['id']);
}

// Gauti žinutes
if ($tab === 'inbox') {
    $messages = get_inbox_messages($user['id']);
} else {
    $messages = get_sent_messages($user['id']);
}

$unread_count = get_unread_messages_count($user['id']);

$page_title = 'Žinutės';
include 'includes/header.php';
?>

<h2>📬 Žinutės</h2>

<!-- Skirtukai -->
<div style="display: flex; gap: 10px; margin-bottom: 20px; border-bottom: 2px solid #e0e0e0;">
    <a href="messages.php?tab=inbox"
       class="btn <?php echo $tab === 'inbox' ? 'btn-primary' : 'btn-secondary'; ?>"
       style="border-radius: 5px 5px 0 0;">
        📥 Gautos (<?php echo $unread_count > 0 ? '<strong>' . $unread_count . '</strong>' : '0'; ?>)
    </a>
    <a href="messages.php?tab=sent"
       class="btn <?php echo $tab === 'sent' ? 'btn-primary' : 'btn-secondary'; ?>"
       style="border-radius: 5px 5px 0 0;">
        📤 Išsiųstos
    </a>
    <a href="messages.php?tab=new"
       class="btn <?php echo $tab === 'new' ? 'btn-primary' : 'btn-secondary'; ?>"
       style="border-radius: 5px 5px 0 0;">
        ✉️ Nauja žinutė
    </a>
</div>

<?php if ($tab === 'new'): ?>
    <!-- Naujos žinutės forma -->
    <div class="auction-details" style="max-width: 800px;">
        <h3>Nauja žinutė</h3>

        <form method="POST" action="">
            <div class="form-group">
                <label for="to_user_id">Gavėjas (Vartotojo ID) <span style="color: red;">*</span></label>
                <input
                    type="number"
                    id="to_user_id"
                    name="to_user_id"
                    class="form-control"
                    required
                    value="<?php echo isset($_GET['to']) ? intval($_GET['to']) : ''; ?>">
                <small style="color: #666;">Įveskite vartotojo ID, kuriam norite siųsti žinutę</small>
            </div>

            <div class="form-group">
                <label for="subject">Tema <span style="color: red;">*</span></label>
                <input
                    type="text"
                    id="subject"
                    name="subject"
                    class="form-control"
                    required
                    maxlength="200"
                    value="<?php echo isset($_GET['subject']) ? htmlspecialchars($_GET['subject']) : ''; ?>">
            </div>

            <div class="form-group">
                <label for="message">Žinutė <span style="color: red;">*</span></label>
                <textarea
                    id="message"
                    name="message"
                    class="form-control"
                    required
                    rows="8"></textarea>
            </div>

            <div class="form-group">
                <label for="auction_id">Aukciono ID (neprivaloma)</label>
                <input
                    type="number"
                    id="auction_id"
                    name="auction_id"
                    class="form-control"
                    value="<?php echo isset($_GET['auction']) ? intval($_GET['auction']) : ''; ?>">
                <small style="color: #666;">Jei žinutė susijusi su konkrečiu aukcione</small>
            </div>

            <button type="submit" name="send_message" class="btn btn-primary">
                Siųsti žinutę
            </button>
        </form>
    </div>

<?php else: ?>
    <!-- Žinučių sąrašas -->
    <?php if (empty($messages)): ?>
        <div class="alert alert-info">
            <?php echo $tab === 'inbox' ? 'Negavote jokių žinučių.' : 'Neišsiuntėte jokių žinučių.'; ?>
        </div>
    <?php else: ?>
        <div style="display: flex; flex-direction: column; gap: 15px;">
            <?php foreach ($messages as $msg): ?>
                <div class="comment" style="<?php echo !$msg['perskaitytas'] && $tab === 'inbox' ? 'border-left: 4px solid #667eea; background: #f0f4ff;' : ''; ?>">
                    <div class="comment-header">
                        <div>
                            <strong>
                                <?php echo $tab === 'inbox' ? '📨 Nuo: ' : '📧 Kam: '; ?>
                                <?php echo htmlspecialchars($tab === 'inbox' ? $msg['siuntejo_vardas'] : $msg['gavejo_vardas']); ?>
                            </strong>
                            <?php if (!$msg['perskaitytas'] && $tab === 'inbox'): ?>
                                <span style="background: #e74c3c; color: white; padding: 2px 8px; border-radius: 10px; font-size: 0.8rem; margin-left: 10px;">
                                    Nauja
                                </span>
                            <?php endif; ?>
                        </div>
                        <span class="comment-time"><?php echo format_datetime($msg['siuntimo_laikas']); ?></span>
                    </div>

                    <div style="margin: 10px 0;">
                        <strong style="color: #667eea;">📋 <?php echo htmlspecialchars($msg['tema']); ?></strong>
                    </div>

                    <div class="comment-text">
                        <?php echo nl2br(htmlspecialchars($msg['tekstas'])); ?>
                    </div>

                    <?php if ($msg['aukciono_pavadinimas']): ?>
                        <div style="margin-top: 10px; padding: 10px; background: #f8f9fa; border-radius: 5px;">
                            🏷️ Susijęs aukcionas: <strong><?php echo htmlspecialchars($msg['aukciono_pavadinimas']); ?></strong>
                            <a href="auction.php?id=<?php echo $msg['aukciono_id']; ?>" class="btn btn-primary" style="padding: 5px 10px; margin-left: 10px;">
                                Peržiūrėti
                            </a>
                        </div>
                    <?php endif; ?>

                    <?php if ($tab === 'inbox'): ?>
                        <div style="margin-top: 10px;">
                            <a href="messages.php?tab=new&to=<?php echo $msg['siuntejas_id']; ?>&subject=RE: <?php echo urlencode($msg['tema']); ?>"
                               class="btn btn-primary" style="padding: 5px 15px;">
                                ↩️ Atsakyti
                            </a>
                            <?php if (!$msg['perskaitytas']): ?>
                                <a href="messages.php?tab=inbox&read=<?php echo $msg['id']; ?>"
                                   class="btn btn-secondary" style="padding: 5px 15px;">
                                    ✓ Pažymėti perskaitytu
                                </a>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
<?php endif; ?>

<div class="alert alert-info mt-20">
    <strong>ℹ️ Kaip naudoti žinutes:</strong>
    <ul style="margin-left: 20px; margin-top: 10px;">
        <li>Norėdami siųsti žinutę, spauskite "Nauja žinutė"</li>
        <li>Į "Gautos" pateks visos gautos žinutės</li>
        <li>Naujias žinutes matysite su "Nauja" žyma</li>
        <li>Galite atsakyti į žinutę paspaudę "Atsakyti"</li>
        <li>Vartotojo ID rasite aukciono puslapyje šalia savininko vardo</li>
    </ul>
</div>

<?php include 'includes/footer.php'; ?>
