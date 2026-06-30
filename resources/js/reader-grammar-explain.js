import {
    readerPage, setStatus, setWordCardLoading, apiPost, escHtml,
} from './reader-ui.js';
import { showLoading, showResult, showError, i18n, state } from './reader/translation-panel.js';

const init = () => {
    const page = readerPage();
    if (!page) return;
    const btn = document.querySelector('[data-ai-tool="grammar-explain"]');
    const url = page.dataset.grammarExplainUrl;
    if (!btn || !url) return;
    let controller = null;

    btn.addEventListener('click', async () => {
        if (controller) {
            controller.abort();
        }
        if (state.requests.grammar) {
            state.requests.grammar.abort();
        }
        const selectedWord = document.querySelector('[data-selected-word]');
        const contextNode = document.querySelector('[data-word-context]');
        const phrase = selectedWord?.textContent?.trim();
        const context = contextNode?.textContent?.trim();
        if (!phrase) return;

        controller = new AbortController();
        state.requests.grammar = controller;
        btn.disabled = true;
        setWordCardLoading(true);
        showLoading('grammar', 'grammar.loading_title', 'grammar.loading_subtitle');

        try {
            const result = await apiPost(url, {
                text: phrase,
                context: context || null,
                source_language: page.dataset.bookLanguage || page.dataset.nativeLanguage || 'en',
            }, controller.signal);
            const data = result.data;
            let partsHtml = '';
            if (data.parts?.length) {
                partsHtml = '<div class="ai-tool-meta">' +
                    data.parts.map((p) =>
                        `<span class="ai-tag">${escHtml(p.text || '')} — ${escHtml(p.role || '')}</span>`
                    ).join('') +
                    '</div>';
            }
            showResult('grammar', `
                <span class="ai-tool-label">${escHtml(i18n['grammar.title'] || 'Grammar explanation')}</span>
                <strong class="ai-tool-construction">${escHtml(data.construction || '')}</strong>
                <p class="ai-tool-purpose">${escHtml(data.purpose || '')}</p>
                ${data.structure ? `<div class="ai-tool-structure"><code>${escHtml(data.structure)}</code></div>` : ''}
                ${partsHtml}
                ${data.simplified_translation ? `<p class="ai-tool-translation">→ ${escHtml(data.simplified_translation)}</p>` : ''}
                ${data.additional_example ? `<blockquote class="ai-tool-example">${escHtml(data.additional_example)}</blockquote>` : ''}
                ${data.common_mistake ? `<p class="ai-tool-mistake">${escHtml(data.common_mistake)}</p>` : ''}`);
            setStatus('');
        } catch (err) {
            if (err.name !== 'AbortError') {
                showError('grammar', err);
            }
        } finally {
            btn.disabled = false;
            setWordCardLoading(false);
            controller = null;
            state.requests.grammar = null;
        }
    });
};

if (document.readyState !== 'loading') {
    init();
} else {
    document.addEventListener('DOMContentLoaded', init);
}
