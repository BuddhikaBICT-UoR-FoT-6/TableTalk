# TableTalk Use Cases

## Overview
This document outlines the core system interactions for the TableTalk restaurant management platform.

## Use Case Diagram

```mermaid
usecaseDiagram
    actor Customer as "Customer (Table)"
    actor Chef as "Kitchen Staff"
    actor Admin as "Administrator"

    package "TableTalk System" {
        usecase UC1 as "Browse Menu"
        usecase UC2 as "Place Order"
        usecase UC3 as "Chat with Kitchen"
        usecase UC4 as "Submit Feedback"
        usecase UC5 as "Receive Email Receipt"
        
        usecase UC6 as "Manage Orders"
        usecase UC7 as "Add Menu Items"
        usecase UC8 as "Upload Dish Images"
        
        usecase UC9 as "View Feedback Dashboard"
        usecase UC10 as "View Analytics"
    }

    Customer --> UC1
    Customer --> UC2
    Customer --> UC3
    Customer --> UC4
    Customer --> UC5

    Chef --> UC3
    Chef --> UC6
    Chef --> UC7
    Chef --> UC8
    Chef --> UC9

    Admin --> UC9
    Admin --> UC10
```

## Description of Interactions
- **Customer**: Authenticates via Table ID. Can browse the interactive menu, place orders directly to the kitchen, and communicate special requests via real-time chat.
- **Kitchen Staff**: Logs in securely to manage the active order queue, communicate with specific tables, and update the menu with new dishes and photography.
- **Administrator**: Oversees the entire operation, primarily focusing on reviewing aggregated and anonymous customer feedback.
