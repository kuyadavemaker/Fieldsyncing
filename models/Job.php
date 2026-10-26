<?php
// Job and dispatch data access.

declare(strict_types=1);

require_once __DIR__ . '/../config/Database.php';

final class Job
{
    private PDO $pdo;
    private Database $dbWrapper;
    public const ALLOWED_STATUSES = ['Scheduled', 'In Progress', 'Completed', 'Cancelled'];

    public function __construct(PDO|Database|null $db = null)
    {
        if ($db instanceof Database) {
            $this->dbWrapper = $db;
            $this->pdo = $db->getConnection();
        } elseif ($db instanceof PDO) {
            $this->pdo = $db;
            $this->dbWrapper = new Database();
        } else {
            $this->dbWrapper = new Database();
            $this->pdo = $this->dbWrapper->getConnection();
        }
    }

    // Retrieve all jobs with relational joins (customer, equipment, technicians, parts used, invoice)
    public function getJobs(): array
    {
        try {
            $sql = "SELECT
                        j.job_id,
                        j.customer_id,
                        j.equipment_id,
                        j.scheduled_datetime,
                        j.status,
                        j.description,
                        j.created_at,
                        c.full_name AS customer_name,
                        c.phone AS customer_phone,
                        c.email AS customer_email,
                        c.billing_address AS customer_address,
                        e.equipment_type,
                        e.model_number,
                        e.serial_number,
                        (SELECT GROUP_CONCAT(CONCAT(t.full_name, ' [', jt.role_on_job, ']') SEPARATOR ', ')
                         FROM job_technicians jt
                         INNER JOIN technicians t ON t.technician_id = jt.technician_id
                         WHERE jt.job_id = j.job_id) AS assigned_technicians,
                        (SELECT GROUP_CONCAT(CONCAT(p.part_name, ' (x', jpu.quantity_used, ' @ $', jpu.price_charged, ')') SEPARATOR ', ')
                         FROM job_parts_used jpu
                         INNER JOIN parts_inventory p ON p.part_id = jpu.part_id
                         WHERE jpu.job_id = j.job_id) AS parts_used,
                        (SELECT GROUP_CONCAT(DISTINCT iul.vehicle_label ORDER BY iul.vehicle_label SEPARATOR ', ')
                         FROM inventory_usage_log iul
                         WHERE iul.job_id = j.job_id) AS service_vehicles,
                        inv.invoice_id,
                        inv.total_amount AS invoice_total,
                        inv.payment_status AS invoice_status
                    FROM jobs j
                    INNER JOIN customers c ON j.customer_id = c.customer_id
                    INNER JOIN equipment e ON j.equipment_id = e.equipment_id
                    LEFT JOIN invoices inv ON inv.job_id = j.job_id
                    ORDER BY j.scheduled_datetime DESC, j.job_id DESC";

            $stmt = $this->pdo->query($sql);
            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (PDOException $e) {
            error_log('[Job::getJobs Error] ' . $e->getMessage());
            return [];
        }
    }

    // Fetch single job ticket by primary key
    public function getJobById(int $jobId): ?array
    {
        if ($jobId <= 0) {
            return null;
        }

        try {
            $sql = "SELECT
                        j.*,
                        c.full_name AS customer_name,
                        c.phone AS customer_phone,
                        e.equipment_type,
                        e.model_number,
                        e.serial_number,
                        inv.invoice_id,
                        inv.total_amount AS invoice_total,
                        inv.payment_status AS invoice_status
                    FROM jobs j
                    INNER JOIN customers c ON j.customer_id = c.customer_id
                    INNER JOIN equipment e ON j.equipment_id = e.equipment_id
                    LEFT JOIN invoices inv ON inv.job_id = j.job_id
                    WHERE j.job_id = :job_id
                    LIMIT 1";

            $stmt = $this->pdo->prepare($sql);
            $stmt->bindValue(':job_id', $jobId, PDO::PARAM_INT);
            $stmt->execute();
            $job = $stmt->fetch(PDO::FETCH_ASSOC);
            return $job ?: null;
        } catch (PDOException $e) {
            error_log('[Job::getJobById Error] ' . $e->getMessage());
            return null;
        }
    }

    // Create new work order ticket within an ACID transaction
    public function addJob(
        int|string $customerId,
        int|string $equipmentId,
        string $scheduledDatetime,
        string $status,
        string $description
    ): bool {
        $customerId = (int) $customerId;
        $equipmentId = (int) $equipmentId;
        $scheduledDatetime = trim($scheduledDatetime);
        $status = in_array(trim($status), self::ALLOWED_STATUSES, true) ? trim($status) : 'Scheduled';
        $description = trim($description);

        if ($customerId <= 0 || $equipmentId <= 0 || $scheduledDatetime === '') {
            return false;
        }

        try {
            return (bool) $this->dbWrapper->transaction(function (PDO $pdo) use ($customerId, $equipmentId, $scheduledDatetime, $status, $description) {
                // Verify customer and equipment foreign integrity
                $custCheck = $pdo->prepare('SELECT customer_id FROM customers WHERE customer_id = :cid');
                $custCheck->execute([':cid' => $customerId]);
                if (!$custCheck->fetch()) {
                    throw new InvalidArgumentException("Customer #{$customerId} not found.");
                }

                $equipCheck = $pdo->prepare('SELECT equipment_id FROM equipment WHERE equipment_id = :eid');
                $equipCheck->execute([':eid' => $equipmentId]);
                if (!$equipCheck->fetch()) {
                    throw new InvalidArgumentException("Equipment #{$equipmentId} not found.");
                }

                $stmt = $pdo->prepare(
                    'INSERT INTO jobs (customer_id, equipment_id, scheduled_datetime, status, description)
                     VALUES (:customer_id, :equipment_id, :scheduled_datetime, :status, :description)'
                );
                return $stmt->execute([
                    ':customer_id' => $customerId,
                    ':equipment_id' => $equipmentId,
                    ':scheduled_datetime' => $scheduledDatetime,
                    ':status' => $status,
                    ':description' => $description !== '' ? $description : null,
                ]);
            });
        } catch (Throwable $e) {
            error_log('[Job::addJob Error] ' . $e->getMessage());
            return false;
        }
    }

    // Update existing work order ticket within an ACID transaction
    public function updateJob(
        int|string $jobId,
        int|string $customerId,
        int|string $equipmentId,
        string $scheduledDatetime,
        string $status,
        string $description
    ): bool {
        $jobId = (int) $jobId;
        $customerId = (int) $customerId;
        $equipmentId = (int) $equipmentId;
        $scheduledDatetime = trim($scheduledDatetime);
        $status = in_array(trim($status), self::ALLOWED_STATUSES, true) ? trim($status) : 'Scheduled';
        $description = trim($description);

        if ($jobId <= 0 || $customerId <= 0 || $equipmentId <= 0 || $scheduledDatetime === '') {
            return false;
        }

        try {
            return (bool) $this->dbWrapper->transaction(function (PDO $pdo) use ($jobId, $customerId, $equipmentId, $scheduledDatetime, $status, $description) {
                $existingJob = $pdo->prepare('SELECT status FROM jobs WHERE job_id = :job_id FOR UPDATE');
                $existingJob->execute([':job_id' => $jobId]);
                if (!$existingJob->fetch(PDO::FETCH_ASSOC)) {
                    return false;
                }

                $stmt = $pdo->prepare(
                    'UPDATE jobs
                     SET customer_id = :customer_id,
                         equipment_id = :equipment_id,
                         scheduled_datetime = :scheduled_datetime,
                         status = :status,
                         description = :description
                     WHERE job_id = :job_id'
                );
                if (!$stmt->execute([
                    ':job_id' => $jobId,
                    ':customer_id' => $customerId,
                    ':equipment_id' => $equipmentId,
                    ':scheduled_datetime' => $scheduledDatetime,
                    ':status' => $status,
                    ':description' => $description !== '' ? $description : null,
                ])) {
                    return false;
                }

                if ($status === 'Completed') {
                    $this->createCompletionInvoice($pdo, $jobId);
                }
                return true;
            });
        } catch (Throwable $e) {
            error_log('[Job::updateJob Error] ' . $e->getMessage());
            return false;
        }
    }

    // Delete job ticket with cascading foreign key consistency
    public function deleteJob(int|string $jobId): bool
    {
        $jobId = (int) $jobId;
        if ($jobId <= 0) {
            return false;
        }

        try {
            return (bool) $this->dbWrapper->transaction(function (PDO $pdo) use ($jobId) {
                $stmt = $pdo->prepare('DELETE FROM jobs WHERE job_id = :job_id');
                return $stmt->execute([':job_id' => $jobId]);
            });
        } catch (Throwable $e) {
            error_log('[Job::deleteJob Error] ' . $e->getMessage());
            return false;
        }
    }

    // Deep relational search across tickets, customers, equipment, technicians, and parts
    public function searchJobs(string $keyword): array
    {
        $keyword = trim($keyword);
        if ($keyword === '') {
            return $this->getJobs();
        }

        try {
            $sql = "SELECT
                        j.job_id,
                        j.customer_id,
                        j.equipment_id,
                        j.scheduled_datetime,
                        j.status,
                        j.description,
                        j.created_at,
                        c.full_name AS customer_name,
                        c.phone AS customer_phone,
                        c.email AS customer_email,
                        c.billing_address AS customer_address,
                        e.equipment_type,
                        e.model_number,
                        e.serial_number,
                        (SELECT GROUP_CONCAT(CONCAT(t.full_name, ' [', jt.role_on_job, ']') SEPARATOR ', ')
                         FROM job_technicians jt
                         INNER JOIN technicians t ON t.technician_id = jt.technician_id
                         WHERE jt.job_id = j.job_id) AS assigned_technicians,
                        (SELECT GROUP_CONCAT(CONCAT(p.part_name, ' (x', jpu.quantity_used, ' @ $', jpu.price_charged, ')') SEPARATOR ', ')
                         FROM job_parts_used jpu
                         INNER JOIN parts_inventory p ON p.part_id = jpu.part_id
                         WHERE jpu.job_id = j.job_id) AS parts_used,
                        (SELECT GROUP_CONCAT(DISTINCT iul.vehicle_label ORDER BY iul.vehicle_label SEPARATOR ', ')
                         FROM inventory_usage_log iul
                         WHERE iul.job_id = j.job_id) AS service_vehicles,
                        inv.invoice_id,
                        inv.total_amount AS invoice_total,
                        inv.payment_status AS invoice_status
                    FROM jobs j
                    INNER JOIN customers c ON j.customer_id = c.customer_id
                    INNER JOIN equipment e ON j.equipment_id = e.equipment_id
                    LEFT JOIN invoices inv ON inv.job_id = j.job_id
                    WHERE CAST(j.job_id AS CHAR) LIKE :kw
                       OR j.status LIKE :kw
                       OR j.description LIKE :kw
                       OR c.full_name LIKE :kw
                       OR c.phone LIKE :kw
                       OR e.equipment_type LIKE :kw
                       OR e.model_number LIKE :kw
                       OR e.serial_number LIKE :kw
                    ORDER BY j.scheduled_datetime DESC, j.job_id DESC";

            $stmt = $this->pdo->prepare($sql);
            $stmt->bindValue(':kw', "%{$keyword}%", PDO::PARAM_STR);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (PDOException $e) {
            error_log('[Job::searchJobs Error] ' . $e->getMessage());
            return [];
        }
    }

    // Assign technician to job with upsert role handling
    public function assignTechnician(int $jobId, int $technicianId, string $role = 'Lead Technician'): bool
    {
        if ($jobId <= 0 || $technicianId <= 0) {
            return false;
        }

        $role = trim($role) !== '' ? trim($role) : 'Lead Technician';

        try {
            return (bool) $this->dbWrapper->transaction(function (PDO $pdo) use ($jobId, $technicianId, $role) {
                // Assert technician existence
                $check = $pdo->prepare('SELECT technician_id FROM technicians WHERE technician_id = :id');
                $check->execute([':id' => $technicianId]);
                if (!$check->fetch()) {
                    throw new InvalidArgumentException("Technician #{$technicianId} not found.");
                }

                $stmt = $pdo->prepare(
                    'INSERT INTO job_technicians (job_id, technician_id, role_on_job)
                     VALUES (:job_id, :technician_id, :role)
                     ON DUPLICATE KEY UPDATE role_on_job = VALUES(role_on_job)'
                );
                return $stmt->execute([
                    ':job_id' => $jobId,
                    ':technician_id' => $technicianId,
                    ':role' => $role,
                ]);
            });
        } catch (Throwable $e) {
            error_log('[Job::assignTechnician Error] ' . $e->getMessage());
            return false;
        }
    }

    // Unassign technician from job
    public function removeTechnician(int $jobId, int $technicianId): bool
    {
        if ($jobId <= 0 || $technicianId <= 0) {
            return false;
        }

        try {
            $stmt = $this->pdo->prepare(
                'DELETE FROM job_technicians WHERE job_id = :job_id AND technician_id = :technician_id'
            );
            return $stmt->execute([
                ':job_id' => $jobId,
                ':technician_id' => $technicianId,
            ]);
        } catch (PDOException $e) {
            error_log('[Job::removeTechnician Error] ' . $e->getMessage());
            return false;
        }
    }

    // Log part usage, deduct stock, and roll back the savepoint if stock is insufficient.
    public function logPartUsed(int $jobId, int $partId, int $quantity, float $priceCharged, string $vehicleLabel = ''): bool
    {
        if ($jobId <= 0 || $partId <= 0 || $quantity <= 0) {
            return false;
        }

        $vehicleLabel = trim($vehicleLabel);
        if ($vehicleLabel === '' || strlen($vehicleLabel) > 100) {
            return false;
        }
        $priceCharged = max(0.0, $priceCharged);

        try {
            return (bool) $this->dbWrapper->transaction(function (PDO $pdo, Database $db) use ($jobId, $partId, $quantity, $priceCharged, $vehicleLabel) {
                // Execute part allocation within an intermediate SAVEPOINT sandbox
                return $db->savepointSandbox('sp_part_usage', function (PDO $pdo) use ($jobId, $partId, $quantity, $priceCharged, $vehicleLabel) {
                    // Row lock on target inventory item to prevent race conditions
                    $lockStmt = $pdo->prepare('SELECT quantity_in_stock, part_name FROM parts_inventory WHERE part_id = :part_id FOR UPDATE');
                    $lockStmt->execute([':part_id' => $partId]);
                    $part = $lockStmt->fetch(PDO::FETCH_ASSOC);

                    if (!$part) {
                        throw new RuntimeException("Part #{$partId} does not exist in inventory.");
                    }

                    $currentStock = (int) $part['quantity_in_stock'];
                    if ($currentStock < $quantity) {
                        throw new RuntimeException("Insufficient stock for '{$part['part_name']}'. Required: {$quantity}, In stock: {$currentStock}.");
                    }

                    // Record parts used
                    $useStmt = $pdo->prepare(
                        'INSERT INTO job_parts_used (job_id, part_id, quantity_used, price_charged)
                         VALUES (:job_id, :part_id, :quantity, :price)
                         ON DUPLICATE KEY UPDATE
                            quantity_used = quantity_used + VALUES(quantity_used),
                            price_charged = VALUES(price_charged)'
                    );
                    $useStmt->execute([
                        ':job_id' => $jobId,
                        ':part_id' => $partId,
                        ':quantity' => $quantity,
                        ':price' => $priceCharged,
                    ]);

                    $usageStmt = $pdo->prepare(
                        'INSERT INTO inventory_usage_log (job_id, part_id, vehicle_label, quantity_used, price_charged)
                         VALUES (:job_id, :part_id, :vehicle_label, :quantity, :price)'
                    );
                    $usageStmt->execute([
                        ':job_id' => $jobId,
                        ':part_id' => $partId,
                        ':vehicle_label' => $vehicleLabel,
                        ':quantity' => $quantity,
                        ':price' => $priceCharged,
                    ]);

                    // Deduct inventory stock
                    $deductStmt = $pdo->prepare(
                        'UPDATE parts_inventory
                         SET quantity_in_stock = quantity_in_stock - :quantity
                         WHERE part_id = :part_id'
                    );
                    $deductStmt->execute([
                        ':quantity' => $quantity,
                        ':part_id' => $partId,
                    ]);

                    return true;
                });
            });
        } catch (Throwable $e) {
            error_log('[Job::logPartUsed Savepoint Rollback] ' . $e->getMessage());
            return false;
        }
    }

    // Create a job with optional technician, part, and invoice setup using savepoints.
    public function compoundDispatch(
        int $customerId,
        int $equipmentId,
        string $scheduledDatetime,
        string $status,
        string $description,
        ?int $technicianId = null,
        string $role = 'Lead Tech',
        ?int $partId = null,
        int $partQty = 1,
        float $partPrice = 0.0,
        ?float $invoiceAmount = null,
        string $vehicleLabel = ''
    ): array {
        $result = [
            'success' => false,
            'job_id' => null,
            'warnings' => [],
            'message' => ''
        ];

        try {
            $this->dbWrapper->transaction(function (PDO $pdo, Database $db) use (
                $customerId, $equipmentId, $scheduledDatetime, $status, $description,
                $technicianId, $role, $partId, $partQty, $partPrice, $invoiceAmount, $vehicleLabel,
                &$result
            ) {
                // Step 1: Create Job
                $jobStmt = $pdo->prepare(
                    'INSERT INTO jobs (customer_id, equipment_id, scheduled_datetime, status, description)
                     VALUES (:cid, :eid, :dt, :status, :desc)'
                );
                $jobStmt->execute([
                    ':cid' => $customerId,
                    ':eid' => $equipmentId,
                    ':dt' => $scheduledDatetime,
                    ':status' => in_array($status, self::ALLOWED_STATUSES, true) ? $status : 'Scheduled',
                    ':desc' => trim($description) ?: null,
                ]);

                $jobId = (int) $pdo->lastInsertId();
                $result['job_id'] = $jobId;

                // Step 2: Assign Technician inside intermediate Savepoint
                if ($technicianId !== null && $technicianId > 0) {
                    try {
                        $db->savepointSandbox('sp_tech_dispatch', function (PDO $pdo) use ($jobId, $technicianId, $role) {
                            $techStmt = $pdo->prepare(
                                'INSERT INTO job_technicians (job_id, technician_id, role_on_job)
                                 VALUES (:jid, :tid, :role)'
                            );
                            $techStmt->execute([':jid' => $jobId, ':tid' => $technicianId, ':role' => $role]);
                        });
                    } catch (Throwable $e) {
                        $result['warnings'][] = "Technician dispatch skipped: " . $e->getMessage();
                    }
                }

                // Step 3: Allocate Part inside intermediate Savepoint
                if ($partId !== null && $partId > 0 && $partQty > 0) {
                    try {
                        $db->savepointSandbox('sp_initial_part', function (PDO $pdo) use ($jobId, $partId, $partQty, $partPrice, $vehicleLabel) {
                            if (trim($vehicleLabel) === '' || strlen(trim($vehicleLabel)) > 100) {
                                throw new InvalidArgumentException('A service van or truck identifier is required for part allocation.');
                            }
                            $lock = $pdo->prepare('SELECT quantity_in_stock, part_name FROM parts_inventory WHERE part_id = :pid FOR UPDATE');
                            $lock->execute([':pid' => $partId]);
                            $stock = $lock->fetch(PDO::FETCH_ASSOC);
                            if (!$stock || (int) $stock['quantity_in_stock'] < $partQty) {
                                throw new RuntimeException("Insufficient stock for part #{$partId}.");
                            }

                            $use = $pdo->prepare(
                                'INSERT INTO job_parts_used (job_id, part_id, quantity_used, price_charged)
                                 VALUES (:jid, :pid, :qty, :price)'
                            );
                            $use->execute([':jid' => $jobId, ':pid' => $partId, ':qty' => $partQty, ':price' => $partPrice]);

                            $usage = $pdo->prepare(
                                'INSERT INTO inventory_usage_log (job_id, part_id, vehicle_label, quantity_used, price_charged)
                                 VALUES (:jid, :pid, :vehicle_label, :qty, :price)'
                            );
                            $usage->execute([
                                ':jid' => $jobId,
                                ':pid' => $partId,
                                ':vehicle_label' => trim($vehicleLabel),
                                ':qty' => $partQty,
                                ':price' => $partPrice,
                            ]);

                            $ded = $pdo->prepare('UPDATE parts_inventory SET quantity_in_stock = quantity_in_stock - :qty WHERE part_id = :pid');
                            $ded->execute([':qty' => $partQty, ':pid' => $partId]);
                        });
                    } catch (Throwable $e) {
                        $result['warnings'][] = "Part allocation skipped: " . $e->getMessage();
                    }
                }

                // Step 4: Invoice Generation inside intermediate Savepoint
                if ($invoiceAmount !== null && $invoiceAmount > 0) {
                    try {
                        $db->savepointSandbox('sp_invoice_init', function (PDO $pdo) use ($jobId, $invoiceAmount) {
                            $inv = $pdo->prepare(
                                'INSERT INTO invoices (job_id, total_amount, issued_date, payment_status)
                                 VALUES (:jid, :amt, :dt, :status)'
                            );
                            $inv->execute([
                                ':jid' => $jobId,
                                ':amt' => $invoiceAmount,
                                ':dt' => date('Y-m-d'),
                                ':status' => 'Unpaid',
                            ]);
                        });
                    } catch (Throwable $e) {
                        $result['warnings'][] = "Invoice initialization skipped: " . $e->getMessage();
                    }
                }

                if ($status === 'Completed') {
                    $this->createCompletionInvoice($pdo, $jobId);
                }

                $result['success'] = true;
                $result['message'] = "Work order #{$jobId} dispatched successfully.";
            });
        } catch (Throwable $e) {
            $result['success'] = false;
            $result['message'] = "Compound dispatch failed: " . $e->getMessage();
            error_log('[Job::compoundDispatch Fatal Rollback] ' . $e->getMessage());
        }

        return $result;
    }

    // Generate or update invoice attached to job
    public function createInvoice(int $jobId, float $totalAmount, string $issuedDate, string $paymentStatus = 'Unpaid'): bool
    {
        $invoiceModel = new Invoice($this->dbWrapper);
        return $invoiceModel->generate($jobId, $totalAmount, $issuedDate, $paymentStatus);
    }

    private function createCompletionInvoice(PDO $pdo, int $jobId): void
    {
        $existingInvoice = $pdo->prepare('SELECT invoice_id FROM invoices WHERE job_id = :job_id LIMIT 1');
        $existingInvoice->execute([':job_id' => $jobId]);
        if ($existingInvoice->fetch(PDO::FETCH_ASSOC)) {
            return;
        }

        $amountQuery = $pdo->prepare(
            'SELECT COALESCE(
                (SELECT SUM(quantity_used * price_charged)
                 FROM inventory_usage_log
                 WHERE job_id = :usage_job_id),
                (SELECT SUM(quantity_used * price_charged)
                 FROM job_parts_used
                 WHERE job_id = :legacy_job_id),
                0
             ) AS parts_total'
        );
        $amountQuery->execute([
            ':usage_job_id' => $jobId,
            ':legacy_job_id' => $jobId,
        ]);
        $amount = (float)$amountQuery->fetchColumn();

        $invoice = $pdo->prepare(
            'INSERT INTO invoices (job_id, total_amount, issued_date, payment_status)
             VALUES (:job_id, :total_amount, :issued_date, :payment_status)'
        );
        $invoice->execute([
            ':job_id' => $jobId,
            ':total_amount' => $amount,
            ':issued_date' => date('Y-m-d'),
            ':payment_status' => 'Unpaid',
        ]);
    }
}