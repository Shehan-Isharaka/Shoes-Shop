<footer class="footer py-5">
    <div class="container">

        <div class="row align-items-start gy-4">

<style>
/* ================= FOOTER ================= */

.footer {
    background: linear-gradient(
        135deg,
        #0f172a,   /* deep navy */
        #1e293b    /* slate */
    );
    border-top: 1px solid rgba(255,255,255,0.08);
    color: #e5e7eb;
}

/* LOGO */
.footer-logo {
    max-height: 60px;
    object-fit: contain;
}

/* CENTER LINKS */
.footer-center-links {
    list-style: none;
    padding: 0;
    margin: 0;
    display: flex;
    justify-content: center;
    gap: 24px;
    flex-wrap: wrap;
}

.footer-center-links li a {
    text-decoration: none;
    color: #cbd5f5; /* soft indigo */
    font-size: 14px;
    font-weight: 500;
    position: relative;
    transition: all .25s ease;
}

/* underline animation */
.footer-center-links li a::after {
    content: "";
    position: absolute;
    left: 0;
    bottom: -4px;
    width: 0;
    height: 2px;
    background: linear-gradient(
        90deg,
        #4f46e5,   /* indigo */
        #ec4899    /* pink */
    );
    transition: width .3s ease;
}

.footer-center-links li a:hover {
    color: #ffffff;
}

.footer-center-links li a:hover::after {
    width: 100%;
}

/* ICONS */
.footer i {
    color: #94a3b8; /* muted slate */
    font-size: 16px;
    transition: all .25s ease;
}

.footer i:hover {
    color: #f97316; /* orange accent */
    transform: translateY(-2px);
}

/* SMALL TEXT */
.footer small,
.footer p {
    color: #9ca3af;
}

/* MOBILE */
@media (max-width: 768px) {
    .footer-center-links {
        gap: 16px;
    }
}

</style>    
            <!-- ================= LEFT : BRAND ================= -->
            <div class="col-md-4 text-center text-md-start">
                <?php if (setting('footer_logo')): ?>
                    <img src="../admin/uploads/branding/<?= setting('footer_logo') ?>"
                         alt="<?= htmlspecialchars(setting('site_name','Pino Shoes')) ?>"
                         class="footer-logo mb-3">
                <?php endif; ?>

                <p class="text-muted small mb-0">
                    <?= setting('site_tagline', 'Premium shoes for every lifestyle') ?>
                </p>
            </div>

            <!-- ================= CENTER : LINKS ================= -->
            <div class="col-md-4 text-center">
                <h6 class="fw-semibold mb-3">Quick Links</h6>

                <ul class="footer-center-links">
                    <li><a href="page.php?slug=about-us">About Us</a></li>
                    <li><a href="page.php?slug=contact-us">Contact</a></li>
                    <li><a href="page.php?slug=terms">Terms</a></li>
                    <li><a href="page.php?slug=faq">FAQ</a></li>
                </ul>
            </div>

            <!-- ================= RIGHT : CONTACT ================= -->
            <div class="col-md-4 text-center text-md-end">
                <h6 class="fw-semibold mb-3">Contact</h6>

                <?php if (setting('footer_address')): ?>
                    <p class="small mb-1">
                        <i class="bi bi-geo-alt me-1"></i>
                        <?= setting('footer_address') ?>
                    </p>
                <?php endif; ?>

                <?php if (setting('footer_phone')): ?>
                    <p class="small mb-1">
                        <i class="bi bi-telephone me-1"></i>
                        <?= setting('footer_phone') ?>
                    </p>
                <?php endif; ?>

                <?php if (setting('footer_email')): ?>
                    <p class="small mb-0">
                        <i class="bi bi-envelope me-1"></i>
                        <?= setting('footer_email') ?>
                    </p>
                <?php endif; ?>
            </div>

        </div>

        <hr class="my-4">

        <!-- ================= COPYRIGHT ================= -->
        <div class="text-center small text-muted">
            © <?= date('Y') ?> <?= setting('site_name','Pino Shoes') ?>. All Rights Reserved.
        </div>

    </div>
</footer>
