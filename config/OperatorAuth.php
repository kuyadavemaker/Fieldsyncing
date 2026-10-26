<?php
declare(strict_types=1);

function fsdimsOperatorEnvFile(): string
{
    $configuredPath = getenv('FSDIMS_ENV_FILE');
    $envFile = $configuredPath !== false && $configuredPath !== ''
        ? $configuredPath
        : dirname(__DIR__, 3) . DIRECTORY_SEPARATOR . 'fieldsync.env';
    $directory = realpath(dirname($envFile));

    if ($directory === false) {
        throw new RuntimeException('The private FSDIMS environment directory does not exist.');
    }

    $resolvedPath = $directory . DIRECTORY_SEPARATOR . basename($envFile);
    $documentRoot = realpath((string)($_SERVER['DOCUMENT_ROOT'] ?? ''));
    if ($documentRoot !== false) {
        $rootPrefix = rtrim($documentRoot, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;
        if (strncasecmp($resolvedPath, $rootPrefix, strlen($rootPrefix)) === 0) {
            throw new RuntimeException('The FSDIMS environment file must be outside the web root.');
        }
    }

    return $resolvedPath;
}

function fsdimsReadOperatorCredentials(): ?array
{
    $envFile = fsdimsOperatorEnvFile();
    $fileValues = [];
    if (is_file($envFile)) {
        $parsedValues = parse_ini_file($envFile, false, INI_SCANNER_RAW);
        if ($parsedValues === false) {
            throw new RuntimeException('The private FSDIMS environment file could not be parsed.');
        }
        $fileValues = $parsedValues;
    }

    $username = getenv('FSDIMS_OPERATOR_USERNAME');
    $passwordHash = getenv('FSDIMS_OPERATOR_PASSWORD_HASH');
    $recoveryHash = getenv('FSDIMS_OPERATOR_RECOVERY_HASH');
    $username = $username !== false && $username !== ''
        ? $username
        : (string)($fileValues['FSDIMS_OPERATOR_USERNAME'] ?? '');
    $passwordHash = $passwordHash !== false && $passwordHash !== ''
        ? $passwordHash
        : (string)($fileValues['FSDIMS_OPERATOR_PASSWORD_HASH'] ?? '');
    $recoveryHash = $recoveryHash !== false && $recoveryHash !== ''
        ? $recoveryHash
        : (string)($fileValues['FSDIMS_OPERATOR_RECOVERY_HASH'] ?? '');

    if ($username === '' && $passwordHash === '') {
        return null;
    }
    if (
        $username === ''
        || (!str_starts_with($passwordHash, '$2y$') && !str_starts_with($passwordHash, '$argon2'))
    ) {
        throw new RuntimeException('FSDIMS operator credentials are incomplete or invalid.');
    }

    return [
        'username' => $username,
        'password_hash' => $passwordHash,
        'recovery_hash' => $recoveryHash,
    ];
}

function fsdimsOperatorCredentials(): array
{
    $credentials = fsdimsReadOperatorCredentials();
    if ($credentials === null) {
        throw new RuntimeException('No FSDIMS operator account has been created.');
    }

    return $credentials;
}

function fsdimsCreateOperatorAccount(string $username, string $password): string
{
    $username = trim($username);
    if (!preg_match('/\A[a-zA-Z0-9._-]{3,64}\z/', $username)) {
        throw new InvalidArgumentException('Operator ID must be 3–64 characters using letters, numbers, dots, underscores, or hyphens.');
    }
    if (strlen($password) < 12) {
        throw new InvalidArgumentException('Choose a passphrase with at least 12 characters.');
    }
    if (getenv('FSDIMS_OPERATOR_USERNAME') !== false || getenv('FSDIMS_OPERATOR_PASSWORD_HASH') !== false) {
        throw new RuntimeException('Account creation is disabled while operator credentials are set in the server environment.');
    }
    if (fsdimsReadOperatorCredentials() !== null) {
        throw new RuntimeException('The operator account has already been created.');
    }

    $passwordHash = password_hash($password, PASSWORD_DEFAULT);
    if ($passwordHash === false) {
        throw new RuntimeException('The operator passphrase could not be securely hashed.');
    }
    $recoveryCode = strtoupper(bin2hex(random_bytes(32)));
    $recoveryHash = password_hash($recoveryCode, PASSWORD_DEFAULT);
    if ($recoveryHash === false) {
        throw new RuntimeException('The recovery code could not be securely hashed.');
    }

    $envFile = fsdimsOperatorEnvFile();
    $handle = @fopen($envFile, 'x');
    if ($handle === false) {
        throw new RuntimeException('Could not create the private operator credentials file. Check that the PHP process can write outside htdocs.');
    }

    $contents = 'FSDIMS_OPERATOR_USERNAME="' . $username . '"' . PHP_EOL
        . 'FSDIMS_OPERATOR_PASSWORD_HASH="' . $passwordHash . '"' . PHP_EOL
        . 'FSDIMS_OPERATOR_RECOVERY_HASH="' . $recoveryHash . '"' . PHP_EOL;
    $written = fwrite($handle, $contents);
    $flushed = $written === strlen($contents) && fflush($handle);
    fclose($handle);

    if (!$flushed) {
        @unlink($envFile);
        throw new RuntimeException('The private operator credentials file could not be written completely.');
    }

    return $recoveryCode;
}

function fsdimsResetOperatorPassword(string $username, string $recoveryCode, string $newPassword): string
{
    if (strlen($newPassword) < 12) {
        throw new InvalidArgumentException('Choose a passphrase with at least 12 characters.');
    }

    $credentials = fsdimsReadOperatorCredentials();
    if (
        $credentials === null
        || $credentials['recovery_hash'] === ''
        || !hash_equals($credentials['username'], trim($username))
        || !password_verify(strtoupper(trim($recoveryCode)), $credentials['recovery_hash'])
    ) {
        throw new RuntimeException('The operator ID or recovery code is incorrect.');
    }
    if (getenv('FSDIMS_OPERATOR_USERNAME') !== false || getenv('FSDIMS_OPERATOR_PASSWORD_HASH') !== false) {
        throw new RuntimeException('Password reset is disabled while operator credentials are set in the server environment.');
    }

    $newPasswordHash = password_hash($newPassword, PASSWORD_DEFAULT);
    $newRecoveryCode = strtoupper(bin2hex(random_bytes(32)));
    $newRecoveryHash = password_hash($newRecoveryCode, PASSWORD_DEFAULT);
    if ($newPasswordHash === false || $newRecoveryHash === false) {
        throw new RuntimeException('New credentials could not be securely generated.');
    }

    $envFile = fsdimsOperatorEnvFile();
    $temporaryFile = $envFile . '.' . bin2hex(random_bytes(8)) . '.tmp';
    $contents = 'FSDIMS_OPERATOR_USERNAME="' . $credentials['username'] . '"' . PHP_EOL
        . 'FSDIMS_OPERATOR_PASSWORD_HASH="' . $newPasswordHash . '"' . PHP_EOL
        . 'FSDIMS_OPERATOR_RECOVERY_HASH="' . $newRecoveryHash . '"' . PHP_EOL;

    if (file_put_contents($temporaryFile, $contents, LOCK_EX) !== strlen($contents)) {
        @unlink($temporaryFile);
        throw new RuntimeException('Updated credentials could not be written to a private temporary file.');
    }
    if (!@rename($temporaryFile, $envFile)) {
        @unlink($temporaryFile);
        throw new RuntimeException('Could not safely replace the private credentials file. Check file permissions.');
    }

    return $newRecoveryCode;
}
