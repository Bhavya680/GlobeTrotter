CREATE EXTENSION IF NOT EXISTS pgcrypto;

CREATE OR REPLACE FUNCTION set_updated_at()
RETURNS TRIGGER AS $$
BEGIN
    NEW.updated_at = NOW();
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

-- ── 1. Users ─────────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS users (
    id              SERIAL PRIMARY KEY,
    first_name      VARCHAR(60) NOT NULL,
    last_name       VARCHAR(60) NOT NULL,
    email           VARCHAR(190) NOT NULL UNIQUE,
    password_hash   VARCHAR(255) NOT NULL,
    phone           VARCHAR(30),
    city            VARCHAR(100),
    country         VARCHAR(100),
    profile_photo   VARCHAR(255),
    additional_info TEXT,
    language_pref   VARCHAR(10) NOT NULL DEFAULT 'en',
    role            VARCHAR(20) NOT NULL DEFAULT 'user' CHECK (role IN ('user', 'admin')),
    created_at      TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    updated_at      TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

DROP TRIGGER IF EXISTS trg_users_updated_at ON users;
CREATE TRIGGER trg_users_updated_at
    BEFORE UPDATE ON users
    FOR EACH ROW EXECUTE FUNCTION set_updated_at();

CREATE INDEX IF NOT EXISTS idx_users_email ON users (email);

-- ── 2. Cities ────────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS cities (
    id                SERIAL PRIMARY KEY,
    name              VARCHAR(120) NOT NULL,
    country           VARCHAR(120) NOT NULL,
    region            VARCHAR(120),
    cost_index        NUMERIC(6,2) NOT NULL DEFAULT 0,
    popularity_score  INT NOT NULL DEFAULT 0,
    description       TEXT,
    image_url         VARCHAR(255),
    created_at        TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

CREATE INDEX IF NOT EXISTS idx_cities_name ON cities (name);
CREATE INDEX IF NOT EXISTS idx_cities_country ON cities (country);
CREATE INDEX IF NOT EXISTS idx_cities_popularity ON cities (popularity_score DESC);

-- ── 3. Activities ────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS activities (
    id              SERIAL PRIMARY KEY,
    city_id         INT NOT NULL REFERENCES cities(id) ON DELETE CASCADE,
    name            VARCHAR(160) NOT NULL,
    description     TEXT,
    category        VARCHAR(20) NOT NULL DEFAULT 'other'
                        CHECK (category IN ('sightseeing','food','adventure','culture','relaxation','other')),
    cost            NUMERIC(10,2) NOT NULL DEFAULT 0,
    duration_hours  NUMERIC(4,1) NOT NULL DEFAULT 1,
    image_url       VARCHAR(255),
    created_at      TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

CREATE INDEX IF NOT EXISTS idx_activities_city ON activities (city_id);
CREATE INDEX IF NOT EXISTS idx_activities_category ON activities (category);
CREATE INDEX IF NOT EXISTS idx_activities_cost ON activities (cost);

-- ── 4. Trips ─────────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS trips (
    id              SERIAL PRIMARY KEY,
    user_id         INT NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    trip_name       VARCHAR(160) NOT NULL,
    description     TEXT,
    start_date      DATE NOT NULL,
    end_date        DATE NOT NULL,
    cover_photo     VARCHAR(255),
    status          VARCHAR(20) NOT NULL DEFAULT 'upcoming' CHECK (status IN ('upcoming','ongoing','completed')),
    visibility      VARCHAR(20) NOT NULL DEFAULT 'private' CHECK (visibility IN ('public','private')),
    share_slug      VARCHAR(40) UNIQUE,
    created_at      TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    updated_at      TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    CONSTRAINT chk_trip_dates CHECK (end_date >= start_date)
);

DROP TRIGGER IF EXISTS trg_trips_updated_at ON trips;
CREATE TRIGGER trg_trips_updated_at
    BEFORE UPDATE ON trips
    FOR EACH ROW EXECUTE FUNCTION set_updated_at();

CREATE INDEX IF NOT EXISTS idx_trips_user ON trips (user_id);
CREATE INDEX IF NOT EXISTS idx_trips_share_slug ON trips (share_slug);

-- ── 5. Trip Stops ────────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS trip_stops (
    id                 SERIAL PRIMARY KEY,
    trip_id            INT NOT NULL REFERENCES trips(id) ON DELETE CASCADE,
    city_id            INT NOT NULL REFERENCES cities(id) ON DELETE RESTRICT,
    arrival_date       DATE,
    departure_date     DATE,
    order_index        INT NOT NULL DEFAULT 0,
    transport_note     TEXT,
    accommodation      VARCHAR(255),
    accommodation_cost NUMERIC(10,2) DEFAULT 0,
    budget_for_stop    NUMERIC(10,2) DEFAULT 0,
    notes              TEXT,
    created_at         TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    updated_at         TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    CONSTRAINT chk_stop_dates CHECK (departure_date >= arrival_date)
);

DROP TRIGGER IF EXISTS trg_trip_stops_updated_at ON trip_stops;
CREATE TRIGGER trg_trip_stops_updated_at
    BEFORE UPDATE ON trip_stops
    FOR EACH ROW EXECUTE FUNCTION set_updated_at();

CREATE INDEX IF NOT EXISTS idx_trip_stops_trip ON trip_stops (trip_id, order_index);
CREATE INDEX IF NOT EXISTS idx_trip_stops_city ON trip_stops (city_id);

-- ── 6. Trip Activities ───────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS trip_activities (
    id              SERIAL PRIMARY KEY,
    trip_stop_id    INT NOT NULL REFERENCES trip_stops(id) ON DELETE CASCADE,
    activity_id     INT NOT NULL REFERENCES activities(id) ON DELETE RESTRICT,
    scheduled_date  DATE NOT NULL,
    scheduled_time  TIME,
    custom_cost     NUMERIC(10,2),
    notes           TEXT,
    created_at      TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

CREATE INDEX IF NOT EXISTS idx_trip_activities_stop ON trip_activities (trip_stop_id, scheduled_date);
CREATE INDEX IF NOT EXISTS idx_trip_activities_activity ON trip_activities (activity_id);

-- ── 7. Trip Budget ───────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS trip_budget (
    id                SERIAL PRIMARY KEY,
    trip_id           INT NOT NULL UNIQUE REFERENCES trips(id) ON DELETE CASCADE,
    transport_budget  NUMERIC(10,2) NOT NULL DEFAULT 0,
    stay_budget       NUMERIC(10,2) NOT NULL DEFAULT 0,
    activities_budget NUMERIC(10,2) NOT NULL DEFAULT 0,
    meals_budget      NUMERIC(10,2) NOT NULL DEFAULT 0,
    misc_budget       NUMERIC(10,2) NOT NULL DEFAULT 0,
    created_at        TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

-- ── 8. Budget Items ──────────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS budget_items (
    id              SERIAL PRIMARY KEY,
    trip_id         INT NOT NULL REFERENCES trips(id) ON DELETE CASCADE,
    stop_id         INT REFERENCES trip_stops(id) ON DELETE CASCADE,
    category        VARCHAR(20) NOT NULL
                        CHECK (category IN ('transport','stay','meals','other')),
    description     VARCHAR(200),
    amount          NUMERIC(10,2) NOT NULL DEFAULT 0,
    spent_on        DATE,
    created_at      TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

CREATE INDEX IF NOT EXISTS idx_budget_items_trip ON budget_items (trip_id);

-- ── 9. Community Posts & Likes ───────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS community_posts (
    id              SERIAL PRIMARY KEY,
    user_id         INT NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    trip_id         INT REFERENCES trips(id) ON DELETE SET NULL,
    title           VARCHAR(200) NOT NULL,
    content         TEXT NOT NULL,
    likes_count     INT NOT NULL DEFAULT 0,
    created_at      TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    updated_at      TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

DROP TRIGGER IF EXISTS trg_community_posts_updated_at ON community_posts;
CREATE TRIGGER trg_community_posts_updated_at
    BEFORE UPDATE ON community_posts
    FOR EACH ROW EXECUTE FUNCTION set_updated_at();

CREATE TABLE IF NOT EXISTS community_likes (
    id              SERIAL PRIMARY KEY,
    post_id         INT NOT NULL REFERENCES community_posts(id) ON DELETE CASCADE,
    user_id         INT NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    created_at      TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    CONSTRAINT unique_post_user_like UNIQUE (post_id, user_id)
);

-- ── 10. Saved Destinations ───────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS saved_destinations (
    id              SERIAL PRIMARY KEY,
    user_id         INT NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    city_id         INT NOT NULL REFERENCES cities(id) ON DELETE CASCADE,
    saved_at        TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    CONSTRAINT unique_user_saved_city UNIQUE (user_id, city_id)
);

-- ── Seed Data ────────────────────────────────────────────────────────────────
INSERT INTO cities (name, country, region, cost_index, popularity_score, image_url) VALUES
('Paris',       'France',       'Europe',        78.50, 98, 'https://images.unsplash.com/photo-1502602898657-3e91760cbb34?auto=format&fit=crop&w=800&q=80'),
('Tokyo',       'Japan',        'Asia',          72.00, 95, 'https://images.unsplash.com/photo-1540959733332-eab4deabeeaf?auto=format&fit=crop&w=800&q=80'),
('Bali',        'Indonesia',    'Asia',          35.00, 90, 'https://images.unsplash.com/photo-1537996194471-e657df975ab4?auto=format&fit=crop&w=800&q=80'),
('New York',    'USA',          'North America', 95.00, 92, 'https://images.unsplash.com/photo-1496442226666-8d4d0e62e6e9?auto=format&fit=crop&w=800&q=80'),
('Barcelona',   'Spain',        'Europe',        60.00, 88, 'https://images.unsplash.com/photo-1539037116277-4db20889f2d4?auto=format&fit=crop&w=800&q=80'),
('Bangkok',     'Thailand',     'Asia',          28.00, 85, 'https://images.unsplash.com/photo-1508009603885-50cf7c579365?auto=format&fit=crop&w=800&q=80'),
('Rome',        'Italy',        'Europe',        70.00, 89, 'https://images.unsplash.com/photo-1552832230-c0197dd311b5?auto=format&fit=crop&w=800&q=80'),
('Cape Town',   'South Africa', 'Africa',        45.00, 75, 'https://images.unsplash.com/photo-1580618672591-eb180b1a973f?auto=format&fit=crop&w=800&q=80')
ON CONFLICT DO NOTHING;

INSERT INTO activities (city_id, name, description, category, cost, duration_hours, image_url) VALUES
(1, 'Louvre Museum Tour',        'Guided tour of the Louvre highlights.',        'culture',      35.00, 3, NULL),
(1, 'Seine River Cruise',        'Evening cruise along the Seine.',              'relaxation',    25.00, 1.5, NULL),
(1, 'Eiffel Tower Summit Visit', 'Skip-the-line access to the top level.',       'sightseeing',   45.00, 2, NULL),
(2, 'Shibuya Food Crawl',        'Street food tasting tour through Shibuya.',    'food',          40.00, 3, NULL),
(2, 'TeamLab Digital Art',       'Immersive digital art museum.',                'culture',       32.00, 2, NULL),
(3, 'Ubud Rice Terrace Trek',    'Guided walk through the Tegallalang terraces.', 'adventure',     20.00, 4, NULL),
(3, 'Balinese Cooking Class',    'Hands-on class with a local chef.',            'food',          30.00, 3, NULL),
(4, 'Broadway Show',             'Evening ticket to a Broadway production.',     'culture',       120.00, 2.5, NULL),
(4, 'Central Park Bike Tour',    'Guided cycling tour through Central Park.',    'sightseeing',   28.00, 2, NULL)
ON CONFLICT DO NOTHING;

-- Seed Demo Users (Admin: Admin@123 | Traveler: Traveler@123)
INSERT INTO users (first_name, last_name, email, password_hash, role) VALUES
('Admin', 'User', 'admin@globetrotter.dev', '$2y$10$gdNM3dX3//fjJJ6S/DtMxO6Ff5LsgEuFZ7PxMP6JHOgQWnzjsEsiC', 'admin'),
('Alex', 'Traveler', 'traveler@globetrotter.dev', '$2y$12$knJTQlDmFJehC6F.6scI7OByDrxUYgjOjiL65dx6Pyvr8siKLc4rq', 'user')
ON CONFLICT (email) DO NOTHING;
