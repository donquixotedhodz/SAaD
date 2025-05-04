# Hotel Reservation Management System

A modern hotel reservation system with QR code functionality built using PHP, MySQL, Bootstrap, and JavaScript.

## Features

- Modern and responsive landing page
- Room booking system with availability checking
- QR code generation for reservations
- Admin panel for managing reservations
- Free admin registration
- Secure authentication system

## Prerequisites

- PHP 7.4 or higher
- MySQL 5.7 or higher
- Composer
- XAMPP (or similar local development environment)

## Installation

1. Clone this repository to your XAMPP htdocs folder:
   ```bash
   git clone <repository-url> c:/xampp/htdocs/richardshotelMS
   ```

2. Navigate to the project directory:
   ```bash
   cd c:/xampp/htdocs/richardshotelMS
   ```

3. Install PHP dependencies using Composer:
   ```bash
   composer install
   ```

4. Create required directories:
   ```bash
   mkdir assets/qrcodes
   mkdir assets/rooms
   ```

5. Make sure the assets/qrcodes directory is writable:
   ```bash
   chmod 777 assets/qrcodes
   ```

6. Start your XAMPP Apache and MySQL services

7. The database and tables will be automatically created when you first access the application

8. Access the application:
   - Main website: http://localhost/richardshotelMS
   - Admin panel: http://localhost/richardshotelMS/admin/login.php

## Directory Structure

```
richardshotelMS/
├── admin/              # Admin panel files
├── assets/            
│   ├── qrcodes/       # Generated QR codes
│   └── rooms/         # Room images
├── config/            # Configuration files
├── css/               # Stylesheets
├── includes/          # PHP processing files
├── js/                # JavaScript files
├── vendor/            # Composer dependencies
├── composer.json      # Composer configuration
└── README.md          # This file
```

## Usage

1. Register an admin account at `/admin/register.php`
2. Add room types and rooms through the admin panel
3. Upload room images to the `assets/rooms` directory
4. Customers can make reservations through the main website
5. QR codes will be generated automatically for each reservation

## Security Features

- Password hashing using PHP's password_hash()
- Prepared statements for all database queries
- Session-based authentication
- Input validation and sanitization

## Contributing

1. Fork the repository
2. Create your feature branch
3. Commit your changes
4. Push to the branch
5. Create a new Pull Request
