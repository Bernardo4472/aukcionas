<!DOCTYPE html>
<html lang="lt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title . ' - ' : ''; ?>Aukcionų portalas</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>
    <nav class="navbar">
        <div class="container">
            <div class="nav-brand">
                <a href="index.php">Aukcionu portalas</a>
            </div>
            <div class="nav-menu">
                <?php if (is_logged_in()): ?>
                    <?php
                    // Load extended functions for message count
                    if (!function_exists('get_unread_messages_count')) {
                        require_once __DIR__ . '/extended_functions.php';
                    }
                    $unread = get_unread_messages_count($_SESSION['user_id']);
                    ?>
                    <a href="index.php">Aukcionai</a>
                    <a href="create_auction.php">Sukurti aukcioną</a>
                    <a href="wallet.php">Piniginė</a>
                    <a href="messages.php">
                        Zinutes
                        <?php if ($unread > 0): ?>
                            <span style="background: #666; color: white; padding: 2px 6px; font-size: 0.75rem;">
                                <?php echo $unread; ?>
                            </span>
                        <?php endif; ?>
                    </a>
                    <a href="profile.php?id=<?php echo $_SESSION['user_id']; ?>">Mano profilis</a>

                    <?php if (has_role('accountant')): ?>
                        <a href="accountant.php">Buhalterija</a>
                    <?php endif; ?>

                    <?php if (has_role('moderator')): ?>
                        <a href="moderator.php">Moderavimas</a>
                    <?php endif; ?>

                    <?php if (has_role('admin')): ?>
                        <a href="admin.php">Administravimas</a>
                    <?php endif; ?>

                    <div class="nav-user">
                        <a href="profile.php?id=<?php echo $_SESSION['user_id']; ?>" style="text-decoration: none; color: inherit;">
                            <span><?php echo htmlspecialchars($_SESSION['vardas']); ?></span>
                        </a>
                        <span class="balance"><?php
                            $user = get_logged_in_user();
                            echo format_money($user['balansas']);
                        ?></span>
                        <a href="logout.php" class="btn-logout">Atsijungti</a>
                    </div>
                <?php else: ?>
                    <a href="login.php">Prisijungti</a>
                    <a href="register.php">Registruotis</a>
                <?php endif; ?>
            </div>
        </div>
    </nav>

    <main class="container">
        <?php
        // Rodyti sėkmės pranešimus
        if (isset($_SESSION['success'])) {
            echo '<div class="alert alert-success">' . htmlspecialchars($_SESSION['success']) . '</div>';
            unset($_SESSION['success']);
        }

        // Rodyti klaidos pranešimus
        if (isset($_SESSION['error'])) {
            echo '<div class="alert alert-error">' . htmlspecialchars($_SESSION['error']) . '</div>';
            unset($_SESSION['error']);
        }
        ?>
