<?php
/**
 * Registracijos puslapis
 * Sukurta: 2025-11-27
 * Autorius: Rokas Kaziulis
 */

session_start();
require_once 'includes/db.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';

// Jei vartotojas jau prisijungęs, peradresuoti į pagrindinį puslapį
if (is_logged_in()) {
    header("Location: index.php");
    exit();
}

// Apdoroti registracijos formą
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $vardas = clean_input($_POST['vardas']);
    $el_pastas = clean_input($_POST['el_pastas']);
    $slaptazodis = $_POST['slaptazodis'];

    $result = register_user($vardas, $el_pastas, $slaptazodis);

    if ($result['success']) {
        $_SESSION['success'] = $result['message'];
        header("Location: login.php");
        exit();
    } else {
        $_SESSION['error'] = $result['message'];
    }
}

$page_title = 'Registracija';
include 'includes/header.php';
?>

<div class="auth-container">
    <h2>Registracija</h2>

    <p class="text-center mb-20">Sukurkite savo paskyrą ir pradėkite dalyvauti aukcionuose!</p>

    <form method="POST" action="" id="registerForm">
        <div class="form-group">
            <label for="vardas">Vardas <span style="color: red;">*</span></label>
            <input
                type="text"
                id="vardas"
                name="vardas"
                class="form-control"
                placeholder="Įveskite savo vardą"
                required
                value="<?php echo isset($_POST['vardas']) ? htmlspecialchars($_POST['vardas']) : ''; ?>">
        </div>

        <div class="form-group">
            <label for="el_pastas">El. paštas <span style="color: red;">*</span></label>
            <input
                type="email"
                id="el_pastas"
                name="el_pastas"
                class="form-control"
                placeholder="vardas@example.com"
                required
                value="<?php echo isset($_POST['el_pastas']) ? htmlspecialchars($_POST['el_pastas']) : ''; ?>">
        </div>

        <div class="form-group">
            <label for="slaptazodis">Slaptažodis <span style="color: red;">*</span></label>
            <input
                type="password"
                id="slaptazodis"
                name="slaptazodis"
                class="form-control"
                placeholder="Bent 8 simboliai"
                required
                minlength="8">
            <small style="color: #666; display: block; margin-top: 5px;">
                Slaptažodis turi būti bent 8 simbolių ilgio.
            </small>
        </div>

        <button type="submit" class="btn btn-primary btn-block">
            Registruotis
        </button>
    </form>

    <div class="auth-links">
        <p>Jau turite paskyrą? <a href="login.php">Prisijunkite čia</a></p>
    </div>

    <div class="alert alert-info mt-20">
        <strong>Pastabos:</strong>
        <ul style="margin-left: 20px; margin-top: 10px;">
            <li>Registracija suteikia pradinį balansą 1000 €</li>
            <li>El. pašto adresas turi būti unikalus</li>
            <li>Slaptažodis yra saugiai šifruojamas</li>
        </ul>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
