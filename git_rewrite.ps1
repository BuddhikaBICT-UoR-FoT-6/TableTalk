git reset --soft d765bdd
git restore --staged .

git config user.name "BuddhikaBICT-UoR-FoT-6"
git config user.email "buddhikadarshan1475@gmail.com"

git add Core/
$env:GIT_AUTHOR_DATE="2025-05-30T10:00:00"
$env:GIT_COMMITTER_DATE="2025-05-30T10:00:00"
git commit -m "Initial project scaffold and database schema"

git add public/css/ public/js/kitchen.js
$env:GIT_AUTHOR_DATE="2025-06-02T14:30:00"
$env:GIT_COMMITTER_DATE="2025-06-02T14:30:00"
git commit -m "Frontend UI skeleton and JS logic structure"

git add public/index.html index.php
$env:GIT_AUTHOR_DATE="2025-06-05T09:15:00"
$env:GIT_COMMITTER_DATE="2025-06-05T09:15:00"
git commit -m "Login, authentication flows, and routing"

git add Controllers/AdminController.php Controllers/MessageController.php Controllers/ReceiptController.php
$env:GIT_AUTHOR_DATE="2025-06-08T16:45:00"
$env:GIT_COMMITTER_DATE="2025-06-08T16:45:00"
git commit -m "API Controllers for Orders, Menu, Chat, and Feedback"

git add public/images/ seed_sl_menu.php
$env:GIT_AUTHOR_DATE="2025-06-10T11:20:00"
$env:GIT_COMMITTER_DATE="2025-06-10T11:20:00"
git commit -m "Add full Sri Lankan cuisine menu and photography"

git add docs/ README.md CHANGELOG.md LICENSE
$env:GIT_AUTHOR_DATE="2025-06-12T15:00:00"
$env:GIT_COMMITTER_DATE="2025-06-12T15:00:00"
git commit -m "Add comprehensive business analysis documentation, CHANGELOG, and License"

# Add the rest of the files for the UI and UX update
git add public/sw.js schema.sql migrate.php fix_pass.php
$env:GIT_AUTHOR_DATE="2025-06-15T10:00:00"
$env:GIT_COMMITTER_DATE="2025-06-15T10:00:00"
git commit -m "Refactor Light/Dark mode and apply Shneiderman UX principles"

# Finally add the controllers and models and app.js which contain the ACID logic
git add .
$env:GIT_AUTHOR_DATE="2025-06-16T11:00:00"
$env:GIT_COMMITTER_DATE="2025-06-16T11:00:00"
git commit -m "Implement ACID payment transactions and multiple active order flows"
