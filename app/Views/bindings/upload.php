<?php
use App\Core\Config;
use App\Core\Security;
/** @var array<int,array<string,mixed>> $sets */
/** @var string|null $error */
?>
<section class="section-head">
    <div>
        <p class="eyebrow">Upload .binds XML</p>
        <h1>Upload bindings</h1>
        <p class="muted">Maximum file size: <?= number_format(Config::getInt('app.upload_max_bytes', 1048576) / 1024, 0) ?> KiB.</p>
    </div>
</section>

<?php if ($error): ?><div class="flash flash-error"><?= Security::e($error) ?></div><?php endif; ?>

<form method="post" enctype="multipart/form-data" class="form-card wide" data-upload-form>
    <input type="hidden" name="_csrf" value="<?= Security::e(Security::csrfToken()) ?>">
    <div class="radio-row">
        <label><input type="radio" name="mode" value="new" checked> New file</label>
        <label><input type="radio" name="mode" value="version"> New version of existing file</label>
    </div>

    <div class="form-grid" data-new-fields>
        <label>Title
            <input name="title" placeholder="Keyboard and mouse, Odyssey 4.2">
        </label>
        <label>Visibility
            <select name="visibility">
                <option value="private">Private</option>
                <option value="public">Public</option>
            </select>
        </label>
        <label class="span-2">Description
            <textarea name="description" rows="3" placeholder="Optional public note"></textarea>
        </label>
    </div>

    <label data-version-fields hidden>Existing binding set
        <select name="binding_set_id">
            <?php foreach ($sets as $set): ?>
                <option value="<?= (int)$set['id'] ?>"><?= Security::e($set['title']) ?>, v<?= Security::e($set['version_number'] ?? '1') ?></option>
            <?php endforeach; ?>
        </select>
    </label>

    <label>Bindings file
        <input name="binds_file" type="file" accept=".binds,.xml,application/xml,text/xml" required>
    </label>

    <label>Version note
        <textarea name="change_note" rows="3" placeholder="Optional history note, for example changed FSS keys"></textarea>
    </label>

    <button type="submit">Process upload</button>
</form>
