<x-app-layout>
    <x-slot name="header">
        Automation Confirmation
    </x-slot>

    <x-slot name="subheader">
        Reviewing {{ $campaign->campaignRecipients->count() }} target{{ $campaign->campaignRecipients->count() !== 1 ? 's' : '' }} before dispatch
    </x-slot>

    <x-slot name="actions">
        <a href="{{ route('campaigns.index') }}" class="bg-white border border-slate-200 text-slate-600 px-4 py-2 rounded-xl text-xs font-bold hover:bg-slate-50 transition-all flex items-center shadow-sm">
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
            ALL CAMPAIGNS
        </a>
    </x-slot>

    <div class="space-y-8"
         x-data="campaignConfirmationForm({
            bodyTemplates: @js($bodyTemplatesForJs),
            signatureTemplates: @js($signatureTemplatesForJs),
            senderIdentities: @js($senderIdentitiesForJs),
            initialBody: @js(old('email_main_body', $campaign->email_main_body)),
            initialSignature: @js(old('email_signature', $campaign->email_signature)),
            initialSenderId: @js(old('sender_identity_id', $campaign->sender_identity_id))
         })">

        @if(session('error'))
            <div class="p-4 bg-red-50 border border-red-200 text-red-700 rounded-2xl text-xs font-semibold flex items-center gap-3">
                <svg class="w-5 h-5 text-red-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                <span>{{ session('error') }}</span>
            </div>
        @endif

        <div class="grid grid-cols-1 xl:grid-cols-12 gap-8 items-start">
            {{-- Left Side: Target Leads list --}}
            <div class="xl:col-span-5 bg-white rounded-3xl shadow-sm border border-slate-100 overflow-hidden flex flex-col">
                <div class="px-6 py-5 border-b border-slate-100 bg-slate-50/50 flex items-center justify-between">
                    <div>
                        <h3 class="text-sm font-bold text-slate-800">Selected Targets</h3>
                        <p class="text-[10px] text-slate-400 font-bold uppercase tracking-widest mt-1">Extraction list</p>
                    </div>
                    <span class="bg-brand-blue text-white px-3 py-1 rounded-xl text-[10px] font-bold shadow-sm shadow-blue-500/10">
                        {{ $campaign->campaignRecipients->count() }} RECIPIENTS
                    </span>
                </div>
                
                <div class="max-h-[85vh] overflow-y-auto custom-scrollbar divide-y divide-slate-100">
                    @forelse($campaign->campaignRecipients as $recipient)
                        @php $lead = $recipient->lead; @endphp
                        <div class="p-5 hover:bg-slate-50/60 transition-colors flex items-start gap-4">
                            <div class="w-10 h-10 rounded-full bg-slate-100 border border-slate-200 flex items-center justify-center font-bold text-slate-600 shrink-0 text-sm">
                                {{ substr($lead?->person_name ?: '?', 0, 1) }}
                            </div>
                            <div class="space-y-1.5 min-w-0 flex-1">
                                <div class="flex items-center justify-between gap-2">
                                    <h4 class="text-sm font-bold text-slate-800 truncate">{{ $lead?->person_name ?: 'Unknown' }}</h4>
                                    @if($lead?->personal__linkdin_url)
                                        <a href="{{ $lead->personal__linkdin_url }}" target="_blank" class="text-slate-400 hover:text-brand-blue shrink-0">
                                            <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 24 24"><path d="M19 0h-14c-2.761 0-5 2.239-5 5v14c0 2.761 2.239 5 5 5h14c2.762 0 5-2.239 5-5v-14c0-2.761-2.238-5-5-5zm-11 19h-3v-11h3v11zm-1.5-12.268c-.966 0-1.75-.79-1.75-1.764s.784-1.764 1.75-1.764 1.75.79 1.75 1.764-.783 1.764-1.75 1.764zm13.5 12.268h-3v-5.604c0-3.368-4-3.113-4 0v5.604h-3v-11h3v1.765c1.396-2.586 7-2.777 7 2.476v6.759z"/></svg>
                                        </a>
                                    @endif
                                </div>
                                <p class="text-xs font-semibold text-slate-600 truncate">{{ $lead?->position_by_apifiapi ?: ($lead?->position_by_search_param ?: 'No title specified') }}</p>
                                <div class="flex flex-col sm:flex-row sm:items-center gap-1 sm:gap-3 text-[10px] text-slate-400 font-medium">
                                    <span class="truncate flex items-center gap-1">
                                        <svg class="w-3 h-3 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path></svg>
                                        {{ $lead?->personal_email_address ?: 'No email address' }}
                                    </span>
                                    <span class="truncate flex items-center gap-1">
                                        <svg class="w-3 h-3 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path></svg>
                                        {{ $lead?->company_name ?: 'No company info' }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="p-8 text-center text-slate-400 text-xs font-medium">
                            No recipients found in this campaign.
                        </div>
                    @endforelse
                </div>
            </div>

            {{-- Right Side: Setup form --}}
            <div class="xl:col-span-7 bg-white rounded-3xl shadow-sm border border-slate-100 p-6 lg:p-8 space-y-6">
                <div class="border-b border-slate-100 pb-5">
                    <h3 class="text-sm font-bold text-slate-800">Campaign Configuration</h3>
                    <p class="text-[10px] text-slate-400 font-bold uppercase tracking-widest mt-1">Refine options and dispatch rules</p>
                </div>

                <form action="{{ route('campaigns.process', $campaign) }}" method="POST" enctype="multipart/form-data" class="space-y-6">
                    @csrf

                    {{-- 1. Delivery Mode --}}
                    <div>
                        <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-3">Delivery Mode</label>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <label class="relative flex flex-col p-4 bg-slate-50/50 hover:bg-slate-50 border border-slate-200 rounded-2xl cursor-pointer transition-all select-none group">
                                <input type="radio" name="delivery_mode" value="draft" class="absolute top-4 right-4 text-brand-blue focus:ring-brand-blue border-slate-300 w-4 h-4 transition-colors" {{ old('delivery_mode', $campaign->delivery_mode) === 'draft' ? 'checked' : '' }}>
                                <span class="text-xs font-bold text-slate-800 uppercase tracking-wider group-hover:text-brand-blue transition-colors">Save as Draft</span>
                                <span class="text-[10px] text-slate-400 mt-1 font-medium">Creates draft emails in n8n for manual validation</span>
                            </label>
                            <label class="relative flex flex-col p-4 bg-slate-50/50 hover:bg-slate-50 border border-slate-200 rounded-2xl cursor-pointer transition-all select-none group">
                                <input type="radio" name="delivery_mode" value="send" class="absolute top-4 right-4 text-brand-blue focus:ring-brand-blue border-slate-300 w-4 h-4 transition-colors" {{ old('delivery_mode', $campaign->delivery_mode) === 'send' ? 'checked' : '' }}>
                                <span class="text-xs font-bold text-slate-800 uppercase tracking-wider group-hover:text-brand-blue transition-colors">Send Instantly</span>
                                <span class="text-[10px] text-slate-400 mt-1 font-medium">Immediately dispatches emails automatically</span>
                            </label>
                        </div>
                        @error('delivery_mode')<p class="text-red-500 text-xs mt-1 font-semibold">{{ $message }}</p>@enderror
                    </div>

                    {{-- 2. Search Time Window --}}
                    <div>
                        <label for="search_window" class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-2">Search Time Window</label>
                        <select id="search_window" name="search_window" class="w-full bg-slate-50 border border-slate-200 rounded-xl text-sm focus:ring-brand-blue focus:border-brand-blue py-2.5 px-3 font-semibold text-slate-700">
                            @foreach(['qdr:m3' => 'Last 3 Months (qdr:m3)', 'qdr:m6' => 'Last 6 Months (qdr:m6)', 'qdr:y' => 'Last 1 Year (qdr:y)'] as $val => $label)
                                <option value="{{ $val }}" {{ old('search_window', $campaign->search_window) === $val ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('search_window')<p class="text-red-500 text-xs mt-1 font-semibold">{{ $message }}</p>@enderror
                    </div>

                    {{-- Templates selectors --}}
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        {{-- 3. Email Body Template --}}
                        <div>
                            <label for="email_body_template_id" class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-2">Email Body Template</label>
                            <select id="email_body_template_id" name="email_body_template_id" class="w-full bg-slate-50 border border-slate-200 rounded-xl text-sm py-2.5 px-3 text-slate-600 font-medium"
                                    x-show="bodyTemplates.length > 0"
                                    x-model="selectedBodyTemplateId" @change="applyBodyTemplate()">
                                <option value="">Insert template content…</option>
                                <template x-for="t in bodyTemplates" :key="t.id">
                                    <option :value="t.id" x-text="t.name"></option>
                                </template>
                            </select>
                            <div x-show="bodyTemplates.length === 0" class="text-xs font-semibold text-slate-500 py-2">
                                No templates found. <a href="{{ route('settings.templates') }}" class="text-brand-blue hover:underline">Create email templates</a>
                            </div>
                        </div>

                        {{-- 5. Email Signature Template --}}
                        <div>
                            <label for="email_signature_template_id" class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-2">Email Signature Template</label>
                            <select id="email_signature_template_id" name="email_signature_template_id" class="w-full bg-slate-50 border border-slate-200 rounded-xl text-sm py-2.5 px-3 text-slate-600 font-medium"
                                    x-show="signatureTemplates.length > 0"
                                    x-model="selectedSignatureTemplateId" @change="applySignatureTemplate()">
                                <option value="">Insert signature content…</option>
                                <template x-for="s in signatureTemplates" :key="s.id">
                                    <option :value="s.id" x-text="s.name"></option>
                                </template>
                            </select>
                            <div x-show="signatureTemplates.length === 0" class="text-xs font-semibold text-slate-500 py-2">
                                No signatures found. <a href="{{ route('settings.templates') }}" class="text-brand-blue hover:underline">Create email signatures</a>
                            </div>
                        </div>
                    </div>

                    {{-- 4. Email Main Body --}}
                    <div>
                        <label for="email_main_body" class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-2">Email Main Body</label>
                        <textarea id="email_main_body" name="email_main_body" rows="10" x-model="emailMainBody"
                                  class="w-full bg-slate-50 border border-slate-200 rounded-xl text-sm focus:ring-brand-blue focus:border-brand-blue p-4 font-mono text-slate-700 leading-relaxed"
                                  placeholder="Type your message body here..."
                                  required></textarea>
                        @error('email_main_body')<p class="text-red-500 text-xs mt-1 font-semibold">{{ $message }}</p>@enderror
                    </div>

                    {{-- 6. Email Signature --}}
                    <div>
                        <label for="email_signature" class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-2">Email Signature</label>
                        <textarea id="email_signature" name="email_signature" rows="4" x-model="emailSignature"
                                  class="w-full bg-slate-50 border border-slate-200 rounded-xl text-sm focus:ring-brand-blue focus:border-brand-blue p-4 font-mono text-slate-700 leading-relaxed"
                                  placeholder="Type your signature here..."></textarea>
                        @error('email_signature')<p class="text-red-500 text-xs mt-1 font-semibold">{{ $message }}</p>@enderror
                    </div>

                    {{-- 7. Sender Context --}}
                    <div class="bg-slate-50 border border-slate-100 rounded-3xl p-5 lg:p-6 space-y-4">
                        <div class="border-b border-slate-200 pb-3">
                            <h4 class="text-xs font-bold text-slate-800 uppercase tracking-wider flex items-center gap-1.5">
                                <svg class="w-4 h-4 text-brand-blue" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                                Sender Context
                            </h4>
                        </div>

                        <div>
                            <label for="sender_identity_id" class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-2">Sender Identity</label>
                            <select id="sender_identity_id" name="sender_identity_id" required x-model="selectedSenderIdentityId" @change="updateSenderPreview()"
                                    x-show="senderIdentities.length > 0"
                                    class="w-full bg-white border border-slate-200 rounded-xl text-sm focus:ring-brand-blue focus:border-brand-blue py-2.5 px-3 font-semibold text-slate-700">
                                <option value="" disabled>Select a sender profile…</option>
                                <template x-for="s in senderIdentities" :key="s.id">
                                    <option :value="s.id" x-text="s.sender_name + ' — ' + s.sender_company"></option>
                                </template>
                            </select>
                            <div x-show="senderIdentities.length === 0" class="text-xs font-semibold text-slate-500 py-2">
                                No sender profiles found. <a href="{{ route('settings.senders') }}" class="text-brand-blue hover:underline">Create sender identity</a>
                            </div>
                            @error('sender_identity_id')<p class="text-red-500 text-xs mt-1 font-semibold">{{ $message }}</p>@enderror
                        </div>

                        {{-- Compact stacked Sender Context preview --}}
                        <div x-show="selectedSender()" x-transition class="grid grid-cols-1 md:grid-cols-2 gap-4 pt-2">
                            <div>
                                <span class="block text-[9px] font-bold text-slate-400 uppercase tracking-widest mb-1.5">Sender Name</span>
                                <div class="bg-white border border-slate-200 rounded-xl px-3.5 py-2 text-xs font-bold text-slate-700 select-none min-h-[36px] flex items-center" x-text="selectedSender()?.sender_name || 'Not available'"></div>
                            </div>
                            <div>
                                <span class="block text-[9px] font-bold text-slate-400 uppercase tracking-widest mb-1.5">Sender Role</span>
                                <div class="bg-white border border-slate-200 rounded-xl px-3.5 py-2 text-xs font-bold text-slate-700 select-none min-h-[36px] flex items-center" x-text="selectedSender()?.sender_role || 'Not available'"></div>
                            </div>
                            <div>
                                <span class="block text-[9px] font-bold text-slate-400 uppercase tracking-widest mb-1.5">Sender Company</span>
                                <div class="bg-white border border-slate-200 rounded-xl px-3.5 py-2 text-xs font-bold text-slate-700 select-none min-h-[36px] flex items-center" x-text="selectedSender()?.sender_company || 'Not available'"></div>
                            </div>
                            <div>
                                <span class="block text-[9px] font-bold text-slate-400 uppercase tracking-widest mb-1.5">Sender Region</span>
                                <div class="bg-white border border-slate-200 rounded-xl px-3.5 py-2 text-xs font-bold text-slate-700 select-none min-h-[36px] flex items-center" x-text="selectedSender()?.sender_region || 'Not available'"></div>
                            </div>
                            <div>
                                <span class="block text-[9px] font-bold text-slate-400 uppercase tracking-widest mb-1.5">Sender Industry</span>
                                <div class="bg-white border border-slate-200 rounded-xl px-3.5 py-2 text-xs font-bold text-slate-700 select-none min-h-[36px] flex items-center" x-text="selectedSender()?.sender_industry || 'Not available'"></div>
                            </div>
                            <div>
                                <span class="block text-[9px] font-bold text-slate-400 uppercase tracking-widest mb-1.5">Sender EO Chapter</span>
                                <div class="bg-white border border-slate-200 rounded-xl px-3.5 py-2 text-xs font-bold text-slate-700 select-none min-h-[36px] flex items-center" x-text="selectedSender()?.sender_eo_chapter || 'Not available'"></div>
                            </div>
                            <div>
                                <span class="block text-[9px] font-bold text-slate-400 uppercase tracking-widest mb-1.5">Sender LinkedIn</span>
                                <div class="bg-white border border-slate-200 rounded-xl px-3.5 py-2 text-xs font-bold text-slate-700 min-h-[36px] flex items-center justify-between">
                                    <span x-text="selectedSender()?.sender_linkedin || 'Not available'" class="truncate mr-2"></span>
                                    <template x-if="selectedSender()?.sender_linkedin">
                                        <a :href="selectedSender()?.sender_linkedin" target="_blank" class="text-brand-blue hover:text-blue-600 transition-colors shrink-0">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path></svg>
                                        </a>
                                    </template>
                                </div>
                            </div>
                            <div>
                                <span class="block text-[9px] font-bold text-slate-400 uppercase tracking-widest mb-1.5">Sender Website</span>
                                <div class="bg-white border border-slate-200 rounded-xl px-3.5 py-2 text-xs font-bold text-slate-700 min-h-[36px] flex items-center justify-between">
                                    <span x-text="selectedSender()?.sender_website || 'Not available'" class="truncate mr-2"></span>
                                    <template x-if="selectedSender()?.sender_website">
                                        <a :href="selectedSender()?.sender_website" target="_blank" class="text-brand-blue hover:text-blue-600 transition-colors shrink-0">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"></path></svg>
                                        </a>
                                    </template>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- 8. Campaign Attachments Direct Upload --}}
                    <div class="space-y-3">
                        <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest">Campaign Attachments</label>
                        
                        {{-- Dashed border local device file upload container --}}
                        <div class="relative group border-2 border-dashed border-slate-200 rounded-2xl p-6 text-center hover:border-brand-blue/50 cursor-pointer transition-all bg-slate-50/30 hover:bg-slate-50/60 flex flex-col items-center justify-center">
                            <input type="file" name="attachments[]" multiple x-ref="attachmentsInput" class="absolute inset-0 opacity-0 cursor-pointer w-full h-full" @change="handleAttachmentSelect($event)">
                            <svg class="w-8 h-8 text-slate-400 group-hover:text-brand-blue transition-colors mb-2 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"></path></svg>
                            <span class="text-xs font-bold text-slate-700 group-hover:text-slate-800">Click to upload documents</span>
                            <span class="text-[10px] text-slate-400 font-semibold mt-1">PDF, CSV, TXT, DOC, DOCX, XLS, XLSX, ZIP, PNG, JPG, JPEG — Max 5MB per file</span>
                        </div>

                        {{-- Alpine dynamically rendering uploaded files list --}}
                        <div x-show="selectedFiles.length > 0" x-transition class="space-y-2 pt-1">
                            <p class="text-[9px] font-bold text-slate-400 uppercase tracking-widest">Selected Files</p>
                            <div class="space-y-1.5 max-h-[200px] overflow-y-auto custom-scrollbar pr-1">
                                <template x-for="(f, index) in selectedFiles" :key="f.name + '-' + index">
                                    <div class="flex items-center justify-between p-2.5 bg-slate-50 border border-slate-200/60 rounded-xl text-xs font-bold text-slate-700">
                                        <span class="truncate flex items-center gap-2">
                                            <svg class="w-4 h-4 text-slate-400 animate-pulse" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                                            <span x-text="f.name" class="truncate"></span>
                                        </span>
                                        <div class="flex items-center gap-3 shrink-0">
                                            <span class="text-[10px] text-slate-400 font-bold uppercase" x-text="humanFileSize(f.size)"></span>
                                            <button type="button" @click="removeAttachment(index)" aria-label="Remove file" class="text-red-500 hover:text-red-700 transition-colors p-1">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                            </button>
                                        </div>
                                    </div>
                                </template>
                            </div>
                        </div>

                        <p class="text-[10px] text-slate-400 font-medium italic mt-2.5 block leading-normal">
                            Note: Files will be encoded and passed to the automation workflow.
                        </p>
                        @error('attachments')<p class="text-red-500 text-xs mt-1 font-semibold">{{ $message }}</p>@enderror
                    </div>

                    {{-- Action Row --}}
                    <div class="flex flex-col-reverse sm:flex-row sm:items-center sm:justify-between gap-4 pt-6 border-t border-slate-100">
                        <a href="{{ route('campaigns.show', $campaign) }}" class="text-center sm:text-left text-xs font-bold text-brand-blue hover:text-blue-600 uppercase tracking-widest order-2 sm:order-1 transition-colors flex items-center justify-center gap-1.5">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                            Exit Setup (Keep Draft)
                        </a>
                        <button type="submit" class="order-1 sm:order-2 bg-brand-blue text-white px-8 py-3 rounded-xl text-xs font-bold hover:bg-blue-600 shadow-lg shadow-blue-500/20 transition-all active:scale-[0.98] uppercase tracking-wider">
                            Initiate Automation Sequence
                        </button>
                    </div>
                </form>
            </div>
        </div>

        {{-- Bottom Safety Exit --}}
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 bg-slate-50 border border-slate-200/60 rounded-3xl px-6 py-5">
            <div class="space-y-1">
                <p class="text-xs text-slate-700 font-bold">Abandon Session Draft?</p>
                <p class="text-[10px] text-slate-400 font-medium">To discard this session permanently, delete the draft campaign and all targets. This cannot be undone.</p>
            </div>
            <form action="{{ route('campaigns.cancel', $campaign) }}" method="POST" onsubmit="return confirm('Are you sure you want to permanently delete this draft campaign session and all selected targets? This action cannot be undone.');">
                @csrf
                <button type="submit" class="w-full sm:w-auto bg-white border border-red-200 text-red-600 px-5 py-2.5 rounded-xl text-xs font-bold hover:bg-red-50 transition-colors shadow-sm uppercase tracking-wider">
                    Delete Session Draft
                </button>
            </form>
        </div>
    </div>

    {{-- Native Javascript Alpine Component definition --}}
    <script>
        function campaignConfirmationForm(config) {
            return {
                bodyTemplates: config.bodyTemplates || [],
                signatureTemplates: config.signatureTemplates || [],
                senderIdentities: config.senderIdentities || [],
                
                selectedBodyTemplateId: '',
                emailMainBody: config.initialBody || '',
                
                selectedSignatureTemplateId: '',
                emailSignature: config.initialSignature || '',
                
                selectedSenderIdentityId: config.initialSenderId || '',
                
                selectedFiles: [],

                init() {
                    this.updateSenderPreview();
                },

                selectedSender() {
                    return this.senderIdentities.find(s => s.id === this.selectedSenderIdentityId);
                },

                applyBodyTemplate() {
                    if (!this.selectedBodyTemplateId) return;
                    const t = this.bodyTemplates.find(i => i.id === this.selectedBodyTemplateId);
                    if (t) {
                        this.emailMainBody = t.content;
                    }
                },

                applySignatureTemplate() {
                    if (!this.selectedSignatureTemplateId) return;
                    const t = this.signatureTemplates.find(i => i.id === this.selectedSignatureTemplateId);
                    if (t) {
                        this.emailSignature = t.content;
                    }
                },

                updateSenderPreview() {
                    // Reactive properties handle this instantly
                },

                handleAttachmentSelect(event) {
                    const files = Array.from(event.target.files);
                    const validFiles = [];
                    let rejectedFile = false;

                    files.forEach(file => {
                        const sizeInMB = file.size / (1024 * 1024);
                        if (sizeInMB > 5) {
                            rejectedFile = true;
                        } else {
                            validFiles.push(file);
                        }
                    });

                    if (rejectedFile) {
                        alert("Each attachment must be 5MB or less.");
                    }

                    // Append valid new files to current selected files array
                    this.selectedFiles = [...this.selectedFiles, ...validFiles];

                    // Sync the real browser file list input via DataTransfer API
                    this.syncFilesInput();
                },

                removeAttachment(index) {
                    this.selectedFiles.splice(index, 1);
                    this.syncFilesInput();
                },

                syncFilesInput() {
                    const dt = new DataTransfer();
                    this.selectedFiles.forEach(file => {
                        dt.items.add(file);
                    });
                    if (this.$refs.attachmentsInput) {
                        this.$refs.attachmentsInput.files = dt.files;
                    }
                },

                humanFileSize(sizeInBytes) {
                    if (sizeInBytes === 0) return '0 B';
                    const k = 1024;
                    const sizes = ['B', 'KB', 'MB', 'GB'];
                    const i = Math.floor(Math.log(sizeInBytes) / Math.log(k));
                    return parseFloat((sizeInBytes / Math.pow(k, i)).toFixed(1)) + ' ' + sizes[i];
                }
            };
        }
    </script>
</x-app-layout>
