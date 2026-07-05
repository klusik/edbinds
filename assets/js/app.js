(() => {
    const uploadForm = document.querySelector('[data-upload-form]');
    if (uploadForm) {
        const modeInputs = uploadForm.querySelectorAll('input[name="mode"]');
        const newFields = uploadForm.querySelector('[data-new-fields]');
        const versionFields = uploadForm.querySelector('[data-version-fields]');
        const sync = () => {
            const mode = uploadForm.querySelector('input[name="mode"]:checked')?.value || 'new';
            if (newFields) newFields.hidden = mode !== 'new';
            if (versionFields) versionFields.hidden = mode !== 'version';
        };
        modeInputs.forEach((input) => input.addEventListener('change', sync));
        sync();
    }

    const graphic = document.querySelector('[data-binding-graphic]');
    if (graphic) {
        document.querySelectorAll('[data-theme-button]').forEach((button) => {
            button.addEventListener('click', () => {
                graphic.classList.remove('theme-elite', 'theme-light', 'theme-grey');
                graphic.classList.add(`theme-${button.dataset.themeButton}`);
            });
        });
    }

    document.querySelectorAll('[data-filter-root]').forEach((root) => {
        const input = root.querySelector('[data-command-filter]');
        if (!input) return;
        input.addEventListener('input', () => {
            const needle = input.value.trim().toLowerCase();
            root.querySelectorAll('[data-command-row]').forEach((row) => {
                row.hidden = needle !== '' && !row.dataset.filterText.includes(needle);
            });
            root.querySelectorAll('[data-category-card]').forEach((card) => {
                const visibleRows = [...card.querySelectorAll('[data-command-row]')].some((row) => !row.hidden);
                card.hidden = !visibleRows;
            });
        });
    });

    const card = window.BINDING_CARD;
    const mapEl = document.getElementById('keyboard-map');
    const detailsEl = document.getElementById('key-details');
    if (card && mapEl) {
        renderKeyboardMap(card, mapEl, detailsEl);
    }

    function renderKeyboardMap(card, mapEl, detailsEl) {
        const rows = [
            ['Esc', 'F1', 'F2', 'F3', 'F4', 'F5', 'F6', 'F7', 'F8', 'F9', 'F10', 'F11', 'F12'],
            ['`', '1', '2', '3', '4', '5', '6', '7', '8', '9', '0', '-', '=', 'Backspace'],
            ['Tab', 'Q', 'W', 'E', 'R', 'T', 'Y', 'U', 'I', 'O', 'P', '[', ']', '\\'],
            ['Caps', 'A', 'S', 'D', 'F', 'G', 'H', 'J', 'K', 'L', ';', 'Acute', 'Enter'],
            ['Left Shift', 'Z', 'X', 'C', 'V', 'B', 'N', 'M', ',', '.', '/', 'Right Shift'],
            ['Left Ctrl', 'Left Alt', 'Space', 'Right Alt', 'Right Ctrl', 'Left', 'Up', 'Down', 'Right'],
        ];
        const index = new Map();
        for (const command of card.commands || []) {
            for (const binding of command.bindings || []) {
                const key = binding.key_label;
                if (!index.has(key)) index.set(key, []);
                index.get(key).push({ command, binding });
                for (const modifier of binding.modifiers || []) {
                    if (!index.has(modifier.label)) index.set(modifier.label, []);
                    index.get(modifier.label).push({ command, binding });
                }
            }
        }

        for (const row of rows) {
            const rowEl = document.createElement('div');
            rowEl.className = 'keyboard-row';
            for (const key of row) {
                const keyEl = document.createElement('button');
                keyEl.type = 'button';
                keyEl.className = 'keycap';
                if (['Backspace', 'Left Shift', 'Right Shift', 'Enter', 'Space'].includes(key)) keyEl.classList.add('wide');
                const hits = index.get(key) || [];
                keyEl.innerHTML = hits.length ? `${escapeHtml(key)} <strong>${hits.length}</strong>` : escapeHtml(key);
                if (hits.length) {
                    keyEl.classList.add('bound');
                    keyEl.addEventListener('click', () => {
                        detailsEl.innerHTML = `<strong>${escapeHtml(key)}</strong>: ` + hits
                            .slice(0, 12)
                            .map((hit) => `${escapeHtml(hit.command.category)} / ${escapeHtml(hit.command.label)} <kbd>${escapeHtml(hit.binding.combo)}</kbd>`)
                            .join(' | ');
                    });
                }
                rowEl.appendChild(keyEl);
            }
            mapEl.appendChild(rowEl);
        }
    }

    function escapeHtml(value) {
        return String(value).replace(/[&<>'"]/g, (char) => ({
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            "'": '&#039;',
            '"': '&quot;',
        }[char]));
    }
})();
