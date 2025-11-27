<?php
/**
 * Duomenų bazės prisijungimo failas
 * Sukurta: 2025-11-27
 * Autorius: Rokas Kaziulis
 */

// Duomenų bazės konfigūracija
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'aukcionas');

// Sukurti prisijungimą prie MySQL duomenų bazės
$conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

// Patikrinti prisijungimą
if ($conn->connect_error) {
    die("Nepavyko prisijungti prie duomenų bazės: " . $conn->connect_error);
}

// Nustatyti UTF-8 kodavimą
$conn->set_charset("utf8mb4");

/**
 * Apsaugoti nuo SQL injekcijų
 * @param string $data - duomenys, kuriuos reikia apsaugoti
 * @return string - apsaugoti duomenys
 */
function clean_input($data) {
    global $conn;
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $conn->real_escape_string($data);
}

/**
 * Patikrinti, ar vartotojas prisijungęs
 * @return bool
 */
function is_logged_in() {
    return isset($_SESSION['user_id']);
}

/**
 * Gauti dabartinio vartotojo informaciją
 * @return array|null
 */
function get_logged_in_user() {
    global $conn;

    if (!is_logged_in()) {
        return null;
    }

    $user_id = $_SESSION['user_id'];
    $stmt = $conn->prepare("SELECT * FROM vartotojai WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        return $result->fetch_assoc();
    }

    return null;
}

/**
 * Patikrinti vartotojo rolę
 * @param string $required_role - reikalaujama rolė
 * @return bool
 */
function has_role($required_role) {
    $user = get_logged_in_user();

    if (!$user) {
        return false;
    }

    // Admin turi visas teises
    if ($user['role'] === 'admin') {
        return true;
    }

    return $user['role'] === $required_role;
}

/**
 * Patikrinti, ar vartotojas turi bent vieną iš nurodytų rolių
 * @param array $roles - rolių masyvas
 * @return bool
 */
function has_any_role($roles) {
    $user = get_logged_in_user();

    if (!$user) {
        return false;
    }

    // Admin turi visas teises
    if ($user['role'] === 'admin') {
        return true;
    }

    return in_array($user['role'], $roles);
}

/**
 * Peradresuoti į prisijungimo puslapį, jei vartotojas neprisijungęs
 */
function require_login() {
    if (!is_logged_in()) {
        header("Location: login.php");
        exit();
    }
}

/**
 * Peradresuoti, jei vartotojas neturi reikiamos rolės
 * @param string $required_role - reikalaujama rolė
 */
function require_role($required_role) {
    require_login();

    if (!has_role($required_role)) {
        $_SESSION['error'] = "Neturite teisės pasiekti šio puslapio.";
        header("Location: index.php");
        exit();
    }
}

/**
 * Įrašyti auditą į audit_log lentelę
 * @param int $user_id - vartotojo ID
 * @param string $action - veiksmas
 * @param string $description - aprašymas
 */
function log_audit($user_id, $action, $description = '') {
    global $conn;

    $stmt = $conn->prepare("INSERT INTO audit_log (vartotojo_id, veiksmas, aprasymas) VALUES (?, ?, ?)");
    $stmt->bind_param("iss", $user_id, $action, $description);
    $stmt->execute();
}
