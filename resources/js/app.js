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
    const translationInput = wordCard.querySelector('[data-word-translation]');
    const contextNode = wordCard.querySelector('[data-word-context]');
    const statusNode = wordCard.querySelector('[data-word-status]');
    const saveButton = wordCard.querySelector('[data-save-word]');
    const speakButton = wordCard.querySelector('[data-speak-word]');
    const nativeLabel = wordCard.querySelector('[data-native-label]');
    let activeToken = null;
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
    if (nativeLabel) nativeLabel.textContent = `${readerPage.dataset.nativeLanguage || 'Native language'} translation`;

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
        activeToken?.classList.remove('is-selected');
        activeToken = null;
    };

    const positionWordCard = () => {
        if (!activeToken || wordCard.hidden) return;

        const rect = activeToken.getBoundingClientRect();
        const viewportPadding = 12;
        const gap = 13;
        const cardWidth = wordCard.offsetWidth;
        const cardHeight = wordCard.offsetHeight;
        const tokenCenter = rect.left + (rect.width / 2);
        const left = Math.min(
            window.innerWidth - cardWidth - viewportPadding,
            Math.max(viewportPadding, tokenCenter - (cardWidth / 2)),
        );
        const roomBelow = window.innerHeight - rect.bottom;
        const placeAbove = roomBelow < cardHeight + gap && rect.top > cardHeight + gap;
        const top = placeAbove
            ? Math.max(viewportPadding, rect.top - cardHeight - gap)
            : Math.min(window.innerHeight - cardHeight - viewportPadding, rect.bottom + gap);
        const arrowLeft = Math.min(cardWidth - 25, Math.max(18, tokenCenter - left - 7));

        wordCard.classList.toggle('is-above', placeAbove);
        wordCard.style.left = `${left}px`;
        wordCard.style.top = `${Math.max(viewportPadding, top)}px`;
        wordCard.style.setProperty('--word-card-arrow-left', `${arrowLeft}px`);
    };

    readingText.addEventListener('click', (event) => {
        const token = event.target.closest('.reader-token');

        if (!token || !token.dataset.readerWord) {
            return;
        }

        activeToken?.classList.remove('is-selected');
        activeToken = token;
        token.classList.add('is-selected');

        const tokens = [...readingText.querySelectorAll('.reader-token')];
        const index = tokens.indexOf(token);
        const context = tokens
            .slice(Math.max(0, index - 6), Math.min(tokens.length, index + 7))
            .map((item) => item.textContent)
            .join(' ');
        selectedWord.textContent = token.dataset.readerWord;
        contextNode.textContent = context;
        translationInput.value = '';
        statusNode.textContent = '';
        wordCard.hidden = false;
        positionWordCard();
        translationInput.focus();
    });

    wordCard.querySelector('[data-close-word-card]')?.addEventListener('click', closeWordCard);
    speakButton?.addEventListener('click', () => {
        if (!activeToken || !('speechSynthesis' in window)) return;

        window.speechSynthesis.cancel();
        const utterance = new SpeechSynthesisUtterance(activeToken.dataset.readerWord);
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
        const translation = translationInput.value.trim();

        if (!translation || !activeToken) {
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
                    original_text: activeToken.dataset.readerWord,
                    translated_text: translation,
                    context: contextNode.textContent,
                }),
            });

            if (!response.ok) {
                throw new Error('Save failed');
            }

            statusNode.textContent = 'Saved to vocabulary ✓';
            activeToken.classList.add('is-saved');
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
