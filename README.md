# GlobeTrotter

A personalized, intelligent, and collaborative platform that transforms the way individuals plan and experience travel.

## Tech Stack
- **Backend**: Core PHP 8.2 (Procedural MVC / REST API endpoints)
- **Database**: PostgreSQL 15
- **Frontend**: HTML5, CSS3, Vanilla ES6 JavaScript, Bootstrap 5, FontAwesome, Chart.js
- **Containerization**: Docker & Docker Compose (Apache + PHP 8.2 + PostgreSQL)

---

## Quick Start via Docker Compose (Recommended)

To run GlobeTrotter in a fully containerized environment with Apache, PHP 8.2, and PostgreSQL pre-configured:

1. Ensure **Docker Desktop** is installed and running.
2. Start the containerized stack:
   ```bash
   docker compose up -d --build
   ```
3. Open your browser and navigate to:
   ```text
   http://localhost:8080
   ```
4. The database is automatically initialized from `database.sql`.
5. Default Admin Credentials:
   - **Email**: `admin@globetrotter.dev`
   - **Password**: `Admin@123`

---

## Native Setup (XAMPP / Local Apache & PostgreSQL)

1. Copy `.env.example` to `.env` or set your environment variables in `config.php`:
   ```bash
   export DB_HOST=127.0.0.1
   export DB_PORT=5432
   export DB_NAME=globetrotter
   export DB_USER=postgres
   export DB_PASS=your_password
   ```
2. Import the schema and seed data into PostgreSQL:
   ```bash
   psql -U postgres -d globetrotter -f database.sql
   ```
3. Execute the schema migration script:
   ```bash
   php migrate_schema.php
   ```
4. Point your local web server (Apache/Nginx) document root to the project root directory.

---

## Application Structure

```text
GlobeTrotter/
├── Dockerfile                  # Container build instructions (PHP 8.2 + Apache)
├── docker-compose.yml          # Container orchestration (Web + Postgres DB)
├── database.sql                # Complete PostgreSQL schema & seed data
├── migrate_schema.php          # Database migration script
├── config.php                  # Application configuration & PDO setup
├── index.php                   # Entry point (redirects to dashboard/login)
├── login.php, register.php     # Authentication screens
├── dashboard.php               # User dashboard & overview
├── create-trip.php, my-trips.php # Trip creation & management
├── itinerary-builder.php       # Interactive day-by-day itinerary builder
├── itinerary-view.php          # Structured trip summary view
├── calendar-view.php           # Visual timeline calendar grid
├── budget-view.php             # Financial tracking & Chart.js breakdown
├── city-search.php             # Destination exploration & discovery
├── community.php               # Social feed & traveler posts
├── profile.php                 # User settings & saved destinations
├── public-itinerary.php        # Sharable read-only public trip viewer
├── admin/index.php             # Administrator analytics dashboard
├── api/                        # REST JSON API endpoints
│   ├── trips.php, stops.php, activities.php, cities.php
│   ├── budget.php, community.php, profile.php
└── assets/                     # Frontend assets (CSS, JS, Uploads)
    ├── css/
    └── js/ (main.js, trips.js, itinerary.js, budget.js, community.js)
```
