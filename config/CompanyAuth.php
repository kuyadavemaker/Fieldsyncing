<?php
declare(strict_types=1);

require_once __DIR__ . '/Database.php';

final class CompanyAuth
{
    private const OPERATIONAL_TABLES = [
        'customers', 'equipment', 'technicians', 'skills', 'technician_skills',
        'parts_inventory', 'jobs', 'job_technicians', 'job_parts_used',
        'inventory_usage_log', 'invoices',
    ];

    public function __construct(private Database $database)
    {
    }

    public function accountCount(): int
    {
        return (int)$this->database->getAuthConnection()
            ->query('SELECT COUNT(*) FROM users')
            ->fetchColumn();
    }

    public function register(
        string $fullName,
        string $email,
        string $password
    ): array {
        $fullName = trim($fullName);
        $email = strtolower(trim($email));
        $username = $email;
        if (strlen($fullName) < 2 || strlen($fullName) > 120) {
            throw new InvalidArgumentException('Enter your name (2–120 characters).');
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 254) {
            throw new InvalidArgumentException('Enter a valid email address.');
        }
        if (strlen($password) < 12) {
            throw new InvalidArgumentException('Choose a passphrase with at least 12 characters.');
        }

        $passwordHash = password_hash($password, PASSWORD_DEFAULT);
        $recoveryCode = strtoupper(bin2hex(random_bytes(32)));
        $recoveryHash = password_hash($recoveryCode, PASSWORD_DEFAULT);
        if ($passwordHash === false || $recoveryHash === false) {
            throw new RuntimeException('Account credentials could not be securely generated.');
        }

        $authPdo = $this->database->getAuthConnection();
        $operationalDatabase = 'fieldsync_company_' . bin2hex(random_bytes(8));
        $workspaceName = $fullName . "'s Workspace";
        $this->provisionDatabase($operationalDatabase);
        try {
            $authPdo->beginTransaction();
            $companyStmt = $authPdo->prepare(
                'INSERT INTO companies (company_name, operational_database)
                 VALUES (:company_name, :operational_database)'
            );
            $companyStmt->execute([
                ':company_name' => $workspaceName,
                ':operational_database' => $operationalDatabase,
            ]);
            $companyId = (int)$authPdo->lastInsertId();

            $userStmt = $authPdo->prepare(
                'INSERT INTO users (company_id, full_name, username, email, password_hash, recovery_hash, role)
                 VALUES (:company_id, :full_name, :username, :email, :password_hash, :recovery_hash, :role)'
            );
            $userStmt->execute([
                ':company_id' => $companyId,
                ':full_name' => $fullName,
                ':username' => $username,
                ':email' => $email,
                ':password_hash' => $passwordHash,
                ':recovery_hash' => $recoveryHash,
                ':role' => 'Head Dispatcher',
            ]);
            $userId = (int)$authPdo->lastInsertId();
            $authPdo->commit();
        } catch (PDOException $e) {
            if ($authPdo->inTransaction()) {
                $authPdo->rollBack();
            }
            $this->dropDatabase($operationalDatabase);
            if ((string)$e->getCode() === '23000') {
                throw new InvalidArgumentException('That operational email is already registered.');
            }
            throw $e;
        } catch (Throwable $e) {
            if ($authPdo->inTransaction()) {
                $authPdo->rollBack();
            }
            $this->dropDatabase($operationalDatabase);
            throw $e;
        }

        $stmt = $authPdo->prepare(
            'SELECT u.user_id, u.company_id, u.full_name, u.username, u.role,
                    c.company_name, c.operational_database
             FROM users u INNER JOIN companies c ON c.company_id = u.company_id
             WHERE u.user_id = :user_id LIMIT 1'
        );
        $stmt->execute([':user_id' => $userId]);
        $user = $stmt->fetch();
        if (!$user) {
            throw new RuntimeException('The new account could not be loaded.');
        }
        return ['user' => $user, 'recovery_code' => $recoveryCode];
    }

    public function authenticate(string $email, string $password): ?array
    {
        $email = strtolower(trim($email));
        $user = $this->findByEmail($email);
        if ($user === null || !password_verify($password, (string)$user['password_hash'])) {
            return null;
        }
        return $user;
    }

    public function findByEmail(string $email): ?array
    {
        $email = strtolower(trim($email));
        if ($email === '') {
            return null;
        }
        $stmt = $this->database->getAuthConnection()->prepare(
            'SELECT u.user_id, u.company_id, u.full_name, u.username, u.email, u.password_hash,
                    u.recovery_hash, u.role, c.company_name, c.operational_database
             FROM users u INNER JOIN companies c ON c.company_id = u.company_id
             WHERE u.email = :email LIMIT 1'
        );
        $stmt->execute([':email' => $email]);
        $user = $stmt->fetch();
        return $user ?: null;
    }

    public function resetPassword(string $email, string $recoveryCode, string $newPassword): string
    {
        if (strlen($newPassword) < 12) {
            throw new InvalidArgumentException('Choose a passphrase with at least 12 characters.');
        }
        $pdo = $this->database->getAuthConnection();
        $stmt = $pdo->prepare(
            'SELECT user_id, recovery_hash FROM users
             WHERE email = :email LIMIT 1'
        );
        $stmt->execute([
            ':email' => strtolower(trim($email)),
        ]);
        $user = $stmt->fetch();
        if (!$user || empty($user['recovery_hash']) || !password_verify(strtoupper(trim($recoveryCode)), $user['recovery_hash'])) {
            throw new RuntimeException('The verified operational email or recovery code is incorrect.');
        }

        $passwordHash = password_hash($newPassword, PASSWORD_DEFAULT);
        $newRecoveryCode = strtoupper(bin2hex(random_bytes(32)));
        $recoveryHash = password_hash($newRecoveryCode, PASSWORD_DEFAULT);
        if ($passwordHash === false || $recoveryHash === false) {
            throw new RuntimeException('New credentials could not be securely generated.');
        }
        $update = $pdo->prepare(
            'UPDATE users SET password_hash = :password_hash, recovery_hash = :recovery_hash
             WHERE user_id = :user_id'
        );
        $update->execute([
            ':password_hash' => $passwordHash,
            ':recovery_hash' => $recoveryHash,
            ':user_id' => (int)$user['user_id'],
        ]);
        return $newRecoveryCode;
    }

    private function provisionDatabase(string $databaseName): void
    {
        if (!preg_match('/\Afieldsync_company_[a-f0-9]{16}\z/', $databaseName)) {
            throw new RuntimeException('Invalid company database name.');
        }
        $serverPdo = $this->database->getServerConnection();
        $serverPdo->exec(
            'CREATE DATABASE `' . $databaseName . '` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci'
        );
        try {
            $source = $this->database->getAuthConnection();
            $target = $this->database->connectTenantDatabase($databaseName);
            foreach (self::OPERATIONAL_TABLES as $tableName) {
                $definition = $source->query('SHOW CREATE TABLE `' . $tableName . '`')->fetch();
                if (!$definition || empty($definition['Create Table'])) {
                    throw new RuntimeException(
                        "Required table {$tableName} is missing. Apply the FieldSync database schema first."
                    );
                }
                $target->exec($definition['Create Table']);
            }
            $skillNames = $source->query('SELECT skill_name FROM skills')->fetchAll(PDO::FETCH_COLUMN);
            if ($skillNames !== []) {
                $skillStmt = $target->prepare('INSERT INTO skills (skill_name) VALUES (:skill_name)');
                foreach ($skillNames as $skillName) {
                    $skillStmt->execute([':skill_name' => $skillName]);
                }
            }
        } catch (Throwable $e) {
            $this->dropDatabase($databaseName);
            throw new RuntimeException(
                'Could not prepare the private company workspace. Verify database permissions and schema.',
                0,
                $e
            );
        }
    }

    private function dropDatabase(string $databaseName): void
    {
        if (preg_match('/\Afieldsync_company_[a-f0-9]{16}\z/', $databaseName)) {
            $this->database->getServerConnection()->exec('DROP DATABASE IF EXISTS `' . $databaseName . '`');
        }
    }
}
