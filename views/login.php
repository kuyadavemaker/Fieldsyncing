<?php
declare(strict_types=1);
$loginError = (string)($_SESSION['login_error'] ?? '');
$loginNotice = (string)($_SESSION['login_notice'] ?? '');
$recoveryCodeOnce = (string)($_SESSION['recovery_code_once'] ?? '');
unset($_SESSION['login_error']);
unset($_SESSION['login_notice'], $_SESSION['recovery_code_once']);
$showCreateForm = $authMode === 'create';
$isForgotMode = $authMode === 'forgot';
$showResetForm = $isForgotMode && $recoveryAvailable;
$isLoginMode = !$showCreateForm && !$isForgotMode;
$title = $showCreateForm ? 'Create your account' : ($showResetForm ? 'Recover your account' : 'Welcome back');
$description = $showCreateForm
    ? 'Set up your team’s calmer, more connected field service workspace.'
    : ($showResetForm
        ? 'Access Recovery Link — Provide your verified operational email parameters below to receive specialized cryptographic key routing details.'
        : 'Sign in to pick up where your team left off.');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($authMode === 'home' ? 'Field service, in sync' : ($showCreateForm ? 'Create account' : ($showResetForm ? 'Account recovery' : 'Sign in')), ENT_QUOTES, 'UTF-8') ?> | FSDIMS</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/workstation.css?v=<?= (int)filemtime(__DIR__ . '/../assets/css/workstation.css') ?>">
    <?php if ($authMode === 'home'): ?>
        <link rel="stylesheet" href="assets/css/landing.css?v=<?= (int)filemtime(__DIR__ . '/../assets/css/landing.css') ?>">
    <?php else: ?>
        <link rel="stylesheet" href="assets/css/landing.css?v=<?= (int)filemtime(__DIR__ . '/../assets/css/landing.css') ?>">
        <link rel="stylesheet" href="assets/css/auth.css?v=<?= (int)filemtime(__DIR__ . '/../assets/css/auth.css') ?>">
    <?php endif; ?>
</head>
<body class="<?= $authMode === 'home' ? 'login-page landing-home' : 'auth-page' ?>">
    <?php require __DIR__ . '/landing-nav.php'; ?>
    <?php if ($authMode === 'home'): ?>
        <?php require __DIR__ . '/landing.php'; ?>
    <?php else: ?>
    <main class="auth-layout">
        <header class="auth-brand">
            <p>Field work, in sync. A calmer way to run your field service operation.</p>
        </header>

        <section class="auth-card<?= $showCreateForm ? ' auth-card--registration' : '' ?><?= $showResetForm ? ' auth-card--recovery' : '' ?>" aria-labelledby="auth-title">
            <?php if ($showResetForm): ?>
                <div class="auth-card-accent" aria-hidden="true"></div>
            <?php endif; ?>

            <div class="auth-status"><span aria-hidden="true"></span>SYSTEM READY</div>

            <?php if ($showResetForm): ?>
                <div class="auth-recovery-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24" fill="none">
                        <rect x="5" y="10" width="14" height="11" rx="2"></rect>
                        <path d="M8 10V7a4 4 0 1 1 8 0v3M12 14v3"></path>
                    </svg>
                </div>
            <?php endif; ?>

            <div class="auth-card-heading">
                <p class="auth-eyebrow"><?= $showCreateForm ? 'DISPATCHER REGISTRATION' : ($showResetForm ? 'CREDENTIAL RECOVERY' : 'OPERATOR SIGN IN') ?></p>
                <h1 id="auth-title"><?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8') ?></h1>
                <p class="auth-description"><?= htmlspecialchars($description, ENT_QUOTES, 'UTF-8') ?></p>
            </div>

            <?php if ($loginError !== ''): ?>
                <div class="auth-alert" role="alert"><?= htmlspecialchars($loginError, ENT_QUOTES, 'UTF-8') ?></div>
            <?php endif; ?>
            <?php if ($loginNotice !== ''): ?>
                <div class="auth-alert auth-alert--success" role="status">
                    <p class="auth-success-message"><?= htmlspecialchars($loginNotice, ENT_QUOTES, 'UTF-8') ?></p>
                    <?php if ($recoveryCodeOnce !== ''): ?>
                        <div class="auth-recovery-code-panel">
                            <span class="auth-recovery-code-label">One-time recovery code</span>
                            <code class="auth-recovery-code"><?= htmlspecialchars($recoveryCodeOnce, ENT_QUOTES, 'UTF-8') ?></code>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <?php if ($showCreateForm || $showResetForm || $isLoginMode): ?>
                <form method="POST" action="index.php" class="auth-form" autocomplete="on">
                    <input type="hidden" name="action" value="<?= $showCreateForm ? 'create_company_account' : ($showResetForm ? 'reset_company_password' : 'login') ?>">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>">

                    <?php if ($showCreateForm): ?>
                        <div class="auth-field-grid">
                            <div class="auth-field">
                                <label for="first-name">First name</label>
                                <div class="auth-input-wrap">
                                    <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="8" r="3.5"></circle><path d="M5 20a7 7 0 0 1 14 0"></path></svg>
                                    <input id="first-name" name="first_name" type="text" minlength="1" maxlength="59" autocomplete="given-name" required autofocus>
                                </div>
                            </div>
                            <div class="auth-field">
                                <label for="last-name">Last name</label>
                                <div class="auth-input-wrap">
                                    <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="8" r="3.5"></circle><path d="M5 20a7 7 0 0 1 14 0"></path></svg>
                                    <input id="last-name" name="last_name" type="text" minlength="1" maxlength="59" autocomplete="family-name" required>
                                </div>
                            </div>
                            <div class="auth-field auth-field--wide">
                                <label for="operator-email">Operational email</label>
                                <div class="auth-input-wrap">
                                    <svg viewBox="0 0 24 24" aria-hidden="true"><rect x="3" y="5" width="18" height="14" rx="2"></rect><path d="m4 7 8 6 8-6"></path></svg>
                                    <input id="operator-email" name="email" type="email" maxlength="254" autocomplete="email" required>
                                </div>
                            </div>
                            <div class="auth-field">
                                <label for="operator-password">Password</label>
                                <div class="auth-input-wrap">
                                    <svg viewBox="0 0 24 24" aria-hidden="true"><rect x="4" y="10" width="16" height="11" rx="2"></rect><path d="M8 10V7a4 4 0 1 1 8 0v3M12 14v3"></path></svg>
                                    <input id="operator-password" name="password" type="password" minlength="12" autocomplete="new-password" required>
                                </div>
                            </div>
                            <div class="auth-field">
                                <label for="operator-password-confirmation">Confirm password</label>
                                <div class="auth-input-wrap">
                                    <svg viewBox="0 0 24 24" aria-hidden="true"><rect x="4" y="10" width="16" height="11" rx="2"></rect><path d="M8 10V7a4 4 0 1 1 8 0v3M9 15l2 2 4-4"></path></svg>
                                    <input id="operator-password-confirmation" name="password_confirmation" type="password" minlength="12" autocomplete="new-password" required>
                                </div>
                            </div>
                        </div>
                        <div class="auth-role">Account role <strong>Head Dispatcher</strong></div>
                        <p class="auth-hint">Use your operational email to sign in. Choose at least 12 characters for your password. Your one-time recovery code will be shown after setup.</p>
                    <?php elseif ($showResetForm): ?>
                        <div class="auth-field-grid">
                            <div class="auth-field auth-field--wide">
                                <label for="operator-email">Verified operational email</label>
                                <div class="auth-input-wrap">
                                    <svg viewBox="0 0 24 24" aria-hidden="true"><rect x="3" y="5" width="18" height="14" rx="2"></rect><path d="m4 7 8 6 8-6"></path></svg>
                                    <input id="operator-email" name="email" type="email" maxlength="254" autocomplete="email" required autofocus>
                                </div>
                            </div>
                            <div class="auth-field auth-field--wide">
                                <label for="recovery-code">One-time recovery code</label>
                                <div class="auth-input-wrap">
                                    <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="8" cy="15" r="4"></circle><path d="m11 12 8-8 2 2-2 2 2 2-3 3-2-2-2 2"></path></svg>
                                    <input id="recovery-code" name="recovery_code" type="text" minlength="64" maxlength="64" pattern="[A-Fa-f0-9]{64}" autocomplete="off" required>
                                </div>
                            </div>
                            <div class="auth-field">
                                <label for="operator-password">New password</label>
                                <div class="auth-input-wrap">
                                    <svg viewBox="0 0 24 24" aria-hidden="true"><rect x="4" y="10" width="16" height="11" rx="2"></rect><path d="M8 10V7a4 4 0 1 1 8 0v3M12 14v3"></path></svg>
                                    <input id="operator-password" name="password" type="password" minlength="12" autocomplete="new-password" required>
                                </div>
                            </div>
                            <div class="auth-field">
                                <label for="operator-password-confirmation">Confirm password</label>
                                <div class="auth-input-wrap">
                                    <svg viewBox="0 0 24 24" aria-hidden="true"><rect x="4" y="10" width="16" height="11" rx="2"></rect><path d="M8 10V7a4 4 0 1 1 8 0v3M9 15l2 2 4-4"></path></svg>
                                    <input id="operator-password-confirmation" name="password_confirmation" type="password" minlength="12" autocomplete="new-password" required>
                                </div>
                            </div>
                        </div>
                        <p class="auth-hint">Your recovery code can only be used once. A replacement code will be issued after reset.</p>
                    <?php elseif ($isLoginMode): ?>
                        <div class="auth-field-grid">
                            <div class="auth-field auth-field--wide">
                                <label for="operator-email">Operational email</label>
                                <div class="auth-input-wrap">
                                    <svg viewBox="0 0 24 24" aria-hidden="true"><rect x="3" y="5" width="18" height="14" rx="2"></rect><path d="m4 7 8 6 8-6"></path></svg>
                                    <input id="operator-email" name="email" type="email" maxlength="254" autocomplete="email" required autofocus>
                                </div>
                            </div>
                            <div class="auth-field auth-field--wide">
                                <label for="operator-password">Password</label>
                                <div class="auth-input-wrap">
                                    <svg viewBox="0 0 24 24" aria-hidden="true"><rect x="4" y="10" width="16" height="11" rx="2"></rect><path d="M8 10V7a4 4 0 1 1 8 0v3M12 14v3"></path></svg>
                                    <input id="operator-password" name="password" type="password" autocomplete="current-password" required>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>

                    <button class="auth-submit" type="submit" aria-live="polite">
                        <span class="auth-submit-label"><?= $showCreateForm ? 'Create account' : ($showResetForm ? 'Reset password' : 'Sign in') ?></span>
                        <span class="auth-submit-indicator" aria-hidden="true">→</span>
                    </button>
                </form>
            <?php elseif ($isForgotMode): ?>
                <div class="auth-alert" role="status">Password recovery is unavailable. Use your saved recovery code or contact your system administrator.</div>
            <?php endif; ?>

            <nav class="auth-links" aria-label="Authentication options">
                <?php if ($showCreateForm || $showResetForm): ?>
                    <a href="index.php?auth=login">Back to sign in</a>
                <?php elseif ($isLoginMode): ?>
                    <a href="index.php?auth=forgot">Forgot your password?</a>
                    <a href="index.php?auth=create">Create an account</a>
                <?php endif; ?>
            </nav>
        </section>
        <p class="auth-footer">Built for the people who keep your field service moving.</p>
    </main>
    <?php endif; ?>
    <script>
        // Sync public navigation with the current location.
        (() => {
            const authForm = document.querySelector('.auth-form');
            if (authForm) {
                authForm.addEventListener('submit', () => {
                    const submitButton = authForm.querySelector('.auth-submit');
                    const submitLabel = authForm.querySelector('.auth-submit-label');
                    const action = authForm.querySelector('input[name="action"]')?.value;
                    if (!submitButton || !submitLabel || submitButton.disabled) return;

                    const loadingLabels = {
                        login: 'Signing in...',
                        create_company_account: 'Creating account...',
                        reset_company_password: 'Resetting password...'
                    };
                    submitButton.disabled = true;
                    submitButton.setAttribute('aria-busy', 'true');
                    submitButton.classList.add('is-loading');
                    submitLabel.textContent = loadingLabels[action] || 'Processing...';
                });
            }

            const links = Array.from(document.querySelectorAll('.landing-nav-links a'));
            const setActiveLink = (current) => {
                links.forEach((link) => {
                    const active = link === current;
                    link.classList.toggle('is-active', active);
                    if (active) link.setAttribute('aria-current', 'page');
                    else link.removeAttribute('aria-current');
                });
            };
            const syncFromLocation = () => {
                const authMode = new URLSearchParams(window.location.search).get('auth');
                const isAuthScreen = ['login', 'create', 'forgot'].includes(authMode);
                const target = isAuthScreen ? '' : window.location.hash.slice(1);
                const current = isAuthScreen
                    ? links.find((link) => link.textContent.trim() === 'Login')
                    : target
                        ? links.find((link) => new URL(link.href, window.location.href).hash.slice(1) === target)
                        : links.find((link) => link.textContent.trim() === 'Home');
                if (current) setActiveLink(current);
            };

            links.forEach((link) => link.addEventListener('click', () => setActiveLink(link)));
            window.addEventListener('hashchange', syncFromLocation);
            window.addEventListener('popstate', syncFromLocation);
            syncFromLocation();
        })();
    </script>
</body>
</html>
