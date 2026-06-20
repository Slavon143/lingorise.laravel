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

mobileMenuButton?.addEventListener('click', () => {
    appSidebar?.classList.toggle('is-open');
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
    let activeToken = null;

    const closeWordCard = () => {
        wordCard.hidden = true;
        activeToken?.classList.remove('is-selected');
        activeToken = null;
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
        const rect = token.getBoundingClientRect();
        const cardWidth = 285;
        const left = Math.min(window.innerWidth - cardWidth - 12, Math.max(12, rect.left - 42));

        selectedWord.textContent = token.dataset.readerWord;
        contextNode.textContent = context;
        translationInput.value = '';
        statusNode.textContent = '';
        wordCard.style.left = `${left}px`;
        wordCard.style.top = `${Math.min(window.innerHeight - 300, rect.bottom + 13)}px`;
        wordCard.hidden = false;
        translationInput.focus();
    });

    wordCard.querySelector('[data-close-word-card]')?.addEventListener('click', closeWordCard);

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
}
