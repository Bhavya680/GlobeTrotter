---
title: Admin Capabilities
description: Detailed list of admin panel features.
type: document
tags: [admin, capabilities]
---
# Admin Capabilities

- **Tab 1 Managing Users**: view all users, toggle admin role, delete users.
- **Tab 2 Popular Cities**: ranked table of cities by trip frequency, bar chart.
- **Tab 3 Popular Activities**: ranked table by frequency, category pie chart.
- **Tab 4 Analytics**: line chart (registrations), pie chart (trip status), bar chart (trips per month), 4 KPI metric cards.
- **Access**: Admin can access admin panel at /admin/index.php. Requires role = 'admin' in users table.
- **Data Privacy**: Admin cannot see other users' private data content, only metadata.
- **Permanence**: Admin actions are permanent (user deletion cascades).
