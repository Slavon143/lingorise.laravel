import {
    readerPage, wordCard, setStatus, setWordCardLoading,
    showUpgrade, positionWordCard, apiPost,
} from './reader-ui.js';

const init = () => {
    const page = readerPage();
    if (!page) return;
    const btn = wordCard()?.querySelector('[data-ai-tool="grammar-explain"]');
    const output = wordCard()?.querySelector('[data-ai-output]');
    if (!btn || !output) return;
    const url = page.dataset.grammarExplainUrl;
    if (!url) return;

    btn.addEventListener('click', async () => {
        const selectedWord = wordCard().querySelector('[data-selected-word]');
        const contextNode = wordCard().querySelector('[data-word-context]');
        const phrase = selectedWord?.textContent?.trim();
        const context = contextNode?.textContent?.trim();
        if (!phrase) return;

        btn.disabled = true;
        setWordCardLoading(true);
        setStatus('Analysing grammar…');
        output.innerHTML = '';
        output.hidden = false;

        try {
            const result = await apiPost(url, {
                text: phrase,
                context: context || null,
                source_language: page.dataset.nativeLanguage || 'en',
                target_language: page.dataset.nativeLanguage || 'de',
            });
            const data = result.data;
            output.innerHTML = `
                <div class="ai-tool-section">
                    <span class="ai-tool-label">Grammar explanation</span>
                    <strong class="ai-tool-construction">${escHtml(data.construction || '')}</strong>
                    <p class="ai-tool-purpose">${escHtml(data.purpose || '')}</p>
                    <div class="ai-tool-structure">
                        <span class="ai-tool-label-small">Structure</span>
                        <code>${escHtml(data.structure || '')}</code>
                    </div>
                    ${data.simplified_translation ? `<p class="ai-tool-translation">→ ${escHtml(data.simplified_translation)}</p>` : ''}
                    ${data.additional_example ? `<blockquote class="ai-tool-example">${escHtml(data.additional_example)}</blockquote>` : ''}
                    ${data.common_mistake ? `<p class="ai-tool-mistake">⚠ ${escHtml(data.common_mistake)}</p>` : ''}
                    ${result.meta?.cache_hit ? '<small class="ai-cache-note">cached</small>' : ''}
                </div>`;
            setStatus('');
        } catch (err) {
            if (err.upgrade_url) {
                setStatus(err.message || 'Upgrade to use this feature');
                showUpgrade();
            } else {
                setStatus(err.message || 'Grammar explanation unavailable.');
            }
        } finally {
            btn.disabled = false;
            setWordCardLoading(false);
        }
    });
};

const escHtml = (str) => {
    const div = document.createElement('div');
    div.textContent = str;
    return div.innerHTML;
};

if (document.readyState !== 'loading') {
    init();
} else {
    document.addEventListener('DOMContentLoaded', init);
}
