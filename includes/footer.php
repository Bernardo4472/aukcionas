    </main>

    <footer class="footer">
        <div class="container">
            <div class="footer-content">
                <div class="footer-info">
                    <h3>Aukcionų portalas</h3>
                    <p>Autorius: <strong>Rokas Kaziulis</strong></p>
                    <p>Modulis: <strong>T120B145 – Kompiuterių tinklai ir internetinės technologijos</strong></p>
                    <p>KTU &copy; <?php echo date('Y'); ?></p>
                </div>
                <div class="footer-links">
                    <h4>Nuorodos</h4>
                    <ul>
                        <li><a href="index.php">Pagrindinis</a></li>
                        <?php if (is_logged_in()): ?>
                            <li><a href="wallet.php">Piniginė</a></li>
                            <li><a href="create_auction.php">Sukurti aukcioną</a></li>
                        <?php else: ?>
                            <li><a href="login.php">Prisijungti</a></li>
                            <li><a href="register.php">Registruotis</a></li>
                        <?php endif; ?>
                    </ul>
                </div>
            </div>
        </div>
    </footer>

    <script src="assets/script.js"></script>
</body>
</html>
