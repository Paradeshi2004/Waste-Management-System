# Waste Management System 🌿

A digital platform connecting communities with municipal authorities to streamline waste reporting, tracking, and environmental action.

## Features

- **Report Complaints** — Submit waste/garbage issues with photos and geolocation
- **Track Status** — Real-time complaint tracking with status timeline
- **Admin Panel** — Municipal authority dashboard for managing all reports
- **Recycling Tips** — Educational content on sustainable waste management

## Tech Stack

| Layer | Technology |
|-------|-----------|
| Frontend | HTML5, CSS3, Bootstrap 5, JavaScript |
| Backend | PHP 8+ |
| Database | MySQL / MariaDB |
| Server | Apache (XAMPP / WAMP / LAMP) |

## Requirements

- XAMPP, WAMP, or LAMP stack
- PHP 8.0+
- MySQL 5.7+ or MariaDB 10+
- PHPMyAdmin (for easy DB setup)

## Quick Setup

### 1. Fork & Clone
```bash
git clone https://github.com/your-repo/Waste-Management-System.git
# Move to your htdocs / www directory
cp -r Waste-Management-System /xampp/htdocs/
```

### 2. Configure Database
1. Open **PHPMyAdmin** → `http://localhost/phpmyadmin`
2. Create a new database called `waste_management_db`
3. Import `sql/wms.sql`

### 3. Configure App
Edit `includes/config.php`:
```php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');        // your MySQL password
define('DB_NAME', 'waste_management_db');
define('APP_URL', 'http://localhost/Waste-Management-System');
```

### 4. Launch
Visit: `http://localhost/Waste-Management-System`

## Default Admin Login
```
Email:    admin@wms.local
Password: password
```
> ⚠️ Change this immediately after first login!

## Project Structure

```
Waste-Management-System/
├── index.php               # Homepage
├── includes/
│   ├── config.php          # DB config & connection
│   ├── auth.php            # Login, register, session helpers
│   ├── complaints.php      # Complaint CRUD helpers
│   ├── header.php          # Shared nav header
│   └── footer.php          # Shared footer
├── pages/
│   ├── login.php           # User login
│   ├── register.php        # New user registration
│   ├── dashboard.php       # User's complaints dashboard
│   ├── submit.php          # Report a new complaint
│   ├── complaint.php       # Complaint detail + timeline
│   ├── tips.php            # Recycling tips
│   └── logout.php          # Session destroy
├── admin/
│   ├── index.php           # Admin dashboard
│   ├── complaints.php      # All complaints management
│   └── edit-complaint.php  # Update complaint status
├── css/
│   └── style.css           # Custom styles
├── js/
│   └── main.js             # Client-side JS
├── uploads/                # Complaint photos (auto-created)
└── sql/
    └── wms.sql             # Database schema + seed data
```

## Contributing

1. Fork the repository
2. Create a feature branch: `git checkout -b feature/my-feature`
3. Commit your changes
4. Submit a pull request

## License

Open-source — free for municipal use and community deployment.
