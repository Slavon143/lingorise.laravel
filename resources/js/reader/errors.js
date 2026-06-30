export const handleAiError = (err, { setStatus, showUpgrade }) => {
    if (err.name === 'AbortError') return;
    if (err.upgradeUrl) {
        setStatus(err.message || 'Upgrade to use this feature');
        showUpgrade();
    } else {
        setStatus(err.message || 'This feature is unavailable.');
    }
};

export const formatQuotaMessage = (err) => {
    if (err.status === 403) {
        return err.message || 'Not available on your current plan.';
    }
    if (err.status === 429) {
        const resetInfo = err.resetsAt
            ? ` Resets at ${new Date(err.resetsAt).toLocaleTimeString()}.`
            : '';
        return `${err.message || 'Rate limit exceeded.'}${resetInfo}`;
    }
    if (err.status === 503) {
        return err.message || 'Service temporarily unavailable.';
    }
    if (err.status === 422) {
        return err.message || 'Invalid selection.';
    }
    return err.message || 'An unexpected error occurred.';
};
