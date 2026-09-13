# Learning Web Technology- Summer 2025-2026, Section AA
 
 Project Layout 

This project follows the MVC (Model-View Controller) Pattern:

```
app/
├── Controllers/     # was app/controllers (capitalized)
├── Models/          # was app/models (capitalized)
├── Views/           # was app/views (capitalized)
└── helpers/         # kept as-is (shared helper functions)
config/              # unchanged (database.php, app.php)
routes/
└── web.php          # NEW: the URL→controller dispatch logic, pulled out of index.php
public/
├── css/             # moved from root css/
├── js/              # moved from assets/js/
└── index.php        # moved from root; now a slim front controller that bootstraps the app and hands off to routes/web.php
uploads/             # unchanged
database.sql         # left at project root
```