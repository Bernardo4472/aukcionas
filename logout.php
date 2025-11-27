<?php
/**
 * Atsijungimo puslapis
 * Sukurta: 2025-11-27
 * Autorius: Rokas Kaziulis
 */

session_start();
require_once 'includes/db.php';
require_once 'includes/auth.php';

// Atsijungti
logout_user();

// Peradresuoti į prisijungimo puslapį
$_SESSION['success'] = 'Sėkmingai atsijungėte.';
header("Location: login.php");
exit();
