<?php
require 'includes/db.php';

// Check if total_budget is a generated column
$s = $pdo->query("
    SELECT column_name, is_generated, generation_expression, column_default
    FROM information_schema.columns
    WHERE table_name = 'trip_budget'
    ORDER BY ordinal_position
");
print_r($s->fetchAll(PDO::FETCH_ASSOC));

// try inserting a sample budget row for trip 1 to test generated col
echo "\n=== TEST INSERT ===\n";
try {
    $pdo->prepare("INSERT INTO trip_budget (trip_id, transport_budget, stay_budget, activities_budget, meals_budget, misc_budget)
                   VALUES (?, ?, ?, ?, ?, ?)
                   ON CONFLICT (trip_id) DO UPDATE SET transport_budget = EXCLUDED.transport_budget")
        ->execute([999999, 100, 200, 50, 75, 25]);
    echo "Insert OK (would work). Rolling back...\n";
    $pdo->prepare("DELETE FROM trip_budget WHERE trip_id = 999999")->execute();
} catch (Exception $e) {
    echo "Insert error: " . $e->getMessage() . "\n";
}
