import {
    readerPage, setStatus, setWordCardLoading, apiPost, escHtml,
} from './reader-ui.js';
import { showLoading, showResult, showError, i18n, state } from './reader/translation-panel.js';

const init = () => {
    const page = readerPage();
    if (!page) return;
    const btn = document.querySelector('[data-ai-tool="context-explain"]');
    const url = page.dataset.contextExplainUrl;
    if (!btn || !url) return;
    let controller = null;

    btn.addEventListener('click', async () => {
        if (state.requests.context) {
            state.requests.context.abort();
        }
        if (controller) {
            controller.abort();
        }
        const selectedWord = document.querySelector('[data-selected-word]');
        const contextNode = document.querySelector('[data-word-context]');
        const phrase = selectedWord?.textContent?.trim();
        const context = contextNode?.textContent?.trim();
        if (!phrase || !context) return;

        controller = new AbortController();
        state.requests.context = controller;
        btn.disabled = true;
        setWordCardLoading(true);
        showLoading('context', 'context.loading_title', 'context.loading_subtitle');

        try {
            const result = await apiPost(url, {
                selected_text: phrase,
                context,
                source_language: page.dataset.bookLanguage || page.dataset.nativeLanguage || 'en',
            }, controller.signal);
            const data = result.data;

            const sections = [];

            if (data.meaning_in_context) {
                sections.push(`<div class="ai-context-meaning">${escHtml(data.meaning_in_context)}</div>`);
            }

            const tags = [];
            if (data.cefr_level) tags.push(`<span class="ai-tag">${escHtml(data.cefr_level)}</span>`);
            if (data.part_of_speech) tags.push(`<span class="ai-tag">${escHtml(data.part_of_speech)}</span>`);
            if (data.register) tags.push(`<span class="ai-tag">${escHtml(data.register)}</span>`);
            if (data.connotation) tags.push(`<span class="ai-tag">${escHtml(data.connotation)}</span>`);
            if (tags.length) {
                sections.push(`<div class="ai-context-tags">${tags.join('')}</div>`);
            }

            if (data.why_this_meaning) {
                sections.push(renderSection(i18n['context.why'] || 'Why', data.why_this_meaning));
            }
            if (data.role_in_sentence) {
                sections.push(renderSection(i18n['context.role'] || 'Role', data.role_in_sentence));
            }
            if (data.base_form) {
                sections.push(renderSection(i18n['context.meaning'] || 'Base form', data.base_form));
            }
            if (data.fixed_expression) {
                sections.push(`<div class="ai-context-section"><span class="ai-context-section-label">${escHtml(i18n['context.fixed_expression'] || 'Fixed expression')}</span><p>${escHtml(data.expression || '')}</p></div>`);
            }
            if (data.literal_translation_warning) {
                sections.push(`<div class="ai-context-warning">${escHtml(data.literal_translation_warning)}</div>`);
            }
            if (Array.isArray(data.synonyms) && data.synonyms.length) {
                sections.push(`<div class="ai-context-synonyms"><strong>${escHtml(i18n['context.synonyms'] || 'Synonyms')}:</strong> ${data.synonyms.map(s => escHtml(s)).join(' · ')}</div>`);
            }
            if (data.common_misunderstanding) {
                sections.push(renderSection(i18n['context.common_mistake'] || 'Common misunderstanding', data.common_misunderstanding));
            }
            if (data.natural_example) {
                sections.push(`<div class="ai-context-example">${escHtml(data.natural_example)}</div>`);
            }

            showResult('context', sections.join(''));
            setStatus('');
        } catch (err) {
            if (err.name !== 'AbortError') {
                showError('context', err);
            }
        } finally {
            btn.disabled = false;
            setWordCardLoading(false);
            controller = null;
            state.requests.context = null;
        }
    });
};

const renderSection = (label, text) => {
    return `<div class="ai-context-section"><span class="ai-context-section-label">${escHtml(label)}</span><p>${escHtml(text)}</p></div>`;
};

if (document.readyState !== 'loading') {
    init();
} else {
    document.addEventListener('DOMContentLoaded', init);
}
