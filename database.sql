-- Aukcionų portalo duomenų bazė
-- Sukurta: 2025-11-27
-- Autorius: Rokas Kaziulis

-- Sukurti duomenų bazę
CREATE DATABASE IF NOT EXISTS aukcionas CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE aukcionas;

-- Vartotojų lentelė
CREATE TABLE IF NOT EXISTS vartotojai (
    id INT AUTO_INCREMENT PRIMARY KEY,
    vardas VARCHAR(100) NOT NULL,
    el_pastas VARCHAR(100) UNIQUE NOT NULL,
    slaptazodis VARCHAR(255) NOT NULL,
    role ENUM('user', 'moderator', 'admin', 'accountant') DEFAULT 'user',
    balansas DECIMAL(10, 2) DEFAULT 1000.00,
    registracijos_data DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_el_pastas (el_pastas),
    INDEX idx_role (role)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Aukcionų lentelė
CREATE TABLE IF NOT EXISTS aukcionai (
    id INT AUTO_INCREMENT PRIMARY KEY,
    pavadinimas VARCHAR(200) NOT NULL,
    aprasymas TEXT,
    pradine_kaina DECIMAL(10, 2) NOT NULL,
    dabartine_kaina DECIMAL(10, 2) NOT NULL,
    bid_step DECIMAL(10, 2) NOT NULL DEFAULT 5.00,
    pradzios_laikas DATETIME NOT NULL,
    pabaigos_laikas DATETIME NOT NULL,
    pasleptas BOOLEAN DEFAULT FALSE,
    vartotojo_id INT NOT NULL,
    busena ENUM('aktyvus', 'pasibaiges', 'atsauktas') DEFAULT 'aktyvus',
    sukurimo_data DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (vartotojo_id) REFERENCES vartotojai(id) ON DELETE CASCADE,
    INDEX idx_busena (busena),
    INDEX idx_pabaigos_laikas (pabaigos_laikas),
    INDEX idx_vartotojo_id (vartotojo_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Statymų lentelė
CREATE TABLE IF NOT EXISTS statymai (
    id INT AUTO_INCREMENT PRIMARY KEY,
    aukciono_id INT NOT NULL,
    vartotojo_id INT NOT NULL,
    suma DECIMAL(10, 2) NOT NULL,
    data_laikas DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (aukciono_id) REFERENCES aukcionai(id) ON DELETE CASCADE,
    FOREIGN KEY (vartotojo_id) REFERENCES vartotojai(id) ON DELETE CASCADE,
    INDEX idx_aukciono_id (aukciono_id),
    INDEX idx_vartotojo_id (vartotojo_id),
    INDEX idx_data_laikas (data_laikas)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Transakcijų lentelė
CREATE TABLE IF NOT EXISTS transakcijos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    vartotojo_id INT NOT NULL,
    suma DECIMAL(10, 2) NOT NULL,
    tipas ENUM('papildymas', 'statymas', 'grazinimas', 'laimejimas') NOT NULL,
    data_laikas DATETIME DEFAULT CURRENT_TIMESTAMP,
    aprasymas VARCHAR(255),
    FOREIGN KEY (vartotojo_id) REFERENCES vartotojai(id) ON DELETE CASCADE,
    INDEX idx_vartotojo_id (vartotojo_id),
    INDEX idx_tipas (tipas),
    INDEX idx_data_laikas (data_laikas)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Komentarų lentelė
CREATE TABLE IF NOT EXISTS komentarai (
    id INT AUTO_INCREMENT PRIMARY KEY,
    aukciono_id INT NOT NULL,
    vartotojo_id INT NOT NULL,
    tekstas TEXT NOT NULL,
    data_laikas DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (aukciono_id) REFERENCES aukcionai(id) ON DELETE CASCADE,
    FOREIGN KEY (vartotojo_id) REFERENCES vartotojai(id) ON DELETE CASCADE,
    INDEX idx_aukciono_id (aukciono_id),
    INDEX idx_data_laikas (data_laikas)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Audito žurnalo lentelė
CREATE TABLE IF NOT EXISTS audit_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    vartotojo_id INT,
    veiksmas VARCHAR(100) NOT NULL,
    aprasymas TEXT,
    data_laikas DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (vartotojo_id) REFERENCES vartotojai(id) ON DELETE SET NULL,
    INDEX idx_vartotojo_id (vartotojo_id),
    INDEX idx_data_laikas (data_laikas)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Demonstraciniai duomenys

-- Demonstraciniai vartotojai (visi slaptažodžiai: admin123, acc123, user123)
INSERT INTO vartotojai (vardas, el_pastas, slaptazodis, role, balansas) VALUES
('Administratorius', 'admin@ktu.lt', '$2y$12$3HZY.Pt69poo3EgJiuH6cOC5lXpLUItrtrlQi7VMnW0eoNtgBsV8e', 'admin', 5000.00),
('Buhalteris', 'accountant@ktu.lt', '$2y$12$yAy1HeloS29BYAjeBwDmSO7R2wr4r/cCmXcYMymldpw8IsZaZ7O2S', 'accountant', 3000.00),
('Vartotojas', 'user@ktu.lt', '$2y$12$NStVAgYOOSDZ3R1xhH0j5e.U26fyVK42Dy9.k1rsehZeK2QlImxK6', 'user', 1000.00),
('Moderatorius', 'moderator@ktu.lt', '$2y$12$3HZY.Pt69poo3EgJiuH6cOC5lXpLUItrtrlQi7VMnW0eoNtgBsV8e', 'moderator', 2000.00),
('Jonas Jonaitis', 'jonas@example.com', '$2y$12$NStVAgYOOSDZ3R1xhH0j5e.U26fyVK42Dy9.k1rsehZeK2QlImxK6', 'user', 500.00),
('Petras Petraitis', 'petras@example.com', '$2y$12$NStVAgYOOSDZ3R1xhH0j5e.U26fyVK42Dy9.k1rsehZeK2QlImxK6', 'user', 750.00);

-- Demonstraciniai aukcionai
INSERT INTO aukcionai (pavadinimas, aprasymas, pradine_kaina, dabartine_kaina, bid_step, pradzios_laikas, pabaigos_laikas, pasleptas, vartotojo_id, busena) VALUES
('iPhone 15 Pro Max', 'Naujas, naudotas tik 2 mėnesius. Su dėklu ir apsaugine plėvele.', 800.00, 850.00, 10.00, '2025-11-20 10:00:00', '2025-12-05 18:00:00', FALSE, 3, 'aktyvus'),
('Samsung Galaxy S24', 'Originalus, su visais priedais. Puiki būklė.', 600.00, 650.00, 15.00, '2025-11-22 12:00:00', '2025-12-10 20:00:00', FALSE, 5, 'aktyvus'),
('MacBook Pro 2024', '16 coliųM3 Max, 32GB RAM, 1TB SSD. Idealus programuotojams.', 2000.00, 2100.00, 50.00, '2025-11-25 09:00:00', '2025-12-15 21:00:00', FALSE, 6, 'aktyvus'),
('Sony PlayStation 5', 'Nenaudotas, dar su garantija. 2 pulteliai.', 400.00, 450.00, 10.00, '2025-11-18 14:00:00', '2025-12-01 17:00:00', FALSE, 3, 'aktyvus'),
('Vintage laikrodis Rolex', 'Kolekcinė prekė. Autentiškas, su sertifikatu.', 5000.00, 5000.00, 100.00, '2025-11-28 08:00:00', '2025-12-20 23:59:59', FALSE, 5, 'aktyvus'),
('Dell XPS 13', 'Nešiojamas kompiuteris, i7 procesorius, 16GB RAM.', 900.00, 900.00, 20.00, '2025-11-27 10:00:00', '2025-12-08 19:00:00', FALSE, 6, 'aktyvus'),
('Slaptas aukcionas', 'Šis aukcionas matomas tik savininkui ir administratoriui.', 100.00, 100.00, 5.00, '2025-11-26 15:00:00', '2025-12-12 16:00:00', TRUE, 3, 'aktyvus');

-- Demonstraciniai statymai
INSERT INTO statymai (aukciono_id, vartotojo_id, suma, data_laikas) VALUES
(1, 5, 850.00, '2025-11-26 14:30:00'),
(2, 6, 650.00, '2025-11-26 15:00:00'),
(3, 3, 2100.00, '2025-11-26 16:00:00'),
(4, 6, 450.00, '2025-11-26 17:00:00');

-- Demonstracinės transakcijos
INSERT INTO transakcijos (vartotojo_id, suma, tipas, aprasymas, data_laikas) VALUES
(3, 1000.00, 'papildymas', 'Pradinis balansas', '2025-11-20 08:00:00'),
(5, 500.00, 'papildymas', 'Pradinis balansas', '2025-11-20 08:00:00'),
(6, 750.00, 'papildymas', 'Pradinis balansas', '2025-11-20 08:00:00'),
(5, -850.00, 'statymas', 'Statymas aukcione #1', '2025-11-26 14:30:00'),
(6, -650.00, 'statymas', 'Statymas aukcione #2', '2025-11-26 15:00:00'),
(3, -2100.00, 'statymas', 'Statymas aukcione #3', '2025-11-26 16:00:00'),
(6, -450.00, 'statymas', 'Statymas aukcione #4', '2025-11-26 17:00:00');

-- Demonstraciniai komentarai
INSERT INTO komentarai (aukciono_id, vartotojo_id, tekstas, data_laikas) VALUES
(1, 6, 'Ar telefonas turi įbrėžimų?', '2025-11-26 10:00:00'),
(1, 3, 'Ne, telefonas idealios būklės!', '2025-11-26 10:30:00'),
(2, 5, 'Ar galima pamatyti gyvai prieš perkant?', '2025-11-26 11:00:00'),
(3, 5, 'Puikus kompiuteris programavimui!', '2025-11-26 12:00:00');

-- Demonstracinis audito įrašas
INSERT INTO audit_log (vartotojo_id, veiksmas, aprasymas) VALUES
(1, 'Prisijungimas', 'Administratorius prisijungė prie sistemos'),
(2, 'Balansas papildytas', 'Buhalteris papildė vartotojo #3 balansą 500 EUR'),
(4, 'Komentaras ištrintas', 'Moderatorius ištrynė netinkamą komentarą #15');
