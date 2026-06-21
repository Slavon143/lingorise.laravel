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

bookFileInput?.addEventListener('change', () => {
    if (bookFileName) {
        bookFileName.textContent = bookFileInput.files?.[0]?.name ?? 'No file selected';
    }
});

const readerPage = document.querySelector('.reader-page');
const readingText = document.querySelector('[data-reading-text]');
const wordCard = document.querySelector('[data-word-card]');

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
    let translationRequest = null;
    let activeToken = null;
    let activeTokens = [];
    let selectionAnchor = null;
    let suppressNextClick = false;
    const fontSelect = document.querySelector('[data-reader-font]');
    const fontAliases = {
        literata: 'kindle',
        merriweather: 'readera',
        georgia: 'apple',
        classic: 'apple',
    };
    const storedFont = localStorage.getItem('lingorise-reader-font-v2') ?? 'readera';
    const savedFont = fontAliases[storedFont] ?? storedFont;

    readingText.dataset.font = savedFont;
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

    const closeWordCard = () => {
        wordCard.hidden = true;
        activeTokens.forEach((token) => token.classList.remove('is-selected'));
        activeToken = null;
        activeTokens = [];
        selectionAnchor = null;
    };

    const positionWordCard = () => {
        if (!activeToken || wordCard.hidden) return;

        const firstRect = activeTokens[0]?.getBoundingClientRect();
        const lastRect = activeTokens.at(-1)?.getBoundingClientRect();
        if (!firstRect || !lastRect) return;
        const viewportPadding = 12;
        const gap = 13;
        const cardWidth = wordCard.offsetWidth;
        const cardHeight = wordCard.offsetHeight;
        const roomBelow = window.innerHeight - lastRect.bottom;
        const placeAbove = roomBelow < cardHeight + gap && firstRect.top > cardHeight + gap;
        const anchorRect = placeAbove ? firstRect : lastRect;
        const tokenCenter = anchorRect.left + (anchorRect.width / 2);
        const left = Math.min(
            window.innerWidth - cardWidth - viewportPadding,
            Math.max(viewportPadding, tokenCenter - (cardWidth / 2)),
        );
        const top = placeAbove
            ? Math.max(viewportPadding, firstRect.top - cardHeight - gap)
            : Math.min(window.innerHeight - cardHeight - viewportPadding, lastRect.bottom + gap);
        const arrowLeft = Math.min(cardWidth - 25, Math.max(18, tokenCenter - left - 7));

        wordCard.classList.toggle('is-above', placeAbove);
        wordCard.style.left = `${left}px`;
        wordCard.style.top = `${Math.max(viewportPadding, top)}px`;
        wordCard.style.setProperty('--word-card-arrow-left', `${arrowLeft}px`);
    };

    const allTokens = [...readingText.querySelectorAll('.reader-token')];

    const openTranslation = (tokens) => {
        const selectedTokens = tokens.slice(0, 10);
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

        selectionLabel.textContent = selectedTokens.length > 1 ? 'Selected phrase' : 'Selected word';
        selectedWord.textContent = phrase;
        contextNode.textContent = context;
        translationInput.textContent = '';
        pronunciationNode.hidden = true;
        explanationNode.hidden = true;
        statusNode.textContent = 'Translating…';
        wordCard.hidden = false;
        positionWordCard();

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
                if (!response.ok) throw new Error(result.message || 'Translation unavailable.');
                return result;
            })
            .then((result) => {
                if (activeTokens[0] !== selectedTokens[0]) return;
                translationInput.textContent = result.translation;
                pronunciationNode.textContent = result.pronunciation;
                pronunciationNode.hidden = !result.pronunciation;
                explanationNode.querySelector('p').textContent = result.explanation;
                explanationNode.hidden = !result.explanation;
                statusNode.textContent = '';
                positionWordCard();
            })
            .catch((error) => {
                if (error.name !== 'AbortError' && activeTokens[0] === selectedTokens[0]) {
                    statusNode.textContent = error.message;
                    positionWordCard();
                }
            })
            .finally(() => {
                if (activeTokens[0] === selectedTokens[0]) wordCard.classList.remove('is-loading');
            });
    };

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

            if (to - from + 1 > 10) {
                statusNode.textContent = 'Select up to 10 words.';
                openTranslation(allTokens.slice(from, from + 10));
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
        openTranslation(selectedTokens.slice(0, 10));

        if (selectedTokens.length > 10) {
            statusNode.textContent = 'Only the first 10 words were selected.';
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

    wordCard.querySelector('[data-close-word-card]')?.addEventListener('click', closeWordCard);
    speakButton?.addEventListener('click', () => {
        if (!activeTokens.length || !('speechSynthesis' in window)) return;

        window.speechSynthesis.cancel();
        const utterance = new SpeechSynthesisUtterance(activeTokens.map((token) => token.dataset.readerWord).join(' '));
        utterance.lang = document.documentElement.lang || 'en';
        window.speechSynthesis.speak(utterance);
    });
    window.addEventListener('resize', positionWordCard);
    window.addEventListener('scroll', positionWordCard, { passive: true });
    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && !wordCard.hidden) closeWordCard();
    });
    document.addEventListener('pointerdown', (event) => {
        if (!wordCard.hidden && !wordCard.contains(event.target) && !event.target.closest('.reader-token')) {
            closeWordCard();
        }
    });

    saveButton?.addEventListener('click', async () => {
        const translation = translationInput.textContent.trim();

        if (!translation || !activeTokens.length) {
            statusNode.textContent = 'Add a translation first.';
            return;
        }

        saveButton.disabled = true;
        statusNode.textContent = 'Saving…';

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
                throw new Error('Save failed');
            }

            statusNode.textContent = 'Saved to vocabulary ✓';
            activeTokens.forEach((token) => token.classList.add('is-saved'));
        } catch {
            statusNode.textContent = 'Could not save the word.';
        } finally {
            saveButton.disabled = false;
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
}
