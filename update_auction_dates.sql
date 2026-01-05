-- Update auction dates to be current (2026)
UPDATE aukcionai SET
    pradzios_laikas = '2026-01-01 10:00:00',
    pabaigos_laikas = '2026-01-15 18:00:00'
WHERE id = 1;

UPDATE aukcionai SET
    pradzios_laikas = '2026-01-02 12:00:00',
    pabaigos_laikas = '2026-01-20 20:00:00'
WHERE id = 2;

UPDATE aukcionai SET
    pradzios_laikas = '2026-01-03 09:00:00',
    pabaigos_laikas = '2026-01-25 21:00:00'
WHERE id = 3;

UPDATE aukcionai SET
    pradzios_laikas = '2026-01-01 14:00:00',
    pabaigos_laikas = '2026-01-10 17:00:00'
WHERE id = 4;

UPDATE aukcionai SET
    pradzios_laikas = '2026-01-04 08:00:00',
    pabaigos_laikas = '2026-01-30 23:59:59'
WHERE id = 5;

UPDATE aukcionai SET
    pradzios_laikas = '2026-01-02 10:00:00',
    pabaigos_laikas = '2026-01-18 19:00:00'
WHERE id = 6;

UPDATE aukcionai SET
    pradzios_laikas = '2026-01-01 15:00:00',
    pabaigos_laikas = '2026-01-22 16:00:00'
WHERE id = 7;
