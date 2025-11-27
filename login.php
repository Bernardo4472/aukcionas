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

// Jei vartotojas jau prisijungęs, peradresuoti į pagrindinį puslapį
if (is_logged_in()) {
    header("Location: index.php");
    exit();
}

// Apdoroti prisijungimo formą
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $el_pastas = clean_input($_POST['el_pastas']);
    $slaptazodis = $_POST['slaptazodis'];

    $result = login_user($el_pastas, $slaptazodis);

    if ($result['success']) {
        $_SESSION['success'] = $result['message'];
        header("Location: index.php");
        exit();
    } else {
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
            <label for="slaptazodis">Slaptažodis <span style="color: red;">*</span></label>
            <input
                type="password"
                id="slaptazodis"
                name="slaptazodis"
                class="form-control"
                placeholder="Įveskite slaptažodį"
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
        <strong>Demonstracinės paskyros:</strong>
        <ul style="margin-left: 20px; margin-top: 10px;">
            <li><strong>Administratorius:</strong> admin@ktu.lt / admin123</li>
            <li><strong>Buhalteris:</strong> accountant@ktu.lt / acc123</li>
            <li><strong>Moderatorius:</strong> moderator@ktu.lt / admin123</li>
            <li><strong>Vartotojas:</strong> user@ktu.lt / user123</li>
        </ul>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
