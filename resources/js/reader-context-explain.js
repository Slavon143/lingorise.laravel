import {
    readerPage, wordCard, setStatus, setWordCardLoading,
    showUpgrade, positionWordCard, apiPost,
} from './reader-ui.js';

const init = () => {
    const page = readerPage();
    if (!page) return;
    const btn = wordCard()?.querySelector('[data-ai-tool="context-explain"]');
    const output = wordCard()?.querySelector('[data-ai-output]');
    if (!btn || !output) return;
    const url = page.dataset.contextExplainUrl;
    if (!url) return;

    btn.addEventListener('click', async () => {
        const selectedWord = wordCard().querySelector('[data-selected-word]');
        const contextNode = wordCard().querySelector('[data-word-context]');
        const phrase = selectedWord?.textContent?.trim();
        const context = contextNode?.textContent?.trim();
        if (!phrase || !context) return;

        btn.disabled = true;
        setWordCardLoading(true);
        setStatus('Explaining…');
        output.innerHTML = '';
        output.hidden = false;

        try {
            const result = await apiPost(url, {
                selected_text: phrase,
                context,
                source_language: page.dataset.nativeLanguage || 'en',
            });
            const data = result.data;
            output.innerHTML = `
                <div class="ai-tool-section">
                    <span class="ai-tool-label">Context explanation</span>
                    <p class="ai-tool-meaning">${escHtml(data.meaning_in_context || '')}</p>
                    ${data.simple_explanation ? `<p class="ai-tool-simple">${escHtml(data.simple_explanation)}</p>` : ''}
                    <div class="ai-tool-meta">
                        ${data.part_of_speech ? `<span class="ai-tag">${escHtml(data.part_of_speech)}</span>` : ''}
                        ${data.cefr_level ? `<span class="ai-tag">${escHtml(data.cefr_level)}</span>` : ''}
                        ${data.grammar_form ? `<span class="ai-tag">${escHtml(data.grammar_form)}</span>` : ''}
                    </div>
                    ${data.example ? `<blockquote class="ai-tool-example">${escHtml(data.example)}</blockquote>` : ''}
                    ${result.meta?.cache_hit ? '<small class="ai-cache-note">cached</small>' : ''}
                </div>`;
            setStatus('');
        } catch (err) {
            if (err.upgrade_url) {
                setStatus(err.message || 'Upgrade to use this feature');
                showUpgrade();
            } else {
                setStatus(err.message || 'Explanation unavailable.');
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
