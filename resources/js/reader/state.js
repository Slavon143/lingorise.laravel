let currentSelection = null;
let abortController = null;

export const selectionState = () => currentSelection;

export const setSelection = (sel) => {
    currentSelection = sel;
    return currentSelection;
};

export const clearSelection = () => {
    currentSelection = null;
    return currentSelection;
};

export const getAbortController = () => abortController;

export const cancelPendingRequest = () => {
    if (abortController) {
        abortController.abort();
    }
    abortController = null;
};

export const createAbortController = () => {
    cancelPendingRequest();
    abortController = new AbortController();
    return abortController;
};

export const determineSelectionType = (wordCount) => {
    if (wordCount <= 1) return 'word';
    if (wordCount <= 10) return 'phrase';
    return 'paragraph';
};

export const buildSelection = (text, normalizedText, context, tokens, pageNumber) => {
    const wordCount = tokens.length;
    return {
        text,
        normalizedText,
        context,
        wordCount,
        pageNumber,
        wordIndexStart: tokens.length > 0 ? parseInt(tokens[0].dataset.wordIndex, 10) : null,
        wordIndexEnd: tokens.length > 0 ? parseInt(tokens[tokens.length - 1].dataset.wordIndex, 10) : null,
        sourceLanguage: null,
        selectionType: determineSelectionType(wordCount),
        tokens,
    };
};
