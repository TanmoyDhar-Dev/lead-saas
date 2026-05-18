<script setup>
import { ref } from 'vue';
import axios from 'axios';

const loadingProvider = ref(null);
const errorMessage = ref('');

const connectProvider = async (provider) => {
    loadingProvider.value = provider;
    errorMessage.value = '';

    try {
        const response = await axios.post('/mailboxes/connections', { provider });
        const authUrl = response.data?.auth_url;

        if (!authUrl) {
            throw new Error('No authorization URL was returned.');
        }

        window.location.href = authUrl;
    } catch (error) {
        errorMessage.value = error.response?.data?.message ?? 'Could not start mailbox connection. Please try again.';
    } finally {
        loadingProvider.value = null;
    }
};
</script>

<template>
    <section class="mx-auto w-full max-w-xl rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
        <div class="space-y-2">
            <h2 class="text-xl font-semibold text-slate-900">Connect your email account</h2>
            <p class="text-sm text-slate-600">
                Choose a provider to authorize your mailbox for outreach sending.
            </p>
        </div>

        <div class="mt-6 grid gap-3 sm:grid-cols-2">
            <button
                type="button"
                class="inline-flex items-center justify-center rounded-lg border border-slate-300 px-4 py-2.5 text-sm font-medium text-slate-700 transition hover:bg-slate-50 disabled:cursor-not-allowed disabled:opacity-60"
                :disabled="loadingProvider !== null"
                @click="connectProvider('google-mail')"
            >
                {{ loadingProvider === 'google-mail' ? 'Connecting...' : 'Connect Gmail' }}
            </button>

            <button
                type="button"
                class="inline-flex items-center justify-center rounded-lg bg-slate-900 px-4 py-2.5 text-sm font-medium text-white transition hover:bg-slate-800 disabled:cursor-not-allowed disabled:opacity-60"
                :disabled="loadingProvider !== null"
                @click="connectProvider('outlook')"
            >
                {{ loadingProvider === 'outlook' ? 'Connecting...' : 'Connect Outlook' }}
            </button>
        </div>

        <p v-if="errorMessage" class="mt-4 rounded-lg border border-red-200 bg-red-50 px-3 py-2 text-sm text-red-700">
            {{ errorMessage }}
        </p>
    </section>
</template>
