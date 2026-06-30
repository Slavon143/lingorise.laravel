import { readerPage, apiPost, allTokens } from './reader-ui.js';

const simpleHash = (str) => {
    let hash = 0;
    for (let i = 0; i < str.length; i++) {
        const char = str.charCodeAt(i);
        hash = ((hash << 5) - hash) + char;
        hash |= 0;
    }
    return Math.abs(hash).toString(36);
};

const init = () => {
    const page = readerPage();
    if (!page) return;
    const btn = document.querySelector('[data-ai-tool="shadowing"]');
    const url = page.dataset.shadowingUrl;
    if (!btn || !url) return;

    btn.addEventListener('click', async () => {
        const selectedWord = document.querySelector('[data-selected-word]');
        const phrase = selectedWord?.textContent?.trim();
        if (!phrase) return;

        const tokens = allTokens();
        const activeTokens = document.querySelectorAll('.reader-token.is-selected');
        const firstIndex = activeTokens.length ? tokens.indexOf(activeTokens[0]) : -1;
        const lastIndex = activeTokens.length ? tokens.indexOf(activeTokens[activeTokens.length - 1]) : -1;

        btn.disabled = true;

        try {
            await apiPost(url, {
                page_number: parseInt(page.dataset.pageNumber || '1', 10),
                word_index_start: firstIndex >= 0 ? firstIndex : 0,
                word_index_end: lastIndex >= 0 ? lastIndex : 0,
                sentence_hash: simpleHash(phrase),
                self_rating: null,
            });
        } catch {
            // silent — practice btn opens shadowing mode regardless
        } finally {
            btn.disabled = false;
        }
    });
};

if (document.readyState !== 'loading') {
    init();
} else {
    document.addEventListener('DOMContentLoaded', init);
}
