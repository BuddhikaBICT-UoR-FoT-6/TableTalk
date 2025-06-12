# TableTalk QA Test Cases

| Test ID | Module | Scenario | Expected Result | Status |
|---|---|---|---|---|
| TC-01 | Auth | Customer logs in with valid Table ID | System routes user to the Customer Menu. | PASS |
| TC-02 | Auth | Customer logs in without Table ID | Toast notification warns "Table ID is required". Button is re-enabled. | PASS |
| TC-03 | Menu | Filter menu by category (e.g. Drinks) | Only items matching 'Drinks' are rendered in the grid. | PASS |
| TC-04 | Cart | Adjust item quantity | Subtotal and total calculation updates accurately. | PASS |
| TC-05 | Order | Submit order with notes | Spinner appears on button. Order saves to database and clears cart. | PASS |
| TC-06 | Chat | Customer sends message | Message appears in UI. Polling fetches reply when chef responds. | PASS |
| TC-07 | Kitchen | Chef views active orders | Order submitted in TC-05 appears in active queue instantly. | PASS |
| TC-08 | Kitchen | Chef adds new dish with image | Form accepts file. File is saved to `/images/`. DB saves path. | PASS |
| TC-09 | Payment | Customer clicks Pay | Order status marked completed. Feedback modal appears. | PASS |
| TC-10 | Theme | User toggles Light/Dark mode | CSS theme transitions instantly. Preference saved to `localStorage`. | PASS |
