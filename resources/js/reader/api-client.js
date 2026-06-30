const csrfToken = () => document.querySelector('meta[name="csrf-token"]')?.content ?? '';

const normalizeError = async (response) => {
    const contentType = response.headers.get('content-type') || '';
    if (contentType.includes('application/json')) {
        try {
            const body = await response.json();
            return {
                status: response.status,
                code: body.error_code || 'unknown',
                message: body.message || 'An error occurred.',
                resetsAt: body.resets_at ?? null,
                upgradeUrl: body.upgrade_url ?? null,
            };
        } catch {
            return {
                status: response.status,
                code: 'invalid_json',
                message: 'Invalid response from server.',
                resetsAt: null,
                upgradeUrl: null,
            };
        }
    }
    const text = await response.text().catch(() => '');
    return {
        status: response.status,
        code: 'server_error',
        message: text || `HTTP ${response.status}`,
        resetsAt: null,
        upgradeUrl: null,
    };
};

export const apiPost = async (url, body, { signal } = {}) => {
    const response = await fetch(url, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': csrfToken(),
        },
        body: JSON.stringify(body),
        signal,
    });
    if (!response.ok) {
        const error = await normalizeError(response);
        throw error;
    }
    return response.json();
};

export const apiGet = async (url, { signal } = {}) => {
    const response = await fetch(url, {
        method: 'GET',
        headers: {
            'Accept': 'application/json',
            'X-CSRF-TOKEN': csrfToken(),
        },
        signal,
    });
    if (!response.ok) {
        const error = await normalizeError(response);
        throw error;
    }
    return response.json();
};

export const apiPostAudio = async (url, body, { signal } = {}) => {
    const response = await fetch(url, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'audio/mpeg',
            'X-CSRF-TOKEN': csrfToken(),
        },
        body: JSON.stringify(body),
        signal,
    });
    if (!response.ok) {
        const contentType = response.headers.get('content-type') || '';
        if (contentType.includes('application/json')) {
            const error = await normalizeError(response);
            throw error;
        }
        throw {
            status: response.status,
            code: 'audio_error',
            message: 'Voice playback unavailable.',
            resetsAt: null,
            upgradeUrl: null,
        };
    }
    return response;
};

export { csrfToken };
