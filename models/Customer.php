<?php
// Customer data access and operations.

declare(strict_types=1);

require_once __DIR__ . '/../config/Database.php';

final class Customer
{
    private PDO $pdo;
    private ?Database $dbWrapper = null;

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

    // Fetch customers with related equipment and job counts, grouped by customer ID.
    public function getAll(): array
    {
        try {
            $sql = 'SELECT
                        c.customer_id,
                        c.full_name,
                        c.phone,
                        c.email,
                        c.billing_address,
                        COUNT(DISTINCT e.equipment_id) AS equipment_count,
                        COUNT(DISTINCT j.job_id) AS job_count
                    FROM customers c
                    LEFT JOIN equipment e ON e.customer_id = c.customer_id
                    LEFT JOIN jobs j ON j.customer_id = c.customer_id
                    GROUP BY c.customer_id, c.full_name, c.phone, c.email, c.billing_address
                    ORDER BY c.customer_id DESC';

            $stmt = $this->pdo->query($sql);
            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (PDOException $e) {
            error_log('[Customer::getAll Error] ' . $e->getMessage());
            return [];
        }
    }

    // Minimal customer roster for high-speed dropdown selector binding
    public function getCustomers(): array
    {
        try {
            $stmt = $this->pdo->query(
                'SELECT customer_id, full_name, phone FROM customers ORDER BY full_name ASC'
            );
            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (PDOException $e) {
            error_log('[Customer::getCustomers Error] ' . $e->getMessage());
            return [];
        }
    }

    // Fetch isolated customer record
    public function getById(int $customerId): ?array
    {
        if ($customerId <= 0) {
            return null;
        }

        try {
            $stmt = $this->pdo->prepare(
                'SELECT customer_id, full_name, phone, email, billing_address
                 FROM customers
                 WHERE customer_id = :customer_id
                 LIMIT 1'
            );
            $stmt->bindValue(':customer_id', $customerId, PDO::PARAM_INT);
            $stmt->execute();
            $customer = $stmt->fetch(PDO::FETCH_ASSOC);
            return $customer ?: null;
        } catch (PDOException $e) {
            error_log('[Customer::getById Error] ' . $e->getMessage());
            return null;
        }
    }

    // Create customer record inside an atomic transaction
    public function addCustomer(string $fullName, string $phone = '', string $email = '', string $billingAddress = ''): bool
    {
        $fullName = trim($fullName);
        $phone = trim($phone);
        $email = trim($email);
        $billingAddress = trim($billingAddress);

        if ($fullName === '' || $phone === '') {
            return false;
        }

        $execute = function (PDO $pdo) use ($fullName, $phone, $email, $billingAddress): bool {
            $stmt = $pdo->prepare(
                'INSERT INTO customers (full_name, phone, email, billing_address)
                 VALUES (:full_name, :phone, :email, :billing_address)'
            );
            return $stmt->execute([
                ':full_name' => $fullName,
                ':phone' => $phone,
                ':email' => $email !== '' ? $email : null,
                ':billing_address' => $billingAddress !== '' ? $billingAddress : null,
            ]);
        };

        try {
            if ($this->dbWrapper) {
                return (bool) $this->dbWrapper->transaction(fn(PDO $pdo) => $execute($pdo));
            }
            return $execute($this->pdo);
        } catch (Throwable $e) {
            error_log('[Customer::addCustomer Error] ' . $e->getMessage());
            return false;
        }
    }

    // Update customer record inside an atomic transaction
    public function update(int $id, string $name, string $phone, string $email, string $address): bool
    {
        if ($id <= 0) {
            return false;
        }

        $name = trim($name);
        $phone = trim($phone);
        $email = trim($email);
        $address = trim($address);

        if ($name === '' || $phone === '') {
            return false;
        }

        $execute = function (PDO $pdo) use ($id, $name, $phone, $email, $address): bool {
            $stmt = $pdo->prepare(
                'UPDATE customers
                 SET full_name = :full_name,
                     phone = :phone,
                     email = :email,
                     billing_address = :billing_address
                 WHERE customer_id = :customer_id'
            );
            return $stmt->execute([
                ':customer_id' => $id,
                ':full_name' => $name,
                ':phone' => $phone,
                ':email' => $email !== '' ? $email : null,
                ':billing_address' => $address !== '' ? $address : null,
            ]);
        };

        try {
            if ($this->dbWrapper) {
                return (bool) $this->dbWrapper->transaction(fn(PDO $pdo) => $execute($pdo));
            }
            return $execute($this->pdo);
        } catch (Throwable $e) {
            error_log('[Customer::update Error] ' . $e->getMessage());
            return false;
        }
    }

    // Backwards-compatible signature
    public function updateCustomer(int $customer_id, string $full_name): bool
    {
        $existing = $this->getById($customer_id);
        if (!$existing) {
            return false;
        }
        return $this->update(
            $customer_id,
            $full_name,
            $existing['phone'] ?? '',
            $existing['email'] ?? '',
            $existing['billing_address'] ?? ''
        );
    }

    // Delete customer record (cascading through foreign keys) inside an ACID sequence
    public function deleteCustomer(int $customerId): bool
    {
        if ($customerId <= 0) {
            return false;
        }

        $execute = function (PDO $pdo) use ($customerId): bool {
            $stmt = $pdo->prepare('DELETE FROM customers WHERE customer_id = :customer_id');
            return $stmt->execute([':customer_id' => $customerId]);
        };

        try {
            if ($this->dbWrapper) {
                return (bool) $this->dbWrapper->transaction(fn(PDO $pdo) => $execute($pdo));
            }
            return $execute($this->pdo);
        } catch (Throwable $e) {
            error_log('[Customer::deleteCustomer Error] ' . $e->getMessage());
            return false;
        }
    }
}
