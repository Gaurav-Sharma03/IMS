<?php
// 1. Load Config & Session
require_once __DIR__ . '/config/constants.php';
require_once __DIR__ . '/config/session.php';

// 2. Include Header
include 'views/layouts/lending-header.php'; 
?>

<style>
    /* Feature Page Specific Styles */
    .feature-hero {
        background-color: var(--secondary-color);
        padding: 8rem 0 6rem 0;
        color: white;
        position: relative;
        overflow: hidden;
    }
    
    .feature-icon-box {
        width: 64px;
        height: 64px;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 16px;
        font-size: 1.75rem;
        margin-bottom: 1.5rem;
        transition: transform 0.3s ease;
    }

    .feature-row {
        padding: 5rem 0;
    }

    /* Abstract UI Representations (CSS Only) */
    .ui-card {
        background: white;
        border-radius: 16px;
        box-shadow: 0 20px 40px -10px rgba(0,0,0,0.1);
        border: 1px solid rgba(0,0,0,0.05);
        padding: 2rem;
        position: relative;
        overflow: hidden;
    }

    .ui-badge {
        display: inline-block;
        padding: 6px 12px;
        border-radius: 50px;
        font-size: 0.75rem;
        font-weight: 700;
        text-transform: uppercase;
    }

    /* Grid Items */
    .grid-feature-card {
        padding: 2rem;
        border-radius: 1rem;
        border: 1px solid var(--bg-light);
        transition: all 0.3s ease;
        height: 100%;
        background: #fff;
    }
    .grid-feature-card:hover {
        border-color: var(--primary-color);
        transform: translateY(-5px);
        box-shadow: 0 10px 30px rgba(0,0,0,0.05);
    }
</style>

<section class="feature-hero text-center">
    <div class="blob" style="top: -100px; left: -100px; opacity: 0.2;"></div>
    <div class="blob" style="bottom: -100px; right: -100px; opacity: 0.2; background: #10b981;"></div>

    <div class="container position-relative z-1">
        <span class="badge border border-light text-light rounded-pill px-3 py-2 mb-4 bg-transparent">
            Powerful Capabilities
        </span>
        <h1 class="display-4 fw-bold mb-4">Built for scale, <span style="color: #818cf8;">designed for speed.</span></h1>
        <p class="lead text-white-50 mx-auto mb-0" style="max-width: 700px;">
            Explore the tools that help freelancers and enterprises automate their financial operations without the headache.
        </p>
    </div>
</section>

<section class="feature-row bg-white">
    <div class="container">
        <div class="row align-items-center g-5">
            <div class="col-lg-6 order-lg-2">
                <div class="feature-icon-box bg-primary bg-opacity-10 text-primary">
                    <i class="fa-solid fa-wand-magic-sparkles"></i>
                </div>
                <h2 class="fw-bold mb-3 display-6">Intelligent Invoicing</h2>
                <p class="text-muted lead mb-4">
                    Create beautiful, branded invoices that look great on any device. Set up recurring profiles and let the system handle the rest.
                </p>
                <ul class="list-unstyled text-muted">
                    <li class="mb-3"><i class="fa-solid fa-circle-check text-primary me-2"></i> <strong>Automated Recurring Billing:</strong> Set it and forget it.</li>
                    <li class="mb-3"><i class="fa-solid fa-circle-check text-primary me-2"></i> <strong>Multi-Currency Support:</strong> Bill globally in USD, EUR, INR, etc.</li>
                    <li class="mb-3"><i class="fa-solid fa-circle-check text-primary me-2"></i> <strong>PDF Export & Email:</strong> Send directly from the dashboard.</li>
                </ul>
            </div>
            <div class="col-lg-6 order-lg-1">
                <div class="ui-card bg-light">
                    <div class="d-flex justify-content-between align-items-center mb-4 border-bottom pb-3">
                        <div class="fw-bold text-dark">INVOICE #2024-001</div>
                        <span class="ui-badge bg-warning bg-opacity-25 text-warning">Pending</span>
                    </div>
                    <div class="mb-4">
                        <div class="bg-white p-3 rounded mb-2 shadow-sm d-flex justify-content-between">
                            <span>Web Design Services</span>
                            <span class="fw-bold">$1,500.00</span>
                        </div>
                        <div class="bg-white p-3 rounded mb-2 shadow-sm d-flex justify-content-between">
                            <span>Hosting (Annual)</span>
                            <span class="fw-bold">$240.00</span>
                        </div>
                    </div>
                    <div class="d-flex justify-content-end">
                        <div class="text-end">
                            <small class="text-muted text-uppercase fw-bold">Total Due</small>
                            <h3 class="fw-bold text-dark m-0">$1,740.00</h3>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="feature-row" style="background-color: #f8fafc;">
    <div class="container">
        <div class="row align-items-center g-5">
            <div class="col-lg-6">
                <div class="feature-icon-box bg-success bg-opacity-10 text-success">
                    <i class="fa-solid fa-users-viewfinder"></i>
                </div>
                <h2 class="fw-bold mb-3 display-6">Dedicated Client Portal</h2>
                <p class="text-muted lead mb-4">
                    Give your clients a professional experience. They get their own login to view history, download receipts, and make payments.
                </p>
                <ul class="list-unstyled text-muted">
                    <li class="mb-3"><i class="fa-solid fa-circle-check text-success me-2"></i> <strong>Self-Service:</strong> Clients download their own PDFs.</li>
                    <li class="mb-3"><i class="fa-solid fa-circle-check text-success me-2"></i> <strong>Secure Payments:</strong> Integration with major gateways.</li>
                    <li class="mb-3"><i class="fa-solid fa-circle-check text-success me-2"></i> <strong>Ticket Support:</strong> Built-in help desk for queries.</li>
                </ul>
            </div>
            <div class="col-lg-6">
                <div class="ui-card">
                    <div class="d-flex gap-3 mb-4">
                        <div class="bg-light rounded-circle" style="width:50px; height:50px;"></div>
                        <div>
                            <h6 class="mb-0 fw-bold">Welcome back, Client!</h6>
                            <small class="text-muted">You have 2 unpaid invoices.</small>
                        </div>
                    </div>
                    <div class="row g-3">
                        <div class="col-6">
                            <div class="p-3 bg-primary bg-opacity-10 rounded text-center">
                                <i class="fa-regular fa-credit-card fa-2x text-primary mb-2"></i>
                                <div class="fw-bold small text-primary">Pay Now</div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="p-3 bg-light rounded text-center">
                                <i class="fa-solid fa-clock-rotate-left fa-2x text-muted mb-2"></i>
                                <div class="fw-bold small text-muted">History</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<section class="feature-row bg-white">
    <div class="container">
        <div class="text-center mb-5">
            <h6 class="text-uppercase fw-bold text-primary mb-2">Wait, there's more</h6>
            <h2 class="fw-bold">Enterprise-grade features for everyone</h2>
        </div>

        <div class="row g-4">
            <div class="col-md-4">
                <div class="grid-feature-card">
                    <i class="fa-solid fa-shield-cat text-danger fa-2x mb-3"></i>
                    <h5 class="fw-bold">Audit Logs</h5>
                    <p class="text-muted small m-0">Track every action taken in the system. See who logged in, who edited an invoice, and when.</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="grid-feature-card">
                    <i class="fa-solid fa-user-lock text-dark fa-2x mb-3"></i>
                    <h5 class="fw-bold">Role-Based Access</h5>
                    <p class="text-muted small m-0">Granular permissions for Superadmins, Admins, Staff, and Clients to keep data safe.</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="grid-feature-card">
                    <i class="fa-solid fa-chart-pie text-info fa-2x mb-3"></i>
                    <h5 class="fw-bold">Financial Analytics</h5>
                    <p class="text-muted small m-0">Visualize revenue growth, outstanding payments, and client acquisition rates instantly.</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="grid-feature-card">
                    <i class="fa-solid fa-envelope-open-text text-warning fa-2x mb-3"></i>
                    <h5 class="fw-bold">Email Notifications</h5>
                    <p class="text-muted small m-0">Automatic alerts for new invoices, payment confirmations, and overdue reminders.</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="grid-feature-card">
                    <i class="fa-solid fa-mobile-screen-button text-primary fa-2x mb-3"></i>
                    <h5 class="fw-bold">Mobile Responsive</h5>
                    <p class="text-muted small m-0">Manage your business from anywhere. The entire dashboard works perfectly on phones.</p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="grid-feature-card">
                    <i class="fa-solid fa-code text-secondary fa-2x mb-3"></i>
                    <h5 class="fw-bold">Developer Ready</h5>
                    <p class="text-muted small m-0">Built on a clean MVC architecture using PDO, making it easy to extend and customize.</p>
                </div>
            </div>
        </div>
    </div>
</section>
<div class="container pb-5">
    <div class="bg-dark rounded-4 p-5 text-center text-white position-relative overflow-hidden" style="background-color: var(--secondary-color) !important;">
        <div class="position-relative z-1">
             <h2 class="fw-bold text-white mb-3">Ready to modernize your workflow?</h2>
        <p class="text-white-50 mb-4 mx-auto" style="max-width: 600px;">
            Join the platform that grows with you. Secure, scalable, and simple.
        </p>
        <div class="d-flex justify-content-center gap-3">
            <a href="views/auth/register.php" class="btn btn-primary btn-lg rounded-pill px-5 fw-bold shadow-lg">Get Started Free</a>
            <a href="pricing.php" class="btn btn-outline-light btn-lg rounded-pill px-5 fw-bold">View Pricing</a>
        </div>
        </div>
        <div class="position-absolute top-0 end-0 p-5 opacity-10">
            <i class="fa-solid fa-building fa-10x text-white"></i>
        </div>
    </div>
</div>







<?php 
// 3. Include Footer
include 'views/layouts/lending-footer.php'; 
?>