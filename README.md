# iposg-assesment-backend

This Laravel backend application is designed to handle customer payment processing and daily reconciliation. The system enables users to upload CSV files containing payment records, which are then validated, processed, and stored asynchronously using Laravel queues and background jobs. Uploaded files are stored in AWS S3 for scalability and security.

Each payment record is automatically converted to a base currency using a third-party exchange rate API. The system also simulates notifications and logs relevant activities for traceability. At the end of each day, the system generates invoices for each customer, consolidating their payments, and sends the invoices via email.

Key features include:

- CSV file upload with validation and S3 storage

- Asynchronous job processing for large files

- Currency conversion via external API

- Payment and invoice storage with relational mapping

- Daily scheduled invoice generation

- Email dispatch with invoice details

This project demonstrates scalable backend architecture using Laravel, job queues, third-party integrations, and scheduled automation.


---

## Environment Variables

Before setting up and running the project locally, you **must** configure the following environment variables in your `.env` file:

```env
API_EXCHANGE_BASE_URL=
API_EXCHANGE_KEY=
API_EXCHANGE_DEFAULT_CURRENCY=

CUSTOM_SLACK_NOTIFICATION_WEBHOOK=
CUSTOM_SLACK_NOTIFICATION_ALERT_MAX=

CUSTOM_STORAGE_PAYMENTS_MAX_FILE_SIZE=
```

**Descriptions:**
- `API_EXCHANGE_BASE_URL`: The base URL for the currency exchange rates API.
- `API_EXCHANGE_KEY`: The API key/token for accessing the exchange service.
- `API_EXCHANGE_DEFAULT_CURRENCY`: The default currency code used for conversions.

- `CUSTOM_SLACK_NOTIFICATION_WEBHOOK`: Slack webhook URL for sending notifications.
- `CUSTOM_SLACK_NOTIFICATION_ALERT_MAX`: The maximum number of records to process before sending a Slack success alert.

- `CUSTOM_STORAGE_PAYMENTS_MAX_FILE_SIZE`: Maximum allowed file size for uploaded payment files.

---

## Local Setup Instructions

1. **Clone the repository:**
   ```bash
   git clone https://github.com/San-Geeth/iposg-assesment-backend.git
   cd iposg-assesment-backend
   ```

2. **Install dependencies:**
   ```bash
   composer install
   ```

3. **Copy `.env` and set up environment variables:**
   ```bash
   cp .env.example .env
   # Edit .env with the required properties listed above
   ```

4. **Generate application key:**
   ```bash
   php artisan key:generate
   ```

5. **Run database migrations:**
   ```bash
   php artisan migrate
   ```

6. **(Optional) Seed your database if required:**
   ```bash
   php artisan db:seed
   ```

7. **Start the development server:**
   ```bash
   php artisan serve
   ```

## Docker Setup Instructions

1. **Build the docker container:**
   ```bash
   docker-build -t iposg-app .
   ```

2. **Run the app:**
   ```bash
   docker-run -p 8000:80 iposg-app
   ```

2. **Execute migrations:**
   ```bash
   docker exec -it <container-name> bash 
   ```

    ```bash
   php artisan migrate 
   ```
   
### Please note that currently need to create mysql container manually and create a new database.

---

## API Overview

### Main Endpoints

- `POST /api/payments` – Save a payment record.
- `POST /api/payments/upload` – Upload a file (CSV) for batch payment processing.

---

## Key Components

### PaymentController

- Handles incoming HTTP requests related to payments.
- Main public method: `savePaymentRecord()`
    - Accepts payment data via a validated request.
    - Delegates actual saving logic to the `PaymentService`.
    - Returns a JSON response indicating success or logs errors.

### PaymentService

- **Core business logic for payments.**
- Main responsibilities:
    - **populatePayments($csvData, $fileId):** Reads and processes payment data from uploaded CSV files. Handles:
        - Parsing the CSV.
        - Cleaning and normalizing each row.
        - Validating required fields and formats (email, amount, currency, date).
        - Converts amounts to USD using the current exchange rate.
        - Saves valid payments via the repository.
        - Sends a Slack notification if processed count exceeds a configured threshold.
    - **validateRow($data):** Performs validation and normalization of a single payment row.
    - **cleanRow($data):** Attempts to fix anomalies in the data, such as trimming whitespace, fixing common email typos, normalizing currency and date formats.
    - **savePayment($data):** Persists a validated payment record into the database.

### FileController

- Handles file upload requests (CSV files containing payment data).
- Main method: `uploadFile()`
    - Receives the file via HTTP request.
    - Saves the file metadata and uploads the file to S3 or configured storage.
    - Dispatches an **asynchronous job** (using Laravel Queues) to process the uploaded file in the background for scalable, non-blocking processing.
    - Returns a JSON response to the client.
- **Asynchronous Job Processing:**  
  File processing is handled in the background via Laravel's job/queue system. To process jobs locally, run:
  ```bash
  php artisan queue:work
  ```
  This command listens for jobs (such as payment file processing) and executes them as they are dispatched.

### Invoice Scheduler

- The application includes a **scheduler** for sending invoices after payments are processed.
- Scheduler configured to run automatically on daily 9pm. If want to manual execute it run:
  ```bash
  php artisan app:send-daily-invoices
  ```

---

## Main Functions & Flow

1. **Receiving Data**
    - Single payment: API endpoint receives data, validates, and saves via `savePaymentRecord`.
    - Multiple payments: CSV is uploaded, processed asynchronously.

2. **Data Cleaning & Validation**
    - Ensures all required fields are present and correctly formatted.
    - Cleans up common user errors (e.g., extra spaces, email typos).

3. **Currency Conversion**
    - Uses the exchange service to convert payment amounts into USD.

4. **Data Persistence**
    - All valid records are saved to the database using the repository layer.

5. **Notifications**
    - Sends a Slack message when a batch job completes, if the number of records processed exceeds a configured alert threshold.

6. **Invoice Generation**
    - A scheduled task/command groups un-invoiced payments, generates invoices, and updates payment records with invoice IDs.

---

## Database

- Payments are stored in the `payments` table.
- Key columns: `customer_id`, `customer_email`, `reference_no`, `payment_date`, `currency`, `amount`, `usd_amount`, `processed`, `invoice_id`.

---

## Notes

- For file uploads, ensure your file size does not exceed the configured `CUSTOM_STORAGE_PAYMENTS_MAX_FILE_SIZE`.
- Exchange rates and Slack notifications depend on the correct configuration of the environment variables above.
- The queue worker and scheduler must be running for background jobs and periodic invoice processing.

---
