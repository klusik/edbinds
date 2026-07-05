<?php
use App\Core\Config;
use App\Core\Security;
/** @var array<string,mixed> $binding */
/** @var array<string,mixed> $parsed */
/** @var array<int,array<string,mixed>> $versions */
$stats = $parsed['stats'] ?? [];
$metadata = $parsed['metadata'] ?? [];
$defaultTheme = Config::getString('app.default_theme', 'elite');
?>
<section class="binding-toolbar">
    <div>
        <p class="eyebrow">Reference card</p>
        <h1><?= Security::e($binding['title']) ?></h1>
        <p class="muted">Owner <?= Security::e($binding['username'] ?? '') ?>, version <?= (int)$binding['version_number'] ?>, <?= Security::e($binding['visibility']) ?>.</p>
    </div>
    <div class="toolbar-actions">
        <button type="button" data-theme-button="elite">Elite</button>
        <button type="button" data-theme-button="light">Light</button>
        <button type="button" data-theme-button="grey">Grey</button>
        <button type="button" onclick="window.print()">Print</button>
        <a class="button ghost" href="index.php?page=download&version=<?= (int)$binding['version_id'] ?>">Download XML</a>
    </div>
</section>

<section class="version-strip">
    <strong>Versions</strong>
    <?php foreach ($versions as $version): ?>
        <a class="version-chip <?= (int)$version['id'] === (int)$binding['version_id'] ? 'active' : '' ?>" href="index.php?page=view&id=<?= (int)$binding['id'] ?>&version=<?= (int)$version['id'] ?>">
            v<?= (int)$version['version_number'] ?>
        </a>
    <?php endforeach; ?>
</section>

<article class="binding-graphic theme-<?= Security::e($defaultTheme) ?>" data-binding-graphic>
    <header class="graphic-header">
        <div>
            <span class="callsign">CMDR REFERENCE</span>
            <h2><?= Security::e($binding['title']) ?></h2>
            <p><?= Security::e($binding['description'] ?: 'Elite Dangerous control profile') ?></p>
        </div>
        <dl>
            <div><dt>Preset</dt><dd><?= Security::e($metadata['preset_name'] ?? '') ?></dd></div>
            <div><dt>Game</dt><dd><?= Security::e(($metadata['major_version'] ?? '') . '.' . ($metadata['minor_version'] ?? '')) ?></dd></div>
            <div><dt>Layout</dt><dd><?= Security::e($metadata['keyboard_layout'] ?? '') ?></dd></div>
            <div><dt>Commands</dt><dd><?= (int)($stats['commands_total'] ?? 0) ?></dd></div>
        </dl>
    </header>

    <section class="keyboard-panel">
        <div class="keyboard-head">
            <h3>Keyboard heat map</h3>
            <p>Keys with one or more assignments are highlighted. Click a key to inspect commands.</p>
        </div>
        <div id="keyboard-map" class="keyboard-map" aria-live="polite"></div>
        <div id="key-details" class="key-details">Select a key.</div>
    </section>

    <section class="category-grid compact" data-filter-root>
        <div class="filter-line">
            <label>Filter commands
                <input data-command-filter placeholder="boost, FSS, cargo, WEP...">
            </label>
        </div>
        <?php foreach (($parsed['categories'] ?? []) as $category => $items): ?>
            <section class="category-card" data-category-card>
                <h3><?= Security::e($category) ?></h3>
                <div class="binding-list">
                    <?php foreach ($items as $item): ?>
                        <div class="binding-row" data-command-row data-filter-text="<?= Security::e(strtolower($category . ' ' . $item['label'] . ' ' . $item['command'])) ?>">
                            <span class="binding-label" title="<?= Security::e($item['command']) ?>"><?= Security::e($item['label']) ?></span>
                            <span class="binding-keys">
                                <?php foreach ($item['bindings'] as $bind): ?>
                                    <kbd><?= Security::e($bind['combo']) ?></kbd>
                                <?php endforeach; ?>
                            </span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </section>
        <?php endforeach; ?>
    </section>

    <?php if (!empty($parsed['conflicts'])): ?>
        <section class="conflict-panel">
            <h3>Potential conflicts</h3>
            <?php foreach (array_slice($parsed['conflicts'], 0, 20) as $conflict): ?>
                <p><strong><?= Security::e($conflict['combo']) ?></strong>: <?= Security::e(implode(', ', $conflict['commands'])) ?></p>
            <?php endforeach; ?>
        </section>
    <?php endif; ?>
</article>

<script>
window.BINDING_CARD = <?= json_encode($parsed, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR) ?>;
</script>
