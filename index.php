<?php
/**
 * Pagrindinis puslapis - Aukcionų sąrašas
 * Sukurta: 2025-11-27
 * Autorius: Rokas Kaziulis
 */

session_start();
require_once 'includes/db.php';
require_once 'includes/auth.php';
require_once 'includes/functions.php';

// Patikrinti, ar vartotojas prisijungęs
require_login();

// Atnaujinti aukcionų statusus
update_auction_statuses();

// Gauti visus aktyvius aukcionus
$user = get_current_user();
$include_hidden = has_role('admin'); // Admin mato visus aukcionus
$auctions = get_active_auctions($include_hidden);

$page_title = 'Pagrindinis';
include 'includes/header.php';
?>

<!-- Projekto antraštė -->
<div class="project-header">
    <h1>Aukcionų portalas</h1>
    <div class="project-info">
        <p><strong>Autorius:</strong> Rokas Kaziulis</p>
        <p><strong>Modulis:</strong> T120B145 – Kompiuterių tinklai ir internetinės technologijos</p>
    </div>
</div>

<!-- Paieškos ir rūšiavimo skydelis -->
<div class="search-section" style="margin-bottom: 30px;">
    <div style="display: flex; gap: 20px; align-items: center; flex-wrap: wrap;">
        <div style="flex: 1; min-width: 250px;">
            <input
                type="text"
                id="searchInput"
                class="form-control"
                placeholder="🔍 Ieškoti aukcionų..."
                onkeyup="searchAuctions()">
        </div>
        <div>
            <select class="form-control" onchange="sortAuctions(this.value)" style="min-width: 200px;">
                <option value="">Rūšiuoti pagal...</option>
                <option value="price-asc">Kaina: nuo mažiausios</option>
                <option value="price-desc">Kaina: nuo didžiausios</option>
                <option value="time-asc">Laikas: baigiasi greičiausiai</option>
                <option value="time-desc">Laikas: baigiasi vėliausiai</option>
            </select>
        </div>
    </div>
</div>

<!-- Statistika -->
<div class="auction-meta" style="margin-bottom: 30px;">
    <div class="meta-item">
        <div class="meta-label">Aktyvių aukcionų</div>
        <div class="meta-value"><?php echo count($auctions); ?></div>
    </div>
    <div class="meta-item">
        <div class="meta-label">Jūsų balansas</div>
        <div class="meta-value" style="color: #27ae60;"><?php echo format_money($user['balansas']); ?></div>
    </div>
    <div class="meta-item">
        <div class="meta-label">Jūsų rolė</div>
        <div class="meta-value">
            <span class="role-badge role-<?php echo $user['role']; ?>">
                <?php
                $roles = [
                    'admin' => 'Administratorius',
                    'moderator' => 'Moderatorius',
                    'accountant' => 'Buhalteris',
                    'user' => 'Vartotojas'
                ];
                echo $roles[$user['role']];
                ?>
            </span>
        </div>
    </div>
</div>

<h2 style="margin-bottom: 20px;">Aktyvūs aukcionai</h2>

<?php if (empty($auctions)): ?>
    <div class="alert alert-info">
        <p>Šiuo metu nėra aktyvių aukcionų.</p>
        <p><a href="create_auction.php" class="btn btn-primary" style="margin-top: 10px;">Sukurti pirmą aukcioną</a></p>
    </div>
<?php else: ?>
    <div class="auctions-grid">
        <?php foreach ($auctions as $auction):
            $highest_bid = get_highest_bid($auction['id']);
            $is_owner = $auction['vartotojo_id'] == $user['id'];

            // Nustatyti statusą
            if (is_auction_upcoming($auction)) {
                $status = 'upcoming';
                $status_text = 'Prasidės netrukus';
            } elseif (is_auction_active($auction)) {
                $status = 'active';
                $status_text = 'Aktyvus';
            } else {
                $status = 'ended';
                $status_text = 'Pasibaigęs';
            }

            // Jei paslėptas, pridėti papildomą statusą
            if ($auction['pasleptas']) {
                $status = 'hidden';
                $status_text = 'Paslėptas';
            }
            ?>
            <div class="auction-card"
                 data-price="<?php echo $auction['dabartine_kaina']; ?>"
                 data-endtime="<?php echo $auction['pabaigos_laikas']; ?>">

                <?php if ($auction['pasleptas']): ?>
                    <div style="background: #f39c12; color: white; padding: 5px 10px; border-radius: 5px; margin-bottom: 10px; text-align: center;">
                        🔒 Paslėptas aukcionas
                    </div>
                <?php endif; ?>

                <h3><?php echo htmlspecialchars($auction['pavadinimas']); ?></h3>

                <div class="auction-description">
                    <?php echo htmlspecialchars(substr($auction['aprasymas'], 0, 150)) . (strlen($auction['aprasymas']) > 150 ? '...' : ''); ?>
                </div>

                <div class="auction-info">
                    <div class="auction-info-item">
                        <span class="label">Dabartinė kaina:</span>
                        <span class="value price"><?php echo format_money($auction['dabartine_kaina']); ?></span>
                    </div>

                    <div class="auction-info-item">
                        <span class="label">Statymo žingsnis:</span>
                        <span class="value"><?php echo format_money($auction['bid_step']); ?></span>
                    </div>

                    <?php if ($highest_bid): ?>
                        <div class="auction-info-item">
                            <span class="label">Aukščiausias statytojas:</span>
                            <span class="value"><?php echo htmlspecialchars($highest_bid['vardas']); ?></span>
                        </div>
                    <?php endif; ?>

                    <div class="auction-info-item">
                        <span class="label">Savininkas:</span>
                        <span class="value"><?php echo htmlspecialchars($auction['savininkas']); ?></span>
                    </div>

                    <div class="auction-info-item">
                        <span class="label">Statusas:</span>
                        <span class="auction-status status-<?php echo $status; ?>">
                            <?php echo $status_text; ?>
                        </span>
                    </div>

                    <?php if ($status === 'active'): ?>
                        <div class="auction-info-item" style="flex-direction: column; align-items: flex-start;">
                            <span class="label">Liko laiko:</span>
                            <span class="auction-timer" data-endtime="<?php echo $auction['pabaigos_laikas']; ?>"></span>
                        </div>
                    <?php else: ?>
                        <div class="auction-info-item">
                            <span class="label">Pabaigos laikas:</span>
                            <span class="value"><?php echo format_datetime($auction['pabaigos_laikas']); ?></span>
                        </div>
                    <?php endif; ?>
                </div>

                <div style="margin-top: 15px;">
                    <a href="auction.php?id=<?php echo $auction['id']; ?>" class="btn btn-primary btn-block">
                        <?php echo $is_owner ? 'Peržiūrėti' : 'Statyti'; ?>
                    </a>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php include 'includes/footer.php'; ?>
