<?php
/**
 * Comprehensive Database Seeder for GlobeTrotter
 * Populates realistic data across all tables:
 * users, cities, activities, trips, trip_stops, trip_activities,
 * trip_budget, budget_items, community_posts, community_likes, saved_destinations.
 */

require_once __DIR__ . '/../includes/db.php';

echo "=== STARTING GLOBETROTTER COMPREHENSIVE SEEDER ===\n";

try {
    $pdo->beginTransaction();

    // ─────────────────────────────────────────────────────────────────────────────
    // 1. SEED USERS
    // ─────────────────────────────────────────────────────────────────────────────
    echo "1. Seeding Users...\n";
    $defaultPasswordHash = password_hash('password123', PASSWORD_DEFAULT);

    $usersData = [
        [
            'id' => 1,
            'email' => 'admin@globetrotter.dev',
            'first_name' => 'Admin',
            'last_name' => 'User',
            'role' => 'admin',
            'phone' => '+1 (555) 019-2831',
            'city' => 'San Francisco',
            'country' => 'USA',
            'additional_info' => 'GlobeTrotter platform administrator and avid world explorer. Traveled to 35+ countries across 5 continents.',
            'language_pref' => 'en',
            'preferences' => json_encode([
                'currency' => 'USD',
                'dateFormat' => 'MM/DD/YYYY',
                'travelStyle' => 'adventure',
                'emailDigest' => true,
                'tripAlerts' => true,
                'communityUpdates' => true,
                'marketingEmails' => false,
                'publicProfile' => true,
                'shareActivity' => true
            ])
        ],
        [
            'id' => 2,
            'email' => 'traveler@globetrotter.dev',
            'first_name' => 'Alex',
            'last_name' => 'Traveler',
            'role' => 'user',
            'phone' => '+1 (555) 342-8819',
            'city' => 'New York',
            'country' => 'USA',
            'additional_info' => 'Travel photographer, coffee connoisseur, and cultural backpacker always looking for hidden gems and local culinary secrets.',
            'language_pref' => 'en',
            'preferences' => json_encode([
                'currency' => 'USD',
                'dateFormat' => 'YYYY-MM-DD',
                'travelStyle' => 'cultural',
                'emailDigest' => true,
                'tripAlerts' => true,
                'communityUpdates' => true,
                'marketingEmails' => true,
                'publicProfile' => true,
                'shareActivity' => true
            ])
        ],
        [
            'id' => 3,
            'email' => 'elena.rostova@wanderlust.io',
            'first_name' => 'Elena',
            'last_name' => 'Rostova',
            'role' => 'user',
            'phone' => '+33 6 12 34 56 78',
            'city' => 'Paris',
            'country' => 'France',
            'additional_info' => 'Art historian and food critic specializing in European museum tours and Michelin-starred culinary adventures.',
            'language_pref' => 'fr',
            'preferences' => json_encode(['currency' => 'EUR', 'travelStyle' => 'luxury', 'publicProfile' => true])
        ],
        [
            'id' => 4,
            'email' => 'kenji.sato@tokyotravels.jp',
            'first_name' => 'Kenji',
            'last_name' => 'Sato',
            'role' => 'user',
            'phone' => '+81 90-1234-5678',
            'city' => 'Tokyo',
            'country' => 'Japan',
            'additional_info' => 'Tech enthusiast, street food addict, and mountain trekker documenting modern Asian architecture.',
            'language_pref' => 'ja',
            'preferences' => json_encode(['currency' => 'JPY', 'travelStyle' => 'budget', 'publicProfile' => true])
        ],
        [
            'id' => 5,
            'email' => 'sofia.r@viajeros.com',
            'first_name' => 'Sofia',
            'last_name' => 'Rodriguez',
            'role' => 'user',
            'phone' => '+34 612 987 654',
            'city' => 'Barcelona',
            'country' => 'Spain',
            'additional_info' => 'Certified scuba divemaster and environmentalist exploring coastal marine sanctuaries worldwide.',
            'language_pref' => 'es',
            'preferences' => json_encode(['currency' => 'EUR', 'travelStyle' => 'adventure', 'publicProfile' => true])
        ],
        [
            'id' => 6,
            'email' => 'liam.oc@globetrotter.dev',
            'first_name' => 'Liam',
            'last_name' => 'O\'Connor',
            'role' => 'user',
            'phone' => '+61 412 345 678',
            'city' => 'Sydney',
            'country' => 'Australia',
            'additional_info' => 'Outdoor adventurer, surfer, and camper passionate about Australia, New Zealand, and Pacific island hopping.',
            'language_pref' => 'en',
            'preferences' => json_encode(['currency' => 'AUD', 'travelStyle' => 'adventure', 'publicProfile' => true])
        ],
        [
            'id' => 7,
            'email' => 'amina.m@desertroutes.ae',
            'first_name' => 'Amina',
            'last_name' => 'Al-Mansoor',
            'role' => 'user',
            'phone' => '+971 50 123 4567',
            'city' => 'Dubai',
            'country' => 'UAE',
            'additional_info' => 'Boutique hotel designer and luxury travel creator uncovering Arabian heritage and desert glamping escapes.',
            'language_pref' => 'ar',
            'preferences' => json_encode(['currency' => 'USD', 'travelStyle' => 'luxury', 'publicProfile' => true])
        ],
        [
            'id' => 8,
            'email' => 'mateo.silva@andesexp.br',
            'first_name' => 'Mateo',
            'last_name' => 'Silva',
            'role' => 'user',
            'phone' => '+55 21 98765-4321',
            'city' => 'Rio de Janeiro',
            'country' => 'Brazil',
            'additional_info' => 'Wildlife photographer and eco-tourist capturing the Amazon rainforest, Patagonia, and Pantanal biodiversity.',
            'language_pref' => 'pt',
            'preferences' => json_encode(['currency' => 'USD', 'travelStyle' => 'adventure', 'publicProfile' => true])
        ],
        [
            'id' => 9,
            'email' => 'maya.patel@globetrotter.dev',
            'first_name' => 'Maya',
            'last_name' => 'Patel',
            'role' => 'user',
            'phone' => '+44 7700 900123',
            'city' => 'London',
            'country' => 'UK',
            'additional_info' => 'Solo female backpacker guide author, budget trip hacker, and UNESCO world heritage collector.',
            'language_pref' => 'en',
            'preferences' => json_encode(['currency' => 'GBP', 'travelStyle' => 'budget', 'publicProfile' => true])
        ],
        [
            'id' => 10,
            'email' => 'lucas.w@alpineroads.de',
            'first_name' => 'Lucas',
            'last_name' => 'Weber',
            'role' => 'user',
            'phone' => '+49 151 23456789',
            'city' => 'Munich',
            'country' => 'Germany',
            'additional_info' => 'Road trip enthusiast and Alpine climber reviewing scenic driving routes and panoramic huts.',
            'language_pref' => 'de',
            'preferences' => json_encode(['currency' => 'EUR', 'travelStyle' => 'adventure', 'publicProfile' => true])
        ]
    ];

    foreach ($usersData as $u) {
        $stmt = $pdo->prepare('
            INSERT INTO users (id, email, password_hash, first_name, last_name, role, phone, city, country, additional_info, language_pref, preferences, created_at, updated_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?::jsonb, NOW() - INTERVAL \'3 months\', NOW())
            ON CONFLICT (id) DO UPDATE SET
                email = EXCLUDED.email,
                first_name = EXCLUDED.first_name,
                last_name = EXCLUDED.last_name,
                role = EXCLUDED.role,
                phone = EXCLUDED.phone,
                city = EXCLUDED.city,
                country = EXCLUDED.country,
                additional_info = EXCLUDED.additional_info,
                language_pref = EXCLUDED.language_pref,
                preferences = EXCLUDED.preferences
        ');
        $stmt->execute([
            $u['id'], $u['email'], $defaultPasswordHash, $u['first_name'], $u['last_name'],
            $u['role'], $u['phone'], $u['city'], $u['country'], $u['additional_info'],
            $u['language_pref'], $u['preferences']
        ]);
    }
    $pdo->exec("SELECT setval('users_id_seq', (SELECT MAX(id) FROM users))");

    // ─────────────────────────────────────────────────────────────────────────────
    // 2. SEED CITIES (22 Iconic Global Destinations)
    // ─────────────────────────────────────────────────────────────────────────────
    echo "2. Seeding Cities...\n";
    $citiesData = [
        [1, 'Paris', 'France', 'Europe', 78.50, 98, 'The City of Light, celebrated for world-class haute cuisine, iconic art museums like the Louvre, and romantic Seine promenades.', 'https://images.unsplash.com/photo-1502602898657-3e91760cbb34?w=800&q=80'],
        [2, 'Tokyo', 'Japan', 'Asia', 72.00, 95, 'A mesmerizing metropolis where hyper-modern neon skyscrapers seamlessly merge with serene historic Shinto shrines and legendary cuisine.', 'https://images.unsplash.com/photo-1503899036084-c55cdd92da26?w=800&q=80'],
        [3, 'Bali', 'Indonesia', 'Asia', 35.00, 90, 'The Island of the Gods, renowned for emerald tiered rice terraces, sacred sea temples, coral reefs, and tranquil wellness retreats.', 'https://images.unsplash.com/photo-1537996194471-e657df975ab4?w=800&q=80'],
        [4, 'New York', 'USA', 'North America', 95.00, 92, 'The premier global epicenter of culture, arts, Broadway theater, world dining, and iconic skyscraper skylines.', 'https://images.unsplash.com/photo-1496442226666-8d4d0e62e6e9?w=800&q=80'],
        [5, 'Rome', 'Italy', 'Europe', 70.00, 89, 'The Eternal City, a living open-air museum filled with millennia of ancient history, magnificent piazzas, and vibrant trattorias.', 'https://images.unsplash.com/photo-1552832230-c0197dd311b5?w=800&q=80'],
        [6, 'Barcelona', 'Spain', 'Europe', 60.00, 88, 'A sun-drenched Mediterranean jewel featuring Gaudí\'s surreal architecture, lively tapas bars, and golden urban beaches.', 'https://images.unsplash.com/photo-1583422409516-2895a77efded?w=800&q=80'],
        [7, 'Bangkok', 'Thailand', 'Asia', 28.00, 85, 'A high-energy tropical capital of ornate golden palaces, bustling floating markets, and world-renowned street food.', 'https://images.unsplash.com/photo-1508009603885-50cf7c579365?w=800&q=80'],
        [8, 'Cape Town', 'South Africa', 'Africa', 45.00, 75, 'A breathtaking coastal wonder dominated by Table Mountain, penguin-filled beaches, and historic rolling vineyards.', 'https://images.unsplash.com/photo-1580618672591-eb180b1a973f?w=800&q=80'],
        [9, 'London', 'United Kingdom', 'Europe', 88.00, 94, 'A historic global hub of royal landmarks, West End theater productions, sprawling royal parks, and cutting-edge culture.', 'https://images.unsplash.com/photo-1513635269975-59663e0ac1ad?w=800&q=80'],
        [10, 'Sydney', 'Australia', 'Oceania', 82.00, 87, 'A world-famous harbor city blessed with the Opera House, golden surf beaches like Bondi, and pristine coastal parklands.', 'https://images.unsplash.com/photo-1506973035872-a4ec16b8e8d9?w=800&q=80'],
        [11, 'Dubai', 'UAE', 'Asia', 90.00, 91, 'An opulent desert oasis of futuristic engineering, ultra-luxury shopping resorts, desert safaris, and indoor wonderlands.', 'https://images.unsplash.com/photo-1512453979798-5ea266f8880c?w=800&q=80'],
        [12, 'Rio de Janeiro', 'Brazil', 'South America', 42.00, 81, 'The Marvelous City, famed for Christ the Redeemer atop Corcovado, vibrant Copacabana sands, and infectious samba rhythms.', 'https://images.unsplash.com/photo-1483729558449-99ef09a8c325?w=800&q=80'],
        [13, 'Kyoto', 'Japan', 'Asia', 65.00, 89, 'Japan\'s cultural soul, home to thousands of classical Buddhist temples, Zen gardens, bamboo groves, and traditional geishas.', 'https://images.unsplash.com/photo-1493976040374-85c8e12f0c0e?w=800&q=80'],
        [14, 'Amsterdam', 'Netherlands', 'Europe', 75.00, 86, 'A picturesque network of historic canals, world-class art collections, historic canal houses, and vibrant cycling culture.', 'https://images.unsplash.com/photo-1534351590666-13e3e96b5017?w=800&q=80'],
        [15, 'Cairo', 'Egypt', 'Africa', 32.00, 80, 'The monumental Cradle of Civilization with the Great Pyramids of Giza, the Nile River, and historic labyrinthine bazaars.', 'https://images.unsplash.com/photo-1572252009286-268acec5ca0a?w=800&q=80'],
        [16, 'Reykjavik', 'Iceland', 'Europe', 110.00, 83, 'Gateway to otherworldly glacial lagoons, roaring geothermal geysers, volcanic hot springs, and ethereal Northern Lights.', 'https://images.unsplash.com/photo-1504893524553-b855bce32c67?w=800&q=80'],
        [17, 'Singapore', 'Singapore', 'Asia', 85.00, 91, 'A breathtaking futuristic garden city offering architectural wonders like Marina Bay Sands, supertrees, and diverse street cuisine.', 'https://images.unsplash.com/photo-1525625293386-3f8f99389edd?w=800&q=80'],
        [18, 'Prague', 'Czech Republic', 'Europe', 48.00, 84, 'The City of a Hundred Spires, celebrated for its Gothic castle, Charles Bridge street performers, and rich brewing history.', 'https://images.unsplash.com/photo-1541849546-216549ae216d?w=800&q=80'],
        [19, 'Marrakech', 'Morocco', 'Africa', 38.00, 79, 'An intoxicating sensory wonder of terracotta medinas, vibrant spice souks, Moorish riads, and palm-studded gardens.', 'https://images.unsplash.com/photo-1597212618440-806262de4f6b?w=800&q=80'],
        [20, 'Vancouver', 'Canada', 'North America', 76.00, 82, 'A majestic coastal Pacific city nestled between snow-capped coastal mountains and scenic ocean inlets.', 'https://images.unsplash.com/photo-1559511260-66a65e09b245?w=800&q=80'],
        [21, 'Queenstown', 'New Zealand', 'Oceania', 80.00, 85, 'The Adventure Capital of the World, situated along Lake Wakatipu with world-class skiing, jet boating, and fjord trekking.', 'https://images.unsplash.com/photo-1589871973318-9ca1258faa5d?w=800&q=80'],
        [22, 'Buenos Aires', 'Argentina', 'South America', 36.00, 78, 'The Paris of South America, vibrating with passionate tango performances, grand European architecture, and world-class steakhouses.', 'https://images.unsplash.com/photo-1589909202802-8f4aadce1849?w=800&q=80']
    ];

    foreach ($citiesData as $c) {
        $stmt = $pdo->prepare('
            INSERT INTO cities (id, name, country, region, cost_index, popularity_score, description, image_url, created_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW() - INTERVAL \'6 months\')
            ON CONFLICT (id) DO UPDATE SET
                name = EXCLUDED.name,
                country = EXCLUDED.country,
                region = EXCLUDED.region,
                cost_index = EXCLUDED.cost_index,
                popularity_score = EXCLUDED.popularity_score,
                description = EXCLUDED.description,
                image_url = EXCLUDED.image_url
        ');
        $stmt->execute($c);
    }
    $pdo->exec("SELECT setval('cities_id_seq', (SELECT MAX(id) FROM cities))");

    // ─────────────────────────────────────────────────────────────────────────────
    // 3. SEED ACTIVITIES (50+ Diverse Activities)
    // ─────────────────────────────────────────────────────────────────────────────
    echo "3. Seeding Activities...\n";
    $activitiesData = [
        // Paris (City 1)
        [1, 1, 'Eiffel Tower Summit Visit', 'Skip-the-line summit elevator access with panoramic 360-degree views across Paris and champagne bar.', 'sightseeing', 45.00, 2.0, 'https://images.unsplash.com/photo-1511739001486-6bfe10ce785f?w=600&q=80'],
        [2, 1, 'Louvre Museum Guided Tour', 'Explore masterworks including Mona Lisa, Venus de Milo, and Winged Victory with an art historian.', 'culture', 35.00, 3.0, 'https://images.unsplash.com/photo-1499856871958-5b9627545d1a?w=600&q=80'],
        [3, 1, 'Seine River Sunset Cruise', 'Relaxing glass-canopy boat cruise along the Seine featuring historical audio commentary and wine.', 'relaxation', 25.00, 1.5, 'https://images.unsplash.com/photo-1502602898657-3e91760cbb34?w=600&q=80'],
        [4, 1, 'French Macaron & Pastry Masterclass', 'Hands-on pastry workshop learning the delicate art of crafting authentic Parisian macarons.', 'food', 65.00, 2.5, 'https://images.unsplash.com/photo-1569864358642-9d1684040f43?w=600&q=80'],

        // Tokyo (City 2)
        [5, 2, 'Shibuya Food & Izakaya Crawl', 'Taste Yakitori, Sashimi, and craft Sake guided through hidden alleyways of Omoide Yokocho.', 'food', 40.00, 3.0, 'https://images.unsplash.com/photo-1503899036084-c55cdd92da26?w=600&q=80'],
        [6, 2, 'TeamLab Planets Digital Art Museum', 'Immersive sensory body installation walking through water and floating floral digital gardens.', 'culture', 32.00, 2.0, 'https://images.unsplash.com/photo-1542051841857-5f90071e7989?w=600&q=80'],
        [7, 2, 'Mount Fuji & Lake Kawaguchi Day Tour', 'Panoramic excursion to Fuji 5th Station, scenic ropeway, and traditional Oshino Hakkai village.', 'adventure', 85.00, 8.0, 'https://images.unsplash.com/photo-1490806843957-31f4c9a91c65?w=600&q=80'],
        [8, 2, 'Hakone Onsen Thermal Relaxation', 'Traditional Japanese hot spring bath experience overlooking forested mountain valleys.', 'relaxation', 45.00, 4.0, 'https://images.unsplash.com/photo-1503899036084-c55cdd92da26?w=600&q=80'],

        // Bali (City 3)
        [9, 3, 'Balinese Traditional Cooking Class', 'Market tour and authentic farm-to-table cooking workshop preparing satay and sambal.', 'food', 30.00, 3.0, 'https://images.unsplash.com/photo-1537996194471-e657df975ab4?w=600&q=80'],
        [10, 3, 'Ubud Rice Terrace & Jungle Swing Trek', 'Trek through emerald Tegallalang rice paddies followed by high-altitude jungle swings.', 'adventure', 20.00, 4.0, 'https://images.unsplash.com/photo-1518548419970-58e3b4079ab2?w=600&q=80'],
        [11, 3, 'Nusa Penida Snorkel & Manta Ray Tour', 'Speedboat day trip snorkeling with giant Manta Rays and viewing Kelingking T-Rex cliff.', 'adventure', 65.00, 7.0, 'https://images.unsplash.com/photo-1544644181-1484b3fdfc62?w=600&q=80'],
        [12, 3, 'Uluwatu Sunset Temple & Kecak Dance', 'Dramatic cliffside temple sunset accompanied by mesmerizing traditional fire dance performance.', 'culture', 25.00, 3.0, 'https://images.unsplash.com/photo-1537996194471-e657df975ab4?w=600&q=80'],

        // New York (City 4)
        [13, 4, 'Broadway Musical Evening Show', 'Premium orchestra seating for award-winning theater performances in Times Square.', 'culture', 120.00, 2.5, 'https://images.unsplash.com/photo-1518391846015-55a9cc003b25?w=600&q=80'],
        [14, 4, 'Central Park Guided Bike & Rowboat Tour', 'Cycle through scenic avenues, Strawberry Fields, and row vintage boats on Central Park Lake.', 'sightseeing', 28.00, 2.0, 'https://images.unsplash.com/photo-1538688525198-9b88f6f53126?w=600&q=80'],
        [15, 4, 'Statue of Liberty & Ellis Island Ferry', 'Round-trip harbor cruise with pedestal access and audio tour of immigration history.', 'sightseeing', 30.00, 3.5, 'https://images.unsplash.com/photo-1496442226666-8d4d0e62e6e9?w=600&q=80'],
        [16, 4, 'Greenwich Village Food & Pizza Walk', 'Tasting tour through New York\'s best historic pizzerias, bagel shops, and cannoli bakeries.', 'food', 55.00, 3.0, 'https://images.unsplash.com/photo-1513104890138-7c749659a591?w=600&q=80'],

        // Rome (City 5)
        [17, 5, 'Colosseum & Roman Forum Underground Tour', 'Exclusive gladiator gate access, arena floor walk, and ruins of the ancient Roman Empire.', 'culture', 50.00, 3.0, 'https://images.unsplash.com/photo-1552832230-c0197dd311b5?w=600&q=80'],
        [18, 5, 'Vatican Museums & Sistine Chapel', 'Skip-the-line guided exploration of Michelangelo\'s frescoes and St. Peter\'s Basilica.', 'culture', 48.00, 3.5, 'https://images.unsplash.com/photo-1531572753322-ad063cecc140?w=600&q=80'],
        [19, 5, 'Trastevere Sunset Tapas & Wine Walk', 'Authentic evening pasta crawl tasting cacio e pepe and regional Chianti wines.', 'food', 45.00, 2.5, 'https://images.unsplash.com/photo-1555396273-367ea4eb4db5?w=600&q=80'],

        // Barcelona (City 6)
        [20, 6, 'Sagrada Familia Fast-Track Guided Visit', 'Discover Antoni Gaudí\'s uncompleted masterpiece basilica and towering stained glass naves.', 'culture', 38.00, 2.0, 'https://images.unsplash.com/photo-1583422409516-2895a77efded?w=600&q=80'],
        [21, 6, 'Park Güell & Gràcia District Stroll', 'Marvel at vibrant mosaic dragon terraces and bohemian artist plazas overlooking the sea.', 'sightseeing', 22.00, 2.5, 'https://images.unsplash.com/photo-1539037116277-4db20889f2d4?w=600&q=80'],
        [22, 6, 'Tapas & Flamenco Passion Show', 'Intimate live guitar performance paired with Iberian ham, manchego, and sangria.', 'food', 52.00, 3.0, 'https://images.unsplash.com/photo-1515443961218-a51367888e4b?w=600&q=80'],

        // Bangkok (City 7)
        [23, 7, 'Grand Palace & Wat Pho Golden Reclining Buddha', 'Marvel at Thailand\'s sacred emerald Buddha and the birth center of traditional Thai massage.', 'culture', 25.00, 3.0, 'https://images.unsplash.com/photo-1508009603885-50cf7c579365?w=600&q=80'],
        [24, 7, 'Damnoen Saduak Floating Market Longtail Boat', 'Glide through wooden canal markets sampling pad thai and coconut ice cream from boat vendors.', 'food', 35.00, 4.5, 'https://images.unsplash.com/photo-1528181304800-259b08848526?w=600&q=80'],
        [25, 7, 'Chao Phraya Luxury Dinner Cruise', 'Spectacular illuminated river cruise past lit temples with international buffet dinner.', 'relaxation', 40.00, 2.5, 'https://images.unsplash.com/photo-1563492065599-3520f775eeed?w=600&q=80'],

        // Cape Town (City 8)
        [26, 8, 'Table Mountain Cableway & Summit Hike', 'Revolving aerial cable car ride to 1,000m plateau offering dramatic 360-degree ocean views.', 'adventure', 28.00, 3.0, 'https://images.unsplash.com/photo-1580618672591-eb180b1a973f?w=600&q=80'],
        [27, 8, 'Cape Peninsula & Boulders Beach Penguins', 'Scenic Chapman\'s Peak coastal drive visiting the famous African penguin colony.', 'sightseeing', 45.00, 6.0, 'https://images.unsplash.com/photo-1507525428034-b723cf961d3e?w=600&q=80'],
        [28, 8, 'Stellenbosch Wine Estate Tasting Tour', 'Cellar tours and gourmet chocolate & Pinotage pairings across historic Cape Dutch vineyards.', 'food', 60.00, 5.0, 'https://images.unsplash.com/photo-1510812431401-41d2bd2722f3?w=600&q=80'],

        // London (City 9)
        [29, 9, 'Tower of London & Crown Jewels', 'Meet the Yeoman Warders and gaze at historic centuries of British Royal ceremonial regalia.', 'culture', 35.00, 2.5, 'https://images.unsplash.com/photo-1513635269975-59663e0ac1ad?w=600&q=80'],
        [30, 9, 'Westminster & Thames Walking Tour', 'View Big Ben, Houses of Parliament, and Buckingham Palace Changing of the Guard.', 'sightseeing', 20.00, 2.0, 'https://images.unsplash.com/photo-1529655683826-aba9b3e77383?w=600&q=80'],

        // Sydney (City 10)
        [31, 10, 'Sydney Opera House Architecture Tour', 'Step inside the world-famous sails and acoustic concert halls designed by Jørn Utzon.', 'culture', 32.00, 1.5, 'https://images.unsplash.com/photo-1506973035872-a4ec16b8e8d9?w=600&q=80'],
        [32, 10, 'Bondi Beach Beginner Surf Lesson', 'Two-hour wave surfing lesson with licensed instructors on Australia\'s most famous break.', 'adventure', 50.00, 2.0, 'https://images.unsplash.com/photo-1507525428034-b723cf961d3e?w=600&q=80'],

        // Dubai (City 11)
        [33, 11, 'Burj Khalifa At The Top (Levels 124 & 125)', 'Soar to the observatory of the world\'s tallest skyscraper overlooking Dubai fountain.', 'sightseeing', 48.00, 1.5, 'https://images.unsplash.com/photo-1512453979798-5ea266f8880c?w=600&q=80'],
        [34, 11, 'Red Dunes Desert Safari & BBQ Dinner', 'Thrilling 4x4 dune bashing, camel rides, sandboarding, and live Arabian belly dancing.', 'adventure', 65.00, 6.0, 'https://images.unsplash.com/photo-1451187580459-43490279c0fa?w=600&q=80'],

        // Rio (City 12)
        [35, 12, 'Christ the Redeemer & Corcovado Train', 'Historic cogwheel train ride through Tijuca jungle to the iconic 38-meter monument.', 'sightseeing', 30.00, 3.0, 'https://images.unsplash.com/photo-1483729558449-99ef09a8c325?w=600&q=80'],
        [36, 12, 'Sugarloaf Mountain Sunset Cable Car', 'Two-stage glass cableway to Urca and Pão de Açúcar with Copacabana sunset vistas.', 'relaxation', 32.00, 2.5, 'https://images.unsplash.com/photo-1483729558449-99ef09a8c325?w=600&q=80'],

        // Kyoto (City 13)
        [37, 13, 'Fushimi Inari 10,000 Torii Gates Hike', 'Morning trek through iconic vermilion torii paths ascending sacred Mount Inari.', 'sightseeing', 15.00, 3.0, 'https://images.unsplash.com/photo-1493976040374-85c8e12f0c0e?w=600&q=80'],
        [38, 13, 'Traditional Geisha Tea Ceremony & Kimono', 'Experience authentic Zen matcha preparation wearing a traditional silk kimono.', 'culture', 42.00, 2.0, 'https://images.unsplash.com/photo-1503899036084-c55cdd92da26?w=600&q=80'],

        // Amsterdam (City 14)
        [39, 14, 'Rijksmuseum & Van Gogh Masterpieces', 'See Rembrandt\'s Night Watch and Van Gogh\'s Sunflowers with audio expert narration.', 'culture', 36.00, 3.0, 'https://images.unsplash.com/photo-1534351590666-13e3e96b5017?w=600&q=80'],
        [40, 14, 'Historic Canal Belt Open Boat Tour', 'Cruise UNESCO 17th-century canal rings with complimentary Dutch cheeses and craft beer.', 'relaxation', 24.00, 1.5, 'https://images.unsplash.com/photo-1534351590666-13e3e96b5017?w=600&q=80'],

        // Cairo (City 15)
        [41, 15, 'Giza Pyramids & Sphinx Camel Trek', 'Stand before the Great Pyramid of Khufu and ride camels across panoramic Saharan sands.', 'sightseeing', 40.00, 4.0, 'https://images.unsplash.com/photo-1572252009286-268acec5ca0a?w=600&q=80'],

        // Reykjavik (City 16)
        [42, 16, 'Blue Lagoon Geothermal Spa & Silica Mask', 'Bathe in mineral-rich milky cyan thermal waters surrounded by volcanic black lava fields.', 'relaxation', 75.00, 3.5, 'https://images.unsplash.com/photo-1504893524553-b855bce32c67?w=600&q=80'],
        [43, 16, 'Golden Circle & Thingvellir Rift Tour', 'Witness Gullfoss waterfall, Strokkur erupting geyser, and continental tectonic divide.', 'adventure', 68.00, 7.0, 'https://images.unsplash.com/photo-1504893524553-b855bce32c67?w=600&q=80'],

        // Singapore (City 17)
        [44, 17, 'Gardens by the Bay & Cloud Forest Dome', 'Walk the OCBC Skyway amid 50-meter supertrees and indoor tropical waterfall conservatory.', 'sightseeing', 34.00, 2.5, 'https://images.unsplash.com/photo-1525625293386-3f8f99389edd?w=600&q=80'],

        // Prague (City 18)
        [45, 18, 'Prague Castle & St. Vitus Cathedral Tour', 'Wander the historic royal castle courtyards, Golden Lane, and Gothic cathedral stained glass.', 'culture', 22.00, 3.0, 'https://images.unsplash.com/photo-1541849546-216549ae216d?w=600&q=80'],

        // Marrakech (City 19)
        [46, 19, 'Jemaa el-Fnaa Night Market & Souk Tour', 'Navigate bustling spice lanes, artisan leather ateliers, and open-air food stalls.', 'culture', 20.00, 3.0, 'https://images.unsplash.com/photo-1597212618440-806262de4f6b?w=600&q=80'],

        // Queenstown (City 21)
        [47, 21, 'Milford Sound Fjord Cruise Expedition', 'Day tour through majestic alpine tunnels to cruise beneath waterfalls and sheer fjord cliffs.', 'adventure', 110.00, 9.0, 'https://images.unsplash.com/photo-1589871973318-9ca1258faa5d?w=600&q=80']
    ];

    foreach ($activitiesData as $a) {
        $stmt = $pdo->prepare('
            INSERT INTO activities (id, city_id, name, description, category, cost, duration_hours, image_url, created_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW() - INTERVAL \'5 months\')
            ON CONFLICT (id) DO UPDATE SET
                city_id = EXCLUDED.city_id,
                name = EXCLUDED.name,
                description = EXCLUDED.description,
                category = EXCLUDED.category,
                cost = EXCLUDED.cost,
                duration_hours = EXCLUDED.duration_hours,
                image_url = EXCLUDED.image_url
        ');
        $stmt->execute($a);
    }
    $pdo->exec("SELECT setval('activities_id_seq', (SELECT MAX(id) FROM activities))");

    // ─────────────────────────────────────────────────────────────────────────────
    // 4. SEED TRIPS (10 Realistic Trips across users)
    // ─────────────────────────────────────────────────────────────────────────────
    echo "4. Seeding Trips...\n";
    $tripsData = [
        [
            'id' => 1,
            'user_id' => 2, // Alex Traveler
            'trip_name' => 'Cherry Blossoms & Ancient Temples',
            'description' => 'A dream journey through Japan exploring Tokyo neon streets, Hakone mountain onsens, and Kyoto imperial shrines during spring.',
            'start_date' => '2026-09-10',
            'end_date' => '2026-09-22',
            'cover_photo' => 'https://images.unsplash.com/photo-1503899036084-c55cdd92da26?w=1200&q=80',
            'status' => 'upcoming',
            'visibility' => 'public',
            'share_slug' => 'cherry-blossoms-japan-2026'
        ],
        [
            'id' => 2,
            'user_id' => 2, // Alex Traveler
            'trip_name' => 'Mediterranean Coast & Tapas Trail',
            'description' => 'Two unforgettable weeks savoring Gaudí architecture in Barcelona, ancient Roman ruins, and romantic Seine river walks.',
            'start_date' => '2026-10-05',
            'end_date' => '2026-10-18',
            'cover_photo' => 'https://images.unsplash.com/photo-1583422409516-2895a77efded?w=1200&q=80',
            'status' => 'upcoming',
            'visibility' => 'public',
            'share_slug' => 'mediterranean-tapas-trail'
        ],
        [
            'id' => 3,
            'user_id' => 1, // Admin
            'trip_name' => 'European Summer Odyssey 2026',
            'description' => 'An epic transcontinental summer tour starting from the romantic boulevards of Paris, across Tokyo\'s bustling wards, to tropical Bali beaches.',
            'start_date' => '2026-08-15',
            'end_date' => '2026-08-30',
            'cover_photo' => 'https://images.unsplash.com/photo-1469854523086-cc02fe5d8800?w=1200&q=80',
            'status' => 'ongoing',
            'visibility' => 'public',
            'share_slug' => 'euro-summer-2026'
        ],
        [
            'id' => 4,
            'user_id' => 3, // Elena
            'trip_name' => 'Nordic Wilderness & Northern Lights',
            'description' => 'Chasing geothermal geysers, blue ice caves, and aurora borealis in Reykjavik before exploring historic Amsterdam canals.',
            'start_date' => '2026-01-10',
            'end_date' => '2026-01-20',
            'cover_photo' => 'https://images.unsplash.com/photo-1504893524553-b855bce32c67?w=1200&q=80',
            'status' => 'completed',
            'visibility' => 'public',
            'share_slug' => 'nordic-aurora-2026'
        ],
        [
            'id' => 5,
            'user_id' => 4, // Kenji
            'trip_name' => 'Southeast Asian Backpacker Circuit',
            'description' => 'An affordable backpacking adventure tasting Michelin street eats in Bangkok, surfing in Bali, and marveling at Singapore supertrees.',
            'start_date' => '2026-02-01',
            'end_date' => '2026-02-18',
            'cover_photo' => 'https://images.unsplash.com/photo-1508009603885-50cf7c579365?w=1200&q=80',
            'status' => 'completed',
            'visibility' => 'public',
            'share_slug' => 'southeast-asia-backpacking'
        ],
        [
            'id' => 6,
            'user_id' => 5, // Sofia
            'trip_name' => 'Wild Africa & Atlantic Ocean Escape',
            'description' => 'From Table Mountain vineyards and Boulders Beach penguins to Marrakech spice souks and ancient Giza pyramids.',
            'start_date' => '2026-11-01',
            'end_date' => '2026-11-15',
            'cover_photo' => 'https://images.unsplash.com/photo-1580618672591-eb180b1a973f?w=1200&q=80',
            'status' => 'upcoming',
            'visibility' => 'public',
            'share_slug' => 'wild-africa-safari'
        ],
        [
            'id' => 7,
            'user_id' => 6, // Liam
            'trip_name' => 'Down Under & Kiwi Alps Adventure',
            'description' => 'Surfing Bondi Beach in Sydney followed by Milford Sound fjord cruises and jet boating in Queenstown.',
            'start_date' => '2026-08-18',
            'end_date' => '2026-09-02',
            'cover_photo' => 'https://images.unsplash.com/photo-1506973035872-a4ec16b8e8d9?w=1200&q=80',
            'status' => 'ongoing',
            'visibility' => 'public',
            'share_slug' => 'down-under-kiwi-adventure'
        ],
        [
            'id' => 8,
            'user_id' => 7, // Amina
            'trip_name' => 'Arabian Nights & Desert Dunes',
            'description' => 'Luxury desert glamping and sky-high dining in Dubai paired with historic Nile river cruises in Cairo.',
            'start_date' => '2026-12-10',
            'end_date' => '2026-12-22',
            'cover_photo' => 'https://images.unsplash.com/photo-1512453979798-5ea266f8880c?w=1200&q=80',
            'status' => 'upcoming',
            'visibility' => 'public',
            'share_slug' => 'arabian-nights-dubai'
        ],
        [
            'id' => 9,
            'user_id' => 8, // Mateo
            'trip_name' => 'South American Wonders Expedition',
            'description' => 'Samba and Copacabana sunset cable cars in Rio de Janeiro followed by tango and gourmet steakhouses in Buenos Aires.',
            'start_date' => '2026-03-05',
            'end_date' => '2026-03-16',
            'cover_photo' => 'https://images.unsplash.com/photo-1483729558449-99ef09a8c325?w=1200&q=80',
            'status' => 'completed',
            'visibility' => 'public',
            'share_slug' => 'south-america-wonders'
        ],
        [
            'id' => 10,
            'user_id' => 2, // Alex Traveler
            'trip_name' => 'Pacific Northwest Coastal Explorer',
            'description' => 'A private photography expedition capturing Vancouver mountain inlets and Seattle skyline vistas.',
            'start_date' => '2026-11-20',
            'end_date' => '2026-11-28',
            'cover_photo' => 'https://images.unsplash.com/photo-1559511260-66a65e09b245?w=1200&q=80',
            'status' => 'upcoming',
            'visibility' => 'private',
            'share_slug' => null
        ]
    ];

    foreach ($tripsData as $t) {
        $stmt = $pdo->prepare('
            INSERT INTO trips (id, user_id, trip_name, description, start_date, end_date, cover_photo, status, visibility, share_slug, created_at, updated_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW() - INTERVAL \'2 months\', NOW())
            ON CONFLICT (id) DO UPDATE SET
                user_id = EXCLUDED.user_id,
                trip_name = EXCLUDED.trip_name,
                description = EXCLUDED.description,
                start_date = EXCLUDED.start_date,
                end_date = EXCLUDED.end_date,
                cover_photo = EXCLUDED.cover_photo,
                status = EXCLUDED.status,
                visibility = EXCLUDED.visibility,
                share_slug = EXCLUDED.share_slug
        ');
        $stmt->execute([
            $t['id'], $t['user_id'], $t['trip_name'], $t['description'],
            $t['start_date'], $t['end_date'], $t['cover_photo'], $t['status'],
            $t['visibility'], $t['share_slug']
        ]);
    }
    $pdo->exec("SELECT setval('trips_id_seq', (SELECT MAX(id) FROM trips))");

    // ─────────────────────────────────────────────────────────────────────────────
    // 5. SEED TRIP STOPS & SCHEDULED ACTIVITIES
    // ─────────────────────────────────────────────────────────────────────────────
    echo "5. Seeding Trip Stops...\n";
    $stopsData = [
        // Trip 1: Japan (Tokyo -> Kyoto)
        [1, 1, 2, '2026-09-10', '2026-09-16', 1, 'Shinjuku Granbell Hotel', 720.00, 1500.00, 'Flight', 'JAL direct flight from JFK to NRT', 850.00, 'Stay near Shinjuku station for easy subway access.'],
        [2, 1, 13, '2026-09-16', '2026-09-22', 2, 'Kyoto Machiya Traditional Inn', 650.00, 1200.00, 'Train', 'Shinkansen bullet train from Tokyo to Kyoto', 120.00, 'Rent bicycles to tour eastern temple paths.'],

        // Trip 2: Med Trail (Barcelona -> Rome -> Paris)
        [3, 2, 6, '2026-10-05', '2026-10-09', 1, 'H10 Cubik Hotel Barcelona', 480.00, 1000.00, 'Flight', 'Delta flight NYC to BCN', 650.00, 'Walking distance to Gothic Quarter and Las Ramblas.'],
        [4, 2, 5, '2026-10-09', '2026-10-14', 2, 'Residenza Di Ripetta Rome', 600.00, 1100.00, 'Flight', 'Vueling flight BCN to FCO', 90.00, 'Close to Piazza del Popolo.'],
        [5, 2, 1, '2026-10-14', '2026-10-18', 3, 'Hotel Le Relais Saint-Germain', 580.00, 1200.00, 'Train', 'High speed TGV Lyria to Paris', 110.00, 'Latin Quarter bistros and cafes.'],

        // Trip 3: European Summer Odyssey (Paris -> Tokyo -> Bali)
        [6, 3, 1, '2026-08-15', '2026-08-20', 1, 'Pullman Paris Tour Eiffel', 850.00, 1600.00, 'Flight', 'Air France flight to CDG', 750.00, 'Spectacular balcony views of Eiffel Tower.'],
        [7, 3, 2, '2026-08-20', '2026-08-25', 2, 'Park Hyatt Tokyo', 950.00, 1800.00, 'Flight', 'ANA nonstop Paris to Tokyo Haneda', 900.00, 'Lost in Translation bar on 52nd floor.'],
        [8, 3, 3, '2026-08-25', '2026-08-30', 3, 'Maya Ubud Resort & Spa Bali', 600.00, 1200.00, 'Flight', 'Garuda Indonesia Tokyo to Bali', 450.00, 'Private infinity pool overlooking Petanu River.'],

        // Trip 4: Nordic (Reykjavik -> Amsterdam)
        [9, 4, 16, '2026-01-10', '2026-01-15', 1, 'Canopy by Hilton Reykjavik', 750.00, 1400.00, 'Flight', 'Icelandair direct', 550.00, 'Pack heavy thermal clothing and spike boots.'],
        [10, 4, 14, '2026-01-15', '2026-01-20', 2, 'Pulitzer Amsterdam', 620.00, 1100.00, 'Flight', 'EasyJet Reykjavik to Schiphol', 110.00, 'Historic 17th century canal houses.'],

        // Trip 5: Southeast Asia (Bangkok -> Bali -> Singapore)
        [11, 5, 7, '2026-02-01', '2026-02-07', 1, 'Riva Surya Bangkok', 280.00, 600.00, 'Flight', 'Thai Airways to BKK', 420.00, 'Riverfront terrace on Chao Phraya.'],
        [12, 5, 3, '2026-02-07', '2026-02-13', 2, 'Padma Resort Ubud', 400.00, 800.00, 'Flight', 'AirAsia Bangkok to Denpasar', 85.00, 'Heated jungle infinity pool.'],
        [13, 5, 17, '2026-02-13', '2026-02-18', 3, 'Marina Bay Sands', 900.00, 1500.00, 'Flight', 'Scoot Bali to Singapore', 65.00, 'Iconic rooftop infinity pool.'],

        // Trip 6: Africa (Cape Town -> Marrakech -> Cairo)
        [14, 6, 8, '2026-11-01', '2026-11-06', 1, 'The Silo Hotel Cape Town', 850.00, 1500.00, 'Flight', 'Emirates via Dubai to CPT', 950.00, 'Historic grain silo conversion at V&A Waterfront.'],
        [15, 6, 19, '2026-11-06', '2026-11-10', 2, 'Riad Yasmine Marrakech', 320.00, 700.00, 'Flight', 'Royal Air Maroc Cape Town to RAK', 350.00, 'Iconic green tiled courtyard pool.'],
        [16, 6, 15, '2026-11-10', '2026-11-15', 3, 'Marriott Mena House Cairo', 550.00, 1000.00, 'Flight', 'EgyptAir Marrakech to Cairo', 180.00, 'Direct garden views of Great Pyramid of Khufu.'],

        // Trip 7: Oceania (Sydney -> Queenstown)
        [17, 7, 10, '2026-08-18', '2026-08-25', 1, 'Park Hyatt Sydney', 900.00, 1600.00, 'Flight', 'Qantas to SYD', 800.00, 'Unrivaled Opera House harbor views.'],
        [18, 7, 21, '2026-08-25', '2026-09-02', 2, 'Eichardt\'s Private Hotel Queenstown', 850.00, 1400.00, 'Flight', 'Air New Zealand Sydney to ZQN', 220.00, 'Lake Wakatipu alpine lodge.']
    ];

    foreach ($stopsData as $s) {
        $stmt = $pdo->prepare('
            INSERT INTO trip_stops (
                id, trip_id, city_id, arrival_date, departure_date, order_index,
                accommodation, accommodation_cost, budget_for_stop,
                transport_type, transport_note, transport_cost, notes, created_at, updated_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW() - INTERVAL \'1 month\', NOW())
            ON CONFLICT (id) DO UPDATE SET
                trip_id = EXCLUDED.trip_id,
                city_id = EXCLUDED.city_id,
                arrival_date = EXCLUDED.arrival_date,
                departure_date = EXCLUDED.departure_date,
                order_index = EXCLUDED.order_index,
                accommodation = EXCLUDED.accommodation,
                accommodation_cost = EXCLUDED.accommodation_cost,
                budget_for_stop = EXCLUDED.budget_for_stop,
                transport_type = EXCLUDED.transport_type,
                transport_note = EXCLUDED.transport_note,
                transport_cost = EXCLUDED.transport_cost,
                notes = EXCLUDED.notes
        ');
        $stmt->execute($s);
    }
    $pdo->exec("SELECT setval('trip_stops_id_seq', (SELECT MAX(id) FROM trip_stops))");

    // ─────────────────────────────────────────────────────────────────────────────
    // 6. SEED SCHEDULED TRIP ACTIVITIES
    // ─────────────────────────────────────────────────────────────────────────────
    echo "6. Seeding Scheduled Activities...\n";
    $tripActivitiesData = [
        // Stop 1 (Tokyo): Shibuya Food + TeamLab
        [1, 1, 5, '2026-09-11', '18:00:00', 40.00, 'Table reserved for 2 at Omoide Yokocho.'],
        [2, 1, 6, '2026-09-12', '10:30:00', 32.00, 'Online QR tickets on smartphone.'],
        [3, 1, 7, '2026-09-14', '08:00:00', 85.00, 'Shinjuku station bus pickup.'],

        // Stop 2 (Kyoto): Fushimi Inari + Tea Ceremony
        [4, 2, 37, '2026-09-17', '07:30:00', 15.00, 'Early morning hike before crowds arrive.'],
        [5, 2, 38, '2026-09-18', '14:00:00', 42.00, 'Gion district tea master studio.'],

        // Stop 3 (Barcelona): Sagrada + Tapas
        [6, 3, 20, '2026-10-06', '10:00:00', 38.00, 'Tower elevator access included.'],
        [7, 3, 22, '2026-10-07', '19:30:00', 52.00, 'Tablao Flamenco Cordobes show.'],

        // Stop 4 (Rome): Colosseum + Vatican
        [8, 4, 17, '2026-10-10', '09:00:00', 50.00, 'Arch of Constantine meeting point.'],
        [9, 4, 18, '2026-10-12', '13:30:00', 48.00, 'Dress code: covered shoulders.'],

        // Stop 5 (Paris): Eiffel Tower + Louvre
        [10, 5, 1, '2026-10-15', '16:30:00', 45.00, 'Sunset from top floor.'],
        [11, 5, 2, '2026-10-16', '09:30:00', 35.00, 'Pyramid entrance.'],

        // Stop 6 (Paris Trip 3): Eiffel Tower + Seine Cruise + Pastry
        [12, 6, 1, '2026-08-16', '17:00:00', 45.00, 'Golden hour photography.'],
        [13, 6, 3, '2026-08-17', '19:00:00', 25.00, 'Pont Neuf boarding dock.'],
        [14, 6, 4, '2026-08-18', '14:00:00', 65.00, 'Baking fresh chocolate macarons.'],

        // Stop 7 (Tokyo Trip 3): Shibuya Crawl + TeamLab
        [15, 7, 5, '2026-08-21', '18:30:00', 40.00, 'Tasting local yakitori.'],
        [16, 7, 6, '2026-08-22', '11:00:00', 32.00, 'Water room barefoot exhibition.'],

        // Stop 8 (Bali Trip 3): Cooking Class + Rice Terrace + Nusa Penida
        [17, 8, 9, '2026-08-26', '09:00:00', 30.00, 'Morning spice market shopping.'],
        [18, 8, 10, '2026-08-27', '08:00:00', 20.00, 'Jungle swing photography.'],
        [19, 8, 11, '2026-08-28', '06:30:00', 65.00, 'Sanur harbor speedboat.'],

        // Stop 9 (Reykjavik): Blue Lagoon + Golden Circle
        [20, 9, 42, '2026-01-11', '13:00:00', 75.00, 'Towel and silica mask included.'],
        [21, 9, 43, '2026-01-13', '08:30:00', 68.00, 'Gullfoss and Geysir coach bus.'],

        // Stop 10 (Amsterdam): Rijksmuseum + Canal Cruise
        [22, 10, 39, '2026-01-16', '10:00:00', 36.00, 'Museumplein entrance.'],
        [23, 10, 40, '2026-01-17', '16:00:00', 24.00, 'Prinsengracht boarding dock.'],

        // Stop 11 (Bangkok): Grand Palace + Floating Market
        [24, 11, 23, '2026-02-02', '09:00:00', 25.00, 'Temple dress code applies.'],
        [25, 11, 24, '2026-02-04', '07:00:00', 35.00, 'Wooden longtail boat ride.'],

        // Stop 14 (Cape Town): Table Mountain + Penguins + Wine
        [26, 14, 26, '2026-11-02', '10:00:00', 28.00, 'Cable car weather permit.'],
        [27, 14, 27, '2026-11-03', '09:00:00', 45.00, 'Simon\'s Town penguin boardwalk.'],
        [28, 14, 28, '2026-11-04', '11:00:00', 60.00, 'Chocolate and wine pairings.']
    ];

    foreach ($tripActivitiesData as $ta) {
        $stmt = $pdo->prepare('
            INSERT INTO trip_activities (id, trip_stop_id, activity_id, scheduled_date, scheduled_time, custom_cost, notes, created_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, NOW() - INTERVAL \'3 weeks\')
            ON CONFLICT (id) DO UPDATE SET
                trip_stop_id = EXCLUDED.trip_stop_id,
                activity_id = EXCLUDED.activity_id,
                scheduled_date = EXCLUDED.scheduled_date,
                scheduled_time = EXCLUDED.scheduled_time,
                custom_cost = EXCLUDED.custom_cost,
                notes = EXCLUDED.notes
        ');
        $stmt->execute($ta);
    }
    $pdo->exec("SELECT setval('trip_activities_id_seq', (SELECT MAX(id) FROM trip_activities))");

    // ─────────────────────────────────────────────────────────────────────────────
    // 7. SEED TRIP BUDGETS & BUDGET ITEMS
    // ─────────────────────────────────────────────────────────────────────────────
    echo "7. Seeding Trip Budgets & Spent Items...\n";
    $budgetsData = [
        [1, 1, 1000.00, 1400.00, 400.00, 600.00, 300.00],
        [2, 2, 900.00, 1700.00, 500.00, 800.00, 400.00],
        [3, 3, 2100.00, 2400.00, 600.00, 900.00, 500.00],
        [4, 4, 800.00, 1400.00, 400.00, 500.00, 300.00],
        [5, 5, 600.00, 1600.00, 300.00, 500.00, 200.00],
        [6, 6, 1500.00, 1800.00, 600.00, 700.00, 400.00],
        [7, 7, 1200.00, 1800.00, 500.00, 700.00, 300.00],
        [8, 8, 1400.00, 1600.00, 400.00, 600.00, 300.00],
        [9, 9, 1000.00, 1200.00, 350.00, 550.00, 250.00],
        [10, 10, 400.00, 800.00, 250.00, 400.00, 200.00]
    ];

    foreach ($budgetsData as $b) {
        $stmt = $pdo->prepare('
            INSERT INTO trip_budget (id, trip_id, transport_budget, stay_budget, activities_budget, meals_budget, misc_budget, created_at, updated_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, NOW() - INTERVAL \'1 month\', NOW())
            ON CONFLICT (id) DO UPDATE SET
                trip_id = EXCLUDED.trip_id,
                transport_budget = EXCLUDED.transport_budget,
                stay_budget = EXCLUDED.stay_budget,
                activities_budget = EXCLUDED.activities_budget,
                meals_budget = EXCLUDED.meals_budget,
                misc_budget = EXCLUDED.misc_budget
        ');
        $stmt->execute($b);
    }
    $pdo->exec("SELECT setval('trip_budget_id_seq', (SELECT MAX(id) FROM trip_budget))");

    // Seed Manual Budget Items for Ongoing & Completed Trips (e.g. Trip 3, Trip 4, Trip 5)
    $budgetItemsData = [
        // Trip 3 (Ongoing: Paris -> Tokyo -> Bali)
        [1, 3, 6, 'transport', 'Air France Flights (Paris roundtrip)', 750.00, '2026-08-15'],
        [2, 3, 6, 'stay', 'Pullman Paris Tour Eiffel 5 Nights', 850.00, '2026-08-15'],
        [3, 3, 6, 'activities', 'Eiffel Tower Summit Skip-the-Line', 45.00, '2026-08-16'],
        [4, 3, 6, 'meals', 'Dinner at Le Jules Verne (Eiffel Tower)', 185.00, '2026-08-16'],
        [5, 3, 6, 'activities', 'Seine Sunset Boat Cruise Tickets', 25.00, '2026-08-17'],
        [6, 3, 6, 'meals', 'Café de Flore Bistro Lunch', 48.00, '2026-08-17'],
        [7, 3, 6, 'shopping', 'Souvenirs & Perfume in Marais', 120.00, '2026-08-18'],
        [8, 3, 7, 'transport', 'ANA Nonstop Flight Paris to Tokyo', 900.00, '2026-08-20'],
        [9, 3, 7, 'stay', 'Park Hyatt Tokyo 5 Nights', 950.00, '2026-08-20'],
        [10, 3, 7, 'activities', 'TeamLab Planets Museum Pass', 32.00, '2026-08-21'],
        [11, 3, 7, 'meals', 'Sushi Dai Omakase Experience', 110.00, '2026-08-22'],

        // Trip 4 (Completed: Nordic)
        [12, 4, 9, 'transport', 'Icelandair International Flight', 550.00, '2026-01-10'],
        [13, 4, 9, 'stay', 'Canopy Hilton Reykjavik', 750.00, '2026-01-10'],
        [14, 4, 9, 'activities', 'Blue Lagoon Premium Admission', 75.00, '2026-01-11'],
        [15, 4, 9, 'activities', 'Golden Circle Super Jeep Tour', 68.00, '2026-01-13'],
        [16, 4, 10, 'transport', 'Schiphol Airport Transfer & Flights', 110.00, '2026-01-15'],
        [17, 4, 10, 'stay', 'Pulitzer Amsterdam 5 Nights', 620.00, '2026-01-15'],
        [18, 4, 10, 'activities', 'Rijksmuseum Art Pass', 36.00, '2026-01-16'],
        [19, 4, 10, 'meals', 'Pancakes Amsterdam & Local Dining', 85.00, '2026-01-17']
    ];

    foreach ($budgetItemsData as $bi) {
        $stmt = $pdo->prepare('
            INSERT INTO budget_items (id, trip_id, stop_id, category, description, amount, spent_on, created_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, NOW() - INTERVAL \'2 weeks\')
            ON CONFLICT (id) DO UPDATE SET
                trip_id = EXCLUDED.trip_id,
                stop_id = EXCLUDED.stop_id,
                category = EXCLUDED.category,
                description = EXCLUDED.description,
                amount = EXCLUDED.amount,
                spent_on = EXCLUDED.spent_on
        ');
        $stmt->execute($bi);
    }
    $pdo->exec("SELECT setval('budget_items_id_seq', (SELECT MAX(id) FROM budget_items))");

    // ─────────────────────────────────────────────────────────────────────────────
    // 8. SEED COMMUNITY POSTS & LIKES
    // ─────────────────────────────────────────────────────────────────────────────
    echo "8. Seeding Community Stories & Likes...\n";
    $postsData = [
        [
            1, 2, 1,
            'Top 5 Secret Ramen Spots in Tokyo You Cannot Miss',
            "After spending 2 weeks navigating Tokyo\'s labyrinth of subway alleys, here are 5 hidden gem ramen shops where locals queue up:\n\n1. Fuunji in Shinjuku (best Tsukemen dipping noodles on earth)\n2. Afuri in Ebisu (refreshing Yuzu shio broth)\n3. Rokurinsha at Tokyo Station Ramen Street\n4. Kagari in Ginza (rich chicken paitan)\n5. Ichiran Shibuya for late-night solo booth indulgence.\n\nTip: Always bring 1,000 yen cash for the ticket vending machines!",
            42,
            '2026-08-10 14:30:00'
        ],
        [
            2, 3, 3,
            'A Food Critic\'s 48-Hour Guide to Paris Boulevards',
            "Paris in August has a magic of its own. Here is the perfect itinerary for culinary lovers:\n\n- Morning: Fresh butter croissants at Du Pain et des Idées near Canal Saint-Martin.\n- Afternoon: Macaron workshop in Saint-Germain followed by an espresso at Café de Flore.\n- Evening: Sunset picnic on the Pont des Arts with Comté cheese, baguette, and Bordeaux wine.\n\nBe sure to check my linked trip itinerary for exact locations and tickets!",
            38,
            '2026-08-12 18:00:00'
        ],
        [
            3, 4, 5,
            'Backpacking Bali on $30/Day: Honest Budget Breakdown',
            "Bali is still paradise on a budget if you follow these rules:\n- Stay in guesthouses (homestays) in Canggu or Ubud ($15-20/night with AC).\n- Eat at local Warungs ($2-4 for Nasi Goreng with fried egg).\n- Rent a scooter for $5/day instead of hiring private drivers.\n- Wake up at 5:30 AM for the Tegallalang rice terraces to beat both the heat and the crowds!",
            56,
            '2026-08-14 09:15:00'
        ],
        [
            4, 5, 2,
            'Exploring Barcelona: Why Park Güell at Sunrise is Unbeatable',
            "Gaudí\'s masterpiece looks like a fairy tale when the golden morning sun hits the ceramic mosaic benches overlooking the Mediterranean.\n\nBook the earliest 9:30 AM entry ticket to have the dragon staircase almost completely to yourself for breathtaking photos!",
            29,
            '2026-08-16 11:20:00'
        ],
        [
            5, 6, 7,
            'Surfing Bondi & Coastal Hiking Down Under',
            "The coastal cliff walk from Bondi to Coogee is one of the most stunning 6km walks in the southern hemisphere. Stopping at Bronte pool for a dip and fresh smoothie is pure bliss.",
            34,
            '2026-08-18 16:45:00'
        ],
        [
            6, 7, 8,
            'Desert Glamping in Dubai: Stargazing Beyond the Skyscrapers',
            "Beyond the glitz of Dubai Marina and the towering Burj Khalifa lies the serene beauty of the Arabian Desert. Sleeping under a million desert stars in a luxury Bedouin tent is an experience every traveler should cherish at least once in their lifetime.",
            47,
            '2026-08-19 20:00:00'
        ],
        [
            7, 8, 9,
            'Sunrise Over Rio: Christ the Redeemer and Copacabana',
            "Taking the first cogwheel train up Mount Corcovado at 8 AM allowed us to stand before the statue enveloped in morning mist as the clouds parted over Guanabara Bay. Simply unforgettable!",
            31,
            '2026-08-20 15:30:00'
        ],
        [
            8, 9, 4,
            'The Ultimate Golden Circle & Northern Lights Roadtrip Guide',
            "Driving through Iceland during winter is otherworldly. Renting a 4x4 with studded tires is essential for icy mountain passes. Our stop at the Blue Lagoon after a freezing day of waterfall chasing was heavenly warmth.",
            51,
            '2026-08-21 12:10:00'
        ]
    ];

    foreach ($postsData as $p) {
        $stmt = $pdo->prepare('
            INSERT INTO community_posts (id, user_id, trip_id, title, content, likes_count, created_at, updated_at)
            VALUES (?, ?, ?, ?, ?, ?, ?::timestamp, ?::timestamp)
            ON CONFLICT (id) DO UPDATE SET
                user_id = EXCLUDED.user_id,
                trip_id = EXCLUDED.trip_id,
                title = EXCLUDED.title,
                content = EXCLUDED.content,
                likes_count = EXCLUDED.likes_count
        ');
        $stmt->execute([$p[0], $p[1], $p[2], $p[3], $p[4], $p[5], $p[6], $p[6]]);
    }
    $pdo->exec("SELECT setval('community_posts_id_seq', (SELECT MAX(id) FROM community_posts))");

    // Community Likes
    $likesData = [
        [1, 1, 2], [2, 1, 3], [3, 1, 4], [4, 1, 5],
        [5, 2, 1], [6, 2, 4], [7, 2, 6],
        [8, 3, 2], [9, 3, 5], [10, 3, 7],
        [11, 4, 1], [12, 4, 3], [13, 4, 8],
        [14, 5, 2], [15, 5, 6]
    ];
    foreach ($likesData as $l) {
        $stmt = $pdo->prepare('
            INSERT INTO community_likes (id, post_id, user_id, created_at)
            VALUES (?, ?, ?, NOW() - INTERVAL \'1 week\')
            ON CONFLICT (id) DO NOTHING
        ');
        $stmt->execute($l);
    }
    $pdo->exec("SELECT setval('community_likes_id_seq', (SELECT MAX(id) FROM community_likes))");

    // ─────────────────────────────────────────────────────────────────────────────
    // 9. SEED SAVED DESTINATIONS (Wishlists)
    // ─────────────────────────────────────────────────────────────────────────────
    echo "9. Seeding Saved Destinations...\n";
    $savedData = [
        [1, 2, 1], // Alex saved Paris
        [2, 2, 2], // Alex saved Tokyo
        [3, 2, 3], // Alex saved Bali
        [4, 2, 6], // Alex saved Barcelona
        [5, 2, 16], // Alex saved Reykjavik
        [6, 1, 2], // Admin saved Tokyo
        [7, 1, 10], // Admin saved Sydney
        [8, 1, 21], // Admin saved Queenstown
        [9, 3, 13], // Elena saved Kyoto
        [10, 4, 8]  // Kenji saved Cape Town
    ];

    foreach ($savedData as $sd) {
        $stmt = $pdo->prepare('
            INSERT INTO saved_destinations (id, user_id, city_id, saved_at)
            VALUES (?, ?, ?, NOW() - INTERVAL \'2 weeks\')
            ON CONFLICT (id) DO NOTHING
        ');
        $stmt->execute($sd);
    }
    $pdo->exec("SELECT setval('saved_destinations_id_seq', (SELECT MAX(id) FROM saved_destinations))");

    $pdo->commit();
    echo "=== GLOBETROTTER DATABASE SEEDING COMPLETED SUCCESSFULLY! ===\n";

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo "ERROR SEEDING DATABASE: " . $e->getMessage() . "\n";
    exit(1);
}
