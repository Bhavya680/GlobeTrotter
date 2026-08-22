# Product Requirements Document (PRD) - Globe Trotter

## 1. Executive Summary & Vision
The overarching vision for Globe Trotter is to become a personalized, intelligent, and collaborative platform that transforms the way individuals plan and experience travel [cite: 1]. The platform aims to empower users to dream, design, and organize trips with ease by offering an end-to-end travel planning tool [cite: 1]. It combines flexibility and interactivity, allowing users to explore global destinations, visualize journeys through structured itineraries, make cost-effective decisions, and share travel plans within a community [cite: 1]. 

## 2. Mission Statement
The mission is to build a user-centric, responsive application that simplifies the complexity of planning multi-city travel [cite: 1]. The platform provides travelers with intuitive tools to:
* Add and manage travel stops and durations [cite: 1].
* Explore cities and activities of interest [cite: 1].
* Estimate trip budgets automatically [cite: 1].
* Visualize timelines and plans [cite: 1].
* Share trip plans with others [cite: 1].

## 3. Problem Statement
The application must solve the problem of fragmented travel planning by allowing users to create customized multi-city itineraries, assign travel dates, activities, and budgets, discover activities, and receive cost breakdowns and visual calendars [cite: 1]. The application must demonstrate proper use of relational databases to store and retrieve complex travel data (user-specific itineraries, stops, activities, estimated expenses) and support dynamic user interfaces that adapt to the user's trip flow [cite: 1].

## 4. Key Features & Functional Requirements
The application will include the following comprehensive set of features, ensuring a rich and user-friendly experience across desktop or mobile platforms [cite: 1]:

### 4.1 Authentication & User Management
* **Login/Signup Screen**: Entry point allowing users to create or access their account [cite: 1]. It authenticates users to manage personal travel plans [cite: 1]. Components include Email & password fields, Login button, Signup link, "Forgot Password", and basic validation [cite: 1]. The registration process captures First Name, Last Name, Email, Phone, City, Country, Additional info, and an optional Profile Photo [cite: 2].
* **User Profile / Settings Screen**: User settings page to update profile information and preferences (enabling data control and privacy) [cite: 1]. Fields include name, photo, email, language preference, delete account, and a list of saved destinations [cite: 1]. It also displays pre-planned trips, previous trips, and an activity summary with metrics [cite: 2].

### 4.2 Core Navigation & Dashboard
* **Dashboard/Home Screen**: Central hub showing upcoming trips, popular cities, and quick actions, allowing users to navigate to their trips and explore inspiration [cite: 1]. Key components are a welcome message, list of recent trips, "Plan New Trip" button, recommended destinations (Explore by Region), and budget highlights/stats [cite: 1, 2].

### 4.3 Trip & Itinerary Management
* **Create Trip Screen**: Form to initiate a new trip by providing a name, travel dates, and description [cite: 1]. Users can optionally upload a cover photo and define visibility (Public/Private) [cite: 1, 2]. Users can dynamically add multiple stops with autocomplete city search [cite: 2].
* **My Trips (Trip List) Screen**: List view of all trips created by the user with basic summary data (name, date range, destination count, edit/view/delete actions) [cite: 1]. Trips are categorized by Ongoing, Upcoming, and Completed status [cite: 2].
* **Itinerary Builder Screen**: Interface to construct the full day-wise trip plan interactively [cite: 1]. Users can add cities, dates, and activities for each stop, and reorder cities [cite: 1]. This acts as the engine for travel planning with a drag-and-drop feature and deep integration with activity discovery [cite: 2].
* **Itinerary View Screen**: Visual representation of the completed trip itinerary in a structured format (day-wise layout, city headers, activity blocks with time and cost) [cite: 1]. Includes a view mode toggle (calendar/list) [cite: 1].
* **Trip Calendar / Timeline Screen**: A calendar-based or vertical timeline view of the full itinerary to help users visualize the journey [cite: 1]. Features a calendar component, expandable day views, drag-to-reorder activities, and quick editing [cite: 1]. The custom calendar highlights multi-day trips as continuous colored bars [cite: 2].

### 4.4 Search & Discovery
* **City Search**: Search interface to find and add cities to a trip [cite: 1]. Displays meta info like country, cost index (1-10 scale), and popularity [cite: 1, 2]. Includes a search bar, "Add to Trip" button, and filters by country/region [cite: 1].
* **Activity Search**: Browse and select things to do in each stop, categorized by interest (sightseeing, food tours, etc.) or cost [cite: 1]. Includes activity filters (type, cost, duration), add/remove buttons, and a quick view of descriptions and images [cite: 1].

### 4.5 Financial Tools
* **Trip Budget & Cost Breakdown Screen**: Summarized financial view showing estimated total cost and breakdowns by transport, stay, activities, and meals [cite: 1]. Includes pie/bar charts, average cost per day, and alerts for overbudget days [cite: 1]. Specifically, actual vs budgeted expenses are compared using warning alerts and visual charts [cite: 2].

### 4.6 Social & Community
* **Shared/Public Itinerary View Screen**: Public page displaying a sharable, read-only version of an itinerary [cite: 1]. Features a public URL, itinerary summary, "Copy Trip" button, and social media sharing [cite: 1, 2].
* **Community Tab**: A dedicated section where users share their experience about a certain trip or activity [cite: 2]. Supports searching, grouping, filtering, and sorting to narrow down results [cite: 2]. Users can like, comment, and link posts to specific public trips [cite: 2].

### 4.7 Administration
* **Admin/Analytics Dashboard (Optional)**: Admin-only interface to track user trends, trip data, and platform usage [cite: 1]. Displays tables and charts of trips created, top cities/activities, user engagement stats, and user management tools [cite: 1]. It features rich metrics including line charts for user registrations and pie charts for trip statuses [cite: 2].

## 5. Non-Functional Requirements
* Responsive design adapting to each user's trip flow [cite: 1].
* Robust relational database management for complex travel data [cite: 1].
* Real-time client-side validations complementing server-side checks [cite: 2].
* Strong security posture (CSRF, PDO prepared statements, password hashing) [cite: 2].
