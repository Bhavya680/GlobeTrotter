<?php
require_once __DIR__ . '/../includes/db.php';

try {
    // 7. Trip Budgets Table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS trip_budgets (
            id                SERIAL PRIMARY KEY,
            trip_id           INT NOT NULL UNIQUE REFERENCES trips(id) ON DELETE CASCADE,
            total_budget      NUMERIC(10,2) NOT NULL DEFAULT 0,
            transport_budget  NUMERIC(10,2) NOT NULL DEFAULT 0,
            stay_budget       NUMERIC(10,2) NOT NULL DEFAULT 0,
            meals_budget      NUMERIC(10,2) NOT NULL DEFAULT 0,
            misc_budget       NUMERIC(10,2) NOT NULL DEFAULT 0,
            created_at        TIMESTAMPTZ NOT NULL DEFAULT NOW()
        )
    ");
    echo "trip_budgets table ensured.\n";

    // 8. Budget Items Table
    $pdo->exec("
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
        )
    ");
    
    $pdo->exec("CREATE INDEX IF NOT EXISTS idx_budget_items_trip ON budget_items (trip_id)");
    echo "budget_items table ensured.\n";

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
