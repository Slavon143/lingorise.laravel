import test from 'node:test';
import assert from 'node:assert/strict';
import { createPracticeRecorder } from '../../resources/js/reader/practice-recorder.js';

class FakeElement {
    constructor() {
        this.textContent = '';
        this.hidden = false;
        this.disabled = false;
        this.dataset = {};
        this.attributes = {};
        this.listeners = {};
        this.classList = {
            values: new Set(),
            add: (...names) => names.forEach((name) => this.classList.values.add(name)),
            remove: (...names) => names.forEach((name) => this.classList.values.delete(name)),
            contains: (name) => this.classList.values.has(name),
        };
    }

    addEventListener(name, callback) {
        this.listeners[name] = this.listeners[name] || [];
        this.listeners[name].push(callback);
    }

    setAttribute(name, value) {
        this.attributes[name] = value;
    }

    click() {
        (this.listeners.click || []).forEach((callback) => callback({ target: this }));
    }
}

class FakeTrack {
    constructor() { this.stopped = false; }
    stop() { this.stopped = true; }
}

class FakeStream {
    constructor() { this.track = new FakeTrack(); }
    getTracks() { return [this.track]; }
}

const createFakeEnv = ({ getUserMediaError = null } = {}) => {
    const timers = [];
    const intervals = [];
    const revoked = [];
    const createdUrls = [];
    const recorders = [];
    let streams = 0;

    class FakeMediaRecorder {
        constructor(stream, options = {}) {
            this.stream = stream;
            this.options = options;
            this.mimeType = options.mimeType || 'audio/webm';
            this.state = 'inactive';
            this.listeners = {};
            this.stopCalls = 0;
            recorders.push(this);
        }

        static isTypeSupported(type) { return type === 'audio/webm'; }

        addEventListener(name, callback) {
            this.listeners[name] = this.listeners[name] || [];
            this.listeners[name].push(callback);
        }

        emit(name, payload = {}) {
            (this.listeners[name] || []).forEach((callback) => callback(payload));
        }

        start() { this.state = 'recording'; }

        stop() {
            this.stopCalls += 1;
            if (this.state === 'inactive') return;
            this.state = 'inactive';
            this.emit('dataavailable', { data: new Blob(['audio'], { type: this.mimeType }) });
            this.emit('stop');
        }
    }

    const env = {
        MediaRecorder: FakeMediaRecorder,
        Blob,
        Audio: class {
            constructor(url) { this.url = url; this.currentTime = 0; this.listeners = {}; }
            addEventListener(name, callback) { this.listeners[name] = callback; }
            play() { return Promise.resolve(); }
            pause() { this.paused = true; }
        },
        URL: {
            createObjectURL: (blob) => {
                const url = `blob:test-${createdUrls.length}`;
                createdUrls.push({ url, blob });
                return url;
            },
            revokeObjectURL: (url) => revoked.push(url),
        },
        navigator: {
            mediaDevices: {
                getUserMedia: async () => {
                    if (getUserMediaError) throw getUserMediaError;
                    streams += 1;
                    return new FakeStream();
                },
            },
        },
        speechSynthesis: { cancelCalls: 0, cancel() { this.cancelCalls += 1; } },
        setTimeout: (callback, ms) => {
            const timer = { callback, ms, cleared: false };
            timers.push(timer);
            return timer;
        },
        clearTimeout: (timer) => { if (timer) timer.cleared = true; },
        setInterval: (callback, ms) => {
            const timer = { callback, ms, cleared: false };
            intervals.push(timer);
            return timer;
        },
        clearInterval: (timer) => { if (timer) timer.cleared = true; },
    };

    return { env, timers, intervals, revoked, createdUrls, recorders, get streams() { return streams; } };
};

const createElements = () => ({
    root: new FakeElement(),
    phraseNode: new FakeElement(),
    listenBtn: new FakeElement(),
    startBtn: new FakeElement(),
    startBtnLabel: new FakeElement(),
    stopBtn: new FakeElement(),
    cancelBtn: new FakeElement(),
    timerNode: new FakeElement(),
    statusNode: new FakeElement(),
    localOnlyNode: new FakeElement(),
    resultNode: new FakeElement(),
    resultTitleNode: new FakeElement(),
    playBtn: new FakeElement(),
    pauseBtn: new FakeElement(),
    recordAgainBtn: new FakeElement(),
    deleteBtn: new FakeElement(),
    ratingNode: new FakeElement(),
    ratingButtons: [new FakeElement(), new FakeElement(), new FakeElement()],
});

const createController = (options = {}) => {
    const fake = createFakeEnv(options);
    const elements = createElements();
    elements.ratingButtons[0].dataset.shadowingRate = 'difficult';
    elements.ratingButtons[1].dataset.shadowingRate = 'almost_correct';
    elements.ratingButtons[2].dataset.shadowingRate = 'good';
    const controller = createPracticeRecorder({
        elements,
        phrase: options.phrase || 'hello world',
        env: fake.env,
        stopExternalAudio: options.stopExternalAudio || (() => {}),
        rateFn: options.rateFn,
    });
    return Object.assign(fake, { elements, controller });
};

test('A. start recording creates one stream, recorder, timers, and recording UI', async () => {
    const ctx = createController();
    await ctx.controller.startRecording();

    assert.equal(ctx.streams, 1);
    assert.equal(ctx.recorders.length, 1);
    assert.equal(ctx.timers.length, 1);
    assert.equal(ctx.intervals.length, 1);
    assert.equal(ctx.controller.state.isRecording, true);
    assert.match(ctx.elements.timerNode.textContent, /Recording 00:00 \/ 00:30/);
});

test('B. double start does not create a second recorder', async () => {
    const ctx = createController();
    await ctx.controller.startRecording();
    await ctx.controller.startRecording();

    assert.equal(ctx.streams, 1);
    assert.equal(ctx.recorders.length, 1);
});

test('C. manual stop stops recorder and tracks, creates Blob URL, shows result', async () => {
    const ctx = createController();
    await ctx.controller.startRecording();
    const recorder = ctx.recorders[0];
    const track = recorder.stream.track;

    ctx.controller.stopRecording('manual');

    assert.equal(recorder.stopCalls, 1);
    assert.equal(track.stopped, true);
    assert.ok(ctx.controller.state.recordingBlob instanceof Blob);
    assert.equal(ctx.controller.state.recordingUrl, 'blob:test-0');
    assert.equal(ctx.elements.resultNode.hidden, false);
});

test('D. time limit auto-stops, saves Blob, and shows warning', async () => {
    const ctx = createController();
    await ctx.controller.startRecording();

    ctx.timers[0].callback();

    assert.equal(ctx.controller.state.stopReason, 'time_limit');
    assert.ok(ctx.controller.state.recordingBlob instanceof Blob);
    assert.equal(ctx.elements.statusNode.hidden, false);
    assert.match(ctx.elements.statusNode.textContent, /Maximum recording time/);
});

test('E. cancel stops and clears chunks without creating a Blob', async () => {
    const ctx = createController();
    await ctx.controller.startRecording();
    ctx.controller.cancelRecording();

    assert.equal(ctx.recorders[0].stopCalls, 1);
    assert.equal(ctx.controller.state.chunks.length, 0);
    assert.equal(ctx.controller.state.recordingBlob, null);
    assert.equal(ctx.controller.state.recordingUrl, null);
    assert.equal(ctx.elements.resultNode.hidden, true);
});

test('F. close cancels recording, stops tracks, clears timers, and revokes URL', async () => {
    const ctx = createController();
    await ctx.controller.startRecording();
    ctx.controller.stopRecording('manual');
    const url = ctx.controller.state.recordingUrl;
    await ctx.controller.startRecording();
    const track = ctx.recorders[1].stream.track;

    ctx.controller.cleanupPracticeState({ reason: 'close' });

    assert.equal(track.stopped, true);
    assert.equal(ctx.timers.every((timer) => timer.cleared), true);
    assert.equal(ctx.intervals.every((timer) => timer.cleared), true);
    assert.ok(ctx.revoked.includes(url));
});

test('G. tab switch resets state', async () => {
    const ctx = createController();
    await ctx.controller.startRecording();
    ctx.controller.cleanupPracticeState({ reason: 'tab_switch' });

    assert.equal(ctx.controller.state.isRecording, false);
    assert.equal(ctx.controller.state.stopReason, 'tab_switch');
});

test('H. selection change deletes previous recording', async () => {
    const ctx = createController();
    await ctx.controller.startRecording();
    ctx.controller.stopRecording('manual');
    const url = ctx.controller.state.recordingUrl;

    ctx.controller.setPhrase('different phrase', 'en');

    assert.equal(ctx.controller.state.recordingUrl, null);
    assert.ok(ctx.revoked.includes(url));
});

test('I. permission denied does not leave recording state and shows error', async () => {
    const ctx = createController({ getUserMediaError: { name: 'NotAllowedError' } });
    await ctx.controller.startRecording();

    assert.equal(ctx.controller.state.isRecording, false);
    assert.match(ctx.elements.statusNode.textContent, /Microphone access was denied/);
    assert.equal(ctx.timers.length, 0);
});

test('J. recorder error stops tracks, clears state, and shows error', async () => {
    const ctx = createController();
    await ctx.controller.startRecording();
    const track = ctx.recorders[0].stream.track;

    ctx.recorders[0].emit('error');

    assert.equal(track.stopped, true);
    assert.equal(ctx.controller.state.isRecording, false);
    assert.match(ctx.elements.statusNode.textContent, /Recording failed/);
});

test('K. too long selection does not request microphone', async () => {
    const ctx = createController({ phrase: Array.from({ length: 31 }, (_, i) => `word${i}`).join(' ') });
    await ctx.controller.startRecording();

    assert.equal(ctx.streams, 0);
    assert.match(ctx.elements.statusNode.textContent, /Select a shorter fragment/);
});

test('L. privacy: rating sends metadata only and no audio payload', async () => {
    let payload = null;
    const ctx = createController({ rateFn: async (_rating, meta) => { payload = meta; } });
    await ctx.controller.startRecording();
    ctx.controller.stopRecording('manual');
    ctx.elements.ratingButtons[1].click();

    assert.equal(payload.word_count, 2);
    assert.equal('duration_ms' in payload, true);
    assert.equal(Object.values(payload).some((value) => value instanceof Blob || (typeof File !== 'undefined' && value instanceof File)), false);
});

test('M. revokeObjectURL is called on delete, record again, and close', async () => {
    const ctx = createController();
    await ctx.controller.startRecording();
    ctx.controller.stopRecording('manual');
    const firstUrl = ctx.controller.state.recordingUrl;
    ctx.controller.deleteRecording();
    assert.ok(ctx.revoked.includes(firstUrl));

    await ctx.controller.startRecording();
    ctx.controller.stopRecording('manual');
    const secondUrl = ctx.controller.state.recordingUrl;
    await ctx.controller.recordAgain();
    assert.ok(ctx.revoked.includes(secondUrl));

    ctx.controller.stopRecording('manual');
    const thirdUrl = ctx.controller.state.recordingUrl;
    ctx.controller.cleanupPracticeState({ reason: 'close' });
    assert.ok(ctx.revoked.includes(thirdUrl));
});
