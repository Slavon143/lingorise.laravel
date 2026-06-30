const csrfToken = () => document.querySelector('meta[name="csrf-token"]')?.content ?? '';
const readerPage = () => document.querySelector('.reader-page');
const readingText = () => document.querySelector('[data-reading-text]');
const wordCard = () => document.querySelector('[data-word-card]');
const allTokens = () => readingText() ? [...readingText().querySelectorAll('.reader-token')] : [];
const statusNode = () => wordCard()?.querySelector('[data-word-status]');
const upgradeBtn = () => wordCard()?.querySelector('[data-upgrade-btn]');

const normalizeReaderWord = (word) => word
    .toLocaleLowerCase()
    .replace(/[^\p{L}\p{N}'’-]+/gu, '')
    .trim();

const findPhraseTokens = (phrase) => {
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

const positionWordCard = () => {
    if (!readerPage() || !wordCard()) return;
    const activeToken = wordCard().querySelector('[data-selected-word]');
    if (!activeToken || wordCard().hidden) return;
    const activeTokens = wordCard().querySelectorAll('.reader-token.is-selected');
    if (!activeTokens.length) return;
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
    wordCard().style.maxHeight = `${Math.min(window.innerHeight - 110, preferredMaxHeight)}px`;
    const cardWidth = wordCard().offsetWidth;
    const cardHeight = wordCard().offsetHeight;
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
    wordCard().classList.toggle('is-above', placeAbove);
    wordCard().style.left = `${left}px`;
    wordCard().style.top = `${top}px`;
    wordCard().style.setProperty('--word-card-arrow-left', `${arrowLeft}px`);
    const positionedCard = wordCard().getBoundingClientRect();
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
        wordCard().classList.toggle('is-above', useAbove);
        wordCard().style.top = `${safeTop}px`;
    }
};

const setWordCardLoading = (loading) => {
    wordCard()?.classList.toggle('is-loading', loading);
};

const setStatus = (message) => {
    if (statusNode()) statusNode().textContent = message;
    positionWordCard();
};

const showUpgrade = () => {
    if (upgradeBtn()) upgradeBtn().hidden = false;
    positionWordCard();
};

const apiPost = async (url, body) => {
    const response = await fetch(url, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': csrfToken(),
        },
        body: JSON.stringify(body),
    });
    const result = await response.json();
    if (!response.ok) throw result;
    return result;
};

export {
    csrfToken,
    readerPage,
    readingText,
    wordCard,
    allTokens,
    statusNode,
    normalizeReaderWord,
    findPhraseTokens,
    positionWordCard,
    setWordCardLoading,
    setStatus,
    showUpgrade,
    apiPost,
};
