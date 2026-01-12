# Aukcionu Portalo Vadovas

## Apie projekta

Aukcionu portalas - tai internetine sistema, skirta vartotojams kurti, valdyti ir dalyvauti aukcionuose. Sistema sukurta naudojant PHP ir MySQL technologijas.

**Autorius:** Rokas Kaziulis  
**Modulis:** T120B145 – Kompiuteriu tinklai ir internetines technologijos

---

## Sistemos architektura

### Technologijos
- **Frontend:** HTML, CSS, JavaScript
- **Backend:** PHP 8.x
- **Duomenu baze:** MySQL/MariaDB
- **Serveris:** Apache (LAMP stack)

### Failu struktura
```
aukcionas/
├── assets/
│   ├── style.css          # Stiliai
│   └── script.js          # JavaScript funkcijos
├── includes/
│   ├── db.php             # Duomenu bazes prisijungimas
│   ├── auth.php           # Autentifikacijos funkcijos
│   ├── functions.php      # Pagrindinės funkcijos
│   ├── extended_functions.php  # Papildomos funkcijos
│   ├── header.php         # Puslapio antraste
│   └── footer.php         # Puslapio porastė
├── uploads/               # Ikeltų nuotrauku katalogas
├── index.php              # Pagrindinis puslapis (aukcionu sarasas)
├── login.php              # Prisijungimo puslapis
├── register.php           # Registracijos puslapis
├── logout.php             # Atsijungimo puslapis
├── auction.php            # Aukciono detales ir statymai
├── create_auction.php     # Naujo aukciono kurimas
├── edit_auction.php       # Aukciono redagavimas
├── wallet.php             # Pinigines puslapis
├── profile.php            # Vartotojo profilis
├── messages.php           # Zinuciu sistema
├── admin.php              # Administratoriaus skydelis
├── moderator.php          # Moderatoriaus skydelis
├── accountant.php         # Buhalterio skydelis
├── ip_management.php      # IP adresu valdymas
└── database.sql           # Duomenu bazes schema
```

---

## Vartotoju roles

Sistema turi 4 skirtingas vartotoju roles su skirtingomis teisemis:

### 1. Vartotojas (user)
- Gali kurti aukcionus
- Gali statyti kitu aukcionuose
- Gali rasyti komentarus
- Gali siusti zinutes kitiems vartotojams
- Gali perziureti savo pinigine ir transakcijas
- Gali redaguoti savo aukcionus
- Gali palikti atsiliepimus kitiems vartotojams

### 2. Moderatorius (moderator)
- Visos vartotojo teises
- Gali istrinti bet kuriuos komentarus
- Gali istrinti aukcionus (be statymu)
- Gali grazinti pinigus statytojams

### 3. Buhalteris (accountant)
- Visos vartotojo teises
- Gali papildyti bet kurio vartotojo balansa
- Gali perziureti visu vartotoju transakcijas

### 4. Administratorius (admin)
- Visos moderatoriaus ir buhalterio teises
- Gali keisti vartotoju roles
- Gali slepti/rodyti aukcionus
- Gali blokuoti/atblokuoti IP adresus
- Gali perziureti audito zurnala
- Gali perziureti IP veiklos statistika

---

## Pagrindinės funkcijos

### Aukcionai

**Aukciono kurimas:**
1. Iveskite pavadinima ir aprasyma
2. Nustatykite pradine kaina
3. Nustatykite statymo zingsni (minimali suma, kuria galima padidinti statyma)
4. Pasirinkite pradzios ir pabaigos laika
5. Galite ikelti nuotraukas
6. Galite paslepti aukciona (matys tik jus ir administratorius)

**Aukciono busenos:**
- **Busimas** - aukcionas dar neprasidejo
- **Aktyvus** - aukcionas vyksta, galima statyti
- **Pasibaiges** - aukcionas baigesi, laimetoja nustato didziausias statymas

**Statymas:**
- Statyti galima tik aktyviuose aukcionuose
- Negalima statyti savo aukcione
- Statymo suma turi buti didesne uz dabartine kaina + statymo zingsni
- Pinigai nuskaitomi is balanso istoja
- Kai kas nors jus pralenkia, jusu pinigai automatiskai grazinami

### Pinigine

- Kiekvienas naujas vartotojas gauna 1000 EUR pradini balansa
- Balansa papildyti gali tik buhalteris arba administratorius
- Visos transakcijos fiksuojamos ir matomos transakciju istorijoje

**Transakciju tipai:**
- **Papildymas** - balanso papildymas
- **Statymas** - pinigai nuskaityti uz statyma
- **Grazinimas** - pinigai grazinti, kai buvote pralenktas
- **Laimejimas** - aukcionas laimetas

### Zinutes

- Galite siusti privačias zinutes kitiems vartotojams
- Zinutes gali buti susietos su konkrečiu aukcionu
- Naujos zinutes pazymimos kaip neperskaitytos
- Galite atsakyti i gautas zinutes

### Atsiliepimai ir reitingai

- Galite palikti atsiliepima kitam vartotojui
- Ivertinimas nuo 1 iki 5 zvaigzduciu
- Atsiliepimo tipai: teigiamas, neutralus, neigiamas
- Vidutinis reitingas rodomas vartotojo profilyje

---

## Duomenu bazes struktura

### Lenteles

**vartotojai** - Vartotoju informacija
- id, vardas, el_pastas, slaptazodis, role, balansas, registracijos_data

**aukcionai** - Aukcionu informacija
- id, vartotojo_id, pavadinimas, aprasymas, pradine_kaina, dabartine_kaina, bid_step, pradzios_laikas, pabaigos_laikas, busena, pasleptas

**statymai** - Statymu istorija
- id, aukciono_id, vartotojo_id, suma, data_laikas

**komentarai** - Aukcionu komentarai
- id, aukciono_id, vartotojo_id, tekstas, data_laikas

**transakcijos** - Pinigu transakcijos
- id, vartotojo_id, suma, tipas, aprasymas, data_laikas

**zinutes** - Privačios zinutes
- id, siuntejas_id, gavejas_id, tema, tekstas, aukciono_id, perskaitytas, siuntimo_laikas

**atsiliepimai** - Vartotoju atsiliepimai
- id, nuo_vartotojo_id, vartotojo_id, ivertinimas, komentaras, tipas, aukciono_id, data_laikas

**nuotraukos** - Aukcionu nuotraukos
- id, aukciono_id, failo_pavadinimas, originalus_pavadinimas, ikelimo_data

**audit_log** - Audito zurnalas
- id, vartotojo_id, veiksmas, aprasymas, data_laikas

**ip_veikla** - IP adresu veikla
- id, ip_adresas, vartotojo_id, veiksmas, aprasymas, data_laikas

**ip_blokavimai** - Uzblokuoti IP adresai
- id, ip_adresas, priezastis, uzblokavo_admin_id, blokavimo_data, galioja_iki, aktyvus

---

## Demonstracines paskyros

Sistema turi is anksto sukurtas demonstracines paskyras:

| Role | El. pastas | Slaptazodis |
|------|-----------|-------------|
| Administratorius | a@a | 1 |
| Buhalteris | b@b | 1 |
| Moderatorius | m@m | 1 |
| Vartotojas | u@u | 1 |

---

## Saugumo funkcijos

- **Slaptazodziu sifrravimas** - visi slaptazodziai saugomi uzsifrruoti (password_hash)
- **SQL injekciju apsauga** - naudojami prepared statements
- **XSS apsauga** - visi ivesties duomenys filtruojami (htmlspecialchars)
- **Sesiju valdymas** - saugus sesiju tvarkymas
- **Roliu tikrinimas** - kiekviename puslapyje tikrinamos teises
- **Audito zurnalas** - visi svarbūs veiksmai registruojami
- **IP blokavimas** - galimybe blokuoti kenkejiskus IP adresus

---

## Diegimas

Zr. UPDATE.md faila del detaliiu diegimo ir atnaujinimo instrukciju.

### Trumpai:
1. Idiekite LAMP stack (Apache, MySQL, PHP)
2. Sukurkite duomenu baze ir importuokite database.sql
3. Nukopijuokite failus i /var/www/html/aukcionas
4. Nustatykite teises (www-data:www-data, 755)
5. Atidarykite http://localhost/aukcionas

---

## Daznai uzduodami klausimai

**K: Kaip suzinoti vartotojo ID?**
A: ID rodomas salia vartotojo vardo visur sistemoje, pvz.: "Jonas (ID: 5)"

**K: Kaip papildyti balansa?**
A: Balansa gali papildyti tik buhalteris arba administratorius per atitinkamus skydelius.

**K: Kas nutinka, kai mane pralenkia aukcione?**
A: Jusu pinigai automatiskai grazinami i jusu pinigine.

**K: Ar galiu istrinti savo aukciona?**
A: Taip, bet tik jei jame nera statymu. Aukcionus su statymais gali istrinti tik moderatorius arba administratorius.

**K: Kaip siusti zinute kitam vartotojui?**
A: Eikite i Zinutes -> Nauja zinute, iveskite gavejo ID ir paraskyte zinute.

**K: Kaip palikti atsiliepima?**
A: Eikite i vartotojo profili ir apačioje rasite atsiliepimo forma.

---

## Kontaktai

Jei turite klausimu ar problemu, susisiekite su sistemos administratoriumi.

**Autorius:** Rokas Kaziulis  
**Modulis:** T120B145 – Kompiuteriu tinklai ir internetines technologijos  
**Metai:** 2025
