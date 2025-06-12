# TableTalk ERD

## Entity Relationship Diagram

```mermaid
erDiagram
    MENU_ITEMS {
        int id PK
        string name
        string category
        decimal price
        string description
        decimal rating
        string image_url
    }

    ORDERS {
        int id PK
        int table_id
        string status "pending, completed"
        decimal total
        string notes
        timestamp created_at
    }

    ORDER_ITEMS {
        int id PK
        int order_id FK
        int menu_item_id FK
        int quantity
        decimal price_at_time
    }

    MESSAGES {
        int id PK
        int table_id
        string sender "table, chef"
        string message
        boolean is_read
        timestamp created_at
    }

    FEEDBACK {
        int id PK
        int table_id
        int order_id FK
        int rating
        string comment
        timestamp created_at
    }

    ORDERS ||--o{ ORDER_ITEMS : "contains"
    MENU_ITEMS ||--o{ ORDER_ITEMS : "included_in"
    ORDERS ||--o| FEEDBACK : "generates"
```

## Schema Details
- **MENU_ITEMS**: Stores catalog of available dishes and their image paths.
- **ORDERS**: Tracks the active and historical transactions linked to a specific `table_id`.
- **MESSAGES**: Facilitates the real-time chat feature between a table and the kitchen. Messages are cleared when an order is finalized.
- **FEEDBACK**: Stores anonymous customer reviews.
