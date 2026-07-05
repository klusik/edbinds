<?php
use App\Core\Security;
/** @var array<int,array<string,mixed>> $sets */
?>
<section class="section-head">
    <div>
        <p class="eyebrow">Private workspace</p>
        <h1>My bindings</h1>
    </div>
    <a class="button" href="index.php?page=upload">Upload new file</a>
</section>

<?php if (!$sets): ?>
    <div class="empty-state">You have no bindings yet.</div>
<?php else: ?>
    <div class="table-wrap">
        <table>
            <thead><tr><th>Title</th><th>Visibility</th><th>Version</th><th>Commands</th><th>Updated</th></tr></thead>
            <tbody>
            <?php foreach ($sets as $set): ?>
                <?php $stats = json_decode((string)($set['stats_json'] ?? '{}'), true) ?: []; ?>
                <tr>
                    <td><a href="index.php?page=view&id=<?= (int)$set['id'] ?>"><?= Security::e($set['title']) ?></a></td>
                    <td><?= Security::e($set['visibility']) ?></td>
                    <td>v<?= Security::e($set['version_number'] ?? '1') ?></td>
                    <td><?= (int)($stats['commands_total'] ?? 0) ?></td>
                    <td><?= Security::e($set['updated_at']) ?></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>
