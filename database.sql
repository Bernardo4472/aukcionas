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

-- Aukciono nuotraukų lentelė
CREATE TABLE IF NOT EXISTS aukciono_nuotraukos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    aukciono_id INT NOT NULL,
    failo_pavadinimas VARCHAR(255) NOT NULL,
    originalus_pavadinimas VARCHAR(255) NOT NULL,
    ikelimo_data DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (aukciono_id) REFERENCES aukcionai(id) ON DELETE CASCADE,
    INDEX idx_aukciono_id (aukciono_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Žinučių lentelė
CREATE TABLE IF NOT EXISTS zinutes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    siuntejas_id INT NOT NULL,
    gavejas_id INT NOT NULL,
    aukciono_id INT,
    tema VARCHAR(200) NOT NULL,
    tekstas TEXT NOT NULL,
    perskaitytas BOOLEAN DEFAULT FALSE,
    siuntimo_laikas DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (siuntejas_id) REFERENCES vartotojai(id) ON DELETE CASCADE,
    FOREIGN KEY (gavejas_id) REFERENCES vartotojai(id) ON DELETE CASCADE,
    FOREIGN KEY (aukciono_id) REFERENCES aukcionai(id) ON DELETE SET NULL,
    INDEX idx_gavejas (gavejas_id),
    INDEX idx_siuntejas (siuntejas_id),
    INDEX idx_perskaitytas (perskaitytas)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Atsiliepimų lentelė
CREATE TABLE IF NOT EXISTS atsiliepimai (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nuo_vartotojo_id INT NOT NULL,
    apie_vartotoja_id INT NOT NULL,
    aukciono_id INT,
    ivertinimas TINYINT NOT NULL CHECK (ivertinimas BETWEEN 1 AND 5),
    komentaras TEXT,
    tipas ENUM('teigiam','neigiamas','neutralus') NOT NULL,
    data_laikas DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (nuo_vartotojo_id) REFERENCES vartotojai(id) ON DELETE CASCADE,
    FOREIGN KEY (apie_vartotoja_id) REFERENCES vartotojai(id) ON DELETE CASCADE,
    FOREIGN KEY (aukciono_id) REFERENCES aukcionai(id) ON DELETE SET NULL,
    INDEX idx_apie_vartotoja (apie_vartotoja_id),
    INDEX idx_tipas (tipas)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- IP blokavimų lentelė
CREATE TABLE IF NOT EXISTS ip_blokavimai (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ip_adresas VARCHAR(45) NOT NULL,
    priezastis TEXT,
    užblokavo_admin_id INT,
    blokavimo_data DATETIME DEFAULT CURRENT_TIMESTAMP,
    galioja_iki DATETIME,
    aktyvus BOOLEAN DEFAULT TRUE,
    FOREIGN KEY (užblokavo_admin_id) REFERENCES vartotojai(id) ON DELETE SET NULL,
    INDEX idx_ip_adresas (ip_adresas),
    INDEX idx_aktyvus (aktyvus)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- IP veiklos žurnalo lentelė
CREATE TABLE IF NOT EXISTS ip_veikla (
    id INT AUTO_INCREMENT PRIMARY KEY,
    vartotojo_id INT,
    ip_adresas VARCHAR(45) NOT NULL,
    veiksmas VARCHAR(100) NOT NULL,
    aprasymas TEXT,
    data_laikas DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (vartotojo_id) REFERENCES vartotojai(id) ON DELETE SET NULL,
    INDEX idx_ip_adresas (ip_adresas),
    INDEX idx_vartotojo_id (vartotojo_id),
    INDEX idx_data_laikas (data_laikas)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Demonstraciniai duomenys

-- Demonstraciniai vartotojai (slaptazodis: 1)
INSERT INTO vartotojai (vardas, el_pastas, slaptazodis, role, balansas) VALUES
('Administratorius', 'a@a', '$2y$10$TKh8H1.PfQx37YgCzwiKb.KjNyWgaHb9cbcoQgdIVFlYg7B77UdFm', 'admin', 5000.00),
('Buhalteris', 'b@b', '$2y$10$TKh8H1.PfQx37YgCzwiKb.KjNyWgaHb9cbcoQgdIVFlYg7B77UdFm', 'accountant', 3000.00),
('Vartotojas', 'u@u', '$2y$10$TKh8H1.PfQx37YgCzwiKb.KjNyWgaHb9cbcoQgdIVFlYg7B77UdFm', 'user', 1000.00),
('Moderatorius', 'm@m', '$2y$10$TKh8H1.PfQx37YgCzwiKb.KjNyWgaHb9cbcoQgdIVFlYg7B77UdFm', 'moderator', 2000.00),
('Jonas Jonaitis', 'j@j', '$2y$10$TKh8H1.PfQx37YgCzwiKb.KjNyWgaHb9cbcoQgdIVFlYg7B77UdFm', 'user', 500.00),
('Petras Petraitis', 'p@p', '$2y$10$TKh8H1.PfQx37YgCzwiKb.KjNyWgaHb9cbcoQgdIVFlYg7B77UdFm', 'user', 750.00),
-- Papildomi testavimo vartotojai (slaptazodis: 1)
('Ona Onute', 'o@o', '$2y$10$TKh8H1.PfQx37YgCzwiKb.KjNyWgaHb9cbcoQgdIVFlYg7B77UdFm', 'user', 1200.00),
('Antanas Antanaitis', 'aa@aa', '$2y$10$TKh8H1.PfQx37YgCzwiKb.KjNyWgaHb9cbcoQgdIVFlYg7B77UdFm', 'user', 800.00),
('Greta Gretaite', 'g@g', '$2y$10$TKh8H1.PfQx37YgCzwiKb.KjNyWgaHb9cbcoQgdIVFlYg7B77UdFm', 'user', 950.00),
('Lukas Lukauskas', 'l@l', '$2y$10$TKh8H1.PfQx37YgCzwiKb.KjNyWgaHb9cbcoQgdIVFlYg7B77UdFm', 'user', 1500.00),
('Ieva Ievaite', 'i@i', '$2y$10$TKh8H1.PfQx37YgCzwiKb.KjNyWgaHb9cbcoQgdIVFlYg7B77UdFm', 'user', 2000.00),
('Darius Dariunas', 'd@d', '$2y$10$TKh8H1.PfQx37YgCzwiKb.KjNyWgaHb9cbcoQgdIVFlYg7B77UdFm', 'user', 650.00),
('Rasa Rasaite', 'r@r', '$2y$10$TKh8H1.PfQx37YgCzwiKb.KjNyWgaHb9cbcoQgdIVFlYg7B77UdFm', 'user', 1100.00);

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

-- Demonstracinė IP veikla (įvairūs scenarijai)
INSERT INTO ip_veikla (vartotojo_id, ip_adresas, veiksmas, aprasymas, data_laikas) VALUES
-- Scenario 1: Normalus vieno vartotojo aktyvumas iš namų
(3, '192.168.1.100', 'Prisijungimas', 'Sėkmingas prisijungimas: user@ktu.lt', '2025-12-01 09:00:00'),
(3, '192.168.1.100', 'Aukciono kūrimas', 'Sukurtas aukcionas: iPhone 15 Pro Max (#1)', '2025-12-01 09:15:00'),
(3, '192.168.1.100', 'Statymas', 'Pastatė 2100.00 € aukcione #3', '2025-12-01 10:30:00'),
(3, '192.168.1.100', 'Komentaras', 'Parašė komentarą aukcione #1', '2025-12-01 11:00:00'),

-- Scenario 2: Šeima/ofisas - keli vartotojai iš to paties IP
(5, '78.56.123.45', 'Registracija', 'Nauja paskyra: Jonas Jonaitis (jonas@example.com)', '2025-11-28 14:20:00'),
(5, '78.56.123.45', 'Prisijungimas', 'Sėkmingas prisijungimas: jonas@example.com', '2025-11-29 08:30:00'),
(5, '78.56.123.45', 'Statymas', 'Pastatė 850.00 € aukcione #1', '2025-11-29 09:45:00'),
(6, '78.56.123.45', 'Prisijungimas', 'Sėkmingas prisijungimas: petras@example.com', '2025-11-29 15:00:00'),
(6, '78.56.123.45', 'Statymas', 'Pastatė 650.00 € aukcione #2', '2025-11-29 15:30:00'),
(7, '78.56.123.45', 'Prisijungimas', 'Sėkmingas prisijungimas: ona@example.com', '2025-11-30 10:00:00'),
(7, '78.56.123.45', 'Žinutė', 'Išsiuntė žinutę vartotojui #5: Klausimas apie prekę', '2025-11-30 10:15:00'),

-- Scenario 3: Vartotojas keliaujantis - keli IP adresai
(8, '85.206.45.12', 'Prisijungimas', 'Sėkmingas prisijungimas: antanas@example.com', '2025-11-25 07:00:00'),
(8, '85.206.45.12', 'Aukciono kūrimas', 'Sukurtas aukcionas: Samsung Galaxy S24 (#2)', '2025-11-25 07:30:00'),
(8, '92.61.178.234', 'Prisijungimas', 'Sėkmingas prisijungimas: antanas@example.com', '2025-11-26 19:00:00'),
(8, '92.61.178.234', 'Komentaras', 'Parašė komentarą aukcione #2', '2025-11-26 19:15:00'),
(8, '188.126.73.89', 'Prisijungimas', 'Sėkmingas prisijungimas: antanas@example.com', '2025-11-28 12:00:00'),
(8, '188.126.73.89', 'Statymas', 'Pastatė 920.00 € aukcione #6', '2025-11-28 12:30:00'),

-- Scenario 4: KTU tinklas - studentai
(9, '193.219.28.45', 'Registracija', 'Nauja paskyra: Greta Gretaitė (greta@example.com)', '2025-11-20 13:00:00'),
(9, '193.219.28.45', 'Prisijungimas', 'Sėkmingas prisijungimas: greta@example.com', '2025-11-21 10:00:00'),
(9, '193.219.28.45', 'Statymas', 'Pastatė 410.00 € aukcione #4', '2025-11-21 11:00:00'),
(10, '193.219.28.45', 'Registracija', 'Nauja paskyra: Lukas Lukauskas (lukas@example.com)', '2025-11-22 09:30:00'),
(10, '193.219.28.45', 'Prisijungimas', 'Sėkmingas prisijungimas: lukas@example.com', '2025-11-22 14:00:00'),
(10, '193.219.28.45', 'Komentaras', 'Parašė komentarą aukcione #3', '2025-11-22 14:15:00'),

-- Scenario 5: Įtartinas aktyvumas - daug nesėkmingų prisijungimų
(NULL, '45.142.212.61', 'Nesėkmingas prisijungimas', 'Bandymas prisijungti su: admin@ktu.lt', '2025-11-30 02:15:23'),
(NULL, '45.142.212.61', 'Nesėkmingas prisijungimas', 'Bandymas prisijungti su: admin@ktu.lt', '2025-11-30 02:15:45'),
(NULL, '45.142.212.61', 'Nesėkmingas prisijungimas', 'Bandymas prisijungti su: root@ktu.lt', '2025-11-30 02:16:12'),
(NULL, '45.142.212.61', 'Nesėkmingas prisijungimas', 'Bandymas prisijungti su: admin@example.com', '2025-11-30 02:16:38'),
(NULL, '45.142.212.61', 'Nesėkminga registracija', 'Bandymas registruotis su: test@test.com', '2025-11-30 02:17:01'),

-- Scenario 6: Mobilaus tinklo vartotojai (dynamic IP)
(11, '88.119.163.12', 'Registracija', 'Nauja paskyra: Ieva Ievaitė (ieva@example.com)', '2025-11-23 16:00:00'),
(11, '88.119.163.12', 'Prisijungimas', 'Sėkmingas prisijungimas: ieva@example.com', '2025-11-23 16:05:00'),
(11, '88.119.165.234', 'Prisijungimas', 'Sėkmingas prisijungimas: ieva@example.com', '2025-11-24 08:30:00'),
(11, '88.119.167.89', 'Prisijungimas', 'Sėkmingas prisijungimas: ieva@example.com', '2025-11-24 18:45:00'),
(11, '88.119.167.89', 'Statymas', 'Pastatė 2150.00 € aukcione #3', '2025-11-24 19:00:00'),

-- Scenario 7: Įmonės tinklas - kolegos
(12, '213.252.140.25', 'Registracija', 'Nauja paskyra: Darius Dariūnas (darius@example.com)', '2025-11-27 09:00:00'),
(12, '213.252.140.25', 'Prisijungimas', 'Sėkmingas prisijungimas: darius@example.com', '2025-11-27 09:30:00'),
(12, '213.252.140.25', 'Aukciono kūrimas', 'Sukurtas aukcionas: Dell XPS 13 (#6)', '2025-11-27 10:00:00'),
(13, '213.252.140.25', 'Registracija', 'Nauja paskyra: Rasa Rasaitė (rasa@example.com)', '2025-11-27 11:00:00'),
(13, '213.252.140.25', 'Prisijungimas', 'Sėkmingas prisijungimas: rasa@example.com', '2025-11-27 11:15:00'),
(13, '213.252.140.25', 'Žinutė', 'Išsiuntė žinutę vartotojui #12: Ar galiu pasiskolinti įkroviklį?', '2025-11-27 11:30:00'),

-- Scenario 8: Administratorių veikla
(1, '192.168.1.50', 'Prisijungimas', 'Sėkmingas prisijungimas: admin@ktu.lt', '2025-11-28 08:00:00'),
(1, '192.168.1.50', 'IP blokavimas', 'Užblokuotas IP: 45.142.212.61', '2025-11-30 09:00:00'),
(4, '192.168.1.55', 'Prisijungimas', 'Sėkmingas prisijungimas: moderator@ktu.lt', '2025-11-29 10:00:00'),
(4, '192.168.1.55', 'Komentaras', 'Moderatorius ištrynė komentarą', '2025-11-29 10:30:00'),

-- Scenario 9: Aktyvūs pirkėjai
(5, '78.56.123.45', 'Statymas', 'Pastatė 860.00 € aukcione #1', '2025-12-01 14:00:00'),
(6, '78.56.123.45', 'Statymas', 'Pastatė 665.00 € aukcione #2', '2025-12-01 14:30:00'),
(9, '193.219.28.45', 'Statymas', 'Pastatė 460.00 € aukcione #4', '2025-12-01 15:00:00'),
(11, '88.119.168.101', 'Statymas', 'Pastatė 2200.00 € aukcione #3', '2025-12-01 16:00:00'),

-- Scenario 10: Žinutės tarp vartotojų
(5, '78.56.123.45', 'Žinutė', 'Išsiuntė žinutę vartotojui #3: Ar galite susitikti šiandien?', '2025-12-02 09:00:00'),
(3, '192.168.1.100', 'Žinutė', 'Išsiuntė žinutę vartotojui #5: Taip, galiu 18:00', '2025-12-02 09:30:00'),
(10, '193.219.28.45', 'Žinutė', 'Išsiuntė žinutę vartotojui #9: Ar dalyvausi aukcione?', '2025-12-02 11:00:00'),

-- Scenario 11: Pavėlavę prisijungimai (naktis)
(6, '78.56.123.45', 'Prisijungimas', 'Sėkmingas prisijungimas: petras@example.com', '2025-12-02 23:45:00'),
(6, '78.56.123.45', 'Statymas', 'Pastatė 680.00 € aukcione #2', '2025-12-02 23:50:00'),
(11, '88.119.169.45', 'Prisijungimas', 'Sėkmingas prisijungimas: ieva@example.com', '2025-12-03 01:30:00'),
(11, '88.119.169.45', 'Komentaras', 'Parašė komentarą aukcione #3', '2025-12-03 01:35:00');

-- Demonstraciniai IP blokavimai
INSERT INTO ip_blokavimai (ip_adresas, priezastis, užblokavo_admin_id, galioja_iki, aktyvus) VALUES
('45.142.212.61', 'Daug nesėkmingų prisijungimo bandymų - galimas bruteforce', 1, '2025-12-30 23:59:59', TRUE),
('103.45.67.89', 'Spam žinutės', 1, NULL, TRUE),
('185.220.101.45', 'Sukčiavimo bandymai', 1, '2026-01-15 00:00:00', TRUE);
