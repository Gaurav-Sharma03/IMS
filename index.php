<?php
// 1. Load Config & Session
require_once __DIR__ . '/config/constants.php';
require_once __DIR__ . '/config/session.php';
require_once __DIR__ . '/config/database.php';

// 2. SMART REDIRECT: If logged in, go to dashboard immediately
if (isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true) {
    $role = $_SESSION['role'] ?? '';
    $redirect = match($role) {
        'superadmin' => 'views/dashboard/superadmin.php',
        'admin'      => 'views/dashboard/admin.php',
        'staff'      => 'views/dashboard/staff.php',
        'client'     => 'views/dashboard/client.php',
        'user'       => 'views/dashboard/setup-company.php',
        default      => 'views/auth/login.php'
    };
    header("Location: " . BASE_URL . $redirect);
    exit;
}

// 3. Fetch Testimonials from DB
$testimonials = [];
try {
    $db = (new Database())->connect();
    // Fetch 3 active testimonials
    $stmt = $db->query("SELECT * FROM testimonials WHERE is_active = 1 ORDER BY created_at DESC LIMIT 3");
    $testimonials = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    // Silent fail if DB not ready yet
}

// 4. Include Header
// NOTE: Ensure this path matches where you saved the header.php
include 'views/layouts/lending-header.php'; 
?>

<style>
    /* --- PAGE SPECIFIC STYLES --- */
    
    /* 1. Hero Animations */
    .hero-section {
        overflow: hidden;
        padding-top: 120px;
        padding-bottom: 100px;
        background: radial-gradient(circle at top right, #eef2ff 0%, transparent 40%),
                    radial-gradient(circle at bottom left, #f0fdfa 0%, transparent 40%);
    }

    .glass-card {
        background: rgba(255, 255, 255, 0.7);
        backdrop-filter: blur(12px);
        border: 1px solid rgba(255, 255, 255, 0.8);
        border-radius: 24px;
        box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.1);
        padding: 2rem;
        transform: perspective(1000px) rotateY(-5deg) rotateX(2deg);
        transition: transform 0.4s ease-out;
    }
    .glass-card:hover { transform: perspective(1000px) rotateY(0deg) rotateX(0deg); }

    /* 2. Logo Strip */
    .logo-strip {
        filter: grayscale(100%);
        opacity: 0.5;
        transition: 0.3s;
    }
    .logo-strip:hover { filter: grayscale(0%); opacity: 1; }

    /* 3. About Section (Dark Theme) */
    .about-section {
        background-color: var(--secondary-color); /* Navy */
        color: white;
        position: relative;
        overflow: hidden;
    }
    .stat-box {
        background: rgba(255, 255, 255, 0.05);
        border: 1px solid rgba(255, 255, 255, 0.1);
        padding: 1.5rem;
        border-radius: 16px;
        text-align: center;
        transition: transform 0.2s;
    }
    .stat-box:hover { transform: translateY(-5px); background: rgba(255, 255, 255, 0.08); }
    .stat-number { font-size: 2.5rem; font-weight: 800; color: var(--accent-color); line-height: 1; margin-bottom: 5px; }
    .stat-label { font-size: 0.75rem; color: #94a3b8; text-transform: uppercase; letter-spacing: 1px; font-weight: 600; }

    /* 4. Steps Section */
    .step-number {
        font-size: 5rem;
        font-weight: 900;
        color: rgba(79, 70, 229, 0.08); /* Very faint primary */
        line-height: 0.8;
        position: absolute;
        top: -20px;
        left: 20px;
        z-index: 0;
    }
    .step-card {
        position: relative;
        z-index: 1;
        background: white;
        padding: 2.5rem 2rem;
        border-radius: 1.5rem;
        border: 1px solid #f1f5f9;
        box-shadow: 0 10px 30px -10px rgba(0,0,0,0.03);
        transition: all 0.3s ease;
        height: 100%;
    }
    .step-card:hover { 
        transform: translateY(-10px); 
        box-shadow: 0 20px 40px -10px rgba(0,0,0,0.08);
        border-color: var(--primary-color);
    }

    /* 5. Features Grid */
    .feature-box {
        padding: 2rem;
        background: white;
        border-radius: 1.5rem;
        border: 1px solid #f1f5f9;
        transition: all 0.3s;
        height: 100%;
    }
    .feature-box:hover {
        border-color: var(--primary-color);
        box-shadow: 0 10px 30px rgba(79, 70, 229, 0.1);
    }
    .icon-square {
        width: 55px; height: 55px;
        border-radius: 14px;
        display: flex; align-items: center; justify-content: center;
        font-size: 1.5rem;
        margin-bottom: 1.5rem;
    }

    /* 6. Testimonials */
    .testimonial-card {
        background: white;
        border: 1px solid #e2e8f0;
        padding: 2.5rem;
        border-radius: 1.5rem;
        height: 100%;
        position: relative;
    }
    .quote-icon {
        position: absolute; top: 20px; right: 20px;
        font-size: 4rem; color: #f1f5f9; z-index: 0;
    }
</style>

<section class="hero-section">
    <div class="container">
        <div class="row align-items-center">
            
            <div class="col-lg-6 mb-5 mb-lg-0">
                <div class="d-inline-flex align-items-center bg-white border shadow-sm rounded-pill px-3 py-2 mb-4">
                    <span class="badge bg-primary rounded-pill me-2">New</span>
                    <span class="text-muted small fw-bold">v2.0 Dashboard is Live</span>
                </div>
                
                <h1 class="display-4 fw-bold mb-4" style="color: var(--secondary-color); letter-spacing: -1px;">
                    Invoicing made <br>
                    <span style="color: var(--primary-color);">simple & powerful.</span>
                </h1>
                
                <p class="lead text-muted mb-5" style="max-width: 500px;">
                    Stop chasing payments. Create professional invoices, track expenses, and manage clients in one secure platform designed for growth.
                </p>
                
                <div class="d-flex gap-3 flex-wrap">
                    <a href="views/auth/register.php" class="btn btn-signup btn-lg px-5 shadow-lg">Start Free Trial</a>
                    <a href="#how-it-works" class="btn btn-outline-dark btn-lg px-4 rounded-pill fw-bold border-2">
                        <i class="fa-regular fa-circle-play me-2"></i> How it works
                    </a>
                </div>
                
                <div class="mt-4 text-muted small fw-bold d-flex gap-4">
                    <span><i class="fa-solid fa-check text-success me-2"></i> No credit card needed</span>
                    <span><i class="fa-solid fa-check text-success me-2"></i> Cancel anytime</span>
                </div>
            </div>

            <div class="col-lg-6">
                <div class="glass-card">
                    <div class="d-flex justify-content-between align-items-center mb-4 border-bottom pb-3">
                        <div>
                            <h5 class="fw-bold mb-0 text-dark">Revenue</h5>
                            <span class="text-muted small">This Month</span>
                        </div>
                        <div class="bg-success bg-opacity-10 text-success px-3 py-1 rounded-pill fw-bold small">
                            +12.5% <i class="fa-solid fa-arrow-trend-up ms-1"></i>
                        </div>
                    </div>

                    <div class="d-flex align-items-end gap-2 mb-4" style="height: 120px;">
                        <div class="w-100 bg-primary bg-opacity-10 rounded-top" style="height: 40%"></div>
                        <div class="w-100 bg-primary bg-opacity-25 rounded-top" style="height: 60%"></div>
                        <div class="w-100 bg-primary bg-opacity-50 rounded-top" style="height: 35%"></div>
                        <div class="w-100 bg-primary rounded-top" style="height: 85%"></div>
                        <div class="w-100 bg-primary bg-opacity-75 rounded-top" style="height: 65%"></div>
                    </div>

                    <div class="d-flex align-items-center justify-content-between p-3 bg-white rounded-3 mb-2 shadow-sm border border-light">
                        <div class="d-flex align-items-center gap-3">
                            <div class="bg-light p-2 rounded text-primary"><i class="fa-solid fa-file-invoice"></i></div>
                            <div>
                                <h6 class="mb-0 fw-bold small text-dark">Invoice #1024</h6>
                                <span class="text-muted" style="font-size: 11px;">Web Development</span>
                            </div>
                        </div>
                        <span class="fw-bold text-dark">$1,250.00</span>
                    </div>

                    <div class="d-flex align-items-center justify-content-between p-3 bg-white rounded-3 shadow-sm border border-light">
                        <div class="d-flex align-items-center gap-3">
                            <div class="bg-light p-2 rounded text-success"><i class="fa-solid fa-check-circle"></i></div>
                            <div>
                                <h6 class="mb-0 fw-bold small text-dark">Payment Recvd</h6>
                                <span class="text-muted" style="font-size: 11px;">Consulting Fees</span>
                            </div>
                        </div>
                        <span class="fw-bold text-success">+$850.00</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<div class="py-4 border-top border-bottom bg-white">
    <div class="container">
        <p class="text-center text-muted small fw-bold text-uppercase mb-4" style="letter-spacing: 1px;">Trusted by 500+ innovative companies</p>
        <div class="d-flex justify-content-center flex-wrap gap-5 logo-strip">
            <h4 class="fw-bold text-secondary mb-0"><i class="fa-brands fa-google me-1"></i> Google</h4>
            <h4 class="fw-bold text-secondary mb-0"><i class="fa-brands fa-aws me-1"></i> Amazon</h4>
            <h4 class="fw-bold text-secondary mb-0"><i class="fa-brands fa-stripe me-1"></i> Stripe</h4>
            <h4 class="fw-bold text-secondary mb-0"><i class="fa-brands fa-spotify me-1"></i> Spotify</h4>
        </div>
    </div>
</div>

<section class="about-section py-5">
    <div class="container py-5">
        <div class="row align-items-center g-5">
            <div class="col-lg-6">
                <h6 class="text-uppercase fw-bold mb-3" style="color: var(--accent-color);">About IMS</h6>
                <h2 class="display-5 fw-bold mb-4">Empowering businesses to grow without boundaries.</h2>
                <p class="text-white-50 lead mb-4" style="font-weight: 300;">
                    We believe that financial tools shouldn't be complicated. IMS was built to bridge the gap between complex enterprise software and simple freelance tools.
                </p>
                <p class="text-white-50 mb-4">
                    Founded in 2024, our mission is to help 1 million businesses get paid faster. We prioritize security, ease of use, and automation so you can focus on what you do best—running your business.
                </p>
                <a href="#" class="btn btn-outline-light rounded-pill px-4 fw-bold mt-2">Read Our Story</a>
            </div>
            <div class="col-lg-6">
                <div class="row g-3">
                    <div class="col-6">
                        <div class="stat-box">
                            <div class="stat-number">10k+</div>
                            <div class="stat-label">Active Users</div>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="stat-box">
                            <div class="stat-number">$50M</div>
                            <div class="stat-label">Processed</div>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="stat-box">
                            <div class="stat-number">99.9%</div>
                            <div class="stat-label">Uptime</div>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="stat-box">
                            <div class="stat-number">24/7</div>
                            <div class="stat-label">Support</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<section id="how-it-works" class="py-5" style="background-color: #f8fafc;">
    <div class="container py-5">
        <div class="text-center mb-5">
            <h6 class="text-primary fw-bold text-uppercase ls-1">Workflow</h6>
            <h2 class="fw-bold display-6 text-dark">Get started in 3 steps</h2>
        </div>
        
        <div class="row g-4">
            <div class="col-md-4">
                <div class="step-card">
                    <div class="step-number">01</div>
                    <div class="mb-4 text-primary position-relative z-1"><i class="fa-solid fa-user-plus fa-3x"></i></div>
                    <h4 class="fw-bold position-relative z-1">Create Account</h4>
                    <p class="text-muted position-relative z-1">Sign up in seconds. Configure your company profile, logo, and branding settings.</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="step-card">
                    <div class="step-number">02</div>
                    <div class="mb-4 text-primary position-relative z-1"><i class="fa-solid fa-file-invoice fa-3x"></i></div>
                    <h4 class="fw-bold position-relative z-1">Send Invoices</h4>
                    <p class="text-muted position-relative z-1">Use our templates to create professional invoices. Send them via email with one click.</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="step-card">
                    <div class="step-number">03</div>
                    <div class="mb-4 text-primary position-relative z-1"><i class="fa-solid fa-sack-dollar fa-3x"></i></div>
                    <h4 class="fw-bold position-relative z-1">Get Paid</h4>
                    <p class="text-muted position-relative z-1">Clients receive a secure link to pay via card or bank transfer. Funds hit your account instantly.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<section id="features" class="py-5 bg-white">
    <div class="container py-5">
        <div class="text-center mb-5">
            <h6 class="text-uppercase fw-bold text-primary">Capabilities</h6>
            <h2 class="fw-bold display-6 mb-3 text-dark">Everything you need to run your business</h2>
            <p class="text-muted mx-auto" style="max-width: 600px;">A comprehensive suite of tools designed for freelancers, agencies, and enterprises.</p>
        </div>
        
        <div class="row g-4">
            <div class="col-md-4">
                <div class="feature-box">
                    <div class="icon-square bg-primary bg-opacity-10 text-primary">
                        <i class="fa-solid fa-bolt"></i>
                    </div>
                    <h4 class="fw-bold text-dark h5">Fast Invoicing</h4>
                    <p class="text-muted small mb-0">Create and send professional invoices in under 30 seconds. Templates included.</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="feature-box">
                    <div class="icon-square bg-success bg-opacity-10 text-success">
                        <i class="fa-solid fa-shield-halved"></i>
                    </div>
                    <h4 class="fw-bold text-dark h5">Bank-Grade Security</h4>
                    <p class="text-muted small mb-0">We use 256-bit SSL encryption and strict audit logs to keep your data safe.</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="feature-box">
                    <div class="icon-square bg-warning bg-opacity-10 text-warning">
                        <i class="fa-solid fa-chart-pie"></i>
                    </div>
                    <h4 class="fw-bold text-dark h5">Financial Insights</h4>
                    <p class="text-muted small mb-0">Real-time dashboards show you exactly how your business is performing.</p>
                </div>
            </div>
        </div>
        
        <div class="text-center mt-5">
            <a href="features.php" class="btn btn-link text-primary fw-bold text-decoration-none">
                View All Features <i class="fa-solid fa-arrow-right ms-1"></i>
            </a>
        </div>
    </div>
</section>

<section class="py-5" style="background-color: #f8fafc;">
    <div class="container py-4">
        <div class="text-center mb-5">
            <h6 class="text-primary fw-bold text-uppercase">Testimonials</h6>
            <h2 class="fw-bold text-dark">Loved by 10,000+ Users</h2>
        </div>

        <div class="row g-4">
            <?php if(empty($testimonials)): ?>
                <div class="col-md-4">
                    <div class="testimonial-card">
                        <i class="fa-solid fa-quote-right quote-icon"></i>
                        <div class="text-warning mb-3"><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i><i class="fa-solid fa-star"></i></div>
                        <p class="text-muted mb-4 position-relative z-1">"IMS transformed how we handle billing. We get paid 2x faster now thanks to the automated reminders."</p>
                        <div class="d-flex align-items-center gap-3">
                            <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center fw-bold" style="width: 45px; height: 45px;">S</div>
                            <div>
                                <h6 class="fw-bold mb-0 text-dark">Sarah Jenkins</h6>
                                <small class="text-muted">CEO, TechFlow</small>
                            </div>
                        </div>
                    </div>
                </div>
                <?php else: ?>
                <?php foreach($testimonials as $t): ?>
                <div class="col-md-4">
                    <div class="testimonial-card">
                        <i class="fa-solid fa-quote-right quote-icon"></i>
                        <div class="text-warning mb-3">
                            <?php for($i=0; $i<$t['rating']; $i++) echo '<i class="fa-solid fa-star"></i> '; ?>
                        </div>
                        <p class="text-muted mb-4 position-relative z-1">"<?= htmlspecialchars($t['content']) ?>"</p>
                        <div class="d-flex align-items-center gap-3">
                            <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center fw-bold" style="width: 45px; height: 45px;">
                                <?= strtoupper(substr($t['client_name'], 0, 1)) ?>
                            </div>
                            <div>
                                <h6 class="fw-bold mb-0 text-dark"><?= htmlspecialchars($t['client_name']) ?></h6>
                                <small class="text-muted"><?= htmlspecialchars($t['client_role']) ?>, <?= htmlspecialchars($t['company']) ?></small>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>

 <div class="text-center mt-5">
            <a href="feedback.php" class="btn btn-link text-primary fw-bold text-decoration-none">
                Give Your Valuable Feedback <i class="fa-solid fa-arrow-right ms-1"></i>
            </a>
        </div>

        </div>
    </div>
</section>

<section class="py-5 bg-white">
    <div class="container">
        <div class="rounded-5 p-5 text-center position-relative overflow-hidden" style="background-color: var(--secondary-color);">
            <div class="position-absolute top-0 start-0 w-100 h-100" style="background: radial-gradient(circle at 80% 20%, rgba(79, 70, 229, 0.2) 0%, transparent 50%);"></div>
            
            <div class="position-relative z-1">
                <h2 class="display-5 fw-bold text-white mb-4">Ready to get paid faster?</h2>
                <p class="text-light opacity-75 mb-5 mx-auto" style="max-width: 600px; font-size: 1.1rem;">
                    Join thousands of businesses managing their finances with IMS today. No credit card required.
                </p>
                <div class="d-flex justify-content-center gap-3">
                    <a href="views/auth/register.php" class="btn btn-light btn-lg rounded-pill px-5 fw-bold" style="color: var(--secondary-color);">Get Started</a>
                    <a href="views/auth/login.php" class="btn btn-outline-light btn-lg rounded-pill px-5 fw-bold">Login</a>
                </div>
            </div>
        </div>
    </div>
</section>

<?php 
// 5. Include Footer
include 'views/layouts/lending-footer.php'; 
?>