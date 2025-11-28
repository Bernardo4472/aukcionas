<?php
/**
 * Išplėstinės funkcijos - nuotraukos, žinutės, reitingai, IP valdymas
 * Sukurta: 2025-11-28
 * Autorius: Rokas Kaziulis
 */

/**
 * Gauti vartotojo IP adresą
 * @return string
 */
function get_user_ip() {
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        return $_SERVER['HTTP_CLIENT_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        return $_SERVER['HTTP_X_FORWARDED_FOR'];
    } else {
        return $_SERVER['REMOTE_ADDR'];
    }
}

/**
 * Patikrinti, ar IP adresas užblokuotas
 * @param string $ip - IP adresas
 * @return bool
 */
function is_ip_blocked($ip) {
    global $conn;

    $stmt = $conn->prepare("SELECT id FROM ip_blokavimai
                            WHERE ip_adresas = ?
                            AND aktyvus = TRUE
                            AND (galioja_iki IS NULL OR galioja_iki > NOW())");
    $stmt->bind_param("s", $ip);
    $stmt->execute();
    $result = $stmt->get_result();

    return $result->num_rows > 0;
}

/**
 * Įrašyti IP veiklą
 * @param int $user_id - vartotojo ID (gali būti null)
 * @param string $action - veiksmas
 * @param string $description - aprašymas
 */
function log_ip_activity($user_id, $action, $description = '') {
    global $conn;

    $ip = get_user_ip();

    $stmt = $conn->prepare("INSERT INTO ip_veikla (vartotojo_id, ip_adresas, veiksmas, aprasymas)
                            VALUES (?, ?, ?, ?)");
    $stmt->bind_param("isss", $user_id, $ip, $action, $description);
    $stmt->execute();
}

/**
 * Įkelti aukciono nuotrauką
 * @param int $auction_id - aukciono ID
 * @param array $file - $_FILES masyvo elementas
 * @return array - rezultatas su 'success' ir 'message'
 */
function upload_auction_photo($auction_id, $file) {
    global $conn;

    // Patikrinti, ar failas buvo įkeltas
    if (!isset($file) || $file['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'message' => 'Klaida įkeliant nuotrauką.'];
    }

    // Patikrinti failo tipą
    $allowed_types = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
    if (!in_array($file['type'], $allowed_types)) {
        return ['success' => false, 'message' => 'Leidžiami tik JPG, PNG ir GIF failai.'];
    }

    // Patikrinti failo dydį (max 5MB)
    if ($file['size'] > 5 * 1024 * 1024) {
        return ['success' => false, 'message' => 'Failas per didelis. Maksimalus dydis: 5MB.'];
    }

    // Generuoti unikalų failo pavadinimą
    $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = 'auction_' . $auction_id . '_' . uniqid() . '.' . $extension;
    $upload_path = __DIR__ . '/../uploads/' . $filename;

    // Perkelti failą
    if (move_uploaded_file($file['tmp_name'], $upload_path)) {
        // Įrašyti į duomenų bazę
        $stmt = $conn->prepare("INSERT INTO aukciono_nuotraukos (aukciono_id, failo_pavadinimas, originalus_pavadinimas)
                                VALUES (?, ?, ?)");
        $stmt->bind_param("iss", $auction_id, $filename, $file['name']);

        if ($stmt->execute()) {
            return ['success' => true, 'message' => 'Nuotrauka sėkmingai įkelta.', 'filename' => $filename];
        } else {
            unlink($upload_path); // Ištrinti failą, jei nepavyko įrašyti į DB
            return ['success' => false, 'message' => 'Klaida išsaugant nuotrauką.'];
        }
    } else {
        return ['success' => false, 'message' => 'Klaida perkeliant failą.'];
    }
}

/**
 * Gauti aukciono nuotraukas
 * @param int $auction_id - aukciono ID
 * @return array - nuotraukų masyvas
 */
function get_auction_photos($auction_id) {
    global $conn;

    $stmt = $conn->prepare("SELECT * FROM aukciono_nuotraukos WHERE aukciono_id = ? ORDER BY ikelimo_data ASC");
    $stmt->bind_param("i", $auction_id);
    $stmt->execute();
    $result = $stmt->get_result();

    return $result->fetch_all(MYSQLI_ASSOC);
}

/**
 * Ištrinti aukciono nuotrauką
 * @param int $photo_id - nuotraukos ID
 * @param int $user_id - vartotojo ID (patikrinimui)
 * @return bool
 */
function delete_auction_photo($photo_id, $user_id) {
    global $conn;

    // Patikrinti, ar vartotojas yra nuotraukos aukciono savininkas
    $stmt = $conn->prepare("SELECT n.failo_pavadinimas, a.vartotojo_id
                            FROM aukciono_nuotraukos n
                            JOIN aukcionai a ON n.aukciono_id = a.id
                            WHERE n.id = ?");
    $stmt->bind_param("i", $photo_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        return false;
    }

    $photo = $result->fetch_assoc();

    // Patikrinti teises
    if ($photo['vartotojo_id'] != $user_id && !has_role('admin')) {
        return false;
    }

    // Ištrinti failą
    $file_path = __DIR__ . '/../uploads/' . $photo['failo_pavadinimas'];
    if (file_exists($file_path)) {
        unlink($file_path);
    }

    // Ištrinti iš DB
    $stmt = $conn->prepare("DELETE FROM aukciono_nuotraukos WHERE id = ?");
    $stmt->bind_param("i", $photo_id);
    return $stmt->execute();
}

/**
 * Siųsti žinutę vartotojui
 * @param int $from_user_id - siuntėjo ID
 * @param int $to_user_id - gavėjo ID
 * @param string $subject - tema
 * @param string $message - žinutės tekstas
 * @param int $auction_id - aukciono ID (neprivaloma)
 * @return array - rezultatas
 */
function send_message($from_user_id, $to_user_id, $subject, $message, $auction_id = null) {
    global $conn;

    if (empty($subject) || empty($message)) {
        return ['success' => false, 'message' => 'Tema ir tekstas yra privalomi.'];
    }

    $stmt = $conn->prepare("INSERT INTO zinutes (siuntejas_id, gavejas_id, tema, tekstas, aukciono_id)
                            VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("iissi", $from_user_id, $to_user_id, $subject, $message, $auction_id);

    if ($stmt->execute()) {
        return ['success' => true, 'message' => 'Žinutė išsiųsta.'];
    } else {
        return ['success' => false, 'message' => 'Klaida siunčiant žinutę.'];
    }
}

/**
 * Gauti vartotojo gautasžinutes
 * @param int $user_id - vartotojo ID
 * @param int $limit - kiek žinučių rodyti
 * @return array - žinučių masyvas
 */
function get_inbox_messages($user_id, $limit = 50) {
    global $conn;

    $stmt = $conn->prepare("SELECT z.*, v.vardas as siuntejo_vardas, a.pavadinimas as aukciono_pavadinimas
                            FROM zinutes z
                            JOIN vartotojai v ON z.siuntejas_id = v.id
                            LEFT JOIN aukcionai a ON z.aukciono_id = a.id
                            WHERE z.gavejas_id = ?
                            ORDER BY z.siuntimo_laikas DESC
                            LIMIT ?");
    $stmt->bind_param("ii", $user_id, $limit);
    $stmt->execute();
    $result = $stmt->get_result();

    return $result->fetch_all(MYSQLI_ASSOC);
}

/**
 * Gauti vartotojo išsiųstas žinutes
 * @param int $user_id - vartotojo ID
 * @param int $limit - kiek žinučių rodyti
 * @return array - žinučių masyvas
 */
function get_sent_messages($user_id, $limit = 50) {
    global $conn;

    $stmt = $conn->prepare("SELECT z.*, v.vardas as gavejo_vardas, a.pavadinimas as aukciono_pavadinimas
                            FROM zinutes z
                            JOIN vartotojai v ON z.gavejas_id = v.id
                            LEFT JOIN aukcionai a ON z.aukciono_id = a.id
                            WHERE z.siuntejas_id = ?
                            ORDER BY z.siuntimo_laikas DESC
                            LIMIT ?");
    $stmt->bind_param("ii", $user_id, $limit);
    $stmt->execute();
    $result = $stmt->get_result();

    return $result->fetch_all(MYSQLI_ASSOC);
}

/**
 * Pažymėti žinutę kaip perskaitytą
 * @param int $message_id - žinutės ID
 * @param int $user_id - vartotojo ID (gavėjo)
 * @return bool
 */
function mark_message_read($message_id, $user_id) {
    global $conn;

    $stmt = $conn->prepare("UPDATE zinutes SET perskaitytas = TRUE
                            WHERE id = ? AND gavejas_id = ?");
    $stmt->bind_param("ii", $message_id, $user_id);
    return $stmt->execute();
}

/**
 * Gauti neperskaitytų žinučių skaičių
 * @param int $user_id - vartotojo ID
 * @return int
 */
function get_unread_messages_count($user_id) {
    global $conn;

    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM zinutes
                            WHERE gavejas_id = ? AND perskaitytas = FALSE");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();

    return (int)$row['count'];
}

/**
 * Pridėti atsiliepimą vartotojui
 * @param int $from_user_id - kas vertina
 * @param int $about_user_id - apie ką
 * @param int $rating - įvertinimas (1-5)
 * @param string $comment - komentaras
 * @param string $type - tipas (teigiamas/neigiamas/neutralus)
 * @param int $auction_id - aukciono ID
 * @return array - rezultatas
 */
function add_feedback($from_user_id, $about_user_id, $rating, $comment, $type, $auction_id = null) {
    global $conn;

    if ($from_user_id == $about_user_id) {
        return ['success' => false, 'message' => 'Negalite vertinti savęs.'];
    }

    if ($rating < 1 || $rating > 5) {
        return ['success' => false, 'message' => 'Įvertinimas turi būti nuo 1 iki 5.'];
    }

    $stmt = $conn->prepare("INSERT INTO atsiliepimai (nuo_vartotojo_id, apie_vartotoja_id, ivertinimas, komentaras, tipas, aukciono_id)
                            VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("iiissi", $from_user_id, $about_user_id, $rating, $comment, $type, $auction_id);

    if ($stmt->execute()) {
        return ['success' => true, 'message' => 'Atsiliepimas pridėtas.'];
    } else {
        return ['success' => false, 'message' => 'Klaida pridedant atsiliepimą.'];
    }
}

/**
 * Gauti vartotojo atsiliepimus
 * @param int $user_id - vartotojo ID
 * @return array - atsiliepimų masyvas
 */
function get_user_feedback($user_id) {
    global $conn;

    $stmt = $conn->prepare("SELECT a.*, v.vardas as nuo_vartotojo_vardas
                            FROM atsiliepimai a
                            JOIN vartotojai v ON a.nuo_vartotojo_id = v.id
                            WHERE a.apie_vartotoja_id = ?
                            ORDER BY a.data_laikas DESC");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    return $result->fetch_all(MYSQLI_ASSOC);
}

/**
 * Gauti vartotojo reitingo statistiką
 * @param int $user_id - vartotojo ID
 * @return array - statistika
 */
function get_user_rating_stats($user_id) {
    global $conn;

    $stmt = $conn->prepare("SELECT
                            COUNT(*) as bendras_skaicius,
                            AVG(ivertinimas) as vidutinis_ivertinimas,
                            SUM(CASE WHEN tipas = 'teigiamas' THEN 1 ELSE 0 END) as teigiamu,
                            SUM(CASE WHEN tipas = 'neigiamas' THEN 1 ELSE 0 END) as neigiamu,
                            SUM(CASE WHEN tipas = 'neutralus' THEN 1 ELSE 0 END) as neutraliu
                            FROM atsiliepimai
                            WHERE apie_vartotoja_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();

    return $result->fetch_assoc();
}

/**
 * Gauti vartotojo pardavimų skaičių
 * @param int $user_id - vartotojo ID
 * @return int
 */
function get_user_sales_count($user_id) {
    global $conn;

    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM aukcionai
                            WHERE vartotojo_id = ? AND busena = 'pasibaiges'");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();

    return (int)$row['count'];
}

/**
 * Gauti vartotojo pirkimų skaičių (laimėti aukcionai)
 * @param int $user_id - vartotojo ID
 * @return int
 */
function get_user_purchases_count($user_id) {
    global $conn;

    // Rasti aukcionus, kur vartotojas yra aukščiausias statytojas
    $stmt = $conn->prepare("SELECT COUNT(DISTINCT a.id) as count
                            FROM aukcionai a
                            JOIN statymai s ON a.id = s.aukciono_id
                            WHERE a.busena = 'pasibaiges'
                            AND s.vartotojo_id = ?
                            AND s.suma = a.dabartine_kaina");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $row = $result->fetch_assoc();

    return (int)$row['count'];
}
