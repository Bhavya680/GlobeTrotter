# GlobeTrotter

A personalized, intelligent, and collaborative platform that transforms the way individuals plan and experience travel.

## Architecture
This project follows an MVC (Model-View-Controller) architecture.
- **public/**: Web root directory. All requests route through `index.php`.
- **config/**: Configuration files. Duplicate `config.example.php` to `config.php` and set your local credentials.
- **src/**: Core application logic (Controllers, Models, Views, Services).
- **database/**: Database schema and migrations.
- **public/assets/**: CSS, JS, and image uploads.

## Setup
1. Clone the repository.
2. Copy `config/config.example.php` to `config/config.php` and configure your database settings.
3. Import `database/schema.sql` to your local MySQL/PostgreSQL database.
4. Point your local web server (Apache/Nginx) document root to the `public/` directory.
