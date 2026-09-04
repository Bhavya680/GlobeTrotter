---
title: Open Knowledge Format (OKF) Root Index
description: Root index for GlobeTrotter Agent's knowledge base.
type: index
tags: [okf, knowledge_base, root]
---
# Open Knowledge Format (OKF)

This file documents the OKF structure for the GlobeTrotter Agent.
- **What OKF is**: The agent's long-term, static memory. It provides factual grounding for sub-agents without needing to query the database.
- **How it works**: `knowledge_loader.py` loads bundles at agent startup.
- **Naming Conventions**: The bundle name matches the folder name. Each file covers one specific topic.
- **Adding new knowledge**: Add a `.md` or `.json` file to the relevant bundle folder.
- **Agent Access**: Agents access OKF via a shared `get_knowledge(bundle_name, topic)` utility.
- **Update Policy**: OKF is updated manually when app features change; it is NOT auto-updated from the database.
