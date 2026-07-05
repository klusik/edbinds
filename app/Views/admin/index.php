<?php
use App\Core\Security;
/** @var array<int,array<string,mixed>> $sets */
/** @var array<int,array<string,mixed>> $users */
?>
<section class="section-head">
    <div>
        <p class="eyebrow">Administrator</p>
        <h1>Administration</h1>
    </div>
    <a class="button" href="index.php?page=upload">Admin upload</a>
</section>

<h2>Bindings</h2>
<div class="table-wrap">
    <table>
        <thead><tr><th>Title</th><th>Owner</th><th>Visibility</th><th>Version</th><th>Actions</th></tr></thead>
        <tbody>
        <?php foreach ($sets as $set): ?>
            <tr>
                <td><a href="index.php?page=view&id=<?= (int)$set['id'] ?>"><?= Security::e($set['title']) ?></a></td>
                <td><?= Security::e($set['username']) ?></td>
                <td><?= Security::e($set['visibility']) ?></td>
                <td>v<?= Security::e($set['version_number'] ?? '1') ?></td>
                <td>
                    <form method="post" action="index.php?page=admin-visibility" class="inline-form">
                        <input type="hidden" name="_csrf" value="<?= Security::e(Security::csrfToken()) ?>">
                        <input type="hidden" name="binding_set_id" value="<?= (int)$set['id'] ?>">
                        <select name="visibility">
                            <option value="private" <?= $set['visibility'] === 'private' ? 'selected' : '' ?>>private</option>
                            <option value="public" <?= $set['visibility'] === 'public' ? 'selected' : '' ?>>public</option>
                        </select>
                        <button type="submit">Save</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>

<h2>Users</h2>
<div class="table-wrap">
    <table>
        <thead><tr><th>ID</th><th>Username</th><th>E-mail</th><th>Role</th><th>Created</th><th>Last login</th></tr></thead>
        <tbody>
        <?php foreach ($users as $row): ?>
            <tr>
                <td><?= (int)$row['id'] ?></td>
                <td><?= Security::e($row['username']) ?></td>
                <td><?= Security::e($row['email']) ?></td>
                <td><?= Security::e($row['role']) ?></td>
                <td><?= Security::e($row['created_at']) ?></td>
                <td><?= Security::e($row['last_login_at'] ?? '') ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
