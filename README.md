# Library Hub — PHP + MySQL Platform

## Overview
Library Hub is an educational PHP web application that stores HTML form submissions in a MySQL database. Users can register library members, add books, and create borrowing records. Saved records appear in tables on the home page.

## Requirements
- PHP 8.1+ with `pdo_mysql` and `mbstring` extensions
- MySQL 8.0+ (or compatible MySQL server)
- A local web server (XAMPP, MAMP, or PHP built-in server)

## Setup
1. Install and start MySQL and a PHP-capable server (for example, MAMP or XAMPP).
2. Import `database.sql` into MySQL using phpMyAdmin's Import tab or run:
   ```bash
   mysql -u root -p < database.sql
   ```
3. Configure the connection. `config.php` reads these environment variables: `DB_HOST` (default `127.0.0.1`), `DB_NAME` (default `library_platform`), `DB_USER` (default `root`), and `DB_PASSWORD` (default empty). For a local setup, change the defaults in `config.php` if needed, or set environment variables. Do not commit real passwords to GitHub.
4. In the project folder, start PHP's local server:
   ```bash
   php -S localhost:8000
   ```
   Alternatively, copy the folder to your XAMPP `htdocs` or MAMP web root and use its localhost URL.
5. Open http://localhost:8000 in your browser (or your XAMPP/MAMP project URL).

## How to use and verify
1. Fill out **Register a member** and submit. Confirm the green success message and that the member appears in Recent members.
2. Fill out **Add a book** and submit. Confirm the book appears in Recent books.
3. Select the new member and book, enter a due date, and submit **Create a loan**. Confirm the record appears in Recent loans.
4. In MySQL Workbench or phpMyAdmin, run:
   ```sql
   USE library_platform;
   SELECT * FROM members;
   SELECT * FROM books;
   SELECT * FROM loans;
   ```
   Confirm the entered form data is present in the database.

## How forms interact with the database
The browser sends form data via POST to `index.php`. PHP validates the submitted values and checks a CSRF token. A PDO connection from `config.php` uses prepared INSERT statements to write to the appropriate MySQL table. On success, the browser is redirected to avoid accidental duplicate submissions. SELECT queries load the latest 25 members, books, and loans for display. Foreign keys link loans to members and books.

## Security and design features
- PDO prepared statements for submitted values to reduce SQL injection risk
- HTML output escaping to reduce cross-site scripting risk
- CSRF token validation on POST requests
- Server-side input validation and email validation
- Database foreign keys and unique email/ISBN constraints
- Responsive layout, success/error feedback, and readable data tables
- Connection errors do not expose database passwords or raw SQL errors

## Project files
- `index.php`: web interface, form handling, and record display
- `config.php`: PDO database connection
- `database.sql`: database and table creation script
- `assets/style.css`: responsive page styling
- `README.md`: setup, usage, features, and database documentation
- `.gitignore`: prevents accidental inclusion of local secrets

## GitHub submission
Create a GitHub repository (for example, `library-hub-php`), upload the contents of this folder, and submit the **actual repository URL** to your instructor. Do not submit a made-up link. Add screenshots of your running site and MySQL SELECT results if your instructor requests evidence.

## Notes and limitations
This is a classroom demonstration, not a production-ready library system. It has no login/access control, pagination, or loan-return workflow. The dropdowns show the 25 most recent members/books. Use a dedicated least-privilege database user and HTTPS if deployed online. The application has not been executed against your own MySQL server; you must run and verify it in your environment before submitting.
