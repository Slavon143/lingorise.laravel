import { stop as stopAudio } from './audio.js';
import { createPracticeRecorder } from './practice-recorder.js';

const state = {
    selectionId: null,
    activeTab: null,
    isSaved: false,
    requests: { context: null, grammar: null, simplify: null },
    results: { context: {}, grammar: {}, simplifications: {} },
    shadowing: { active: false, controller: null, triggerEnter: false },
};

let wordCard = null;
let tabPanels = {};
let i18n = {};
let lifecycleEventsBound = false;

const getTabBtn = (name) => wordCard?.querySelector(`[data-tab="${name}"]`);
const getTabPanel = (name) => wordCard?.querySelector(`[data-tab-panel="${name}"]`);

const escHtml = (str) => {
    const div = document.createElement('div');
    div.textContent = str;
    return div.innerHTML;
};

const closeTabPanel = () => {
    if (state.shadowing.active) {
        exitShadowing('tab_switch');
    }
    Object.keys(tabPanels).forEach((name) => {
        const panel = tabPanels[name];
        if (panel) panel.hidden = true;
    });
    if (state.activeTab) {
        const btn = getTabBtn(state.activeTab);
        if (btn) btn.setAttribute('aria-selected', 'false');
    }
    state.activeTab = null;
};

const switchTab = (tabName) => {
    if (state.activeTab === tabName) return;
    closeTabPanel();
    const panel = tabPanels[tabName];
    if (panel) panel.hidden = false;
    const btn = getTabBtn(tabName);
    if (btn) btn.setAttribute('aria-selected', 'true');
    state.activeTab = tabName;
};

const showLoading = (tabName, titleKey, subtitleKey) => {
    const panel = tabPanels[tabName];
    if (!panel) return;
    const title = i18n[titleKey] || 'Loading…';
    const subtitle = i18n[subtitleKey] || '';
    panel.innerHTML = `<div class="ai-loading-panel" role="status" aria-live="polite" aria-busy="true">
        <div class="ai-loading-spinner"></div>
        <span class="ai-loading-title">${escHtml(title)}</span>
        ${subtitle ? `<span class="ai-loading-subtitle">${escHtml(subtitle)}</span>` : ''}
    </div>`;
    panel.hidden = false;
    switchTab(tabName);
};

const showResult = (tabName, html) => {
    const panel = tabPanels[tabName];
    if (!panel) return;
    panel.innerHTML = `<div class="ai-tool-section">${html}</div>`;
    panel.hidden = false;
    switchTab(tabName);
};

const showResultNode = (tabName, node) => {
    const panel = tabPanels[tabName];
    if (!panel) return;
    const wrapper = document.createElement('div');
    wrapper.className = 'ai-tool-section';
    wrapper.appendChild(node);
    panel.replaceChildren(wrapper);
    panel.hidden = false;
    switchTab(tabName);
};

const showError = (tabName, error) => {
    const panel = tabPanels[tabName];
    if (!panel) return;
    const err = typeof error === 'object' ? error : { message: error.message || 'Service unavailable.', upgradeUrl: null, resetsAt: null };
    const title = i18n['error.title'] || 'Could not load the explanation';
    const subtitle = err.message || i18n['error.subtitle'] || 'The AI service is temporarily unavailable.';
    const tryAgain = i18n['error.try_again'] || 'Try again';
    let extra = '';
    if (err.upgradeUrl) {
        extra += `<a href="${escHtml(err.upgradeUrl)}" class="ai-error-upgrade">${escHtml(i18n['error.upgrade'] || 'Upgrade plan')}</a>`;
    }
    if (err.resetsAt) {
        const d = new Date(err.resetsAt);
        extra += `<span class="ai-error-resets">${escHtml(i18n['error.quota_exceeded'] || 'Resets at').replace(':time', d.toLocaleTimeString())}</span>`;
    }
    panel.innerHTML = `<div class="ai-error-panel">
        <span class="ai-loading-title">${escHtml(title)}</span>
        <span class="ai-loading-subtitle">${escHtml(subtitle)}</span>
        ${extra}
        <button type="button" class="ai-error-retry" data-retry>${escHtml(tryAgain)}</button>
    </div>`;
    panel.hidden = false;
    switchTab(tabName);

    const retryBtn = panel.querySelector('[data-retry]');
    if (retryBtn) {
        retryBtn.addEventListener('click', () => {
            const toolBtn = getTabBtn(tabName);
            if (toolBtn && !toolBtn.disabled) {
                toolBtn.click();
            }
        }, { once: true });
    }
};

const clearState = (reason = 'selection_change') => {
    Object.values(state.requests).forEach((ctrl) => ctrl?.abort());
    state.requests = { context: null, grammar: null, simplify: null };
    state.results = { context: {}, grammar: {}, simplifications: {} };
    state.activeTab = null;
    state.isSaved = false;
    delete state.selectionId;
    if (state.shadowing.active) {
        exitShadowing(reason);
    }
    closeTabPanel();
};

const enterShadowing = ({ phrase, locale, listenFn, rateFn }) => {
    if (state.shadowing.active) {
        state.shadowing.controller?.setPhrase(phrase, locale);
        return;
    }
    state.shadowing.active = true;

    const studyTools = wordCard?.querySelector('[data-study-tools]');
    const shadowingMode = wordCard?.querySelector('[data-shadowing-mode]');
    const practiceBtn = wordCard?.querySelector('[data-practice-btn]');
    const translationBlock = wordCard?.querySelector('.word-card-translation');

    if (studyTools) studyTools.hidden = true;
    if (practiceBtn) practiceBtn.hidden = true;
    if (translationBlock) translationBlock.style.display = 'none';
    if (shadowingMode) shadowingMode.hidden = false;

    const label = (key, fallback = '') => i18n[`practice.${key}`] || fallback;
    const titleNode = wordCard?.querySelector('[data-shadowing-title]');
    if (titleNode) titleNode.textContent = label('title', 'Practice pronunciation');

    state.shadowing.controller = createPracticeRecorder({
        elements: {
            root: shadowingMode,
            phraseNode: wordCard?.querySelector('[data-shadowing-phrase]'),
            listenBtn: wordCard?.querySelector('[data-shadowing-listen]'),
            startBtn: wordCard?.querySelector('[data-shadowing-record]'),
            startBtnLabel: wordCard?.querySelector('[data-shadowing-record-label]'),
            stopBtn: wordCard?.querySelector('[data-shadowing-stop]'),
            cancelBtn: wordCard?.querySelector('[data-shadowing-cancel]'),
            timerNode: wordCard?.querySelector('[data-shadowing-timer]'),
            statusNode: wordCard?.querySelector('[data-shadowing-status]'),
            localOnlyNode: wordCard?.querySelector('[data-shadowing-local-only]'),
            resultNode: wordCard?.querySelector('[data-shadowing-result]'),
            resultTitleNode: wordCard?.querySelector('[data-shadowing-result-title]'),
            playBtn: wordCard?.querySelector('[data-shadowing-play]'),
            pauseBtn: wordCard?.querySelector('[data-shadowing-pause]'),
            recordAgainBtn: wordCard?.querySelector('[data-shadowing-record-again]'),
            deleteBtn: wordCard?.querySelector('[data-shadowing-delete]'),
            ratingNode: wordCard?.querySelector('[data-shadowing-rating]'),
            ratingButtons: [...wordCard?.querySelectorAll('[data-shadowing-rate]') || []],
        },
        labels: {
            listen: label('listen', 'Listen'),
            start_recording: label('start_recording', 'Start recording'),
            stop: label('stop', 'Stop'),
            cancel: label('cancel', 'Cancel'),
            recording: label('recording', 'Recording'),
            your_recording: label('your_recording', 'Your recording'),
            play: label('play', 'Play'),
            pause: label('pause', 'Pause'),
            record_again: label('record_again', 'Record again'),
            delete: label('delete', 'Delete'),
            max_time_reached: label('max_time_reached', 'Maximum recording time reached. Recording stopped automatically.'),
            microphone_denied: label('microphone_denied', 'Microphone access was denied. Allow access in your browser settings.'),
            microphone_not_found: label('microphone_not_found', 'Microphone not found.'),
            microphone_busy: label('microphone_busy', 'Microphone is busy in another app or unavailable.'),
            secure_context_required: label('secure_context_required', 'Recording is only available over a secure HTTPS connection.'),
            recording_failed: label('recording_failed', 'Recording failed. Please try again.'),
            select_shorter_text: label('select_shorter_text', 'Select a shorter fragment for pronunciation practice.'),
            recording_local_only: label('recording_local_only', 'Your recording stays on this device and is not uploaded to the server.'),
        },
        phrase,
        locale,
        listenFn,
        rateFn,
        stopExternalAudio: stopAudio,
    });

    const backBtn = wordCard?.querySelector('[data-shadowing-back]');
    if (backBtn) backBtn.onclick = () => exitShadowing('close');
};

const exitShadowing = (reason = 'close') => {
    if (!state.shadowing.active) return;
    state.shadowing.active = false;
    state.shadowing.controller?.cleanupPracticeState({ reason });
    state.shadowing.controller = null;

    const studyTools = wordCard?.querySelector('[data-study-tools]');
    const shadowingMode = wordCard?.querySelector('[data-shadowing-mode]');
    const practiceBtn = wordCard?.querySelector('[data-practice-btn]');
    const translationBlock = wordCard?.querySelector('.word-card-translation');

    if (studyTools) studyTools.hidden = false;
    if (practiceBtn) practiceBtn.hidden = false;
    if (translationBlock) translationBlock.style.display = '';
    if (shadowingMode) shadowingMode.hidden = true;

};

const init = (card) => {
    wordCard = card;
    const tablist = wordCard?.querySelector('[data-tablist]');
    if (!tablist) return;

    i18n = {};
    try {
        const raw = wordCard.dataset.i18n;
        if (raw) i18n = JSON.parse(raw);
    } catch (e) { /* ignore */ }

    tabPanels = {};
    wordCard.querySelectorAll('[data-tab-panel]').forEach((panel) => {
        const name = panel.dataset.tabPanel;
        tabPanels[name] = panel;
        panel.hidden = true;
    });

    const tabs = [...tablist.querySelectorAll('[data-tab]')];
    tabs.forEach((btn) => {
        btn.addEventListener('click', () => {
            const tabName = btn.dataset.tab;
            switchTab(tabName);
        });
    });
    tablist.addEventListener('keydown', (event) => {
        const current = tabs.findIndex((b) => b === document.activeElement);
        if (current === -1) return;
        let next = current;
        if (event.key === 'ArrowRight' || event.key === 'ArrowDown') next = (current + 1) % tabs.length;
        else if (event.key === 'ArrowLeft' || event.key === 'ArrowUp') next = (current - 1 + tabs.length) % tabs.length;
        else if (event.key === 'Home') next = 0;
        else if (event.key === 'End') next = tabs.length - 1;
        else return;
        event.preventDefault();
        tabs[next].focus();
    });

    const practiceBtn = wordCard?.querySelector('[data-practice-btn]');
    if (practiceBtn) {
        practiceBtn.addEventListener('click', () => {
            if (practiceBtn.disabled) return;
            state.shadowing.triggerEnter = true;
        });
    }

    const closeBtn = wordCard?.querySelector('[data-close-word-card]');
    if (closeBtn) {
        closeBtn.addEventListener('click', () => {
            clearState('close');
        });
    }

    if (!lifecycleEventsBound) {
        lifecycleEventsBound = true;
        window.addEventListener('pagehide', () => clearState('page_hide'));
        window.addEventListener('beforeunload', () => clearState('page_hide'));
        document.addEventListener('visibilitychange', () => {
            if (document.hidden && state.shadowing.active) {
                clearState('page_hide');
            }
        });
    }
};

export {
    init, switchTab, closeTabPanel, clearState,
    showLoading, showResult, showResultNode, showError,
    enterShadowing, exitShadowing, state, i18n,
};
