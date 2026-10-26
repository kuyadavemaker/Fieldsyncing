<?php
declare(strict_types=1);

$authNavigation = ($authMode ?? 'home') !== 'home';
$landingHref = $authNavigation ? 'index.php?auth=home' : 'index.php';
$sectionHref = static fn(string $section): string => $authNavigation
    ? 'index.php?auth=home#' . $section
    : '#' . $section;
$activePage = (string)($authMode ?? 'home');
?>
<header class="landing-nav-wrap" data-site-header>
    <nav class="landing-nav" aria-label="Main navigation">
        <a class="landing-brand" href="<?= htmlspecialchars($landingHref, ENT_QUOTES, 'UTF-8') ?>#home" aria-label="FSDIMS Field Service home">
            <span class="landing-brand-short">FSDIMS</span><span class="landing-brand-long"><i aria-hidden="true">·</i> FIELD SERVICE</span>
        </a>

        <div class="landing-nav-links">
            <a href="<?= htmlspecialchars($sectionHref('home'), ENT_QUOTES, 'UTF-8') ?>"<?= $activePage === 'home' ? ' class="is-active" aria-current="page"' : '' ?>>Home</a>
            <a href="<?= htmlspecialchars($sectionHref('platform'), ENT_QUOTES, 'UTF-8') ?>"<?= $activePage === 'platform' ? ' class="is-active" aria-current="page"' : '' ?>>Platform</a>
            <a href="<?= htmlspecialchars($sectionHref('workflow'), ENT_QUOTES, 'UTF-8') ?>"<?= $activePage === 'workflow' ? ' class="is-active" aria-current="page"' : '' ?>>Workflow</a>
            <a href="<?= htmlspecialchars($sectionHref('about'), ENT_QUOTES, 'UTF-8') ?>"<?= $activePage === 'about' ? ' class="is-active" aria-current="page"' : '' ?>>About</a>
            <a href="<?= htmlspecialchars($sectionHref('contact'), ENT_QUOTES, 'UTF-8') ?>"<?= $activePage === 'contact' ? ' class="is-active" aria-current="page"' : '' ?>>Contact</a>
            <a href="index.php?auth=login"<?= $activePage === 'login' ? ' class="is-active" aria-current="page"' : '' ?>>Login</a>
        </div>
    </nav>
</header>
