<style>
    /* --- FOOTER SPECIFIC STYLES --- */
    .footer-section {
        background-color: var(--secondary-color); /* Deep Navy */
        color: #e2e8f0;
        padding-top: 5rem;
        padding-bottom: 2rem;
        border-top: 1px solid rgba(255, 255, 255, 0.05);
        font-size: 0.95rem;
        position: relative;
    }

    /* Brand & Description */
    .footer-brand {
        font-weight: 800;
        font-size: 1.5rem;
        color: white;
        text-decoration: none;
        letter-spacing: -0.5px;
        display: inline-flex;
        align-items: center;
        margin-bottom: 1.25rem;
    }
    .footer-desc {
        color: #94a3b8; /* Slate-400 */
        line-height: 1.7;
        margin-bottom: 2rem;
        font-size: 0.95rem;
    }

    /* Headings */
    .footer-heading {
        color: white;
        font-weight: 700;
        font-size: 1.05rem;
        margin-bottom: 1.5rem;
        letter-spacing: 0.5px;
    }

    /* Links List */
    .footer-links {
        list-style: none;
        padding: 0;
        margin: 0;
    }
    .footer-links li {
        margin-bottom: 0.75rem;
    }
    .footer-links a {
        color: #94a3b8;
        text-decoration: none;
        transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
        display: inline-block;
        font-size: 0.95rem;
    }
    .footer-links a:hover {
        color: var(--primary-color);
        padding-left: 5px; /* Subtle slide effect */
        text-shadow: 0 0 15px rgba(79, 70, 229, 0.4);
    }

    /* Social Buttons */
    .social-btn {
        width: 40px;
        height: 40px;
        border-radius: 10px;
        background: rgba(255, 255, 255, 0.05);
        display: flex;
        align-items: center;
        justify-content: center;
        color: #cbd5e1;
        transition: all 0.3s ease;
        border: 1px solid rgba(255, 255, 255, 0.05);
        text-decoration: none;
    }
    .social-btn:hover {
        background: var(--primary-color);
        color: white;
        transform: translateY(-3px);
        box-shadow: 0 5px 15px rgba(79, 70, 229, 0.3);
        border-color: var(--primary-color);
    }

    /* Contact Items */
    .contact-item {
        display: flex;
        align-items: flex-start;
        gap: 15px;
        margin-bottom: 1.25rem;
        color: #94a3b8;
    }
    .contact-icon {
        color: var(--primary-color);
        font-size: 1.1rem;
        margin-top: 2px;
        flex-shrink: 0;
    }
    .contact-text a {
        color: #94a3b8;
        text-decoration: none;
        transition: color 0.2s;
    }
    .contact-text a:hover {
        color: white;
    }

    /* Footer Bottom */
    .footer-bottom {
        margin-top: 4rem;
        padding-top: 2rem;
        border-top: 1px solid rgba(255, 255, 255, 0.08);
        font-size: 0.85rem;
        color: #64748b;
    }
    .footer-bottom-links a {
        color: #64748b;
        text-decoration: none;
        margin-left: 1.5rem;
        transition: color 0.2s;
    }
    .footer-bottom-links a:hover { color: white; }
</style>

<footer class="footer-section">
    <div class="container">
        <div class="row g-5">
            
            <div class="col-lg-4 col-md-12">
                <a href="<?= BASE_URL ?>index.php" class="footer-brand">
                    <i class="fa-solid fa-layer-group text-primary me-2"></i>
                    IMS<span class="text-primary">.</span>
                </a>
                <p class="footer-desc">
                    Simplifying business finances one invoice at a time. Manage clients, track payments, and grow your business with our secure enterprise platform.
                </p>
                <div class="d-flex gap-2">
                    <a href="#" class="social-btn" aria-label="Facebook"><i class="fa-brands fa-facebook-f"></i></a>
                    <a href="#" class="social-btn" aria-label="Twitter"><i class="fa-brands fa-twitter"></i></a>
                    <a href="#" class="social-btn" aria-label="LinkedIn"><i class="fa-brands fa-linkedin-in"></i></a>
                    <a href="#" class="social-btn" aria-label="GitHub"><i class="fa-brands fa-github"></i></a>
                </div>
            </div>

            <div class="col-lg-2 col-md-6 col-6">
                <h6 class="footer-heading">Product</h6>
                <ul class="footer-links">
                    <li><a href="<?= BASE_URL ?>features.php">Features</a></li>
                    <li><a href="<?= BASE_URL ?>prices.php">Pricing</a></li>
                    <li><a href="#">Enterprise</a></li>
                    <li><a href="#">Security</a></li>
                    <li><a href="#">API Docs</a></li>
                </ul>
            </div>

            <div class="col-lg-2 col-md-6 col-6">
                <h6 class="footer-heading">Company</h6>
                <ul class="footer-links">
                    <li><a href="#">About Us</a></li>
                    <li><a href="#">Careers</a> <span class="badge bg-primary bg-opacity-25 text-primary ms-1" style="font-size: 0.6rem;">Hiring</span></li>
                    <li><a href="#">Blog</a></li>
                    <li><a href="#">Press Kit</a></li>
                    <li><a href="<?= BASE_URL ?>contact.php">Contact</a></li>
                </ul>
            </div>

            <div class="col-lg-4 col-md-12">
                <h6 class="footer-heading">Get in Touch</h6>
                
                <div class="contact-item">
                    <div class="contact-icon"><i class="fa-solid fa-location-dot"></i></div>
                    <div class="contact-text">
                        123 Innovation Blvd, Suite 400<br>
                        San Francisco, CA 94103
                    </div>
                </div>

                <div class="contact-item">
                    <div class="contact-icon"><i class="fa-solid fa-envelope"></i></div>
                    <div class="contact-text">
                        <a href="mailto:support@invoicesys.com">support@invoicesys.com</a><br>
                        <a href="mailto:sales@invoicesys.com">sales@invoicesys.com</a>
                    </div>
                </div>

                <div class="contact-item">
                    <div class="contact-icon"><i class="fa-solid fa-phone"></i></div>
                    <div class="contact-text">
                        <a href="tel:+15551234567">+1 (555) 123-4567</a>
                    </div>
                </div>
            </div>

        </div>

        <div class="footer-bottom d-flex flex-column flex-md-row justify-content-between align-items-center">
            <div class="mb-2 mb-md-0">
                &copy; <?= date("Y"); ?> Invoice Management System (IMS). All rights reserved.
            </div>
            <div class="footer-bottom-links d-flex">
                <a href="#">Privacy Policy</a>
                <a href="#">Terms of Service</a>
                <a href="#">Cookies</a>
            </div>
        </div>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>