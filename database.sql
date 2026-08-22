CREATE EXTENSION IF NOT EXISTS pgcrypto;

CREATE OR REPLACE FUNCTION set_updated_at()
RETURNS TRIGGER AS $$
BEGIN
    NEW.updated_at = NOW();
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

CREATE TABLE users (
    id              SERIAL PRIMARY KEY,
    name            VARCHAR(120) NOT NULL,
    email           VARCHAR(190) NOT NULL UNIQUE,
    password_hash   VARCHAR(255) NOT NULL,
    profile_photo   VARCHAR(255),
    language_pref   VARCHAR(10) NOT NULL DEFAULT 'en',
    is_admin        BOOLEAN NOT NULL DEFAULT FALSE,
    created_at      TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    updated_at      TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

CREATE TRIGGER trg_users_updated_at
    BEFORE UPDATE ON users
    FOR EACH ROW EXECUTE FUNCTION set_updated_at();

CREATE INDEX idx_users_email ON users (email);

CREATE TABLE cities (
    id              SERIAL PRIMARY KEY,
    name            VARCHAR(120) NOT NULL,
    country         VARCHAR(120) NOT NULL,
    region          VARCHAR(120),
    cost_index      NUMERIC(6,2) NOT NULL DEFAULT 0,
    popularity      INT NOT NULL DEFAULT 0,
    image_url       VARCHAR(255),
    created_at      TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

CREATE INDEX idx_cities_name ON cities (name);
CREATE INDEX idx_cities_country ON cities (country);
CREATE INDEX idx_cities_popularity ON cities (popularity DESC);

CREATE TABLE activities (
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

CREATE INDEX idx_activities_city ON activities (city_id);
CREATE INDEX idx_activities_category ON activities (category);
CREATE INDEX idx_activities_cost ON activities (cost);

CREATE TABLE trips (
    id              SERIAL PRIMARY KEY,
    user_id         INT NOT NULL REFERENCES users(id) ON DELETE CASCADE,
    name            VARCHAR(160) NOT NULL,
    start_date      DATE NOT NULL,
    end_date        DATE NOT NULL,
    description     TEXT,
    cover_photo     VARCHAR(255),
    is_public       BOOLEAN NOT NULL DEFAULT FALSE,
    share_slug      VARCHAR(40) UNIQUE,
    created_at      TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    updated_at      TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    CONSTRAINT chk_trip_dates CHECK (end_date >= start_date)
);

CREATE TRIGGER trg_trips_updated_at
    BEFORE UPDATE ON trips
    FOR EACH ROW EXECUTE FUNCTION set_updated_at();

CREATE INDEX idx_trips_user ON trips (user_id);
CREATE INDEX idx_trips_share_slug ON trips (share_slug);

CREATE TABLE stops (
    id              SERIAL PRIMARY KEY,
    trip_id         INT NOT NULL REFERENCES trips(id) ON DELETE CASCADE,
    city_id         INT NOT NULL REFERENCES cities(id) ON DELETE RESTRICT,
    start_date      DATE NOT NULL,
    end_date        DATE NOT NULL,
    sort_order      INT NOT NULL DEFAULT 0,
    created_at      TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    updated_at      TIMESTAMPTZ NOT NULL DEFAULT NOW(),
    CONSTRAINT chk_stop_dates CHECK (end_date >= start_date)
);

CREATE TRIGGER trg_stops_updated_at
    BEFORE UPDATE ON stops
    FOR EACH ROW EXECUTE FUNCTION set_updated_at();

CREATE INDEX idx_stops_trip ON stops (trip_id, sort_order);
CREATE INDEX idx_stops_city ON stops (city_id);

CREATE TABLE stop_activities (
    id              SERIAL PRIMARY KEY,
    stop_id         INT NOT NULL REFERENCES stops(id) ON DELETE CASCADE,
    activity_id     INT NOT NULL REFERENCES activities(id) ON DELETE RESTRICT,
    scheduled_date  DATE NOT NULL,
    scheduled_time  TIME,
    cost_override   NUMERIC(10,2),
    notes           TEXT,
    created_at      TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

CREATE INDEX idx_stop_activities_stop ON stop_activities (stop_id, scheduled_date);
CREATE INDEX idx_stop_activities_activity ON stop_activities (activity_id);

CREATE TABLE budget_items (
    id              SERIAL PRIMARY KEY,
    trip_id         INT NOT NULL REFERENCES trips(id) ON DELETE CASCADE,
    stop_id         INT REFERENCES stops(id) ON DELETE CASCADE,
    category        VARCHAR(20) NOT NULL
                        CHECK (category IN ('transport','stay','meals','other')),
    description     VARCHAR(200),
    amount          NUMERIC(10,2) NOT NULL DEFAULT 0,
    spent_on        DATE,
    created_at      TIMESTAMPTZ NOT NULL DEFAULT NOW()
);

CREATE INDEX idx_budget_items_trip ON budget_items (trip_id);
CREATE INDEX idx_budget_items_stop ON budget_items (stop_id);

INSERT INTO cities (name, country, region, cost_index, popularity, image_url) VALUES
('Paris',       'France',       'Europe',        78.50, 98, NULL),
('Tokyo',       'Japan',        'Asia',          72.00, 95, NULL),
('Bali',        'Indonesia',    'Asia',          35.00, 90, NULL),
('New York',    'USA',          'North America', 95.00, 92, NULL),
('Barcelona',   'Spain',        'Europe',        60.00, 88, NULL),
('Bangkok',     'Thailand',     'Asia',          28.00, 85, NULL),
('Rome',        'Italy',        'Europe',        70.00, 89, NULL),
('Cape Town',   'South Africa', 'Africa',        45.00, 75, NULL);

INSERT INTO activities (city_id, name, description, category, cost, duration_hours, image_url) VALUES
(1, 'Louvre Museum Tour',        'Guided tour of the Louvre highlights.',        'culture',      35.00, 3, NULL),
(1, 'Seine River Cruise',        'Evening cruise along the Seine.',              'relaxation',    25.00, 1.5, NULL),
(1, 'Eiffel Tower Summit Visit', 'Skip-the-line access to the top level.',       'sightseeing',   45.00, 2, NULL),
(2, 'Shibuya Food Crawl',        'Street food tasting tour through Shibuya.',    'food',          40.00, 3, NULL),
(2, 'TeamLab Digital Art',       'Immersive digital art museum.',                'culture',       32.00, 2, NULL),
(3, 'Ubud Rice Terrace Trek',    'Guided walk through the Tegallalang terraces.', 'adventure',     20.00, 4, NULL),
(3, 'Balinese Cooking Class',    'Hands-on class with a local chef.',            'food',          30.00, 3, NULL),
(4, 'Broadway Show',             'Evening ticket to a Broadway production.',     'culture',       120.00, 2.5, NULL),
(4, 'Central Park Bike Tour',     'Guided cycling tour through Central Park.',    'sightseeing',   28.00, 2, NULL);

-- password: Admin@123
INSERT INTO users (name, email, password_hash, is_admin) VALUES
('Admin User', 'admin@globetrotter.dev', '$2y$10$gdNM3dX3//fjJJ6S/DtMxO6Ff5LsgEuFZ7PxMP6JHOgQWnzjsEsiC', TRUE);

-- ── Itinerary Builder: extra columns on stops ──────────────────────────────
ALTER TABLE stops
    ADD COLUMN IF NOT EXISTS transport_note      TEXT,
    ADD COLUMN IF NOT EXISTS accommodation       VARCHAR(255),
    ADD COLUMN IF NOT EXISTS accommodation_cost  NUMERIC(10,2),
    ADD COLUMN IF NOT EXISTS stop_notes          TEXT;

