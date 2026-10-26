<?php
// Invoice data access and operations.

declare(strict_types=1);

require_once __DIR__ . '/../config/Database.php';

final class Invoice
{
    private PDO $pdo;
    private ?Database $dbWrapper = null;
    public const ALLOWED_STATUSES = ['Unpaid', 'Paid', 'Partial', 'Cancelled'];

    public function __construct(PDO|Database|null $db = null)
    {
        if ($db instanceof Database) {
            $this->dbWrapper = $db;
            $this->pdo = $db->getConnection();
        } elseif ($db instanceof PDO) {
            $this->pdo = $db;
        } else {
            $this->dbWrapper = new Database();
            $this->pdo = $this->dbWrapper->getConnection();
        }
    }

    // Retrieve all invoices joined with job, customer, and equipment telemetry
    public function getAll(): array
    {
        try {
            $sql = 'SELECT
                        i.invoice_id,
                        i.job_id,
                        i.total_amount,
                        i.issued_date,
                        i.payment_status,
                        c.full_name AS customer_name,
                        c.phone AS customer_phone,
                        e.equipment_type,
                        j.scheduled_datetime,
                        j.status AS job_status
                    FROM invoices i
                    INNER JOIN jobs j ON j.job_id = i.job_id
                    INNER JOIN customers c ON c.customer_id = j.customer_id
                    INNER JOIN equipment e ON e.equipment_id = j.equipment_id
                    ORDER BY i.issued_date DESC, i.invoice_id DESC';

            $stmt = $this->pdo->query($sql);
            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (PDOException $e) {
            error_log('[Invoice::getAll Error] ' . $e->getMessage());
            return [];
        }
    }

    // Retrieve specific invoice by primary key
    public function getById(int $invoiceId): ?array
    {
        if ($invoiceId <= 0) {
            return null;
        }

        try {
            $stmt = $this->pdo->prepare('SELECT * FROM invoices WHERE invoice_id = :id LIMIT 1');
            $stmt->bindValue(':id', $invoiceId, PDO::PARAM_INT);
            $stmt->execute();
            $inv = $stmt->fetch(PDO::FETCH_ASSOC);
            return $inv ?: null;
        } catch (PDOException $e) {
            error_log('[Invoice::getById Error] ' . $e->getMessage());
            return null;
        }
    }

    // Retrieve invoice attached to a specific job
    public function getByJobId(int $jobId): ?array
    {
        if ($jobId <= 0) {
            return null;
        }

        try {
            $stmt = $this->pdo->prepare('SELECT * FROM invoices WHERE job_id = :job_id LIMIT 1');
            $stmt->bindValue(':job_id', $jobId, PDO::PARAM_INT);
            $stmt->execute();
            $inv = $stmt->fetch(PDO::FETCH_ASSOC);
            return $inv ?: null;
        } catch (PDOException $e) {
            error_log('[Invoice::getByJobId Error] ' . $e->getMessage());
            return null;
        }
    }

    // Generate or update invoice record for a work order
    public function generate(int $jobId, float $total, string $issuedDate, string $status = 'Unpaid'): bool
    {
        if ($jobId <= 0) {
            return false;
        }

        $total = max(0.0, $total);
        $issuedDate = trim($issuedDate) ?: date('Y-m-d');
        $status = in_array($status, self::ALLOWED_STATUSES, true) ? $status : 'Unpaid';

        $execute = function (PDO $pdo) use ($jobId, $total, $issuedDate, $status): bool {
            // Verify parent job exists
            $check = $pdo->prepare('SELECT job_id FROM jobs WHERE job_id = :job_id');
            $check->execute([':job_id' => $jobId]);
            if (!$check->fetch()) {
                throw new InvalidArgumentException("Job ticket #{$jobId} does not exist.");
            }

            $stmt = $pdo->prepare(
                'INSERT INTO invoices (job_id, total_amount, issued_date, payment_status)
                 VALUES (:job_id, :total_amount, :issued_date, :payment_status)
                 ON DUPLICATE KEY UPDATE
                    total_amount = VALUES(total_amount),
                    issued_date = VALUES(issued_date),
                    payment_status = VALUES(payment_status)'
            );
            return $stmt->execute([
                ':job_id' => $jobId,
                ':total_amount' => $total,
                ':issued_date' => $issuedDate,
                ':payment_status' => $status,
            ]);
        };

        try {
            if ($this->dbWrapper) {
                return (bool) $this->dbWrapper->transaction(fn(PDO $pdo) => $execute($pdo));
            }
            return $execute($this->pdo);
        } catch (Throwable $e) {
            error_log('[Invoice::generate Error] ' . $e->getMessage());
            return false;
        }
    }

    // Update existing invoice
    public function update(int $id, float $total, string $issuedDate, string $status): bool
    {
        if ($id <= 0) {
            return false;
        }

        $total = max(0.0, $total);
        $issuedDate = trim($issuedDate) ?: date('Y-m-d');
        $status = in_array($status, self::ALLOWED_STATUSES, true) ? $status : 'Unpaid';

        $execute = function (PDO $pdo) use ($id, $total, $issuedDate, $status): bool {
            $stmt = $pdo->prepare(
                'UPDATE invoices
                 SET total_amount = :total_amount,
                     issued_date = :issued_date,
                     payment_status = :payment_status
                 WHERE invoice_id = :invoice_id'
            );
            return $stmt->execute([
                ':invoice_id' => $id,
                ':total_amount' => $total,
                ':issued_date' => $issuedDate,
                ':payment_status' => $status,
            ]);
        };

        try {
            if ($this->dbWrapper) {
                return (bool) $this->dbWrapper->transaction(fn(PDO $pdo) => $execute($pdo));
            }
            return $execute($this->pdo);
        } catch (Throwable $e) {
            error_log('[Invoice::update Error] ' . $e->getMessage());
            return false;
        }
    }

    // Fast inline payment status toggle
    public function updateStatus(int $invoiceId, string $status): bool
    {
        if ($invoiceId <= 0) {
            return false;
        }

        $status = in_array($status, self::ALLOWED_STATUSES, true) ? $status : 'Unpaid';

        $execute = function (PDO $pdo) use ($invoiceId, $status): bool {
            $stmt = $pdo->prepare('UPDATE invoices SET payment_status = :status WHERE invoice_id = :id');
            return $stmt->execute([':status' => $status, ':id' => $invoiceId]);
        };

        try {
            if ($this->dbWrapper) {
                return (bool) $this->dbWrapper->transaction(fn(PDO $pdo) => $execute($pdo));
            }
            return $execute($this->pdo);
        } catch (Throwable $e) {
            error_log('[Invoice::updateStatus Error] ' . $e->getMessage());
            return false;
        }
    }

    // Delete invoice entry
    public function delete(int $invoiceId): bool
    {
        if ($invoiceId <= 0) {
            return false;
        }

        $execute = function (PDO $pdo) use ($invoiceId): bool {
            $stmt = $pdo->prepare('DELETE FROM invoices WHERE invoice_id = :id');
            return $stmt->execute([':id' => $invoiceId]);
        };

        try {
            if ($this->dbWrapper) {
                return (bool) $this->dbWrapper->transaction(fn(PDO $pdo) => $execute($pdo));
            }
            return $execute($this->pdo);
        } catch (Throwable $e) {
            error_log('[Invoice::delete Error] ' . $e->getMessage());
            return false;
        }
    }
}
