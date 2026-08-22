<?php
require_once __DIR__ . '/includes/db.php';

echo "Running GlobeTrotter Database Schema Migration...\n";

try {
    // 1. Users table fixes
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS users (
            id SERIAL PRIMARY KEY,
            first_name VARCHAR(60) NOT NULL DEFAULT '',
            last_name VARCHAR(60) NOT NULL DEFAULT '',
            email VARCHAR(190) NOT NULL UNIQUE,
            password_hash VARCHAR(255) NOT NULL,
            phone VARCHAR(30),
            city VARCHAR(100),
            country VARCHAR(100),
            profile_photo VARCHAR(255),
            additional_info TEXT,
            language_pref VARCHAR(10) NOT NULL DEFAULT 'en',
            role VARCHAR(20) NOT NULL DEFAULT 'user',
            created_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
            updated_at TIMESTAMPTZ NOT NULL DEFAULT NOW()
        );
        ALTER TABLE users ADD COLUMN IF NOT EXISTS first_name VARCHAR(60) NOT NULL DEFAULT '';
        ALTER TABLE users ADD COLUMN IF NOT EXISTS last_name VARCHAR(60) NOT NULL DEFAULT '';
        ALTER TABLE users ADD COLUMN IF NOT EXISTS phone VARCHAR(30);
        ALTER TABLE users ADD COLUMN IF NOT EXISTS city VARCHAR(100);
        ALTER TABLE users ADD COLUMN IF NOT EXISTS country VARCHAR(100);
        ALTER TABLE users ADD COLUMN IF NOT EXISTS additional_info TEXT;
        ALTER TABLE users ADD COLUMN IF NOT EXISTS role VARCHAR(20) NOT NULL DEFAULT 'user';
    ");

    // Copy name to first_name/last_name if name column exists
    $cols = $pdo->query("SELECT column_name FROM information_schema.columns WHERE table_name='users'")->fetchAll(PDO::FETCH_COLUMN);
    if (in_array('name', $cols)) {
        $pdo->exec("UPDATE users SET first_name = split_part(name, ' ', 1), last_name = COALESCE(NULLIF(substr(name, length(split_part(name, ' ', 1)) + 2), ''), '') WHERE first_name = '' OR first_name IS NULL;");
    }

    // 2. Cities table fixes
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS cities (
            id SERIAL PRIMARY KEY,
            name VARCHAR(120) NOT NULL,
            country VARCHAR(120) NOT NULL,
            region VARCHAR(120),
            cost_index NUMERIC(6,2) NOT NULL DEFAULT 0,
            popularity_score INT NOT NULL DEFAULT 0,
            description TEXT,
            image_url VARCHAR(255),
            created_at TIMESTAMPTZ NOT NULL DEFAULT NOW()
        );
        ALTER TABLE cities ADD COLUMN IF NOT EXISTS popularity_score INT NOT NULL DEFAULT 0;
    ");
    $cityCols = $pdo->query("SELECT column_name FROM information_schema.columns WHERE table_name='cities'")->fetchAll(PDO::FETCH_COLUMN);
    if (in_array('popularity', $cityCols)) {
        $pdo->exec("UPDATE cities SET popularity_score = popularity WHERE popularity_score = 0;");
    }

    // 3. Trips table fixes
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS trips (
            id SERIAL PRIMARY KEY,
            user_id INT NOT NULL REFERENCES users(id) ON DELETE CASCADE,
            trip_name VARCHAR(160) NOT NULL DEFAULT 'My Trip',
            description TEXT,
            start_date DATE NOT NULL DEFAULT CURRENT_DATE,
            end_date DATE NOT NULL DEFAULT CURRENT_DATE,
            cover_photo VARCHAR(255),
            status VARCHAR(20) NOT NULL DEFAULT 'upcoming',
            visibility VARCHAR(20) NOT NULL DEFAULT 'private',
            share_slug VARCHAR(40) UNIQUE,
            created_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
            updated_at TIMESTAMPTZ NOT NULL DEFAULT NOW()
        );
        ALTER TABLE trips ADD COLUMN IF NOT EXISTS trip_name VARCHAR(160);
        ALTER TABLE trips ADD COLUMN IF NOT EXISTS status VARCHAR(20) NOT NULL DEFAULT 'upcoming';
        ALTER TABLE trips ADD COLUMN IF NOT EXISTS visibility VARCHAR(20) NOT NULL DEFAULT 'private';
        ALTER TABLE trips ADD COLUMN IF NOT EXISTS share_slug VARCHAR(40);
    ");
    $tripCols = $pdo->query("SELECT column_name FROM information_schema.columns WHERE table_name='trips'")->fetchAll(PDO::FETCH_COLUMN);
    if (in_array('name', $tripCols)) {
        $pdo->exec("UPDATE trips SET trip_name = name WHERE trip_name IS NULL OR trip_name = '';");
    }

    // 4. Stops / Trip Stops
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS trip_stops (
            id SERIAL PRIMARY KEY,
            trip_id INT NOT NULL REFERENCES trips(id) ON DELETE CASCADE,
            city_id INT NOT NULL REFERENCES cities(id) ON DELETE RESTRICT,
            arrival_date DATE NOT NULL,
            departure_date DATE NOT NULL,
            order_index INT NOT NULL DEFAULT 0,
            transport_note TEXT,
            accommodation VARCHAR(255),
            accommodation_cost NUMERIC(10,2) DEFAULT 0,
            budget_for_stop NUMERIC(10,2) DEFAULT 0,
            notes TEXT,
            created_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
            updated_at TIMESTAMPTZ NOT NULL DEFAULT NOW()
        );
    ");
    // If old 'stops' table exists, migrate rows to 'trip_stops' if needed
    $tables = $pdo->query("SELECT table_name FROM information_schema.tables WHERE table_schema='public'")->fetchAll(PDO::FETCH_COLUMN);
    if (in_array('stops', $tables) && !in_array('trip_stops', $tables)) {
        $pdo->exec("ALTER TABLE stops RENAME TO trip_stops;");
        $pdo->exec("ALTER TABLE trip_stops RENAME COLUMN start_date TO arrival_date;");
        $pdo->exec("ALTER TABLE trip_stops RENAME COLUMN end_date TO departure_date;");
        $pdo->exec("ALTER TABLE trip_stops RENAME COLUMN sort_order TO order_index;");
    }

    // 5. Activities / Trip Activities
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS trip_activities (
            id SERIAL PRIMARY KEY,
            trip_stop_id INT NOT NULL REFERENCES trip_stops(id) ON DELETE CASCADE,
            activity_id INT NOT NULL REFERENCES activities(id) ON DELETE RESTRICT,
            scheduled_date DATE NOT NULL,
            scheduled_time TIME,
            custom_cost NUMERIC(10,2),
            notes TEXT,
            created_at TIMESTAMPTZ NOT NULL DEFAULT NOW()
        );
    ");
    if (in_array('stop_activities', $tables) && !in_array('trip_activities', $tables)) {
        $pdo->exec("ALTER TABLE stop_activities RENAME TO trip_activities;");
        $pdo->exec("ALTER TABLE trip_activities RENAME COLUMN stop_id TO trip_stop_id;");
        $pdo->exec("ALTER TABLE trip_activities RENAME COLUMN cost_override TO custom_cost;");
    }

    // 6. Auxiliary tables
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS trip_budget (
            id SERIAL PRIMARY KEY,
            trip_id INT NOT NULL UNIQUE REFERENCES trips(id) ON DELETE CASCADE,
            transport_budget NUMERIC(10,2) NOT NULL DEFAULT 0,
            stay_budget NUMERIC(10,2) NOT NULL DEFAULT 0,
            activities_budget NUMERIC(10,2) NOT NULL DEFAULT 0,
            meals_budget NUMERIC(10,2) NOT NULL DEFAULT 0,
            misc_budget NUMERIC(10,2) NOT NULL DEFAULT 0,
            created_at TIMESTAMPTZ NOT NULL DEFAULT NOW()
        );

        CREATE TABLE IF NOT EXISTS budget_items (
            id SERIAL PRIMARY KEY,
            trip_id INT NOT NULL REFERENCES trips(id) ON DELETE CASCADE,
            stop_id INT REFERENCES trip_stops(id) ON DELETE CASCADE,
            category VARCHAR(20) NOT NULL,
            description VARCHAR(200),
            amount NUMERIC(10,2) NOT NULL DEFAULT 0,
            spent_on DATE,
            created_at TIMESTAMPTZ NOT NULL DEFAULT NOW()
        );

        CREATE TABLE IF NOT EXISTS community_posts (
            id SERIAL PRIMARY KEY,
            user_id INT NOT NULL REFERENCES users(id) ON DELETE CASCADE,
            trip_id INT REFERENCES trips(id) ON DELETE SET NULL,
            title VARCHAR(200) NOT NULL,
            content TEXT NOT NULL,
            likes_count INT NOT NULL DEFAULT 0,
            created_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
            updated_at TIMESTAMPTZ NOT NULL DEFAULT NOW()
        );

        CREATE TABLE IF NOT EXISTS community_likes (
            id SERIAL PRIMARY KEY,
            post_id INT NOT NULL REFERENCES community_posts(id) ON DELETE CASCADE,
            user_id INT NOT NULL REFERENCES users(id) ON DELETE CASCADE,
            created_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
            CONSTRAINT unique_post_user_like UNIQUE (post_id, user_id)
        );

        CREATE TABLE IF NOT EXISTS saved_destinations (
            id SERIAL PRIMARY KEY,
            user_id INT NOT NULL REFERENCES users(id) ON DELETE CASCADE,
            city_id INT NOT NULL REFERENCES cities(id) ON DELETE CASCADE,
            saved_at TIMESTAMPTZ NOT NULL DEFAULT NOW(),
            CONSTRAINT unique_user_saved_city UNIQUE (user_id, city_id)
        );
    ");

    echo "Migration completed successfully!\n";

} catch (Exception $e) {
    echo "Migration Note/Warning: " . $e->getMessage() . "\n";
}
