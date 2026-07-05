<?php
use App\Core\Security;
/** @var array<int,array<string,mixed>> $sets */
?>
<section class="hero">
    <div>
        <p class="eyebrow">Elite Dangerous Odyssey bindings</p>
        <h1>Public reference cards for keyboard, HOTAS and hybrid setups.</h1>
        <p>Upload a <code>.binds</code> file after login, keep immutable versions, and publish only the profiles you want other commanders to see.</p>
    </div>
    <div class="hero-panel">
        <strong>Current public cards</strong>
        <span><?= count($sets) ?></span>
    </div>
</section>

<section class="section-head">
    <h2>Public bindings</h2>
    <a class="button" href="index.php?page=upload">Upload bindings</a>
</section>

<?php if (!$sets): ?>
    <div class="empty-state">No public bindings yet. Log in and upload the first card.</div>
<?php else: ?>
    <div class="card-grid">
        <?php foreach ($sets as $set): ?>
            <?php $stats = json_decode((string)($set['stats_json'] ?? '{}'), true) ?: []; ?>
            <article class="summary-card">
                <div class="summary-card-top">
                    <span class="pill <?= $set['visibility'] === 'public' ? 'pill-public' : '' ?>"><?= Security::e($set['visibility']) ?></span>
                    <span>v<?= Security::e($set['version_number'] ?? '1') ?></span>
                </div>
                <h3><a href="index.php?page=view&id=<?= (int)$set['id'] ?>"><?= Security::e($set['title']) ?></a></h3>
                <p><?= Security::e($set['description'] ?: 'No description.') ?></p>
                <dl class="meta-list">
                    <div><dt>Owner</dt><dd><?= Security::e($set['username'] ?? '') ?></dd></div>
                    <div><dt>Commands</dt><dd><?= (int)($stats['commands_total'] ?? 0) ?></dd></div>
                    <div><dt>Conflicts</dt><dd><?= (int)($stats['conflicts_total'] ?? 0) ?></dd></div>
                </dl>
            </article>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
