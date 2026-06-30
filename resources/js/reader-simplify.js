import {
    readerPage, setStatus, setWordCardLoading, apiPost,
} from './reader-ui.js';
import { showLoading, showResultNode, showError, i18n, state } from './reader/translation-panel.js';

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

    const label = (key, fallback) => i18n[`simplify.${key}`] || fallback;

    const appendLabeledText = (parent, title, textValue) => {
        const block = document.createElement('div');
        const labelNode = document.createElement('span');
        const textNode = document.createElement('p');
        labelNode.className = 'ai-tool-label-small';
        labelNode.textContent = title;
        textNode.className = 'ai-tool-simplified';
        textNode.textContent = textValue || '';
        block.append(labelNode, textNode);
        parent.appendChild(block);
    };

    const renderResult = (text, data, level) => {
        const root = document.createElement('div');
        const title = document.createElement('span');
        title.className = 'ai-tool-label';
        title.textContent = i18n['simplify.title'] || 'Simplified';
        root.appendChild(title);

        if (data.is_fragment === true) {
            const warning = document.createElement('p');
            warning.className = 'ai-tool-changes';
            warning.textContent = label('fragment_warning', 'This is an incomplete sentence fragment. No missing continuation was invented.');
            root.appendChild(warning);
        }

        const compare = document.createElement('div');
        if (window.innerWidth > 540) {
            compare.style.display = 'grid';
            compare.style.gridTemplateColumns = '1fr 1fr';
            compare.style.gap = '12px';
        }
        appendLabeledText(compare, label('original', 'Original'), data.original || text);
        appendLabeledText(compare, `${label('simplified', 'Simplified')} (${data.level || data.target_level || level})`, data.simplified || '');
        root.appendChild(compare);

        if (Array.isArray(data.replacements) && data.replacements.length > 0) {
            const replacements = document.createElement('div');
            replacements.className = 'ai-tool-pairs';
            const replacementsTitle = document.createElement('span');
            replacementsTitle.className = 'ai-tool-label-small';
            replacementsTitle.textContent = label('replacements', 'Replacements');
            replacements.appendChild(replacementsTitle);

            data.replacements.forEach((replacement) => {
                const item = document.createElement('div');
                item.className = 'ai-tool-pair';
                const original = document.createElement('p');
                const simplified = document.createElement('p');
                const reason = document.createElement('p');
                original.textContent = `${label('original_value', 'Original')}: ${replacement.original || ''}`;
                simplified.textContent = `${label('replacement_value', 'Replacement')}: ${replacement.replacement || replacement.simplified || ''}`;
                reason.textContent = `${label('reason', 'Reason')}: ${replacement.reason || ''}`;
                item.append(original, simplified, reason);
                replacements.appendChild(item);
            });
            root.appendChild(replacements);
        }

        if (data.explanation || data.changes_explanation) {
            const explanation = document.createElement('p');
            explanation.className = 'ai-tool-changes';
            explanation.textContent = data.explanation || data.changes_explanation;
            root.appendChild(explanation);
        }

        const tags = document.createElement('div');
        tags.style.display = 'flex';
        tags.style.gap = '6px';
        tags.style.marginTop = '8px';
        const levelTag = document.createElement('span');
        levelTag.className = 'ai-tag';
        levelTag.textContent = data.level || data.target_level || level;
        tags.appendChild(levelTag);
        if (data.meaning_preserved === true) {
            const meaningTag = document.createElement('span');
            meaningTag.className = 'ai-tag';
            meaningTag.textContent = label('meaning_preserved', 'Meaning preserved');
            tags.appendChild(meaningTag);
        }
        root.appendChild(tags);

        return root;
    };

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
                target_language: page.dataset.nativeLanguage || 'de',
                target_level: currentLevel,
            }, { signal: controller.signal });
            const data = result.data;
            showResultNode('simplify', renderResult(text, data, currentLevel));
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
