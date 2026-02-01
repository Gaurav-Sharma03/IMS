<?php
// 1. Load Config & Session
require_once __DIR__ . '/config/constants.php';
require_once __DIR__ . '/config/session.php';
require_once __DIR__ . '/config/database.php';

$successMsg = '';
$errorMsg = '';

// 2. Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_message'])) {
    try {
        $db = (new Database())->connect();
        
        // Sanitize
        $name = htmlspecialchars(trim($_POST['name']));
        $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
        $subject = htmlspecialchars(trim($_POST['subject']));
        $message = htmlspecialchars(trim($_POST['message']));

        if (!empty($name) && !empty($email) && !empty($message)) {
            $stmt = $db->prepare("INSERT INTO contacts (name, email, subject, message) VALUES (?, ?, ?, ?)");
            if ($stmt->execute([$name, $email, $subject, $message])) {
                $successMsg = "Message sent successfully! We'll get back to you shortly.";
            } else {
                $errorMsg = "Something went wrong. Please try again.";
            }
        } else {
            $errorMsg = "Please fill in all required fields.";
        }
    } catch (Exception $e) {
        $errorMsg = "System Error: " . $e->getMessage();
    }
}

// 3. Fetch Testimonials
$testimonials = [];
try {
    $db = (new Database())->connect();
    $tStmt = $db->query("SELECT * FROM testimonials WHERE is_active = 1 ORDER BY created_at DESC LIMIT 3");
    $testimonials = $tStmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) { /* Silent fail for testimonials */ }

// 4. Include Header
include 'views/layouts/lending-header.php';
?>

<style>
    /* Contact Page Specific Styles */
    .contact-hero {
        background-color: var(--secondary-color);
        padding: 6rem 0 10rem 0;
        color: white;
        position: relative;
        overflow: hidden;
    }
    
    .contact-card {
        background: #fff;
        border-radius: 20px;
        box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.15);
        overflow: hidden;
        margin-top: -6rem;
        position: relative;
        z-index: 10;
        border: 1px solid rgba(0,0,0,0.05);
    }

    .form-control {
        padding: 0.8rem 1rem;
        border-radius: 10px;
        border: 1px solid #e2e8f0;
        background-color: #f8fafc;
    }
    .form-control:focus {
        background-color: #fff;
        border-color: var(--primary-color);
        box-shadow: 0 0 0 4px rgba(79, 70, 229, 0.1);
    }

    /* Info Box */
    .contact-info-box {
        background: linear-gradient(135deg, var(--secondary-color) 0%, #1e1b4b 100%);
        color: white;
        padding: 3rem;
        height: 100%;
        position: relative;
        overflow: hidden;
    }
    
    .info-item {
        display: flex;
        align-items: flex-start;
        gap: 20px;
        margin-bottom: 2rem;
    }
    .info-icon {
        width: 45px; height: 45px;
        background: rgba(255,255,255,0.1);
        border-radius: 10px;
        display: flex; align-items: center; justify-content: center;
        flex-shrink: 0;
        color: var(--accent-color);
    }

    /* Testimonial Cards */
    .testimonial-card {
        background: #fff;
        border: 1px solid #f1f5f9;
        padding: 2rem;
        border-radius: 16px;
        height: 100%;
        transition: transform 0.3s ease;
    }
    .testimonial-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 30px rgba(0,0,0,0.05);
        border-color: var(--primary-color);
    }
</style>

<section class="contact-hero text-center">
    <div class="blob" style="top: -100px; right: -100px; opacity: 0.3;"></div>
    <div class="container position-relative z-1">
        <h1 class="display-4 fw-bold mb-3">Get in Touch</h1>
        <p class="lead text-white-50 mx-auto" style="max-width: 600px;">
            Have questions about our pricing, features, or enterprise solutions? We're here to help.
        </p>
    </div>
</section>

<section class="pb-5">
    <div class="container">
        <div class="contact-card">
            <div class="row g-0">
                
                <div class="col-lg-7 p-5 bg-white">
                    <h3 class="fw-bold text-dark mb-2">Send us a message</h3>
                    <p class="text-muted mb-4">Our team typically responds within 2 hours.</p>

                    <?php if($successMsg): ?>
                        <div class="alert alert-success border-0 shadow-sm rounded-3 mb-4">
                            <i class="fa-solid fa-check-circle me-2"></i> <?= $successMsg ?>
                        </div>
                    <?php endif; ?>

                    <?php if($errorMsg): ?>
                        <div class="alert alert-danger border-0 shadow-sm rounded-3 mb-4">
                            <i class="fa-solid fa-triangle-exclamation me-2"></i> <?= $errorMsg ?>
                        </div>
                    <?php endif; ?>

                    <form method="POST">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label small fw-bold text-muted">Full Name</label>
                                <input type="text" name="name" class="form-control" placeholder="John Doe" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-bold text-muted">Email Address</label>
                                <input type="email" name="email" class="form-control" placeholder="john@company.com" required>
                            </div>
                            <div class="col-12">
                                <label class="form-label small fw-bold text-muted">Subject</label>
                                <select name="subject" class="form-select form-control">
                                    <option value="General Inquiry">General Inquiry</option>
                                    <option value="Sales & Pricing">Sales & Pricing</option>
                                    <option value="Technical Support">Technical Support</option>
                                    <option value="Enterprise Solution">Enterprise Solution</option>
                                </select>
                            </div>
                            <div class="col-12">
                                <label class="form-label small fw-bold text-muted">Message</label>
                                <textarea name="message" class="form-control" rows="5" placeholder="How can we help you?" required></textarea>
                            </div>
                            <div class="col-12 pt-2">
                                <button type="submit" name="send_message" class="btn btn-primary w-100 py-3 fw-bold rounded-3 shadow-lg">
                                    Send Message <i class="fa-solid fa-paper-plane ms-2"></i>
                                </button>
                            </div>
                        </div>
                    </form>
                </div>

                <div class="col-lg-5">
                    <div class="contact-info-box">
                        <div class="blob" style="bottom: -50px; right: -50px; width: 150px; height: 150px;"></div>
                        
                        <h4 class="fw-bold mb-5">Contact Information</h4>
                        
                        <div class="info-item">
                            <div class="info-icon"><i class="fa-solid fa-location-dot"></i></div>
                            <div>
                                <h6 class="fw-bold mb-1">Our Headquarters</h6>
                                <p class="text-white-50 small mb-0">
                                    123 Innovation Blvd, Suite 400<br>
                                    San Francisco, CA 94103, USA
                                </p>
                            </div>
                        </div>

                        <div class="info-item">
                            <div class="info-icon"><i class="fa-solid fa-envelope"></i></div>
                            <div>
                                <h6 class="fw-bold mb-1">Email Us</h6>
                                <p class="text-white-50 small mb-0">
                                    support@invoicesys.com<br>
                                    sales@invoicesys.com
                                </p>
                            </div>
                        </div>

                        <div class="info-item">
                            <div class="info-icon"><i class="fa-solid fa-phone"></i></div>
                            <div>
                                <h6 class="fw-bold mb-1">Call Us</h6>
                                <p class="text-white-50 small mb-0">
                                    Mon-Fri from 8am to 5pm<br>
                                    +1 (555) 123-4567
                                </p>
                            </div>
                        </div>

                        <div class="mt-auto pt-5">
                            <h6 class="fw-bold mb-3">Follow Us</h6>
                            <div class="d-flex gap-3">
                                <a href="#" class="text-white opacity-75 hover-opacity-100"><i class="fa-brands fa-twitter fa-lg"></i></a>
                                <a href="#" class="text-white opacity-75 hover-opacity-100"><i class="fa-brands fa-linkedin fa-lg"></i></a>
                                <a href="#" class="text-white opacity-75 hover-opacity-100"><i class="fa-brands fa-github fa-lg"></i></a>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
</section>

<section class="py-5 bg-light">
    <div class="container py-4">
        <div class="text-center mb-5">
            <h6 class="text-primary fw-bold text-uppercase">Testimonials</h6>
            <h2 class="fw-bold">Trusted by Industry Leaders</h2>
        </div>

        <div class="row g-4">
            <?php foreach($testimonials as $t): ?>
            <div class="col-md-4">
                <div class="testimonial-card">
                    <div class="text-warning mb-3">
                        <?php for($i=0; $i<$t['rating']; $i++) echo '<i class="fa-solid fa-star"></i> '; ?>
                    </div>
                    <p class="text-muted mb-4">"<?= htmlspecialchars($t['content']) ?>"</p>
                    <div class="d-flex align-items-center gap-3">
                        <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center fw-bold" style="width: 40px; height: 40px;">
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
        </div>
    </div>
</section>

<?php 
// Include Footer
include 'views/layouts/lending-footer.php'; 
?>