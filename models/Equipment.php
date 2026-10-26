<?php
// Equipment data access and operations.

declare(strict_types=1);

require_once __DIR__ . '/../config/Database.php';

final class Equipment
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

    // Fetch equipment with customer details, grouped by equipment ID.
    public function getAll(): array
    {
        try {
            $sql = 'SELECT
                        e.equipment_id,
                        e.customer_id,
                        e.equipment_type,
                        e.model_number,
                        e.serial_number,
                        e.install_date,
                        c.full_name AS customer_name,
                        c.phone AS customer_phone
                    FROM equipment e
                    INNER JOIN customers c ON c.customer_id = e.customer_id
                    ORDER BY e.equipment_id DESC';

            $stmt = $this->pdo->query($sql);
            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (PDOException $e) {
            error_log('[Equipment::getAll Error] ' . $e->getMessage());
            return [];
        }
    }

    // Retrieve equipment belonging to a specific customer for linked modal selectors
    public function getByCustomer(int $customerId): array
    {
        if ($customerId <= 0) {
            return [];
        }

        try {
            $stmt = $this->pdo->prepare(
                'SELECT equipment_id, customer_id, equipment_type, model_number, serial_number, install_date
                 FROM equipment
                 WHERE customer_id = :customer_id
                 ORDER BY equipment_type ASC'
            );
            $stmt->bindValue(':customer_id', $customerId, PDO::PARAM_INT);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (PDOException $e) {
            error_log('[Equipment::getByCustomer Error] ' . $e->getMessage());
            return [];
        }
    }

    // Fetch single equipment unit by ID
    public function getById(int $equipmentId): ?array
    {
        if ($equipmentId <= 0) {
            return null;
        }

        try {
            $stmt = $this->pdo->prepare(
                'SELECT e.*, c.full_name AS customer_name
                 FROM equipment e
                 INNER JOIN customers c ON c.customer_id = e.customer_id
                 WHERE e.equipment_id = :equipment_id
                 LIMIT 1'
            );
            $stmt->bindValue(':equipment_id', $equipmentId, PDO::PARAM_INT);
            $stmt->execute();
            $unit = $stmt->fetch(PDO::FETCH_ASSOC);
            return $unit ?: null;
        } catch (PDOException $e) {
            error_log('[Equipment::getById Error] ' . $e->getMessage());
            return null;
        }
    }

    // Create equipment entry within an ACID transaction
    public function create(int $customerId, string $type, string $model = '', string $serial = '', string $installDate = ''): bool
    {
        if ($customerId <= 0 || trim($type) === '') {
            return false;
        }

        $type = trim($type);
        $model = trim($model);
        $serial = trim($serial);
        $installDate = trim($installDate) ?: null;

        $execute = function (PDO $pdo) use ($customerId, $type, $model, $serial, $installDate): bool {
            // Check customer existence
            $check = $pdo->prepare('SELECT customer_id FROM customers WHERE customer_id = :id');
            $check->execute([':id' => $customerId]);
            if (!$check->fetch()) {
                throw new InvalidArgumentException("Customer ID {$customerId} does not exist.");
            }

            $stmt = $pdo->prepare(
                'INSERT INTO equipment (customer_id, equipment_type, model_number, serial_number, install_date)
                 VALUES (:customer_id, :equipment_type, :model_number, :serial_number, :install_date)'
            );
            return $stmt->execute([
                ':customer_id' => $customerId,
                ':equipment_type' => $type,
                ':model_number' => $model !== '' ? $model : null,
                ':serial_number' => $serial !== '' ? $serial : null,
                ':install_date' => $installDate,
            ]);
        };

        try {
            if ($this->dbWrapper) {
                return (bool) $this->dbWrapper->transaction(fn(PDO $pdo) => $execute($pdo));
            }
            return $execute($this->pdo);
        } catch (Throwable $e) {
            error_log('[Equipment::create Error] ' . $e->getMessage());
            return false;
        }
    }

    // Update equipment record within an ACID transaction
    public function update(int $id, int $customerId, string $type, string $model = '', string $serial = '', string $installDate = ''): bool
    {
        if ($id <= 0 || $customerId <= 0 || trim($type) === '') {
            return false;
        }

        $type = trim($type);
        $model = trim($model);
        $serial = trim($serial);
        $installDate = trim($installDate) ?: null;

        $execute = function (PDO $pdo) use ($id, $customerId, $type, $model, $serial, $installDate): bool {
            $stmt = $pdo->prepare(
                'UPDATE equipment
                 SET customer_id = :customer_id,
                     equipment_type = :equipment_type,
                     model_number = :model_number,
                     serial_number = :serial_number,
                     install_date = :install_date
                 WHERE equipment_id = :equipment_id'
            );
            return $stmt->execute([
                ':equipment_id' => $id,
                ':customer_id' => $customerId,
                ':equipment_type' => $type,
                ':model_number' => $model !== '' ? $model : null,
                ':serial_number' => $serial !== '' ? $serial : null,
                ':install_date' => $installDate,
            ]);
        };

        try {
            if ($this->dbWrapper) {
                return (bool) $this->dbWrapper->transaction(fn(PDO $pdo) => $execute($pdo));
            }
            return $execute($this->pdo);
        } catch (Throwable $e) {
            error_log('[Equipment::update Error] ' . $e->getMessage());
            return false;
        }
    }

    // Delete equipment unit within an ACID transaction
    public function delete(int $id): bool
    {
        if ($id <= 0) {
            return false;
        }

        $execute = function (PDO $pdo) use ($id): bool {
            $stmt = $pdo->prepare('DELETE FROM equipment WHERE equipment_id = :equipment_id');
            return $stmt->execute([':equipment_id' => $id]);
        };

        try {
            if ($this->dbWrapper) {
                return (bool) $this->dbWrapper->transaction(fn(PDO $pdo) => $execute($pdo));
            }
            return $execute($this->pdo);
        } catch (Throwable $e) {
            error_log('[Equipment::delete Error] ' . $e->getMessage());
            return false;
        }
    }
}
