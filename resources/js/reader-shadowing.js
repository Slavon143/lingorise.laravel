import {
    readerPage, wordCard, setStatus, setWordCardLoading,
    showUpgrade, positionWordCard, apiPost, allTokens,
} from './reader-ui.js';

const init = () => {
    const page = readerPage();
    if (!page) return;
    const btn = wordCard()?.querySelector('[data-ai-tool="shadowing"]');
    const output = wordCard()?.querySelector('[data-ai-output]');
    if (!btn || !output) return;
    const url = page.dataset.shadowingUrl;
    if (!url) return;

    btn.addEventListener('click', async () => {
        const selectedWord = wordCard().querySelector('[data-selected-word]');
        const phrase = selectedWord?.textContent?.trim();
        if (!phrase) return;

        const tokens = allTokens();
        const activeTokens = wordCard().querySelectorAll('.reader-token.is-selected');
        const firstIndex = activeTokens.length
            ? tokens.indexOf(activeTokens[0])
            : -1;
        const lastIndex = activeTokens.length
            ? tokens.indexOf(activeTokens[activeTokens.length - 1])
            : -1;

        btn.disabled = true;
        setWordCardLoading(true);
        setStatus('Recording practice…');
        output.innerHTML = '';
        output.hidden = false;

        try {
            const result = await apiPost(url, {
                page_number: parseInt(page.dataset.pageNumber || '1', 10),
                word_index_start: firstIndex >= 0 ? firstIndex : 0,
                word_index_end: lastIndex >= 0 ? lastIndex : 0,
                sentence_hash: simpleHash(phrase),
                self_rating: null,
            });
            output.innerHTML = `
                <div class="ai-tool-section">
                    <span class="ai-tool-label">Shadowing practice</span>
                    <p class="ai-tool-shadowing-ok">✓ Practice recorded</p>
                    <p class="ai-tool-attempts">Attempt <strong>#${escHtml(String(result.data.attempts_count))}</strong></p>
                    ${result.data.last_practiced_at ? `<small class="ai-tool-date">${new Date(result.data.last_practiced_at).toLocaleString()}</small>` : ''}
                </div>`;
            setStatus('');
        } catch (err) {
            if (err.upgrade_url) {
                setStatus(err.message || 'Upgrade to use this feature');
                showUpgrade();
            } else {
                setStatus(err.message || 'Shadowing unavailable.');
            }
        } finally {
            btn.disabled = false;
            setWordCardLoading(false);
        }
    });
};

const simpleHash = (str) => {
    let hash = 0;
    for (let i = 0; i < str.length; i++) {
        const char = str.charCodeAt(i);
        hash = ((hash << 5) - hash) + char;
        hash |= 0;
    }
    return Math.abs(hash).toString(36);
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
