# UI/UX Design Guidelines - Globe Trotter

## 1. Design System & Theming
The application employs a custom CSS theme layered over Bootstrap 5 [cite: 2]. The color palette is defined via CSS variables to ensure consistency:
* **Primary**: `--gt-primary: #2563EB` (Vibrant Blue for CTAs, highlights, active states) [cite: 2].
* **Accent**: `--gt-accent: #F59E0B` (Warm Amber for alerts, warnings, and special UI elements) [cite: 2].
* **Dark (Text/Headings)**: `--gt-dark: #1E293B` (Deep Slate) [cite: 2].
* **Light (Backgrounds/Cards)**: `--gt-light: #F8FAFC` (Off-white/Soft Gray) [cite: 2].

Typography should be clean and readable, leveraging modern sans-serif web fonts. Elements use subtle border radii (`16px` for main cards) and modern box shadows (`0 8px 32px rgba(0,0,0,0.12)`) to provide depth [cite: 2].

## 2. Common UI Components
* **Cards**: Heavy use of card-based layouts for Trips, Cities, and Activities. Cards often feature an image/gradient placeholder, bold titles, and badging [cite: 2].
* **Status Badges**: Bootstrap badges indicate trip states (`success` for completed, `warning` for ongoing, `info` for upcoming) [cite: 2].
* **Icons**: FontAwesome (`fa-globe`, `fa-suitcase`, `fa-map-marker-alt`) is used extensively for visual anchors [cite: 2].
* **Loaders & Skeletons**: Every AJAX call displays a loading state. Buttons disable and show a Bootstrap `spinner-border sm`. Content areas render CSS animated grey skeleton boxes matching the shape of incoming data [cite: 2].
* **Toast Notifications**: Built via Bootstrap Toasts in `main.js`, appearing in the top-right corner, auto-dismissing after 3 seconds for success, error, info, or warning messages [cite: 2].
* **Empty States**: If a list or grid is empty, a styled state appears with a large muted FontAwesome icon, a "Nothing here yet" heading, specific subtext, and a relevant CTA button (e.g., "Plan Your First Trip") [cite: 2].

## 3. Screen-by-Screen Layout Breakdowns

### 3.1 Authentication (Screens 1 & 2)
* **Layout**: Centered white card (`border-radius: 16px`) on a full-screen travel-themed animated gradient or image [cite: 2].
* **Features**: Circular avatar placeholders, input fields with integrated FontAwesome icons, real-time validation via JS, and password strength indicators (weak/medium/strong) [cite: 2].

### 3.2 Dashboard (Screen 3)
* **Hero**: Full-width banner image with CSS gradient overlay and a personalized greeting [cite: 2].
* **Stats Bar**: Three mini cards (Total Trips, Countries Visited, Upcoming trips) with large numbers and icons [cite: 2].
* **Regions**: Horizontal scrollable row of region cards (Asia, Europe, etc.) featuring background images and city counts [cite: 2].
* **My Trips Preview**: Up to 4 horizontal cards of recent trips, alongside a sticky Floating Action Button (FAB) for planning a new trip [cite: 2].

### 3.3 Trip Creation & My Trips (Screens 4 & 6)
* **Creation**: Clean form card with autocomplete search for cities, allowing multiple stops to be added dynamically [cite: 2]. Suggestion grids pop up for activities based on city selection [cite: 2].
* **My Trips List**: Three-section layout (Ongoing, Upcoming, Completed) filtering trip cards. Includes interactive deletion with Bootstrap modals and dynamic sorting without page reloads [cite: 2].

### 3.4 Itinerary Builder & View (Screens 5 & 9)
* **Builder**: Left-panel trip overview sidebar and main content section representing stops [cite: 2]. Stop cards feature a drag handle (`fa-grip-vertical`) for HTML5/SortableJS reordering, activity rows with remove buttons, and debounced auto-saving notes [cite: 2].
* **View**: A two-column day-by-day table (Physical Activity vs Expense) or timeline. A sticky sidebar/bottom bar tracks "Total Spent So Far" against the budget [cite: 2]. 
* **Charts**: The budget tab features a Chart.js bar chart (Budgeted vs Actual) and a React-powered Glassmorphism Donut Chart showing category breakdown [cite: 2].

### 3.5 Search & Discovery (Screen 8)
* **Tabbed Interface**: Clean toggle between "City Search" and "Activity Search" [cite: 2].
* **Filters**: Comprehensive side/top filters for Region, Cost Index, Category, Duration, and custom sorting [cite: 2].
* **Results**: Populated via AJAX. City cards display images and "Add to Current Trip" dropdowns; Activity cards trigger "Quick View" modals [cite: 2].

### 3.6 Custom Calendar View (Screen 11)
* **Grid Layout**: Built from scratch using CSS Grid and vanilla JS (no FullCalendar library). Highlights today's date and features multi-day trips as continuous colored bars across cells [cite: 2].
* **Interactivity**: Clicking bars opens a "Trip Quick View" modal; clicking empty dates prompts trip creation [cite: 2]. Also supports a Week View plotting specific hourly activities [cite: 2].

### 3.7 Community & Admin (Screens 10 & 12)
* **Community**: Feed of POST CARDs displaying avatars, bold titles, truncated text, tag pills, and like/comment interaction. Likes are toggled instantly with optimistic UI updates [cite: 2].
* **Admin Dashboard**: Specialized navbar. Features 4 tabs: User Management, Popular Cities, Popular Activities, and Analytics. Employs Chart.js (Line/Bar charts) and a React pie chart for tracking user trends and platform adoption [cite: 2].
