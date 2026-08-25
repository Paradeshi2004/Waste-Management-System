<?php
require_once __DIR__ . '/../includes/config.php';

$db = getDB();
$tips = $db->query("SELECT * FROM recycling_tips WHERE published = 1 ORDER BY created_at DESC")->fetchAll();

$categories = array_unique(array_column($tips, 'category'));

include __DIR__ . '/../includes/header.php';
?>

<h2 class="fw-bold mb-2"><i class="bi bi-lightbulb text-warning me-2"></i>Recycling Tips & Education</h2>
<p class="text-muted mb-4">Practical advice to help you reduce waste and recycle smarter.</p>

<!-- Category filter pills -->
<div class="mb-4 d-flex flex-wrap gap-2">
    <button class="btn btn-success btn-sm filter-btn active" data-cat="all">All</button>
    <?php foreach ($categories as $cat): ?>
        <button class="btn btn-outline-success btn-sm filter-btn" data-cat="<?= sanitize($cat) ?>">
            <?= sanitize($cat) ?>
        </button>
    <?php endforeach; ?>
</div>

<?php if (empty($tips)): ?>
    <div class="text-center text-muted py-5">
        <i class="bi bi-journal-x display-4"></i>
        <p class="mt-2">No tips available yet.</p>
    </div>
<?php else: ?>
    <div class="row g-4" id="tipsGrid">
        <?php foreach ($tips as $tip): ?>
        <div class="col-md-6 col-lg-4 tip-card" data-cat="<?= sanitize($tip['category']) ?>">
            <div class="card h-100 border-0 shadow-sm">
                <?php if ($tip['image_path']): ?>
                    <img src="<?= UPLOAD_URL . sanitize($tip['image_path']) ?>" class="card-img-top" style="height:180px;object-fit:cover;" alt="">
                <?php else: ?>
                    <div class="card-img-top bg-success bg-opacity-10 d-flex align-items-center justify-content-center" style="height:120px;">
                        <i class="bi bi-recycle text-success display-5"></i>
                    </div>
                <?php endif; ?>
                <div class="card-body">
                    <span class="badge bg-success mb-2"><?= sanitize($tip['category']) ?></span>
                    <h5 class="card-title fw-bold"><?= sanitize($tip['title']) ?></h5>
                    <p class="card-text text-muted"><?= nl2br(sanitize($tip['content'])) ?></p>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<script>
document.querySelectorAll('.filter-btn').forEach(btn => {
    btn.addEventListener('click', function() {
        document.querySelectorAll('.filter-btn').forEach(b => b.classList.remove('active', 'btn-success'));
        document.querySelectorAll('.filter-btn').forEach(b => b.classList.add('btn-outline-success'));
        this.classList.add('active', 'btn-success');
        this.classList.remove('btn-outline-success');
        const cat = this.dataset.cat;
        document.querySelectorAll('.tip-card').forEach(card => {
            card.style.display = (cat === 'all' || card.dataset.cat === cat) ? '' : 'none';
        });
    });
});
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
