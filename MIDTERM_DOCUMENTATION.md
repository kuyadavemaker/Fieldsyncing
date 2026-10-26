# FieldSync Midterm Documentation

## Company Accounts and Local Setup

The auth screen supports multiple registrations. Each registration asks for the
dispatcher’s name, operational email, and password; the operational email is also
the account username and sign-in identifier. No separate Operator ID is required.
The system creates a private workspace name automatically, assigns the initial
**Head Dispatcher** role, and provisions a separate operational database for the
account. The dispatcher can manage jobs, customers, equipment, technicians,
inventory, van/truck usage, and invoices in that workspace. Company workspaces
start with an empty operational database; they do not copy another company's
customer or job records.

For an existing `fieldsync_db`, run `sql/migrations/20261015_company_accounts.sql`
and `sql/migrations/20261023_email_login_identifier.sql` in phpMyAdmin's SQL tab.
The second migration widens the username column to hold a full email address. A
fresh install can use `sql/fieldsync_db.sql`, which includes the account tables.
The MySQL account used by `config/Database.php` must
be allowed to create databases and tables for company workspaces. Set appropriate
database credentials there before deploying beyond a local development server.

The sign-up form stores a password hash (never the passphrase), assigns the fixed
Head Dispatcher role, and creates a one-time 64-character recovery code. Save the
recovery code privately when it appears after registration. The password reset
flow invalidates the old code and issues a replacement.

The login gate runs before operational database connection or domain-model
initialization. Sessions use HTTP-only, SameSite cookies, session ID rotation,
CSRF validation, a five-attempt temporary lockout, and a 30-minute idle timeout.

Accounts that already have an operational email can sign in with that email and
their password. Legacy Operator ID-only environment credentials do not contain an
email and are not accepted by the email sign-in flow; provision an email-backed
company account before removing those legacy credentials.

## Completion and Inventory Behavior

Changing a job to `Completed` creates an unpaid invoice in the same database
transaction if one does not already exist. The invoice total is calculated from
the job's logged parts (`quantity_used × price_charged`); the current schema has
no labor-rate or labor-amount field. Existing invoices are preserved. Part
usage logging locks the inventory row and applies the usage record and stock
deduction within a savepoint, while also recording each allocation against the
entered service van/truck ID in `inventory_usage_log`. If stock validation
fails during compound dispatch, that allocation is rolled back while the job
and completion invoice can still commit.

Fresh installs create the usage ledger from `sql/fieldsync_db.sql`. For an
existing database, apply `sql/migrations/20261015_inventory_usage_log.sql`
before using vehicle-level part logging.

## Standalone Code Snippets

### 1) Add Record

```php
<?php
$pdo = new PDO('mysql:host=localhost;dbname=fieldsync_db;charset=utf8mb4', 'root', '');

$statement = $pdo->prepare(
    'INSERT INTO jobs (customer_id, equipment_id, scheduled_datetime, status, description)
     VALUES (:customer_id, :equipment_id, :scheduled_datetime, :status, :description)'
);

$statement->bindValue(':customer_id', (int) $_POST['customer_id'], PDO::PARAM_INT);
$statement->bindValue(':equipment_id', (int) $_POST['equipment_id'], PDO::PARAM_INT);
$statement->bindValue(':scheduled_datetime', trim($_POST['scheduled_datetime']), PDO::PARAM_STR);
$statement->bindValue(':status', trim($_POST['status']), PDO::PARAM_STR);
$statement->bindValue(':description', trim($_POST['description']), PDO::PARAM_STR);

$statement->execute();
```

### 2) Fetch Records

```php
<?php
$statement = $pdo->prepare(
    'SELECT j.job_id, j.status, j.scheduled_datetime, j.description,
            c.full_name AS customer_name,
            e.equipment_type,
            e.model_number
     FROM jobs j
     INNER JOIN customers c ON c.customer_id = j.customer_id
     INNER JOIN equipment e ON e.equipment_id = j.equipment_id
     ORDER BY j.scheduled_datetime DESC'
);

$statement->execute();
$jobs = $statement->fetchAll(PDO::FETCH_ASSOC);
```

### 3) Update Record

```php
<?php
$statement = $pdo->prepare(
    'UPDATE jobs
     SET customer_id = :customer_id,
         equipment_id = :equipment_id,
         scheduled_datetime = :scheduled_datetime,
         status = :status,
         description = :description
     WHERE job_id = :job_id'
);

$statement->bindValue(':job_id', (int) $_POST['job_id'], PDO::PARAM_INT);
$statement->bindValue(':customer_id', (int) $_POST['customer_id'], PDO::PARAM_INT);
$statement->bindValue(':equipment_id', (int) $_POST['equipment_id'], PDO::PARAM_INT);
$statement->bindValue(':scheduled_datetime', trim($_POST['scheduled_datetime']), PDO::PARAM_STR);
$statement->bindValue(':status', trim($_POST['status']), PDO::PARAM_STR);
$statement->bindValue(':description', trim($_POST['description']), PDO::PARAM_STR);

$statement->execute();
```

### 4) Delete Record

```php
<?php
$statement = $pdo->prepare('DELETE FROM jobs WHERE job_id = :job_id');
$statement->bindValue(':job_id', (int) $_POST['job_id'], PDO::PARAM_INT);
$statement->execute();
```

### 5) Search Records

```php
<?php
$keyword = trim($_GET['search'] ?? '');
$term = '%' . $keyword . '%';

$statement = $pdo->prepare(
    'SELECT j.job_id, j.status, j.description, j.scheduled_datetime,
            c.full_name AS customer_name,
            e.equipment_type
     FROM jobs j
     INNER JOIN customers c ON c.customer_id = j.customer_id
     INNER JOIN equipment e ON e.equipment_id = j.equipment_id
     WHERE j.job_id LIKE :keyword
        OR j.status LIKE :keyword
        OR j.description LIKE :keyword
        OR c.full_name LIKE :keyword
        OR e.equipment_type LIKE :keyword
     ORDER BY j.scheduled_datetime DESC'
);

$statement->bindValue(':keyword', $term, PDO::PARAM_STR);
$statement->execute();
$results = $statement->fetchAll(PDO::FETCH_ASSOC);
```

## Step-by-Step Technical Explanations

1. Adding a Record
   - The user submits the modal form to the controller in `index.php`.
   - The action is checked and validated against the CSRF token before processing.
   - The model method sanitizes values with `trim()` and converts numeric values to `int` or `float` where required.
   - The SQL statement is prepared with named placeholders, each value is bound with `bindValue()`, and the statement executes.
   - This protects against SQL injection and keeps the insert consistent with the `jobs` table schema.

2. Fetching Records
   - The app uses relational `INNER JOIN` queries to merge `jobs` with `customers` and `equipment`.
   - `SELECT ... FROM jobs j INNER JOIN customers c ... INNER JOIN equipment e ...` returns a single row with both customer and asset information.
   - The result is retrieved using `fetchAll(PDO::FETCH_ASSOC)`, which returns an associative array for the view layer.
   - Each value is output in the page using `htmlspecialchars()` to prevent cross-site scripting and maintain safe display.

3. Updating a Record
   - The edit modal is pre-populated from the selected job record before it is displayed.
   - The user changes the fields and submits the form with the primary key (`job_id`) and updated values.
   - The model executes an `UPDATE jobs SET ... WHERE job_id = :job_id` query using parameter binding for each field.
   - This ensures the right row is changed without exposing raw SQL or allowing malformed input.

4. Deleting a Record
   - The delete action receives the integer `job_id` from the form and passes it to the model method.
   - The SQL is a prepared `DELETE FROM jobs WHERE job_id = :job_id` statement.
   - Because the schema uses foreign keys with `ON DELETE CASCADE`, related rows in `job_technicians`, `job_parts_used`, and `invoices` are removed automatically when the parent job is deleted.
   - This protects data integrity and keeps the database consistent after record removal.

5. Searching Records
   - The search form submits a keyword through the GET request and the controller loads `searchJobs()`.
   - The keyword is enclosed in `%` wildcards so partial matches work for any field.
   - The query checks multiple columns: `job_id`, `status`, `description`, `customer full_name`, and `equipment_type`.
   - The prepared statement binds one wildcard parameter and executes against the database, returning the matching job rows in a safe, reusable way.

## Project Notes

- Main entry point: `index.php`
- Database schema: `sql/fieldsync_db.sql`
- Core job logic: `models/Job.php`
- Job form handling: `views/job-modals.php`
- Backend routing: `index.php`

This project uses PDO prepared statements, relational database joins, and normalized table relationships to keep the FieldSync application reliable and maintainable.
