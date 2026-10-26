<?php
// Database connection and transaction helpers.

declare(strict_types=1);

final class Database
{
    private string $host = '127.0.0.1';
    private string $db_name = 'fieldsync_db';
    private string $username = 'root';
    private string $password = '';
    private int $port = 3306;
    private ?PDO $conn = null;
    private int $transactionDepth = 0;

    public function __construct()
    {
    }

    public function getAuthConnection(): PDO
    {
        return $this->connect($this->db_name);
    }

    public function getServerConnection(): PDO
    {
        return $this->connect(null);
    }

    public function connectTenantDatabase(string $databaseName): PDO
    {
        if (!preg_match('/\Afieldsync_company_[a-f0-9]{16}\z/', $databaseName)) {
            throw new InvalidArgumentException('Invalid operational database name.');
        }
        return $this->connect($databaseName);
    }

    // Establish or return active PDO connection with strict security and engine settings
    public function getConnection(): PDO
    {
        if ($this->conn instanceof PDO) {
            return $this->conn;
        }

        $tenantDatabase = (string)($_SESSION['operational_database'] ?? $this->db_name);
        if (!preg_match('/\A(?:fieldsync_db|fieldsync_company_[a-f0-9]{16})\z/', $tenantDatabase)) {
            throw new RuntimeException('The authenticated company database context is invalid.');
        }
        $this->conn = $this->connect($tenantDatabase);
        return $this->conn;
    }

    private function connect(?string $databaseName): PDO
    {
        $dsn = sprintf(
            'mysql:host=%s;port=%d;%scharset=utf8mb4',
            $this->host,
            $this->port,
            $databaseName !== null ? 'dbname=' . $databaseName . ';' : ''
        );
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::ATTR_PERSISTENT => false,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
        ];

        try {
            return new PDO($dsn, $this->username, $this->password, $options);
        } catch (PDOException $e) {
            error_log('[FATAL DB CONNECTION ERROR] ' . $e->getMessage());
            throw new RuntimeException(
                'Database infrastructure unavailable. Verify MySQL is running and configured.',
                (int)$e->getCode(),
                $e
            );
        }
    }

    // Check if a transaction is currently active
    public function inTransaction(): bool
    {
        return $this->getConnection()->inTransaction();
    }

    // Begin transaction or push savepoint if already inside transaction
    public function beginTransaction(): bool
    {
        $pdo = $this->getConnection();
        if ($this->transactionDepth === 0 || !$pdo->inTransaction()) {
            $this->transactionDepth = 1;
            return $pdo->beginTransaction();
        }

        $this->transactionDepth++;
        $savepoint = 'sp_depth_' . $this->transactionDepth;
        $pdo->exec("SAVEPOINT `{$savepoint}`");
        return true;
    }

    // Commit current level or release savepoint
    public function commit(): bool
    {
        $pdo = $this->getConnection();
        if ($this->transactionDepth <= 1) {
            $this->transactionDepth = 0;
            return $pdo->inTransaction() ? $pdo->commit() : true;
        }

        $savepoint = 'sp_depth_' . $this->transactionDepth;
        $pdo->exec("RELEASE SAVEPOINT `{$savepoint}`");
        $this->transactionDepth--;
        return true;
    }

    // Rollback current level or rollback to savepoint
    public function rollBack(): bool
    {
        $pdo = $this->getConnection();
        if ($this->transactionDepth <= 1) {
            $this->transactionDepth = 0;
            return $pdo->inTransaction() ? $pdo->rollBack() : true;
        }

        $savepoint = 'sp_depth_' . $this->transactionDepth;
        $pdo->exec("ROLLBACK TO SAVEPOINT `{$savepoint}`");
        $this->transactionDepth--;
        return true;
    }

    // Create an explicit named savepoint
    public function createSavepoint(string $name): void
    {
        $safeName = preg_replace('/[^a-zA-Z0-9_]/', '_', $name);
        $this->getConnection()->exec("SAVEPOINT `{$safeName}`");
    }

    // Rollback to an explicit named savepoint
    public function rollbackSavepoint(string $name): void
    {
        $safeName = preg_replace('/[^a-zA-Z0-9_]/', '_', $name);
        $this->getConnection()->exec("ROLLBACK TO SAVEPOINT `{$safeName}`");
    }

    // Release an explicit named savepoint
    public function releaseSavepoint(string $name): void
    {
        $safeName = preg_replace('/[^a-zA-Z0-9_]/', '_', $name);
        $this->getConnection()->exec("RELEASE SAVEPOINT `{$safeName}`");
    }

    // Run a transaction callback with rollback and nested savepoint support.
    public function transaction(callable $callback): mixed
    {
        $pdo = $this->getConnection();
        $isRoot = !$pdo->inTransaction();
        $spName = 'sp_' . bin2hex(random_bytes(6));

        if ($isRoot) {
            $pdo->beginTransaction();
        } else {
            $pdo->exec("SAVEPOINT `{$spName}`");
        }

        try {
            $result = $callback($pdo, $this);
            if ($isRoot) {
                if ($pdo->inTransaction()) {
                    $pdo->commit();
                }
            } else {
                $pdo->exec("RELEASE SAVEPOINT `{$spName}`");
            }
            return $result;
        } catch (Throwable $e) {
            if ($isRoot) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
            } else {
                $pdo->exec("ROLLBACK TO SAVEPOINT `{$spName}`");
            }
            error_log('[TRANSACTION EXCEPTION ROLLBACK] ' . $e->getMessage());
            throw $e;
        }
    }

    // Execute callable inside a named savepoint sandbox without killing the outer transaction
    public function savepointSandbox(string $savepointName, callable $callback): mixed
    {
        $pdo = $this->getConnection();
        $safeName = 'sp_' . preg_replace('/[^a-zA-Z0-9_]/', '_', $savepointName) . '_' . bin2hex(random_bytes(3));
        $pdo->exec("SAVEPOINT `{$safeName}`");

        try {
            $result = $callback($pdo, $this);
            $pdo->exec("RELEASE SAVEPOINT `{$safeName}`");
            return $result;
        } catch (Throwable $e) {
            $pdo->exec("ROLLBACK TO SAVEPOINT `{$safeName}`");
            error_log("[SAVEPOINT SANDBOX ROLLBACK: {$safeName}] " . $e->getMessage());
            throw $e;
        }
    }
}