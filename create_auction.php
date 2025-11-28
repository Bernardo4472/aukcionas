<?php
/**
 * Aukciono sukūrimo puslapis
 * Sukurta: 2025-11-27
 * Autorius: Rokas Kaziulis
 */

session_start();
require_once 'includes/db.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';
require_once 'includes/extended_functions.php';

// Patikrinti, ar vartotojas prisijungęs
require_login();

// Apdoroti formos pateikimą
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pavadinimas = clean_input($_POST['pavadinimas']);
    $aprasymas = clean_input($_POST['aprasymas']);
    $pradine_kaina = floatval($_POST['pradine_kaina']);
    $bid_step = floatval($_POST['bid_step']);
    $pradzios_laikas = clean_input($_POST['pradzios_laikas']);
    $pabaigos_laikas = clean_input($_POST['pabaigos_laikas']);
    $pasleptas = isset($_POST['pasleptas']) ? 1 : 0;
    $user_id = $_SESSION['user_id'];

    // Validacija
    $errors = [];

    if (empty($pavadinimas)) {
        $errors[] = 'Pavadinimas yra privalomas.';
    }

    if (empty($aprasymas)) {
        $errors[] = 'Aprašymas yra privalomas.';
    }

    if ($pradine_kaina <= 0) {
        $errors[] = 'Pradinė kaina turi būti teigiama.';
    }

    if ($bid_step <= 0) {
        $errors[] = 'Statymo žingsnis turi būti teigiamas.';
    }

    if (strtotime($pradzios_laikas) >= strtotime($pabaigos_laikas)) {
        $errors[] = 'Pabaigos laikas turi būti vėlesnis nei pradžios laikas.';
    }

    if (strtotime($pabaigos_laikas) <= time()) {
        $errors[] = 'Pabaigos laikas turi būti ateityje.';
    }

    // Jei nėra klaidų, sukurti aukcioną
    if (empty($errors)) {
        $stmt = $conn->prepare("INSERT INTO aukcionai (pavadinimas, aprasymas, pradine_kaina, dabartine_kaina, bid_step, pradzios_laikas, pabaigos_laikas, pasleptas, vartotojo_id, busena)
                                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'aktyvus')");
        $stmt->bind_param("ssdddssii", $pavadinimas, $aprasymas, $pradine_kaina, $pradine_kaina, $bid_step, $pradzios_laikas, $pabaigos_laikas, $pasleptas, $user_id);

        if ($stmt->execute()) {
            $auction_id = $conn->insert_id;

            // Įkelti nuotraukas, jei yra
            if (isset($_FILES['photos']) && !empty($_FILES['photos']['name'][0])) {
                $photo_count = count($_FILES['photos']['name']);

                for ($i = 0; $i < $photo_count; $i++) {
                    if ($_FILES['photos']['error'][$i] === UPLOAD_ERR_OK) {
                        $file = [
                            'name' => $_FILES['photos']['name'][$i],
                            'type' => $_FILES['photos']['type'][$i],
                            'tmp_name' => $_FILES['photos']['tmp_name'][$i],
                            'error' => $_FILES['photos']['error'][$i],
                            'size' => $_FILES['photos']['size'][$i]
                        ];
                        upload_auction_photo($auction_id, $file);
                    }
                }
            }

            // Įrašyti auditą
            log_audit($user_id, 'Aukciono sukūrimas', 'Sukurtas naujas aukcionas: ' . $pavadinimas . ' (#' . $auction_id . ')');

            $_SESSION['success'] = 'Aukcionas sėkmingai sukurtas!';
            header("Location: auction.php?id=" . $auction_id);
            exit();
        } else {
            $_SESSION['error'] = 'Įvyko klaida kuriant aukcioną. Bandykite dar kartą.';
        }
    } else {
        $_SESSION['error'] = implode('<br>', $errors);
    }
}

$page_title = 'Sukurti aukcioną';
include 'includes/header.php';
?>

<h2>Sukurti naują aukcioną</h2>

<div class="auction-details" style="max-width: 800px; margin: 0 auto;">
    <form method="POST" action="" id="auctionForm" enctype="multipart/form-data">
        <div class="form-group">
            <label for="pavadinimas">Pavadinimas <span style="color: red;">*</span></label>
            <input
                type="text"
                id="pavadinimas"
                name="pavadinimas"
                class="form-control"
                placeholder="Pvz.: iPhone 15 Pro Max"
                required
                maxlength="200"
                value="<?php echo isset($_POST['pavadinimas']) ? htmlspecialchars($_POST['pavadinimas']) : ''; ?>">
        </div>

        <div class="form-group">
            <label for="aprasymas">Aprašymas <span style="color: red;">*</span></label>
            <textarea
                id="aprasymas"
                name="aprasymas"
                class="form-control"
                placeholder="Detalus prekės aprašymas..."
                required
                rows="5"><?php echo isset($_POST['aprasymas']) ? htmlspecialchars($_POST['aprasymas']) : ''; ?></textarea>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
            <div class="form-group">
                <label for="pradine_kaina">Pradinė kaina (€) <span style="color: red;">*</span></label>
                <input
                    type="number"
                    id="pradine_kaina"
                    name="pradine_kaina"
                    class="form-control"
                    placeholder="0.00"
                    step="0.01"
                    min="0.01"
                    required
                    value="<?php echo isset($_POST['pradine_kaina']) ? $_POST['pradine_kaina'] : ''; ?>">
            </div>

            <div class="form-group">
                <label for="bid_step">Statymo žingsnis (€) <span style="color: red;">*</span></label>
                <input
                    type="number"
                    id="bid_step"
                    name="bid_step"
                    class="form-control"
                    placeholder="5.00"
                    step="0.01"
                    min="0.01"
                    required
                    value="<?php echo isset($_POST['bid_step']) ? $_POST['bid_step'] : '5.00'; ?>">
                <small style="color: #666; display: block; margin-top: 5px;">
                    Minimali suma, kuria kiekvienas statymas turi padidinti kainą
                </small>
            </div>
        </div>

        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
            <div class="form-group">
                <label for="pradzios_laikas">Pradžios laikas <span style="color: red;">*</span></label>
                <input
                    type="datetime-local"
                    id="pradzios_laikas"
                    name="pradzios_laikas"
                    class="form-control"
                    required
                    value="<?php echo isset($_POST['pradzios_laikas']) ? $_POST['pradzios_laikas'] : date('Y-m-d\TH:i'); ?>">
            </div>

            <div class="form-group">
                <label for="pabaigos_laikas">Pabaigos laikas <span style="color: red;">*</span></label>
                <input
                    type="datetime-local"
                    id="pabaigos_laikas"
                    name="pabaigos_laikas"
                    class="form-control"
                    required
                    value="<?php echo isset($_POST['pabaigos_laikas']) ? $_POST['pabaigos_laikas'] : date('Y-m-d\TH:i', strtotime('+7 days')); ?>">
            </div>
        </div>

        <div class="form-group">
            <label for="photos">Nuotraukos (neprivaloma)</label>
            <input
                type="file"
                id="photos"
                name="photos[]"
                class="form-control"
                accept="image/jpeg,image/jpg,image/png,image/gif"
                multiple>
            <small style="color: #666; display: block; margin-top: 5px;">
                Galite įkelti kelias nuotraukas. Leidžiami formatai: JPG, PNG, GIF. Maksimalus dydis: 5MB kiekvienai.
            </small>
        </div>

        <div class="form-group">
            <div class="checkbox-group">
                <input
                    type="checkbox"
                    id="pasleptas"
                    name="pasleptas"
                    value="1"
                    <?php echo (isset($_POST['pasleptas']) && $_POST['pasleptas']) ? 'checked' : ''; ?>>
                <label for="pasleptas" style="margin-bottom: 0;">
                    Paslėpti aukcioną (matomas tik administratoriui ir savininkui)
                </label>
            </div>
        </div>

        <div style="display: flex; gap: 15px; margin-top: 30px;">
            <button type="submit" class="btn btn-primary">
                Sukurti aukcioną
            </button>
            <a href="index.php" class="btn btn-secondary">
                Atšaukti
            </a>
        </div>
    </form>
</div>

<div class="alert alert-info" style="max-width: 800px; margin: 30px auto 0;">
    <strong>Pastabos:</strong>
    <ul style="margin-left: 20px; margin-top: 10px;">
        <li>Visi laukai, pažymėti <span style="color: red;">*</span>, yra privalomi</li>
        <li>Pradinė kaina ir statymo žingsnis turi būti teigiami skaičiai</li>
        <li>Pabaigos laikas turi būti vėlesnis nei pradžios laikas</li>
        <li>Paslėpti aukcionai matomi tik jums ir administratoriui</li>
        <li>Galite statyti bet kuriuo metu tarp pradžios ir pabaigos laiko</li>
    </ul>
</div>

<?php include 'includes/footer.php'; ?>
