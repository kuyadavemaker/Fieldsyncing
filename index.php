<?php
// Main application controller for the company workspace.

declare(strict_types=1);

// Strict session security configuration
ini_set('session.use_only_cookies', '1');
ini_set('session.use_strict_mode', '1');

$cookieParams = [
    'lifetime' => 0,
    'path' => '/',
    'domain' => '',
    'secure' => (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off'),
    'httponly' => true,
    'samesite' => 'Strict'
];
session_set_cookie_params($cookieParams);
session_start();
ob_start();

// Session fixation guard: periodic cycling of session identifier
if (!isset($_SESSION['session_born'])) {
    $_SESSION['session_born'] = time();
} elseif (time() - $_SESSION['session_born'] > 1800) {
    session_regenerate_id(true);
    $_SESSION['session_born'] = time();
}

// Cryptographically secure CSRF token initialization
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

require_once __DIR__ . '/config/CompanyAuth.php';
$database = new Database();
$companyAuth = new CompanyAuth($database);

function fsdimsAuthenticateSession(array $user, ?string $recoveryCode = null): void
{
    session_regenerate_id(true);
    $_SESSION['operator_authenticated'] = true;
    $_SESSION['operator_last_activity'] = time();
    $_SESSION['session_born'] = time();
    $_SESSION['operator_user_id'] = (int)$user['user_id'];
    $_SESSION['operator_company_id'] = (int)$user['company_id'];
    $_SESSION['operator_name'] = (string)$user['full_name'];
    $_SESSION['operator_role'] = (string)$user['role'];
    $_SESSION['operator_company_name'] = (string)$user['company_name'];
    $_SESSION['operational_database'] = (string)$user['operational_database'];
    $_SESSION['login_attempts'] = 0;
    unset($_SESSION['login_locked_until'], $_SESSION['login_error']);
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    if ($recoveryCode !== null) {
        $_SESSION['recovery_code_once'] = $recoveryCode;
    }
}

// Keep public authentication routes independent of domain models.
$page = (string)($_GET['page'] ?? 'dashboard');
$allowedPages = ['dashboard', 'jobs', 'customers', 'equipment', 'technicians', 'inventory', 'invoices'];
if (!in_array($page, $allowedPages, true)) {
    $page = 'dashboard';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && (string)($_POST['action'] ?? '') === 'create_company_account') {
    $postedToken = (string)($_POST['csrf_token'] ?? '');
    if (!hash_equals($_SESSION['csrf_token'], $postedToken)) {
        http_response_code(403);
        $_SESSION['login_error'] = 'Your sign-up token expired. Please try again.';
    } elseif ((string)($_POST['password'] ?? '') !== (string)($_POST['password_confirmation'] ?? '')) {
        $_SESSION['login_error'] = 'The passphrases do not match.';
    } else {
        try {
            $account = $companyAuth->register(
                trim((string)($_POST['first_name'] ?? '') . ' ' . (string)($_POST['last_name'] ?? '')),
                (string)($_POST['email'] ?? ''),
                (string)($_POST['password'] ?? '')
            );
            $_SESSION['recovery_code_once'] = (string)$account['recovery_code'];
            $_SESSION['login_notice'] = 'Your account is ready. Sign in with your operational email and password.';
            header('Location: index.php?auth=login');
            exit;
        } catch (InvalidArgumentException $e) {
            $_SESSION['login_error'] = $e->getMessage();
        } catch (Throwable $e) {
            error_log('[Company account setup error] ' . $e->getMessage());
            $_SESSION['login_error'] = 'Account creation failed. Check the database setup and server log.';
        }
    }

    header('Location: index.php?auth=create');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && (string)($_POST['action'] ?? '') === 'reset_company_password') {
    $postedToken = (string)($_POST['csrf_token'] ?? '');
    if (!hash_equals($_SESSION['csrf_token'], $postedToken)) {
        http_response_code(403);
        $_SESSION['login_error'] = 'Your secure reset token expired. Please try again.';
    } elseif (!empty($_SESSION['login_locked_until']) && (int)$_SESSION['login_locked_until'] > time()) {
        $_SESSION['login_error'] = 'Recovery is temporarily locked. Please try again in a few minutes.';
    } elseif ((string)($_POST['password'] ?? '') !== (string)($_POST['password_confirmation'] ?? '')) {
        $_SESSION['login_error'] = 'The new passphrases do not match.';
    } else {
        try {
            $recoveryCode = $companyAuth->resetPassword(
                (string)($_POST['email'] ?? ''),
                (string)($_POST['recovery_code'] ?? ''),
                (string)($_POST['password'] ?? '')
            );
            $user = $companyAuth->findByEmail((string)($_POST['email'] ?? ''));
            if ($user === null) {
                throw new RuntimeException('Reset account could not be loaded after password reset.');
            }
            fsdimsAuthenticateSession($user, $recoveryCode);
            header('Location: index.php?page=dashboard');
            exit;
        } catch (InvalidArgumentException $e) {
            $_SESSION['login_error'] = $e->getMessage();
        } catch (Throwable $e) {
            $attempts = (int)($_SESSION['login_attempts'] ?? 0) + 1;
            $_SESSION['login_attempts'] = $attempts;
            if ($attempts >= 5) {
                $_SESSION['login_locked_until'] = time() + 300;
                $_SESSION['login_attempts'] = 0;
            }
            $_SESSION['login_error'] = $e instanceof RuntimeException && $e->getMessage() === 'The verified operational email or recovery code is incorrect.'
                ? $e->getMessage()
                : 'Password recovery could not be completed. Check the database configuration or server log.';
            error_log('[Company password reset error] ' . $e->getMessage());
        }
    }

    header('Location: index.php?auth=forgot');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && (string)($_POST['action'] ?? '') === 'login') {
    $postedToken = (string)($_POST['csrf_token'] ?? '');
    if (!hash_equals($_SESSION['csrf_token'], $postedToken)) {
        http_response_code(403);
        $_SESSION['login_error'] = 'Your secure sign-in token expired. Please try again.';
    } elseif (!empty($_SESSION['login_locked_until']) && (int)$_SESSION['login_locked_until'] > time()) {
        $_SESSION['login_error'] = 'Sign-in is temporarily locked. Please try again in a few minutes.';
    } else {
        try {
            $user = $companyAuth->authenticate(
                (string)($_POST['email'] ?? ''),
                (string)($_POST['password'] ?? '')
            );
        } catch (Throwable $e) {
            error_log('[Company authentication error] ' . $e->getMessage());
            http_response_code(503);
            $_SESSION['login_error'] = 'Sign-in is unavailable. Apply the company account and email-login migrations, then check database access.';
            header('Location: index.php?auth=login');
            exit;
        }

        if ($user !== null) {
            fsdimsAuthenticateSession($user);
            header('Location: index.php?page=dashboard');
            exit;
        }

        $attempts = (int)($_SESSION['login_attempts'] ?? 0) + 1;
        $_SESSION['login_attempts'] = $attempts;
        if ($attempts >= 5) {
            $_SESSION['login_locked_until'] = time() + 300;
            $_SESSION['login_attempts'] = 0;
        }
        $_SESSION['login_error'] = 'The operational email or password is incorrect.';
    }

    header('Location: index.php?auth=login');
    exit;
}

$hasCompanySession = isset(
    $_SESSION['operator_user_id'],
    $_SESSION['operator_company_id'],
    $_SESSION['operational_database']
);
$isAuthenticated = !empty($_SESSION['operator_authenticated']) && $hasCompanySession;
if (!empty($_SESSION['operator_authenticated']) && !$hasCompanySession) {
    unset($_SESSION['operator_authenticated'], $_SESSION['operator_last_activity']);
    session_regenerate_id(true);
    $_SESSION['login_error'] = 'Please sign in again to select your company workspace.';
}
if ($isAuthenticated && (string)($_GET['auth'] ?? '') === 'home') {
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    $authMode = 'home';
    $recoveryAvailable = true;
    require __DIR__ . '/views/login.php';
    exit;
}
if ($isAuthenticated && time() - (int)($_SESSION['operator_last_activity'] ?? 0) > 1800) {
    unset(
        $_SESSION['operator_authenticated'],
        $_SESSION['operator_last_activity'],
        $_SESSION['operator_user_id'],
        $_SESSION['operator_company_id'],
        $_SESSION['operator_name'],
        $_SESSION['operator_role'],
        $_SESSION['operator_company_name'],
        $_SESSION['operational_database']
    );
    session_regenerate_id(true);
    $_SESSION['login_error'] = 'Your session expired after inactivity. Please sign in again.';
    $isAuthenticated = false;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && (string)($_POST['action'] ?? '') === 'logout' && $isAuthenticated) {
    $postedToken = (string)($_POST['csrf_token'] ?? '');
    if (!hash_equals($_SESSION['csrf_token'], $postedToken)) {
        http_response_code(403);
        $_SESSION['login_error'] = 'Your secure sign-out token expired. Please sign in again.';
    } else {
        unset(
            $_SESSION['operator_authenticated'],
            $_SESSION['operator_last_activity'],
            $_SESSION['operator_user_id'],
            $_SESSION['operator_company_id'],
            $_SESSION['operator_name'],
            $_SESSION['operator_role'],
            $_SESSION['operator_company_name'],
            $_SESSION['operational_database']
        );
        session_regenerate_id(true);
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        $_SESSION['login_error'] = 'You have been securely signed out.';
    }
    header('Location: index.php');
    exit;
}

if (!$isAuthenticated) {
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    try {
        $accountCount = $companyAuth->accountCount();
    } catch (Throwable $e) {
        error_log('[Company authentication database error] ' . $e->getMessage());
        $accountCount = 0;
        $_SESSION['login_error'] = 'Apply sql/migrations/20261015_company_accounts.sql and sql/migrations/20261023_email_login_identifier.sql before creating accounts.';
    }
    $recoveryAvailable = true;
    $authMode = in_array((string)($_GET['auth'] ?? ''), ['home', 'login', 'create', 'forgot'], true)
        ? (string)$_GET['auth']
        : 'home';
    require __DIR__ . '/views/login.php';
    exit;
}

$_SESSION['operator_last_activity'] = time();
header('Cache-Control: no-store, private');

// Require Core Architecture Modules
require_once __DIR__ . '/config/Database.php';
require_once __DIR__ . '/models/Customer.php';
require_once __DIR__ . '/models/Equipment.php';
require_once __DIR__ . '/models/Technician.php';
require_once __DIR__ . '/models/Inventory.php';
require_once __DIR__ . '/models/Invoice.php';
require_once __DIR__ . '/models/Job.php';

// Initialize Database Engine & Domain Models
$database = new Database();
$db = $database->getConnection();

$customerModel = new Customer($database);
$equipmentModel = new Equipment($database);
$technicianModel = new Technician($database);
$inventoryModel = new Inventory($database);
$invoiceModel = new Invoice($database);
$jobModel = new Job($database);

// Handle authenticated operations.
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Assert CSRF token authenticity
    $postedToken = (string)($_POST['csrf_token'] ?? '');
    if (!hash_equals($_SESSION['csrf_token'], $postedToken)) {
        http_response_code(403);
        $_SESSION['flash_message'] = 'Security validation failed: Invalid or expired CSRF token.';
        $_SESSION['flash_type'] = 'error';
        header("Location: index.php?page={$page}");
        exit;
    }

    $action = (string)($_POST['action'] ?? '');
    $redirectPage = $page;
    $success = false;
    $message = 'Action failed.';
    $messageType = 'error';
    $warnings = [];

    try {
        switch ($action) {
            // Work order actions.
            case 'create_job':
            case 'create':
                $success = $jobModel->addJob(
                    (int)($_POST['customer_id'] ?? 0),
                    (int)($_POST['equipment_id'] ?? 0),
                    (string)($_POST['scheduled_datetime'] ?? ''),
                    (string)($_POST['status'] ?? 'Scheduled'),
                    (string)($_POST['description'] ?? '')
                );
                $message = $success ? 'Work order ticket created and committed to ledger.' : 'Failed to create work order ticket.';
                $messageType = $success ? 'success' : 'error';
                $redirectPage = 'jobs';
                break;

            case 'compound_dispatch':
                $dispatchResult = $jobModel->compoundDispatch(
                    (int)($_POST['customer_id'] ?? 0),
                    (int)($_POST['equipment_id'] ?? 0),
                    (string)($_POST['scheduled_datetime'] ?? date('Y-m-d H:i:s')),
                    (string)($_POST['status'] ?? 'Scheduled'),
                    (string)($_POST['description'] ?? ''),
                    !empty($_POST['technician_id']) ? (int)$_POST['technician_id'] : null,
                    (string)($_POST['tech_role'] ?? 'Lead Technician'),
                    !empty($_POST['part_id']) ? (int)$_POST['part_id'] : null,
                    (int)($_POST['part_quantity'] ?? 1),
                    (float)($_POST['part_price'] ?? 0.0),
                    !empty($_POST['invoice_amount']) ? (float)$_POST['invoice_amount'] : null,
                    (string)($_POST['service_vehicle'] ?? '')
                );
                $success = $dispatchResult['success'];
                $message = $dispatchResult['message'];
                $messageType = $success ? 'success' : 'error';
                $warnings = $dispatchResult['warnings'] ?? [];
                $redirectPage = 'jobs';
                break;

            case 'update_job':
            case 'update':
                $success = $jobModel->updateJob(
                    (int)($_POST['job_id'] ?? 0),
                    (int)($_POST['customer_id'] ?? 0),
                    (int)($_POST['equipment_id'] ?? 0),
                    (string)($_POST['scheduled_datetime'] ?? ''),
                    (string)($_POST['status'] ?? 'Scheduled'),
                    (string)($_POST['description'] ?? '')
                );
                $message = $success ? 'Work order details updated and synchronized.' : 'Failed to update work order.';
                $messageType = $success ? 'success' : 'error';
                $redirectPage = 'jobs';
                break;

            case 'delete_job':
                $success = $jobModel->deleteJob((int)($_POST['job_id'] ?? 0));
                $message = $success ? 'Work order and associated relational records purged.' : 'Failed to delete work order.';
                $messageType = $success ? 'success' : 'error';
                $redirectPage = 'jobs';
                break;

            case 'assign_technician':
                $success = $jobModel->assignTechnician(
                    (int)($_POST['job_id'] ?? 0),
                    (int)($_POST['technician_id'] ?? 0),
                    (string)($_POST['role_on_job'] ?? 'Lead Technician')
                );
                $message = $success ? 'Technician assigned to work order.' : 'Failed to assign technician.';
                $messageType = $success ? 'success' : 'error';
                $redirectPage = 'jobs';
                break;

            case 'log_part_used':
                $success = $jobModel->logPartUsed(
                    (int)($_POST['job_id'] ?? 0),
                    (int)($_POST['part_id'] ?? 0),
                    (int)($_POST['quantity_used'] ?? 1),
                    (float)($_POST['price_charged'] ?? 0.0),
                    (string)($_POST['service_vehicle'] ?? '')
                );
                $message = $success ? 'Part usage logged and inventory stock deducted safely.' : 'Insufficient stock or invalid part selection.';
                $messageType = $success ? 'success' : 'error';
                $redirectPage = 'jobs';
                break;

            // Customer actions.
            case 'create_customer':
                $success = $customerModel->addCustomer(
                    (string)($_POST['full_name'] ?? ''),
                    (string)($_POST['phone'] ?? ''),
                    (string)($_POST['email'] ?? ''),
                    (string)($_POST['billing_address'] ?? '')
                );
                $message = $success ? 'Customer account registered successfully.' : 'Failed to register customer.';
                $messageType = $success ? 'success' : 'error';
                $redirectPage = 'customers';
                break;

            case 'update_customer':
                $success = $customerModel->update(
                    (int)($_POST['customer_id'] ?? 0),
                    (string)($_POST['full_name'] ?? ''),
                    (string)($_POST['phone'] ?? ''),
                    (string)($_POST['email'] ?? ''),
                    (string)($_POST['billing_address'] ?? '')
                );
                $message = $success ? 'Customer details updated.' : 'Failed to update customer account.';
                $messageType = $success ? 'success' : 'error';
                $redirectPage = 'customers';
                break;

            case 'delete_customer':
                $success = $customerModel->deleteCustomer((int)($_POST['customer_id'] ?? 0));
                $message = $success ? 'Customer and linked records deleted.' : 'Failed to delete customer.';
                $messageType = $success ? 'success' : 'error';
                $redirectPage = 'customers';
                break;

            // Equipment actions.
            case 'create_equipment':
                $success = $equipmentModel->create(
                    (int)($_POST['customer_id'] ?? 0),
                    (string)($_POST['equipment_type'] ?? ''),
                    (string)($_POST['model_number'] ?? ''),
                    (string)($_POST['serial_number'] ?? ''),
                    (string)($_POST['install_date'] ?? '')
                );
                $message = $success ? 'Equipment unit registered to customer asset fleet.' : 'Failed to register equipment.';
                $messageType = $success ? 'success' : 'error';
                $redirectPage = 'equipment';
                break;

            case 'update_equipment':
                $success = $equipmentModel->update(
                    (int)($_POST['equipment_id'] ?? 0),
                    (int)($_POST['customer_id'] ?? 0),
                    (string)($_POST['equipment_type'] ?? ''),
                    (string)($_POST['model_number'] ?? ''),
                    (string)($_POST['serial_number'] ?? ''),
                    (string)($_POST['install_date'] ?? '')
                );
                $message = $success ? 'Equipment specifications updated.' : 'Failed to update equipment.';
                $messageType = $success ? 'success' : 'error';
                $redirectPage = 'equipment';
                break;

            case 'delete_equipment':
                $success = $equipmentModel->delete((int)($_POST['equipment_id'] ?? 0));
                $message = $success ? 'Equipment record purged.' : 'Failed to delete equipment record.';
                $messageType = $success ? 'success' : 'error';
                $redirectPage = 'equipment';
                break;

            // Technician and skill actions.
            case 'create_technician':
                $success = $technicianModel->create(
                    (string)($_POST['full_name'] ?? ''),
                    (string)($_POST['license_number'] ?? ''),
                    (string)($_POST['phone'] ?? ''),
                    (string)($_POST['hire_date'] ?? '')
                );
                $message = $success ? 'Field technician registered successfully.' : 'Failed to register technician.';
                $messageType = $success ? 'success' : 'error';
                $redirectPage = 'technicians';
                break;

            case 'update_technician':
                $success = $technicianModel->update(
                    (int)($_POST['technician_id'] ?? 0),
                    (string)($_POST['full_name'] ?? ''),
                    (string)($_POST['license_number'] ?? ''),
                    (string)($_POST['phone'] ?? ''),
                    (string)($_POST['hire_date'] ?? '')
                );
                $message = $success ? 'Technician credentials updated.' : 'Failed to update technician.';
                $messageType = $success ? 'success' : 'error';
                $redirectPage = 'technicians';
                break;

            case 'delete_technician':
                $success = $technicianModel->delete((int)($_POST['technician_id'] ?? 0));
                $message = $success ? 'Technician removed from active duty.' : 'Failed to delete technician.';
                $messageType = $success ? 'success' : 'error';
                $redirectPage = 'technicians';
                break;

            case 'assign_skill':
                $success = $technicianModel->assignSkill(
                    (int)($_POST['technician_id'] ?? 0),
                    (int)($_POST['skill_id'] ?? 0),
                    (string)($_POST['certified_date'] ?? date('Y-m-d'))
                );
                $message = $success ? 'Certification logged to technician profile.' : 'Failed to assign certification.';
                $messageType = $success ? 'success' : 'error';
                $redirectPage = 'technicians';
                break;

            // Inventory actions.
            case 'create_part':
                $success = $inventoryModel->create(
                    (string)($_POST['part_name'] ?? ''),
                    (string)($_POST['sku'] ?? ''),
                    (int)($_POST['quantity'] ?? 0),
                    (float)($_POST['unit_cost'] ?? 0.0)
                );
                $message = $success ? 'Part registered or restocked in warehouse inventory.' : 'Failed to update inventory.';
                $messageType = $success ? 'success' : 'error';
                $redirectPage = 'inventory';
                break;

            case 'update_part':
                $success = $inventoryModel->update(
                    (int)($_POST['part_id'] ?? 0),
                    (string)($_POST['part_name'] ?? ''),
                    (string)($_POST['sku'] ?? ''),
                    (int)($_POST['quantity'] ?? 0),
                    (float)($_POST['unit_cost'] ?? 0.0)
                );
                $message = $success ? 'Inventory item specifications updated.' : 'Failed to update inventory part.';
                $messageType = $success ? 'success' : 'error';
                $redirectPage = 'inventory';
                break;

            case 'delete_part':
                $success = $inventoryModel->delete((int)($_POST['part_id'] ?? 0));
                $message = $success ? 'Part removed from inventory.' : 'Failed to delete inventory part.';
                $messageType = $success ? 'success' : 'error';
                $redirectPage = 'inventory';
                break;

            // Invoice actions.
            case 'generate_invoice':
                $success = $jobModel->createInvoice(
                    (int)($_POST['job_id'] ?? 0),
                    (float)($_POST['total_amount'] ?? 0.0),
                    (string)($_POST['issued_date'] ?? date('Y-m-d')),
                    (string)($_POST['payment_status'] ?? 'Unpaid')
                );
                $message = $success ? 'Billing invoice issued and committed.' : 'Failed to generate invoice.';
                $messageType = $success ? 'success' : 'error';
                $redirectPage = 'invoices';
                break;

            case 'update_invoice_status':
                $success = $invoiceModel->updateStatus(
                    (int)($_POST['invoice_id'] ?? 0),
                    (string)($_POST['payment_status'] ?? 'Unpaid')
                );
                $message = $success ? 'Invoice settlement status updated.' : 'Failed to update invoice status.';
                $messageType = $success ? 'success' : 'error';
                $redirectPage = 'invoices';
                break;

            case 'update_invoice':
                $success = $invoiceModel->update(
                    (int)($_POST['invoice_id'] ?? 0),
                    (float)($_POST['total_amount'] ?? 0.0),
                    (string)($_POST['issued_date'] ?? date('Y-m-d')),
                    (string)($_POST['payment_status'] ?? 'Unpaid')
                );
                $message = $success ? 'Invoice details updated.' : 'Failed to update invoice.';
                $messageType = $success ? 'success' : 'error';
                $redirectPage = 'invoices';
                break;

            case 'delete_invoice':
                $success = $invoiceModel->delete((int)($_POST['invoice_id'] ?? 0));
                $message = $success ? 'Invoice purged from ledger.' : 'Failed to delete invoice.';
                $messageType = $success ? 'success' : 'error';
                $redirectPage = 'invoices';
                break;

            default:
                $message = "Unknown operational command '{$action}'.";
                $messageType = 'error';
        }
    } catch (Throwable $e) {
        error_log('[POST Handler Unhandled Exception] ' . $e->getMessage());
        $success = false;
        $message = 'The operation could not be completed. Review the server log for details.';
        $messageType = 'error';
    }

    // Set flash session variables and redirect (PRG Pattern)
    $_SESSION['flash_message'] = $message;
    $_SESSION['flash_type'] = $messageType;
    if (!empty($warnings)) {
        $_SESSION['flash_warnings'] = $warnings;
    }

    header("Location: index.php?page={$redirectPage}");
    exit;
}

// Retrieve & purge flash session feedback
$flashMessage = $_SESSION['flash_message'] ?? '';
$flashType = $_SESSION['flash_type'] ?? '';
$flashWarnings = $_SESSION['flash_warnings'] ?? [];
unset($_SESSION['flash_message'], $_SESSION['flash_type'], $_SESSION['flash_warnings']);

// Load data for the selected page.
$searchQuery = trim((string)($_GET['search'] ?? ''));

// Roster lists for dropdown selector dependencies
$allJobs = $jobModel->getJobs();
$allInvoices = $invoiceModel->getAll();
$customers = $customerModel->getCustomers();
$equipment = $equipmentModel->getAll();
$technicianRows = $technicianModel->getAll();
$inventoryRows = $inventoryModel->getAll();
$skillRows = $technicianModel->getSkills();
$invoiceJobs = $allJobs;

// Page-specific main entities
$jobs = ($page === 'jobs') ? $jobModel->searchJobs($searchQuery) : [];
$customerRows = ($page === 'customers') ? $customerModel->getAll() : [];
$equipmentRows = ($page === 'equipment') ? $equipmentModel->getAll() : [];
$invoiceRows = ($page === 'invoices') ? $allInvoices : [];

// Aggregate System KPIs
$stats = [
    'total_jobs' => count($allJobs),
    'total_customers' => count($customers),
    'total_equipment' => count($equipment),
    'total_technicians' => count($technicianRows),
    'total_parts' => count($inventoryRows),
    'low_stock_parts' => count(array_filter($inventoryRows, fn($p) => (int)$p['quantity_in_stock'] <= 5)),
    'total_invoices' => count($allInvoices),
];

// Page metadata descriptors
$pageTitles = [
    'dashboard' => 'Overview',
    'jobs' => 'Dispatch',
    'customers' => 'Customers',
    'equipment' => 'Assets',
    'technicians' => 'Team',
    'inventory' => 'Inventory',
    'invoices' => 'Invoices'
];

$pageEyebrows = [
    'dashboard' => 'Field Service Dispatch // Live Operations',
    'jobs' => 'Operational Sector 01 // Field Dispatch',
    'customers' => 'Operational Sector 02 // Account Master',
    'equipment' => 'Operational Sector 03 // Hardware Inventory',
    'technicians' => 'Operational Sector 04 // Workforce Command',
    'inventory' => 'Operational Sector 05 // Logistics & Parts',
    'invoices' => 'Operational Sector 06 // Financial Ledger'
];

$pageDescriptions = [
    'dashboard' => 'A live operational overview of service work, customer activity, and parts availability.',
    'jobs' => 'Centralized work order dispatch board with ACID state management and technician routing.',
    'customers' => 'Commercial client accounts, service locations, and associated equipment assets.',
    'equipment' => 'Customer hardware registry, serial numbers, commissioning records, and field diagnostics.',
    'technicians' => 'Certified field force, active work allocations, licenses, and technical credentials.',
    'inventory' => 'Warehouse spare parts catalog, unit valuations, SKU tracking, and real-time stock levels.',
    'invoices' => 'Commercial billing matrix, work order receivables, and settlement tracking.'
];

$pageTitle = $pageTitles[$page] ?? 'Operations Workstation';
$pageEyebrow = $pageEyebrows[$page] ?? 'Master Workstation';
$pageDescription = $pageDescriptions[$page] ?? '';

// Render the selected view.
require_once __DIR__ . '/views/header.php';

switch ($page) {
    case 'dashboard':
        require_once __DIR__ . '/views/dashboard.php';
        break;
    case 'jobs':
        require_once __DIR__ . '/views/job-list.php';
        require_once __DIR__ . '/views/job-modals.php';
        break;
    case 'customers':
        require_once __DIR__ . '/views/customer-list.php';
        break;
    case 'equipment':
        require_once __DIR__ . '/views/equipment-list.php';
        break;
    case 'technicians':
        require_once __DIR__ . '/views/technician-list.php';
        break;
    case 'inventory':
        require_once __DIR__ . '/views/inventory-list.php';
        break;
    case 'invoices':
        require_once __DIR__ . '/views/invoice-list.php';
        break;
}

require_once __DIR__ . '/views/footer.php';
ob_end_flush();