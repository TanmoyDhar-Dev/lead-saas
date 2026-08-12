<x-app-layout>
    <x-slot name="header">Bulk Reply</x-slot>
    <x-slot name="subheader">Imported Leads · Reply to recipients from a previous campaign</x-slot>

    <div class="space-y-6" x-data="bulkReplyPage()">
        @unless($outlookConnected)
        <div class="rounded-2xl border border-amber-100 bg-amber-50 px-5 py-3 text-sm text-amber-800 font-medium">
            Connect Microsoft Outlook under Integrations before sending bulk replies.
        </div>
        @endunless

        <div class="bg-white rounded-3xl shadow-sm border border-slate-100 overflow-hidden">
            <form @submit.prevent="submitBulkReply()">
                <div class="p-6 md:p-8 space-y-6">
                    <div>
                        <label class="text-[10px] font-bold text-slate-400 uppercase tracking-widest block mb-1">Select Previous Campaign *</label>
                        <select x-model="bulkReply.campaignId"
                                @change="onBulkReplyCampaignChange()"
                                :disabled="bulkReply.campaignsLoading || bulkReply.submitting"
                                class="w-full bg-slate-50 border-slate-200 rounded-xl py-3 text-sm focus:ring-brand-blue focus:border-brand-blue">
                            <option value="">Choose a campaign…</option>
                            <template x-for="c in bulkReply.campaigns" :key="c.id">
                                <option :value="c.id"
                                        x-text="(c.name || 'Campaign') + ' · ' + (c.subject || 'No subject') + ' (' + (c.sent_recipients_count || 0) + ' sent)'"></option>
                            </template>
                        </select>
                        <p x-show="bulkReply.campaignsLoading" class="text-[11px] text-slate-400 mt-2">Loading campaigns…</p>
                        <p x-show="!bulkReply.campaignsLoading && bulkReply.campaigns.length === 0" class="text-[11px] text-slate-400 mt-2">
                            No sent outreach campaigns found yet.
                        </p>
                    </div>

                    <div x-show="bulkReply.campaignId" x-cloak class="rounded-2xl border border-slate-100 overflow-hidden">
                        <div class="px-4 py-3 bg-slate-50 border-b border-slate-100 flex items-center justify-between">
                            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Campaign Recipients</p>
                            <p class="text-[11px] text-slate-500 font-medium">
                                <span x-text="bulkReply.selectedLeadIds.length"></span> selected
                            </p>
                        </div>

                        <div class="max-h-72 overflow-y-auto" x-show="!bulkReply.recipientsLoading">
                            <table class="w-full text-left border-collapse">
                                <thead>
                                    <tr class="bg-white border-b border-slate-100">
                                        <th class="px-3 py-3 w-10">
                                            <input type="checkbox"
                                                   :checked="bulkReplySelectAllChecked"
                                                   @change="toggleBulkReplySelectAll($event.target.checked)"
                                                   class="w-4 h-4 text-brand-blue border-slate-300 rounded focus:ring-brand-blue">
                                        </th>
                                        <th class="px-3 py-3 text-[10px] font-bold text-slate-400 uppercase tracking-widest">Organization</th>
                                        <th class="px-3 py-3 text-[10px] font-bold text-slate-400 uppercase tracking-widest">Contact</th>
                                        <th class="px-3 py-3 text-[10px] font-bold text-slate-400 uppercase tracking-widest">Email</th>
                                        <th class="px-3 py-3 text-[10px] font-bold text-slate-400 uppercase tracking-widest">Sent</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100">
                                    <template x-for="row in bulkReply.recipients" :key="row.imported_lead_id">
                                        <tr class="hover:bg-slate-50 transition-colors">
                                            <td class="px-3 py-3">
                                                <input type="checkbox"
                                                       :value="row.imported_lead_id"
                                                       x-model="bulkReply.selectedLeadIds"
                                                       class="w-4 h-4 text-brand-blue border-slate-300 rounded focus:ring-brand-blue">
                                            </td>
                                            <td class="px-3 py-3 text-sm font-bold text-slate-800" x-text="row.organization_name || '—'"></td>
                                            <td class="px-3 py-3 text-sm text-slate-700" x-text="row.contact_name || '—'"></td>
                                            <td class="px-3 py-3 text-xs text-slate-600" x-text="row.to_email || '—'"></td>
                                            <td class="px-3 py-3 text-[11px] text-slate-400" x-text="row.sent_at_label || '—'"></td>
                                        </tr>
                                    </template>
                                    <tr x-show="bulkReply.recipients.length === 0">
                                        <td colspan="5" class="px-4 py-8 text-center text-sm text-slate-400">
                                            No sent recipients found for this campaign.
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <div class="p-8 text-center" x-show="bulkReply.recipientsLoading">
                            <div class="animate-spin w-8 h-8 border-4 border-brand-blue border-t-transparent rounded-full mx-auto"></div>
                        </div>
                    </div>

                    <div>
                        <label class="text-[10px] font-bold text-slate-400 uppercase tracking-widest block mb-1">Email Subject</label>
                        <input type="text"
                               readonly
                               :value="bulkReply.replySubject"
                               placeholder="Select a campaign to set subject"
                               class="w-full bg-slate-50 border-slate-200 rounded-xl text-sm text-slate-700 focus:ring-0 focus:border-slate-200 py-3 px-4 cursor-default">
                    </div>

                    <div>
                        <label class="text-xs font-bold text-slate-400">Body *</label>
                        <textarea x-model="bulkReply.body"
                                  rows="6"
                                  required
                                  :disabled="bulkReply.submitting"
                                  placeholder="Write your bulk reply…"
                                  class="w-full bg-slate-50 border-slate-200 rounded-xl mt-1 p-4 text-sm focus:ring-brand-blue focus:border-brand-blue"></textarea>
                    </div>

                    <div>
                        <label class="text-xs font-bold text-slate-400 mb-2 block">Attachments (Optional)</label>
                        <div class="w-full flex items-center justify-center p-6 border-2 border-dashed border-slate-300 rounded-xl bg-slate-50 hover:bg-slate-100 hover:border-brand-blue transition-colors cursor-pointer relative"
                             :class="bulkReply.dragOver ? 'border-brand-blue bg-blue-50' : ''"
                             @dragover.prevent="bulkReply.dragOver = true"
                             @dragleave.prevent="bulkReply.dragOver = false"
                             @drop.prevent="onBulkReplyDrop($event)">
                            <input type="file"
                                   multiple
                                   accept=".pdf,.doc,.docx,image/*"
                                   @change="onBulkReplyFilesSelected($event)"
                                   class="absolute inset-0 w-full h-full opacity-0 cursor-pointer"
                                   :disabled="bulkReply.submitting">
                            <div class="text-center pointer-events-none">
                                <svg class="mx-auto h-8 w-8 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"></path></svg>
                                <span class="mt-2 block text-sm font-semibold text-slate-700">Drop files here or click to upload</span>
                                <span class="mt-1 block text-[10px] text-slate-400">PDF, DOC, DOCX, JPG, PNG · max 5 MB each</span>
                            </div>
                        </div>
                        <div x-show="bulkReply.files.length > 0" class="mt-2 text-xs font-bold text-brand-blue">
                            <span x-text="bulkReply.files.length + ' file(s) selected'"></span>
                            <button type="button" @click="bulkReply.files = []" class="ml-2 text-slate-400 hover:text-slate-600 font-bold">Clear</button>
                        </div>
                    </div>
                </div>

                <div class="p-4 md:p-6 border-t border-slate-200 bg-white flex justify-end gap-3">
                    <button type="submit"
                            class="px-6 py-2.5 text-sm font-bold text-white bg-brand-blue rounded-xl hover:bg-blue-600 shadow-lg shadow-blue-500/30 disabled:opacity-50 disabled:cursor-not-allowed"
                            :disabled="bulkReply.submitting || !bulkReply.campaignId || bulkReply.selectedLeadIds.length === 0 || !(bulkReply.body || '').trim()">
                        <span x-show="!bulkReply.submitting">Queue Bulk Reply</span>
                        <span x-show="bulkReply.submitting">Queuing…</span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    @push('scripts')
    <script>
        function bulkReplyPage() {
            return {
                outlookConnected: @json($outlookConnected),
                bulkReply: {
                    campaignsLoading: false,
                    recipientsLoading: false,
                    submitting: false,
                    dragOver: false,
                    campaigns: [],
                    campaignId: '',
                    replySubject: '',
                    recipients: [],
                    selectedLeadIds: [],
                    body: '',
                    files: [],
                },

                init() {
                    this.fetchBulkReplyCampaigns();
                },

                get bulkReplySelectAllChecked() {
                    const ids = this.bulkReply.recipients.map((r) => r.imported_lead_id);
                    return ids.length > 0 && ids.every((id) => this.bulkReply.selectedLeadIds.includes(id));
                },

                async fetchBulkReplyCampaigns() {
                    this.bulkReply.campaignsLoading = true;
                    try {
                        const res = await fetch(@js(url('/api/imported-outreach/campaigns')), {
                            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                            credentials: 'same-origin',
                        });
                        const data = await res.json().catch(() => ({}));
                        if (!res.ok) throw new Error(data.message || 'Failed to load campaigns.');
                        this.bulkReply.campaigns = data.data || [];
                    } catch (e) {
                        this.bulkReply.campaigns = [];
                        window.toast?.error(e.message || 'Failed to load campaigns.');
                    } finally {
                        this.bulkReply.campaignsLoading = false;
                    }
                },

                async onBulkReplyCampaignChange() {
                    this.bulkReply.recipients = [];
                    this.bulkReply.selectedLeadIds = [];
                    this.bulkReply.replySubject = '';

                    const id = this.bulkReply.campaignId;
                    if (!id) return;

                    const campaign = this.bulkReply.campaigns.find((c) => String(c.id) === String(id));
                    if (campaign?.subject) {
                        this.bulkReply.replySubject = /^re:/i.test(campaign.subject)
                            ? campaign.subject
                            : ('Re: ' + campaign.subject);
                    }

                    this.bulkReply.recipientsLoading = true;
                    try {
                        const res = await fetch(@js(url('/api/imported-outreach')) + '/' + id + '/recipients', {
                            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                            credentials: 'same-origin',
                        });
                        const data = await res.json().catch(() => ({}));
                        if (!res.ok) throw new Error(data.message || 'Failed to load recipients.');
                        this.bulkReply.recipients = data.data || [];
                        this.bulkReply.replySubject = data.reply_subject || this.bulkReply.replySubject;
                        this.bulkReply.selectedLeadIds = this.bulkReply.recipients.map((r) => r.imported_lead_id);
                    } catch (e) {
                        this.bulkReply.recipients = [];
                        window.toast?.error(e.message || 'Failed to load recipients.');
                    } finally {
                        this.bulkReply.recipientsLoading = false;
                    }
                },

                toggleBulkReplySelectAll(checked) {
                    this.bulkReply.selectedLeadIds = checked
                        ? this.bulkReply.recipients.map((r) => r.imported_lead_id)
                        : [];
                },

                onBulkReplyFilesSelected(event) {
                    const files = Array.from(event.target.files || []);
                    this.bulkReply.files = this.filterBulkReplyFiles(files);
                    event.target.value = '';
                },

                onBulkReplyDrop(event) {
                    this.bulkReply.dragOver = false;
                    const files = Array.from(event.dataTransfer?.files || []);
                    this.bulkReply.files = this.filterBulkReplyFiles(files);
                },

                filterBulkReplyFiles(files) {
                    const allowed = files.filter((f) => f.size <= 5 * 1024 * 1024);
                    if (allowed.length !== files.length) {
                        window.toast?.error('Each attachment must be 5 MB or less.');
                    }
                    return allowed;
                },

                async submitBulkReply() {
                    if (this.bulkReply.submitting) return;
                    if (!this.outlookConnected) {
                        window.toast?.warning('Connect Microsoft Outlook first under Integrations.');
                        return;
                    }
                    if (!this.bulkReply.campaignId) {
                        window.toast?.error('Select a previous campaign.');
                        return;
                    }
                    if (this.bulkReply.selectedLeadIds.length === 0) {
                        window.toast?.error('Select at least one recipient.');
                        return;
                    }
                    const body = (this.bulkReply.body || '').trim();
                    if (!body) {
                        window.toast?.error('Reply body is required.');
                        return;
                    }

                    this.bulkReply.submitting = true;
                    try {
                        const formData = new FormData();
                        formData.append('parent_outreach_id', this.bulkReply.campaignId);
                        formData.append('body_template', body);
                        this.bulkReply.selectedLeadIds.forEach((id) => {
                            formData.append('selected_lead_ids[]', id);
                        });
                        this.bulkReply.files.forEach((file) => {
                            formData.append('attachments[]', file);
                        });

                        const res = await fetch(@js(url('/api/imported-outreach/bulk-reply')), {
                            method: 'POST',
                            headers: {
                                'Accept': 'application/json',
                                'X-Requested-With': 'XMLHttpRequest',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                            },
                            credentials: 'same-origin',
                            body: formData,
                        });

                        const data = await res.json().catch(() => ({}));
                        if (!res.ok) {
                            const firstError = data.errors
                                ? Object.values(data.errors).flat()[0]
                                : null;
                            throw new Error(firstError || data.message || 'Failed to queue bulk reply.');
                        }

                        window.toast?.success(data.message || 'Bulk replies have been queued.');
                        this.bulkReply.body = '';
                        this.bulkReply.files = [];
                    } catch (e) {
                        window.toast?.error(e.message || 'Failed to queue bulk reply.');
                    } finally {
                        this.bulkReply.submitting = false;
                    }
                },
            };
        }
    </script>
    @endpush
</x-app-layout>
