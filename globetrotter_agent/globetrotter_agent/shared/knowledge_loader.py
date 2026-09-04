"""Loads OKF bundles into memory at agent startup."""
import os
import json
import logging
import frontmatter

logger = logging.getLogger(__name__)

KNOWLEDGE = {
    "ontologies": {}
}

# The root directory of the project should be determined relative to this file
# This file is in globetrotter_agent/globetrotter_agent/shared/
PROJECT_ROOT = os.path.abspath(os.path.join(os.path.dirname(__file__), "..", ".."))
OKF_DIR = os.path.join(PROJECT_ROOT, "okf")

def _load_all_knowledge():
    """Scans and loads all OKF data into memory."""
    if not os.path.exists(OKF_DIR):
        logger.warning(f"OKF directory not found at {OKF_DIR}")
        return

    bundles_dir = os.path.join(OKF_DIR, "bundles")
    ontologies_dir = os.path.join(OKF_DIR, "ontologies")
    
    file_count = 0
    total_size = 0

    # Load Ontologies
    if os.path.exists(ontologies_dir):
        for filename in os.listdir(ontologies_dir):
            if filename.endswith(".json"):
                filepath = os.path.join(ontologies_dir, filename)
                try:
                    with open(filepath, 'r', encoding='utf-8') as f:
                        content = f.read()
                        total_size += len(content)
                        KNOWLEDGE["ontologies"][filename] = json.loads(content)
                        file_count += 1
                except Exception as e:
                    logger.error(f"Failed to load ontology {filename}: {e}")

    # Load Markdown Bundles
    if os.path.exists(bundles_dir):
        for bundle_name in os.listdir(bundles_dir):
            bundle_path = os.path.join(bundles_dir, bundle_name)
            if os.path.isdir(bundle_path):
                if bundle_name not in KNOWLEDGE:
                    KNOWLEDGE[bundle_name] = {}
                    
                for filename in os.listdir(bundle_path):
                    if filename.endswith(".md"):
                        filepath = os.path.join(bundle_path, filename)
                        try:
                            with open(filepath, 'r', encoding='utf-8') as f:
                                content = f.read()
                                total_size += len(content)
                                post = frontmatter.loads(content)
                                
                                # Store the parsed frontmatter object so agents can access .metadata and .content
                                topic_name = filename.replace(".md", "")
                                KNOWLEDGE[bundle_name][topic_name] = {
                                    "metadata": post.metadata,
                                    "content": post.content
                                }
                                file_count += 1
                        except Exception as e:
                            logger.error(f"Failed to load bundle file {bundle_name}/{filename}: {e}")

    logger.info(f"OKF loading complete. Loaded {file_count} files ({total_size} bytes).")

# Initialize loading upon import
_load_all_knowledge()

def get_knowledge(bundle_name: str, topic: str = None) -> str:
    """Returns OKF knowledge for a bundle or a specific topic within it."""
    if bundle_name not in KNOWLEDGE:
        logger.warning(f"Bundle {bundle_name} not found in OKF.")
        return ""
        
    bundle = KNOWLEDGE[bundle_name]
    
    if topic:
        if topic in bundle:
            # Reconstruct the text
            item = bundle[topic]
            return f"{item['content']}"
        else:
            logger.warning(f"Topic {topic} not found in bundle {bundle_name}.")
            return ""
            
    # Concatenate all topics
    concatenated = []
    for t_name, item in bundle.items():
        title = item["metadata"].get("title", t_name)
        concatenated.append(f"### {title}\n{item['content']}\n")
        
    return "\n".join(concatenated)

def get_ontology(ontology_name: str) -> dict:
    """Returns a parsed ontology JSON dict."""
    if not ontology_name.endswith(".json"):
        ontology_name += ".json"
    return KNOWLEDGE["ontologies"].get(ontology_name, {})
