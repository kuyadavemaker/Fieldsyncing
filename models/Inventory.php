<?php
// Inventory data access and operations.

declare(strict_types=1);

require_once __DIR__ . '/../config/Database.php';

final class Inventory
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

    // Fetch inventory with stock status while preserving distinct parts.
    public function getAll(): array
    {
        try {
            $sql = "SELECT
                        part_id,
                        part_name,
                        sku,
                        quantity_in_stock,
                        unit_cost,
                        CASE
                            WHEN quantity_in_stock <= 0 THEN 'Out of stock'
                            WHEN quantity_in_stock <= 5 THEN 'Low stock'
                            ELSE 'In stock'
                        END AS stock_status
                    FROM parts_inventory
                    ORDER BY quantity_in_stock ASC, part_name ASC";

            $stmt = $this->pdo->query($sql);
            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (PDOException $e) {
            error_log('[Inventory::getAll Error] ' . $e->getMessage());
            return [];
        }
    }

    // Fetch single part record
    public function getById(int $partId): ?array
    {
        if ($partId <= 0) {
            return null;
        }

        try {
            $stmt = $this->pdo->prepare('SELECT * FROM parts_inventory WHERE part_id = :part_id LIMIT 1');
            $stmt->bindValue(':part_id', $partId, PDO::PARAM_INT);
            $stmt->execute();
            $part = $stmt->fetch(PDO::FETCH_ASSOC);
            return $part ?: null;
        } catch (PDOException $e) {
            error_log('[Inventory::getById Error] ' . $e->getMessage());
            return null;
        }
    }

    // Register or restock inventory item with duplicate SKU handling
    public function create(string $name, string $sku, int $quantity, float $cost): bool
    {
        $name = trim($name);
        $sku = strtoupper(trim($sku));
        $quantity = max(0, $quantity);
        $cost = max(0.0, $cost);

        if ($name === '' || $sku === '') {
            return false;
        }

        $execute = function (PDO $pdo) use ($name, $sku, $quantity, $cost): bool {
            $stmt = $pdo->prepare(
                'INSERT INTO parts_inventory (part_name, sku, quantity_in_stock, unit_cost)
                 VALUES (:part_name, :sku, :quantity, :cost)
                 ON DUPLICATE KEY UPDATE
                    quantity_in_stock = quantity_in_stock + VALUES(quantity_in_stock),
                    unit_cost = VALUES(unit_cost)'
            );
            return $stmt->execute([
                ':part_name' => $name,
                ':sku' => $sku,
                ':quantity' => $quantity,
                ':cost' => $cost,
            ]);
        };

        try {
            if ($this->dbWrapper) {
                return (bool) $this->dbWrapper->transaction(fn(PDO $pdo) => $execute($pdo));
            }
            return $execute($this->pdo);
        } catch (Throwable $e) {
            error_log('[Inventory::create Error] ' . $e->getMessage());
            return false;
        }
    }

    // Update existing inventory item
    public function update(int $id, string $name, string $sku, int $quantity, float $cost): bool
    {
        if ($id <= 0) {
            return false;
        }

        $name = trim($name);
        $sku = strtoupper(trim($sku));
        $quantity = max(0, $quantity);
        $cost = max(0.0, $cost);

        if ($name === '' || $sku === '') {
            return false;
        }

        $execute = function (PDO $pdo) use ($id, $name, $sku, $quantity, $cost): bool {
            $stmt = $pdo->prepare(
                'UPDATE parts_inventory
                 SET part_name = :part_name,
                     sku = :sku,
                     quantity_in_stock = :quantity,
                     unit_cost = :cost
                 WHERE part_id = :part_id'
            );
            return $stmt->execute([
                ':part_id' => $id,
                ':part_name' => $name,
                ':sku' => $sku,
                ':quantity' => $quantity,
                ':cost' => $cost,
            ]);
        };

        try {
            if ($this->dbWrapper) {
                return (bool) $this->dbWrapper->transaction(fn(PDO $pdo) => $execute($pdo));
            }
            return $execute($this->pdo);
        } catch (Throwable $e) {
            error_log('[Inventory::update Error] ' . $e->getMessage());
            return false;
        }
    }

    // Adjust inventory stock level directly
    public function updateQuantity(int $partId, int $quantity): bool
    {
        if ($partId <= 0) {
            return false;
        }

        $quantity = max(0, $quantity);

        $execute = function (PDO $pdo) use ($partId, $quantity): bool {
            $stmt = $pdo->prepare('UPDATE parts_inventory SET quantity_in_stock = :quantity WHERE part_id = :part_id');
            return $stmt->execute([':part_id' => $partId, ':quantity' => $quantity]);
        };

        try {
            if ($this->dbWrapper) {
                return (bool) $this->dbWrapper->transaction(fn(PDO $pdo) => $execute($pdo));
            }
            return $execute($this->pdo);
        } catch (Throwable $e) {
            error_log('[Inventory::updateQuantity Error] ' . $e->getMessage());
            return false;
        }
    }

    // Delete inventory item
    public function delete(int $id): bool
    {
        if ($id <= 0) {
            return false;
        }

        $execute = function (PDO $pdo) use ($id): bool {
            $stmt = $pdo->prepare('DELETE FROM parts_inventory WHERE part_id = :part_id');
            return $stmt->execute([':part_id' => $id]);
        };

        try {
            if ($this->dbWrapper) {
                return (bool) $this->dbWrapper->transaction(fn(PDO $pdo) => $execute($pdo));
            }
            return $execute($this->pdo);
        } catch (Throwable $e) {
            error_log('[Inventory::delete Error] ' . $e->getMessage());
            return false;
        }
    }
}
