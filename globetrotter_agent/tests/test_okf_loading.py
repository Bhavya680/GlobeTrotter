"""Test specifications for OKF knowledge loading."""
from globetrotter_agent.shared.knowledge_loader import KNOWLEDGE, get_knowledge, get_ontology

def test_all_bundles_load():
    """Verify all 8 bundles load without errors."""
    bundles = [
        "activities_taxonomy", "admin_insights", "app_features", 
        "budget_intelligence", "community_standards", "destinations", 
        "travel_wisdom", "user_support"
    ]
    for bundle in bundles:
        assert bundle in KNOWLEDGE, f"Bundle {bundle} not loaded"

def test_all_ontologies_load():
    """Verify all 4 ontology JSONs parse correctly."""
    ontologies = [
        "activity_categories.json", "travel_entities.json",
        "budget_categories.json", "region_taxonomy.json"
    ]
    for ont in ontologies:
        assert ont in KNOWLEDGE["ontologies"], f"Ontology {ont} not loaded"

def test_get_knowledge_by_topic():
    """Verify get_knowledge("app_features", "overview") returns non-empty string."""
    content = get_knowledge("app_features", "overview")
    assert content != "", "Knowledge by topic should not be empty"

def test_get_knowledge_all_topics():
    """Verify get_knowledge("destinations") returns combined content of all topics."""
    content = get_knowledge("destinations")
    assert content != "", "Knowledge of all topics should not be empty"
    assert "###" in content, "Should contain markdown headers from concatenation"

def test_missing_bundle_returns_empty():
    """Verify get_knowledge("nonexistent") returns ""."""
    assert get_knowledge("nonexistent") == ""

def test_ontology_structure():
    """Verify travel_entities.json has "entities" key, activity_categories.json has "categories" key, etc."""
    entities = get_ontology("travel_entities")
    assert "entities" in entities, "travel_entities missing 'entities' key"
    
    categories = get_ontology("activity_categories")
    assert "categories" in categories, "activity_categories missing 'categories' key"
