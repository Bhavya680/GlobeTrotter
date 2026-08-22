<?php
/**
 * GlobeTrotter - Universal Front Controller & Application Router
 */
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/auth.php';

$rawUri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$path = trim(urldecode($rawUri), '/');

// ── 1. Root Route Handler ──────────────────────────────────────────────────
if ($path === '' || $path === 'index.php') {
    if (is_logged_in()) {
        require __DIR__ . '/pages/dashboard.php';
    } else {
        require __DIR__ . '/pages/auth/login.php';
    }
    exit;
}

// ── 2. Route Definition Mapping (Supports both clean URLs and legacy .php) ─
$routes = [
    // Dashboard
    'dashboard'             => 'pages/dashboard.php',
    'dashboard.php'         => 'pages/dashboard.php',

    // Authentication
    'login'                 => 'pages/auth/login.php',
    'login.php'             => 'pages/auth/login.php',
    'register'              => 'pages/auth/register.php',
    'register.php'          => 'pages/auth/register.php',
    'logout'                => 'pages/auth/logout.php',
    'logout.php'            => 'pages/auth/logout.php',

    // Trips & Planning
    'trips'                 => 'pages/trips/my-trips.php',
    'my-trips'              => 'pages/trips/my-trips.php',
    'my-trips.php'          => 'pages/trips/my-trips.php',
    'trips/create'          => 'pages/trips/create-trip.php',
    'create-trip'           => 'pages/trips/create-trip.php',
    'create-trip.php'       => 'pages/trips/create-trip.php',
    'trips/builder'         => 'pages/trips/itinerary-builder.php',
    'itinerary-builder'     => 'pages/trips/itinerary-builder.php',
    'itinerary-builder.php' => 'pages/trips/itinerary-builder.php',
    'trips/view'            => 'pages/trips/itinerary-view.php',
    'itinerary-view'        => 'pages/trips/itinerary-view.php',
    'itinerary-view.php'    => 'pages/trips/itinerary-view.php',
    'trips/budget'          => 'pages/trips/budget-view.php',
    'budget-view'           => 'pages/trips/budget-view.php',
    'budget-view.php'       => 'pages/trips/budget-view.php',
    'trips/calendar'        => 'pages/trips/calendar-view.php',
    'calendar-view'         => 'pages/trips/calendar-view.php',
    'calendar-view.php'     => 'pages/trips/calendar-view.php',
    'trips/public'          => 'pages/trips/public-itinerary.php',
    'public-itinerary'      => 'pages/trips/public-itinerary.php',
    'public-itinerary.php'  => 'pages/trips/public-itinerary.php',

    // Discovery & Search
    'explore'               => 'pages/discovery/city-search.php',
    'cities'                => 'pages/discovery/city-search.php',
    'city-search'           => 'pages/discovery/city-search.php',
    'city-search.php'       => 'pages/discovery/city-search.php',
    'activities'            => 'pages/discovery/activity-search.php',
    'activity-search'       => 'pages/discovery/activity-search.php',
    'activity-search.php'   => 'pages/discovery/activity-search.php',

    // Community & Social
    'community'             => 'pages/community/community.php',
    'community.php'         => 'pages/community/community.php',

    // User Account
    'profile'               => 'pages/account/profile.php',
    'profile.php'           => 'pages/account/profile.php',

    // Error Pages
    '404'                   => 'pages/errors/404.php',
    '404.php'               => 'pages/errors/404.php',
    'error'                 => 'pages/errors/error.php',
    'error.php'             => 'pages/errors/error.php',
];

// ── 3. Public Share Slug Alias (/share/{slug}) ─────────────────────────────
if (preg_match('#^share/([a-zA-Z0-9_-]+)$#', $path, $matches)) {
    $_GET['slug'] = $matches[1];
    require __DIR__ . '/pages/trips/public-itinerary.php';
    exit;
}

// ── 4. Direct Match Dispatcher ─────────────────────────────────────────────
if (isset($routes[$path])) {
    $targetFile = __DIR__ . '/' . $routes[$path];
    if (file_exists($targetFile)) {
        require $targetFile;
        exit;
    }
}

// ── 5. Static or Existing File Passthrough ─────────────────────────────────
if (file_exists(__DIR__ . '/' . $path) && !is_dir(__DIR__ . '/' . $path)) {
    return false;
}

// ── 6. 404 Fallback ────────────────────────────────────────────────────────
http_response_code(404);
require __DIR__ . '/pages/errors/404.php';
exit;
