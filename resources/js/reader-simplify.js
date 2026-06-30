import {
    readerPage, wordCard, setStatus, setWordCardLoading,
    showUpgrade, positionWordCard, apiPost,
} from './reader-ui.js';

const init = () => {
    const page = readerPage();
    if (!page) return;
    const btn = wordCard()?.querySelector('[data-ai-tool="simplify"]');
    const output = wordCard()?.querySelector('[data-ai-output]');
    if (!btn || !output) return;
    const url = page.dataset.simplifyUrl;
    if (!url) return;

    btn.addEventListener('click', async () => {
        const selectedWord = wordCard().querySelector('[data-selected-word]');
        const contextNode = wordCard().querySelector('[data-word-context]');
        const phrase = selectedWord?.textContent?.trim();
        const context = contextNode?.textContent?.trim();
        if (!phrase) return;

        btn.disabled = true;
        setWordCardLoading(true);
        setStatus('Simplifying…');
        output.innerHTML = '';
        output.hidden = false;

        try {
            const result = await apiPost(url, {
                text: context && context.includes(phrase) ? context : phrase,
                source_language: page.dataset.nativeLanguage || 'en',
                target_level: 'A2',
            });
            const data = result.data;
            output.innerHTML = `
                <div class="ai-tool-section">
                    <span class="ai-tool-label">Simplified</span>
                    <p class="ai-tool-simplified">${escHtml(data.simplified || '')}</p>
                    ${data.changes_explanation ? `<p class="ai-tool-changes">${escHtml(data.changes_explanation)}</p>` : ''}
                    ${data.replacements?.length ? `
                        <div class="ai-tool-pairs">
                            ${data.replacements.map((r) => `<span class="ai-tool-pair"><del>${escHtml(r.original)}</del> → <ins>${escHtml(r.simplified)}</ins></span>`).join('')}
                        </div>
                    ` : ''}
                    <span class="ai-tag">${escHtml(data.target_level || 'A2')}</span>
                    ${result.meta?.cache_hit ? '<small class="ai-cache-note">cached</small>' : ''}
                </div>`;
            setStatus('');
        } catch (err) {
            if (err.upgrade_url) {
                setStatus(err.message || 'Upgrade to use this feature');
                showUpgrade();
            } else {
                setStatus(err.message || 'Simplification unavailable.');
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
