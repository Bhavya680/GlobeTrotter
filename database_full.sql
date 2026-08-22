--
-- PostgreSQL database dump
--

\restrict ubRfDAGifdPQtfVaEweGxE1ddFd6tejjP2RLyGmTJsD5sQtLeNAAGGk6C0G88CL

-- Dumped from database version 15.17 (Homebrew)
-- Dumped by pg_dump version 15.17 (Homebrew)

SET statement_timeout = 0;
SET lock_timeout = 0;
SET idle_in_transaction_session_timeout = 0;
SET client_encoding = 'UTF8';
SET standard_conforming_strings = on;
SELECT pg_catalog.set_config('search_path', '', false);
SET check_function_bodies = false;
SET xmloption = content;
SET client_min_messages = warning;
SET row_security = off;

ALTER TABLE IF EXISTS ONLY public.trips DROP CONSTRAINT IF EXISTS trips_user_id_fkey;
ALTER TABLE IF EXISTS ONLY public.trip_stops DROP CONSTRAINT IF EXISTS trip_stops_trip_id_fkey;
ALTER TABLE IF EXISTS ONLY public.trip_stops DROP CONSTRAINT IF EXISTS trip_stops_city_id_fkey;
ALTER TABLE IF EXISTS ONLY public.trip_budget DROP CONSTRAINT IF EXISTS trip_budget_trip_id_fkey;
ALTER TABLE IF EXISTS ONLY public.trip_activities DROP CONSTRAINT IF EXISTS trip_activities_trip_stop_id_fkey;
ALTER TABLE IF EXISTS ONLY public.trip_activities DROP CONSTRAINT IF EXISTS trip_activities_activity_id_fkey;
ALTER TABLE IF EXISTS ONLY public.saved_destinations DROP CONSTRAINT IF EXISTS saved_destinations_user_id_fkey;
ALTER TABLE IF EXISTS ONLY public.saved_destinations DROP CONSTRAINT IF EXISTS saved_destinations_city_id_fkey;
ALTER TABLE IF EXISTS ONLY public.community_posts DROP CONSTRAINT IF EXISTS community_posts_user_id_fkey;
ALTER TABLE IF EXISTS ONLY public.community_posts DROP CONSTRAINT IF EXISTS community_posts_trip_id_fkey;
ALTER TABLE IF EXISTS ONLY public.community_likes DROP CONSTRAINT IF EXISTS community_likes_user_id_fkey;
ALTER TABLE IF EXISTS ONLY public.community_likes DROP CONSTRAINT IF EXISTS community_likes_post_id_fkey;
ALTER TABLE IF EXISTS ONLY public.budget_items DROP CONSTRAINT IF EXISTS budget_items_trip_id_fkey;
ALTER TABLE IF EXISTS ONLY public.budget_items DROP CONSTRAINT IF EXISTS budget_items_stop_id_fkey;
ALTER TABLE IF EXISTS ONLY public.activities DROP CONSTRAINT IF EXISTS activities_city_id_fkey;
DROP TRIGGER IF EXISTS trg_users_updated_at ON public.users;
DROP TRIGGER IF EXISTS trg_trips_updated_at ON public.trips;
DROP TRIGGER IF EXISTS trg_trip_stops_updated_at ON public.trip_stops;
DROP TRIGGER IF EXISTS trg_community_posts_updated_at ON public.community_posts;
DROP INDEX IF EXISTS public.idx_users_email;
DROP INDEX IF EXISTS public.idx_trips_user;
DROP INDEX IF EXISTS public.idx_trips_share_slug;
DROP INDEX IF EXISTS public.idx_trip_stops_trip;
DROP INDEX IF EXISTS public.idx_trip_stops_city;
DROP INDEX IF EXISTS public.idx_trip_activities_stop;
DROP INDEX IF EXISTS public.idx_trip_activities_activity;
DROP INDEX IF EXISTS public.idx_cities_popularity;
DROP INDEX IF EXISTS public.idx_cities_name;
DROP INDEX IF EXISTS public.idx_cities_country;
DROP INDEX IF EXISTS public.idx_budget_items_trip;
DROP INDEX IF EXISTS public.idx_activities_cost;
DROP INDEX IF EXISTS public.idx_activities_city;
DROP INDEX IF EXISTS public.idx_activities_category;
ALTER TABLE IF EXISTS ONLY public.users DROP CONSTRAINT IF EXISTS users_pkey;
ALTER TABLE IF EXISTS ONLY public.users DROP CONSTRAINT IF EXISTS users_email_key;
ALTER TABLE IF EXISTS ONLY public.saved_destinations DROP CONSTRAINT IF EXISTS unique_user_saved_city;
ALTER TABLE IF EXISTS ONLY public.community_likes DROP CONSTRAINT IF EXISTS unique_post_user_like;
ALTER TABLE IF EXISTS ONLY public.trips DROP CONSTRAINT IF EXISTS trips_share_slug_key;
ALTER TABLE IF EXISTS ONLY public.trips DROP CONSTRAINT IF EXISTS trips_pkey;
ALTER TABLE IF EXISTS ONLY public.trip_stops DROP CONSTRAINT IF EXISTS trip_stops_pkey;
ALTER TABLE IF EXISTS ONLY public.trip_budget DROP CONSTRAINT IF EXISTS trip_budget_trip_id_key;
ALTER TABLE IF EXISTS ONLY public.trip_budget DROP CONSTRAINT IF EXISTS trip_budget_pkey;
ALTER TABLE IF EXISTS ONLY public.trip_activities DROP CONSTRAINT IF EXISTS trip_activities_pkey;
ALTER TABLE IF EXISTS ONLY public.saved_destinations DROP CONSTRAINT IF EXISTS saved_destinations_pkey;
ALTER TABLE IF EXISTS ONLY public.community_posts DROP CONSTRAINT IF EXISTS community_posts_pkey;
ALTER TABLE IF EXISTS ONLY public.community_likes DROP CONSTRAINT IF EXISTS community_likes_pkey;
ALTER TABLE IF EXISTS ONLY public.cities DROP CONSTRAINT IF EXISTS cities_pkey;
ALTER TABLE IF EXISTS ONLY public.budget_items DROP CONSTRAINT IF EXISTS budget_items_pkey;
ALTER TABLE IF EXISTS ONLY public.activities DROP CONSTRAINT IF EXISTS activities_pkey;
ALTER TABLE IF EXISTS public.users ALTER COLUMN id DROP DEFAULT;
ALTER TABLE IF EXISTS public.trips ALTER COLUMN id DROP DEFAULT;
ALTER TABLE IF EXISTS public.trip_stops ALTER COLUMN id DROP DEFAULT;
ALTER TABLE IF EXISTS public.trip_budget ALTER COLUMN id DROP DEFAULT;
ALTER TABLE IF EXISTS public.trip_activities ALTER COLUMN id DROP DEFAULT;
ALTER TABLE IF EXISTS public.saved_destinations ALTER COLUMN id DROP DEFAULT;
ALTER TABLE IF EXISTS public.community_posts ALTER COLUMN id DROP DEFAULT;
ALTER TABLE IF EXISTS public.community_likes ALTER COLUMN id DROP DEFAULT;
ALTER TABLE IF EXISTS public.cities ALTER COLUMN id DROP DEFAULT;
ALTER TABLE IF EXISTS public.budget_items ALTER COLUMN id DROP DEFAULT;
ALTER TABLE IF EXISTS public.activities ALTER COLUMN id DROP DEFAULT;
DROP SEQUENCE IF EXISTS public.users_id_seq;
DROP TABLE IF EXISTS public.users;
DROP SEQUENCE IF EXISTS public.trips_id_seq;
DROP TABLE IF EXISTS public.trips;
DROP SEQUENCE IF EXISTS public.trip_stops_id_seq;
DROP TABLE IF EXISTS public.trip_stops;
DROP SEQUENCE IF EXISTS public.trip_budget_id_seq;
DROP TABLE IF EXISTS public.trip_budget;
DROP SEQUENCE IF EXISTS public.trip_activities_id_seq;
DROP TABLE IF EXISTS public.trip_activities;
DROP SEQUENCE IF EXISTS public.saved_destinations_id_seq;
DROP TABLE IF EXISTS public.saved_destinations;
DROP SEQUENCE IF EXISTS public.community_posts_id_seq;
DROP TABLE IF EXISTS public.community_posts;
DROP SEQUENCE IF EXISTS public.community_likes_id_seq;
DROP TABLE IF EXISTS public.community_likes;
DROP SEQUENCE IF EXISTS public.cities_id_seq;
DROP TABLE IF EXISTS public.cities;
DROP SEQUENCE IF EXISTS public.budget_items_id_seq;
DROP TABLE IF EXISTS public.budget_items;
DROP SEQUENCE IF EXISTS public.activities_id_seq;
DROP TABLE IF EXISTS public.activities;
DROP FUNCTION IF EXISTS public.set_updated_at();
DROP EXTENSION IF EXISTS pgcrypto;
--
-- Name: pgcrypto; Type: EXTENSION; Schema: -; Owner: -
--

CREATE EXTENSION IF NOT EXISTS pgcrypto WITH SCHEMA public;


--
-- Name: EXTENSION pgcrypto; Type: COMMENT; Schema: -; Owner: 
--

COMMENT ON EXTENSION pgcrypto IS 'cryptographic functions';


--
-- Name: set_updated_at(); Type: FUNCTION; Schema: public; Owner: snehapatel
--

CREATE FUNCTION public.set_updated_at() RETURNS trigger
    LANGUAGE plpgsql
    AS $$
BEGIN
    NEW.updated_at = NOW();
    RETURN NEW;
END;
$$;


ALTER FUNCTION public.set_updated_at() OWNER TO snehapatel;

SET default_tablespace = '';

SET default_table_access_method = heap;

--
-- Name: activities; Type: TABLE; Schema: public; Owner: snehapatel
--

CREATE TABLE public.activities (
    id integer NOT NULL,
    city_id integer NOT NULL,
    name character varying(160) NOT NULL,
    description text,
    category character varying(20) DEFAULT 'other'::character varying NOT NULL,
    cost numeric(10,2) DEFAULT 0 NOT NULL,
    duration_hours numeric(4,1) DEFAULT 1 NOT NULL,
    image_url character varying(255),
    created_at timestamp with time zone DEFAULT now() NOT NULL,
    CONSTRAINT activities_category_check CHECK (((category)::text = ANY ((ARRAY['sightseeing'::character varying, 'food'::character varying, 'adventure'::character varying, 'culture'::character varying, 'relaxation'::character varying, 'other'::character varying])::text[])))
);


ALTER TABLE public.activities OWNER TO snehapatel;

--
-- Name: activities_id_seq; Type: SEQUENCE; Schema: public; Owner: snehapatel
--

CREATE SEQUENCE public.activities_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.activities_id_seq OWNER TO snehapatel;

--
-- Name: activities_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: snehapatel
--

ALTER SEQUENCE public.activities_id_seq OWNED BY public.activities.id;


--
-- Name: budget_items; Type: TABLE; Schema: public; Owner: snehapatel
--

CREATE TABLE public.budget_items (
    id integer NOT NULL,
    trip_id integer NOT NULL,
    stop_id integer,
    category character varying(20) NOT NULL,
    description character varying(200),
    amount numeric(10,2) DEFAULT 0 NOT NULL,
    spent_on date,
    created_at timestamp with time zone DEFAULT now() NOT NULL,
    CONSTRAINT budget_items_category_check CHECK (((category)::text = ANY ((ARRAY['transport'::character varying, 'stay'::character varying, 'meals'::character varying, 'activities'::character varying, 'shopping'::character varying, 'other'::character varying])::text[])))
);


ALTER TABLE public.budget_items OWNER TO snehapatel;

--
-- Name: budget_items_id_seq; Type: SEQUENCE; Schema: public; Owner: snehapatel
--

CREATE SEQUENCE public.budget_items_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.budget_items_id_seq OWNER TO snehapatel;

--
-- Name: budget_items_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: snehapatel
--

ALTER SEQUENCE public.budget_items_id_seq OWNED BY public.budget_items.id;


--
-- Name: cities; Type: TABLE; Schema: public; Owner: snehapatel
--

CREATE TABLE public.cities (
    id integer NOT NULL,
    name character varying(120) NOT NULL,
    country character varying(120) NOT NULL,
    region character varying(120),
    cost_index numeric(6,2) DEFAULT 0 NOT NULL,
    popularity_score integer DEFAULT 0 NOT NULL,
    description text,
    image_url character varying(255),
    created_at timestamp with time zone DEFAULT now() NOT NULL
);


ALTER TABLE public.cities OWNER TO snehapatel;

--
-- Name: cities_id_seq; Type: SEQUENCE; Schema: public; Owner: snehapatel
--

CREATE SEQUENCE public.cities_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.cities_id_seq OWNER TO snehapatel;

--
-- Name: cities_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: snehapatel
--

ALTER SEQUENCE public.cities_id_seq OWNED BY public.cities.id;


--
-- Name: community_likes; Type: TABLE; Schema: public; Owner: snehapatel
--

CREATE TABLE public.community_likes (
    id integer NOT NULL,
    post_id integer NOT NULL,
    user_id integer NOT NULL,
    created_at timestamp with time zone DEFAULT now() NOT NULL
);


ALTER TABLE public.community_likes OWNER TO snehapatel;

--
-- Name: community_likes_id_seq; Type: SEQUENCE; Schema: public; Owner: snehapatel
--

CREATE SEQUENCE public.community_likes_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.community_likes_id_seq OWNER TO snehapatel;

--
-- Name: community_likes_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: snehapatel
--

ALTER SEQUENCE public.community_likes_id_seq OWNED BY public.community_likes.id;


--
-- Name: community_posts; Type: TABLE; Schema: public; Owner: snehapatel
--

CREATE TABLE public.community_posts (
    id integer NOT NULL,
    user_id integer NOT NULL,
    trip_id integer,
    title character varying(200) NOT NULL,
    content text NOT NULL,
    likes_count integer DEFAULT 0 NOT NULL,
    created_at timestamp with time zone DEFAULT now() NOT NULL,
    updated_at timestamp with time zone DEFAULT now() NOT NULL
);


ALTER TABLE public.community_posts OWNER TO snehapatel;

--
-- Name: community_posts_id_seq; Type: SEQUENCE; Schema: public; Owner: snehapatel
--

CREATE SEQUENCE public.community_posts_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.community_posts_id_seq OWNER TO snehapatel;

--
-- Name: community_posts_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: snehapatel
--

ALTER SEQUENCE public.community_posts_id_seq OWNED BY public.community_posts.id;


--
-- Name: saved_destinations; Type: TABLE; Schema: public; Owner: snehapatel
--

CREATE TABLE public.saved_destinations (
    id integer NOT NULL,
    user_id integer NOT NULL,
    city_id integer NOT NULL,
    saved_at timestamp with time zone DEFAULT now() NOT NULL
);


ALTER TABLE public.saved_destinations OWNER TO snehapatel;

--
-- Name: saved_destinations_id_seq; Type: SEQUENCE; Schema: public; Owner: snehapatel
--

CREATE SEQUENCE public.saved_destinations_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.saved_destinations_id_seq OWNER TO snehapatel;

--
-- Name: saved_destinations_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: snehapatel
--

ALTER SEQUENCE public.saved_destinations_id_seq OWNED BY public.saved_destinations.id;


--
-- Name: trip_activities; Type: TABLE; Schema: public; Owner: snehapatel
--

CREATE TABLE public.trip_activities (
    id integer NOT NULL,
    trip_stop_id integer NOT NULL,
    activity_id integer NOT NULL,
    scheduled_date date NOT NULL,
    scheduled_time time without time zone,
    custom_cost numeric(10,2),
    notes text,
    created_at timestamp with time zone DEFAULT now() NOT NULL
);


ALTER TABLE public.trip_activities OWNER TO snehapatel;

--
-- Name: trip_activities_id_seq; Type: SEQUENCE; Schema: public; Owner: snehapatel
--

CREATE SEQUENCE public.trip_activities_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.trip_activities_id_seq OWNER TO snehapatel;

--
-- Name: trip_activities_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: snehapatel
--

ALTER SEQUENCE public.trip_activities_id_seq OWNED BY public.trip_activities.id;


--
-- Name: trip_budget; Type: TABLE; Schema: public; Owner: snehapatel
--

CREATE TABLE public.trip_budget (
    id integer NOT NULL,
    trip_id integer NOT NULL,
    transport_budget numeric(10,2) DEFAULT 0 NOT NULL,
    stay_budget numeric(10,2) DEFAULT 0 NOT NULL,
    activities_budget numeric(10,2) DEFAULT 0 NOT NULL,
    meals_budget numeric(10,2) DEFAULT 0 NOT NULL,
    misc_budget numeric(10,2) DEFAULT 0 NOT NULL,
    created_at timestamp with time zone DEFAULT now() NOT NULL,
    updated_at timestamp with time zone DEFAULT now() NOT NULL
);


ALTER TABLE public.trip_budget OWNER TO snehapatel;

--
-- Name: trip_budget_id_seq; Type: SEQUENCE; Schema: public; Owner: snehapatel
--

CREATE SEQUENCE public.trip_budget_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.trip_budget_id_seq OWNER TO snehapatel;

--
-- Name: trip_budget_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: snehapatel
--

ALTER SEQUENCE public.trip_budget_id_seq OWNED BY public.trip_budget.id;


--
-- Name: trip_stops; Type: TABLE; Schema: public; Owner: snehapatel
--

CREATE TABLE public.trip_stops (
    id integer NOT NULL,
    trip_id integer NOT NULL,
    city_id integer NOT NULL,
    arrival_date date,
    departure_date date,
    order_index integer DEFAULT 0 NOT NULL,
    transport_note text,
    accommodation character varying(255),
    accommodation_cost numeric(10,2) DEFAULT 0,
    budget_for_stop numeric(10,2) DEFAULT 0,
    notes text,
    created_at timestamp with time zone DEFAULT now() NOT NULL,
    updated_at timestamp with time zone DEFAULT now() NOT NULL,
    transport_type character varying DEFAULT 'flight'::character varying,
    transport_cost numeric DEFAULT 0,
    CONSTRAINT chk_stop_dates CHECK ((departure_date >= arrival_date))
);


ALTER TABLE public.trip_stops OWNER TO snehapatel;

--
-- Name: trip_stops_id_seq; Type: SEQUENCE; Schema: public; Owner: snehapatel
--

CREATE SEQUENCE public.trip_stops_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.trip_stops_id_seq OWNER TO snehapatel;

--
-- Name: trip_stops_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: snehapatel
--

ALTER SEQUENCE public.trip_stops_id_seq OWNED BY public.trip_stops.id;


--
-- Name: trips; Type: TABLE; Schema: public; Owner: snehapatel
--

CREATE TABLE public.trips (
    id integer NOT NULL,
    user_id integer NOT NULL,
    trip_name character varying(160) NOT NULL,
    description text,
    start_date date NOT NULL,
    end_date date NOT NULL,
    cover_photo character varying(255),
    status character varying(20) DEFAULT 'upcoming'::character varying NOT NULL,
    visibility character varying(20) DEFAULT 'private'::character varying NOT NULL,
    share_slug character varying(40),
    created_at timestamp with time zone DEFAULT now() NOT NULL,
    updated_at timestamp with time zone DEFAULT now() NOT NULL,
    CONSTRAINT chk_trip_dates CHECK ((end_date >= start_date)),
    CONSTRAINT trips_status_check CHECK (((status)::text = ANY ((ARRAY['upcoming'::character varying, 'ongoing'::character varying, 'completed'::character varying])::text[]))),
    CONSTRAINT trips_visibility_check CHECK (((visibility)::text = ANY ((ARRAY['public'::character varying, 'private'::character varying])::text[])))
);


ALTER TABLE public.trips OWNER TO snehapatel;

--
-- Name: trips_id_seq; Type: SEQUENCE; Schema: public; Owner: snehapatel
--

CREATE SEQUENCE public.trips_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.trips_id_seq OWNER TO snehapatel;

--
-- Name: trips_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: snehapatel
--

ALTER SEQUENCE public.trips_id_seq OWNED BY public.trips.id;


--
-- Name: users; Type: TABLE; Schema: public; Owner: snehapatel
--

CREATE TABLE public.users (
    id integer NOT NULL,
    first_name character varying(60) NOT NULL,
    last_name character varying(60) NOT NULL,
    email character varying(190) NOT NULL,
    password_hash character varying(255) NOT NULL,
    phone character varying(30),
    city character varying(100),
    country character varying(100),
    profile_photo character varying(255),
    additional_info text,
    language_pref character varying(10) DEFAULT 'en'::character varying NOT NULL,
    role character varying(20) DEFAULT 'user'::character varying NOT NULL,
    created_at timestamp with time zone DEFAULT now() NOT NULL,
    updated_at timestamp with time zone DEFAULT now() NOT NULL,
    preferences jsonb DEFAULT '{}'::jsonb,
    CONSTRAINT users_role_check CHECK (((role)::text = ANY ((ARRAY['user'::character varying, 'admin'::character varying])::text[])))
);


ALTER TABLE public.users OWNER TO snehapatel;

--
-- Name: users_id_seq; Type: SEQUENCE; Schema: public; Owner: snehapatel
--

CREATE SEQUENCE public.users_id_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER TABLE public.users_id_seq OWNER TO snehapatel;

--
-- Name: users_id_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: snehapatel
--

ALTER SEQUENCE public.users_id_seq OWNED BY public.users.id;


--
-- Name: activities id; Type: DEFAULT; Schema: public; Owner: snehapatel
--

ALTER TABLE ONLY public.activities ALTER COLUMN id SET DEFAULT nextval('public.activities_id_seq'::regclass);


--
-- Name: budget_items id; Type: DEFAULT; Schema: public; Owner: snehapatel
--

ALTER TABLE ONLY public.budget_items ALTER COLUMN id SET DEFAULT nextval('public.budget_items_id_seq'::regclass);


--
-- Name: cities id; Type: DEFAULT; Schema: public; Owner: snehapatel
--

ALTER TABLE ONLY public.cities ALTER COLUMN id SET DEFAULT nextval('public.cities_id_seq'::regclass);


--
-- Name: community_likes id; Type: DEFAULT; Schema: public; Owner: snehapatel
--

ALTER TABLE ONLY public.community_likes ALTER COLUMN id SET DEFAULT nextval('public.community_likes_id_seq'::regclass);


--
-- Name: community_posts id; Type: DEFAULT; Schema: public; Owner: snehapatel
--

ALTER TABLE ONLY public.community_posts ALTER COLUMN id SET DEFAULT nextval('public.community_posts_id_seq'::regclass);


--
-- Name: saved_destinations id; Type: DEFAULT; Schema: public; Owner: snehapatel
--

ALTER TABLE ONLY public.saved_destinations ALTER COLUMN id SET DEFAULT nextval('public.saved_destinations_id_seq'::regclass);


--
-- Name: trip_activities id; Type: DEFAULT; Schema: public; Owner: snehapatel
--

ALTER TABLE ONLY public.trip_activities ALTER COLUMN id SET DEFAULT nextval('public.trip_activities_id_seq'::regclass);


--
-- Name: trip_budget id; Type: DEFAULT; Schema: public; Owner: snehapatel
--

ALTER TABLE ONLY public.trip_budget ALTER COLUMN id SET DEFAULT nextval('public.trip_budget_id_seq'::regclass);


--
-- Name: trip_stops id; Type: DEFAULT; Schema: public; Owner: snehapatel
--

ALTER TABLE ONLY public.trip_stops ALTER COLUMN id SET DEFAULT nextval('public.trip_stops_id_seq'::regclass);


--
-- Name: trips id; Type: DEFAULT; Schema: public; Owner: snehapatel
--

ALTER TABLE ONLY public.trips ALTER COLUMN id SET DEFAULT nextval('public.trips_id_seq'::regclass);


--
-- Name: users id; Type: DEFAULT; Schema: public; Owner: snehapatel
--

ALTER TABLE ONLY public.users ALTER COLUMN id SET DEFAULT nextval('public.users_id_seq'::regclass);


--
-- Data for Name: activities; Type: TABLE DATA; Schema: public; Owner: snehapatel
--

COPY public.activities (id, city_id, name, description, category, cost, duration_hours, image_url, created_at) FROM stdin;
1	1	Eiffel Tower Summit Visit	Skip-the-line summit elevator access with panoramic 360-degree views across Paris and champagne bar.	sightseeing	45.00	2.0	https://images.unsplash.com/photo-1511739001486-6bfe10ce785f?w=600&q=80	2026-08-22 13:53:56.562874+05:30
2	1	Louvre Museum Guided Tour	Explore masterworks including Mona Lisa, Venus de Milo, and Winged Victory with an art historian.	culture	35.00	3.0	https://images.unsplash.com/photo-1499856871958-5b9627545d1a?w=600&q=80	2026-08-22 13:53:56.562874+05:30
3	1	Seine River Sunset Cruise	Relaxing glass-canopy boat cruise along the Seine featuring historical audio commentary and wine.	relaxation	25.00	1.5	https://images.unsplash.com/photo-1502602898657-3e91760cbb34?w=600&q=80	2026-08-22 13:53:56.562874+05:30
4	1	French Macaron & Pastry Masterclass	Hands-on pastry workshop learning the delicate art of crafting authentic Parisian macarons.	food	65.00	2.5	https://images.unsplash.com/photo-1569864358642-9d1684040f43?w=600&q=80	2026-08-22 13:53:56.562874+05:30
5	2	Shibuya Food & Izakaya Crawl	Taste Yakitori, Sashimi, and craft Sake guided through hidden alleyways of Omoide Yokocho.	food	40.00	3.0	https://images.unsplash.com/photo-1503899036084-c55cdd92da26?w=600&q=80	2026-08-22 13:53:56.562874+05:30
6	2	TeamLab Planets Digital Art Museum	Immersive sensory body installation walking through water and floating floral digital gardens.	culture	32.00	2.0	https://images.unsplash.com/photo-1542051841857-5f90071e7989?w=600&q=80	2026-08-22 13:53:56.562874+05:30
7	2	Mount Fuji & Lake Kawaguchi Day Tour	Panoramic excursion to Fuji 5th Station, scenic ropeway, and traditional Oshino Hakkai village.	adventure	85.00	8.0	https://images.unsplash.com/photo-1490806843957-31f4c9a91c65?w=600&q=80	2026-08-22 13:53:56.562874+05:30
8	2	Hakone Onsen Thermal Relaxation	Traditional Japanese hot spring bath experience overlooking forested mountain valleys.	relaxation	45.00	4.0	https://images.unsplash.com/photo-1503899036084-c55cdd92da26?w=600&q=80	2026-08-22 13:53:56.562874+05:30
9	3	Balinese Traditional Cooking Class	Market tour and authentic farm-to-table cooking workshop preparing satay and sambal.	food	30.00	3.0	https://images.unsplash.com/photo-1537996194471-e657df975ab4?w=600&q=80	2026-08-22 13:53:56.562874+05:30
10	3	Ubud Rice Terrace & Jungle Swing Trek	Trek through emerald Tegallalang rice paddies followed by high-altitude jungle swings.	adventure	20.00	4.0	https://images.unsplash.com/photo-1518548419970-58e3b4079ab2?w=600&q=80	2026-03-22 15:59:58.783218+05:30
11	3	Nusa Penida Snorkel & Manta Ray Tour	Speedboat day trip snorkeling with giant Manta Rays and viewing Kelingking T-Rex cliff.	adventure	65.00	7.0	https://images.unsplash.com/photo-1544644181-1484b3fdfc62?w=600&q=80	2026-03-22 15:59:58.783218+05:30
12	3	Uluwatu Sunset Temple & Kecak Dance	Dramatic cliffside temple sunset accompanied by mesmerizing traditional fire dance performance.	culture	25.00	3.0	https://images.unsplash.com/photo-1537996194471-e657df975ab4?w=600&q=80	2026-03-22 15:59:58.783218+05:30
13	4	Broadway Musical Evening Show	Premium orchestra seating for award-winning theater performances in Times Square.	culture	120.00	2.5	https://images.unsplash.com/photo-1518391846015-55a9cc003b25?w=600&q=80	2026-03-22 15:59:58.783218+05:30
14	4	Central Park Guided Bike & Rowboat Tour	Cycle through scenic avenues, Strawberry Fields, and row vintage boats on Central Park Lake.	sightseeing	28.00	2.0	https://images.unsplash.com/photo-1538688525198-9b88f6f53126?w=600&q=80	2026-03-22 15:59:58.783218+05:30
15	4	Statue of Liberty & Ellis Island Ferry	Round-trip harbor cruise with pedestal access and audio tour of immigration history.	sightseeing	30.00	3.5	https://images.unsplash.com/photo-1496442226666-8d4d0e62e6e9?w=600&q=80	2026-03-22 15:59:58.783218+05:30
16	4	Greenwich Village Food & Pizza Walk	Tasting tour through New York's best historic pizzerias, bagel shops, and cannoli bakeries.	food	55.00	3.0	https://images.unsplash.com/photo-1513104890138-7c749659a591?w=600&q=80	2026-03-22 15:59:58.783218+05:30
17	5	Colosseum & Roman Forum Underground Tour	Exclusive gladiator gate access, arena floor walk, and ruins of the ancient Roman Empire.	culture	50.00	3.0	https://images.unsplash.com/photo-1552832230-c0197dd311b5?w=600&q=80	2026-03-22 15:59:58.783218+05:30
18	5	Vatican Museums & Sistine Chapel	Skip-the-line guided exploration of Michelangelo's frescoes and St. Peter's Basilica.	culture	48.00	3.5	https://images.unsplash.com/photo-1531572753322-ad063cecc140?w=600&q=80	2026-03-22 15:59:58.783218+05:30
19	5	Trastevere Sunset Tapas & Wine Walk	Authentic evening pasta crawl tasting cacio e pepe and regional Chianti wines.	food	45.00	2.5	https://images.unsplash.com/photo-1555396273-367ea4eb4db5?w=600&q=80	2026-03-22 15:59:58.783218+05:30
20	6	Sagrada Familia Fast-Track Guided Visit	Discover Antoni Gaudí's uncompleted masterpiece basilica and towering stained glass naves.	culture	38.00	2.0	https://images.unsplash.com/photo-1583422409516-2895a77efded?w=600&q=80	2026-03-22 15:59:58.783218+05:30
21	6	Park Güell & Gràcia District Stroll	Marvel at vibrant mosaic dragon terraces and bohemian artist plazas overlooking the sea.	sightseeing	22.00	2.5	https://images.unsplash.com/photo-1539037116277-4db20889f2d4?w=600&q=80	2026-03-22 15:59:58.783218+05:30
22	6	Tapas & Flamenco Passion Show	Intimate live guitar performance paired with Iberian ham, manchego, and sangria.	food	52.00	3.0	https://images.unsplash.com/photo-1515443961218-a51367888e4b?w=600&q=80	2026-03-22 15:59:58.783218+05:30
23	7	Grand Palace & Wat Pho Golden Reclining Buddha	Marvel at Thailand's sacred emerald Buddha and the birth center of traditional Thai massage.	culture	25.00	3.0	https://images.unsplash.com/photo-1508009603885-50cf7c579365?w=600&q=80	2026-03-22 15:59:58.783218+05:30
24	7	Damnoen Saduak Floating Market Longtail Boat	Glide through wooden canal markets sampling pad thai and coconut ice cream from boat vendors.	food	35.00	4.5	https://images.unsplash.com/photo-1528181304800-259b08848526?w=600&q=80	2026-03-22 15:59:58.783218+05:30
25	7	Chao Phraya Luxury Dinner Cruise	Spectacular illuminated river cruise past lit temples with international buffet dinner.	relaxation	40.00	2.5	https://images.unsplash.com/photo-1563492065599-3520f775eeed?w=600&q=80	2026-03-22 15:59:58.783218+05:30
26	8	Table Mountain Cableway & Summit Hike	Revolving aerial cable car ride to 1,000m plateau offering dramatic 360-degree ocean views.	adventure	28.00	3.0	https://images.unsplash.com/photo-1580618672591-eb180b1a973f?w=600&q=80	2026-03-22 15:59:58.783218+05:30
27	8	Cape Peninsula & Boulders Beach Penguins	Scenic Chapman's Peak coastal drive visiting the famous African penguin colony.	sightseeing	45.00	6.0	https://images.unsplash.com/photo-1507525428034-b723cf961d3e?w=600&q=80	2026-03-22 15:59:58.783218+05:30
28	8	Stellenbosch Wine Estate Tasting Tour	Cellar tours and gourmet chocolate & Pinotage pairings across historic Cape Dutch vineyards.	food	60.00	5.0	https://images.unsplash.com/photo-1510812431401-41d2bd2722f3?w=600&q=80	2026-03-22 15:59:58.783218+05:30
29	9	Tower of London & Crown Jewels	Meet the Yeoman Warders and gaze at historic centuries of British Royal ceremonial regalia.	culture	35.00	2.5	https://images.unsplash.com/photo-1513635269975-59663e0ac1ad?w=600&q=80	2026-03-22 15:59:58.783218+05:30
30	9	Westminster & Thames Walking Tour	View Big Ben, Houses of Parliament, and Buckingham Palace Changing of the Guard.	sightseeing	20.00	2.0	https://images.unsplash.com/photo-1529655683826-aba9b3e77383?w=600&q=80	2026-03-22 15:59:58.783218+05:30
31	10	Sydney Opera House Architecture Tour	Step inside the world-famous sails and acoustic concert halls designed by Jørn Utzon.	culture	32.00	1.5	https://images.unsplash.com/photo-1506973035872-a4ec16b8e8d9?w=600&q=80	2026-03-22 15:59:58.783218+05:30
32	10	Bondi Beach Beginner Surf Lesson	Two-hour wave surfing lesson with licensed instructors on Australia's most famous break.	adventure	50.00	2.0	https://images.unsplash.com/photo-1507525428034-b723cf961d3e?w=600&q=80	2026-03-22 15:59:58.783218+05:30
33	11	Burj Khalifa At The Top (Levels 124 & 125)	Soar to the observatory of the world's tallest skyscraper overlooking Dubai fountain.	sightseeing	48.00	1.5	https://images.unsplash.com/photo-1512453979798-5ea266f8880c?w=600&q=80	2026-03-22 15:59:58.783218+05:30
34	11	Red Dunes Desert Safari & BBQ Dinner	Thrilling 4x4 dune bashing, camel rides, sandboarding, and live Arabian belly dancing.	adventure	65.00	6.0	https://images.unsplash.com/photo-1451187580459-43490279c0fa?w=600&q=80	2026-03-22 15:59:58.783218+05:30
35	12	Christ the Redeemer & Corcovado Train	Historic cogwheel train ride through Tijuca jungle to the iconic 38-meter monument.	sightseeing	30.00	3.0	https://images.unsplash.com/photo-1483729558449-99ef09a8c325?w=600&q=80	2026-03-22 15:59:58.783218+05:30
36	12	Sugarloaf Mountain Sunset Cable Car	Two-stage glass cableway to Urca and Pão de Açúcar with Copacabana sunset vistas.	relaxation	32.00	2.5	https://images.unsplash.com/photo-1483729558449-99ef09a8c325?w=600&q=80	2026-03-22 15:59:58.783218+05:30
37	13	Fushimi Inari 10,000 Torii Gates Hike	Morning trek through iconic vermilion torii paths ascending sacred Mount Inari.	sightseeing	15.00	3.0	https://images.unsplash.com/photo-1493976040374-85c8e12f0c0e?w=600&q=80	2026-03-22 15:59:58.783218+05:30
38	13	Traditional Geisha Tea Ceremony & Kimono	Experience authentic Zen matcha preparation wearing a traditional silk kimono.	culture	42.00	2.0	https://images.unsplash.com/photo-1503899036084-c55cdd92da26?w=600&q=80	2026-03-22 15:59:58.783218+05:30
39	14	Rijksmuseum & Van Gogh Masterpieces	See Rembrandt's Night Watch and Van Gogh's Sunflowers with audio expert narration.	culture	36.00	3.0	https://images.unsplash.com/photo-1534351590666-13e3e96b5017?w=600&q=80	2026-03-22 15:59:58.783218+05:30
40	14	Historic Canal Belt Open Boat Tour	Cruise UNESCO 17th-century canal rings with complimentary Dutch cheeses and craft beer.	relaxation	24.00	1.5	https://images.unsplash.com/photo-1534351590666-13e3e96b5017?w=600&q=80	2026-03-22 15:59:58.783218+05:30
41	15	Giza Pyramids & Sphinx Camel Trek	Stand before the Great Pyramid of Khufu and ride camels across panoramic Saharan sands.	sightseeing	40.00	4.0	https://images.unsplash.com/photo-1572252009286-268acec5ca0a?w=600&q=80	2026-03-22 15:59:58.783218+05:30
42	16	Blue Lagoon Geothermal Spa & Silica Mask	Bathe in mineral-rich milky cyan thermal waters surrounded by volcanic black lava fields.	relaxation	75.00	3.5	https://images.unsplash.com/photo-1504893524553-b855bce32c67?w=600&q=80	2026-03-22 15:59:58.783218+05:30
43	16	Golden Circle & Thingvellir Rift Tour	Witness Gullfoss waterfall, Strokkur erupting geyser, and continental tectonic divide.	adventure	68.00	7.0	https://images.unsplash.com/photo-1504893524553-b855bce32c67?w=600&q=80	2026-03-22 15:59:58.783218+05:30
44	17	Gardens by the Bay & Cloud Forest Dome	Walk the OCBC Skyway amid 50-meter supertrees and indoor tropical waterfall conservatory.	sightseeing	34.00	2.5	https://images.unsplash.com/photo-1525625293386-3f8f99389edd?w=600&q=80	2026-03-22 15:59:58.783218+05:30
45	18	Prague Castle & St. Vitus Cathedral Tour	Wander the historic royal castle courtyards, Golden Lane, and Gothic cathedral stained glass.	culture	22.00	3.0	https://images.unsplash.com/photo-1541849546-216549ae216d?w=600&q=80	2026-03-22 15:59:58.783218+05:30
46	19	Jemaa el-Fnaa Night Market & Souk Tour	Navigate bustling spice lanes, artisan leather ateliers, and open-air food stalls.	culture	20.00	3.0	https://images.unsplash.com/photo-1597212618440-806262de4f6b?w=600&q=80	2026-03-22 15:59:58.783218+05:30
47	21	Milford Sound Fjord Cruise Expedition	Day tour through majestic alpine tunnels to cruise beneath waterfalls and sheer fjord cliffs.	adventure	110.00	9.0	https://images.unsplash.com/photo-1589871973318-9ca1258faa5d?w=600&q=80	2026-03-22 15:59:58.783218+05:30
\.


--
-- Data for Name: budget_items; Type: TABLE DATA; Schema: public; Owner: snehapatel
--

COPY public.budget_items (id, trip_id, stop_id, category, description, amount, spent_on, created_at) FROM stdin;
1	3	6	transport	Air France Flights (Paris roundtrip)	750.00	2026-08-15	2026-08-08 15:59:58.783218+05:30
2	3	6	stay	Pullman Paris Tour Eiffel 5 Nights	850.00	2026-08-15	2026-08-08 15:59:58.783218+05:30
3	3	6	activities	Eiffel Tower Summit Skip-the-Line	45.00	2026-08-16	2026-08-08 15:59:58.783218+05:30
4	3	6	meals	Dinner at Le Jules Verne (Eiffel Tower)	185.00	2026-08-16	2026-08-08 15:59:58.783218+05:30
5	3	6	activities	Seine Sunset Boat Cruise Tickets	25.00	2026-08-17	2026-08-08 15:59:58.783218+05:30
6	3	6	meals	Café de Flore Bistro Lunch	48.00	2026-08-17	2026-08-08 15:59:58.783218+05:30
7	3	6	shopping	Souvenirs & Perfume in Marais	120.00	2026-08-18	2026-08-08 15:59:58.783218+05:30
8	3	7	transport	ANA Nonstop Flight Paris to Tokyo	900.00	2026-08-20	2026-08-08 15:59:58.783218+05:30
9	3	7	stay	Park Hyatt Tokyo 5 Nights	950.00	2026-08-20	2026-08-08 15:59:58.783218+05:30
10	3	7	activities	TeamLab Planets Museum Pass	32.00	2026-08-21	2026-08-08 15:59:58.783218+05:30
11	3	7	meals	Sushi Dai Omakase Experience	110.00	2026-08-22	2026-08-08 15:59:58.783218+05:30
12	4	9	transport	Icelandair International Flight	550.00	2026-01-10	2026-08-08 15:59:58.783218+05:30
13	4	9	stay	Canopy Hilton Reykjavik	750.00	2026-01-10	2026-08-08 15:59:58.783218+05:30
14	4	9	activities	Blue Lagoon Premium Admission	75.00	2026-01-11	2026-08-08 15:59:58.783218+05:30
15	4	9	activities	Golden Circle Super Jeep Tour	68.00	2026-01-13	2026-08-08 15:59:58.783218+05:30
16	4	10	transport	Schiphol Airport Transfer & Flights	110.00	2026-01-15	2026-08-08 15:59:58.783218+05:30
17	4	10	stay	Pulitzer Amsterdam 5 Nights	620.00	2026-01-15	2026-08-08 15:59:58.783218+05:30
18	4	10	activities	Rijksmuseum Art Pass	36.00	2026-01-16	2026-08-08 15:59:58.783218+05:30
19	4	10	meals	Pancakes Amsterdam & Local Dining	85.00	2026-01-17	2026-08-08 15:59:58.783218+05:30
\.


--
-- Data for Name: cities; Type: TABLE DATA; Schema: public; Owner: snehapatel
--

COPY public.cities (id, name, country, region, cost_index, popularity_score, description, image_url, created_at) FROM stdin;
1	Paris	France	Europe	78.50	98	The City of Light, celebrated for world-class haute cuisine, iconic art museums like the Louvre, and romantic Seine promenades.	https://images.unsplash.com/photo-1502602898657-3e91760cbb34?w=800&q=80	2026-08-22 13:53:56.561944+05:30
2	Tokyo	Japan	Asia	72.00	95	A mesmerizing metropolis where hyper-modern neon skyscrapers seamlessly merge with serene historic Shinto shrines and legendary cuisine.	https://images.unsplash.com/photo-1503899036084-c55cdd92da26?w=800&q=80	2026-08-22 13:53:56.561944+05:30
3	Bali	Indonesia	Asia	35.00	90	The Island of the Gods, renowned for emerald tiered rice terraces, sacred sea temples, coral reefs, and tranquil wellness retreats.	https://images.unsplash.com/photo-1537996194471-e657df975ab4?w=800&q=80	2026-08-22 13:53:56.561944+05:30
4	New York	USA	North America	95.00	92	The premier global epicenter of culture, arts, Broadway theater, world dining, and iconic skyscraper skylines.	https://images.unsplash.com/photo-1496442226666-8d4d0e62e6e9?w=800&q=80	2026-08-22 13:53:56.561944+05:30
5	Rome	Italy	Europe	70.00	89	The Eternal City, a living open-air museum filled with millennia of ancient history, magnificent piazzas, and vibrant trattorias.	https://images.unsplash.com/photo-1552832230-c0197dd311b5?w=800&q=80	2026-08-22 13:53:56.561944+05:30
6	Barcelona	Spain	Europe	60.00	88	A sun-drenched Mediterranean jewel featuring Gaudí's surreal architecture, lively tapas bars, and golden urban beaches.	https://images.unsplash.com/photo-1583422409516-2895a77efded?w=800&q=80	2026-08-22 13:53:56.561944+05:30
7	Bangkok	Thailand	Asia	28.00	85	A high-energy tropical capital of ornate golden palaces, bustling floating markets, and world-renowned street food.	https://images.unsplash.com/photo-1508009603885-50cf7c579365?w=800&q=80	2026-08-22 13:53:56.561944+05:30
8	Cape Town	South Africa	Africa	45.00	75	A breathtaking coastal wonder dominated by Table Mountain, penguin-filled beaches, and historic rolling vineyards.	https://images.unsplash.com/photo-1580618672591-eb180b1a973f?w=800&q=80	2026-08-22 13:53:56.561944+05:30
9	London	United Kingdom	Europe	88.00	94	A historic global hub of royal landmarks, West End theater productions, sprawling royal parks, and cutting-edge culture.	https://images.unsplash.com/photo-1513635269975-59663e0ac1ad?w=800&q=80	2026-02-22 15:59:58.783218+05:30
10	Sydney	Australia	Oceania	82.00	87	A world-famous harbor city blessed with the Opera House, golden surf beaches like Bondi, and pristine coastal parklands.	https://images.unsplash.com/photo-1506973035872-a4ec16b8e8d9?w=800&q=80	2026-02-22 15:59:58.783218+05:30
11	Dubai	UAE	Asia	90.00	91	An opulent desert oasis of futuristic engineering, ultra-luxury shopping resorts, desert safaris, and indoor wonderlands.	https://images.unsplash.com/photo-1512453979798-5ea266f8880c?w=800&q=80	2026-02-22 15:59:58.783218+05:30
12	Rio de Janeiro	Brazil	South America	42.00	81	The Marvelous City, famed for Christ the Redeemer atop Corcovado, vibrant Copacabana sands, and infectious samba rhythms.	https://images.unsplash.com/photo-1483729558449-99ef09a8c325?w=800&q=80	2026-02-22 15:59:58.783218+05:30
13	Kyoto	Japan	Asia	65.00	89	Japan's cultural soul, home to thousands of classical Buddhist temples, Zen gardens, bamboo groves, and traditional geishas.	https://images.unsplash.com/photo-1493976040374-85c8e12f0c0e?w=800&q=80	2026-02-22 15:59:58.783218+05:30
14	Amsterdam	Netherlands	Europe	75.00	86	A picturesque network of historic canals, world-class art collections, historic canal houses, and vibrant cycling culture.	https://images.unsplash.com/photo-1534351590666-13e3e96b5017?w=800&q=80	2026-02-22 15:59:58.783218+05:30
15	Cairo	Egypt	Africa	32.00	80	The monumental Cradle of Civilization with the Great Pyramids of Giza, the Nile River, and historic labyrinthine bazaars.	https://images.unsplash.com/photo-1572252009286-268acec5ca0a?w=800&q=80	2026-02-22 15:59:58.783218+05:30
16	Reykjavik	Iceland	Europe	110.00	83	Gateway to otherworldly glacial lagoons, roaring geothermal geysers, volcanic hot springs, and ethereal Northern Lights.	https://images.unsplash.com/photo-1504893524553-b855bce32c67?w=800&q=80	2026-02-22 15:59:58.783218+05:30
17	Singapore	Singapore	Asia	85.00	91	A breathtaking futuristic garden city offering architectural wonders like Marina Bay Sands, supertrees, and diverse street cuisine.	https://images.unsplash.com/photo-1525625293386-3f8f99389edd?w=800&q=80	2026-02-22 15:59:58.783218+05:30
18	Prague	Czech Republic	Europe	48.00	84	The City of a Hundred Spires, celebrated for its Gothic castle, Charles Bridge street performers, and rich brewing history.	https://images.unsplash.com/photo-1541849546-216549ae216d?w=800&q=80	2026-02-22 15:59:58.783218+05:30
19	Marrakech	Morocco	Africa	38.00	79	An intoxicating sensory wonder of terracotta medinas, vibrant spice souks, Moorish riads, and palm-studded gardens.	https://images.unsplash.com/photo-1597212618440-806262de4f6b?w=800&q=80	2026-02-22 15:59:58.783218+05:30
20	Vancouver	Canada	North America	76.00	82	A majestic coastal Pacific city nestled between snow-capped coastal mountains and scenic ocean inlets.	https://images.unsplash.com/photo-1559511260-66a65e09b245?w=800&q=80	2026-02-22 15:59:58.783218+05:30
21	Queenstown	New Zealand	Oceania	80.00	85	The Adventure Capital of the World, situated along Lake Wakatipu with world-class skiing, jet boating, and fjord trekking.	https://images.unsplash.com/photo-1589871973318-9ca1258faa5d?w=800&q=80	2026-02-22 15:59:58.783218+05:30
22	Buenos Aires	Argentina	South America	36.00	78	The Paris of South America, vibrating with passionate tango performances, grand European architecture, and world-class steakhouses.	https://images.unsplash.com/photo-1589909202802-8f4aadce1849?w=800&q=80	2026-02-22 15:59:58.783218+05:30
\.


--
-- Data for Name: community_likes; Type: TABLE DATA; Schema: public; Owner: snehapatel
--

COPY public.community_likes (id, post_id, user_id, created_at) FROM stdin;
1	1	2	2026-08-15 15:59:58.783218+05:30
2	1	3	2026-08-15 15:59:58.783218+05:30
3	1	4	2026-08-15 15:59:58.783218+05:30
4	1	5	2026-08-15 15:59:58.783218+05:30
5	2	1	2026-08-15 15:59:58.783218+05:30
6	2	4	2026-08-15 15:59:58.783218+05:30
7	2	6	2026-08-15 15:59:58.783218+05:30
8	3	2	2026-08-15 15:59:58.783218+05:30
9	3	5	2026-08-15 15:59:58.783218+05:30
10	3	7	2026-08-15 15:59:58.783218+05:30
11	4	1	2026-08-15 15:59:58.783218+05:30
12	4	3	2026-08-15 15:59:58.783218+05:30
13	4	8	2026-08-15 15:59:58.783218+05:30
14	5	2	2026-08-15 15:59:58.783218+05:30
15	5	6	2026-08-15 15:59:58.783218+05:30
\.


--
-- Data for Name: community_posts; Type: TABLE DATA; Schema: public; Owner: snehapatel
--

COPY public.community_posts (id, user_id, trip_id, title, content, likes_count, created_at, updated_at) FROM stdin;
1	2	1	Top 5 Secret Ramen Spots in Tokyo You Cannot Miss	After spending 2 weeks navigating Tokyo\\'s labyrinth of subway alleys, here are 5 hidden gem ramen shops where locals queue up:\n\n1. Fuunji in Shinjuku (best Tsukemen dipping noodles on earth)\n2. Afuri in Ebisu (refreshing Yuzu shio broth)\n3. Rokurinsha at Tokyo Station Ramen Street\n4. Kagari in Ginza (rich chicken paitan)\n5. Ichiran Shibuya for late-night solo booth indulgence.\n\nTip: Always bring 1,000 yen cash for the ticket vending machines!	42	2026-08-10 14:30:00+05:30	2026-08-10 14:30:00+05:30
2	3	3	A Food Critic's 48-Hour Guide to Paris Boulevards	Paris in August has a magic of its own. Here is the perfect itinerary for culinary lovers:\n\n- Morning: Fresh butter croissants at Du Pain et des Idées near Canal Saint-Martin.\n- Afternoon: Macaron workshop in Saint-Germain followed by an espresso at Café de Flore.\n- Evening: Sunset picnic on the Pont des Arts with Comté cheese, baguette, and Bordeaux wine.\n\nBe sure to check my linked trip itinerary for exact locations and tickets!	38	2026-08-12 18:00:00+05:30	2026-08-12 18:00:00+05:30
3	4	5	Backpacking Bali on $30/Day: Honest Budget Breakdown	Bali is still paradise on a budget if you follow these rules:\n- Stay in guesthouses (homestays) in Canggu or Ubud ($15-20/night with AC).\n- Eat at local Warungs ($2-4 for Nasi Goreng with fried egg).\n- Rent a scooter for $5/day instead of hiring private drivers.\n- Wake up at 5:30 AM for the Tegallalang rice terraces to beat both the heat and the crowds!	56	2026-08-14 09:15:00+05:30	2026-08-14 09:15:00+05:30
4	5	2	Exploring Barcelona: Why Park Güell at Sunrise is Unbeatable	Gaudí\\'s masterpiece looks like a fairy tale when the golden morning sun hits the ceramic mosaic benches overlooking the Mediterranean.\n\nBook the earliest 9:30 AM entry ticket to have the dragon staircase almost completely to yourself for breathtaking photos!	29	2026-08-16 11:20:00+05:30	2026-08-16 11:20:00+05:30
5	6	7	Surfing Bondi & Coastal Hiking Down Under	The coastal cliff walk from Bondi to Coogee is one of the most stunning 6km walks in the southern hemisphere. Stopping at Bronte pool for a dip and fresh smoothie is pure bliss.	34	2026-08-18 16:45:00+05:30	2026-08-18 16:45:00+05:30
6	7	8	Desert Glamping in Dubai: Stargazing Beyond the Skyscrapers	Beyond the glitz of Dubai Marina and the towering Burj Khalifa lies the serene beauty of the Arabian Desert. Sleeping under a million desert stars in a luxury Bedouin tent is an experience every traveler should cherish at least once in their lifetime.	47	2026-08-19 20:00:00+05:30	2026-08-19 20:00:00+05:30
7	8	9	Sunrise Over Rio: Christ the Redeemer and Copacabana	Taking the first cogwheel train up Mount Corcovado at 8 AM allowed us to stand before the statue enveloped in morning mist as the clouds parted over Guanabara Bay. Simply unforgettable!	31	2026-08-20 15:30:00+05:30	2026-08-20 15:30:00+05:30
8	9	4	The Ultimate Golden Circle & Northern Lights Roadtrip Guide	Driving through Iceland during winter is otherworldly. Renting a 4x4 with studded tires is essential for icy mountain passes. Our stop at the Blue Lagoon after a freezing day of waterfall chasing was heavenly warmth.	51	2026-08-21 12:10:00+05:30	2026-08-21 12:10:00+05:30
\.


--
-- Data for Name: saved_destinations; Type: TABLE DATA; Schema: public; Owner: snehapatel
--

COPY public.saved_destinations (id, user_id, city_id, saved_at) FROM stdin;
1	2	1	2026-08-08 15:59:58.783218+05:30
2	2	2	2026-08-08 15:59:58.783218+05:30
3	2	3	2026-08-08 15:59:58.783218+05:30
4	2	6	2026-08-08 15:59:58.783218+05:30
5	2	16	2026-08-08 15:59:58.783218+05:30
6	1	2	2026-08-08 15:59:58.783218+05:30
7	1	10	2026-08-08 15:59:58.783218+05:30
8	1	21	2026-08-08 15:59:58.783218+05:30
9	3	13	2026-08-08 15:59:58.783218+05:30
10	4	8	2026-08-08 15:59:58.783218+05:30
\.


--
-- Data for Name: trip_activities; Type: TABLE DATA; Schema: public; Owner: snehapatel
--

COPY public.trip_activities (id, trip_stop_id, activity_id, scheduled_date, scheduled_time, custom_cost, notes, created_at) FROM stdin;
1	1	5	2026-09-11	18:00:00	40.00	Table reserved for 2 at Omoide Yokocho.	2026-08-01 15:59:58.783218+05:30
2	1	6	2026-09-12	10:30:00	32.00	Online QR tickets on smartphone.	2026-08-01 15:59:58.783218+05:30
3	1	7	2026-09-14	08:00:00	85.00	Shinjuku station bus pickup.	2026-08-01 15:59:58.783218+05:30
4	2	37	2026-09-17	07:30:00	15.00	Early morning hike before crowds arrive.	2026-08-01 15:59:58.783218+05:30
5	2	38	2026-09-18	14:00:00	42.00	Gion district tea master studio.	2026-08-01 15:59:58.783218+05:30
6	3	20	2026-10-06	10:00:00	38.00	Tower elevator access included.	2026-08-01 15:59:58.783218+05:30
7	3	22	2026-10-07	19:30:00	52.00	Tablao Flamenco Cordobes show.	2026-08-01 15:59:58.783218+05:30
8	4	17	2026-10-10	09:00:00	50.00	Arch of Constantine meeting point.	2026-08-01 15:59:58.783218+05:30
9	4	18	2026-10-12	13:30:00	48.00	Dress code: covered shoulders.	2026-08-01 15:59:58.783218+05:30
10	5	1	2026-10-15	16:30:00	45.00	Sunset from top floor.	2026-08-01 15:59:58.783218+05:30
11	5	2	2026-10-16	09:30:00	35.00	Pyramid entrance.	2026-08-01 15:59:58.783218+05:30
12	6	1	2026-08-16	17:00:00	45.00	Golden hour photography.	2026-08-01 15:59:58.783218+05:30
13	6	3	2026-08-17	19:00:00	25.00	Pont Neuf boarding dock.	2026-08-01 15:59:58.783218+05:30
14	6	4	2026-08-18	14:00:00	65.00	Baking fresh chocolate macarons.	2026-08-01 15:59:58.783218+05:30
15	7	5	2026-08-21	18:30:00	40.00	Tasting local yakitori.	2026-08-01 15:59:58.783218+05:30
16	7	6	2026-08-22	11:00:00	32.00	Water room barefoot exhibition.	2026-08-01 15:59:58.783218+05:30
17	8	9	2026-08-26	09:00:00	30.00	Morning spice market shopping.	2026-08-01 15:59:58.783218+05:30
18	8	10	2026-08-27	08:00:00	20.00	Jungle swing photography.	2026-08-01 15:59:58.783218+05:30
19	8	11	2026-08-28	06:30:00	65.00	Sanur harbor speedboat.	2026-08-01 15:59:58.783218+05:30
20	9	42	2026-01-11	13:00:00	75.00	Towel and silica mask included.	2026-08-01 15:59:58.783218+05:30
21	9	43	2026-01-13	08:30:00	68.00	Gullfoss and Geysir coach bus.	2026-08-01 15:59:58.783218+05:30
22	10	39	2026-01-16	10:00:00	36.00	Museumplein entrance.	2026-08-01 15:59:58.783218+05:30
23	10	40	2026-01-17	16:00:00	24.00	Prinsengracht boarding dock.	2026-08-01 15:59:58.783218+05:30
24	11	23	2026-02-02	09:00:00	25.00	Temple dress code applies.	2026-08-01 15:59:58.783218+05:30
25	11	24	2026-02-04	07:00:00	35.00	Wooden longtail boat ride.	2026-08-01 15:59:58.783218+05:30
26	14	26	2026-11-02	10:00:00	28.00	Cable car weather permit.	2026-08-01 15:59:58.783218+05:30
27	14	27	2026-11-03	09:00:00	45.00	Simon's Town penguin boardwalk.	2026-08-01 15:59:58.783218+05:30
28	14	28	2026-11-04	11:00:00	60.00	Chocolate and wine pairings.	2026-08-01 15:59:58.783218+05:30
29	6	14	2026-08-15	\N	\N	\N	2026-08-22 16:03:04.71662+05:30
\.


--
-- Data for Name: trip_budget; Type: TABLE DATA; Schema: public; Owner: snehapatel
--

COPY public.trip_budget (id, trip_id, transport_budget, stay_budget, activities_budget, meals_budget, misc_budget, created_at, updated_at) FROM stdin;
1	1	1000.00	1400.00	400.00	600.00	300.00	2026-08-22 14:53:33.812069+05:30	2026-08-22 14:53:53.011661+05:30
2	2	900.00	1700.00	500.00	800.00	400.00	2026-07-22 15:59:58.783218+05:30	2026-08-22 15:59:58.783218+05:30
3	3	2100.00	2400.00	600.00	900.00	500.00	2026-07-22 15:59:58.783218+05:30	2026-08-22 15:59:58.783218+05:30
4	4	800.00	1400.00	400.00	500.00	300.00	2026-07-22 15:59:58.783218+05:30	2026-08-22 15:59:58.783218+05:30
5	5	600.00	1600.00	300.00	500.00	200.00	2026-07-22 15:59:58.783218+05:30	2026-08-22 15:59:58.783218+05:30
6	6	1500.00	1800.00	600.00	700.00	400.00	2026-07-22 15:59:58.783218+05:30	2026-08-22 15:59:58.783218+05:30
7	7	1200.00	1800.00	500.00	700.00	300.00	2026-07-22 15:59:58.783218+05:30	2026-08-22 15:59:58.783218+05:30
8	8	1400.00	1600.00	400.00	600.00	300.00	2026-07-22 15:59:58.783218+05:30	2026-08-22 15:59:58.783218+05:30
9	9	1000.00	1200.00	350.00	550.00	250.00	2026-07-22 15:59:58.783218+05:30	2026-08-22 15:59:58.783218+05:30
10	10	400.00	800.00	250.00	400.00	200.00	2026-07-22 15:59:58.783218+05:30	2026-08-22 15:59:58.783218+05:30
\.


--
-- Data for Name: trip_stops; Type: TABLE DATA; Schema: public; Owner: snehapatel
--

COPY public.trip_stops (id, trip_id, city_id, arrival_date, departure_date, order_index, transport_note, accommodation, accommodation_cost, budget_for_stop, notes, created_at, updated_at, transport_type, transport_cost) FROM stdin;
1	1	2	2026-09-10	2026-09-16	1	JAL direct flight from JFK to NRT	Shinjuku Granbell Hotel	720.00	1500.00	Stay near Shinjuku station for easy subway access.	2026-07-22 15:59:58.783218+05:30	2026-08-22 15:59:58.783218+05:30	Flight	850
2	1	13	2026-09-16	2026-09-22	2	Shinkansen bullet train from Tokyo to Kyoto	Kyoto Machiya Traditional Inn	650.00	1200.00	Rent bicycles to tour eastern temple paths.	2026-07-22 15:59:58.783218+05:30	2026-08-22 15:59:58.783218+05:30	Train	120
3	2	6	2026-10-05	2026-10-09	1	Delta flight NYC to BCN	H10 Cubik Hotel Barcelona	480.00	1000.00	Walking distance to Gothic Quarter and Las Ramblas.	2026-08-22 14:14:00.616033+05:30	2026-08-22 15:59:58.783218+05:30	Flight	650
4	2	5	2026-10-09	2026-10-14	2	Vueling flight BCN to FCO	Residenza Di Ripetta Rome	600.00	1100.00	Close to Piazza del Popolo.	2026-08-22 14:14:00.616033+05:30	2026-08-22 15:59:58.783218+05:30	Flight	90
5	2	1	2026-10-14	2026-10-18	3	High speed TGV Lyria to Paris	Hotel Le Relais Saint-Germain	580.00	1200.00	Latin Quarter bistros and cafes.	2026-08-22 14:14:00.616033+05:30	2026-08-22 15:59:58.783218+05:30	Train	110
6	3	1	2026-08-15	2026-08-20	1	Air France flight to CDG	Pullman Paris Tour Eiffel	850.00	1600.00	Spectacular balcony views of Eiffel Tower.	2026-07-22 15:59:58.783218+05:30	2026-08-22 15:59:58.783218+05:30	Flight	750
7	3	2	2026-08-20	2026-08-25	2	ANA nonstop Paris to Tokyo Haneda	Park Hyatt Tokyo	950.00	1800.00	Lost in Translation bar on 52nd floor.	2026-07-22 15:59:58.783218+05:30	2026-08-22 15:59:58.783218+05:30	Flight	900
8	3	3	2026-08-25	2026-08-30	3	Garuda Indonesia Tokyo to Bali	Maya Ubud Resort & Spa Bali	600.00	1200.00	Private infinity pool overlooking Petanu River.	2026-07-22 15:59:58.783218+05:30	2026-08-22 15:59:58.783218+05:30	Flight	450
9	4	16	2026-01-10	2026-01-15	1	Icelandair direct	Canopy by Hilton Reykjavik	750.00	1400.00	Pack heavy thermal clothing and spike boots.	2026-07-22 15:59:58.783218+05:30	2026-08-22 15:59:58.783218+05:30	Flight	550
10	4	14	2026-01-15	2026-01-20	2	EasyJet Reykjavik to Schiphol	Pulitzer Amsterdam	620.00	1100.00	Historic 17th century canal houses.	2026-07-22 15:59:58.783218+05:30	2026-08-22 15:59:58.783218+05:30	Flight	110
11	5	7	2026-02-01	2026-02-07	1	Thai Airways to BKK	Riva Surya Bangkok	280.00	600.00	Riverfront terrace on Chao Phraya.	2026-07-22 15:59:58.783218+05:30	2026-08-22 15:59:58.783218+05:30	Flight	420
12	5	3	2026-02-07	2026-02-13	2	AirAsia Bangkok to Denpasar	Padma Resort Ubud	400.00	800.00	Heated jungle infinity pool.	2026-07-22 15:59:58.783218+05:30	2026-08-22 15:59:58.783218+05:30	Flight	85
13	5	17	2026-02-13	2026-02-18	3	Scoot Bali to Singapore	Marina Bay Sands	900.00	1500.00	Iconic rooftop infinity pool.	2026-07-22 15:59:58.783218+05:30	2026-08-22 15:59:58.783218+05:30	Flight	65
14	6	8	2026-11-01	2026-11-06	1	Emirates via Dubai to CPT	The Silo Hotel Cape Town	850.00	1500.00	Historic grain silo conversion at V&A Waterfront.	2026-07-22 15:59:58.783218+05:30	2026-08-22 15:59:58.783218+05:30	Flight	950
15	6	19	2026-11-06	2026-11-10	2	Royal Air Maroc Cape Town to RAK	Riad Yasmine Marrakech	320.00	700.00	Iconic green tiled courtyard pool.	2026-07-22 15:59:58.783218+05:30	2026-08-22 15:59:58.783218+05:30	Flight	350
16	6	15	2026-11-10	2026-11-15	3	EgyptAir Marrakech to Cairo	Marriott Mena House Cairo	550.00	1000.00	Direct garden views of Great Pyramid of Khufu.	2026-07-22 15:59:58.783218+05:30	2026-08-22 15:59:58.783218+05:30	Flight	180
17	7	10	2026-08-18	2026-08-25	1	Qantas to SYD	Park Hyatt Sydney	900.00	1600.00	Unrivaled Opera House harbor views.	2026-07-22 15:59:58.783218+05:30	2026-08-22 15:59:58.783218+05:30	Flight	800
18	7	21	2026-08-25	2026-09-02	2	Air New Zealand Sydney to ZQN	Eichardt's Private Hotel Queenstown	850.00	1400.00	Lake Wakatipu alpine lodge.	2026-07-22 15:59:58.783218+05:30	2026-08-22 15:59:58.783218+05:30	Flight	220
\.


--
-- Data for Name: trips; Type: TABLE DATA; Schema: public; Owner: snehapatel
--

COPY public.trips (id, user_id, trip_name, description, start_date, end_date, cover_photo, status, visibility, share_slug, created_at, updated_at) FROM stdin;
1	2	Cherry Blossoms & Ancient Temples	A dream journey through Japan exploring Tokyo neon streets, Hakone mountain onsens, and Kyoto imperial shrines during spring.	2026-09-10	2026-09-22	https://images.unsplash.com/photo-1503899036084-c55cdd92da26?w=1200&q=80	upcoming	public	cherry-blossoms-japan-2026	2026-06-22 15:59:58.783218+05:30	2026-08-22 15:59:58.783218+05:30
2	2	Mediterranean Coast & Tapas Trail	Two unforgettable weeks savoring Gaudí architecture in Barcelona, ancient Roman ruins, and romantic Seine river walks.	2026-10-05	2026-10-18	https://images.unsplash.com/photo-1583422409516-2895a77efded?w=1200&q=80	upcoming	public	mediterranean-tapas-trail	2026-06-22 15:59:58.783218+05:30	2026-08-22 15:59:58.783218+05:30
3	1	European Summer Odyssey 2026	An epic transcontinental summer tour starting from the romantic boulevards of Paris, across Tokyo's bustling wards, to tropical Bali beaches.	2026-08-15	2026-08-30	https://images.unsplash.com/photo-1469854523086-cc02fe5d8800?w=1200&q=80	ongoing	public	euro-summer-2026	2026-08-22 14:14:00.616033+05:30	2026-08-22 15:59:58.783218+05:30
4	3	Nordic Wilderness & Northern Lights	Chasing geothermal geysers, blue ice caves, and aurora borealis in Reykjavik before exploring historic Amsterdam canals.	2026-01-10	2026-01-20	https://images.unsplash.com/photo-1504893524553-b855bce32c67?w=1200&q=80	completed	public	nordic-aurora-2026	2026-06-22 15:59:58.783218+05:30	2026-08-22 15:59:58.783218+05:30
5	4	Southeast Asian Backpacker Circuit	An affordable backpacking adventure tasting Michelin street eats in Bangkok, surfing in Bali, and marveling at Singapore supertrees.	2026-02-01	2026-02-18	https://images.unsplash.com/photo-1508009603885-50cf7c579365?w=1200&q=80	completed	public	southeast-asia-backpacking	2026-06-22 15:59:58.783218+05:30	2026-08-22 15:59:58.783218+05:30
6	5	Wild Africa & Atlantic Ocean Escape	From Table Mountain vineyards and Boulders Beach penguins to Marrakech spice souks and ancient Giza pyramids.	2026-11-01	2026-11-15	https://images.unsplash.com/photo-1580618672591-eb180b1a973f?w=1200&q=80	upcoming	public	wild-africa-safari	2026-06-22 15:59:58.783218+05:30	2026-08-22 15:59:58.783218+05:30
7	6	Down Under & Kiwi Alps Adventure	Surfing Bondi Beach in Sydney followed by Milford Sound fjord cruises and jet boating in Queenstown.	2026-08-18	2026-09-02	https://images.unsplash.com/photo-1506973035872-a4ec16b8e8d9?w=1200&q=80	ongoing	public	down-under-kiwi-adventure	2026-06-22 15:59:58.783218+05:30	2026-08-22 15:59:58.783218+05:30
8	7	Arabian Nights & Desert Dunes	Luxury desert glamping and sky-high dining in Dubai paired with historic Nile river cruises in Cairo.	2026-12-10	2026-12-22	https://images.unsplash.com/photo-1512453979798-5ea266f8880c?w=1200&q=80	upcoming	public	arabian-nights-dubai	2026-06-22 15:59:58.783218+05:30	2026-08-22 15:59:58.783218+05:30
9	8	South American Wonders Expedition	Samba and Copacabana sunset cable cars in Rio de Janeiro followed by tango and gourmet steakhouses in Buenos Aires.	2026-03-05	2026-03-16	https://images.unsplash.com/photo-1483729558449-99ef09a8c325?w=1200&q=80	completed	public	south-america-wonders	2026-06-22 15:59:58.783218+05:30	2026-08-22 15:59:58.783218+05:30
10	2	Pacific Northwest Coastal Explorer	A private photography expedition capturing Vancouver mountain inlets and Seattle skyline vistas.	2026-11-20	2026-11-28	https://images.unsplash.com/photo-1559511260-66a65e09b245?w=1200&q=80	upcoming	private	\N	2026-06-22 15:59:58.783218+05:30	2026-08-22 15:59:58.783218+05:30
\.


--
-- Data for Name: users; Type: TABLE DATA; Schema: public; Owner: snehapatel
--

COPY public.users (id, first_name, last_name, email, password_hash, phone, city, country, profile_photo, additional_info, language_pref, role, created_at, updated_at, preferences) FROM stdin;
8	Mateo	Silva	mateo.silva@andesexp.br	$2y$12$m47nsnXjA8M87Khatc2qseZ6d9TMfML7bAIFR0tLG/tLXCVfe6dxm	+55 21 98765-4321	Rio de Janeiro	Brazil	\N	Wildlife photographer and eco-tourist capturing the Amazon rainforest, Patagonia, and Pantanal biodiversity.	pt	user	2026-05-22 15:59:58.783218+05:30	2026-08-22 15:59:58.783218+05:30	{"currency": "USD", "travelStyle": "adventure", "publicProfile": true}
9	Maya	Patel	maya.patel@globetrotter.dev	$2y$12$m47nsnXjA8M87Khatc2qseZ6d9TMfML7bAIFR0tLG/tLXCVfe6dxm	+44 7700 900123	London	UK	\N	Solo female backpacker guide author, budget trip hacker, and UNESCO world heritage collector.	en	user	2026-05-22 15:59:58.783218+05:30	2026-08-22 15:59:58.783218+05:30	{"currency": "GBP", "travelStyle": "budget", "publicProfile": true}
1	Admin	User	admin@globetrotter.dev	$2y$12$lwSCbrd5atB8a5w3xXt/uOH64Za.5Im9Xk5oK.NC3f0K0Uf6Dg4Ye	+1 (555) 019-2831	San Francisco	USA	\N	GlobeTrotter platform administrator and avid world explorer. Traveled to 35+ countries across 5 continents.	en	admin	2026-08-22 13:53:56.564146+05:30	2026-08-22 15:59:58.783218+05:30	{"currency": "USD", "dateFormat": "MM/DD/YYYY", "tripAlerts": true, "emailDigest": true, "travelStyle": "adventure", "publicProfile": true, "shareActivity": true, "marketingEmails": false, "communityUpdates": true}
2	Alex	Traveler	traveler@globetrotter.dev	$2y$12$knJTQlDmFJehC6F.6scI7OByDrxUYgjOjiL65dx6Pyvr8siKLc4rq	+1 (555) 342-8819	New York	USA	\N	Travel photographer, coffee connoisseur, and cultural backpacker always looking for hidden gems and local culinary secrets.	en	user	2026-08-22 13:59:10.957174+05:30	2026-08-22 15:59:58.783218+05:30	{"currency": "USD", "dateFormat": "YYYY-MM-DD", "tripAlerts": true, "emailDigest": true, "travelStyle": "cultural", "publicProfile": true, "shareActivity": true, "marketingEmails": true, "communityUpdates": true}
3	Elena	Rostova	elena.rostova@wanderlust.io	$2y$12$m47nsnXjA8M87Khatc2qseZ6d9TMfML7bAIFR0tLG/tLXCVfe6dxm	+33 6 12 34 56 78	Paris	France	\N	Art historian and food critic specializing in European museum tours and Michelin-starred culinary adventures.	fr	user	2026-05-22 15:59:58.783218+05:30	2026-08-22 15:59:58.783218+05:30	{"currency": "EUR", "travelStyle": "luxury", "publicProfile": true}
4	Kenji	Sato	kenji.sato@tokyotravels.jp	$2y$12$m47nsnXjA8M87Khatc2qseZ6d9TMfML7bAIFR0tLG/tLXCVfe6dxm	+81 90-1234-5678	Tokyo	Japan	\N	Tech enthusiast, street food addict, and mountain trekker documenting modern Asian architecture.	ja	user	2026-05-22 15:59:58.783218+05:30	2026-08-22 15:59:58.783218+05:30	{"currency": "JPY", "travelStyle": "budget", "publicProfile": true}
5	Sofia	Rodriguez	sofia.r@viajeros.com	$2y$12$m47nsnXjA8M87Khatc2qseZ6d9TMfML7bAIFR0tLG/tLXCVfe6dxm	+34 612 987 654	Barcelona	Spain	\N	Certified scuba divemaster and environmentalist exploring coastal marine sanctuaries worldwide.	es	user	2026-05-22 15:59:58.783218+05:30	2026-08-22 15:59:58.783218+05:30	{"currency": "EUR", "travelStyle": "adventure", "publicProfile": true}
6	Liam	O'Connor	liam.oc@globetrotter.dev	$2y$12$m47nsnXjA8M87Khatc2qseZ6d9TMfML7bAIFR0tLG/tLXCVfe6dxm	+61 412 345 678	Sydney	Australia	\N	Outdoor adventurer, surfer, and camper passionate about Australia, New Zealand, and Pacific island hopping.	en	user	2026-05-22 15:59:58.783218+05:30	2026-08-22 15:59:58.783218+05:30	{"currency": "AUD", "travelStyle": "adventure", "publicProfile": true}
7	Amina	Al-Mansoor	amina.m@desertroutes.ae	$2y$12$m47nsnXjA8M87Khatc2qseZ6d9TMfML7bAIFR0tLG/tLXCVfe6dxm	+971 50 123 4567	Dubai	UAE	\N	Boutique hotel designer and luxury travel creator uncovering Arabian heritage and desert glamping escapes.	ar	user	2026-05-22 15:59:58.783218+05:30	2026-08-22 15:59:58.783218+05:30	{"currency": "USD", "travelStyle": "luxury", "publicProfile": true}
10	Lucas	Weber	lucas.w@alpineroads.de	$2y$12$m47nsnXjA8M87Khatc2qseZ6d9TMfML7bAIFR0tLG/tLXCVfe6dxm	+49 151 23456789	Munich	Germany	\N	Road trip enthusiast and Alpine climber reviewing scenic driving routes and panoramic huts.	de	user	2026-05-22 15:59:58.783218+05:30	2026-08-22 15:59:58.783218+05:30	{"currency": "EUR", "travelStyle": "adventure", "publicProfile": true}
\.


--
-- Name: activities_id_seq; Type: SEQUENCE SET; Schema: public; Owner: snehapatel
--

SELECT pg_catalog.setval('public.activities_id_seq', 47, true);


--
-- Name: budget_items_id_seq; Type: SEQUENCE SET; Schema: public; Owner: snehapatel
--

SELECT pg_catalog.setval('public.budget_items_id_seq', 19, true);


--
-- Name: cities_id_seq; Type: SEQUENCE SET; Schema: public; Owner: snehapatel
--

SELECT pg_catalog.setval('public.cities_id_seq', 22, true);


--
-- Name: community_likes_id_seq; Type: SEQUENCE SET; Schema: public; Owner: snehapatel
--

SELECT pg_catalog.setval('public.community_likes_id_seq', 15, true);


--
-- Name: community_posts_id_seq; Type: SEQUENCE SET; Schema: public; Owner: snehapatel
--

SELECT pg_catalog.setval('public.community_posts_id_seq', 8, true);


--
-- Name: saved_destinations_id_seq; Type: SEQUENCE SET; Schema: public; Owner: snehapatel
--

SELECT pg_catalog.setval('public.saved_destinations_id_seq', 10, true);


--
-- Name: trip_activities_id_seq; Type: SEQUENCE SET; Schema: public; Owner: snehapatel
--

SELECT pg_catalog.setval('public.trip_activities_id_seq', 29, true);


--
-- Name: trip_budget_id_seq; Type: SEQUENCE SET; Schema: public; Owner: snehapatel
--

SELECT pg_catalog.setval('public.trip_budget_id_seq', 10, true);


--
-- Name: trip_stops_id_seq; Type: SEQUENCE SET; Schema: public; Owner: snehapatel
--

SELECT pg_catalog.setval('public.trip_stops_id_seq', 18, true);


--
-- Name: trips_id_seq; Type: SEQUENCE SET; Schema: public; Owner: snehapatel
--

SELECT pg_catalog.setval('public.trips_id_seq', 10, true);


--
-- Name: users_id_seq; Type: SEQUENCE SET; Schema: public; Owner: snehapatel
--

SELECT pg_catalog.setval('public.users_id_seq', 10, true);


--
-- Name: activities activities_pkey; Type: CONSTRAINT; Schema: public; Owner: snehapatel
--

ALTER TABLE ONLY public.activities
    ADD CONSTRAINT activities_pkey PRIMARY KEY (id);


--
-- Name: budget_items budget_items_pkey; Type: CONSTRAINT; Schema: public; Owner: snehapatel
--

ALTER TABLE ONLY public.budget_items
    ADD CONSTRAINT budget_items_pkey PRIMARY KEY (id);


--
-- Name: cities cities_pkey; Type: CONSTRAINT; Schema: public; Owner: snehapatel
--

ALTER TABLE ONLY public.cities
    ADD CONSTRAINT cities_pkey PRIMARY KEY (id);


--
-- Name: community_likes community_likes_pkey; Type: CONSTRAINT; Schema: public; Owner: snehapatel
--

ALTER TABLE ONLY public.community_likes
    ADD CONSTRAINT community_likes_pkey PRIMARY KEY (id);


--
-- Name: community_posts community_posts_pkey; Type: CONSTRAINT; Schema: public; Owner: snehapatel
--

ALTER TABLE ONLY public.community_posts
    ADD CONSTRAINT community_posts_pkey PRIMARY KEY (id);


--
-- Name: saved_destinations saved_destinations_pkey; Type: CONSTRAINT; Schema: public; Owner: snehapatel
--

ALTER TABLE ONLY public.saved_destinations
    ADD CONSTRAINT saved_destinations_pkey PRIMARY KEY (id);


--
-- Name: trip_activities trip_activities_pkey; Type: CONSTRAINT; Schema: public; Owner: snehapatel
--

ALTER TABLE ONLY public.trip_activities
    ADD CONSTRAINT trip_activities_pkey PRIMARY KEY (id);


--
-- Name: trip_budget trip_budget_pkey; Type: CONSTRAINT; Schema: public; Owner: snehapatel
--

ALTER TABLE ONLY public.trip_budget
    ADD CONSTRAINT trip_budget_pkey PRIMARY KEY (id);


--
-- Name: trip_budget trip_budget_trip_id_key; Type: CONSTRAINT; Schema: public; Owner: snehapatel
--

ALTER TABLE ONLY public.trip_budget
    ADD CONSTRAINT trip_budget_trip_id_key UNIQUE (trip_id);


--
-- Name: trip_stops trip_stops_pkey; Type: CONSTRAINT; Schema: public; Owner: snehapatel
--

ALTER TABLE ONLY public.trip_stops
    ADD CONSTRAINT trip_stops_pkey PRIMARY KEY (id);


--
-- Name: trips trips_pkey; Type: CONSTRAINT; Schema: public; Owner: snehapatel
--

ALTER TABLE ONLY public.trips
    ADD CONSTRAINT trips_pkey PRIMARY KEY (id);


--
-- Name: trips trips_share_slug_key; Type: CONSTRAINT; Schema: public; Owner: snehapatel
--

ALTER TABLE ONLY public.trips
    ADD CONSTRAINT trips_share_slug_key UNIQUE (share_slug);


--
-- Name: community_likes unique_post_user_like; Type: CONSTRAINT; Schema: public; Owner: snehapatel
--

ALTER TABLE ONLY public.community_likes
    ADD CONSTRAINT unique_post_user_like UNIQUE (post_id, user_id);


--
-- Name: saved_destinations unique_user_saved_city; Type: CONSTRAINT; Schema: public; Owner: snehapatel
--

ALTER TABLE ONLY public.saved_destinations
    ADD CONSTRAINT unique_user_saved_city UNIQUE (user_id, city_id);


--
-- Name: users users_email_key; Type: CONSTRAINT; Schema: public; Owner: snehapatel
--

ALTER TABLE ONLY public.users
    ADD CONSTRAINT users_email_key UNIQUE (email);


--
-- Name: users users_pkey; Type: CONSTRAINT; Schema: public; Owner: snehapatel
--

ALTER TABLE ONLY public.users
    ADD CONSTRAINT users_pkey PRIMARY KEY (id);


--
-- Name: idx_activities_category; Type: INDEX; Schema: public; Owner: snehapatel
--

CREATE INDEX idx_activities_category ON public.activities USING btree (category);


--
-- Name: idx_activities_city; Type: INDEX; Schema: public; Owner: snehapatel
--

CREATE INDEX idx_activities_city ON public.activities USING btree (city_id);


--
-- Name: idx_activities_cost; Type: INDEX; Schema: public; Owner: snehapatel
--

CREATE INDEX idx_activities_cost ON public.activities USING btree (cost);


--
-- Name: idx_budget_items_trip; Type: INDEX; Schema: public; Owner: snehapatel
--

CREATE INDEX idx_budget_items_trip ON public.budget_items USING btree (trip_id);


--
-- Name: idx_cities_country; Type: INDEX; Schema: public; Owner: snehapatel
--

CREATE INDEX idx_cities_country ON public.cities USING btree (country);


--
-- Name: idx_cities_name; Type: INDEX; Schema: public; Owner: snehapatel
--

CREATE INDEX idx_cities_name ON public.cities USING btree (name);


--
-- Name: idx_cities_popularity; Type: INDEX; Schema: public; Owner: snehapatel
--

CREATE INDEX idx_cities_popularity ON public.cities USING btree (popularity_score DESC);


--
-- Name: idx_trip_activities_activity; Type: INDEX; Schema: public; Owner: snehapatel
--

CREATE INDEX idx_trip_activities_activity ON public.trip_activities USING btree (activity_id);


--
-- Name: idx_trip_activities_stop; Type: INDEX; Schema: public; Owner: snehapatel
--

CREATE INDEX idx_trip_activities_stop ON public.trip_activities USING btree (trip_stop_id, scheduled_date);


--
-- Name: idx_trip_stops_city; Type: INDEX; Schema: public; Owner: snehapatel
--

CREATE INDEX idx_trip_stops_city ON public.trip_stops USING btree (city_id);


--
-- Name: idx_trip_stops_trip; Type: INDEX; Schema: public; Owner: snehapatel
--

CREATE INDEX idx_trip_stops_trip ON public.trip_stops USING btree (trip_id, order_index);


--
-- Name: idx_trips_share_slug; Type: INDEX; Schema: public; Owner: snehapatel
--

CREATE INDEX idx_trips_share_slug ON public.trips USING btree (share_slug);


--
-- Name: idx_trips_user; Type: INDEX; Schema: public; Owner: snehapatel
--

CREATE INDEX idx_trips_user ON public.trips USING btree (user_id);


--
-- Name: idx_users_email; Type: INDEX; Schema: public; Owner: snehapatel
--

CREATE INDEX idx_users_email ON public.users USING btree (email);


--
-- Name: community_posts trg_community_posts_updated_at; Type: TRIGGER; Schema: public; Owner: snehapatel
--

CREATE TRIGGER trg_community_posts_updated_at BEFORE UPDATE ON public.community_posts FOR EACH ROW EXECUTE FUNCTION public.set_updated_at();


--
-- Name: trip_stops trg_trip_stops_updated_at; Type: TRIGGER; Schema: public; Owner: snehapatel
--

CREATE TRIGGER trg_trip_stops_updated_at BEFORE UPDATE ON public.trip_stops FOR EACH ROW EXECUTE FUNCTION public.set_updated_at();


--
-- Name: trips trg_trips_updated_at; Type: TRIGGER; Schema: public; Owner: snehapatel
--

CREATE TRIGGER trg_trips_updated_at BEFORE UPDATE ON public.trips FOR EACH ROW EXECUTE FUNCTION public.set_updated_at();


--
-- Name: users trg_users_updated_at; Type: TRIGGER; Schema: public; Owner: snehapatel
--

CREATE TRIGGER trg_users_updated_at BEFORE UPDATE ON public.users FOR EACH ROW EXECUTE FUNCTION public.set_updated_at();


--
-- Name: activities activities_city_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: snehapatel
--

ALTER TABLE ONLY public.activities
    ADD CONSTRAINT activities_city_id_fkey FOREIGN KEY (city_id) REFERENCES public.cities(id) ON DELETE CASCADE;


--
-- Name: budget_items budget_items_stop_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: snehapatel
--

ALTER TABLE ONLY public.budget_items
    ADD CONSTRAINT budget_items_stop_id_fkey FOREIGN KEY (stop_id) REFERENCES public.trip_stops(id) ON DELETE CASCADE;


--
-- Name: budget_items budget_items_trip_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: snehapatel
--

ALTER TABLE ONLY public.budget_items
    ADD CONSTRAINT budget_items_trip_id_fkey FOREIGN KEY (trip_id) REFERENCES public.trips(id) ON DELETE CASCADE;


--
-- Name: community_likes community_likes_post_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: snehapatel
--

ALTER TABLE ONLY public.community_likes
    ADD CONSTRAINT community_likes_post_id_fkey FOREIGN KEY (post_id) REFERENCES public.community_posts(id) ON DELETE CASCADE;


--
-- Name: community_likes community_likes_user_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: snehapatel
--

ALTER TABLE ONLY public.community_likes
    ADD CONSTRAINT community_likes_user_id_fkey FOREIGN KEY (user_id) REFERENCES public.users(id) ON DELETE CASCADE;


--
-- Name: community_posts community_posts_trip_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: snehapatel
--

ALTER TABLE ONLY public.community_posts
    ADD CONSTRAINT community_posts_trip_id_fkey FOREIGN KEY (trip_id) REFERENCES public.trips(id) ON DELETE SET NULL;


--
-- Name: community_posts community_posts_user_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: snehapatel
--

ALTER TABLE ONLY public.community_posts
    ADD CONSTRAINT community_posts_user_id_fkey FOREIGN KEY (user_id) REFERENCES public.users(id) ON DELETE CASCADE;


--
-- Name: saved_destinations saved_destinations_city_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: snehapatel
--

ALTER TABLE ONLY public.saved_destinations
    ADD CONSTRAINT saved_destinations_city_id_fkey FOREIGN KEY (city_id) REFERENCES public.cities(id) ON DELETE CASCADE;


--
-- Name: saved_destinations saved_destinations_user_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: snehapatel
--

ALTER TABLE ONLY public.saved_destinations
    ADD CONSTRAINT saved_destinations_user_id_fkey FOREIGN KEY (user_id) REFERENCES public.users(id) ON DELETE CASCADE;


--
-- Name: trip_activities trip_activities_activity_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: snehapatel
--

ALTER TABLE ONLY public.trip_activities
    ADD CONSTRAINT trip_activities_activity_id_fkey FOREIGN KEY (activity_id) REFERENCES public.activities(id) ON DELETE RESTRICT;


--
-- Name: trip_activities trip_activities_trip_stop_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: snehapatel
--

ALTER TABLE ONLY public.trip_activities
    ADD CONSTRAINT trip_activities_trip_stop_id_fkey FOREIGN KEY (trip_stop_id) REFERENCES public.trip_stops(id) ON DELETE CASCADE;


--
-- Name: trip_budget trip_budget_trip_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: snehapatel
--

ALTER TABLE ONLY public.trip_budget
    ADD CONSTRAINT trip_budget_trip_id_fkey FOREIGN KEY (trip_id) REFERENCES public.trips(id) ON DELETE CASCADE;


--
-- Name: trip_stops trip_stops_city_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: snehapatel
--

ALTER TABLE ONLY public.trip_stops
    ADD CONSTRAINT trip_stops_city_id_fkey FOREIGN KEY (city_id) REFERENCES public.cities(id) ON DELETE RESTRICT;


--
-- Name: trip_stops trip_stops_trip_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: snehapatel
--

ALTER TABLE ONLY public.trip_stops
    ADD CONSTRAINT trip_stops_trip_id_fkey FOREIGN KEY (trip_id) REFERENCES public.trips(id) ON DELETE CASCADE;


--
-- Name: trips trips_user_id_fkey; Type: FK CONSTRAINT; Schema: public; Owner: snehapatel
--

ALTER TABLE ONLY public.trips
    ADD CONSTRAINT trips_user_id_fkey FOREIGN KEY (user_id) REFERENCES public.users(id) ON DELETE CASCADE;


--
-- PostgreSQL database dump complete
--

\unrestrict ubRfDAGifdPQtfVaEweGxE1ddFd6tejjP2RLyGmTJsD5sQtLeNAAGGk6C0G88CL

