# TableTalk Requirements

## Functional Requirements (User Stories)

### Customer
- **As a customer**, I need to log in using my Table ID so that my orders are tracked correctly.
- **As a customer**, I need to view the menu categorized by Foods, Drinks, and Desserts so that I can easily find what I want to eat.
- **As a customer**, I need to add a note to my order before submitting so that the kitchen accommodates my dietary preferences.
- **As a customer**, I need to chat directly with the kitchen so that I can ask questions about the preparation time.
- **As a customer**, I need to submit a star rating and written feedback upon paying so that I can review my experience.
- **As a customer**, I need to receive an HTML email receipt after payment so that I have a record of my transaction.

### Kitchen Staff
- **As a chef**, I need to view incoming orders in real-time so that I can prepare them immediately.
- **As a chef**, I need to mark orders as "completed" so that they are cleared from the active queue.
- **As a chef**, I need to add new menu items via a form with an image uploader so that the menu stays up to date.
- **As a chef**, I need to view an aggregated feedback dashboard so that I can evaluate customer satisfaction anonymously.

### System Administrator
- **As an admin**, I need a secure login portal so that unauthorized users cannot access sensitive data.
- **As an admin**, I need to view all anonymous feedback so that I can make managerial decisions.

## Non-Functional Requirements
- **Performance**: API responses should load in under 200ms.
- **UX/Usability**: The UI must support a Light and Dark mode toggle and adhere to Fitts's Law (44px touch targets).
- **Feedback**: The system must provide visual loading indicators (spinners) during network requests and disable buttons to prevent duplicate submissions.
- **Aesthetics**: The application must utilize modern glassmorphism UI design principles.
