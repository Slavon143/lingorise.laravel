const STOP_REASONS = [
    'manual',
    'time_limit',
    'cancel',
    'close',
    'tab_switch',
    'selection_change',
    'page_hide',
    'recorder_error',
    'permission_lost',
    'new_recording',
];

const SAVE_REASONS = ['manual', 'time_limit'];
const MIME_TYPES = [
    'audio/webm;codecs=opus',
    'audio/webm',
    'audio/ogg;codecs=opus',
];

const defaultLabels = {
    listen: 'Listen',
    start_recording: 'Start recording',
    stop: 'Stop',
    cancel: 'Cancel',
    recording: 'Recording',
    your_recording: 'Your recording',
    play: 'Play',
    pause: 'Pause',
    record_again: 'Record again',
    delete: 'Delete',
    max_time_reached: 'Maximum recording time reached. Recording stopped automatically.',
    microphone_denied: 'Microphone access was denied. Allow access in your browser settings.',
    microphone_not_found: 'Microphone not found.',
    microphone_busy: 'Microphone is busy in another app or unavailable.',
    secure_context_required: 'Recording is only available over a secure HTTPS connection.',
    recording_failed: 'Recording failed. Please try again.',
    select_shorter_text: 'Select a shorter fragment for pronunciation practice.',
    recording_local_only: 'Your recording stays on this device and is not uploaded to the server.',
};

const createPracticeState = () => ({
    mediaStream: null,
    mediaRecorder: null,
    chunks: [],
    recordingBlob: null,
    recordingUrl: null,
    recordingAudio: null,
    isRecording: false,
    startedAt: null,
    elapsedMs: 0,
    maxDurationMs: 30000,
    timerInterval: null,
    autoStopTimer: null,
    stopReason: null,
    isStopping: false,
});

const countWords = (text) => (text || '').trim().split(/\s+/u).filter(Boolean).length;

const maxDurationForText = (text) => {
    const words = countWords(text);
    if (words <= 1) return 15000;
    if (words <= 10) return 30000;
    if (words <= 30) return 45000;
    return null;
};

const formatTime = (ms) => {
    const totalSeconds = Math.max(0, Math.floor(ms / 1000));
    const minutes = String(Math.floor(totalSeconds / 60)).padStart(2, '0');
    const seconds = String(totalSeconds % 60).padStart(2, '0');
    return `${minutes}:${seconds}`;
};

const chooseMimeType = (MediaRecorderClass) => {
    if (!MediaRecorderClass?.isTypeSupported) return null;
    return MIME_TYPES.find((type) => MediaRecorderClass.isTypeSupported(type)) || null;
};

const stopTracks = (stream) => {
    stream?.getTracks?.().forEach((track) => track.stop());
};

const microphoneMessage = (error, labels) => {
    const name = error?.name;
    if (name === 'NotAllowedError') return labels.microphone_denied;
    if (name === 'NotFoundError') return labels.microphone_not_found;
    if (name === 'NotReadableError') return labels.microphone_busy;
    if (name === 'SecurityError') return labels.secure_context_required;
    if (name === 'AbortError') return labels.recording_failed;
    return labels.recording_failed;
};

const createPracticeRecorder = ({
    elements,
    labels = {},
    phrase = '',
    locale = 'en',
    listenFn = null,
    rateFn = null,
    stopExternalAudio = null,
    onRecordingStarted = null,
    onRecordingStopped = null,
    env = globalThis,
} = {}) => {
    const l = { ...defaultLabels, ...labels };
    const state = createPracticeState();
    let currentPhrase = phrase;
    let currentLocale = locale;

    const setText = (node, text) => {
        if (node) node.textContent = text;
    };

    const setHidden = (node, hidden) => {
        if (node) node.hidden = hidden;
    };

    const setDisabled = (node, disabled) => {
        if (node) node.disabled = disabled;
    };

    const setStatus = (message = '', type = '') => {
        if (!elements?.statusNode) return;
        elements.statusNode.textContent = message;
        elements.statusNode.dataset.statusType = type;
        elements.statusNode.hidden = !message;
    };

    const stopRecordingAudio = () => {
        if (state.recordingAudio) {
            state.recordingAudio.pause?.();
            state.recordingAudio.currentTime = 0;
            state.recordingAudio = null;
        }
    };

    const revokeRecordingUrl = () => {
        if (state.recordingUrl) {
            env.URL?.revokeObjectURL?.(state.recordingUrl);
            state.recordingUrl = null;
        }
    };

    const updateTimer = () => {
        if (state.isRecording && state.startedAt) {
            state.elapsedMs = Date.now() - state.startedAt;
        }
        setText(elements?.timerNode, `${l.recording} ${formatTime(state.elapsedMs)} / ${formatTime(state.maxDurationMs)}`);
    };

    const setIdleUi = () => {
        elements?.root?.classList?.remove('is-recording');
        setHidden(elements?.startBtn, false);
        setDisabled(elements?.startBtn, false);
        elements?.startBtn?.setAttribute?.('aria-pressed', 'false');
        setText(elements?.startBtnLabel, state.recordingUrl ? l.record_again : l.start_recording);
        setHidden(elements?.stopBtn, true);
        setDisabled(elements?.stopBtn, true);
        setHidden(elements?.cancelBtn, true);
        setDisabled(elements?.cancelBtn, true);
        setDisabled(elements?.listenBtn, false);
        setDisabled(elements?.playBtn, !state.recordingUrl);
        setDisabled(elements?.pauseBtn, !state.recordingUrl);
        setDisabled(elements?.recordAgainBtn, !state.recordingUrl);
        setDisabled(elements?.deleteBtn, !state.recordingUrl);
        updateTimer();
    };

    const setRecordingUi = () => {
        elements?.root?.classList?.add('is-recording');
        setHidden(elements?.startBtn, true);
        setDisabled(elements?.startBtn, true);
        elements?.startBtn?.setAttribute?.('aria-pressed', 'true');
        setHidden(elements?.stopBtn, false);
        setDisabled(elements?.stopBtn, false);
        setHidden(elements?.cancelBtn, false);
        setDisabled(elements?.cancelBtn, false);
        setDisabled(elements?.listenBtn, true);
        setDisabled(elements?.playBtn, true);
        setDisabled(elements?.pauseBtn, true);
        setDisabled(elements?.recordAgainBtn, true);
        setDisabled(elements?.deleteBtn, true);
        setHidden(elements?.resultNode, true);
        setHidden(elements?.ratingNode, true);
        updateTimer();
    };

    const showRecordingResult = () => {
        setText(elements?.resultTitleNode, l.your_recording);
        setText(elements?.playBtn, l.play);
        setText(elements?.pauseBtn, l.pause);
        setText(elements?.recordAgainBtn, l.record_again);
        setText(elements?.deleteBtn, l.delete);
        setHidden(elements?.resultNode, !state.recordingUrl);
        setHidden(elements?.ratingNode, !state.recordingUrl);
    };

    const resetRecordingData = ({ revokeUrl = true } = {}) => {
        stopRecordingAudio();
        if (revokeUrl) revokeRecordingUrl();
        state.chunks = [];
        state.recordingBlob = null;
        setHidden(elements?.resultNode, true);
        setHidden(elements?.ratingNode, true);
    };

    const clearTimers = () => {
        if (state.autoStopTimer) {
            env.clearTimeout?.(state.autoStopTimer);
            state.autoStopTimer = null;
        }
        if (state.timerInterval) {
            env.clearInterval?.(state.timerInterval);
            state.timerInterval = null;
        }
    };

    const finalizeStop = (reason) => {
        const shouldSave = SAVE_REASONS.includes(reason);
        clearTimers();
        stopTracks(state.mediaStream);
        state.mediaStream = null;
        state.mediaRecorder = null;
        state.isRecording = false;
        state.isStopping = false;
        state.startedAt = null;
        state.stopReason = reason;

        if (shouldSave && state.chunks.length > 0) {
            const type = state.chunks[0]?.type || 'audio/webm';
            state.recordingBlob = new env.Blob(state.chunks, { type });
            revokeRecordingUrl();
            state.recordingUrl = env.URL?.createObjectURL?.(state.recordingBlob) || null;
            state.chunks = [];
            showRecordingResult();
            if (reason === 'time_limit') setStatus(l.max_time_reached, 'warning');
        } else {
            resetRecordingData({ revokeUrl: !shouldSave });
        }

        setIdleUi();
        onRecordingStopped?.(reason, state);
    };

    const stopRecording = (reason = 'manual') => {
        const stopReason = STOP_REASONS.includes(reason) ? reason : 'manual';
        if (state.isStopping) return;
        state.stopReason = stopReason;
        state.isStopping = true;
        clearTimers();

        const recorder = state.mediaRecorder;
        if (recorder && recorder.state !== 'inactive') {
            const onStop = () => finalizeStop(stopReason);
            recorder.addEventListener?.('stop', onStop, { once: true });
            try {
                recorder.stop();
            } catch {
                finalizeStop('recorder_error');
            }
        } else {
            finalizeStop(stopReason);
        }

        stopTracks(state.mediaStream);
    };

    const cancelRecording = () => {
        stopRecording('cancel');
        resetRecordingData();
        setStatus('');
    };

    const startTimer = () => {
        updateTimer();
        state.timerInterval = env.setInterval?.(updateTimer, 250) || null;
        state.autoStopTimer = env.setTimeout?.(() => stopRecording('time_limit'), state.maxDurationMs) || null;
    };

    const startRecording = async () => {
        if (state.isRecording || state.isStopping) return;

        const maxDuration = maxDurationForText(currentPhrase);
        if (maxDuration === null) {
            setStatus(l.select_shorter_text, 'error');
            return;
        }

        const MediaRecorderClass = env.MediaRecorder;
        if (!env.navigator?.mediaDevices?.getUserMedia || !MediaRecorderClass) {
            setStatus(l.recording_failed, 'error');
            return;
        }

        stopExternalAudio?.();
        env.speechSynthesis?.cancel?.();
        stopRecordingAudio();
        resetRecordingData();
        setStatus('');
        state.elapsedMs = 0;
        state.maxDurationMs = maxDuration;
        state.stopReason = null;

        try {
            const stream = await env.navigator.mediaDevices.getUserMedia({ audio: true });
            if (state.isRecording || state.isStopping) {
                stopTracks(stream);
                return;
            }

            const mimeType = chooseMimeType(MediaRecorderClass);
            const recorder = mimeType ? new MediaRecorderClass(stream, { mimeType }) : new MediaRecorderClass(stream);
            state.mediaStream = stream;
            state.mediaRecorder = recorder;
            state.chunks = [];

            recorder.addEventListener('dataavailable', (event) => {
                if (event.data?.size > 0) state.chunks.push(event.data);
            });
            recorder.addEventListener('stop', () => {
                if (state.isStopping) return;
                finalizeStop(state.stopReason || 'manual');
            });
            recorder.addEventListener('error', () => {
                setStatus(l.recording_failed, 'error');
                stopRecording('recorder_error');
            });

            state.isRecording = true;
            state.startedAt = Date.now();
            recorder.start();
            setRecordingUi();
            startTimer();
            onRecordingStarted?.(state);
        } catch (error) {
            state.isRecording = false;
            state.isStopping = false;
            clearTimers();
            stopTracks(state.mediaStream);
            state.mediaStream = null;
            state.mediaRecorder = null;
            setIdleUi();
            setStatus(microphoneMessage(error, l), 'error');
        }
    };

    const playRecording = async () => {
        if (state.isRecording || !state.recordingUrl) return;
        stopExternalAudio?.();
        env.speechSynthesis?.cancel?.();
        stopRecordingAudio();
        state.recordingAudio = new env.Audio(state.recordingUrl);
        state.recordingAudio.addEventListener?.('ended', () => {
            state.recordingAudio = null;
        }, { once: true });
        await state.recordingAudio.play?.();
    };

    const pauseRecording = () => {
        stopRecordingAudio();
    };

    const recordAgain = async () => {
        stopRecording('new_recording');
        resetRecordingData();
        await startRecording();
    };

    const deleteRecording = () => {
        resetRecordingData();
        setStatus('');
        setIdleUi();
    };

    const listen = async () => {
        if (state.isRecording) return;
        stopRecordingAudio();
        await listenFn?.(currentPhrase, currentLocale, elements?.listenBtn);
    };

    const cleanupPracticeState = ({ reason = 'close', resetUi = true } = {}) => {
        stopRecording(reason);
        clearTimers();
        if (state.mediaRecorder && state.mediaRecorder.state !== 'inactive') {
            try { state.mediaRecorder.stop(); } catch { /* ignore */ }
        }
        stopTracks(state.mediaStream);
        stopRecordingAudio();
        stopExternalAudio?.();
        env.speechSynthesis?.cancel?.();
        resetRecordingData();
        state.mediaStream = null;
        state.mediaRecorder = null;
        state.isRecording = false;
        state.isStopping = false;
        state.startedAt = null;
        state.elapsedMs = 0;
        state.stopReason = reason;
        if (resetUi) {
            setStatus('');
            setIdleUi();
        }
    };

    const setPhrase = (nextPhrase, nextLocale = currentLocale) => {
        if (nextPhrase !== currentPhrase) cleanupPracticeState({ reason: 'selection_change' });
        currentPhrase = nextPhrase;
        currentLocale = nextLocale;
        setText(elements?.phraseNode, currentPhrase);
        state.maxDurationMs = maxDurationForText(currentPhrase) || 0;
        updateTimer();
    };

    const bind = () => {
        setText(elements?.listenBtn, l.listen);
        setText(elements?.startBtnLabel, l.start_recording);
        setText(elements?.stopBtn, l.stop);
        setText(elements?.cancelBtn, l.cancel);
        setText(elements?.localOnlyNode, l.recording_local_only);
        elements?.startBtn?.addEventListener?.('click', startRecording);
        elements?.stopBtn?.addEventListener?.('click', () => stopRecording('manual'));
        elements?.cancelBtn?.addEventListener?.('click', cancelRecording);
        elements?.listenBtn?.addEventListener?.('click', listen);
        elements?.playBtn?.addEventListener?.('click', playRecording);
        elements?.pauseBtn?.addEventListener?.('click', pauseRecording);
        elements?.recordAgainBtn?.addEventListener?.('click', recordAgain);
        elements?.deleteBtn?.addEventListener?.('click', deleteRecording);
        elements?.ratingButtons?.forEach?.((button) => {
            button.addEventListener('click', async () => {
                elements.ratingButtons.forEach((item) => item.classList.remove('is-selected'));
                button.classList.add('is-selected');
                await rateFn?.(button.dataset.shadowingRate, {
                    word_count: countWords(currentPhrase),
                    duration_ms: state.elapsedMs,
                    practiced_at: new Date().toISOString(),
                });
            });
        });
        setPhrase(currentPhrase, currentLocale);
        setIdleUi();
    };

    bind();

    return {
        state,
        startRecording,
        stopRecording,
        cancelRecording,
        cleanupPracticeState,
        deleteRecording,
        recordAgain,
        setPhrase,
        playRecording,
        pauseRecording,
    };
};

export {
    STOP_REASONS,
    countWords,
    maxDurationForText,
    chooseMimeType,
    createPracticeRecorder,
};
