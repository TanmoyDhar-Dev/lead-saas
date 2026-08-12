<x-app-layout>
    <x-slot name="header">Outreach Thread</x-slot>
    <x-slot name="subheader">Imported Leads · Conversations with sent outreach</x-slot>

    <div class="h-full min-h-0" x-data="outreachThreadPage()">
        <div class="bg-white rounded-3xl shadow-sm border border-slate-100 overflow-hidden flex flex-col lg:flex-row h-[calc(100vh-8.5rem)]">
            <div class="w-full lg:w-80 shrink-0 border-b lg:border-b-0 lg:border-r border-slate-100 flex flex-col min-h-0">
                <div class="p-4 border-b border-slate-100 shrink-0">
                    <label class="block text-[8px] font-black text-slate-400 uppercase tracking-widest mb-2">Search</label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <svg class="h-4 w-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                        </span>
                        <input type="text" x-model="searchQuery"
                               placeholder="Organization, contact, email..."
                               class="w-full bg-slate-50 border border-slate-200 rounded-xl text-sm font-medium text-slate-800 focus:ring-2 focus:ring-brand-blue outline-none py-2.5 pl-9 pr-3">
                    </div>
                </div>
                <div class="flex-1 overflow-y-auto">
                    <template x-if="filteredLeads.length === 0">
                        <div class="px-4 py-10 text-center">
                            <p class="text-sm font-medium text-slate-500" x-text="searchQuery ? 'No matching leads' : 'No outreach threads yet'"></p>
                            <p class="text-[11px] text-slate-400 mt-1" x-text="searchQuery ? 'Try a different search term' : 'Send outreach from Imported Leads first'"></p>
                        </div>
                    </template>
                    <template x-for="lead in filteredLeads" :key="lead.id">
                        <a :href="@js(route('imported-leads.threads')) + '?lead=' + lead.id"
                           class="block px-4 py-3 border-b border-slate-50 hover:bg-slate-50 transition-colors"
                           :class="threadLeadId == lead.id ? 'bg-blue-50' : ''">
                            <div class="text-sm font-bold text-slate-800 truncate" x-text="lead.organization_name || '—'"></div>
                            <div class="text-[11px] text-slate-500 truncate mt-0.5" x-text="lead.contact_name || '—'"></div>
                            <div class="text-[10px] text-slate-400 mt-1 truncate" x-text="lead.email || 'No email'"></div>
                        </a>
                    </template>
                </div>
            </div>

            <div class="flex-1 flex flex-col min-h-0 min-w-0">
                <div class="p-5 border-b border-slate-100 shrink-0">
                    <h3 class="text-lg font-bold text-slate-800"
                        x-text="threadData ? ((threadData.organization_name || 'Lead') + (threadData.contact_name ? (' · ' + threadData.contact_name) : '')) : 'Select a lead'"></h3>
                    <p class="text-[11px] text-slate-400 mt-0.5">Sent emails and replies in chronological order</p>
                </div>

                <div class="flex-1 overflow-y-auto p-6 space-y-3" x-show="!threadLoading && threadData" x-ref="threadScroll">
                    <template x-if="!(threadData?.email_thread || []).length">
                        <div class="rounded-2xl border border-dashed border-slate-200 bg-slate-50 px-4 py-10 text-center">
                            <p class="text-sm font-medium text-slate-500">No outreach messages yet</p>
                            <p class="text-[11px] text-slate-400 mt-1">Sent emails and replies will appear here</p>
                        </div>
                    </template>

                    <template x-for="msg in (threadData?.email_thread || [])" :key="msg.id">
                        <div class="flex" :class="msg.direction === 'outbound' ? 'justify-end' : 'justify-start'">
                            <div class="max-w-[85%] rounded-2xl px-4 py-3 border"
                                 :class="msg.direction === 'outbound'
                                    ? 'bg-brand-blue text-white border-brand-blue shadow-sm shadow-blue-500/20'
                                    : 'bg-slate-50 text-slate-800 border-slate-200'">
                                <div class="flex items-center justify-between gap-3 mb-1.5">
                                    <span class="text-[9px] font-bold uppercase tracking-widest"
                                          :class="msg.direction === 'outbound' ? 'text-blue-100' : 'text-slate-400'"
                                          x-text="msg.label"></span>
                                    <span class="text-[10px] shrink-0"
                                          :class="msg.direction === 'outbound' ? 'text-blue-100' : 'text-slate-400'"
                                          x-text="msg.occurred_at_label || ''"></span>
                                </div>
                                <div class="text-[11px] mb-1"
                                     :class="msg.direction === 'outbound' ? 'text-blue-100' : 'text-slate-500'"
                                     x-show="msg.from_email || msg.to_email"
                                     x-text="msg.direction === 'outbound'
                                        ? ('To: ' + (msg.to_email || '—'))
                                        : ('From: ' + (msg.from_email || '—'))"></div>
                                <div class="text-xs font-bold mb-2"
                                     :class="msg.direction === 'outbound' ? 'text-white' : 'text-slate-800'"
                                     x-text="msg.subject || '(No subject)'"></div>
                                <div class="text-xs leading-relaxed break-words"
                                     :class="msg.direction === 'outbound' ? 'text-blue-50' : 'text-slate-600'"
                                     x-show="msg.body_html"
                                     x-html="msg.body_html"></div>
                                <div class="text-xs leading-relaxed whitespace-pre-wrap"
                                     :class="msg.direction === 'outbound' ? 'text-blue-50' : 'text-slate-600'"
                                     x-show="!msg.body_html && msg.body_text"
                                     x-text="msg.body_text"></div>
                                <div class="mt-2" x-show="msg.direction === 'outbound' && msg.status">
                                    <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[9px] font-bold uppercase bg-white/15 text-blue-50"
                                          x-text="msg.status"></span>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>

                <div class="flex-1 flex items-center justify-center p-10" x-show="threadLoading">
                    <div class="animate-spin w-8 h-8 border-4 border-brand-blue border-t-transparent rounded-full"></div>
                </div>

                <div class="flex-1 flex items-center justify-center p-10" x-show="!threadLoading && !threadData && !threadLeadId">
                    <div class="text-center">
                        <p class="text-sm font-medium text-slate-500">Select a lead to view the thread</p>
                        <p class="text-[11px] text-slate-400 mt-1">Conversations appear after you send outreach</p>
                    </div>
                </div>

                <div class="border-t border-slate-100 p-4 bg-slate-50/80 shrink-0"
                     x-show="!threadLoading && threadData?.can_reply">
                    <form @submit.prevent="submitThreadReply()" class="space-y-2">
                        <label class="text-[10px] font-bold text-slate-400 uppercase tracking-widest block">Reply</label>
                        <textarea x-model="replyBody"
                                  rows="3"
                                  placeholder="Write your reply…"
                                  class="w-full bg-white border-slate-200 rounded-xl text-sm py-2.5 px-3 focus:ring-brand-blue focus:border-brand-blue resize-y min-h-[72px]"
                                  :disabled="replySending"></textarea>
                        <div class="flex items-center justify-between gap-3">
                            <p class="text-[10px] text-slate-400">Sends from your connected Outlook mailbox</p>
                            <button type="submit"
                                    class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-brand-blue text-white text-sm font-semibold hover:bg-blue-600 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
                                    :disabled="replySending || !(replyBody || '').trim()">
                                <span x-show="!replySending">Send Reply</span>
                                <span x-show="replySending">Sending…</span>
                            </button>
                        </div>
                    </form>
                </div>
                <div class="border-t border-slate-100 px-4 py-3 bg-slate-50/80 shrink-0"
                     x-show="!threadLoading && threadData && !threadData.can_reply">
                    <p class="text-[11px] text-slate-400 text-center">Send outreach first to enable replies from the app</p>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        function outreachThreadPage() {
            return {
                outlookConnected: @json($outlookConnected),
                threadLeadId: @json($selectedLeadId ?: null),
                threadLoading: false,
                threadData: null,
                replyBody: '',
                replySending: false,
                searchQuery: '',
                allLeads: {!! $leads->map(function ($l) {
                    return [
                        'id' => $l->id,
                        'organization_name' => $l->organization_name,
                        'contact_name' => $l->contact_name,
                        'email' => $l->primaryEmail(),
                    ];
                })->values()->toJson() !!},

                get filteredLeads() {
                    const q = (this.searchQuery || '').trim().toLowerCase();
                    if (!q) return this.allLeads;
                    return this.allLeads.filter(l =>
                        (l.organization_name || '').toLowerCase().includes(q) ||
                        (l.contact_name || '').toLowerCase().includes(q) ||
                        (l.email || '').toLowerCase().includes(q)
                    );
                },

                init() {
                    if (this.threadLeadId) {
                        this.loadThread(this.threadLeadId);
                    }
                },

                async loadThread(id) {
                    this.threadLeadId = id;
                    this.threadLoading = true;
                    this.threadData = null;
                    this.replyBody = '';
                    try {
                        const res = await fetch(@js(url('/imported-leads')) + '/' + id, {
                            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                            credentials: 'same-origin',
                        });
                        if (!res.ok) throw new Error('Failed to load');
                        this.threadData = await res.json();
                        this.$nextTick(() => {
                            const el = this.$refs.threadScroll;
                            if (el) el.scrollTop = el.scrollHeight;
                        });
                    } catch {
                        window.toast?.error('Unable to load outreach thread.');
                    } finally {
                        this.threadLoading = false;
                    }
                },

                async submitThreadReply() {
                    const body = (this.replyBody || '').trim();
                    if (!body || !this.threadLeadId || this.replySending) return;
                    if (!this.outlookConnected) {
                        window.toast?.error('Connect Outlook before sending a reply.');
                        return;
                    }

                    this.replySending = true;
                    try {
                        const res = await fetch(@js(url('/imported-leads')) + '/' + this.threadLeadId + '/reply', {
                            method: 'POST',
                            headers: {
                                'Accept': 'application/json',
                                'Content-Type': 'application/json',
                                'X-Requested-With': 'XMLHttpRequest',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                            },
                            credentials: 'same-origin',
                            body: JSON.stringify({ body }),
                        });
                        const data = await res.json().catch(() => ({}));
                        if (!res.ok) throw new Error(data.message || 'Failed to send reply.');
                        this.replyBody = '';
                        window.toast?.success(data.message || 'Reply sent.');
                        await this.loadThread(this.threadLeadId);
                    } catch (e) {
                        window.toast?.error(e.message || 'Failed to send reply.');
                    } finally {
                        this.replySending = false;
                    }
                },
            };
        }
    </script>
    @endpush
</x-app-layout>
