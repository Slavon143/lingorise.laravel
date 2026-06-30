import './reader-ui.js';
import './reader-ai-translation-enhanced.js';
import './reader-context-explain.js';
import './reader-grammar-explain.js';
import './reader-simplify.js';
import './reader-shadowing.js';

import { playNaturalVoice, playBrowserVoice, stop as stopAudio } from './reader/audio.js';
import { showContextualMenu } from './reader/contextual-menu.js';
import { init as initPanel, clearState as clearPanelState, enterShadowing, state as panelState } from './reader/translation-panel.js';
import { apiPost } from './reader/api-client.js';
import { createPracticeRecorder } from './reader/practice-recorder.js';

const saveButton = document.querySelector('.save-word');

saveButton?.addEventListener('click', () => {
    saveButton.innerHTML = '<span>✓</span> Word saved';
    saveButton.classList.add('is-saved');
});

const filterButtons = document.querySelectorAll('.filter-chip');
const bookCards = document.querySelectorAll('.book-card');

filterButtons.forEach((button) => {
    button.addEventListener('click', () => {
        const filter = button.dataset.filter;

        filterButtons.forEach((item) => item.classList.remove('is-active'));
        button.classList.add('is-active');

        bookCards.forEach((card) => {
            const shouldShow = filter === 'all'
                || card.dataset.level === filter
                || card.dataset.level === 'all';

            card.classList.toggle('is-hidden', !shouldShow);
        });
    });
});

const recordButton = document.querySelector('.record-button');
const recordPanel = document.querySelector('.record-panel');
const recordTitle = document.querySelector('.record-state strong');
const recordHint = document.querySelector('.record-state small');

recordButton?.addEventListener('click', () => {
    const isRecording = recordButton.classList.toggle('is-recording');

    recordPanel?.classList.toggle('is-active', isRecording);
    recordButton.setAttribute('aria-label', isRecording ? 'Stop voice recording' : 'Start voice recording');

    if (recordTitle) {
        recordTitle.textContent = isRecording ? 'Listening…' : 'Your turn';
    }

    if (recordHint) {
        recordHint.textContent = isRecording ? 'Speak the phrase naturally' : 'Tap the microphone and repeat';
    }
});

document.querySelectorAll('.password-toggle').forEach((button) => {
    button.addEventListener('click', () => {
        const input = button.closest('.password-field')?.querySelector('input');

        if (!input) {
            return;
        }

        const showPassword = input.type === 'password';
        input.type = showPassword ? 'text' : 'password';
        button.textContent = showPassword ? 'Hide' : 'Show';
        button.setAttribute('aria-label', showPassword ? 'Hide password' : 'Show password');
    });
});

const languageModal = document.querySelector('[data-language-modal]');

document.querySelectorAll('[data-open-languages]').forEach((button) => {
    button.addEventListener('click', () => {
        if (languageModal) {
            languageModal.hidden = false;
        }
    });
});

document.querySelectorAll('[data-close-languages]').forEach((button) => {
    button.addEventListener('click', () => {
        if (languageModal) {
            languageModal.hidden = true;
        }
    });
});

const mobileMenuButton = document.querySelector('.mobile-menu-button');
const appSidebar = document.querySelector('.app-sidebar');
const appShell = document.querySelector('.app-shell');
const sidebarBackdrop = document.querySelector('[data-sidebar-backdrop]');
const sidebarCollapseButton = document.querySelector('[data-sidebar-collapse]');

mobileMenuButton?.addEventListener('click', () => {
    appSidebar?.classList.add('is-open');

    if (sidebarBackdrop) {
        sidebarBackdrop.hidden = false;
    }
});

const closeMobileSidebar = () => {
    appSidebar?.classList.remove('is-open');

    if (sidebarBackdrop) {
        sidebarBackdrop.hidden = true;
    }
};

sidebarBackdrop?.addEventListener('click', closeMobileSidebar);

document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape') {
        closeMobileSidebar();
    }
});

if (appShell && sidebarCollapseButton) {
    const sidebarCollapsed = localStorage.getItem('lingorise-sidebar-collapsed') === 'true';
    appShell.classList.toggle('is-sidebar-collapsed', sidebarCollapsed);
    sidebarCollapseButton.setAttribute('aria-label', sidebarCollapsed ? 'Expand sidebar' : 'Collapse sidebar');
    sidebarCollapseButton.setAttribute('title', sidebarCollapsed ? 'Expand sidebar' : 'Collapse sidebar');

    sidebarCollapseButton.addEventListener('click', () => {
        const collapsed = appShell.classList.toggle('is-sidebar-collapsed');
        localStorage.setItem('lingorise-sidebar-collapsed', String(collapsed));
        sidebarCollapseButton.setAttribute('aria-label', collapsed ? 'Expand sidebar' : 'Collapse sidebar');
        sidebarCollapseButton.setAttribute('title', collapsed ? 'Expand sidebar' : 'Collapse sidebar');
        const label = sidebarCollapseButton.querySelector('span');
        if (label) label.textContent = collapsed ? 'Expand' : 'Collapse';
    });
}

document.querySelectorAll('.app-nav a').forEach((link) => {
    link.addEventListener('click', closeMobileSidebar);
});

const adminSidebar = document.querySelector('[data-admin-sidebar]');
const adminMenuButton = document.querySelector('[data-admin-menu]');
const adminBackdrop = document.querySelector('[data-admin-backdrop]');

const closeAdminSidebar = () => {
    adminSidebar?.classList.remove('is-open');

    if (adminBackdrop) {
        adminBackdrop.hidden = true;
    }
};

adminMenuButton?.addEventListener('click', () => {
    adminSidebar?.classList.add('is-open');

    if (adminBackdrop) {
        adminBackdrop.hidden = false;
    }
});

adminBackdrop?.addEventListener('click', closeAdminSidebar);

document.querySelectorAll('.admin-nav a').forEach((link) => {
    link.addEventListener('click', closeAdminSidebar);
});

const languageNames = {
    de: 'German',
    ru: 'Russian',
    sv: 'Swedish',
    es: 'Spanish',
    fr: 'French',
    uk: 'Ukrainian',
    en: 'English',
};

document.querySelectorAll('[data-language-group]').forEach((group) => {
    group.addEventListener('change', (event) => {
        const input = event.target;

        if (!(input instanceof HTMLInputElement)) {
            return;
        }

        group.querySelectorAll('.language-option').forEach((option) => {
            option.classList.toggle('is-selected', option.contains(input));
        });

        const summarySelector = input.name === 'native_locale'
            ? '[data-native-summary]'
            : '[data-learning-summary]';
        const summary = document.querySelector(summarySelector);

        if (summary) {
            summary.textContent = languageNames[input.value] ?? input.value;
        }
    });
});

const bookFileInput = document.querySelector('.file-drop-zone input[type="file"]');
const bookFileName = document.querySelector('[data-file-name]');
const bookCreateForm = document.querySelector('[data-book-create-form]');
const bookAutofillStatus = document.querySelector('[data-file-autofill-status]');

const touchedBookFields = new Set();
let isBookAutofilling = false;

bookCreateForm?.querySelectorAll('[data-autofill-field]').forEach((field) => {
    field.addEventListener('input', () => {
        if (!isBookAutofilling) touchedBookFields.add(field.name);
    });
    field.addEventListener('change', () => {
        if (!isBookAutofilling) touchedBookFields.add(field.name);
    });
});

const titleFromFilename = (filename) => filename
    .replace(/\.[^.]+$/u, '')
    .replace(/[_-]+/gu, ' ')
    .replace(/\s+/gu, ' ')
    .trim()
    .replace(/\w\S*/gu, (part) => part.charAt(0).toLocaleUpperCase() + part.slice(1));

const fillBookField = (name, value, forceWhenDefault = false) => {
    if (!value || !bookCreateForm) return false;
    const field = bookCreateForm.querySelector(`[name="${name}"]`);
    if (!field || touchedBookFields.has(name)) return false;

    const current = field.value?.trim?.() ?? '';
    const defaultValues = {
        language_locale: 'en',
        level: 'A2',
        visibility: 'private',
    };
    const canFill = current === '' || (forceWhenDefault && current === defaultValues[name]);

    if (!canFill) return false;

    isBookAutofilling = true;
    field.value = value;
    field.dispatchEvent(new Event('change', { bubbles: true }));
    isBookAutofilling = false;
    return true;
};

bookFileInput?.addEventListener('change', () => {
    const file = bookFileInput.files?.[0];
    if (bookFileName) {
        bookFileName.textContent = file?.name ?? 'No file selected';
    }

    if (!file || !bookCreateForm) return;

    fillBookField('title', titleFromFilename(file.name));
    fillBookField('category', 'Fiction');
    fillBookField('language_locale', 'en', true);
    fillBookField('level', 'A2', true);
    fillBookField('visibility', 'private', true);

    if (bookAutofillStatus) {
        bookAutofillStatus.hidden = false;
        bookAutofillStatus.textContent = 'Detecting book details…';
    }

    const formData = new FormData();
    formData.append('book_file', file);

    fetch(bookCreateForm.dataset.bookMetadataUrl, {
        method: 'POST',
        headers: {
            'Accept': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? '',
        },
        body: formData,
    })
        .then(async (response) => {
            if (!response.ok) throw new Error('metadata failed');
            return response.json();
        })
        .then(({ metadata }) => {
            let filled = 0;
            filled += fillBookField('title', metadata?.title) ? 1 : 0;
            filled += fillBookField('author', metadata?.author) ? 1 : 0;
            filled += fillBookField('category', metadata?.category) ? 1 : 0;
            filled += fillBookField('language_locale', metadata?.language_locale, true) ? 1 : 0;
            filled += fillBookField('level', metadata?.level, true) ? 1 : 0;
            filled += fillBookField('visibility', metadata?.visibility, true) ? 1 : 0;

            if (bookAutofillStatus) {
                bookAutofillStatus.textContent = filled > 0
                    ? 'Book details filled automatically. You can edit them.'
                    : 'Filename used for title. You can edit the details.';
            }
        })
        .catch(() => {
            if (bookAutofillStatus) {
                bookAutofillStatus.textContent = 'Could not read metadata, but filename defaults were applied.';
            }
        });
});

const readerPage = document.querySelector('.reader-page');
const readingText = document.querySelector('[data-reading-text]');
const wordCard = document.querySelector('[data-word-card]');
const wordCardOverlay = document.querySelector('[data-word-card-overlay]');

if (readerPage && readingText && wordCard) {
    const selectedWord = wordCard.querySelector('[data-selected-word]');
    const selectionLabel = wordCard.querySelector('[data-selection-label]');
    const translationInput = wordCard.querySelector('[data-word-translation]');
    const contextNode = wordCard.querySelector('[data-word-context]');
    const statusNode = wordCard.querySelector('[data-word-status]');
    const saveButton = wordCard.querySelector('[data-save-word]');
    const speakButton = wordCard.querySelector('[data-speak-word]');
    const nativeLabel = wordCard.querySelector('[data-native-label]');
    const pronunciationNode = wordCard.querySelector('[data-word-pronunciation]');
    const explanationNode = wordCard.querySelector('[data-word-explanation]');
    const vocabularyList = document.querySelector('[data-vocabulary-list]');
    const vocabularyEmpty = document.querySelector('[data-vocabulary-empty]');
    const vocabularyCount = document.querySelector('[data-vocabulary-count]');
    const upgradeBtn = document.querySelector('[data-upgrade-btn]');
    let translationRequest = null;
    let activeToken = null;
    let activeTokens = [];
    let selectionAnchor = null;
    let suppressNextClick = false;
    let isProgrammaticScroll = false;
    let programmaticScrollTimer = null;
    const fontSelect = document.querySelector('[data-reader-font]');
    const fontAliases = {
        literata: 'kindle',
        merriweather: 'readera',
        georgia: 'apple',
        classic: 'apple',
    };
    const storedFont = localStorage.getItem('lingorise-reader-font-v2') ?? 'readera';
    const savedFont = fontAliases[storedFont] ?? storedFont;
    const readerCapabilities = (() => {
        try {
            return JSON.parse(readerPage.dataset.readerCapabilities || '{}');
        } catch {
            return {};
        }
    })();
    const translationMaxWords = Number(readerCapabilities?.limits?.translation_max_words || 10);

    readingText.dataset.font = savedFont;

    const setSaveButtonState = (enabled, label = 'Add to vocabulary') => {
        if (!saveButton) return;
        saveButton.disabled = !enabled;
        saveButton.textContent = label;
    };

    const languageNamesForTranslation = {
        de: 'German',
        ru: 'Russian',
        sv: 'Swedish',
        es: 'Spanish',
        fr: 'French',
        uk: 'Ukrainian',
        en: 'English',
    };
    if (nativeLabel) {
        const nativeLanguage = readerPage.dataset.nativeLanguage || '';
        nativeLabel.textContent = `${languageNamesForTranslation[nativeLanguage] || 'Your'} translation`;
    }

    if (fontSelect) {
        fontSelect.value = savedFont;
        fontSelect.addEventListener('change', () => {
            readingText.dataset.font = fontSelect.value;
            readingText.style.fontFamily = '';
            localStorage.setItem('lingorise-reader-font-v2', fontSelect.value);
        });
    }

    initPanel(wordCard);

    const practiceBtn = wordCard.querySelector('[data-practice-btn]');
    if (practiceBtn) {
        practiceBtn.addEventListener('click', () => {
            if (practiceBtn.disabled || !activeTokens.length) return;
            const phrase = activeTokens.map((t) => t.dataset.readerWord).join(' ');
            const locale = readerPage.dataset.bookLanguage || document.documentElement.lang || 'en';
            enterShadowing({
                phrase,
                locale,
                listenFn: (text, l, btn) => playNaturalVoice(text, l, btn),
                rateFn: async (rating) => {
                    await apiPost(readerPage.dataset.shadowingUrl, shadowingPayload(activeTokens, rating));
                    statusNode.textContent = 'Practice saved.';
                    positionWordCard();
                },
            });
        });
    }

    const closeWordCard = () => {
        wordCard.hidden = true;
        if (wordCardOverlay) wordCardOverlay.hidden = true;
        activeTokens.forEach((token) => token.classList.remove('is-selected'));
        activeToken = null;
        activeTokens = [];
        selectionAnchor = null;
        clearPanelState('close');
        stopAudio();
    };

    const positionWordCard = (force = false) => {
        if (!activeToken) return;
        if (!force && wordCard.hidden) return;
        const wasHidden = wordCard.hidden;
        if (wasHidden) wordCard.hidden = false; // force layout to measure offsetHeight

        const tokenRects = activeTokens.map((token) => token.getBoundingClientRect());
        if (!tokenRects.length) return;

        const selectionRect = {
            top: Math.min(...tokenRects.map((rect) => rect.top)),
            right: Math.max(...tokenRects.map((rect) => rect.right)),
            bottom: Math.max(...tokenRects.map((rect) => rect.bottom)),
            left: Math.min(...tokenRects.map((rect) => rect.left)),
        };
        const viewportPadding = 12;
        const gap = 13;
        const headerEdge = 80;
        const footerEdge = window.innerHeight - 84;
        const availableAbove = Math.max(0, selectionRect.top - headerEdge - gap);
        const availableBelow = Math.max(0, footerEdge - selectionRect.bottom - gap);
        const preferredMaxHeight = Math.max(180, Math.max(availableAbove, availableBelow));

        wordCard.style.maxHeight = `${Math.min(window.innerHeight - 110, preferredMaxHeight)}px`;

        const cardWidth = wordCard.offsetWidth;
        const cardHeight = wordCard.offsetHeight;
        const placeAbove = availableBelow < cardHeight && availableAbove > availableBelow;
        const anchorRect = placeAbove ? tokenRects[0] : tokenRects.at(-1);
        const tokenCenter = anchorRect.left + (anchorRect.width / 2);
        const left = Math.min(
            window.innerWidth - cardWidth - viewportPadding,
            Math.max(viewportPadding, tokenCenter - (cardWidth / 2)),
        );
        const top = placeAbove
            ? selectionRect.top - cardHeight - gap
            : selectionRect.bottom + gap;
        const arrowLeft = Math.min(cardWidth - 25, Math.max(18, tokenCenter - left - 7));

        wordCard.classList.toggle('is-above', placeAbove);
        wordCard.style.left = `${left}px`;
        wordCard.style.top = `${top}px`;
        wordCard.style.setProperty('--word-card-arrow-left', `${arrowLeft}px`);

        const positionedCard = wordCard.getBoundingClientRect();
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

            wordCard.classList.toggle('is-above', useAbove);
            wordCard.style.top = `${safeTop}px`;
        }
        if (wasHidden) wordCard.hidden = true;
    };

    const allTokens = [...readingText.querySelectorAll('.reader-token')];

    const normalizeReaderWord = (word) => word
        .toLocaleLowerCase()
        .replace(/[^\p{L}\p{N}'’-]+/gu, '')
        .trim();

    const findPhraseTokens = (phrase) => {
        const words = phrase
            .trim()
            .split(/\s+/u)
            .map(normalizeReaderWord)
            .filter(Boolean);
        if (!words.length) return [];

        for (let index = 0; index <= allTokens.length - words.length; index += 1) {
            const matches = words.every((word, offset) => (
                normalizeReaderWord(allTokens[index + offset].dataset.readerWord) === word
            ));
            if (matches) return allTokens.slice(index, index + words.length);
        }

        return [];
    };

    const simpleHash = (str) => {
        let hash = 0;
        for (let index = 0; index < str.length; index += 1) {
            hash = ((hash << 5) - hash) + str.charCodeAt(index);
            hash |= 0;
        }
        return Math.abs(hash).toString(36);
    };

    const shadowingPayload = (tokens, selfRating = null) => {
        const firstIndex = tokens.length ? allTokens.indexOf(tokens[0]) : -1;
        const lastIndex = tokens.length ? allTokens.indexOf(tokens.at(-1)) : -1;
        const phrase = tokens.map((token) => token.dataset.readerWord).join(' ');

        return {
            page_number: parseInt(readerPage.dataset.pageNumber || '1', 10),
            word_index_start: firstIndex >= 0 ? firstIndex : 0,
            word_index_end: lastIndex >= 0 ? lastIndex : 0,
            sentence_hash: simpleHash(phrase),
            self_rating: selfRating,
        };
    };

    const openTranslation = (tokens) => {
        const selectedTokens = tokens.slice(0, translationMaxWords);
        if (!selectedTokens.length) return;

        activeTokens.forEach((token) => token.classList.remove('is-selected'));
        activeTokens = selectedTokens;
        activeToken = selectedTokens[0];
        activeTokens.forEach((token) => token.classList.add('is-selected'));

        const firstIndex = allTokens.indexOf(selectedTokens[0]);
        const lastIndex = allTokens.indexOf(selectedTokens.at(-1));
        const context = tokens
            ? allTokens
            .slice(Math.max(0, firstIndex - 6), Math.min(allTokens.length, lastIndex + 7))
            .map((item) => item.textContent)
            .join(' ')
            : '';
        const phrase = selectedTokens.map((token) => token.dataset.readerWord).join(' ');

        const studyTools = wordCard.querySelector('[data-study-tools]');
        const tabPanelsContainer = wordCard.querySelector('[data-tab-panel]');
        selectionLabel.textContent = selectedTokens.length > 1 ? 'Selected phrase' : 'Selected word';
        selectedWord.textContent = phrase;
        selectedWord.className = 'selected-text';
        const wc = phrase.split(/\s+/u).length;
        if (wc <= 3) selectedWord.classList.add('selected-text--large');
        else if (wc <= 10) selectedWord.classList.add('selected-text--medium');
        else selectedWord.classList.add('selected-text--compact');
        contextNode.textContent = context;
        translationInput.textContent = '';
        pronunciationNode.hidden = true;
        explanationNode.hidden = true;
        statusNode.textContent = 'Translating…';
        setSaveButtonState(false, 'Waiting for translation');
        if (upgradeBtn) upgradeBtn.hidden = true;
        if (studyTools) studyTools.hidden = true;
        clearPanelState('selection_change');
        wordCard.querySelectorAll('[data-ai-tool]').forEach((btn) => { btn.disabled = true; });
        positionWordCard(true);
        wordCard.hidden = false;
        if (wordCardOverlay) wordCardOverlay.hidden = false;
        requestAnimationFrame(() => positionWordCard());

        translationRequest?.abort();
        translationRequest = new AbortController();
        wordCard.classList.add('is-loading');

        fetch(readerPage.dataset.translationUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? '',
            },
            body: JSON.stringify({
                word: phrase,
                context,
            }),
            signal: translationRequest.signal,
        })
            .then(async (response) => {
                const result = await response.json();
                if (!response.ok) {
                    if (result.upgrade_url) {
                        statusNode.textContent = result.message || 'Limit reached.';
                        setSaveButtonState(false, 'Limit reached');
                        if (upgradeBtn) upgradeBtn.hidden = false;
                        positionWordCard();
                        return;
                    }
                    throw new Error(result.message || 'Translation unavailable.');
                }
                return result;
            })
            .then((result) => {
                if (!result || activeTokens[0] !== selectedTokens[0]) return;
                translationInput.textContent = result.translation;
                pronunciationNode.textContent = result.pronunciation;
                pronunciationNode.hidden = !result.pronunciation;
                const explanationP = explanationNode.querySelector('p');
                if (explanationP) explanationP.textContent = result.explanation;
                explanationNode.hidden = !result.explanation;
                statusNode.textContent = '';
                setSaveButtonState(Boolean(result.translation), 'Add to vocabulary');
                const aiTools = wordCard.querySelector('[data-ai-tools]');
                if (studyTools) studyTools.hidden = false;
                wordCard.querySelectorAll('[data-ai-tool]').forEach((btn) => { btn.disabled = false; });
                const practiceBtn = wordCard.querySelector('[data-practice-btn]');
                if (practiceBtn) practiceBtn.disabled = false;
                positionWordCard();
            })
            .catch((error) => {
                if (error.name !== 'AbortError' && activeTokens[0] === selectedTokens[0]) {
                    statusNode.textContent = error.message;
                    setSaveButtonState(false, 'Translation unavailable');
                    positionWordCard();
                }
            })
            .finally(() => {
                if (activeTokens[0] === selectedTokens[0]) wordCard.classList.remove('is-loading');
            });
    };

    const finishProgrammaticScroll = () => {
        window.clearTimeout(programmaticScrollTimer);
        programmaticScrollTimer = window.setTimeout(() => {
            isProgrammaticScroll = false;
        }, 700);
    };

    const waitForWindowLoad = () => new Promise((resolve) => {
        if (document.readyState === 'complete') {
            resolve();
            return;
        }

        window.addEventListener('load', resolve, { once: true });
    });

    const waitForImages = async () => {
        const pendingImages = [...document.images]
            .filter((image) => !image.complete)
            .map((image) => new Promise((resolve) => {
                image.addEventListener('load', resolve, { once: true });
                image.addEventListener('error', resolve, { once: true });
            }));

        await Promise.all(pendingImages);
    };

    const waitForStableRect = (element, stableFrames = 5) => new Promise((resolve) => {
        let previous = null;
        let stable = 0;

        const check = () => {
            const rect = element.getBoundingClientRect();
            const current = [rect.top, rect.left, rect.width, rect.height].map((value) => Math.round(value * 10) / 10);

            if (previous && current.every((value, index) => value === previous[index])) {
                stable += 1;
            } else {
                stable = 0;
            }

            previous = current;

            if (stable >= stableFrames) {
                resolve();
                return;
            }

            requestAnimationFrame(check);
        };

        requestAnimationFrame(check);
    });

    const focusPhrase = readerPage.dataset.focusPhrase?.trim();
    if (focusPhrase) {
        const focusedTokens = findPhraseTokens(focusPhrase);
        if (focusedTokens.length) {
            selectionAnchor = focusedTokens[0];
            isProgrammaticScroll = true;
            focusedTokens.forEach((token) => token.classList.add('is-selected'));

            const revealFocusedPhrase = async () => {
                await waitForWindowLoad();
                if (document.fonts?.ready) {
                    await document.fonts.ready;
                }
                await waitForImages();
                await waitForStableRect(readingText);

                focusedTokens[0].scrollIntoView({ block: 'center', behavior: 'auto' });
                finishProgrammaticScroll();
                await waitForStableRect(focusedTokens[0]);
                await new Promise((resolve) => window.setTimeout(resolve, 250));

                openTranslation(focusedTokens);
                positionWordCard();
                finishProgrammaticScroll();
            };

            revealFocusedPhrase();
        }
    }

    readingText.addEventListener('click', (event) => {
        const token = event.target.closest('.reader-token');
        if (!token || !token.dataset.readerWord) return;

        if (suppressNextClick) {
            suppressNextClick = false;
            return;
        }

        if (event.shiftKey && selectionAnchor) {
            const start = allTokens.indexOf(selectionAnchor);
            const end = allTokens.indexOf(token);
            const from = Math.min(start, end);
            const to = Math.max(start, end);

            if (to - from + 1 > translationMaxWords) {
                statusNode.textContent = `Select up to ${translationMaxWords} words.`;
                openTranslation(allTokens.slice(from, from + translationMaxWords));
                return;
            }

            openTranslation(allTokens.slice(from, to + 1));
            return;
        }

        selectionAnchor = token;
        openTranslation([token]);
    });

    readingText.addEventListener('mouseup', () => {
        const selection = window.getSelection();
        if (!selection || selection.isCollapsed || !selection.rangeCount) return;

        const range = selection.getRangeAt(0);
        const selectedTokens = allTokens.filter((token) => range.intersectsNode(token));
        if (!selectedTokens.length) return;

        suppressNextClick = true;
        selectionAnchor = selectedTokens[0];
        openTranslation(selectedTokens.slice(0, translationMaxWords));

        if (selectedTokens.length > translationMaxWords) {
            statusNode.textContent = `Only the first ${translationMaxWords} words were selected.`;
        }

        selection.removeAllRanges();
    });

    readingText.addEventListener('keydown', (event) => {
        if ((event.key === 'Enter' || event.key === ' ') && event.target.matches('.reader-token')) {
            event.preventDefault();
            selectionAnchor = event.target;
            openTranslation([event.target]);
        }
    });

    vocabularyList?.addEventListener('click', (event) => {
        const item = event.target.closest('[data-vocabulary-original]');
        if (!item) return;

        const tokens = findPhraseTokens(item.dataset.vocabularyOriginal);
        if (tokens.length) {
            selectionAnchor = tokens[0];
            tokens[0].scrollIntoView({ block: 'center' });
            window.setTimeout(() => openTranslation(tokens), 0);
        }
    });

    wordCard.querySelector('[data-close-word-card]')?.addEventListener('click', closeWordCard);
    wordCardOverlay?.addEventListener('click', closeWordCard);
    speakButton?.addEventListener('click', async () => {
        if (!activeTokens.length) return;
        statusNode.textContent = '';
        const result = await playNaturalVoice(
            activeTokens.map((token) => token.dataset.readerWord).join(' '),
            readerPage.dataset.bookLanguage || document.documentElement.lang || 'en',
            speakButton,
        );

        if (!result.ok) {
            if (result.upgradeUrl && upgradeBtn) {
                upgradeBtn.hidden = false;
            }

            statusNode.textContent = result.message || 'Voice playback is unavailable.';
            positionWordCard();
        }
    });
    window.addEventListener('resize', positionWordCard);
    window.addEventListener('scroll', () => {
        if (isProgrammaticScroll) {
            finishProgrammaticScroll();
            return;
        }

        if (!wordCard.hidden) closeWordCard();
    }, { passive: true });
    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && !wordCard.hidden) {
            if (panelState.shadowing.active) {
                const backBtn = wordCard.querySelector('[data-shadowing-back]');
                backBtn?.click();
                return;
            }
            if (panelState.activeTab) {
                clearPanelState('tab_switch');
                return;
            }
            closeWordCard();
        }
    });
    document.addEventListener('pointerdown', (event) => {
        if (!wordCard.hidden && !wordCard.contains(event.target) && !event.target.closest('.reader-token')) {
            closeWordCard();
        }
    });

    saveButton?.addEventListener('click', async () => {
        const translation = translationInput.textContent.trim();

        if (!translation || !activeTokens.length) {
            statusNode.textContent = statusNode.textContent || 'Translation is not available yet.';
            return;
        }

        saveButton.disabled = true;
        saveButton.innerHTML = '<span>•</span> Saving…';
        statusNode.textContent = 'Saving…';
        let canEnableAfterSave = true;

        try {
            const response = await fetch(readerPage.dataset.vocabularyUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? '',
                },
                body: JSON.stringify({
                    original_text: activeTokens.map((token) => token.dataset.readerWord).join(' '),
                    translated_text: translation,
                    context: contextNode.textContent,
                }),
            });

            if (!response.ok) {
                const result = await response.json().catch(() => ({}));
                if (result.saved === false && result.upgrade_url) {
                    statusNode.innerHTML = 'Free limit reached. <a href="' + result.upgrade_url + '" style="color:var(--blue);text-decoration:underline;">Upgrade to Pro</a> for unlimited vocabulary.';
                    canEnableAfterSave = false;
                    setSaveButtonState(false, 'Limit reached');
                    return;
                }
                throw new Error('Save failed');
            }

            const result = await response.json();
            saveButton.textContent = 'Saved ✓';
            saveButton.disabled = true;
            panelState.isSaved = true;
            activeTokens.forEach((token) => token.classList.add('is-saved'));

            if (vocabularyList && result.entry) {
                const normalized = result.entry.original_text.toLocaleLowerCase();
                let item = [...vocabularyList.querySelectorAll('[data-vocabulary-original]')]
                    .find((node) => node.dataset.vocabularyOriginal.toLocaleLowerCase() === normalized);

                if (!item) {
                    item = document.createElement('button');
                    item.type = 'button';
                    item.className = 'reader-vocabulary-item';
                    item.dataset.vocabularyOriginal = result.entry.original_text;
                    item.innerHTML = '<strong></strong><span></span>';
                    vocabularyList.prepend(item);
                }

                item.querySelector('strong').textContent = result.entry.original_text;
                item.querySelector('span').textContent = result.entry.translated_text;
                vocabularyEmpty?.setAttribute('hidden', '');
                if (vocabularyCount) vocabularyCount.textContent = vocabularyList.children.length;
            }
        } catch {
            statusNode.textContent = 'Could not save the word.';
        } finally {
            if (panelState.isSaved) {
                saveButton.textContent = 'Saved ✓';
                saveButton.disabled = true;
            } else if (canEnableAfterSave && translationInput.textContent.trim()) {
                setSaveButtonState(true, 'Add to vocabulary');
            }
        }
    });

    document.querySelector('[data-reader-increase]')?.addEventListener('click', () => {
        const current = parseFloat(getComputedStyle(readingText).fontSize);
        readingText.style.fontSize = `${Math.min(25, current + 1)}px`;
    });

    document.querySelector('[data-reader-decrease]')?.addEventListener('click', () => {
        const current = parseFloat(getComputedStyle(readingText).fontSize);
        readingText.style.fontSize = `${Math.max(15, current - 1)}px`;
    });

    document.querySelector('[data-reader-theme]')?.addEventListener('click', () => {
        readerPage.classList.toggle('is-dark');
    });

    const readerPanelsButton = document.querySelector('[data-reader-panels]');
    const readerPanelsLabel = document.querySelector('[data-reader-panels-label]');
    const readerPanelBackdrop = document.querySelector('[data-reader-panel-backdrop]');

    readerPanelsButton?.addEventListener('click', () => {
        if (window.matchMedia('(max-width: 820px)').matches) {
            readerPage.classList.add('is-reader-panel-open');

            if (readerPanelBackdrop) {
                readerPanelBackdrop.hidden = false;
            }

            return;
        }

        const hidden = readerPage.classList.toggle('is-panels-hidden');
        localStorage.setItem('lingorise-reader-panels-hidden', String(hidden));
        readerPanelsButton.setAttribute('aria-label', hidden ? 'Show reading panels' : 'Hide reading panels');
        if (readerPanelsLabel) readerPanelsLabel.textContent = hidden ? 'Show panels' : 'Panels';
    });

    const closeReaderPanel = () => {
        readerPage.classList.remove('is-reader-panel-open');

        if (readerPanelBackdrop) {
            readerPanelBackdrop.hidden = true;
        }
    };

    readerPanelBackdrop?.addEventListener('click', closeReaderPanel);

    if (localStorage.getItem('lingorise-reader-panels-hidden') === 'true') {
        readerPage.classList.add('is-panels-hidden');
        readerPanelsButton?.setAttribute('aria-label', 'Show reading panels');
        if (readerPanelsLabel) readerPanelsLabel.textContent = 'Show panels';
    }

    readingText.addEventListener('contextmenu', (event) => {
        const token = event.target.closest('.reader-token');
        if (!token) return;
        event.preventDefault();

        if (wordCard.hidden || activeTokens[0] !== token) {
            selectionAnchor = token;
            openTranslation([token]);
        }

        const count = activeTokens.length;
        const type = count > 6 ? 'paragraph' : count > 3 ? 'sentence' : count > 1 ? 'phrase' : 'word';

        showContextualMenu(event.clientX, event.clientY, type, (action) => {
            if (action === 'translate') return;

            if (action === 'listen') {
                speakButton?.click();
                return;
            }

            if (action === 'save') {
                saveButton?.click();
                return;
            }

            if (action === 'shadowing') {
                practiceBtn?.click();
                return;
            }

            const btn = wordCard.querySelector(`[data-ai-tool="${action}"]`);
            if (btn) btn.click();
        });
    });
}

const speakingPractice = document.querySelector('[data-speaking-practice]');

if (speakingPractice) {
    const phrase = speakingPractice.dataset.speakingText;
    const locale = speakingPractice.dataset.speakingLocale || 'en';
    const listenButton = speakingPractice.querySelector('[data-speaking-listen]');
    const recorder = speakingPractice.querySelector('.speaking-recorder');
    const supportNode = speakingPractice.querySelector('[data-speaking-support]');
    const statusNode = speakingPractice.querySelector('[data-speaking-status]');
    const resultNode = speakingPractice.querySelector('[data-speaking-result]');
    const transcriptNode = speakingPractice.querySelector('[data-speaking-transcript]');
    const scoreNode = speakingPractice.querySelector('[data-speaking-score]');
    const feedbackNode = speakingPractice.querySelector('[data-speaking-feedback]');
    const Recognition = window.SpeechRecognition || window.webkitSpeechRecognition;
    let recognition = null;
    let recognitionStarted = false;
    let practiceLabels = {};

    try {
        practiceLabels = speakingPractice.dataset.practiceI18n
            ? JSON.parse(speakingPractice.dataset.practiceI18n)
            : {};
    } catch {
        practiceLabels = {};
    }

    const normalizeSpeech = (text) => text.toLocaleLowerCase()
        .replace(/[^\p{L}\p{N}\s]/gu, '')
        .replace(/\s+/g, ' ')
        .trim();

    const similarity = (expected, heard) => {
        const left = normalizeSpeech(expected);
        const right = normalizeSpeech(heard);
        const rows = Array.from({ length: left.length + 1 }, (_, index) => [index]);
        for (let column = 0; column <= right.length; column += 1) rows[0][column] = column;

        for (let row = 1; row <= left.length; row += 1) {
            for (let column = 1; column <= right.length; column += 1) {
                rows[row][column] = Math.min(
                    rows[row - 1][column] + 1,
                    rows[row][column - 1] + 1,
                    rows[row - 1][column - 1] + (left[row - 1] === right[column - 1] ? 0 : 1),
                );
            }
        }

        return Math.max(0, Math.round((1 - rows[left.length][right.length] / Math.max(left.length, right.length, 1)) * 100));
    };

    const listenToPhrase = async (text = phrase, l = locale, button = listenButton) => {
        const result = await playNaturalVoice(text, l, button);

        if (!result.ok && statusNode) {
            statusNode.hidden = false;
            statusNode.textContent = result.message || 'Voice playback is unavailable.';
        }
    };

    listenButton?.addEventListener('click', () => listenToPhrase(phrase, locale, listenButton));

    if (!Recognition) {
        statusNode.hidden = false;
        statusNode.textContent = 'Speech recognition is not supported in this browser, but you can still record and play back your voice.';
    } else {
        recognition = new Recognition();
        recognition.lang = locale;
        recognition.interimResults = false;
        recognition.maxAlternatives = 1;

        recognition.addEventListener('result', (event) => {
            const transcript = event.results[0][0].transcript;
            const score = similarity(phrase, transcript);
            transcriptNode.textContent = transcript;
            scoreNode.textContent = score;
            feedbackNode.textContent = score >= 85
                ? 'Excellent — the phrase was recognised clearly.'
                : score >= 60
                    ? 'Good start. Listen once more and repeat slowly.'
                    : 'Try again one part at a time.';
            resultNode.hidden = false;
        });

        recognition.addEventListener('end', () => {
            recognitionStarted = false;
        });

        let micBlocked = false;

        recognition.addEventListener('error', (event) => {
            if (event.error === 'not-allowed') {
                micBlocked = true;
                statusNode.hidden = false;
                statusNode.textContent = practiceLabels.microphone_denied || 'Microphone access was denied. Allow access in your browser settings.';
                return;
            }
            statusNode.hidden = false;
            statusNode.textContent = 'I could not hear that. Please try again.';
        });

        recognition.addEventListener('start', () => {
            micBlocked = false;
            recognitionStarted = true;
        });
    }

    const controller = createPracticeRecorder({
        elements: {
            root: recorder,
            phraseNode: null,
            listenBtn: speakingPractice.querySelector('[data-speaking-listen-inline]'),
            startBtn: speakingPractice.querySelector('[data-speaking-record]'),
            startBtnLabel: speakingPractice.querySelector('[data-speaking-record-label]'),
            stopBtn: speakingPractice.querySelector('[data-speaking-stop]'),
            cancelBtn: speakingPractice.querySelector('[data-speaking-cancel]'),
            timerNode: speakingPractice.querySelector('[data-speaking-timer]'),
            statusNode,
            localOnlyNode: supportNode,
            resultNode: speakingPractice.querySelector('[data-speaking-recording-result]'),
            resultTitleNode: speakingPractice.querySelector('[data-speaking-recording-result-title]'),
            playBtn: speakingPractice.querySelector('[data-speaking-play]'),
            pauseBtn: speakingPractice.querySelector('[data-speaking-pause]'),
            recordAgainBtn: speakingPractice.querySelector('[data-speaking-record-again]'),
            deleteBtn: speakingPractice.querySelector('[data-speaking-delete]'),
            ratingNode: null,
            ratingButtons: [],
        },
        labels: practiceLabels,
        phrase,
        locale,
        listenFn: listenToPhrase,
        stopExternalAudio: stopAudio,
        onRecordingStarted: () => {
            resultNode.hidden = true;
            if (recognition && !recognitionStarted) {
                try {
                    recognition.start();
                    supportNode.textContent = practiceLabels.recording_local_only || 'Your voice stays in this browser.';
                } catch {
                    // Recording playback still works when speech recognition is unavailable.
                }
            }
        },
        onRecordingStopped: (reason) => {
            if (recognition && recognitionStarted) {
                try { recognition.stop(); } catch { /* ignore */ }
            }
            if (!['manual', 'time_limit'].includes(reason)) {
                resultNode.hidden = true;
            }
        },
    });

    window.addEventListener('pagehide', () => controller.cleanupPracticeState({ reason: 'page_hide' }));
    window.addEventListener('beforeunload', () => controller.cleanupPracticeState({ reason: 'page_hide' }));
    document.addEventListener('visibilitychange', () => {
        if (document.hidden && controller.state.isRecording) {
            controller.cleanupPracticeState({ reason: 'page_hide' });
        }
    });
}
