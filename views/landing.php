<?php
declare(strict_types=1);

$todayLabel = strtoupper(date('l, F j'));
?>
<div class="landing-page">
    <main class="landing-main">
        <?php if ($loginError !== ''): ?>
            <div class="landing-notice" role="alert">
                <?= htmlspecialchars($loginError, ENT_QUOTES, 'UTF-8') ?>
            </div>
        <?php endif; ?>

        <section class="landing-hero" id="home" aria-labelledby="landing-title">
            <div class="hero-copy">
                <div class="eyebrow">
                    <span class="eyebrow-pulse" aria-hidden="true"></span>
                    FIELD SERVICE, REIMAGINED
                </div>
                <h1 id="landing-title">Good work<br>happens <span>in sync.</span></h1>
                <p class="hero-description">
                    One calm, connected workspace for the people who keep everything moving.
                    Dispatch smarter, stay stocked, and get paid sooner.
                </p>
                <div class="hero-actions">
                    <a class="button button-primary" href="index.php?auth=create">
                        Start your workspace
                        <svg viewBox="0 0 20 20" aria-hidden="true">
                            <path d="M4 10h11m-5-5 5 5-5 5"/>
                        </svg>
                    </a>
                    <a class="button button-outline" href="#platform">
                        <span class="play-icon" aria-hidden="true">
                            <svg viewBox="0 0 20 20">
                                <path d="m7 4 9 6-9 6z"/>
                            </svg>
                        </span>
                        Explore the platform
                    </a>
                </div>
                <div class="hero-proof">
                    <div class="avatar-stack" aria-hidden="true">
                        <span>JD</span><span>MK</span><span>AR</span><span>+</span>
                    </div>
                    <p>
                        <strong>Built for the people in the field</strong><br>
                        Less juggling. More great work.
                    </p>
                </div>
            </div>

            <aside class="hero-visual" role="img" aria-label="Illustrative FieldSync dispatch dashboard preview">
                <div class="visual-orbit orbit-one" aria-hidden="true"></div>
                <div class="visual-orbit orbit-two" aria-hidden="true"></div>
                <div class="dashboard-card">
                    <div class="dashboard-top">
                        <div class="window-dots" aria-hidden="true"><i></i><i></i><i></i></div>
                        <span>WORKSPACE / OVERVIEW</span>
                        <span aria-hidden="true">•••</span>
                    </div>
                    <div class="dash-heading">
                        <div>
                            <small><?= htmlspecialchars($todayLabel, ENT_QUOTES, 'UTF-8') ?></small>
                            <h2>Good morning, Jordan <span aria-hidden="true">✦</span></h2>
                        </div>
                        <span class="live-pill"><i aria-hidden="true"></i> LIVE</span>
                    </div>
                    <div class="dash-stats">
                        <div class="dash-stat">
                            <span>ACTIVE JOBS</span>
                            <strong>24 <small>↗ 12%</small></strong>
                            <div class="mini-bars" aria-hidden="true">
                                <i></i><i></i><i></i><i></i><i></i><i></i><i></i><i></i><i></i>
                            </div>
                        </div>
                        <div class="dash-stat">
                            <span>COMPLETED</span>
                            <strong>18 <small>today</small></strong>
                            <div class="stat-progress" aria-hidden="true"><i></i></div>
                        </div>
                        <div class="dash-stat">
                            <span>ON THE ROAD</span>
                            <strong>08 <small>techs</small></strong>
                            <div class="tech-dots" aria-hidden="true"><i></i><i></i><i></i><i></i><b>+4</b></div>
                        </div>
                    </div>
                    <div class="dispatch-head">
                        <span>UP NEXT</span>
                        <span class="dispatch-label">TODAY'S SCHEDULE</span>
                    </div>
                    <div class="dispatch-row">
                        <span class="dispatch-icon icon-cyan" aria-hidden="true">
                            <svg viewBox="0 0 24 24">
                                <path d="M4 19V9l8-5 8 5v10H4Z"/>
                                <path d="M9 19v-6h6v6"/>
                            </svg>
                        </span>
                        <span class="job-name">
                            <strong>Northside HVAC · Unit repair</strong>
                            <small>Alex Rivera <i>·</i> 9:30 AM</small>
                        </span>
                        <span class="job-status">EN ROUTE</span>
                    </div>
                    <div class="dispatch-row">
                        <span class="dispatch-icon icon-green" aria-hidden="true">
                            <svg viewBox="0 0 24 24">
                                <path d="M12 3v18M5 8h14M5 16h14"/>
                                <circle cx="12" cy="12" r="9"/>
                            </svg>
                        </span>
                        <span class="job-name">
                            <strong>Evergreen Plumbing · Inspection</strong>
                            <small>Morgan Lee <i>·</i> 10:15 AM</small>
                        </span>
                        <span class="job-status status-pending">SCHEDULED</span>
                    </div>
                    <div class="map-strip" aria-hidden="true">
                        <div class="map-lines"></div>
                        <span class="map-pin pin-a">●</span>
                        <span class="map-pin pin-b">●</span>
                        <span class="map-label"><i></i> 8 technicians in the field</span>
                    </div>
                </div>
                <div class="floating-note note-top">
                    <span class="note-check" aria-hidden="true">
                        <svg viewBox="0 0 20 20"><path d="m4 10 4 4 8-9"/></svg>
                    </span>
                    <span><strong>Job completed</strong><small>Invoice sent automatically</small></span>
                </div>
                <div class="floating-note note-bottom">
                    <span class="note-chart" aria-hidden="true">↗</span>
                    <span><strong>All caught up</strong><small>Inventory looks healthy</small></span>
                </div>
            </aside>

            <div class="hero-bottomline" aria-hidden="true">
                <span>ONE PLATFORM. THE WHOLE OPERATION.</span>
                <span>DESIGNED FOR TEAMS THAT KEEP THE WORLD RUNNING <b>↓</b></span>
            </div>
        </section>

        <div class="marquee-band" role="group" aria-label="Industries supported">
            <div class="marquee-track" aria-hidden="true">
                <span>HVAC</span><i>✳</i>
                <span>PLUMBING</span><i>✳</i>
                <span>ELECTRICAL</span><i>✳</i>
                <span>FACILITIES</span><i>✳</i>
                <span>FIELD SERVICE</span><i>✳</i>
                <span>HVAC</span><i>✳</i>
                <span>PLUMBING</span><i>✳</i>
                <span>ELECTRICAL</span><i>✳</i>
                <span>FACILITIES</span><i>✳</i>
                <span>FIELD SERVICE</span><i>✳</i>
            </div>
        </div>

        <section class="landing-section platform-section" id="platform" aria-labelledby="platform-title">
            <div class="section-intro">
                <div>
                    <div class="eyebrow"><span class="eyebrow-pulse" aria-hidden="true"></span> EVERYTHING, CONNECTED</div>
                    <h2 id="platform-title">Tools that work<br><span>as hard as you do.</span></h2>
                </div>
                <p>Every part of your operation, finally in the same place. Less admin between the work and the win.</p>
            </div>
            <div class="feature-grid">
                <article class="feature-card feature-large">
                    <div class="feature-icon feature-icon-green" aria-hidden="true">
                        <svg viewBox="0 0 24 24">
                            <rect x="3" y="4" width="18" height="16" rx="2"/>
                            <path d="M8 2v4m8-4v4M3 10h18M8 14h3m-3 3h7"/>
                        </svg>
                    </div>
                    <span class="card-index">01 / DISPATCH</span>
                    <h3>Every job, right<br>where it belongs.</h3>
                    <p>Keep the day moving with a clear view of jobs, people, and priorities — all in real time.</p>
                    <div class="card-visual schedule-visual" aria-hidden="true">
                        <div class="schedule-labels"><span>9 AM</span><span>10 AM</span><span>11 AM</span></div>
                        <div class="schedule-grid">
                            <div class="schedule-block block-green">Install · Alex</div>
                            <div class="schedule-block block-cyan">Service · Morgan</div>
                            <div class="schedule-block block-violet">Quote · Jamie</div>
                        </div>
                    </div>
                </article>
                <article class="feature-card">
                    <div class="feature-icon feature-icon-cyan" aria-hidden="true">
                        <svg viewBox="0 0 24 24">
                            <path d="M20 7 12 3 4 7v10l8 4 8-4V7Z"/>
                            <path d="m4.5 7.3 7.5 4 7.5-4M12 21V11.5M8 5l8 4"/>
                        </svg>
                    </div>
                    <span class="card-index">02 / INVENTORY</span>
                    <h3>Know what's<br>on the truck.</h3>
                    <p>Track parts, stock, and equipment without another spreadsheet to chase.</p>
                    <div class="inventory-pills" aria-label="Example inventory">
                        <span><i aria-hidden="true"></i> Air filters <b>24</b></span>
                        <span><i aria-hidden="true"></i> Copper fittings <b>18</b></span>
                        <span><i aria-hidden="true"></i> Thermostats <b>07</b></span>
                    </div>
                </article>
                <article class="feature-card">
                    <div class="feature-icon feature-icon-amber" aria-hidden="true">
                        <svg viewBox="0 0 24 24">
                            <rect x="4" y="3" width="16" height="18" rx="2"/>
                            <path d="M8 8h8m-8 4h8m-8 4h4"/>
                            <path d="m15 17 2 2 4-5"/>
                        </svg>
                    </div>
                    <span class="card-index">03 / INVOICING</span>
                    <h3>Close the loop.<br>Get paid faster.</h3>
                    <p>Turn completed jobs into clean invoices, while every detail is still fresh.</p>
                    <div class="invoice-preview" aria-label="Example invoice">
                        <span>INVOICE #1048</span>
                        <strong>$486.00</strong>
                        <small><i aria-hidden="true"></i> SENT JUST NOW</small>
                    </div>
                </article>
            </div>
        </section>

        <section class="workflow-section" id="workflow" aria-labelledby="workflow-title">
            <div class="workflow-inner">
                <div class="workflow-copy">
                    <div class="eyebrow"><span class="eyebrow-pulse" aria-hidden="true"></span> A BETTER WAY TO WORK</div>
                    <h2 id="workflow-title">From first call<br>to <span>paid in full.</span></h2>
                    <p>One smooth handoff at a time. FieldSync keeps the details flowing so your team can focus on the work that matters.</p>
                </div>
                <div class="workflow-steps">
                    <article class="workflow-step">
                        <span class="step-number" aria-hidden="true">01</span>
                        <span class="step-icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24"><path d="M5 4h14v16H5zM8 8h8m-8 4h8m-8 4h5"/></svg>
                        </span>
                        <div><h3>Capture the work</h3><p>Every customer, asset, and request starts with a clear record.</p></div>
                        <span class="step-arrow" aria-hidden="true">↗</span>
                    </article>
                    <article class="workflow-step">
                        <span class="step-number" aria-hidden="true">02</span>
                        <span class="step-icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3 2"/></svg>
                        </span>
                        <div><h3>Get the right person there</h3><p>Keep schedules, technicians, and job updates together.</p></div>
                        <span class="step-arrow" aria-hidden="true">↗</span>
                    </article>
                    <article class="workflow-step">
                        <span class="step-number" aria-hidden="true">03</span>
                        <span class="step-icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24">
                                <path d="M4 5h16v14H4zM8 9h8m-8 4h5"/>
                                <path d="m14 17 2 2 4-5"/>
                            </svg>
                        </span>
                        <div><h3>Wrap up without the wait</h3><p>Finish the job, send the invoice, and get back to your people.</p></div>
                        <span class="step-arrow" aria-hidden="true">↗</span>
                    </article>
                </div>
            </div>
        </section>

        <section class="people-section" id="about" aria-labelledby="team-title">
            <div class="people-copy">
                <div class="eyebrow"><span class="eyebrow-pulse" aria-hidden="true"></span> MADE FOR YOUR CREW</div>
                <h2 id="team-title">Good work is<br><span>a team sport.</span></h2>
                <p>Give dispatchers, technicians, and owners a shared view of the day — and the space to do their best work.</p>
                <div class="people-quote">
                    <div class="quote-avatar" aria-hidden="true">FS</div>
                    <p>Built for real field teams.<br><strong>Simple enough to start. Ready to grow.</strong></p>
                </div>
            </div>
            <div class="people-visual" role="img" aria-label="Example team status view">
                <div class="people-card">
                    <div class="people-card-head">
                        <span>YOUR TEAM, IN SYNC</span>
                        <span class="team-live"><i aria-hidden="true"></i> ALL SYSTEMS GO</span>
                    </div>
                    <div class="team-row">
                        <span class="team-avatar team-one">JD</span>
                        <span><strong>Jordan Davis</strong><small>Head dispatcher</small></span>
                        <b class="team-status">ONLINE</b>
                    </div>
                    <div class="team-row">
                        <span class="team-avatar team-two">AR</span>
                        <span><strong>Alex Rivera</strong><small>Field technician</small></span>
                        <b class="team-status">ON A JOB</b>
                    </div>
                    <div class="team-row">
                        <span class="team-avatar team-three">ML</span>
                        <span><strong>Morgan Lee</strong><small>Field technician</small></span>
                        <b class="team-status">ON THE WAY</b>
                    </div>
                    <div class="team-row">
                        <span class="team-avatar team-four">JT</span>
                        <span><strong>Jamie Taylor</strong><small>Field technician</small></span>
                        <b class="team-status">AVAILABLE</b>
                    </div>
                    <div class="team-footer">
                        <span>4 teammates online</span>
                        <span class="team-footer-avatars" aria-hidden="true">● ● ● ●</span>
                    </div>
                </div>
                <div class="people-glow" aria-hidden="true"></div>
            </div>
        </section>

        <footer class="landing-footer" id="contact">
            <a class="landing-brand" href="index.php" aria-label="FieldSync home">
                <span class="landing-brand-icon" aria-hidden="true">
                    <svg viewBox="0 0 24 24">
                        <path d="M4 12.5 9 17l11-11"/>
                        <path d="M12 3h9v9"/>
                    </svg>
                </span>
                <span>field<span>sync</span></span>
            </a>
            <p>Less busywork. More good work.</p>
            <div>
                <a href="index.php?auth=login">Log in</a>
                <a class="button button-primary footer-cta" href="index.php?auth=create">
                    Get started
                    <svg viewBox="0 0 20 20" aria-hidden="true">
                        <path d="M4 10h11m-5-5 5 5-5 5"/>
                    </svg>
                </a>
            </div>
            <small>© <?= date('Y') ?> FieldSync · Field service, in sync.</small>
        </footer>
    </main>
</div>
<script>
    (() => {
        const header = document.querySelector('[data-site-header]');
        const navLinks = Array.from(document.querySelectorAll('.landing-nav-links a'));
        const trackedSections = navLinks
            .map((link) => document.getElementById(new URL(link.href, window.location.href).hash.slice(1)))
            .filter((section) => section instanceof HTMLElement);
        if (!header || trackedSections.length === 0) return;

        // Track only sections shown in the centered menu.
        const setActiveSection = (sectionId) => {
            navLinks.forEach((link) => {
                const isActive = new URL(link.href, window.location.href).hash === `#${sectionId}`;
                link.classList.toggle('is-active', isActive);
                if (isActive) link.setAttribute('aria-current', 'location');
                else link.removeAttribute('aria-current');
            });
        };

        let scrollUpdatePending = false;
        const syncActiveSection = () => {
            scrollUpdatePending = false;
            const atPageEnd = window.scrollY + window.innerHeight >= document.documentElement.scrollHeight - 2;
            if (atPageEnd) {
                setActiveSection(trackedSections[trackedSections.length - 1].id);
                return;
            }
            const activeLine = header.getBoundingClientRect().height + Math.min(160, window.innerHeight * 0.25);
            const current = trackedSections.reduce((nearest, section) => {
                const distance = Math.abs(section.getBoundingClientRect().top - activeLine);
                return distance < nearest.distance ? { section, distance } : nearest;
            }, { section: trackedSections[0], distance: Number.POSITIVE_INFINITY }).section;
            if (current) setActiveSection(current.id);
        };
        const scheduleScrollSync = () => {
            if (scrollUpdatePending) return;
            scrollUpdatePending = true;
            window.requestAnimationFrame(syncActiveSection);
        };

        // Keep the active menu item in sync while scrolling.
        const observer = new IntersectionObserver(scheduleScrollSync, {
            rootMargin: `-${header.offsetHeight + 8}px 0px -80% 0px`,
            threshold: 0
        });
        trackedSections.forEach((section) => observer.observe(section));
        window.addEventListener('scroll', scheduleScrollSync, { passive: true });
        window.addEventListener('resize', scheduleScrollSync);
        window.addEventListener('pageshow', scheduleScrollSync);
        navLinks.forEach((link) => {
            link.addEventListener('click', () => {
                const sectionId = new URL(link.href, window.location.href).hash.slice(1);
                if (sectionId) setActiveSection(sectionId);
            });
        });
        window.addEventListener('hashchange', () => {
            const section = document.getElementById(window.location.hash.slice(1));
            if (section && trackedSections.includes(section)) setActiveSection(section.id);
            else scheduleScrollSync();
        });

        if (window.location.hash) {
            const initialSection = document.getElementById(window.location.hash.slice(1));
            if (initialSection && trackedSections.includes(initialSection)) setActiveSection(initialSection.id);
        }
        scheduleScrollSync();
    })();
</script>
