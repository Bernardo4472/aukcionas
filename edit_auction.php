<?php
/**
 * Aukciono redagavimo puslapis
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

// Patikrinti, ar vartotojas yra aukciono savininkas arba admin
if ($auction['savininko_id'] != $user['id'] && !has_role('admin')) {
    $_SESSION['error'] = 'Neturite teisės redaguoti šio aukciono.';
    header("Location: index.php");
    exit();
}

// Apdoroti formos pateikimą
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_auction'])) {
    $pavadinimas = clean_input($_POST['pavadinimas']);
    $aprasymas = clean_input($_POST['aprasymas']);
    $pabaigos_laikas = clean_input($_POST['pabaigos_laikas']);
    $pasleptas = isset($_POST['pasleptas']) ? 1 : 0;

    // Validacija
    $errors = [];

    if (empty($pavadinimas)) {
        $errors[] = 'Pavadinimas yra privalomas.';
    }

    if (empty($aprasymas)) {
        $errors[] = 'Aprašymas yra privalomas.';
    }

    if (strtotime($pabaigos_laikas) <= time()) {
        $errors[] = 'Pabaigos laikas turi būti ateityje.';
    }

    // Jei nėra klaidų, atnaujinti aukcioną
    if (empty($errors)) {
        $stmt = $conn->prepare("UPDATE aukcionai
                                SET pavadinimas = ?, aprasymas = ?, pabaigos_laikas = ?, pasleptas = ?
                                WHERE id = ?");
        $stmt->bind_param("sssii", $pavadinimas, $aprasymas, $pabaigos_laikas, $pasleptas, $auction_id);

        if ($stmt->execute()) {
            log_audit($user['id'], 'Aukciono redagavimas', 'Redaguotas aukcionas #' . $auction_id);
            $_SESSION['success'] = 'Aukcionas sėkmingai atnaujintas!';
            header("Location: auction.php?id=" . $auction_id);
            exit();
        } else {
            $_SESSION['error'] = 'Įvyko klaida atnaujinant aukcioną.';
        }
    } else {
        $_SESSION['error'] = implode('<br>', $errors);
    }
}

// Apdoroti nuotraukos įkėlimą
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['upload_photo']) && isset($_FILES['photo'])) {
    $result = upload_auction_photo($auction_id, $_FILES['photo']);

    if ($result['success']) {
        $_SESSION['success'] = $result['message'];
    } else {
        $_SESSION['error'] = $result['message'];
    }

    header("Location: edit_auction.php?id=" . $auction_id);
    exit();
}

// Apdoroti nuotraukos ištrynimą
if (isset($_GET['delete_photo'])) {
    $photo_id = intval($_GET['delete_photo']);

    if (delete_auction_photo($photo_id, $user['id'])) {
        $_SESSION['success'] = 'Nuotrauka ištrinta.';
    } else {
        $_SESSION['error'] = 'Nepavyko ištrinti nuotraukos.';
    }

    header("Location: edit_auction.php?id=" . $auction_id);
    exit();
}

$photos = get_auction_photos($auction_id);

$page_title = 'Redaguoti aukcioną';
include 'includes/header.php';
?>

<h2>Redaguoti aukcioną</h2>

<div style="margin-bottom: 20px;">
    <a href="auction.php?id=<?php echo $auction_id; ?>" class="btn btn-secondary">← Grįžti į aukcioną</a>
</div>

<!-- Aukciono informacija -->
<div class="auction-details" style="max-width: 900px;">
    <div class="alert alert-info">
        <strong>ℹ️ Svarbu:</strong> Galite redaguoti tik kai kuriuos laukus. Pradinė kaina ir statymo žingsnis negali būti keičiami po aukciono pradžios.
    </div>

    <form method="POST" action="" enctype="multipart/form-data">
        <div class="form-group">
            <label for="pavadinimas">Pavadinimas <span style="color: red;">*</span></label>
            <input
                type="text"
                id="pavadinimas"
                name="pavadinimas"
                class="form-control"
                required
                maxlength="200"
                value="<?php echo htmlspecialchars($auction['pavadinimas']); ?>">
        </div>

        <div class="form-group">
            <label for="aprasymas">Aprašymas <span style="color: red;">*</span></label>
            <textarea
                id="aprasymas"
                name="aprasymas"
                class="form-control"
                required
                rows="6"><?php echo htmlspecialchars($auction['aprasymas']); ?></textarea>
        </div>

        <div class="form-group">
            <label>Pradinė kaina</label>
            <input
                type="text"
                class="form-control"
                value="<?php echo format_money($auction['pradine_kaina']); ?>"
                disabled>
            <small style="color: #666;">Šio lauko negalima keisti</small>
        </div>

        <div class="form-group">
            <label>Dabartinė kaina</label>
            <input
                type="text"
                class="form-control"
                value="<?php echo format_money($auction['dabartine_kaina']); ?>"
                disabled>
            <small style="color: #666;">Kaina keičiasi automatiškai su statymais</small>
        </div>

        <div class="form-group">
            <label for="pabaigos_laikas">Pabaigos laikas <span style="color: red;">*</span></label>
            <input
                type="datetime-local"
                id="pabaigos_laikas"
                name="pabaigos_laikas"
                class="form-control"
                required
                value="<?php echo date('Y-m-d\TH:i', strtotime($auction['pabaigos_laikas'])); ?>">
        </div>

        <div class="form-group">
            <div class="checkbox-group">
                <input
                    type="checkbox"
                    id="pasleptas"
                    name="pasleptas"
                    value="1"
                    <?php echo $auction['pasleptas'] ? 'checked' : ''; ?>>
                <label for="pasleptas" style="margin-bottom: 0;">
                    Paslėpti aukcioną (matomas tik administratoriui ir savininkui)
                </label>
            </div>
        </div>

        <button type="submit" name="update_auction" class="btn btn-primary">
            Atnaujinti aukcioną
        </button>
    </form>
</div>

<!-- Nuotraukų valdymas -->
<div class="auction-details" style="max-width: 900px; margin-top: 30px;">
    <h3>📸 Aukciono nuotraukos</h3>

    <!-- Esamos nuotraukos -->
    <?php if (!empty($photos)): ?>
        <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 15px; margin-bottom: 30px;">
            <?php foreach ($photos as $photo): ?>
                <div style="border: 2px solid #e0e0e0; border-radius: 8px; padding: 10px; position: relative;">
                    <img src="uploads/<?php echo htmlspecialchars($photo['failo_pavadinimas']); ?>"
                         alt="<?php echo htmlspecialchars($photo['originalus_pavadinimas']); ?>"
                         style="width: 100%; height: 200px; object-fit: cover; border-radius: 5px;">
                    <div style="margin-top: 10px; font-size: 0.85rem; color: #666;">
                        <?php echo htmlspecialchars($photo['originalus_pavadinimas']); ?>
                    </div>
                    <a href="edit_auction.php?id=<?php echo $auction_id; ?>&delete_photo=<?php echo $photo['id']; ?>"
                       class="btn btn-danger btn-block"
                       style="margin-top: 10px; padding: 5px;"
                       onclick="return confirm('Ar tikrai norite ištrinti šią nuotrauką?')">
                        🗑️ Ištrinti
                    </a>
                </div>
            <?php endforeach; ?>
        </div>
    <?php else: ?>
        <p style="color: #999;">Aukcionas neturi nuotraukų.</p>
    <?php endif; ?>

    <!-- Nuotraukos įkėlimo forma -->
    <div style="background: #f8f9fa; padding: 20px; border-radius: 8px;">
        <h4>Pridėti naują nuotrauką</h4>

        <form method="POST" action="" enctype="multipart/form-data">
            <div class="form-group">
                <label for="photo">Pasirinkite nuotrauką</label>
                <input
                    type="file"
                    id="photo"
                    name="photo"
                    class="form-control"
                    accept="image/jpeg,image/jpg,image/png,image/gif"
                    required>
                <small style="color: #666; display: block; margin-top: 5px;">
                    Leidžiami formatai: JPG, PNG, GIF. Maksimalus dydis: 5MB
                </small>
            </div>

            <button type="submit" name="upload_photo" class="btn btn-success">
                📤 Įkelti nuotrauką
            </button>
        </form>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
