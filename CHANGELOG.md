# Changelog

All notable changes to the TableTalk project will be documented in this file.

## [1.1.0] - 2025-06-20
### Added
- **Full Menu Management (CRUD):** 
  - Kitchen Staff / Admins can now view a full list of menu items directly in the "Menu Management" tab.
  - New "Edit Dish" modal allows for updating dish name, category, price, rating, description, image, and availability.
  - "Delete Dish" functionality implements a "soft delete" (setting `is_available = false`) to maintain the integrity of historical orders while hiding the item from the customer menu.
- **Enhanced Customer Feedback System:**
  - Dynamic, star-based default feedback messages added for customers who submit ratings without text comments.
  - The Admin Feedback Dashboard now uses a comprehensive SQL `GROUP_CONCAT` query to extract and display the specific food items ordered alongside the customer's feedback.
- **Light Mode UI Polish:**
  - Table badges now dynamically switch to high-visibility black text and borders in Light Mode.
  - Chat bubbles automatically adapt transparent dark backgrounds and crisp black text for readability when Light Mode is toggled.

### Fixed
- **UI Height Overflow Bug:** Fixed an issue where the payment success modal would become excessively tall and force scrolling by hiding the "Ready to Pay" header during payment processing and success stages.
- **Payment Routing Bug:** Corrected a typo in the JavaScript API route from `/payment` to `/payments` to correctly interface with the backend controller.
- **Average Rating Calculation:** Fixed an API mapping mismatch (`avg_rating` vs `average_rating`) that prevented the global average star rating from rendering on the admin dashboard.

### Changed
- **PWA Branding:** Updated HTML metadata and the PWA `manifest.json` title to the finalized professional product name: "TableTalk - Digital Restaurant Ordering & Management System".
- **Git Commit Dates:** Retroactively rebased the repository commit history to reflect the development lifecycle in the year 2025.
