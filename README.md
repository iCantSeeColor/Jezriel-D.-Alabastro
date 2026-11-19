# Personal Portfolio Website (Dynamic PHP + MySQL)

A dynamic personal portfolio website of **Jezriel D. Alabastro**, a 3rd year BS Information Technology student at Negros Oriental State University. This is the final version of my midterm portfolio. The static HTML pages were converted into a PHP and MySQL application with a secure Admin Dashboard, so all content can be managed without editing source code.

**Live website:** https://jezriel-portfolio.infinityfreeapp.com
**Midterm website address (Netlify):** https://shiny-griffin-745a5c.netlify.app/
**Admin login page:** `/admin/login.php`

> Administrator credentials are submitted privately through the LMS and are not stored in this repository.

## Features

### Public website
- Home, About, Skills, Projects, Resume (education, experience, achievements) and Contact pages
- All content is loaded from the MySQL database
- Project filter buttons built from the categories in the database
- Contact form that saves messages to the database
- Responsive layout (desktop, tablet, mobile) with light and dark mode

### Admin Dashboard
- Secure administrator login and logout
- Dashboard with statistics (projects, skills, education, experience, achievements, messages, unread messages)
- **Profile:** update name, headline, introduction, biography, contact details, social links and profile photo
- **Skills:** add, edit, delete and categorize
- **Projects:** add, edit, delete and upload screenshots
- **Education, Experience, Achievements:** add, edit, delete (achievements support a certificate image)
- **Messages:** view, mark as read or unread, delete
- **Settings:** website title, logo (text or image), footer text and default theme

Every change in the admin panel appears automatically on the public website.

## Security Features
- Passwords hashed with `password_hash()` and verified with `password_verify()`; no plain-text passwords
- Session ID regenerated after login, session cookie flags (HttpOnly, SameSite), and automatic logout after 30 minutes of inactivity
- Login attempt limit: 5 failed attempts per IP address locks login for 15 minutes
- Admin pages protected by `require_admin()`; a generic error message is shown for failed logins
- CSRF tokens on every form that changes data (including logout and delete actions)
- Prepared statements (PDO) for all database queries to prevent SQL injection
- All dynamic output is escaped with `htmlspecialchars()` to prevent XSS
- Server-side validation on every form
- Secure image uploads: real file content check (`finfo` and `getimagesize`), allowed types JPG/PNG/WEBP/GIF only, 2 MB size limit, maximum dimensions, random file names, and script execution blocked inside `uploads/`
- Contact form spam protection: hidden honeypot field and a limit of 3 messages per IP every 10 minutes
- Safe error messages (database details are never shown to visitors)
- `includes/` and `database/` folders blocked from web access; directory listing disabled; security headers set in `.htaccess`
- Database credentials stored in `includes/config.php`, which is excluded from Git

## Technologies
HTML5, CSS3, JavaScript, PHP 8, MySQL/MariaDB (PDO), Apache (XAMPP for local development)

## Requirements
- XAMPP (or any Apache + PHP 8.0+ + MySQL/MariaDB stack)
- PHP extensions: `pdo_mysql`, `fileinfo`, `mbstring`
- A web browser

## Project Structure
```
portfolio/
├── index.php, about.php, skills.php, projects.php, resume.php, contact.php
├── .htaccess                  security headers, directory listing off
├── admin/                     login, logout, dashboard, profile, skills, projects,
│                              education, experience, achievements, messages, settings
├── includes/                  db, auth, security, functions, upload, header/footer,
│                              admin_header/admin_footer, config.example.php
├── css/                       style.css (public), admin.css (admin)
├── js/main.js
├── images/                    static images
├── uploads/                   uploaded images (script execution disabled)
├── database/
│   ├── portfolio.sql          database structure and sample data
│   └── create_admin.php       command-line script to create the admin account
└── README.md
```

## Local Installation (XAMPP)

1. **Copy the project** into `C:\xampp\htdocs\portfolio` (or clone it):
   ```
   git clone https://github.com/iCantSeeColor/Jezriel-D.-Alabastro.git portfolio
   ```
2. **Start Apache and MySQL** in the XAMPP Control Panel.
3. **Import the database.** Open `http://localhost/phpmyadmin`, click **Import**, choose `database/portfolio.sql`, and run it once. It creates the database `portfolio_db` with all tables and sample data.
4. **Create the configuration file.** Copy `includes/config.example.php` to `includes/config.php` and fill in your values. For local XAMPP:
   ```php
   <?php
   return [
       'db_host'    => 'localhost',
       'db_name'    => 'portfolio_db',
       'db_user'    => 'root',
       'db_pass'    => '',
       'db_charset' => 'utf8mb4',
   ];
   ```
5. **Create the administrator account.** In a terminal, inside the project folder:
   ```
   C:\xampp\php\php.exe database\create_admin.php
   ```
   Enter a username and a password of at least 10 characters. The password is stored only as a hash.
6. **Open the site:**
   - Public website: `http://localhost/portfolio/`
   - Admin login: `http://localhost/portfolio/admin/login.php`

## Deployment Notes (InfinityFree)
1. Create a MySQL database in the hosting control panel and import a copy of `portfolio.sql` without the `CREATE DATABASE` and `USE` lines.
2. Upload the project files to `htdocs` by FTP. Do not upload `.git`, `.gitignore` or the `database/` folder.
3. Create `includes/config.php` on the server with the hosting database host, name, username and password.
4. Create the administrator by inserting a username and a password hash into the `admins` table through phpMyAdmin.
5. Enable the free SSL certificate and uncomment the HTTPS redirect lines in the server's `.htaccess`.

## Screenshots
Screenshots of the public website, admin login, dashboard, CRUD operations, database and responsive layout are in the `screenshots/` folder.

## Version Control
The repository history shows the development from the static midterm site to the final dynamic website: database structure, PDO connection, page conversion, admin authentication, security hardening, CRUD modules, secure uploads, contact form, settings, and documentation.

## Author
Jezriel D. Alabastro, BS Information Technology, Negros Oriental State University
