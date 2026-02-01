<?php
// 1. Load Config & Session
require_once __DIR__ . '/config/constants.php';
require_once __DIR__ . '/config/session.php';
require_once __DIR__ . '/config/database.php';

$success = false;
$error = '';

// 2. Database Connection
$db = (new Database())->connect();

// 3. Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_feedback'])) {
    try {
        // Sanitize Input
        $name = htmlspecialchars(trim($_POST['name']));
        $role = htmlspecialchars(trim($_POST['role']));
        $company = htmlspecialchars(trim($_POST['company']));
        $rating = (int) $_POST['rating'];
        $message = htmlspecialchars(trim($_POST['message']));

        // Validation
        if ($rating < 1 || $rating > 5) {
            $error = "Please select a valid star rating.";
        } elseif (empty($name) || empty($message)) {
            $error = "Name and Message are required.";
        } else {
            // Insert into Database
            $stmt = $db->prepare("INSERT INTO testimonials (client_name, client_role, company, content, rating, is_active, created_at) VALUES (?, ?, ?, ?, ?, 0, NOW())");
            
            if ($stmt->execute([$name, $role, $company, $message, $rating])) {
                $success = true;
            } else {
                $error = "Database error. Please try again.";
            }
        }
    } catch (Exception $e) {
        $error = "System Error: " . $e->getMessage();
    }
}

// 4. Fetch Approved Testimonials for Display
$testimonials = [];
try {
    $tStmt = $db->query("SELECT * FROM testimonials WHERE is_active = 1 ORDER BY created_at DESC");
    $testimonials = $tStmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    // Silent fail if table doesn't exist yet
}

// 5. Include Header
include 'views/layouts/lending-header.php';
?>

<style>
    /* --- HERO & GENERAL STYLES --- */
    .feedback-hero {
        background-color: var(--secondary-color); /* Deep Navy */
        padding: 8rem 0 12rem 0; /* Extra bottom padding for overlap */
        color: white;
        position: relative;
        overflow: hidden;
        text-align: center;
    }

    /* Floating Card Design */
    .feedback-card {
        background: #ffffff;
        border-radius: 24px;
        box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.15);
        padding: 3.5rem;
        margin-top: -8rem; /* Pulls card up into Hero */
        position: relative;
        z-index: 10;
        border: 1px solid rgba(0,0,0,0.05);
        max-width: 750px;
        margin-left: auto;
        margin-right: auto;
    }

    /* Star Rating Widget (CSS Only) */
    .rate-group {
        display: flex;
        flex-direction: row-reverse;
        justify-content: center;
        gap: 8px;
        margin-bottom: 2rem;
    }
    .rate-group input { display: none; }
    
    .rate-group label {
        font-size: 2.5rem;
        color: #e2e8f0; /* Gray-200 */
        cursor: pointer;
        transition: transform 0.2s, color 0.2s;
    }
    
    .rate-group label:hover,
    .rate-group label:hover ~ label,
    .rate-group input:checked ~ label {
        color: #f59e0b; /* Amber-500 */
        transform: scale(1.1);
    }

    /* Form Inputs */
    .form-label {
        font-weight: 700;
        font-size: 0.75rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        color: #64748b;
        margin-bottom: 0.5rem;
    }
    
    .form-control {
        background-color: #f8fafc;
        border: 1px solid #e2e8f0;
        padding: 1rem;
        border-radius: 12px;
        font-size: 0.95rem;
        transition: all 0.3s;
    }
    
    .form-control:focus {
        background-color: #fff;
        border-color: var(--primary-color);
        box-shadow: 0 0 0 4px rgba(79, 70, 229, 0.1);
    }

    /* Success State */
    .success-box { text-align: center; padding: 3rem 1rem; }
    .success-icon {
        width: 80px; height: 80px;
        background: #dcfce7; color: #16a34a;
        border-radius: 50%;
        display: flex; align-items: center; justify-content: center;
        font-size: 2rem;
        margin: 0 auto 1.5rem auto;
    }

    /* --- REVIEW LIST STYLES --- */
    .review-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
        gap: 1.5rem;
        margin-top: 4rem;
    }

    .review-card {
        background: #fff;
        border: 1px solid #f1f5f9;
        border-radius: 16px;
        padding: 2rem;
        transition: transform 0.3s;
        height: 100%;
        display: flex;
        flex-direction: column;
    }
    .review-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 30px rgba(0,0,0,0.05);
        border-color: var(--primary-color);
    }
    
    .review-avatar {
        width: 45px; height: 45px;
        background: var(--primary-color);
        color: white;
        border-radius: 50%;
        display: flex; align-items: center; justify-content: center;
        font-weight: 700;
        font-size: 1.1rem;
    }
</style>

<section class="feedback-hero">
    <div class="blob" style="top: -150px; right: -100px; opacity: 0.15;"></div>
    <div class="blob" style="bottom: -150px; left: -100px; opacity: 0.15; background: #10b981;"></div>

    <div class="container position-relative z-1">
        <span class="badge border border-light text-light rounded-pill px-3 py-2 mb-4 bg-transparent opacity-75">
            Client Stories
        </span>
        <h1 class="display-4 fw-bold mb-3">Share your experience</h1>
        <p class="lead text-white-50 mx-auto" style="max-width: 600px;">
            Your feedback fuels our innovation. Tell us what you love and how we can improve the IMS platform.
        </p>
    </div>
</section>

<section class="pb-5">
    <div class="container">
        
        <div class="feedback-card animate__animated animate__fadeInUp">
            
            <?php if ($success): ?>
                <div class="success-box">
                    <div class="success-icon animate__animated animate__bounceIn">
                        <i class="fa-solid fa-check"></i>
                    </div>
                    <h3 class="fw-bold text-dark mb-3">Feedback Submitted!</h3>
                    <p class="text-muted mb-4 mx-auto" style="max-width: 400px;">
                        Thank you for taking the time to review us. Your testimonial helps us build a better product for everyone.
                    </p>
                    <a href="index.php" class="btn btn-primary rounded-pill px-5 py-3 fw-bold shadow-lg">Return to Home</a>
                </div>
            <?php else: ?>
                
                <?php if ($error): ?>
                    <div class="alert alert-danger border-0 shadow-sm rounded-3 mb-4 d-flex align-items-center">
                        <i class="fa-solid fa-circle-exclamation me-3 fs-4"></i>
                        <div><?= $error ?></div>
                    </div>
                <?php endif; ?>

                <form method="POST" action="">
                    <div class="text-center mb-4">
                        <label class="form-label d-block mb-3">How would you rate IMS?</label>
                        <div class="rate-group">
                            <input type="radio" id="star5" name="rating" value="5" required />
                            <label for="star5" title="Excellent"><i class="fa-solid fa-star"></i></label>
                            
                            <input type="radio" id="star4" name="rating" value="4" />
                            <label for="star4" title="Good"><i class="fa-solid fa-star"></i></label>
                            
                            <input type="radio" id="star3" name="rating" value="3" />
                            <label for="star3" title="Average"><i class="fa-solid fa-star"></i></label>
                            
                            <input type="radio" id="star2" name="rating" value="2" />
                            <label for="star2" title="Poor"><i class="fa-solid fa-star"></i></label>
                            
                            <input type="radio" id="star1" name="rating" value="1" />
                            <label for="star1" title="Very Poor"><i class="fa-solid fa-star"></i></label>
                        </div>
                    </div>

                    <div class="row g-4">
                        <div class="col-md-6">
                            <label class="form-label">Your Name <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control" placeholder="Jane Doe" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Company Name</label>
                            <input type="text" name="company" class="form-control" placeholder="Acme Corp">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Job Title</label>
                            <input type="text" name="role" class="form-control" placeholder="e.g. CEO, Freelancer">
                        </div>
                        <div class="col-12">
                            <label class="form-label">Your Review <span class="text-danger">*</span></label>
                            <textarea name="message" class="form-control" rows="5" placeholder="What features do you use the most?" required></textarea>
                        </div>
                        <div class="col-12 pt-3">
                            <button type="submit" name="submit_feedback" class="btn btn-primary w-100 py-3 rounded-pill fw-bold shadow-lg text-uppercase ls-1">
                                Submit Feedback <i class="fa-solid fa-paper-plane ms-2"></i>
                            </button>
                        </div>
                    </div>
                </form>
            <?php endif; ?>
        </div>

    </div>
</section>

<section class="py-5 bg-light border-top">
    <div class="container py-4">
        <div class="text-center mb-5">
            <h6 class="text-uppercase fw-bold text-primary ls-1">Community Love</h6>
            <h2 class="fw-bold display-6 text-dark">What others are saying</h2>
        </div>

        <?php if (empty($testimonials)): ?>
            <div class="text-center py-5">
                <p class="text-muted">No reviews yet. Be the first to share your story!</p>
            </div>
        <?php else: ?>
            <div class="review-grid">
                <?php foreach ($testimonials as $t): ?>
                    <div class="review-card">
                        <div class="d-flex justify-content-between align-items-start mb-3">
                            <div class="review-avatar">
                                <?= strtoupper(substr($t['client_name'], 0, 1)) ?>
                            </div>
                            <div class="text-warning small">
                                <?php for($i=0; $i<$t['rating']; $i++) echo '<i class="fa-solid fa-star"></i>'; ?>
                            </div>
                        </div>
                        
                        <p class="text-muted flex-grow-1 mb-4" style="font-size: 0.95rem; line-height: 1.6;">
                            "<?= htmlspecialchars($t['content']) ?>"
                        </p>
                        
                        <div class="border-top pt-3 mt-auto">
                            <h6 class="fw-bold text-dark mb-0 small"><?= htmlspecialchars($t['client_name']) ?></h6>
                            <span class="text-muted smaller" style="font-size: 0.8rem;">
                                <?= htmlspecialchars($t['client_role']) ?>
                                <?= !empty($t['company']) ? ' at ' . htmlspecialchars($t['company']) : '' ?>
                            </span>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

    </div>
</section>

<section class="py-5 text-center bg-white">
    <div class="container">
        <p class="text-muted mb-3 fw-bold text-uppercase small ls-1">Join our community</p>
        <h3 class="fw-bold mb-4">Start your journey with IMS today</h3>
        <a href="views/auth/register.php" class="text-primary fw-bold text-decoration-none border-bottom border-primary border-2 pb-1">
            Create free account <i class="fa-solid fa-arrow-right ms-1"></i>
        </a>
    </div>
</section>

<?php 
// 6. Include Footer
include 'views/layouts/lending-footer.php'; 
?>