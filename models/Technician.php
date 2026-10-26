<?php
// Technician data access and operations.

declare(strict_types=1);

require_once __DIR__ . '/../config/Database.php';

final class Technician
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

    // Fetch technicians with certifications and dispatch counts.
    public function getAll(): array
    {
        try {
            $sql = 'SELECT
                        t.technician_id,
                        t.full_name,
                        t.license_number,
                        t.phone,
                        t.hire_date,
                        GROUP_CONCAT(CONCAT(s.skill_name, \' (certified \', ts.certified_date, \')\') ORDER BY s.skill_name SEPARATOR ", ") AS skills,
                        COUNT(DISTINCT jt.job_id) AS assigned_jobs
                    FROM technicians t
                    LEFT JOIN technician_skills ts ON ts.technician_id = t.technician_id
                    LEFT JOIN skills s ON s.skill_id = ts.skill_id
                    LEFT JOIN job_technicians jt ON jt.technician_id = t.technician_id
                    GROUP BY t.technician_id, t.full_name, t.license_number, t.phone, t.hire_date
                    ORDER BY t.technician_id DESC';

            $stmt = $this->pdo->query($sql);
            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (PDOException $e) {
            error_log('[Technician::getAll Error] ' . $e->getMessage());
            return [];
        }
    }

    // Roster of active technicians for quick modal assignment dropdowns
    public function getTechnicians(): array
    {
        try {
            $stmt = $this->pdo->query(
                'SELECT technician_id, full_name, license_number, phone FROM technicians ORDER BY full_name ASC'
            );
            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (PDOException $e) {
            error_log('[Technician::getTechnicians Error] ' . $e->getMessage());
            return [];
        }
    }

    // Fetch single technician by ID
    public function getById(int $technicianId): ?array
    {
        if ($technicianId <= 0) {
            return null;
        }

        try {
            $stmt = $this->pdo->prepare('SELECT * FROM technicians WHERE technician_id = :id LIMIT 1');
            $stmt->bindValue(':id', $technicianId, PDO::PARAM_INT);
            $stmt->execute();
            $res = $stmt->fetch(PDO::FETCH_ASSOC);
            return $res ?: null;
        } catch (PDOException $e) {
            error_log('[Technician::getById Error] ' . $e->getMessage());
            return null;
        }
    }

    // Add technician inside an ACID transaction
    public function create(string $name, string $license, string $phone, string $hireDate): bool
    {
        $name = trim($name);
        $license = trim($license);
        $phone = trim($phone);
        $hireDate = trim($hireDate) ?: date('Y-m-d');

        if ($name === '' || $license === '' || $phone === '') {
            return false;
        }

        $execute = function (PDO $pdo) use ($name, $license, $phone, $hireDate): bool {
            $stmt = $pdo->prepare(
                'INSERT INTO technicians (full_name, license_number, phone, hire_date)
                 VALUES (:full_name, :license_number, :phone, :hire_date)'
            );
            return $stmt->execute([
                ':full_name' => $name,
                ':license_number' => $license,
                ':phone' => $phone,
                ':hire_date' => $hireDate,
            ]);
        };

        try {
            if ($this->dbWrapper) {
                return (bool) $this->dbWrapper->transaction(fn(PDO $pdo) => $execute($pdo));
            }
            return $execute($this->pdo);
        } catch (Throwable $e) {
            error_log('[Technician::create Error] ' . $e->getMessage());
            return false;
        }
    }

    // Update technician details inside an ACID transaction
    public function update(int $id, string $name, string $license, string $phone, string $hireDate): bool
    {
        if ($id <= 0) {
            return false;
        }

        $name = trim($name);
        $license = trim($license);
        $phone = trim($phone);
        $hireDate = trim($hireDate) ?: date('Y-m-d');

        if ($name === '' || $license === '' || $phone === '') {
            return false;
        }

        $execute = function (PDO $pdo) use ($id, $name, $license, $phone, $hireDate): bool {
            $stmt = $pdo->prepare(
                'UPDATE technicians
                 SET full_name = :full_name,
                     license_number = :license_number,
                     phone = :phone,
                     hire_date = :hire_date
                 WHERE technician_id = :technician_id'
            );
            return $stmt->execute([
                ':technician_id' => $id,
                ':full_name' => $name,
                ':license_number' => $license,
                ':phone' => $phone,
                ':hire_date' => $hireDate,
            ]);
        };

        try {
            if ($this->dbWrapper) {
                return (bool) $this->dbWrapper->transaction(fn(PDO $pdo) => $execute($pdo));
            }
            return $execute($this->pdo);
        } catch (Throwable $e) {
            error_log('[Technician::update Error] ' . $e->getMessage());
            return false;
        }
    }

    // Delete technician with automatic cascade cleanup
    public function delete(int $id): bool
    {
        if ($id <= 0) {
            return false;
        }

        $execute = function (PDO $pdo) use ($id): bool {
            $stmt = $pdo->prepare('DELETE FROM technicians WHERE technician_id = :technician_id');
            return $stmt->execute([':technician_id' => $id]);
        };

        try {
            if ($this->dbWrapper) {
                return (bool) $this->dbWrapper->transaction(fn(PDO $pdo) => $execute($pdo));
            }
            return $execute($this->pdo);
        } catch (Throwable $e) {
            error_log('[Technician::delete Error] ' . $e->getMessage());
            return false;
        }
    }

    // Retrieve global skill directory
    public function getSkills(): array
    {
        try {
            $stmt = $this->pdo->query('SELECT skill_id, skill_name FROM skills ORDER BY skill_name ASC');
            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (PDOException $e) {
            error_log('[Technician::getSkills Error] ' . $e->getMessage());
            return [];
        }
    }

    // Register a new skill in the catalog
    public function createSkill(string $skillName): bool
    {
        $skillName = trim($skillName);
        if ($skillName === '') {
            return false;
        }

        try {
            $stmt = $this->pdo->prepare('INSERT INTO skills (skill_name) VALUES (:skill_name) ON DUPLICATE KEY UPDATE skill_name = skill_name');
            return $stmt->execute([':skill_name' => $skillName]);
        } catch (PDOException $e) {
            error_log('[Technician::createSkill Error] ' . $e->getMessage());
            return false;
        }
    }

    // Assign skill certification to technician
    public function assignSkill(int $technicianId, int $skillId, string $certifiedDate): bool
    {
        if ($technicianId <= 0 || $skillId <= 0) {
            return false;
        }

        $certifiedDate = trim($certifiedDate) ?: date('Y-m-d');

        $execute = function (PDO $pdo) use ($technicianId, $skillId, $certifiedDate): bool {
            $stmt = $pdo->prepare(
                'INSERT INTO technician_skills (technician_id, skill_id, certified_date)
                 VALUES (:technician_id, :skill_id, :certified_date)
                 ON DUPLICATE KEY UPDATE certified_date = VALUES(certified_date)'
            );
            return $stmt->execute([
                ':technician_id' => $technicianId,
                ':skill_id' => $skillId,
                ':certified_date' => $certifiedDate,
            ]);
        };

        try {
            if ($this->dbWrapper) {
                return (bool) $this->dbWrapper->transaction(fn(PDO $pdo) => $execute($pdo));
            }
            return $execute($this->pdo);
        } catch (Throwable $e) {
            error_log('[Technician::assignSkill Error] ' . $e->getMessage());
            return false;
        }
    }

    // Remove skill certification from technician
    public function removeSkill(int $technicianId, int $skillId): bool
    {
        if ($technicianId <= 0 || $skillId <= 0) {
            return false;
        }

        try {
            $stmt = $this->pdo->prepare(
                'DELETE FROM technician_skills WHERE technician_id = :technician_id AND skill_id = :skill_id'
            );
            return $stmt->execute([
                ':technician_id' => $technicianId,
                ':skill_id' => $skillId,
            ]);
        } catch (PDOException $e) {
            error_log('[Technician::removeSkill Error] ' . $e->getMessage());
            return false;
        }
    }
}
