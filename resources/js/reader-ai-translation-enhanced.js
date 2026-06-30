import {
    readerPage, wordCard, allTokens, findPhraseTokens,
    positionWordCard, setWordCardLoading, setStatus, showUpgrade,
} from './reader-ui.js';

const init = () => {
    const page = readerPage();
    if (!page) return;
    const url = page.dataset.contextExplainUrl || page.dataset.translationUrl;
    if (!url) return;

    const enableAiButtons = () => {
        wordCard()?.querySelectorAll('[data-ai-tool]').forEach((btn) => {
            btn.disabled = false;
        });
    };

    const observer = new MutationObserver(() => {
        const translation = wordCard()?.querySelector('[data-word-translation]');
        if (translation?.textContent?.trim()) {
            enableAiButtons();
            observer.disconnect();
        }
    });

    const translationText = wordCard()?.querySelector('[data-word-translation]');
    if (translationText?.textContent?.trim()) {
        enableAiButtons();
    } else if (wordCard()) {
        observer.observe(wordCard(), { childList: true, subtree: true, characterData: true });
    }
};

if (document.readyState !== 'loading') {
    init();
} else {
    document.addEventListener('DOMContentLoaded', init);
}
