<?php
// 1. Load Config & Session
require_once __DIR__ . '/config/constants.php';
require_once __DIR__ . '/config/session.php';
require_once __DIR__ . '/config/database.php'; // Ensure DB connection is available

// 2. Fetch Plans from Database
try {
    $database = new Database();
    $db = $database->connect();
    
    // Fetch active plans, ordered by price
    $stmt = $db->prepare("SELECT * FROM plans WHERE status = 'active' ORDER BY price ASC");
    $stmt->execute();
    $plans = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (Exception $e) {
    // Fallback if DB fails (prevents page crash)
    $plans = []; 
    // error_log($e->getMessage()); // Optional: Log error
}

include 'views/layouts/lending-header.php'; 
?>

<style>
    /* --- PRICING SPECIFIC STYLES --- */
    .pricing-hero {
        background-color: var(--secondary-color);
        padding: 8rem 0 10rem 0; /* Extra padding bottom for overlap */
        color: white;
        position: relative;
        overflow: hidden;
        clip-path: polygon(0 0, 100% 0, 100% 85%, 0 100%);
    }

    .pricing-card {
        background: #fff;
        border-radius: 1.5rem;
        padding: 2.5rem;
        border: 1px solid #bebebe;
        box-shadow: 0 10px 30px -5px rgba(0,0,0,0.05);
        transition: all 0.3s cubic-bezier(0.25, 0.8, 0.25, 1);
        position: relative;
        height: 100%;
        display: flex;
        flex-direction: column;
    }

    .pricing-card:hover {
        transform: translateY(-10px);
        box-shadow: 0 20px 40px -10px rgba(0,0,0,0.1);
        border-color: var(--primary-color);
    }

    /* Recommended Card Styling */
    .pricing-card.popular {
        border: 2px solid var(--primary-color);
        box-shadow: 0 20px 50px -10px rgba(79, 70, 229, 0.2);
        z-index: 10;
        transform: scale(1.05);
    }
    .pricing-card.popular:hover {
        transform: scale(1.05) translateY(-5px);
    }

    .badge-popular {
        position: absolute;
        top: -15px;
        left: 50%;
        transform: translateX(-50%);
        background: var(--primary-color);
        color: white;
        padding: 0.5rem 1.5rem;
        border-radius: 50px;
        font-weight: 700;
        font-size: 0.8rem;
        text-transform: uppercase;
        letter-spacing: 1px;
        box-shadow: 0 5px 15px rgba(79, 70, 229, 0.3);
    }

    .price-tag {
        font-size: 3rem;
        font-weight: 800;
        color: var(--secondary-color);
        line-height: 1;
        margin: 1.5rem 0;
    }
    .price-period {
        font-size: 1rem;
        color: #64748b; /* text-muted */
        font-weight: 500;
    }

    .feature-list {
        list-style: none;
        padding: 0;
        margin: 2rem 0;
        flex-grow: 1; /* Pushes button to bottom */
    }
    .feature-list li {
        margin-bottom: 1rem;
        color: #1e293b; /* text-dark */
        display: flex;
        align-items: center;
        gap: 10px;
        font-size: 0.95rem;
    }
    
    /* FAQ Section */
    .faq-section { padding: 6rem 0; background: #fff; }
    .accordion-button:not(.collapsed) {
        background-color: rgba(79, 70, 229, 0.05);
        color: var(--primary-color);
        box-shadow: none;
    }
    .accordion-button:focus { box-shadow: none; border-color: rgba(0,0,0,0.1); }
</style>

<section class="pricing-hero text-center">
    <div class="blob" style="top: -50px; right: -50px; opacity: 0.3;"></div>
    <div class="container position-relative z-1">
        <h1 class="display-4 fw-bold mb-3">Transparent, Simple Pricing</h1>
        <p class="lead text-white-50 mx-auto" style="max-width: 600px;">
            No hidden fees. No credit card required to start. Cancel anytime.
        </p>
        
        <div class="d-inline-flex align-items-center bg-white bg-opacity-10 rounded-pill p-1 mt-4 border border-light border-opacity-10">
            <button class="btn btn-sm btn-light rounded-pill px-4 fw-bold text-primary">Monthly</button>
            <button class="btn btn-sm text-white px-4 fw-bold">Yearly <span class="badge bg-success ms-1 text-white" style="font-size: 0.6rem;">SAVE 20%</span></button>
        </div>
    </div>
</section>

<section class="pb-5" style="margin-top: -6rem; position: relative; z-index: 10;">
    <div class="container">
        <div class="row g-4 align-items-center justify-content-center">
            
            <?php if(empty($plans)): ?>
                <div class="col-12 text-center py-5">
                    <div class="bg-white p-5 rounded-4 shadow-sm mx-auto" style="max-width: 500px;">
                        <i class="fa-solid fa-server fa-3x text-muted mb-3 opacity-50"></i>
                        <h4 class="text-dark">No Plans Available</h4>
                        <p class="text-muted">Please check back later or contact support.</p>
                    </div>
                </div>
            <?php else: ?>
                <?php foreach($plans as $plan): 
                    // Determine if this is the "Popular" plan
                    $isPopular = (isset($plan['is_recommended']) && $plan['is_recommended'] == 1);
                    
                    // Parse features list
                    $featuresList = !empty($plan['features']) 
                                    ? explode(',', $plan['features']) 
                                    : ['Standard Access', 'Basic Support']; 
                ?>
                <div class="col-lg-4 col-md-6">
                    <div class="pricing-card <?= $isPopular ? 'popular' : '' ?>">
                        
                        <?php if($isPopular): ?>
                            <div class="badge-popular">Most Popular</div>
                        <?php endif; ?>

                        <h5 class="fw-bold text-uppercase text-muted small mb-2">
                            <?= htmlspecialchars($plan['name']) ?>
                        </h5>
                        
                        <div class="price-tag">
                            $<?= number_format($plan['price'], 0) ?>
                            <span class="price-period">/mo</span>
                        </div>
                        
                        <p class="text-muted small mb-4">
                            <?= htmlspecialchars($plan['description'] ?? 'Great for growing businesses.') ?>
                        </p>
                        
                        <hr class="border-light">

                        <ul class="feature-list">
                            <?php foreach($featuresList as $feat): ?>
                                <li>
                                    <i class="fa-solid fa-circle-check text-success"></i> 
                                    <?= htmlspecialchars(trim($feat)) ?>
                                </li>
                            <?php endforeach; ?>
                        </ul>

                        <div class="mt-auto">
                            <a href="views/auth/register.php?plan_id=<?= $plan['plan_id'] ?>" 
                               class="btn w-100 btn-lg rounded-pill fw-bold <?= $isPopular ? 'btn-primary shadow-lg' : 'btn-outline-dark' ?>">
                                Choose <?= htmlspecialchars($plan['name']) ?>
                            </a>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>

        </div>
    </div>
</section>

<section class="faq-section">
    <div class="container">
        <div class="text-center mb-5">
            <h6 class="text-primary fw-bold text-uppercase ls-1">Got Questions?</h6>
            <h2 class="fw-bold">Frequently Asked Questions</h2>
        </div>

        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="accordion accordion-flush" id="faqAccordion">
                    
                    <div class="accordion-item border mb-3 rounded-3 overflow-hidden">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed fw-bold py-3 px-4" type="button" data-bs-toggle="collapse" data-bs-target="#faq1">
                                Can I change plans later?
                            </button>
                        </h2>
                        <div id="faq1" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                            <div class="accordion-body text-muted px-4 pb-4">
                                Absolutely! You can upgrade or downgrade your plan at any time directly from your dashboard. The changes will take effect immediately.
                            </div>
                        </div>
                    </div>

                    <div class="accordion-item border mb-3 rounded-3 overflow-hidden">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed fw-bold py-3 px-4" type="button" data-bs-toggle="collapse" data-bs-target="#faq2">
                                Is there a free trial?
                            </button>
                        </h2>
                        <div id="faq2" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                            <div class="accordion-body text-muted px-4 pb-4">
                                Yes, we offer a 14-day free trial on the Professional plan so you can test drive all the features before committing. No credit card required.
                            </div>
                        </div>
                    </div>

                    <div class="accordion-item border mb-3 rounded-3 overflow-hidden">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed fw-bold py-3 px-4" type="button" data-bs-toggle="collapse" data-bs-target="#faq3">
                                What payment methods do you accept?
                            </button>
                        </h2>
                        <div id="faq3" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                            <div class="accordion-body text-muted px-4 pb-4">
                                We accept all major credit cards (Visa, Mastercard, Amex) as well as PayPal. Enterprise clients can request invoice-based billing.
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>
</section>

<div class="container pb-5">
    <div class="bg-dark rounded-4 p-5 text-center text-white position-relative overflow-hidden" style="background-color: var(--secondary-color) !important;">
        <div class="position-relative z-1">
            <h3 class="fw-bold mb-3">Need a custom solution?</h3>
            <p class="text-white-50 mb-4">For large organizations requiring SSO, Audit Logs, and dedicated support.</p>
            <a href="contact.php" class="btn btn-light rounded-pill px-4 fw-bold">Contact Sales</a>
        </div>
        <div class="position-absolute top-0 end-0 p-5 opacity-10">
            <i class="fa-solid fa-building fa-10x text-white"></i>
        </div>
    </div>
</div>

<?php 
// 4. Include Footer
include 'views/layouts/lending-footer.php'; 
?>