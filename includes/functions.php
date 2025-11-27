<?php
/**
 * Pagalbinės funkcijos
 * Sukurta: 2025-11-27
 * Autorius: Rokas Kaziulis
 */

/**
 * Formatuoti sumą eurais
 * @param float $amount - suma
 * @return string - suformatuota suma
 */
function format_money($amount) {
    return number_format($amount, 2, ',', ' ') . ' €';
}

/**
 * Formatuoti datą ir laiką
 * @param string $datetime - data ir laikas
 * @return string - suformatuota data ir laikas
 */
function format_datetime($datetime) {
    return date('Y-m-d H:i', strtotime($datetime));
}

/**
 * Gauti visus aktyvius aukcionus
 * @param bool $include_hidden - ar įtraukti paslėptus aukcionus
 * @return array - aukcionų masyvas
 */
function get_active_auctions($include_hidden = false) {
    global $conn;

    $query = "SELECT a.*, v.vardas as savininkas
              FROM aukcionai a
              JOIN vartotojai v ON a.vartotojo_id = v.id
              WHERE a.busena = 'aktyvus'
              AND a.pradzios_laikas <= NOW()
              AND a.pabaigos_laikas > NOW()";

    if (!$include_hidden) {
        $query .= " AND a.pasleptas = FALSE";
    }

    $query .= " ORDER BY a.sukurimo_data DESC";

    $result = $conn->query($query);
    return $result->fetch_all(MYSQLI_ASSOC);
}

/**
 * Gauti aukcioną pagal ID
 * @param int $auction_id - aukciono ID
 * @return array|null - aukciono duomenys arba null
 */
function get_auction($auction_id) {
    global $conn;

    $stmt = $conn->prepare("SELECT a.*, v.vardas as savininkas, v.id as savininko_id
                            FROM aukcionai a
                            JOIN vartotojai v ON a.vartotojo_id = v.id
                            WHERE a.id = ?");
    $stmt->bind_param("i", $auction_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        return $result->fetch_assoc();
    }

    return null;
}

/**
 * Gauti aukščiausią statymą aukcione
 * @param int $auction_id - aukciono ID
 * @return array|null - statymo duomenys arba null
 */
function get_highest_bid($auction_id) {
    global $conn;

    $stmt = $conn->prepare("SELECT s.*, v.vardas
                            FROM statymai s
                            JOIN vartotojai v ON s.vartotojo_id = v.id
                            WHERE s.aukciono_id = ?
                            ORDER BY s.suma DESC
                            LIMIT 1");
    $stmt->bind_param("i", $auction_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        return $result->fetch_assoc();
    }

    return null;
}

/**
 * Gauti visus statymus aukcione
 * @param int $auction_id - aukciono ID
 * @return array - statymų masyvas
 */
function get_auction_bids($auction_id) {
    global $conn;

    $stmt = $conn->prepare("SELECT s.*, v.vardas
                            FROM statymai s
                            JOIN vartotojai v ON s.vartotojo_id = v.id
                            WHERE s.aukciono_id = ?
                            ORDER BY s.data_laikas DESC");
    $stmt->bind_param("i", $auction_id);
    $stmt->execute();
    $result = $stmt->get_result();

    return $result->fetch_all(MYSQLI_ASSOC);
}

/**
 * Patikrinti, ar aukcionas yra aktyvus
 * @param array $auction - aukciono duomenys
 * @return bool
 */
function is_auction_active($auction) {
    $now = time();
    $start = strtotime($auction['pradzios_laikas']);
    $end = strtotime($auction['pabaigos_laikas']);

    return $auction['busena'] === 'aktyvus' && $now >= $start && $now < $end;
}

/**
 * Patikrinti, ar aukcionas dar neprasidėjo
 * @param array $auction - aukciono duomenys
 * @return bool
 */
function is_auction_upcoming($auction) {
    return strtotime($auction['pradzios_laikas']) > time();
}

/**
 * Patikrinti, ar aukcionas pasibaigė
 * @param array $auction - aukciono duomenys
 * @return bool
 */
function is_auction_ended($auction) {
    return strtotime($auction['pabaigos_laikas']) <= time() || $auction['busena'] !== 'aktyvus';
}

/**
 * Sukurti statymą aukcione
 * @param int $auction_id - aukciono ID
 * @param int $user_id - vartotojo ID
 * @param float $bid_amount - statymo suma
 * @return array - rezultatas su 'success' ir 'message'
 */
function place_bid($auction_id, $user_id, $bid_amount) {
    global $conn;

    // Gauti aukciono duomenis
    $auction = get_auction($auction_id);

    if (!$auction) {
        return ['success' => false, 'message' => 'Aukcionas nerastas.'];
    }

    // Patikrinti, ar aukcionas aktyvus
    if (!is_auction_active($auction)) {
        return ['success' => false, 'message' => 'Aukcionas neaktyvus.'];
    }

    // Patikrinti, ar vartotojas nėra aukciono savininkas
    if ($auction['savininko_id'] == $user_id) {
        return ['success' => false, 'message' => 'Negalite statyti savo aukcione.'];
    }

    // Patikrinti minimalią statymą sumą
    $minimum_bid = $auction['dabartine_kaina'] + $auction['bid_step'];

    if ($bid_amount < $minimum_bid) {
        return ['success' => false, 'message' => 'Minimali statymo suma: ' . format_money($minimum_bid)];
    }

    // Gauti vartotojo balansą
    $stmt = $conn->prepare("SELECT balansas FROM vartotojai WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();

    // Patikrinti, ar vartotojas turi pakankamai pinigų
    if ($user['balansas'] < $bid_amount) {
        return ['success' => false, 'message' => 'Nepakanka lėšų. Jūsų balansas: ' . format_money($user['balansas'])];
    }

    // Pradėti transakciją
    $conn->begin_transaction();

    try {
        // Gauti ankstesnį aukščiausią statymą
        $previous_bid = get_highest_bid($auction_id);

        // Grąžinti pinigus ankstesniam statytojui
        if ($previous_bid) {
            $stmt = $conn->prepare("UPDATE vartotojai SET balansas = balansas + ? WHERE id = ?");
            $stmt->bind_param("di", $previous_bid['suma'], $previous_bid['vartotojo_id']);
            $stmt->execute();

            // Įrašyti grąžinimo transakciją
            $stmt = $conn->prepare("INSERT INTO transakcijos (vartotojo_id, suma, tipas, aprasymas) VALUES (?, ?, 'grazinimas', ?)");
            $description = 'Automatinis grąžinimas iš aukciono #' . $auction_id;
            $stmt->bind_param("ids", $previous_bid['vartotojo_id'], $previous_bid['suma'], $description);
            $stmt->execute();
        }

        // Nuskaičiuoti pinigus iš naujo statytojo
        $stmt = $conn->prepare("UPDATE vartotojai SET balansas = balansas - ? WHERE id = ?");
        $stmt->bind_param("di", $bid_amount, $user_id);
        $stmt->execute();

        // Įrašyti statymo transakciją
        $stmt = $conn->prepare("INSERT INTO transakcijos (vartotojo_id, suma, tipas, aprasymas) VALUES (?, ?, 'statymas', ?)");
        $negative_amount = -$bid_amount;
        $description = 'Statymas aukcione #' . $auction_id;
        $stmt->bind_param("ids", $user_id, $negative_amount, $description);
        $stmt->execute();

        // Įrašyti statymą
        $stmt = $conn->prepare("INSERT INTO statymai (aukciono_id, vartotojo_id, suma) VALUES (?, ?, ?)");
        $stmt->bind_param("iid", $auction_id, $user_id, $bid_amount);
        $stmt->execute();

        // Atnaujinti aukciono kainą
        $stmt = $conn->prepare("UPDATE aukcionai SET dabartine_kaina = ? WHERE id = ?");
        $stmt->bind_param("di", $bid_amount, $auction_id);
        $stmt->execute();

        // Įrašyti auditą
        log_audit($user_id, 'Statymas', 'Vartotojas pastatė ' . format_money($bid_amount) . ' aukcione #' . $auction_id);

        // Patvirtinti transakciją
        $conn->commit();

        return ['success' => true, 'message' => 'Statymas sėkmingas! Jūsų statymas: ' . format_money($bid_amount)];

    } catch (Exception $e) {
        // Atšaukti transakciją
        $conn->rollback();
        return ['success' => false, 'message' => 'Įvyko klaida. Bandykite dar kartą.'];
    }
}

/**
 * Gauti komentarus aukcione
 * @param int $auction_id - aukciono ID
 * @return array - komentarų masyvas
 */
function get_auction_comments($auction_id) {
    global $conn;

    $stmt = $conn->prepare("SELECT k.*, v.vardas
                            FROM komentarai k
                            JOIN vartotojai v ON k.vartotojo_id = v.id
                            WHERE k.aukciono_id = ?
                            ORDER BY k.data_laikas DESC");
    $stmt->bind_param("i", $auction_id);
    $stmt->execute();
    $result = $stmt->get_result();

    return $result->fetch_all(MYSQLI_ASSOC);
}

/**
 * Pridėti komentarą
 * @param int $auction_id - aukciono ID
 * @param int $user_id - vartotojo ID
 * @param string $comment - komentaro tekstas
 * @return array - rezultatas su 'success' ir 'message'
 */
function add_comment($auction_id, $user_id, $comment) {
    global $conn;

    if (empty($comment)) {
        return ['success' => false, 'message' => 'Komentaras negali būti tuščias.'];
    }

    $stmt = $conn->prepare("INSERT INTO komentarai (aukciono_id, vartotojo_id, tekstas) VALUES (?, ?, ?)");
    $stmt->bind_param("iis", $auction_id, $user_id, $comment);

    if ($stmt->execute()) {
        log_audit($user_id, 'Komentaras', 'Pridėtas komentaras aukcione #' . $auction_id);
        return ['success' => true, 'message' => 'Komentaras pridėtas.'];
    } else {
        return ['success' => false, 'message' => 'Įvyko klaida. Bandykite dar kartą.'];
    }
}

/**
 * Atnaujinti aukciono statusą
 */
function update_auction_statuses() {
    global $conn;

    // Pažymėti pasibaigusius aukcionus
    $conn->query("UPDATE aukcionai
                  SET busena = 'pasibaiges'
                  WHERE busena = 'aktyvus'
                  AND pabaigos_laikas <= NOW()");
}

/**
 * Gauti vartotojo transakcijas
 * @param int $user_id - vartotojo ID
 * @param int $limit - kiek įrašų grąžinti
 * @return array - transakcijų masyvas
 */
function get_user_transactions($user_id, $limit = 50) {
    global $conn;

    $stmt = $conn->prepare("SELECT * FROM transakcijos
                            WHERE vartotojo_id = ?
                            ORDER BY data_laikas DESC
                            LIMIT ?");
    $stmt->bind_param("ii", $user_id, $limit);
    $stmt->execute();
    $result = $stmt->get_result();

    return $result->fetch_all(MYSQLI_ASSOC);
}

/**
 * Papildyti vartotojo balansą
 * @param int $user_id - vartotojo ID
 * @param float $amount - suma
 * @param string $description - aprašymas
 * @return array - rezultatas su 'success' ir 'message'
 */
function add_balance($user_id, $amount, $description = 'Balanso papildymas') {
    global $conn;

    if ($amount <= 0) {
        return ['success' => false, 'message' => 'Suma turi būti teigiama.'];
    }

    $conn->begin_transaction();

    try {
        // Papildyti balansą
        $stmt = $conn->prepare("UPDATE vartotojai SET balansas = balansas + ? WHERE id = ?");
        $stmt->bind_param("di", $amount, $user_id);
        $stmt->execute();

        // Įrašyti transakciją
        $stmt = $conn->prepare("INSERT INTO transakcijos (vartotojo_id, suma, tipas, aprasymas) VALUES (?, ?, 'papildymas', ?)");
        $stmt->bind_param("ids", $user_id, $amount, $description);
        $stmt->execute();

        $conn->commit();

        return ['success' => true, 'message' => 'Balansas sėkmingai papildytas.'];

    } catch (Exception $e) {
        $conn->rollback();
        return ['success' => false, 'message' => 'Įvyko klaida. Bandykite dar kartą.'];
    }
}
