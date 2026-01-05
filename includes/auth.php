<?php
/**
 * Autentifikacijos funkcijos
 * Sukurta: 2025-11-27
 * Autorius: Rokas Kaziulis
 */

/**
 * Registruoti naują vartotoją
 * @param string $vardas - vartotojo vardas
 * @param string $el_pastas - el. paštas
 * @param string $slaptazodis - slaptažodis
 * @return array - rezultatas su 'success' ir 'message'
 */
function register_user($vardas, $el_pastas, $slaptazodis) {
    global $conn;

    // Validacija
    if (empty($vardas) || empty($el_pastas) || empty($slaptazodis)) {
        return ['success' => false, 'message' => 'Visi laukai yra privalomi.'];
    }

    // Patikrinti el. pašto formatą
    if (!filter_var($el_pastas, FILTER_VALIDATE_EMAIL)) {
        return ['success' => false, 'message' => 'Neteisingas el. pašto formatas.'];
    }

    // Patikrinti slaptažodžio ilgį (minimum 8 simboliai)
    if (strlen($slaptazodis) < 8) {
        return ['success' => false, 'message' => 'Slaptažodis turi būti bent 8 simbolių ilgio.'];
    }

    // Patikrinti, ar el. paštas jau egzistuoja
    $stmt = $conn->prepare("SELECT id FROM vartotojai WHERE el_pastas = ?");
    $stmt->bind_param("s", $el_pastas);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        return ['success' => false, 'message' => 'Šis el. paštas jau užregistruotas.'];
    }

    // Užšifruoti slaptažodį
    $hashed_password = password_hash($slaptazodis, PASSWORD_DEFAULT);

    // Įrašyti vartotoją į duomenų bazę
    $stmt = $conn->prepare("INSERT INTO vartotojai (vardas, el_pastas, slaptazodis, role, balansas) VALUES (?, ?, ?, 'user', 1000.00)");
    $stmt->bind_param("sss", $vardas, $el_pastas, $hashed_password);

    if ($stmt->execute()) {
        $user_id = $conn->insert_id;

        // Įrašyti pradinį balansą į transakcijas
        $stmt = $conn->prepare("INSERT INTO transakcijos (vartotojo_id, suma, tipas, aprasymas) VALUES (?, 1000.00, 'papildymas', 'Pradinis balansas')");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();

        // Įrašyti auditą
        log_audit($user_id, 'Registracija', 'Naujas vartotojas užsiregistravo: ' . $el_pastas);

        return ['success' => true, 'message' => 'Registracija sėkminga! Galite prisijungti su savo el. paštu.'];
    } else {
        return ['success' => false, 'message' => 'Įvyko klaida registracijos metu. Bandykite dar kartą.'];
    }
}

/**
 * Prisijungti vartotojui
 * @param string $el_pastas - el. paštas
 * @param string $slaptazodis - slaptažodis (neprivalomas)
 * @return array - rezultatas su 'success' ir 'message'
 */
function login_user($el_pastas, $slaptazodis) {
    global $conn;

    // Validacija - tik el. paštas privalomas
    if (empty($el_pastas)) {
        return ['success' => false, 'message' => 'El. paštas yra privalomas.'];
    }

    // Patikrinti, ar vartotojas egzistuoja
    $stmt = $conn->prepare("SELECT * FROM vartotojai WHERE el_pastas = ?");
    $stmt->bind_param("s", $el_pastas);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        return ['success' => false, 'message' => 'Vartotojas su šiuo el. paštu nerastas.'];
    }

    $user = $result->fetch_assoc();

    // Patikrinti slaptažodį tik jei jis pateiktas
    if (!empty($slaptazodis)) {
        if (!password_verify($slaptazodis, $user['slaptazodis'])) {
            return ['success' => false, 'message' => 'Neteisingas slaptažodis.'];
        }
    }

    // Sukurti sesiją
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['vardas'] = $user['vardas'];
    $_SESSION['role'] = $user['role'];

    // Įrašyti auditą
    log_audit($user['id'], 'Prisijungimas', 'Vartotojas prisijungė: ' . $el_pastas);

    return ['success' => true, 'message' => 'Sveiki sugrįžę, ' . $user['vardas'] . '!'];
}

/**
 * Atsijungti vartotojui
 */
function logout_user() {
    if (is_logged_in()) {
        $user = get_logged_in_user();
        log_audit($user['id'], 'Atsijungimas', 'Vartotojas atsijungė: ' . $user['el_pastas']);
    }

    session_unset();
    session_destroy();
}
