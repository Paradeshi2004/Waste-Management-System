</main>
<footer class="bg-dark text-light py-4 mt-5">
    <div class="container">
        <div class="row">
            <div class="col-md-4">
                <h6 class="fw-bold"><i class="bi bi-recycle me-1"></i><?= APP_NAME ?></h6>
                <p class="small text-muted">Connecting communities with municipal authorities for a cleaner, healthier environment.</p>
            </div>
            <div class="col-md-4">
                <h6 class="fw-bold">Quick Links</h6>
                <ul class="list-unstyled small">
                    <li><a href="<?= APP_URL ?>" class="text-muted text-decoration-none">Home</a></li>
                    <li><a href="<?= APP_URL ?>/pages/submit.php" class="text-muted text-decoration-none">Report Issue</a></li>
                    <li><a href="<?= APP_URL ?>/pages/tips.php" class="text-muted text-decoration-none">Recycling Tips</a></li>
                </ul>
            </div>
            <div class="col-md-4">
                <h6 class="fw-bold">Open Source</h6>
                <p class="small text-muted">This project is open-source. Contributions welcome!</p>
                <a href="https://github.com/your-repo/Waste-Management-System" class="btn btn-outline-light btn-sm" target="_blank">
                    <i class="bi bi-github me-1"></i>GitHub
                </a>
            </div>
        </div>
        <hr class="border-secondary">
        <p class="text-center text-muted small mb-0">&copy; <?= date('Y') ?> <?= APP_NAME ?>. All rights reserved.</p>
    </div>
</footer>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="<?= APP_URL ?>/js/main.js"></script>
</body>
</html>
