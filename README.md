# Aukcionų Portalas

A full-featured auction portal system built with PHP, MySQL, HTML, CSS, and JavaScript.

**Project Title:** Aukcionų portalas
**Author:** Rokas Kaziulis
**Module:** T120B145 – Kompiuterių tinklai ir internetinės technologijos
**University:** Kauno Technologijos Universitetas (KTU)

---

## 📋 Features

### Core Functionality
- ✅ **User Authentication**: Secure registration and login with password hashing
- ✅ **Auction Creation**: Create auctions with start/end times, bid steps, and visibility controls
- ✅ **Bidding System**: Place bids with automatic refunds when outbid
- ✅ **Virtual Wallet**: Manage balance with transaction history
- ✅ **Comments System**: Comment on auctions
- ✅ **Role-Based Access Control**: User, Moderator, Accountant, Administrator roles
- ✅ **Audit Logging**: Track all important actions

### User Roles

#### 👤 User
- Create auctions
- Place bids
- View transaction history
- Comment on auctions

#### 🛡️ Moderator
- Delete inappropriate comments
- Delete problematic auctions (without bids)
- View all auctions and comments

#### 💼 Accountant
- View all users and balances
- Add money to user wallets
- View complete transaction history

#### ⚙️ Administrator
- All moderator and accountant permissions
- Change user roles
- Hide/unhide auctions
- View audit logs
- Full system access

---

## 🛠️ Technologies

- **Backend:** PHP 8+
- **Database:** MySQL 5.7+
- **Frontend:** HTML5, CSS3, JavaScript (ES6+)
- **Server:** Apache (XAMPP)
- **Database Interface:** MySQLi with prepared statements

---

## 📁 Project Structure

```
/aukcionas/
│
├── index.php                 # Main auction listing page
├── login.php                 # User login
├── register.php              # User registration
├── logout.php                # Logout handler
├── create_auction.php        # Create new auction
├── auction.php               # Auction details and bidding
├── wallet.php                # User wallet and transactions
├── accountant.php            # Accountant panel
├── admin.php                 # Administrator panel
├── moderator.php             # Moderator panel
│
├── includes/
│   ├── db.php               # Database connection and utilities
│   ├── auth.php             # Authentication functions
│   ├── functions.php        # Helper functions
│   ├── header.php           # Page header template
│   └── footer.php           # Page footer template
│
├── assets/
│   ├── style.css            # Main stylesheet
│   └── script.js            # JavaScript functionality
│
├── database.sql             # Database schema and demo data
└── README.md                # This file
```

---

## ⚙️ Installation

### Prerequisites
- XAMPP (Apache + MySQL + PHP)
- Web browser (Chrome, Firefox, Edge, etc.)
- Text editor (optional, for code inspection)

### Step-by-Step Setup

#### 1. Install XAMPP
- Download XAMPP from [https://www.apachefriends.org](https://www.apachefriends.org)
- Install XAMPP to `C:\xampp` (default location)

#### 2. Start Services
- Open XAMPP Control Panel
- Start **Apache** service
- Start **MySQL** service

#### 3. Create Database
- Open browser and navigate to `http://localhost/phpmyadmin`
- Click "New" in the left sidebar
- Create database named: `aukcionas`
- Select the database
- Click "Import" tab
- Click "Choose File" and select `database.sql` from the project
- Click "Go" to import

#### 4. Copy Project Files
- Copy the entire `aukcionas` folder to `C:\xampp\htdocs\`
- Final path should be: `C:\xampp\htdocs\aukcionas\`

#### 5. Access the Application
- Open browser and navigate to: `http://localhost/aukcionas/`
- You will be redirected to the login page
- Use one of the demo accounts (see below)

---

## 🔐 Demo Accounts

The system includes pre-configured demo accounts:

| Role | Email | Password | Balance |
|------|-------|----------|---------|
| **Administrator** | admin@ktu.lt | admin123 | 5,000.00 € |
| **Accountant** | accountant@ktu.lt | acc123 | 3,000.00 € |
| **Moderator** | moderator@ktu.lt | admin123 | 2,000.00 € |
| **User** | user@ktu.lt | user123 | 1,000.00 € |

---

## 💡 Usage Guide

### For Regular Users

#### Register an Account
1. Go to `http://localhost/aukcionas/register.php`
2. Fill in your name, email, and password (min 8 characters)
3. You'll receive 1,000 € starting balance

#### Create an Auction
1. Login and click "Sukurti aukcioną" in navigation
2. Fill in auction details:
   - Title and description
   - Starting price and bid step
   - Start and end times
   - Optional: Check "Hide auction" to make it private

#### Place a Bid
1. Browse auctions on the main page
2. Click on an auction to view details
3. Enter your bid amount (must be >= current price + bid step)
4. Click "Statyti" to place bid
5. Money is deducted from your wallet immediately
6. If outbid, you'll automatically receive a refund

#### View Your Wallet
1. Click "Piniginė" in navigation
2. View current balance and transaction history

### For Accountants

1. Login with accountant credentials
2. Access "Buhalterija" from navigation
3. View all user balances
4. Click "Papildyti" to add money to any user's wallet
5. View complete transaction history

### For Moderators

1. Login with moderator credentials
2. Access "Moderavimas" from navigation
3. Delete inappropriate comments
4. Delete problematic auctions (only those without bids)

### For Administrators

1. Login with admin credentials
2. Access "Administravimas" from navigation
3. Change user roles
4. Hide/unhide auctions
5. View audit logs
6. Add money to wallets
7. Full system oversight

---

## 🔒 Security Features

- ✅ **Password Hashing**: All passwords hashed with `password_hash()`
- ✅ **SQL Injection Prevention**: Prepared statements throughout
- ✅ **XSS Protection**: Input sanitization with `htmlspecialchars()`
- ✅ **Session Management**: Secure session handling
- ✅ **Role-Based Access**: Enforced on every page
- ✅ **Audit Logging**: All critical actions logged

---

## 📊 Database Schema

### Tables

#### `vartotojai` (Users)
- id, vardas, el_pastas, slaptazodis, role, balansas, registracijos_data

#### `aukcionai` (Auctions)
- id, pavadinimas, aprasymas, pradine_kaina, dabartine_kaina, bid_step
- pradzios_laikas, pabaigos_laikas, pasleptas, vartotojo_id, busena

#### `statymai` (Bids)
- id, aukciono_id, vartotojo_id, suma, data_laikas

#### `transakcijos` (Transactions)
- id, vartotojo_id, suma, tipas, data_laikas, aprasymas

#### `komentarai` (Comments)
- id, aukciono_id, vartotojo_id, tekstas, data_laikas

#### `audit_log` (Audit Log)
- id, vartotojo_id, veiksmas, aprasymas, data_laikas

---

## 🎨 Design Features

- **Responsive Design**: Works on desktop and mobile devices
- **Modern UI**: Clean, professional interface
- **Color-Coded Status**: Easy visual identification of auction states
- **Real-Time Countdown**: Live auction timer updates
- **Toast Messages**: Success/error notifications
- **Role Badges**: Visual role indicators

---

## 🚀 Advanced Features

### Automatic Refund System
When a user is outbid, the system automatically:
1. Deducts new bidder's money
2. Refunds previous highest bidder
3. Updates auction price
4. Records all transactions
5. Logs the action

### Auction Visibility Control
- Users can hide their auctions
- Hidden auctions visible only to owner and admin
- Useful for private or sensitive items

### Bid Step Validation
- Enforces minimum bid increments
- Prevents underbidding
- Customizable per auction

### Transaction History
- Complete audit trail
- All balance changes tracked
- Transaction types: deposit, bid, refund, winning

---

## 🐛 Troubleshooting

### Cannot Access http://localhost/aukcionas/

**Solution:**
- Ensure Apache is running in XAMPP
- Check that files are in `C:\xampp\htdocs\aukcionas\`
- Try `http://127.0.0.1/aukcionas/`

### Database Connection Error

**Solution:**
- Ensure MySQL is running in XAMPP
- Verify database name is `aukcionas`
- Check `includes/db.php` for correct credentials
- Default: host=localhost, user=root, password=blank

### Cannot Login

**Solution:**
- Verify database was imported correctly
- Check that `vartotojai` table has demo users
- Try re-importing `database.sql`

### Auction Timer Not Updating

**Solution:**
- Enable JavaScript in your browser
- Clear browser cache
- Check browser console for errors

---

## 📝 Validation Rules

### Registration
- All fields required
- Email must be valid format
- Email must be unique
- Password minimum 8 characters

### Auction Creation
- All fields required
- Start price must be positive
- Bid step must be positive
- End time must be after start time
- End time must be in the future

### Bidding
- Cannot bid on own auction
- Bid must be >= current price + bid step
- Must have sufficient balance
- Auction must be active

---

## 🎓 Academic Context

This project was developed as part of the **T120B145 – Kompiuterių tinklai ir internetinės technologijos** (Computer Networks and Internet Technologies) module at **Kauno Technologijos Universitetas (KTU)**.

### Learning Objectives Demonstrated
- Full-stack web development
- Database design and normalization
- User authentication and authorization
- Session management
- Security best practices
- Transaction handling
- Role-based access control
- Audit logging
- Responsive web design

---

## 📄 License

This is an academic project created for educational purposes.

**Author:** Rokas Kaziulis
**Year:** 2025
**Institution:** Kauno Technologijos Universitetas (KTU)

---

## 🙏 Credits

- **Developer:** Rokas Kaziulis
- **Institution:** Kauno Technologijos Universitetas
- **Module:** T120B145 – Kompiuterių tinklai ir internetinės technologijos
- **Technologies:** PHP, MySQL, HTML, CSS, JavaScript
- **Server:** XAMPP

---

## 📞 Support

For issues or questions:
1. Check the Troubleshooting section
2. Verify all installation steps were followed
3. Ensure XAMPP services are running
4. Check browser console for JavaScript errors
5. Review database connection settings

---

**Last Updated:** 2025-11-27
**Version:** 1.0
**Status:** Production Ready
