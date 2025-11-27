/**
 * Aukcionų portalo JavaScript
 * Sukurta: 2025-11-27
 * Autorius: Rokas Kaziulis
 */

// Patvirtinimo dialogai
function confirmDelete(message) {
    return confirm(message || 'Ar tikrai norite ištrinti?');
}

// Formos validacija
document.addEventListener('DOMContentLoaded', function() {

    // Automatiškai uždaryti pranešimus po 5 sekundžių
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(alert => {
        setTimeout(() => {
            alert.style.opacity = '0';
            setTimeout(() => alert.remove(), 300);
        }, 5000);
    });

    // Bid formos validacija
    const bidForm = document.getElementById('bidForm');
    if (bidForm) {
        bidForm.addEventListener('submit', function(e) {
            const bidAmount = parseFloat(document.getElementById('bid_amount').value);
            const minBid = parseFloat(document.getElementById('min_bid').value);

            if (bidAmount < minBid) {
                e.preventDefault();
                alert('Statymo suma per maža! Minimali suma: ' + minBid + ' €');
                return false;
            }
        });
    }

    // Aukciono formos validacija
    const auctionForm = document.getElementById('auctionForm');
    if (auctionForm) {
        auctionForm.addEventListener('submit', function(e) {
            const startTime = new Date(document.getElementById('pradzios_laikas').value);
            const endTime = new Date(document.getElementById('pabaigos_laikas').value);
            const price = parseFloat(document.getElementById('pradine_kaina').value);
            const bidStep = parseFloat(document.getElementById('bid_step').value);

            if (startTime >= endTime) {
                e.preventDefault();
                alert('Pabaigos laikas turi būti vėlesnis nei pradžios laikas!');
                return false;
            }

            if (price <= 0) {
                e.preventDefault();
                alert('Pradinė kaina turi būti teigiama!');
                return false;
            }

            if (bidStep <= 0) {
                e.preventDefault();
                alert('Statymo žingsnis turi būti teigiamas!');
                return false;
            }
        });
    }

    // Registracijos formos validacija
    const registerForm = document.getElementById('registerForm');
    if (registerForm) {
        registerForm.addEventListener('submit', function(e) {
            const password = document.getElementById('slaptazodis').value;
            const email = document.getElementById('el_pastas').value;

            if (password.length < 8) {
                e.preventDefault();
                alert('Slaptažodis turi būti bent 8 simbolių ilgio!');
                return false;
            }

            if (!email.includes('@')) {
                e.preventDefault();
                alert('Neteisingas el. pašto formatas!');
                return false;
            }
        });
    }

    // Balanso papildymo validacija
    const balanceForm = document.getElementById('addBalanceForm');
    if (balanceForm) {
        balanceForm.addEventListener('submit', function(e) {
            const amount = parseFloat(document.getElementById('amount').value);

            if (amount <= 0) {
                e.preventDefault();
                alert('Suma turi būti teigiama!');
                return false;
            }

            if (amount > 10000) {
                e.preventDefault();
                alert('Maksimali vienkartinė suma: 10,000 €');
                return false;
            }
        });
    }
});

// Laikmačio funkcija aukcionams
function updateAuctionTimers() {
    const timers = document.querySelectorAll('.auction-timer');

    timers.forEach(timer => {
        const endTime = new Date(timer.dataset.endtime).getTime();
        const now = new Date().getTime();
        const distance = endTime - now;

        if (distance < 0) {
            timer.innerHTML = 'Aukcionas pasibaigė';
            timer.style.color = '#e74c3c';
            return;
        }

        const days = Math.floor(distance / (1000 * 60 * 60 * 24));
        const hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
        const minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
        const seconds = Math.floor((distance % (1000 * 60)) / 1000);

        let timeString = '';
        if (days > 0) timeString += days + 'd ';
        timeString += hours + 'h ' + minutes + 'm ' + seconds + 's';

        timer.innerHTML = 'Liko: ' + timeString;
    });
}

// Atnaujinti laikmačius kas sekundę
if (document.querySelectorAll('.auction-timer').length > 0) {
    updateAuctionTimers();
    setInterval(updateAuctionTimers, 1000);
}

// Paieškos funkcija
function searchAuctions() {
    const input = document.getElementById('searchInput');
    const filter = input.value.toLowerCase();
    const cards = document.querySelectorAll('.auction-card');

    cards.forEach(card => {
        const title = card.querySelector('h3').textContent.toLowerCase();
        const description = card.querySelector('.auction-description').textContent.toLowerCase();

        if (title.includes(filter) || description.includes(filter)) {
            card.style.display = '';
        } else {
            card.style.display = 'none';
        }
    });
}

// Rūšiavimo funkcija
function sortAuctions(criteria) {
    const grid = document.querySelector('.auctions-grid');
    const cards = Array.from(document.querySelectorAll('.auction-card'));

    cards.sort((a, b) => {
        switch(criteria) {
            case 'price-asc':
                return parseFloat(a.dataset.price) - parseFloat(b.dataset.price);
            case 'price-desc':
                return parseFloat(b.dataset.price) - parseFloat(a.dataset.price);
            case 'time-asc':
                return new Date(a.dataset.endtime) - new Date(b.dataset.endtime);
            case 'time-desc':
                return new Date(b.dataset.endtime) - new Date(a.dataset.endtime);
            default:
                return 0;
        }
    });

    cards.forEach(card => grid.appendChild(card));
}

// Dinaminis bid skaičiavimas
function updateMinimumBid() {
    const currentPrice = parseFloat(document.getElementById('current_price').value);
    const bidStep = parseFloat(document.getElementById('bid_step_value').value);
    const minBid = currentPrice + bidStep;

    document.getElementById('min_bid').value = minBid;
    document.getElementById('bid_amount').min = minBid;
    document.getElementById('bid_amount').value = minBid.toFixed(2);
}

// Konsolės pranešimas
console.log('Aukcionų portalas - Rokas Kaziulis © 2025');
console.log('T120B145 – Kompiuterių tinklai ir internetinės technologijos');
