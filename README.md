<div align="center">

# 🌍 GlobeTrotter - Smart Travel Itinerary & Global Adventure Platform

### *A modern, full-stack travel planning and itinerary management platform featuring multi-city scheduling, interactive budget tracking, real-time analytics, public itinerary sharing, and community stories.*

[![License](https://img.shields.io/badge/License-MIT-blue?style=for-the-badge)](LICENSE)
[![PHP](https://img.shields.io/badge/PHP-8.2%20%7C%208.5-777BB4?style=for-the-badge&logo=php&logoColor=white)](https://www.php.net/)
[![PostgreSQL](https://img.shields.io/badge/PostgreSQL-15.x-336791?style=for-the-badge&logo=postgresql&logoColor=white)](https://www.postgresql.org/)
[![JavaScript](https://img.shields.io/badge/JavaScript-ES6+-F7DF1E?style=for-the-badge&logo=javascript&logoColor=black)](https://developer.mozilla.org/)
[![Bootstrap](https://img.shields.io/badge/Bootstrap-5.3-7952B3?style=for-the-badge&logo=bootstrap&logoColor=white)](https://getbootstrap.com/)
[![Chart.js](https://img.shields.io/badge/Chart.js-4.x-FF6384?style=for-the-badge&logo=chartdotjs&logoColor=white)](https://www.chartjs.org/)
[![React](https://img.shields.io/badge/React-18.x-61DAFB?style=for-the-badge&logo=react&logoColor=black)](https://react.dev/)

---

**GlobeTrotter is an end-to-end intelligent travel operating system designed to simplify, optimize, and inspire journey planning worldwide. Built with a bespoke travel aesthetic, clean glassmorphism accents, and robust server-side security, it replaces scattered travel notes with automated timeline generation, categorized cost accounting, live destination exploration, and instant multi-user synchronization.**

</div>

---

## 🎯 Key Feature Modules

GlobeTrotter contains **12 fully-implemented core screens and modules** divided into planning, visualization, financial, social, and administrative layers:

### 📊 1. User Dashboard & Travel Command Center (`dashboard.php`)
- **Live Travel Statistics:** Dynamic counters for Total Trips, Destinations Explored, Days Traveled, and Saved Wishlist items.
- **Hero Next Adventure Card:** High-visibility banner highlighting the user's nearest upcoming or ongoing journey with a live countdown timer.
- **Trip Status Carousels:** Tabbed segregation of trips (`Upcoming`, `Ongoing`, `Completed`) with custom cover imagery, destination tags, and one-click actions.
- **Destination Spotlight:** Curated carousel of trending global cities with real-time cost indices, popularity metrics, and instant wishlist toggles.

### ✈️ 2. Trip Creation & Itinerary Builder (`create-trip.php`, `itinerary-builder.php`)
- **Intuitive Trip Generator:** Quick setup modal capturing trip title, date range, description, visibility (`Public` vs. `Private`), and high-res cover photos.
- **Multi-Stop Timeline Builder:** Add, edit, reorder, and remove multi-city stops with dedicated arrival/departure dates, hotel stays, and transportation notes (flight, train, car, ferry).
- **Activity Scheduler:** Attach curated or custom activities to specific stops with time slots, duration estimates, categories, and custom pricing.
- **Auto-Syncing Date Propagation:** Modifying stop durations automatically recalculates timeline boundaries across the trip.

### 🗺️ 3. Itinerary View & Travel Guide (`itinerary-view.php`)
- **Day-by-Day Chronological Timeline:** Rich vertical timeline detailing hotel check-ins, transport connections, and categorized activities.
- **Live Weather & Destination Highlights:** Local climate indicators and regional tags for each itinerary destination.
- **Direct Navigation Controls:** Quick-jump shortcuts to the visual builder, budget tracking dashboard, and public shareable link.

### 💰 4. Interactive Budget Tracking & Cost Analytics (`budget-view.php`)
- **Budget vs. Actual Expense Breakdown:** High-precision tracking comparing initial budget estimates against actual expenditures.
- **Dynamic Category Charting (Chart.js):** Donut and bar charts breaking down spending across `Transport`, `Accommodation`, `Meals`, `Activities`, and `Shopping`.
- **Itemized Expense Ledger:** Log, edit, and categorize on-the-go receipts with date stamps and stop associations.
- **Over-Budget Warning System:** Real-time visual progress bars alerting users when category spending exceeds targeted allowances.

### 🔍 5. Global City & Activity Explorer (`city-search.php`, `activity-search.php`)
- **Comprehensive Global Database:** 22+ indexed major cities across 6 continents and 47+ curated activities.
- **Multi-Criteria Search & Filtering:** Filter destinations by region (`Europe`, `Asia`, `North America`, `South America`, `Africa`, `Oceania`), budget tier, and popularity score.
- **Category Filter Tabs:** Explore activities by `Sightseeing`, `Food & Dining`, `Adventure`, `Culture`, or `Relaxation` with duration and price sorting.
- **One-Click Wishlist:** Save favorite cities directly to your profile from the search grid.

### 📅 6. Visual Calendar View (`calendar-view.php`)
- **Full-Month Interactive Timeline:** Visual calendar overlay mapping all user trips and multi-city stops across months.
- **Trip Status Color Coding:** Instant differentiation between upcoming (blue), ongoing (amber), and completed (green) travel windows.
- **Event Modal Previews:** Click any calendar event to inspect stop details, scheduled activities, and jump directly to the itinerary view.

### 🌐 7. Public Itinerary Sharing & Trip Cloning (`public-itinerary.php`)
- **Secure Public Showcase:** Accessible via vanity URLs (`/share/{slug}`) or ID queries for any trip marked as `Public`.
- **Privacy Enforcement:** Sensitive financial details and custom budget notes are stripped from public viewers.
- **One-Click "Copy This Trip" Engine:** Authenticated travelers can duplicate the entire itinerary—including all stops, hotels, transport notes, and scheduled activities—directly into their own account.
- **Social Sharing Integration:** Native share buttons for WhatsApp, X (Twitter), Facebook, and clipboard link copying with instant toast feedback.

### 💬 8. Community Hub & Travel Stories (`community.php`)
- **Traveler Social Feed:** Share trip reports, food reviews, photo stories, and backpacking tips with the community.
- **Attached Itineraries:** Link public trips to community posts so fellow travelers can view and clone the exact route.
- **Interactive Engagement:** Real-time like counts, author profile cards, and filterable discussions.

### 🛡️ 9. Admin Operations & Analytics Dashboard (`admin/index.php`)
- **Role-Based Access Control:** Strict `requireAdmin()` enforcement; unauthorized visitors are redirected with access denial notices.
- **Platform KPI Metrics:** Animated count-up summary cards for Total Users, Trips Created, Activities Planned, and Community Posts with monthly growth badges.
- **Advanced Visualizations:**
  - 12-Month User Registration Trend (Curved Chart.js Area Line Chart)
  - Trip Status Distribution Donut (React 18 Glowing SVG Component)
  - 6-Month Public vs. Private Trips (Grouped Bar Chart)
- **User Management Table:** Paginated (20/page), searchable, and sortable user registry with instant modal inspection, role promotion/demotion toggles, and safe deletion cascades.
- **Destination & Activity Rankings:** Ranked tables and horizontal bar charts highlighting top-performing cities and popular activity categories.

### 👤 10. Profile & Account Settings (`profile.php`)
- **Profile Customization:** Update personal bio, contact info, home location, and upload custom avatar photos.
- **Travel Preferences:** Customize display currency (`USD`, `EUR`, `GBP`, `JPY`, `AUD`), date formatting, preferred travel style, and notification toggles.
- **Saved Wishlist Manager:** View, manage, and jump to saved dream destinations.
- **Security & Password Management:** Secure password updates with hash validation and session confirmation.

---

## 🏗️ Architecture Overview

GlobeTrotter utilizes a robust, modern MVC and REST API architecture built for speed, security, and multi-user database synchronization:

```mermaid
graph TD
    subgraph Client ["🎨 FRONTEND LAYER (Vanilla ES6 + Bootstrap 5 + React 18 + Chart.js)"]
        UI["Responsive UI / Glassmorphism Components"]
        State["Session State / Global Toasts"]
        API_Wrapper["Universal api Client / CSRF Header"]
        React_Module["React 18 Admin Donut Chart"]
        Charts["Chart.js Visualization Engines"]
    end

    subgraph Server ["🔙 BACKEND LAYER (PHP 8.2+ REST Gateway)"]
        Router["Page Controllers & Routing Layer"]
        Auth_Mid["Auth & Session Regeneration Middleware"]
        RBAC_Mid["Role-Based Access Control - requireAdmin"]
        CSRF_Mid["CSRF Token Verification - X-CSRF-Token"]
        
        subgraph API_Services ["⚙️ REST JSON API SERVICES"]
            Trips_API["api/trips.php - CRUD & Clone Engine"]
            Stops_API["api/stops.php - Stop & Activity Management"]
            Budget_API["api/budget.php - Cost Accounting"]
            Cities_API["api/cities.php - Destination Search"]
            Comm_API["api/community.php - Social & Likes"]
            Admin_API["api/admin.php - User Management"]
            Profile_API["api/profile.php - Preferences & Wishlist"]
        end
    end

    subgraph Storage ["💾 DATABASE & STORAGE LAYER"]
        PDO_Layer["PDO Prepared Statements & Transactions"]
        DB[("PostgreSQL 15 Database")]
        Cloud_DB[("Shared Cloud DB - Neon / Supabase")]
        Uploads["Local Uploads / Assets Storage"]
    end

    UI --> API_Wrapper
    UI --> State
    React_Module --> UI
    Charts --> UI
    
    API_Wrapper -->|AJAX JSON Requests| Router
    Router --> Auth_Mid
    Auth_Mid --> RBAC_Mid
    RBAC_Mid --> CSRF_Mid
    CSRF_Mid --> API_Services
    
    API_Services --> PDO_Layer
    PDO_Layer -->|Local Connection| DB
    PDO_Layer -->|Real-Time Cloud Sync| Cloud_DB
    API_Services --> Uploads
```

---

## 🚀 Quick Start (Local & Cloud Sync Setup)

### Prerequisites
- **PHP 8.1+** (PHP 8.2 or 8.5 recommended) with `pdo_pgsql` extension enabled
- **PostgreSQL 15+** running locally OR a free cloud instance (**Neon.tech**, **Supabase**, or **Render**)

---

### Option A: Local Setup (Standalone)

#### 1. Clone the Repository
```bash
git clone https://github.com/Bhavya680/GlobeTrotter.git
cd GlobeTrotter
```

#### 2. Configure Environment
Copy `.env.example` to `.env`:
```bash
cp .env.example .env
```
Ensure your local PostgreSQL credentials match in `.env`:
```env
DB_HOST=127.0.0.1
DB_PORT=5432
DB_NAME=globetrotter
DB_USER=postgres
DB_PASS=your_password
SITE_URL=http://localhost:8080
```

#### 3. Initialize Database & Seed Rich Dummy Data
Create the database and import the complete full-stack dataset (schema + 10 users + 22 cities + 47 activities + 10 trips):
```bash
createdb -U postgres globetrotter
psql -U postgres -d globetrotter -f database_full.sql
```
*(Alternative: Run `php scratch/seed_database.php` at any time to re-seed fresh data).*

#### 4. Launch the Development Server
```bash
php -S 127.0.0.1:8080 -t .
```
Visit **`http://localhost:8080`** in your browser.

---

### Option B: Real-Time Multi-User Cloud Sync (Collaborative)

Want you and your friend/collaborators to see edits, new trips, and community posts reflected on each other's screens in real-time?

1. Create a free PostgreSQL database on **[Neon.tech](https://neon.tech)** or **[Supabase](https://supabase.com)**.
2. In the cloud SQL Editor, open and run the provided **`database_full.sql`** script.
3. In your `.env` file (and your friend's `.env` file), paste the shared connection string:
   ```env
   DATABASE_URL="postgresql://username:password@ep-cool-project.us-east-2.aws.neon.tech/globetrotter?sslmode=require"
   ```
4. Start the PHP server on both computers. **All updates now synchronize live between both machines!**

---

## 🔐 Demo Credentials (All Passwords: `password123`)

| Email | Full Name | Role | Primary Access & Use Case |
| :--- | :--- | :--- | :--- |
| `admin@globetrotter.dev` | Admin User | `admin` | Full administrative access, Admin Analytics Dashboard, User Management, and global trip builder. |
| `traveler@globetrotter.dev` | Alex Traveler | `user` | Standard traveler profile, multiple pre-seeded upcoming trips, budget logs, and wishlist items. |
| `elena.rostova@wanderlust.io` | Elena Rostova | `user` | European food critic profile with completed Nordic Aurora itinerary and community guides. |
| `kenji.sato@tokyotravels.jp` | Kenji Sato | `user` | Tokyo backpacker profile with Southeast Asian budget circuit and popular ramen reviews. |

---

## 🛡️ Business Rules & Security Enforced

| Rule / Security Feature | Enforcing Layer | Technical Handling Behavior |
| :--- | :--- | :--- |
| **CSRF Protection** | `includes/header.php` / `main.js` | Generates per-session cryptographic tokens; `api()` automatically includes `X-CSRF-Token` headers for mutating requests. |
| **Admin Route Protection** | `includes/auth.php` (`requireAdmin()`) | Verifies `role = 'admin'`. Non-admins are blocked with `403` or redirected with an `"Access denied."` flash message. |
| **Session Fixation Defense** | `includes/auth.php` (`login_user()`) | Invokes `session_regenerate_id(true)` upon successful authentication. Cookies use `HttpOnly` and `SameSite=Lax`. |
| **Trip Ownership Guard** | `api/trips.php`, `api/stops.php` | Validates that mutating operations (edit/delete/update) match `user_id = current_user_id()`. Rejects with `403 Forbidden`. |
| **Private Itinerary Shield** | `public-itinerary.php` | Restricts view access on private itineraries to the owner only; hides all budget records on public views. |
| **Atomic Trip Cloning** | `api/trips.php?action=copy` | Executes a database transaction replicating the parent trip, its stops, and all scheduled activities for the new owner. |
| **Self-Demotion / Deletion Lock** | `api/admin.php` | Prevents administrators from demoting or deleting their own active account, guarding against accidental lockouts. |
| **Date Range Constraints** | PostgreSQL DB / `trips.php` | Enforces `end_date >= start_date` and `departure_date >= arrival_date` at both database and API validation levels. |
| **Error Page Redirection** | Apache `.htaccess` / `404.php` | Custom ErrorDocuments seamlessly route broken endpoints or missing resources to polished recovery screens. |

---

## 📁 Project Directory Structure

```
GlobeTrotter/
│
├── 🌐 index.php                        # Root entry router (redirects to dashboard or login)
├── 🔐 login.php, register.php, logout.php # Authentication & registration controllers
├── 📊 dashboard.php                    # User dashboard & overview metrics
├── ✈️ create-trip.php, my-trips.php    # Trip creation & user journey management
├── 🛠️ itinerary-builder.php            # Interactive day-by-day itinerary builder
├── 🗺️ itinerary-view.php               # Detailed structured summary of itinerary
├── 💰 budget-view.php                  # Financial tracker with category expense ledger
├── 🔍 city-search.php, activity-search.php # Global city & activity explorer
├── 📅 calendar-view.php                # Visual interactive trip timeline calendar
├── 💬 community.php                    # Social feed & traveler community posts
├── 👤 profile.php                      # User settings, preferences, and saved destinations
├── 🌐 public-itinerary.php             # Sharable public trip view with 1-click clone engine
├── 🚫 404.php, error.php               # Custom branded error & recovery pages
├── ⚙️ config.php                       # Application configuration & auto .env loader
├── 🔐 .env.example                     # Environment template (local & cloud database URL)
├── 🗄️ database.sql                     # Core PostgreSQL schema definition
├── 🗄️ database_full.sql                # Complete standalone dump (Schema + 100% Seed Data)
│
├── 🛡️ admin/                           # Administrative Module
│   └── 📊 index.php                    # Admin analytics, user manager, and rank charts
│
├── ⚙️ api/                             # RESTful JSON API Endpoints
│   ├── ✈️ trips.php                     # Trip CRUD, status calculation, and copy engine
│   ├── 📍 stops.php                     # Multi-city stop management & activity links
│   ├── 🎯 activities.php                # Activity catalog, filtering, and duration queries
│   ├── 🌆 cities.php                    # Destination search and popularity rankings
│   ├── 💰 budget.php                    # Estimated vs. actual expense calculations
│   ├── 💬 community.php                 # Community posts, trip attachments, and like toggles
│   ├── 👤 profile.php                   # User profile updates, preferences, and wishlists
│   └── 🛡️ admin.php                     # User role management, stats, and delete cascades
│
├── 🧩 includes/                        # Core Shared Backend Modules
│   ├── 🗄️ db.php                       # PDO connection engine with Cloud DATABASE_URL & SSL
│   ├── 🔐 auth.php                      # Authentication, session security, and RBAC helpers
│   ├── 🛠️ functions.php                 # Flash notices, CSRF token generators, image uploads
│   ├── 🧭 header.php                    # Global HTML head, SVG favicon, and dual navbar
│   └── 🦶 footer.php                    # Global JS scripts, Bootstrap bundle, and closing tags
│
├── 🎨 assets/                          # Static Frontend Assets
│   ├── 🎨 css/
│   │   ├── style.css                   # Global styles, variables, skeletons, empty states
│   │   ├── dashboard.css               # Navbar, metric cards, and layout styling
│   │   ├── trips.css                   # Trip creation, modal forms, and card styles
│   │   └── admin.css                   # Admin sidebar, dark mode accents, and table styles
│   │
│   ├── 📜 js/
│   │   ├── main.js                     # Global API client, CSRF injection, toast stack
│   │   ├── trips.js                    # Trip lifecycle handlers & filter listeners
│   │   ├── itinerary.js                # Dynamic itinerary stop & activity management
│   │   ├── budget.js                   # Expense calculation & ledger handlers
│   │   ├── budget-chart.js             # Chart.js donut and expense bar engines
│   │   ├── community.js                # Post creation, like toggles, and share modals
│   │   ├── admin.js                    # Admin table search, sorting, and Chart.js trends
│   │   └── admin-pie.js                # React 18 glowing SVG donut chart component
│   │
│   └── 🖼️ uploads/                     # User-generated media storage (profiles, covers)
│
└── 🧪 scratch/                         # Database Migration & Seeding Utilities
    └── 🌱 seed_database.php            # Comprehensive multi-table database seeder
```

---

<div align="center">

**Built with ❤️ for passionate global travelers.**

*Plan seamlessly. Travel fearlessly. Explore endlessly.*

</div>
