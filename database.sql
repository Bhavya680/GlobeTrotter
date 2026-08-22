-- PostgreSQL Schema for GlobeTrotter

CREATE OR REPLACE FUNCTION update_updated_at_column()
RETURNS TRIGGER AS $$
BEGIN
    NEW.updated_at = CURRENT_TIMESTAMP;
    RETURN NEW;
END;
$$ language 'plpgsql';

CREATE TABLE IF NOT EXISTS users (
    id SERIAL PRIMARY KEY,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    email VARCHAR(255) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    phone VARCHAR(20),
    city VARCHAR(100),
    country VARCHAR(100),
    profile_photo VARCHAR(255),
    additional_info TEXT,
    role VARCHAR(20) DEFAULT 'user' CHECK (role IN ('user', 'admin')),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

DROP TRIGGER IF EXISTS update_users_updated_at ON users;
CREATE TRIGGER update_users_updated_at
BEFORE UPDATE ON users
FOR EACH ROW
EXECUTE FUNCTION update_updated_at_column();

CREATE TABLE IF NOT EXISTS trips (
    id SERIAL PRIMARY KEY,
    user_id INT,
    trip_name VARCHAR(255) NOT NULL,
    description TEXT,
    start_date DATE,
    end_date DATE,
    cover_photo VARCHAR(255),
    status VARCHAR(20) DEFAULT 'upcoming' CHECK (status IN ('upcoming', 'ongoing', 'completed')),
    visibility VARCHAR(20) DEFAULT 'private' CHECK (visibility IN ('public', 'private')),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS cities (
    id SERIAL PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    country VARCHAR(100),
    region VARCHAR(100),
    cost_index DECIMAL(5,2),
    popularity_score INT DEFAULT 0,
    description TEXT,
    image_url VARCHAR(255)
);

CREATE TABLE IF NOT EXISTS activities (
    id SERIAL PRIMARY KEY,
    city_id INT,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    category VARCHAR(50) CHECK (category IN ('sightseeing', 'food', 'adventure', 'culture', 'shopping', 'wellness')),
    cost DECIMAL(10,2) DEFAULT 0.00,
    duration_hours DECIMAL(4,1),
    image_url VARCHAR(255),
    FOREIGN KEY (city_id) REFERENCES cities(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS trip_stops (
    id SERIAL PRIMARY KEY,
    trip_id INT,
    city_id INT,
    arrival_date DATE,
    departure_date DATE,
    order_index INT DEFAULT 0,
    notes TEXT,
    budget_for_stop DECIMAL(10,2) DEFAULT 0.00,
    FOREIGN KEY (trip_id) REFERENCES trips(id) ON DELETE CASCADE,
    FOREIGN KEY (city_id) REFERENCES cities(id)
);

CREATE TABLE IF NOT EXISTS trip_activities (
    id SERIAL PRIMARY KEY,
    trip_stop_id INT,
    activity_id INT,
    scheduled_date DATE,
    scheduled_time TIME,
    custom_cost DECIMAL(10,2),
    notes TEXT,
    FOREIGN KEY (trip_stop_id) REFERENCES trip_stops(id) ON DELETE CASCADE,
    FOREIGN KEY (activity_id) REFERENCES activities(id)
);

CREATE TABLE IF NOT EXISTS trip_budget (
    id SERIAL PRIMARY KEY,
    trip_id INT UNIQUE,
    transport_budget DECIMAL(10,2) DEFAULT 0.00,
    stay_budget DECIMAL(10,2) DEFAULT 0.00,
    activities_budget DECIMAL(10,2) DEFAULT 0.00,
    meals_budget DECIMAL(10,2) DEFAULT 0.00,
    misc_budget DECIMAL(10,2) DEFAULT 0.00,
    total_budget DECIMAL(10,2) GENERATED ALWAYS AS (transport_budget + stay_budget + activities_budget + meals_budget + misc_budget) STORED,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (trip_id) REFERENCES trips(id) ON DELETE CASCADE
);

DROP TRIGGER IF EXISTS update_trip_budget_updated_at ON trip_budget;
CREATE TRIGGER update_trip_budget_updated_at
BEFORE UPDATE ON trip_budget
FOR EACH ROW
EXECUTE FUNCTION update_updated_at_column();

CREATE TABLE IF NOT EXISTS community_posts (
    id SERIAL PRIMARY KEY,
    user_id INT,
    trip_id INT NULL,
    title VARCHAR(255),
    content TEXT NOT NULL,
    likes_count INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (trip_id) REFERENCES trips(id) ON DELETE SET NULL
);

CREATE TABLE IF NOT EXISTS community_likes (
    id SERIAL PRIMARY KEY,
    post_id INT,
    user_id INT,
    UNIQUE (post_id, user_id),
    FOREIGN KEY (post_id) REFERENCES community_posts(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS saved_destinations (
    id SERIAL PRIMARY KEY,
    user_id INT,
    city_id INT,
    saved_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE (user_id, city_id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (city_id) REFERENCES cities(id) ON DELETE CASCADE
);

-- Seed Data

-- 1. Users
-- Hash is for 'Password123'
INSERT INTO users (first_name, last_name, email, password_hash, role) VALUES 
('Admin', 'User', 'admin@globetrotter.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin'),
('Test', 'User1', 'user1@test.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'user'),
('Test', 'User2', 'user2@test.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'user');

-- 2. Cities
INSERT INTO cities (name, country, region, cost_index) VALUES
('Paris', 'France', 'Europe', 8.5),
('Tokyo', 'Japan', 'Asia', 7.2),
('New York', 'USA', 'Americas', 9.0),
('Bali', 'Indonesia', 'Asia', 4.5),
('Rome', 'Italy', 'Europe', 7.8),
('Bangkok', 'Thailand', 'Asia', 3.5),
('Dubai', 'UAE', 'Middle East', 8.0),
('Cape Town', 'South Africa', 'Africa', 5.0);

-- 3. Activities
INSERT INTO activities (city_id, name, category, cost, duration_hours) VALUES
(1, 'Eiffel Tower Visit', 'sightseeing', 30.00, 3.0),
(1, 'Louvre Museum', 'culture', 20.00, 4.0),
(1, 'Seine River Cruise', 'sightseeing', 15.00, 1.5),
(2, 'Sushi Making Class', 'food', 80.00, 2.5),
(2, 'Mount Fuji Day Trip', 'adventure', 100.00, 10.0),
(2, 'Akihabara Shopping', 'shopping', 0.00, 3.0),
(3, 'Statue of Liberty', 'sightseeing', 25.00, 4.0),
(3, 'Broadway Show', 'culture', 150.00, 3.0),
(3, 'Central Park Bike Tour', 'adventure', 35.00, 2.0),
(4, 'Ubud Monkey Forest', 'sightseeing', 5.00, 2.0),
(4, 'Balinese Massage', 'wellness', 20.00, 1.5),
(4, 'Mount Batur Sunrise Trek', 'adventure', 45.00, 6.0),
(5, 'Colosseum Guided Tour', 'sightseeing', 35.00, 2.5),
(5, 'Vatican Museums', 'culture', 40.00, 4.0),
(5, 'Pasta Making Class', 'food', 60.00, 3.0),
(6, 'Grand Palace Tour', 'culture', 15.00, 3.0),
(6, 'Chatuchak Weekend Market', 'shopping', 0.00, 4.0),
(6, 'Thai Street Food Tour', 'food', 30.00, 3.5),
(7, 'Burj Khalifa Observation Deck', 'sightseeing', 45.00, 2.0),
(7, 'Desert Safari', 'adventure', 70.00, 6.0),
(7, 'Dubai Mall Shopping', 'shopping', 0.00, 5.0),
(8, 'Table Mountain Cable Car', 'sightseeing', 25.00, 3.0),
(8, 'Cape Point Tour', 'adventure', 50.00, 8.0),
(8, 'Wine Tasting in Stellenbosch', 'food', 40.00, 5.0);

-- 4. Sample Trips for user1 (Upcoming & Completed)
INSERT INTO trips (user_id, trip_name, start_date, end_date, status, visibility) VALUES
(2, 'Summer in Europe', '2027-06-01', '2027-06-15', 'upcoming', 'private'),
(2, 'Bali Retreat', '2023-09-10', '2023-09-20', 'completed', 'public');

-- 5. Trip Stops
INSERT INTO trip_stops (trip_id, city_id, arrival_date, departure_date, order_index) VALUES
(1, 1, '2027-06-01', '2027-06-07', 0),
(1, 5, '2027-06-07', '2027-06-15', 1),
(2, 4, '2023-09-10', '2023-09-20', 0);

-- 6. Trip Activities
INSERT INTO trip_activities (trip_stop_id, activity_id, scheduled_date) VALUES
(1, 1, '2027-06-02'),
(1, 2, '2027-06-03'),
(2, 13, '2027-06-08'),
(3, 11, '2023-09-12');
