# Architecture & Tech Stack - Globe Trotter

## 1. Technology Stack
The application is built strictly without comprehensive full-stack frameworks (like Laravel), relying on core technologies to ensure deep database mastery and fundamental understanding [cite: 2].
* **Backend**: PHP 8+ (Core PHP only) [cite: 2].
* **Web Server**: Apache via XAMPP (localhost environment) [cite: 2].
* **Database**: MySQL [cite: 2].
* **Frontend Core**: HTML5, CSS3, Vanilla JavaScript ES6 [cite: 2].
* **UI Framework & Styling**: Bootstrap 5 (CDN) for layout and components, FontAwesome (CDN) for icons [cite: 2].
* **Data Visualization**: 
  * Chart.js (CDN) for most bar/line charts [cite: 2].
  * React 18+, ReactDOM, Recharts, and Tailwind CSS (all via CDN) specifically for complex donut chart components (e.g., budget breakdown and admin trip status) [cite: 2].
* **Build Tools**: esbabel/esbuild via npm exclusively for compiling React JSX to JS [cite: 2].

## 2. System Architecture & Directory Structure
The architecture follows a modular procedural pattern with a distinct API layer returning JSON for AJAX interactions [cite: 2]. 

```text
globetrotter/
├── config.php                 # DB credentials, site constants
├── index.php                  # Redirects to dashboard or login
├── login.php, register.php, logout.php # Auth flow
├── dashboard.php              # Main landing page
├── create-trip.php, my-trips.php, itinerary-builder.php, itinerary-view.php # Trip management
├── city-search.php, activity-search.php # Discovery
├── calendar-view.php, profile.php, community.php, public-itinerary.php # Additional views
├── admin/                     # Admin module
│   ├── index.php              # Admin dashboard
│   ├── users.php, analytics.php
├── includes/                  # Core modules
│   ├── db.php                 # PDO MySQL connection (singleton pattern)
│   ├── auth.php               # Session helpers & role validation
│   ├── functions.php          # Reusable PHP helpers (flash messages, sanitization)
│   ├── header.php, footer.php # Common layout templates
├── api/                       # JSON Endpoint layer
│   ├── trips.php, stops.php, activities.php, cities.php, budget.php, community.php, profile.php
├── assets/
│   ├── css/style.css          # Custom CSS (Bootstrap overrides)
│   ├── js/
│   │   ├── main.js, trips.js, itinerary.js, calendar.js
│   │   ├── budget-chart.jsx   # React source for donut chart
│   │   ├── budget-chart.js    # Compiled JS output
│   ├── uploads/
│       ├── profiles/, covers/ # User and trip image storage
```

## 3. Database Schema (MySQL)
The system is powered by a normalized relational database containing the following tables [cite: 2]:

1. **users**: `id` (PK), `first_name`, `last_name`, `email` (UNIQUE), `password_hash`, `phone`, `city`, `country`, `profile_photo`, `additional_info`, `role` ('user' or 'admin'), timestamps [cite: 2].
2. **trips**: `id` (PK), `user_id` (FK), `trip_name`, `description`, `start_date`, `end_date`, `cover_photo`, `status` (upcoming, ongoing, completed), `visibility` (public, private), timestamps [cite: 2].
3. **cities**: `id` (PK), `name`, `country`, `region`, `cost_index`, `popularity_score`, `description`, `image_url` [cite: 2].
4. **activities**: `id` (PK), `city_id` (FK), `name`, `description`, `category` (ENUM), `cost`, `duration_hours`, `image_url` [cite: 2].
5. **trip_stops**: `id` (PK), `trip_id` (FK CASCADE), `city_id` (FK), `arrival_date`, `departure_date`, `order_index`, `notes`, `budget_for_stop` [cite: 2].
6. **trip_activities**: `id` (PK), `trip_stop_id` (FK CASCADE), `activity_id` (FK), `scheduled_date`, `scheduled_time`, `custom_cost`, `notes` [cite: 2].
7. **trip_budget**: `id` (PK), `trip_id` (FK UNIQUE), `transport_budget`, `stay_budget`, `activities_budget`, `meals_budget`, `misc_budget`, `total_budget` (GENERATED ALWAYS AS sum of budgets STORED) [cite: 2].
8. **community_posts**: `id` (PK), `user_id` (FK), `trip_id` (FK NULLABLE), `title`, `content`, `likes_count`, timestamps [cite: 2].
9. **community_likes**: `id` (PK), `post_id` (FK CASCADE), `user_id` (FK), UNIQUE KEY on (post_id, user_id) [cite: 2].
10. **saved_destinations**: `id` (PK), `user_id` (FK), `city_id` (FK), `saved_at`, UNIQUE KEY on (user_id, city_id) [cite: 2].

## 4. API & Communication Pattern
* All communication from the dynamic frontend components (like the itinerary builder, search, and charts) is executed via asynchronous `fetch()` requests (AJAX) to the `/api/` directory [cite: 2].
* API responses strictly adhere to a JSON structure: `{"success": true/false, "data": ..., "message": "..."}` [cite: 2].
* Security checks validate CSRF tokens on every POST/PUT/DELETE request and verify resource ownership before modifications [cite: 2].

## 5. Security Posture
* Passwords are encrypted using PHP's native `password_hash()` [cite: 2].
* SQL Injection is mitigated natively by strictly requiring PDO prepared statements for ALL database queries; string interpolation in queries is strictly forbidden [cite: 2].
* XSS is prevented by ensuring no query results are echoed unsanitized, utilizing `htmlspecialchars()` [cite: 2].
* Cross-Site Request Forgery (CSRF) protection is implemented by storing a token in `$_SESSION['csrf_token']` and validating it on state-changing requests [cite: 2].
