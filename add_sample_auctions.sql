-- Add sample auctions only (without creating users)
-- Uses existing user IDs from your database

-- First, get the admin user ID (assuming it's ID 1)
SET @admin_id = 1;

-- Insert sample auctions with current dates (2026)
INSERT INTO aukcionai (pavadinimas, aprasymas, pradine_kaina, dabartine_kaina, bid_step, pradzios_laikas, pabaigos_laikas, pasleptas, vartotojo_id, busena) VALUES
('iPhone 15 Pro Max', 'Naujas, naudotas tik 2 mėnesius. Su dėklu ir apsaugine plėvele.', 800.00, 800.00, 10.00, '2026-01-01 10:00:00', '2026-01-15 18:00:00', FALSE, @admin_id, 'aktyvus'),
('Samsung Galaxy S24', 'Originalus, su visais priedais. Puiki būklė.', 600.00, 600.00, 15.00, '2026-01-02 12:00:00', '2026-01-20 20:00:00', FALSE, @admin_id, 'aktyvus'),
('MacBook Pro 2024', '16 colių, M3 Max, 32GB RAM, 1TB SSD. Idealus programuotojams.', 2000.00, 2000.00, 50.00, '2026-01-03 09:00:00', '2026-01-25 21:00:00', FALSE, @admin_id, 'aktyvus'),
('Sony PlayStation 5', 'Nenaudotas, dar su garantija. 2 pulteliai.', 400.00, 400.00, 10.00, '2026-01-01 14:00:00', '2026-01-10 17:00:00', FALSE, @admin_id, 'aktyvus'),
('Vintage laikrodis Rolex', 'Kolekcinė prekė. Autentiškas, su sertifikatu.', 5000.00, 5000.00, 100.00, '2026-01-04 08:00:00', '2026-01-30 23:59:59', FALSE, @admin_id, 'aktyvus'),
('Dell XPS 13', 'Nešiojamas kompiuteris, i7 procesorius, 16GB RAM.', 900.00, 900.00, 20.00, '2026-01-02 10:00:00', '2026-01-18 19:00:00', FALSE, @admin_id, 'aktyvus');
