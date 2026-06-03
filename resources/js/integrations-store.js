const MODAL_NAME = 'integration-microsoft';
const OAUTH_CHANNEL = 'lead-saas-microsoft-oauth';
const OAUTH_STORAGE_KEY = 'lead_saas_microsoft_oauth_result';

function closeIntegrationModal() {
    window.dispatchEvent(new CustomEvent('close-modal', { bubbles: true, detail: MODAL_NAME }));

    document.querySelectorAll('[x-on\\:close-modal\\.window]').forEach((el) => {
        if (el.__x?.$data && 'show' in el.__x.$data) {
            el.__x.$data.show = false;
        }
    });
}

function readOAuthResultFromStorage() {
    try {
        const raw = localStorage.getItem(OAUTH_STORAGE_KEY);
        if (!raw) {
            return null;
        }
        localStorage.removeItem(OAUTH_STORAGE_KEY);
        return JSON.parse(raw);
    } catch {
        localStorage.removeItem(OAUTH_STORAGE_KEY);
        return null;
    }
}

document.addEventListener('alpine:init', () => {
    let oauthChannel = null;

    try {
        oauthChannel = new BroadcastChannel(OAUTH_CHANNEL);
    } catch {
        oauthChannel = null;
    }

    Alpine.store('integrations', {
        microsoftConnected: false,
        microsoftEmail: null,
        statusUrl: null,
        redirectUrl: null,
        disconnectUrl: null,
        loading: false,
        connecting: false,
        errorMessage: null,
        successMessage: null,
        oauthPopup: null,
        oauthPollTimer: null,
        oauthHandled: false,
        items: [
            {
                id: 'microsoft',
                label: 'Microsoft Outlook',
                connected: false,
                email: null,
                icon: '<svg class="w-4 h-4" viewBox="0 0 23 23"><path fill="#F25022" d="M1 1h10v10H1z"/><path fill="#00A4EF" d="M1 12h10v10H1z"/><path fill="#7FBA00" d="M12 1h10v10H12z"/><path fill="#FFB900" d="M12 12h10v10H12z"/></svg>',
            },
        ],

        configure(config) {
            this.microsoftConnected = config.microsoftConnected;
            this.microsoftEmail = config.microsoftEmail;
            this.statusUrl = config.statusUrl;
            this.redirectUrl = config.redirectUrl;
            this.disconnectUrl = config.disconnectUrl;
            this.syncMicrosoftItem();
        },

        initListeners() {
            if (this._listenersReady) {
                return;
            }
            this._listenersReady = true;

            window.addEventListener('message', (e) => this.onOAuthMessage(e));

            if (oauthChannel) {
                oauthChannel.onmessage = (e) => this.onOAuthMessage({ data: e.data });
            }
        },

        syncMicrosoftItem() {
            const item = this.items.find((i) => i.id === 'microsoft');
            if (item) {
                item.connected = this.microsoftConnected;
                item.email = this.microsoftEmail;
            }
        },

        openModal(id) {
            this.errorMessage = null;
            this.successMessage = null;
            window.dispatchEvent(new CustomEvent('open-modal', { bubbles: true, detail: 'integration-' + id }));
            this.refreshStatus({ silent: false });
        },

        async refreshStatus({ silent = false } = {}) {
            if (!silent) {
                this.loading = true;
            }
            try {
                const res = await fetch(this.statusUrl, {
                    headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                    credentials: 'same-origin',
                });
                if (!res.ok) {
                    throw new Error('Could not load integration status.');
                }
                const data = await res.json();
                const ms = data.integrations?.microsoft ?? {};
                this.microsoftConnected = !!ms.connected;
                this.microsoftEmail = ms.email ?? null;
                this.syncMicrosoftItem();
            } catch (err) {
                if (!silent) {
                    this.errorMessage = err.message || 'Failed to refresh status.';
                }
            } finally {
                if (!silent) {
                    this.loading = false;
                }
            }
        },

        connectMicrosoft() {
            this.initListeners();
            this.oauthHandled = false;
            this.clearOAuthPoll();
            localStorage.removeItem(OAUTH_STORAGE_KEY);
            this.connecting = true;
            this.errorMessage = null;
            this.successMessage = null;

            const w = 520;
            const h = 640;
            const left = (screen.width - w) / 2;
            const top = (screen.height - h) / 2;

            this.oauthPopup = window.open(
                this.redirectUrl,
                'microsoft_oauth',
                `width=${w},height=${h},left=${left},top=${top},scrollbars=yes`
            );

            if (!this.oauthPopup) {
                this.connecting = false;
                this.errorMessage = 'Popup blocked. Allow popups for this site and try again.';
                return;
            }

            this.oauthPollTimer = setInterval(() => {
                const stored = readOAuthResultFromStorage();
                if (stored?.type === 'integration-oauth') {
                    this.onOAuthMessage({ data: stored });
                    return;
                }

                if (this.oauthPopup?.closed) {
                    this.handleOAuthComplete(null, null);
                }
            }, 400);
        },

        clearOAuthPoll() {
            if (this.oauthPollTimer) {
                clearInterval(this.oauthPollTimer);
                this.oauthPollTimer = null;
            }
        },

        closeModal() {
            closeIntegrationModal();
        },

        async handleOAuthComplete(success, message) {
            if (this.oauthHandled) {
                return;
            }

            this.clearOAuthPoll();
            this.connecting = false;

            if (this.oauthPopup && !this.oauthPopup.closed) {
                try {
                    this.oauthPopup.close();
                } catch {
                    /* ignore */
                }
            }

            if (success === true) {
                this.oauthHandled = true;
                if (message) {
                    this.successMessage = message;
                }
                this.microsoftConnected = true;
                this.syncMicrosoftItem();
                await this.refreshStatus({ silent: true });
                setTimeout(() => {
                    this.closeModal();
                }, 2000);
                return;
            }

            if (success === false) {
                this.oauthHandled = true;
                this.errorMessage = message || 'Microsoft sign-in failed.';
                this.syncMicrosoftItem();
                return;
            }

            await this.refreshStatus({ silent: true });
            if (this.microsoftConnected) {
                this.oauthHandled = true;
                this.successMessage = 'Outlook connected successfully.';
                this.syncMicrosoftItem();
                setTimeout(() => {
                    this.closeModal();
                }, 2000);
            }
        },

        onOAuthMessage(event) {
            if (!event?.data || event.data.type !== 'integration-oauth') {
                return;
            }
            if (event.data.provider !== 'microsoft') {
                return;
            }
            this.handleOAuthComplete(event.data.success, event.data.message);
        },

        async disconnectMicrosoft() {
            if (!confirm('Disconnect Microsoft Outlook?')) {
                return;
            }
            this.connecting = true;
            this.errorMessage = null;
            try {
                const res = await fetch(this.disconnectUrl, {
                    method: 'DELETE',
                    headers: {
                        Accept: 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? '',
                    },
                    credentials: 'same-origin',
                });
                if (!res.ok) {
                    throw new Error('Disconnect failed.');
                }
                this.microsoftConnected = false;
                this.microsoftEmail = null;
                this.syncMicrosoftItem();
                this.successMessage = 'Outlook disconnected.';
            } catch (err) {
                this.errorMessage = err.message || 'Could not disconnect.';
            } finally {
                this.connecting = false;
            }
        },
    });
});
