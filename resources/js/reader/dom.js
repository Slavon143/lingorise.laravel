export const readerPage = () => document.querySelector('.reader-page');
export const readingText = () => document.querySelector('[data-reading-text]');
export const wordCard = () => document.querySelector('[data-word-card]');
export const allTokens = () => readingText() ? [...readingText().querySelectorAll('.reader-token')] : [];
export const statusNode = () => wordCard()?.querySelector('[data-word-status]');
export const upgradeBtn = () => wordCard()?.querySelector('[data-upgrade-btn]');
export const aiOutput = () => wordCard()?.querySelector('[data-ai-output]');
export const aiTools = () => wordCard()?.querySelector('[data-ai-tools]');

export const escHtml = (str) => {
    const div = document.createElement('div');
    div.textContent = str;
    return div.innerHTML;
};

export const toggleLoading = (loading) => {
    wordCard()?.classList.toggle('is-loading', loading);
};

export const setStatus = (message) => {
    const el = statusNode();
    if (el) el.textContent = message;
};

export const showUpgrade = () => {
    const el = upgradeBtn();
    if (el) el.hidden = false;
};

export const hideAiOutput = () => {
    const el = aiOutput();
    if (el) {
        el.innerHTML = '';
        el.hidden = true;
    }
};

export const disableAiTools = (disabled = true) => {
    wordCard()?.querySelectorAll('[data-ai-tool]').forEach((btn) => {
        btn.disabled = disabled;
    });
};

export const enableAiToolsAfterTranslation = () => {
    const translation = wordCard()?.querySelector('[data-word-translation]');
    if (translation?.textContent?.trim()) {
        disableAiTools(false);
    }
};

export const normalizeReaderWord = (word) => word
    .toLocaleLowerCase()
    .replace(/[^\p{L}\p{N}'’-]+/gu, '')
    .trim();

export const findPhraseTokens = (phrase) => {
    const words = phrase.trim().split(/\s+/u).map(normalizeReaderWord).filter(Boolean);
    if (!words.length) return [];
    const tokens = allTokens();
    for (let index = 0; index <= tokens.length - words.length; index += 1) {
        const matches = words.every((word, offset) => (
            normalizeReaderWord(tokens[index + offset]?.dataset.readerWord ?? '') === word
        ));
        if (matches) return tokens.slice(index, index + words.length);
    }
    return [];
};

export const positionWordCard = (force = false) => {
    const card = wordCard();
    if (!card) return;
    if (!force && card.hidden) return;
    const wasHidden = card.hidden;
    if (wasHidden) card.hidden = false; // force layout to measure offsetHeight
    const activeTokens = card.querySelectorAll('.reader-token.is-selected');
    if (!activeTokens.length) {
        if (wasHidden) card.hidden = true;
        return;
    }
    const tokenRects = [...activeTokens].map((t) => t.getBoundingClientRect());
    const selectionRect = {
        top: Math.min(...tokenRects.map((r) => r.top)),
        right: Math.max(...tokenRects.map((r) => r.right)),
        bottom: Math.max(...tokenRects.map((r) => r.bottom)),
        left: Math.min(...tokenRects.map((r) => r.left)),
    };
    const viewportPadding = 12;
    const gap = 13;
    const headerEdge = 80;
    const footerEdge = window.innerHeight - 84;
    const availableAbove = Math.max(0, selectionRect.top - headerEdge - gap);
    const availableBelow = Math.max(0, footerEdge - selectionRect.bottom - gap);
    const preferredMaxHeight = Math.max(180, Math.max(availableAbove, availableBelow));
    card.style.maxHeight = `${Math.min(window.innerHeight - 110, preferredMaxHeight)}px`;
    const cardWidth = card.offsetWidth;
    const cardHeight = card.offsetHeight;
    const placeAbove = availableBelow < cardHeight && availableAbove > availableBelow;
    const anchorRect = placeAbove ? tokenRects[0] : tokenRects[tokenRects.length - 1];
    const tokenCenter = anchorRect.left + (anchorRect.width / 2);
    const left = Math.min(
        window.innerWidth - cardWidth - viewportPadding,
        Math.max(viewportPadding, tokenCenter - (cardWidth / 2)),
    );
    const top = placeAbove
        ? selectionRect.top - cardHeight - gap
        : selectionRect.bottom + gap;
    const arrowLeft = Math.min(cardWidth - 25, Math.max(18, tokenCenter - left - 7));
    card.classList.toggle('is-above', placeAbove);
    card.style.left = `${left}px`;
    card.style.top = `${top}px`;
    card.style.setProperty('--word-card-arrow-left', `${arrowLeft}px`);
    const positionedCard = card.getBoundingClientRect();
    const overlapsSelection = !(
        positionedCard.bottom <= selectionRect.top - gap
        || positionedCard.top >= selectionRect.bottom + gap
        || positionedCard.right <= selectionRect.left
        || positionedCard.left >= selectionRect.right
    );
    if (overlapsSelection) {
        const useAbove = availableAbove >= availableBelow;
        const safeTop = useAbove
            ? Math.max(headerEdge, selectionRect.top - positionedCard.height - gap)
            : Math.min(footerEdge - positionedCard.height, selectionRect.bottom + gap);
        card.classList.toggle('is-above', useAbove);
        card.style.top = `${safeTop}px`;
    }
    if (wasHidden) card.hidden = true;
};
