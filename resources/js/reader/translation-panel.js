const state = {
    selectionId: null,
    activeTab: null,
    isSaved: false,
    requests: { context: null, grammar: null, simplify: null },
    results: { context: null, grammar: null, simplifications: {} },
    shadowing: { active: false, recorder: null, stream: null, blobUrl: null },
};

let wordCard = null;
let tabPanels = {};
let i18n = {};

const getTabBtn = (name) => wordCard?.querySelector(`[data-tab="${name}"]`);
const getTabPanel = (name) => wordCard?.querySelector(`[data-tab-panel="${name}"]`);

const escHtml = (str) => {
    const div = document.createElement('div');
    div.textContent = str;
    return div.innerHTML;
};

const closeTabPanel = () => {
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
    if (state.shadowing.active) return;
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

const showError = (tabName, error) => {
    const panel = tabPanels[tabName];
    if (!panel) return;
    const err = typeof error === 'object' ? error : { message: error.message || 'Service unavailable.', upgradeUrl: null, resetsAt: null };
    const title = i18n['error.title'] || 'Could not load the explanation';
    const subtitle = i18n['error.subtitle'] || 'The AI service is temporarily unavailable.';
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

const clearState = () => {
    Object.values(state.requests).forEach((ctrl) => ctrl?.abort());
    state.requests = { context: null, grammar: null, simplify: null };
    state.results = { context: null, grammar: null, simplifications: {} };
    state.activeTab = null;
    state.isSaved = false;
    delete state.selectionId;
    closeTabPanel();
    exitShadowing();
};

const setupShadowing = (shadowingPhrase) => {
    const recordBtn = wordCard?.querySelector('[data-shadowing-record]');
    const playBtn = wordCard?.querySelector('[data-shadowing-play]');
    const listenBtn = wordCard?.querySelector('[data-shadowing-listen]');
    const ratingBtns = wordCard?.querySelectorAll('[data-shadowing-rate]');
    const backBtn = wordCard?.querySelector('[data-shadowing-back]');
    const phraseNode = wordCard?.querySelector('[data-shadowing-phrase]');
    const ratingNode = wordCard?.querySelector('[data-shadowing-rating]');

    if (phraseNode) phraseNode.textContent = shadowingPhrase;
    if (recordBtn) {
        recordBtn.disabled = false;
        recordBtn.classList.remove('is-recording');
        recordBtn.querySelector('strong').textContent = 'Record';
    }
    if (playBtn) { playBtn.hidden = true; playBtn.disabled = true; }
    if (ratingNode) ratingNode.hidden = true;
    if (backBtn) backBtn.onclick = exitShadowing;
};

const enterShadowing = ({ phrase, locale, listenFn }) => {
    if (state.shadowing.active) return;
    state.shadowing.active = true;

    const studyTools = wordCard?.querySelector('[data-study-tools]');
    const shadowingMode = wordCard?.querySelector('[data-shadowing-mode]');
    const practiceBtn = wordCard?.querySelector('[data-practice-btn]');
    const translationBlock = wordCard?.querySelector('.word-card-translation');

    if (studyTools) studyTools.hidden = true;
    if (practiceBtn) practiceBtn.hidden = true;
    if (translationBlock) translationBlock.style.display = 'none';
    if (shadowingMode) shadowingMode.hidden = false;

    setupShadowing(phrase);

    const recordBtn = wordCard?.querySelector('[data-shadowing-record]');
    const playBtn = wordCard?.querySelector('[data-shadowing-play]');
    const listenBtn = wordCard?.querySelector('[data-shadowing-listen]');
    const ratingBtns = wordCard?.querySelectorAll('[data-shadowing-rate]');

    if (listenBtn) {
        listenBtn.onclick = () => listenFn(phrase, locale, listenBtn);
    }

    const Recognition = window.SpeechRecognition || window.webkitSpeechRecognition;
    if (recordBtn && Recognition) {
        const recognition = new Recognition();
        recognition.lang = locale;
        recognition.interimResults = false;
        recognition.maxAlternatives = 1;
        let recording = false;

        recordBtn.onclick = () => {
            if (recording) {
                recognition.stop();
                return;
            }
            recording = true;
            recordBtn.classList.add('is-recording');
            recordBtn.querySelector('strong').textContent = 'Stop';
            recognition.start();
        };

        recognition.addEventListener('result', (event) => {
            const transcript = event.results[0][0].transcript;
            if (playBtn) {
                playBtn.hidden = false;
                playBtn.disabled = false;
                playBtn.dataset.transcript = transcript;
            }
            if (ratingBtns.length) { ratingBtns.forEach((b) => b.classList.remove('is-selected')); }
            if (ratingNode) ratingNode.hidden = false;
        });

        recognition.addEventListener('end', () => {
            recording = false;
            if (recordBtn) {
                recordBtn.classList.remove('is-recording');
                recordBtn.querySelector('strong').textContent = 'Record again';
            }
        });

        recognition.addEventListener('error', () => {
            recording = false;
            if (recordBtn) {
                recordBtn.classList.remove('is-recording');
                recordBtn.querySelector('strong').textContent = 'Record';
            }
        });
    }

    ratingBtns.forEach((btn) => {
        btn.onclick = () => {
            ratingBtns.forEach((b) => b.classList.remove('is-selected'));
            btn.classList.add('is-selected');
        };
    });
};

const exitShadowing = () => {
    if (!state.shadowing.active) return;
    state.shadowing.active = false;

    const studyTools = wordCard?.querySelector('[data-study-tools]');
    const shadowingMode = wordCard?.querySelector('[data-shadowing-mode]');
    const practiceBtn = wordCard?.querySelector('[data-practice-btn]');
    const translationBlock = wordCard?.querySelector('.word-card-translation');

    if (studyTools) studyTools.hidden = false;
    if (practiceBtn) practiceBtn.hidden = false;
    if (translationBlock) translationBlock.style.display = '';
    if (shadowingMode) shadowingMode.hidden = true;

    if (state.shadowing.blobUrl) {
        URL.revokeObjectURL(state.shadowing.blobUrl);
        state.shadowing.blobUrl = null;
    }
    if (state.shadowing.stream) {
        state.shadowing.stream.getTracks().forEach((t) => t.stop());
        state.shadowing.stream = null;
    }
    if (state.shadowing.recorder && state.shadowing.recorder.state !== 'inactive') {
        state.shadowing.recorder.stop();
    }
    state.shadowing.recorder = null;
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
            clearState();
        });
    }
};

export {
    init, switchTab, closeTabPanel, clearState,
    showLoading, showResult, showError,
    enterShadowing, exitShadowing, state, i18n,
};
