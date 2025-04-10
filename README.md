# TableTalk - Restaurant Ordering System

TableTalk is a simple yet effective full-stack restaurant table ordering system built with plain PHP, MySQL, and Vanilla JavaScript. It features a REST API backend and a Progressive Web App (PWA) frontend designed for tablets at restaurant tables, and a dedicated queue view for the kitchen.

## Features

- **Customer PWA (Tablet):** Browse the menu, place an order, view live order status and wait times, process mock payments, and submit feedback.
- **Kitchen Display System (KDS):** Real-time view for chefs to manage incoming orders and update statuses.
- **RESTful API:** Clean API structure with robust JSON endpoints.
- **Custom MVC Architecture:** Zero-dependency PHP backend using a front controller pattern and PDO for database access.
- **JWT Authentication:** Secure endpoints with custom JSON Web Tokens.

## Project Structure

- `/Core` - Custom framework classes (Database, Router, JWT utility)
- `/Models` - Database interaction classes
- `/Controllers` - Request handlers for API endpoints
- `/public` - Frontend PWA files (HTML, CSS, JS, Service Worker)
- `index.php` - Front controller handling all API routing
- `schema.sql` - Database schema and initial seed data

## Setup Instructions

### 1. Database Setup
1. Create a MySQL database (e.g., using phpMyAdmin or the MySQL CLI).
2. Import the `schema.sql` file to create tables and seed data.
3. Ensure your local MySQL credentials match those in `Core/Database.php` (default is `root` with no password).

### 2. Running Locally (PHP Built-in Server)
You can run this project easily using PHP's built-in development server. Navigate to the project root directory and run:

```bash
php -S localhost:8000
```

### 3. Usage
- **Customer Tablet View:** Navigate to `http://localhost:8000` (or `http://localhost:8000/public/`) in your browser to see the ordering interface.
- **Kitchen View:** Navigate to `http://localhost:8000/public/kitchen.html`. Log in with `chef@tabletalk.local` and password `password123`.

### 4. Postman Testing
Import the provided `Postman Collection.json` into Postman to test all endpoints independently. Make sure to update the environment variables `chef_token` and `table_token` after calling the respective Auth endpoints.

## Order Lifecycle

1. **Pending:** Customer places order via the tablet.
2. **Preparing:** Chef sees the order in the Kitchen view and updates status to preparing, optionally modifying the wait time.
3. **Ready:** Chef marks the order as ready.
4. **Served:** Chef marks the order as served. This triggers the tablet view to transition to the payment screen.
5. **Paid:** Customer pays (mock), transitioning the tablet to the feedback screen.
6. **Completed:** Customer submits feedback and is thanked.
