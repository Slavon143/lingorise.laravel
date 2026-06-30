import { apiPostAudio, csrfToken } from './api-client.js';

let currentAudio = null;
let currentBlobUrl = null;

const cleanup = () => {
    if (currentAudio) {
        currentAudio.pause();
        currentAudio.removeEventListener('ended', cleanup);
        currentAudio = null;
    }
    if (currentBlobUrl) {
        URL.revokeObjectURL(currentBlobUrl);
        currentBlobUrl = null;
    }
};

const playNaturalVoice = async (text, locale = 'en', button = null) => {
    const speechUrl = document.body.dataset.speechUrl;
    if (!speechUrl) {
        return { ok: false, message: 'Voice playback is unavailable.' };
    }
    button?.classList.add('is-loading');
    try {
        const response = await apiPostAudio(speechUrl, { text, locale });
        cleanup();
        const blob = await response.blob();
        currentBlobUrl = URL.createObjectURL(blob);
        currentAudio = new Audio(currentBlobUrl);
        currentAudio.addEventListener('ended', () => {
            URL.revokeObjectURL(currentBlobUrl);
            currentBlobUrl = null;
            currentAudio = null;
        }, { once: true });
        await currentAudio.play();
        return { ok: true };
    } catch (err) {
        if (err.name === 'AbortError') {
            return { ok: false, aborted: true };
        }
        cleanup();
        return {
            ok: false,
            blocked: err.status === 403,
            message: err.message || 'Natural voice unavailable.',
            upgradeUrl: err.upgradeUrl,
        };
    } finally {
        button?.classList.remove('is-loading');
    }
};

const playBrowserVoice = (text, locale = 'en') => {
    if (!('speechSynthesis' in window)) return;
    window.speechSynthesis.cancel();
    const utterance = new SpeechSynthesisUtterance(text);
    utterance.lang = locale;
    utterance.rate = 0.88;
    window.speechSynthesis.speak(utterance);
};

const stop = () => {
    cleanup();
    if ('speechSynthesis' in window) {
        window.speechSynthesis.cancel();
    }
};

const isBrowserTtsAvailable = () => 'speechSynthesis' in window;

export {
    playNaturalVoice,
    playBrowserVoice,
    stop,
    cleanup,
    isBrowserTtsAvailable,
};
