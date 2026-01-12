<?php
/**
 * Prisijungimo puslapis
 * Sukurta: 2025-11-27
 * Autorius: Rokas Kaziulis
 */

session_start();
require_once 'includes/db.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';
require_once 'includes/extended_functions.php';

// Jei vartotojas jau prisijungęs, peradresuoti į pagrindinį puslapį
if (is_logged_in()) {
    header("Location: index.php");
    exit();
}

// Patikrinti, ar IP adresas užblokuotas
$user_ip = get_user_ip();
if (is_ip_blocked($user_ip)) {
    $_SESSION['error'] = 'Jūsų IP adresas užblokuotas. Susisiekite su administracija.';
    header("Location: login.php");
    exit();
}

// Apdoroti prisijungimo formą
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $el_pastas = clean_input($_POST['el_pastas']);
    $slaptazodis = $_POST['slaptazodis'];

    $result = login_user($el_pastas, $slaptazodis);

    if ($result['success']) {
        // Įrašyti sėkmingą prisijungimą
        log_ip_activity($_SESSION['user_id'], 'Prisijungimas', 'Sėkmingas prisijungimas: ' . $el_pastas);

        $_SESSION['success'] = $result['message'];
        header("Location: index.php");
        exit();
    } else {
        // Įrašyti nesėkmingą prisijungimo bandymą
        log_ip_activity(null, 'Nesėkmingas prisijungimas', 'Bandymas prisijungti su: ' . $el_pastas);

        $_SESSION['error'] = $result['message'];
    }
}

$page_title = 'Prisijungimas';
include 'includes/header.php';
?>

<div class="auth-container">
    <h2>Prisijungimas</h2>

    <p class="text-center mb-20">Prisijunkite prie savo paskyros</p>

    <form method="POST" action="">
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
            <label for="slaptazodis">Slaptazodis <span style="color: red;">*</span></label>
            <input
                type="password"
                id="slaptazodis"
                name="slaptazodis"
                class="form-control"
                placeholder="Iveskite slaptazodi"
                required>
        </div>

        <button type="submit" class="btn btn-primary btn-block">
            Prisijungti
        </button>
    </form>

    <div class="auth-links">
        <p>Neturite paskyros? <a href="register.php">Registruokitės čia</a></p>
    </div>

    <div class="alert alert-info mt-20">
        <strong>Demonstracines paskyros:</strong>
        <ul style="margin-left: 20px; margin-top: 10px;">
            <li><strong>Administratorius:</strong> a@a / 1</li>
            <li><strong>Buhalteris:</strong> b@b / 1</li>
            <li><strong>Moderatorius:</strong> m@m / 1</li>
            <li><strong>Vartotojas:</strong> u@u / 1</li>
        </ul>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
