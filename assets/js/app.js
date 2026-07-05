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



    document.querySelectorAll('[data-print-binding]').forEach((button) => {
        button.addEventListener('click', () => {
            document.body.classList.add('is-printing-binding');
            window.print();
        });
    });

    window.addEventListener('afterprint', () => {
        document.body.classList.remove('is-printing-binding');
    });

    document.querySelectorAll('[data-export-png]').forEach((button) => {
        button.addEventListener('click', async () => {
            const target = document.querySelector('[data-binding-graphic]');
            if (!target) return;

            const originalText = button.textContent;
            button.disabled = true;
            button.textContent = 'Exporting...';

            try {
                await exportBindingGraphicToPng(target, {
                    filename: buildPngFilename(target),
                    scale: Number(button.dataset.exportScale || 2),
                });
            } catch (error) {
                console.error(error);
                alert('PNG export failed. Please try again in a current Chromium, Edge or Firefox browser.');
            } finally {
                button.disabled = false;
                button.textContent = originalText;
            }
        });
    });

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



    /**
     * Export the binding reference card as a high-DPI PNG using direct canvas rendering.
     *
     * The previous SVG foreignObject bridge was visually useful, but browser-dependent:
     * Chromium and Firefox can reject rasterizing foreignObject content after CSS variables,
     * column layout, or complex generated styles are embedded. This renderer draws the card
     * directly to canvas from the parsed binding data, so it does not depend on DOM-to-image
     * browser internals and works reliably on normal shared hosting.
     *
     * @param {HTMLElement} element Rendered binding card root element.
     * @param {{filename: string, scale: number}} options Export options.
     * @returns {Promise<void>} Resolves after the browser starts the download.
     */
    async function exportBindingGraphicToPng(element, options) {
        if (document.fonts && document.fonts.ready) {
            await document.fonts.ready;
        }

        const model = buildCanvasExportModel(element);
        const canvas = renderBindingGraphicToCanvas(model, options);
        const blob = await canvasToBlob(canvas);

        downloadBlob(blob, options.filename || 'elite-bindings.png');
    }

    /**
     * Build the complete canvas export model from the DOM and parsed binding payload.
     *
     * @param {HTMLElement} element Rendered binding card root element.
     * @returns {{
     *     title: string,
     *     description: string,
     *     callsign: string,
     *     stats: Array<{label: string, value: string}>,
     *     categories: Array<{name: string, rows: Array<{label: string, bindings: string[]}>}>,
     *     palette: Record<string,string>
     * }} Canvas renderer model.
     */
    function buildCanvasExportModel(element) {
        const parsed = window.BINDING_CARD || {};
        const header = element.querySelector('.graphic-header');
        const title = header?.querySelector('h2')?.textContent?.trim() || document.title || 'Elite Bindings';
        const description = header?.querySelector('p')?.textContent?.trim() || 'Elite Dangerous control profile';
        const callsign = header?.querySelector('.callsign')?.textContent?.trim() || 'CMDR REFERENCE';
        const stats = Array.from(header?.querySelectorAll('dl div') || []).map((entry) => ({
            label: entry.querySelector('dt')?.textContent?.trim() || '',
            value: entry.querySelector('dd')?.textContent?.trim() || '',
        })).filter((entry) => entry.label !== '' || entry.value !== '');

        return {
            title,
            description,
            callsign,
            stats,
            categories: buildCanvasCategories(parsed),
            palette: resolveCanvasPalette(element),
        };
    }

    /**
     * Convert parsed binding categories into a normalized renderer structure.
     *
     * @param {Record<string,mixed>} parsed Parsed binding payload from PHP.
     * @returns {Array<{name: string, rows: Array<{label: string, bindings: string[]}>}>} Normalized categories.
     */
    function buildCanvasCategories(parsed) {
        const result = [];
        const categories = parsed.categories || {};

        for (const [name, rows] of Object.entries(categories)) {
            const normalizedRows = Array.isArray(rows) ? rows.map((row) => ({
                label: String(row.label || row.command || 'Unnamed command'),
                bindings: Array.isArray(row.bindings)
                    ? row.bindings.map((binding) => String(binding.combo || binding.key_label || '').trim()).filter(Boolean)
                    : [],
            })).filter((row) => row.label !== '') : [];

            if (normalizedRows.length > 0) {
                result.push({ name: String(name), rows: normalizedRows });
            }
        }

        if (Array.isArray(parsed.conflicts) && parsed.conflicts.length > 0) {
            result.push({
                name: 'Potential conflicts',
                rows: parsed.conflicts.slice(0, 20).map((conflict) => ({
                    label: Array.isArray(conflict.commands) ? conflict.commands.join(', ') : 'Conflict',
                    bindings: [String(conflict.combo || '')].filter(Boolean),
                })),
            });
        }

        return result;
    }

    /**
     * Resolve the active graphic theme into concrete canvas colors.
     *
     * @param {HTMLElement} element Rendered binding card root element.
     * @returns {Record<string,string>} Canvas color palette.
     */
    function resolveCanvasPalette(element) {
        if (element.classList.contains('theme-light')) {
            return {
                background: '#f5f7fb',
                backgroundAlt: '#e8edf7',
                panel: '#ffffff',
                panelSoft: '#eef2f8',
                line: 'rgba(8, 18, 30, 0.15)',
                text: '#152033',
                muted: '#64748b',
                accent: '#2563eb',
                accentSoft: 'rgba(37, 99, 235, 0.12)',
                danger: '#b42318',
                shadow: 'rgba(8, 18, 30, 0.14)',
            };
        }

        if (element.classList.contains('theme-grey')) {
            return {
                background: '#16181c',
                backgroundAlt: '#22262d',
                panel: '#25282e',
                panelSoft: '#30343c',
                line: 'rgba(255, 255, 255, 0.14)',
                text: '#f0f2f5',
                muted: '#b2b7c0',
                accent: '#a7b0bd',
                accentSoft: 'rgba(167, 176, 189, 0.14)',
                danger: '#ff7c72',
                shadow: 'rgba(0, 0, 0, 0.32)',
            };
        }

        return {
            background: '#070b11',
            backgroundAlt: '#101821',
            panel: '#111923',
            panelSoft: '#182434',
            line: 'rgba(255, 255, 255, 0.13)',
            text: '#eef4ff',
            muted: '#9ba8ba',
            accent: '#ff7a18',
            accentSoft: 'rgba(255, 122, 24, 0.13)',
            danger: '#ff5f56',
            shadow: 'rgba(0, 0, 0, 0.38)',
        };
    }

    /**
     * Render the normalized binding model into a high-resolution canvas.
     *
     * @param {ReturnType<typeof buildCanvasExportModel>} model Canvas renderer model.
     * @param {{filename: string, scale: number}} options Export options.
     * @returns {HTMLCanvasElement} Rendered canvas.
     */
    function renderBindingGraphicToCanvas(model, options) {
        const metrics = createCanvasExportMetrics();
        const measureCanvas = document.createElement('canvas');
        const measureContext = measureCanvas.getContext('2d');

        if (!measureContext) {
            throw new Error('Could not create canvas measurement context.');
        }

        const layout = measureCanvasExportLayout(measureContext, model, metrics);
        const requestedScale = Number.isFinite(options.scale) && options.scale > 0 ? options.scale : 2;
        const scale = clampExportScale(layout.width, layout.height, requestedScale);

        const canvas = document.createElement('canvas');
        canvas.width = Math.ceil(layout.width * scale);
        canvas.height = Math.ceil(layout.height * scale);

        const context = canvas.getContext('2d');
        if (!context) {
            throw new Error('Could not create canvas render context.');
        }

        context.scale(scale, scale);
        context.imageSmoothingEnabled = true;
        context.imageSmoothingQuality = 'high';

        drawCanvasExport(context, model, layout, metrics);

        return canvas;
    }

    /**
     * Create fixed metrics for the generated PNG reference card.
     *
     * @returns {Record<string,number|string>} Canvas layout metrics.
     */
    function createCanvasExportMetrics() {
        return {
            width: 2400,
            margin: 48,
            gap: 20,
            cardRadius: 22,
            categoryPadding: 18,
            rowPaddingY: 8,
            titleFont: '900 58px Inter, Segoe UI, Arial, sans-serif',
            subtitleFont: '500 22px Inter, Segoe UI, Arial, sans-serif',
            callsignFont: '900 16px Inter, Segoe UI, Arial, sans-serif',
            statLabelFont: '800 13px Inter, Segoe UI, Arial, sans-serif',
            statValueFont: '900 22px Inter, Segoe UI, Arial, sans-serif',
            categoryFont: '900 25px Inter, Segoe UI, Arial, sans-serif',
            labelFont: '800 17px Inter, Segoe UI, Arial, sans-serif',
            keyFont: '800 14px Consolas, Menlo, Monaco, monospace',
            footerFont: '700 14px Inter, Segoe UI, Arial, sans-serif',
            titleLineHeight: 64,
            subtitleLineHeight: 30,
            categoryLineHeight: 30,
            labelLineHeight: 22,
            keyLineHeight: 17,
            pillPaddingX: 10,
            pillPaddingY: 5,
            rowGap: 0,
            headerHeight: 220,
            footerHeight: 42,
        };
    }

    /**
     * Measure the complete card layout and assign masonry positions to category cards.
     *
     * @param {CanvasRenderingContext2D} context Measurement context.
     * @param {ReturnType<typeof buildCanvasExportModel>} model Canvas renderer model.
     * @param {Record<string,number|string>} metrics Canvas layout metrics.
     * @returns {{
     *     width: number,
     *     height: number,
     *     columns: number,
     *     columnWidth: number,
     *     categoryPositions: Array<{category: {name: string, rows: Array<{label: string, bindings: string[]}>}, x: number, y: number, width: number, height: number}>
     * }} Measured layout.
     */
    function measureCanvasExportLayout(context, model, metrics) {
        const width = Number(metrics.width);
        const margin = Number(metrics.margin);
        const gap = Number(metrics.gap);
        const availableWidth = width - margin * 2;
        const columns = Math.max(4, Math.min(6, Math.floor((availableWidth + gap) / 360)));
        const columnWidth = Math.floor((availableWidth - gap * (columns - 1)) / columns);
        const categoryTop = margin + Number(metrics.headerHeight);
        const columnHeights = new Array(columns).fill(categoryTop);
        const categoryPositions = [];

        for (const category of model.categories) {
            const cardHeight = measureCategoryCard(context, category, columnWidth, metrics);
            let columnIndex = 0;

            for (let index = 1; index < columns; index += 1) {
                if (columnHeights[index] < columnHeights[columnIndex]) {
                    columnIndex = index;
                }
            }

            const x = margin + columnIndex * (columnWidth + gap);
            const y = columnHeights[columnIndex];
            categoryPositions.push({ category, x, y, width: columnWidth, height: cardHeight });
            columnHeights[columnIndex] += cardHeight + gap;
        }

        const contentHeight = Math.max(...columnHeights);
        const height = Math.ceil(contentHeight + Number(metrics.footerHeight) + margin);

        return { width, height, columns, columnWidth, categoryPositions };
    }

    /**
     * Measure a single category card.
     *
     * @param {CanvasRenderingContext2D} context Measurement context.
     * @param {{name: string, rows: Array<{label: string, bindings: string[]}>}} category Category data.
     * @param {number} width Card width.
     * @param {Record<string,number|string>} metrics Canvas layout metrics.
     * @returns {number} Card height in CSS pixels.
     */
    function measureCategoryCard(context, category, width, metrics) {
        const padding = Number(metrics.categoryPadding);
        let height = padding;

        context.font = String(metrics.categoryFont);
        height += wrapCanvasText(context, category.name, width - padding * 2, String(metrics.categoryFont)).length * Number(metrics.categoryLineHeight);
        height += 10;

        for (const row of category.rows) {
            height += measureBindingRow(context, row, width - padding * 2, metrics);
        }

        height += padding;

        return Math.ceil(height);
    }

    /**
     * Measure one command row including wrapped label text and stacked key pills.
     *
     * @param {CanvasRenderingContext2D} context Measurement context.
     * @param {{label: string, bindings: string[]}} row Row data.
     * @param {number} width Available row width.
     * @param {Record<string,number|string>} metrics Canvas layout metrics.
     * @returns {number} Row height.
     */
    function measureBindingRow(context, row, width, metrics) {
        const keyWidth = Math.floor(width * 0.43);
        const labelWidth = width - keyWidth - 12;
        const labelLines = wrapCanvasText(context, row.label, labelWidth, String(metrics.labelFont));
        const labelHeight = labelLines.length * Number(metrics.labelLineHeight);
        const keyHeight = measurePills(context, row.bindings.length ? row.bindings : ['Unbound'], keyWidth, metrics).height;

        return Math.ceil(Math.max(labelHeight, keyHeight) + Number(metrics.rowPaddingY) * 2);
    }

    /**
     * Measure a vertical stack of keyboard binding pills.
     *
     * @param {CanvasRenderingContext2D} context Measurement context.
     * @param {string[]} labels Pill labels.
     * @param {number} width Available width.
     * @param {Record<string,number|string>} metrics Canvas layout metrics.
     * @returns {{height: number, pills: Array<{label: string, lines: string[], width: number, height: number}>}} Pill measurements.
     */
    function measurePills(context, labels, width, metrics) {
        const result = [];
        let totalHeight = 0;

        context.font = String(metrics.keyFont);

        for (const label of labels) {
            const lines = wrapCanvasText(context, label, Math.max(16, width - Number(metrics.pillPaddingX) * 2), String(metrics.keyFont));
            const textWidth = Math.max(...lines.map((line) => context.measureText(line).width), 0);
            const pillWidth = Math.min(width, Math.ceil(textWidth + Number(metrics.pillPaddingX) * 2));
            const pillHeight = Math.ceil(lines.length * Number(metrics.keyLineHeight) + Number(metrics.pillPaddingY) * 2);
            result.push({ label, lines, width: pillWidth, height: pillHeight });
            totalHeight += pillHeight + 5;
        }

        if (result.length > 0) {
            totalHeight -= 5;
        }

        return { height: Math.max(22, totalHeight), pills: result };
    }

    /**
     * Draw the complete canvas export.
     *
     * @param {CanvasRenderingContext2D} context Render context.
     * @param {ReturnType<typeof buildCanvasExportModel>} model Canvas renderer model.
     * @param {ReturnType<typeof measureCanvasExportLayout>} layout Measured layout.
     * @param {Record<string,number|string>} metrics Canvas layout metrics.
     * @returns {void}
     */
    function drawCanvasExport(context, model, layout, metrics) {
        drawExportBackground(context, model.palette, layout.width, layout.height);
        drawExportHeader(context, model, metrics, layout.width);

        for (const card of layout.categoryPositions) {
            drawCategoryCard(context, card, model.palette, metrics);
        }

        context.font = String(metrics.footerFont);
        context.fillStyle = model.palette.muted;
        context.textAlign = 'right';
        context.textBaseline = 'alphabetic';
        context.fillText('Generated by Elite Bindings Vault', layout.width - Number(metrics.margin), layout.height - 24);
    }

    /**
     * Draw the generated card background.
     *
     * @param {CanvasRenderingContext2D} context Render context.
     * @param {Record<string,string>} palette Theme palette.
     * @param {number} width Canvas CSS width.
     * @param {number} height Canvas CSS height.
     * @returns {void}
     */
    function drawExportBackground(context, palette, width, height) {
        const gradient = context.createLinearGradient(0, 0, 0, height);
        gradient.addColorStop(0, palette.backgroundAlt);
        gradient.addColorStop(1, palette.background);
        context.fillStyle = gradient;
        context.fillRect(0, 0, width, height);

        const glow = context.createRadialGradient(120, 80, 0, 120, 80, 680);
        glow.addColorStop(0, palette.accentSoft);
        glow.addColorStop(1, 'rgba(0, 0, 0, 0)');
        context.fillStyle = glow;
        context.fillRect(0, 0, width, height);
    }

    /**
     * Draw the reference card header with metadata chips.
     *
     * @param {CanvasRenderingContext2D} context Render context.
     * @param {ReturnType<typeof buildCanvasExportModel>} model Canvas renderer model.
     * @param {Record<string,number|string>} metrics Canvas layout metrics.
     * @param {number} width Canvas CSS width.
     * @returns {void}
     */
    function drawExportHeader(context, model, metrics, width) {
        const margin = Number(metrics.margin);
        const palette = model.palette;
        const statsWidth = 520;
        const textWidth = width - margin * 2 - statsWidth - 36;
        let y = margin + 8;

        context.textAlign = 'left';
        context.textBaseline = 'top';

        context.font = String(metrics.callsignFont);
        context.fillStyle = palette.accent;
        context.fillText(model.callsign.toUpperCase(), margin, y);
        y += 28;

        context.font = String(metrics.titleFont);
        context.fillStyle = palette.text;
        const titleLines = wrapCanvasText(context, model.title, textWidth, String(metrics.titleFont)).slice(0, 2);
        for (const line of titleLines) {
            context.fillText(line, margin, y);
            y += Number(metrics.titleLineHeight);
        }

        context.font = String(metrics.subtitleFont);
        context.fillStyle = palette.muted;
        const descriptionLines = wrapCanvasText(context, model.description, textWidth, String(metrics.subtitleFont)).slice(0, 2);
        for (const line of descriptionLines) {
            context.fillText(line, margin, y);
            y += Number(metrics.subtitleLineHeight);
        }

        const boxGap = 12;
        const boxWidth = (statsWidth - boxGap) / 2;
        const boxHeight = 72;
        const statX = width - margin - statsWidth;
        const statY = margin;

        model.stats.slice(0, 4).forEach((stat, index) => {
            const x = statX + (index % 2) * (boxWidth + boxGap);
            const boxY = statY + Math.floor(index / 2) * (boxHeight + boxGap);

            drawRoundedRect(context, x, boxY, boxWidth, boxHeight, 16, palette.panel);
            context.strokeStyle = palette.line;
            context.lineWidth = 1;
            drawRoundedStroke(context, x, boxY, boxWidth, boxHeight, 16);

            context.font = String(metrics.statLabelFont);
            context.fillStyle = palette.muted;
            context.fillText(stat.label.toUpperCase(), x + 16, boxY + 12);

            context.font = String(metrics.statValueFont);
            context.fillStyle = palette.text;
            const valueLines = wrapCanvasText(context, stat.value || 'n/a', boxWidth - 32, String(metrics.statValueFont)).slice(0, 1);
            context.fillText(valueLines[0] || 'n/a', x + 16, boxY + 38);
        });
    }

    /**
     * Draw one measured category card.
     *
     * @param {{category: {name: string, rows: Array<{label: string, bindings: string[]}>}, x: number, y: number, width: number, height: number}} card Card layout.
     * @param {Record<string,string>} palette Theme palette.
     * @param {Record<string,number|string>} metrics Canvas layout metrics.
     * @returns {void}
     */
    function drawCategoryCard(context, card, palette, metrics) {
        const padding = Number(metrics.categoryPadding);
        const x = card.x;
        const y = card.y;
        const width = card.width;
        let cursorY = y + padding;

        context.save();
        context.shadowColor = palette.shadow;
        context.shadowBlur = 18;
        context.shadowOffsetY = 8;
        drawRoundedRect(context, x, y, width, card.height, Number(metrics.cardRadius), palette.panel);
        context.restore();

        context.strokeStyle = palette.line;
        context.lineWidth = 1;
        drawRoundedStroke(context, x, y, width, card.height, Number(metrics.cardRadius));

        context.textAlign = 'left';
        context.textBaseline = 'top';
        context.font = String(metrics.categoryFont);
        context.fillStyle = palette.accent;

        const titleLines = wrapCanvasText(context, card.category.name, width - padding * 2, String(metrics.categoryFont));
        for (const line of titleLines) {
            context.fillText(line, x + padding, cursorY);
            cursorY += Number(metrics.categoryLineHeight);
        }
        cursorY += 10;

        for (const row of card.category.rows) {
            const rowHeight = measureBindingRow(context, row, width - padding * 2, metrics);
            drawBindingRow(context, row, x + padding, cursorY, width - padding * 2, rowHeight, palette, metrics);
            cursorY += rowHeight;
        }
    }

    /**
     * Draw one command row.
     *
     * @param {CanvasRenderingContext2D} context Render context.
     * @param {{label: string, bindings: string[]}} row Row data.
     * @param {number} x Row x coordinate.
     * @param {number} y Row y coordinate.
     * @param {number} width Row width.
     * @param {number} height Row height.
     * @param {Record<string,string>} palette Theme palette.
     * @param {Record<string,number|string>} metrics Canvas layout metrics.
     * @returns {void}
     */
    function drawBindingRow(context, row, x, y, width, height, palette, metrics) {
        const keyWidth = Math.floor(width * 0.43);
        const labelWidth = width - keyWidth - 12;
        const labelX = x;
        const keyX = x + labelWidth + 12;
        const contentY = y + Number(metrics.rowPaddingY);

        context.strokeStyle = palette.line;
        context.lineWidth = 1;
        context.beginPath();
        context.moveTo(x, y + height - 0.5);
        context.lineTo(x + width, y + height - 0.5);
        context.stroke();

        context.font = String(metrics.labelFont);
        context.fillStyle = palette.text;
        context.textAlign = 'left';
        context.textBaseline = 'top';

        const labelLines = wrapCanvasText(context, row.label, labelWidth, String(metrics.labelFont));
        let lineY = contentY;
        for (const line of labelLines) {
            context.fillText(line, labelX, lineY);
            lineY += Number(metrics.labelLineHeight);
        }

        drawPills(context, row.bindings.length ? row.bindings : ['Unbound'], keyX, contentY, keyWidth, palette, metrics);
    }

    /**
     * Draw stacked keyboard binding pills aligned to the right.
     *
     * @param {CanvasRenderingContext2D} context Render context.
     * @param {string[]} labels Pill labels.
     * @param {number} x Pill area x coordinate.
     * @param {number} y Pill area y coordinate.
     * @param {number} width Pill area width.
     * @param {Record<string,string>} palette Theme palette.
     * @param {Record<string,number|string>} metrics Canvas layout metrics.
     * @returns {void}
     */
    function drawPills(context, labels, x, y, width, palette, metrics) {
        const measured = measurePills(context, labels, width, metrics);
        let cursorY = y;

        for (const pill of measured.pills) {
            const pillX = x + width - pill.width;
            drawRoundedRect(context, pillX, cursorY, pill.width, pill.height, 999, palette.panelSoft);
            context.strokeStyle = palette.accent;
            context.lineWidth = 1;
            drawRoundedStroke(context, pillX, cursorY, pill.width, pill.height, 999);

            context.font = String(metrics.keyFont);
            context.fillStyle = palette.text;
            context.textAlign = 'center';
            context.textBaseline = 'top';

            let textY = cursorY + Number(metrics.pillPaddingY);
            for (const line of pill.lines) {
                context.fillText(line, pillX + pill.width / 2, textY);
                textY += Number(metrics.keyLineHeight);
            }

            cursorY += pill.height + 5;
        }
    }

    /**
     * Wrap text into canvas-measured lines.
     *
     * @param {CanvasRenderingContext2D} context Render context.
     * @param {string} value Text value.
     * @param {number} maxWidth Maximum line width.
     * @param {string} font Canvas font string.
     * @returns {string[]} Wrapped lines.
     */
    function wrapCanvasText(context, value, maxWidth, font) {
        context.font = font;

        const text = String(value || '').replace(/\s+/g, ' ').trim();
        if (text === '') {
            return [''];
        }

        const words = text.split(' ');
        const lines = [];
        let line = '';

        for (const word of words) {
            const candidate = line === '' ? word : `${line} ${word}`;
            if (context.measureText(candidate).width <= maxWidth) {
                line = candidate;
                continue;
            }

            if (line !== '') {
                lines.push(line);
                line = '';
            }

            if (context.measureText(word).width <= maxWidth) {
                line = word;
                continue;
            }

            let fragment = '';
            for (const char of word) {
                const candidateFragment = fragment + char;
                if (context.measureText(candidateFragment).width <= maxWidth || fragment === '') {
                    fragment = candidateFragment;
                } else {
                    lines.push(fragment);
                    fragment = char;
                }
            }
            line = fragment;
        }

        if (line !== '') {
            lines.push(line);
        }

        return lines.length > 0 ? lines : [''];
    }

    /**
     * Draw a filled rounded rectangle.
     *
     * @param {CanvasRenderingContext2D} context Render context.
     * @param {number} x Rectangle x coordinate.
     * @param {number} y Rectangle y coordinate.
     * @param {number} width Rectangle width.
     * @param {number} height Rectangle height.
     * @param {number} radius Requested corner radius.
     * @param {string} fillStyle Fill style.
     * @returns {void}
     */
    function drawRoundedRect(context, x, y, width, height, radius, fillStyle) {
        drawRoundedPath(context, x, y, width, height, radius);
        context.fillStyle = fillStyle;
        context.fill();
    }

    /**
     * Draw a rounded rectangle stroke.
     *
     * @param {CanvasRenderingContext2D} context Render context.
     * @param {number} x Rectangle x coordinate.
     * @param {number} y Rectangle y coordinate.
     * @param {number} width Rectangle width.
     * @param {number} height Rectangle height.
     * @param {number} radius Requested corner radius.
     * @returns {void}
     */
    function drawRoundedStroke(context, x, y, width, height, radius) {
        drawRoundedPath(context, x, y, width, height, radius);
        context.stroke();
    }

    /**
     * Create a rounded rectangle path without relying on CanvasRenderingContext2D.roundRect.
     *
     * @param {CanvasRenderingContext2D} context Render context.
     * @param {number} x Rectangle x coordinate.
     * @param {number} y Rectangle y coordinate.
     * @param {number} width Rectangle width.
     * @param {number} height Rectangle height.
     * @param {number} radius Requested corner radius.
     * @returns {void}
     */
    function drawRoundedPath(context, x, y, width, height, radius) {
        const safeRadius = Math.max(0, Math.min(radius, width / 2, height / 2));

        context.beginPath();
        context.moveTo(x + safeRadius, y);
        context.lineTo(x + width - safeRadius, y);
        context.quadraticCurveTo(x + width, y, x + width, y + safeRadius);
        context.lineTo(x + width, y + height - safeRadius);
        context.quadraticCurveTo(x + width, y + height, x + width - safeRadius, y + height);
        context.lineTo(x + safeRadius, y + height);
        context.quadraticCurveTo(x, y + height, x, y + height - safeRadius);
        context.lineTo(x, y + safeRadius);
        context.quadraticCurveTo(x, y, x + safeRadius, y);
        context.closePath();
    }

    /**
     * Build a stable PNG file name from the visible binding title.
     *
     * @param {HTMLElement} target Rendered binding card root element.
     * @returns {string} Download file name.
     */
    function buildPngFilename(target) {
        const rawTitle = target.querySelector('.graphic-header h2')?.textContent || document.title || 'elite-bindings';
        const slug = rawTitle
            .trim()
            .toLowerCase()
            .normalize('NFKD')
            .replace(/[\u0300-\u036f]/g, '')
            .replace(/[^a-z0-9]+/g, '-')
            .replace(/^-+|-+$/g, '')
            .slice(0, 80) || 'elite-bindings';

        return `${slug}-reference-card.png`;
    }

    /**
     * Collect same-origin CSS rules so the cloned card renders inside the SVG export.
     *
     * @returns {string} CSS text.
     */
    function collectExportCssText() {
        const cssChunks = [];

        for (const sheet of Array.from(document.styleSheets)) {
            try {
                for (const rule of Array.from(sheet.cssRules || [])) {
                    cssChunks.push(rule.cssText);
                }
            } catch (error) {
                if (sheet.ownerNode && sheet.ownerNode.textContent) {
                    cssChunks.push(sheet.ownerNode.textContent);
                }
            }
        }

        cssChunks.push(`
            * { box-sizing: border-box; }
            html, body { margin: 0; padding: 0; background: transparent; }
            .binding-graphic.export-raster-target {
                width: 100% !important;
                max-width: none !important;
                margin: 0 !important;
                box-shadow: none !important;
            }
            .binding-graphic.export-raster-target .filter-line,
            .binding-graphic.export-raster-target .keyboard-panel {
                display: none !important;
            }
            .binding-graphic.export-raster-target .category-grid {
                display: block !important;
                column-width: 320px !important;
                column-gap: 14px !important;
                column-fill: balance !important;
            }
            .binding-graphic.export-raster-target .category-card {
                display: inline-block !important;
                break-inside: avoid !important;
                page-break-inside: avoid !important;
                width: 100% !important;
            }
        `);

        return cssChunks.join('\n');
    }

    /**
     * Serialize the cloned card as XHTML payload for the SVG foreignObject.
     *
     * @param {HTMLElement} clone Cloned binding card element.
     * @param {string} cssText CSS text to embed.
     * @param {number} width Export width in CSS pixels.
     * @param {number} height Export height in CSS pixels.
     * @returns {string} Serialized XHTML.
     */
    function serializeExportHtml(clone, cssText, width, height) {
        const wrapper = document.createElement('div');
        wrapper.setAttribute('xmlns', 'http://www.w3.org/1999/xhtml');
        wrapper.style.width = `${width}px`;
        wrapper.style.minHeight = `${height}px`;
        wrapper.style.margin = '0';
        wrapper.style.padding = '0';

        const style = document.createElement('style');
        style.textContent = cssText;
        wrapper.appendChild(style);
        wrapper.appendChild(clone.cloneNode(true));

        return new XMLSerializer().serializeToString(wrapper);
    }

    /**
     * Create an SVG document containing the serialized XHTML export payload.
     *
     * @param {string} serializedHtml Serialized XHTML.
     * @param {number} width SVG width in CSS pixels.
     * @param {number} height SVG height in CSS pixels.
     * @returns {string} SVG source.
     */
    function createExportSvg(serializedHtml, width, height) {
        return `<?xml version="1.0" encoding="UTF-8"?>
<svg xmlns="http://www.w3.org/2000/svg" width="${width}" height="${height}" viewBox="0 0 ${width} ${height}">
    <foreignObject width="100%" height="100%">${serializedHtml}</foreignObject>
</svg>`;
    }

    /**
     * Limit canvas pixel count to avoid browser canvas allocation failures on huge cards.
     *
     * @param {number} width CSS pixel width.
     * @param {number} height CSS pixel height.
     * @param {number} requestedScale Requested output multiplier.
     * @returns {number} Safe export scale.
     */
    function clampExportScale(width, height, requestedScale) {
        const maxPixels = 80000000;
        const requestedPixels = width * height * requestedScale * requestedScale;
        if (requestedPixels <= maxPixels) {
            return requestedScale;
        }

        return Math.max(1, Math.sqrt(maxPixels / (width * height)));
    }

    /**
     * Load an image from an object URL.
     *
     * @param {string} url Object URL.
     * @returns {Promise<HTMLImageElement>} Loaded image element.
     */
    function loadImage(url) {
        return new Promise((resolve, reject) => {
            const image = new Image();
            image.onload = () => resolve(image);
            image.onerror = () => reject(new Error('Could not render SVG export image.'));
            image.src = url;
        });
    }

    /**
     * Convert a canvas to a PNG blob.
     *
     * @param {HTMLCanvasElement} canvas Rendered canvas.
     * @returns {Promise<Blob>} PNG blob.
     */
    function canvasToBlob(canvas) {
        return new Promise((resolve, reject) => {
            canvas.toBlob((blob) => {
                if (blob) {
                    resolve(blob);
                } else {
                    reject(new Error('Could not encode PNG export.'));
                }
            }, 'image/png');
        });
    }

    /**
     * Trigger a browser download for a generated blob.
     *
     * @param {Blob} blob File blob.
     * @param {string} filename Download file name.
     * @returns {void}
     */
    function downloadBlob(blob, filename) {
        const url = URL.createObjectURL(blob);
        const anchor = document.createElement('a');
        anchor.href = url;
        anchor.download = filename;
        document.body.appendChild(anchor);
        anchor.click();
        anchor.remove();
        setTimeout(() => URL.revokeObjectURL(url), 1000);
    }

    /**
     * Resolve on the next animation frame.
     *
     * @returns {Promise<void>} Frame completion promise.
     */
    function nextAnimationFrame() {
        return new Promise((resolve) => requestAnimationFrame(() => resolve()));
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
