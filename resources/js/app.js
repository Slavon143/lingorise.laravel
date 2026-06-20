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
