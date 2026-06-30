import {
    readerPage, setStatus, setWordCardLoading, apiPost, escHtml,
} from './reader-ui.js';
import { showLoading, showResult, showError, i18n, state } from './reader/translation-panel.js';

const STORAGE_KEY = 'lingorise:simplification-level';

const init = () => {
    const page = readerPage();
    if (!page) return;
    const btn = document.querySelector('[data-ai-tool="simplify"]');
    const url = page.dataset.simplifyUrl;
    if (!btn || !url) return;
    const levelsContainer = document.querySelector('[data-simplify-levels]');
    const resultContainer = document.querySelector('[data-simplify-result]');
    if (!levelsContainer || !resultContainer) return;
    let controller = null;

    let currentLevel = localStorage.getItem(STORAGE_KEY) || 'B1';
    levelsContainer.querySelectorAll('[data-level]').forEach((lvl) => {
        lvl.classList.toggle('is-active', lvl.dataset.level === currentLevel);
    });

    btn.addEventListener('click', async () => {
        if (controller) {
            controller.abort();
        }
        if (state.requests.simplify) {
            state.requests.simplify.abort();
        }
        const selectedWord = document.querySelector('[data-selected-word]');
        const contextNode = document.querySelector('[data-word-context]');
        const phrase = selectedWord?.textContent?.trim();
        const context = contextNode?.textContent?.trim();
        if (!phrase) return;

        controller = new AbortController();
        state.requests.simplify = controller;
        btn.disabled = true;
        setWordCardLoading(true);
        levelsContainer.hidden = false;
        showLoading('simplify', 'simplify.loading_title', 'simplify.loading_subtitle');

        try {
            const text = context && context.includes(phrase) ? context : phrase;
            const result = await apiPost(url, {
                text,
                source_language: page.dataset.bookLanguage || page.dataset.nativeLanguage || 'en',
                target_level: currentLevel,
            }, controller.signal);
            const data = result.data;
            const isMobile = window.innerWidth <= 540;
            showResult('simplify', `
                <span class="ai-tool-label">${escHtml(i18n['simplify.title'] || 'Simplified')}</span>
                ${isMobile
                    ? `<p class="ai-tool-simplified"><strong>${escHtml(i18n['simplify.original'] || 'Original')}:</strong><br>${escHtml(text)}</p>
                       <p class="ai-tool-simplified"><strong>${escHtml(i18n['simplify.simplified'] || 'Simplified')} (${escHtml(data.target_level || currentLevel)}):</strong><br>${escHtml(data.simplified || '')}</p>`
                    : `<div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
                          <div><span class="ai-tool-label-small">${escHtml(i18n['simplify.original'] || 'Original')}</span><p class="ai-tool-simplified">${escHtml(text)}</p></div>
                          <div><span class="ai-tool-label-small">${escHtml(i18n['simplify.simplified'] || 'Simplified')} (${escHtml(data.target_level || currentLevel)})</span><p class="ai-tool-simplified">${escHtml(data.simplified || '')}</p></div>
                       </div>`
                }
                ${data.replacements?.length ? `
                    <div class="ai-tool-pairs">
                        ${data.replacements.map((r) => `<span class="ai-tool-pair"><del>${escHtml(r.original)}</del> → <ins>${escHtml(r.simplified)}</ins></span>`).join('')}
                    </div>
                ` : ''}
                ${data.changes_explanation ? `<p class="ai-tool-changes">${escHtml(data.changes_explanation)}</p>` : ''}
                <div style="display:flex;gap:6px;margin-top:8px">
                    <span class="ai-tag">${escHtml(data.target_level || currentLevel)}</span>
                </div>`);
            setStatus('');
        } catch (err) {
            if (err.name !== 'AbortError') {
                showError('simplify', err);
            }
        } finally {
            btn.disabled = false;
            setWordCardLoading(false);
            controller = null;
            state.requests.simplify = null;
        }
    });

    levelsContainer.querySelectorAll('[data-level]').forEach((lvl) => {
        lvl.addEventListener('click', () => {
            currentLevel = lvl.dataset.level;
            localStorage.setItem(STORAGE_KEY, currentLevel);
            levelsContainer.querySelectorAll('[data-level]').forEach((b) => b.classList.remove('is-active'));
            lvl.classList.add('is-active');
            btn.click();
        });
    });
};

if (document.readyState !== 'loading') {
    init();
} else {
    document.addEventListener('DOMContentLoaded', init);
}
