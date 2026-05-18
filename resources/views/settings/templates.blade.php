<x-app-layout>
    <x-slot name="header">
        Email Templates
    </x-slot>

    <x-slot name="subheader">
        Refine the content and signature formatting of your outreach sequences
    </x-slot>

    <x-slot name="actions">
        <button @click="openNewBody()" class="bg-brand-blue text-white px-5 py-2.5 rounded-xl text-xs font-bold hover:bg-blue-600 transition-all flex items-center shadow-lg shadow-blue-500/20 uppercase tracking-wider">
            <svg class="w-4 h-4 mr-2 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path></svg>
            New Body Template
        </button>
    </x-slot>

    <div class="space-y-8" x-data="{
        bodyFormOpen: false,
        bodyEditMode: false,
        bodyTemplate: { id: '', name: '', subject: '', content: '', is_default: false },

        sigFormOpen: false,
        sigEditMode: false,
        signatureTemplate: { id: '', name: '', content: '', is_default: false },

        openNewBody() {
            this.bodyFormOpen = true;
            this.bodyEditMode = false;
            this.bodyTemplate = { id: '', name: '', subject: '', content: '', is_default: false };
            this.$nextTick(() => this.$refs.bodyNameInput.focus());
        },
        openEditBody(item) {
            this.bodyFormOpen = true;
            this.bodyEditMode = true;
            this.bodyTemplate = { 
                id: item.id, 
                name: item.name, 
                subject: item.subject || '', 
                content: item.content, 
                is_default: !!item.is_default 
            };
            this.$nextTick(() => this.$refs.bodyNameInput.focus());
        },
        openNewSig() {
            this.sigFormOpen = true;
            this.sigEditMode = false;
            this.signatureTemplate = { id: '', name: '', content: '', is_default: false };
            this.$nextTick(() => this.$refs.sigNameInput.focus());
        },
        openEditSig(item) {
            this.sigFormOpen = true;
            this.sigEditMode = true;
            this.signatureTemplate = { 
                id: item.id, 
                name: item.name, 
                content: item.content, 
                is_default: !!item.is_default 
            };
            this.$nextTick(() => this.$refs.sigNameInput.focus());
        }
    }">

        {{-- Success/Error Alerts --}}
        @if(session('success'))
            <div class="p-4 bg-emerald-50 border border-emerald-200 text-emerald-700 rounded-2xl text-xs font-semibold flex items-center gap-3">
                <svg class="w-5 h-5 text-emerald-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                <span>{{ session('success') }}</span>
            </div>
        @endif

        {{-- Email Body Templates Drawer Form --}}
        <div x-show="bodyFormOpen" x-collapse x-cloak class="bg-slate-50 border border-slate-200/80 rounded-3xl p-6 space-y-4">
            <div class="flex items-center justify-between border-b border-slate-200/60 pb-3">
                <h4 class="text-xs font-bold text-slate-800 uppercase tracking-wider" x-text="bodyEditMode ? 'Edit Body Template' : 'Create Body Template'"></h4>
                <button @click="bodyFormOpen = false" class="text-slate-400 hover:text-slate-600">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>
            </div>
            
            <form :action="bodyEditMode ? '/settings/templates/body/' + bodyTemplate.id : '{{ route('settings.templates.body.store') }}'" method="POST" class="space-y-4">
                @csrf
                <template x-if="bodyEditMode">
                    <input type="hidden" name="_method" value="PUT">
                </template>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1.5">Template Name</label>
                        <input type="text" name="name" x-ref="bodyNameInput" x-model="bodyTemplate.name" required placeholder="e.g. Cold Outreach Standard"
                               class="w-full bg-white border border-slate-200 rounded-xl text-xs py-2.5 px-3 font-semibold text-slate-700 focus:ring-brand-blue focus:border-brand-blue">
                    </div>
                    <div>
                        <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1.5">Subject</label>
                        <input type="text" name="subject" x-model="bodyTemplate.subject" placeholder="e.g. Quick question regarding @{{companyName}}"
                               class="w-full bg-white border border-slate-200 rounded-xl text-xs py-2.5 px-3 font-semibold text-slate-700 focus:ring-brand-blue focus:border-brand-blue">
                    </div>
                </div>

                <div>
                    <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1.5">Email Body Content</label>
                    <textarea name="content" x-model="bodyTemplate.content" rows="6" required placeholder="Hi @{{personName}}, ..."
                              class="w-full bg-white border border-slate-200 rounded-xl text-xs p-4 font-mono text-slate-700 leading-relaxed focus:ring-brand-blue focus:border-brand-blue"></textarea>
                </div>

                <div class="flex items-center justify-between gap-4 pt-2">
                    <label class="flex items-center gap-2 select-none cursor-pointer">
                        <input type="checkbox" name="is_default" value="1" x-model="bodyTemplate.is_default" class="rounded text-brand-blue border-slate-200 focus:ring-brand-blue w-4 h-4">
                        <span class="text-xs font-semibold text-slate-600">Set as default template for campaigns</span>
                    </label>
                    <div class="flex items-center gap-2">
                        <button type="button" @click="bodyFormOpen = false" class="bg-white border border-slate-200 text-slate-600 px-4 py-2 rounded-xl text-xs font-bold hover:bg-slate-50 transition-colors uppercase tracking-wider">Cancel</button>
                        <button type="submit" class="bg-brand-blue text-white px-5 py-2.5 rounded-xl text-xs font-bold hover:bg-blue-600 transition-colors shadow-sm uppercase tracking-wider">Save Template</button>
                    </div>
                </div>
            </form>
        </div>

        {{-- Email Body Templates Listing Table --}}
        <div class="bg-white rounded-3xl shadow-sm border border-slate-100 overflow-hidden">
            <div class="px-6 py-5 border-b border-slate-100 bg-slate-50/50 flex items-center justify-between">
                <div>
                    <h3 class="text-sm font-bold text-slate-800">Email Body Templates</h3>
                    <p class="text-[10px] text-slate-400 font-bold uppercase tracking-widest mt-1">Ready content models for campaign setups</p>
                </div>
            </div>
            
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-slate-50/20 border-b border-slate-100/80">
                            <th class="px-6 py-4 text-[9px] font-bold text-slate-400 uppercase tracking-widest">Name</th>
                            <th class="px-6 py-4 text-[9px] font-bold text-slate-400 uppercase tracking-widest">Subject</th>
                            <th class="px-6 py-4 text-[9px] font-bold text-slate-400 uppercase tracking-widest">Default</th>
                            <th class="px-6 py-4 text-[9px] font-bold text-slate-400 uppercase tracking-widest text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($bodyTemplates as $body)
                            <tr class="hover:bg-slate-50/50 transition-colors">
                                <td class="px-6 py-4">
                                    <div class="font-bold text-slate-800 text-xs">{{ $body->name }}</div>
                                    @if(auth()->user()->isAdmin() && (int)$body->user_id !== (int)auth()->id())
                                        <div class="text-[9px] font-semibold text-slate-400 mt-0.5">Owner ID: {{ $body->user_id }}</div>
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-xs font-semibold text-slate-500 truncate max-w-xs">
                                    {{ $body->subject ?: '(No Subject)' }}
                                </td>
                                <td class="px-6 py-4">
                                    @if($body->is_default)
                                        <span class="bg-emerald-50 text-emerald-700 border border-emerald-100 px-2.5 py-0.5 rounded-full text-[9px] font-bold uppercase tracking-wider">
                                            Default
                                        </span>
                                    @else
                                        <form action="{{ route('settings.templates.body.default', $body) }}" method="POST">
                                            @csrf
                                            <button type="submit" class="text-slate-400 hover:text-brand-blue text-[9px] font-bold uppercase tracking-wider hover:underline">
                                                Set Default
                                            </button>
                                        </form>
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <div class="flex justify-end items-center gap-2">
                                        <button @click="openEditBody(@js($body))" class="p-2 text-slate-400 hover:text-brand-blue transition-colors rounded-lg hover:bg-slate-50" title="Edit Template">
                                            <svg class="w-4.5 h-4.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"></path></svg>
                                        </button>
                                        <form action="{{ route('settings.templates.body.destroy', $body) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete this body template?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="p-2 text-slate-400 hover:text-red-600 transition-colors rounded-lg hover:bg-slate-50" title="Delete Template">
                                                <svg class="w-4.5 h-4.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-6 py-12 text-center text-slate-400 text-xs font-semibold">
                                    No body templates created yet.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Email Signature Drawer Form --}}
        <div x-show="sigFormOpen" x-collapse x-cloak class="bg-slate-50 border border-slate-200/80 rounded-3xl p-6 space-y-4">
            <div class="flex items-center justify-between border-b border-slate-200/60 pb-3">
                <h4 class="text-xs font-bold text-slate-800 uppercase tracking-wider" x-text="sigEditMode ? 'Edit Signature Template' : 'Create Signature Template'"></h4>
                <button @click="sigFormOpen = false" class="text-slate-400 hover:text-slate-600">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>
            </div>
            
            <form :action="sigEditMode ? '/settings/templates/signature/' + signatureTemplate.id : '{{ route('settings.templates.signature.store') }}'" method="POST" class="space-y-4">
                @csrf
                <template x-if="sigEditMode">
                    <input type="hidden" name="_method" value="PUT">
                </template>

                <div>
                    <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1.5">Signature Name</label>
                    <input type="text" name="name" x-ref="sigNameInput" x-model="signatureTemplate.name" required placeholder="e.g. Standard Signature"
                           class="w-full bg-white border border-slate-200 rounded-xl text-xs py-2.5 px-3 font-semibold text-slate-700 focus:ring-brand-blue focus:border-brand-blue">
                </div>

                <div>
                    <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1.5">Signature Content</label>
                    <textarea name="content" x-model="signatureTemplate.content" rows="4" required placeholder="Best regards,&#10;John Doe"
                              class="w-full bg-white border border-slate-200 rounded-xl text-xs p-4 font-mono text-slate-700 leading-relaxed focus:ring-brand-blue focus:border-brand-blue"></textarea>
                </div>

                <div class="flex items-center justify-between gap-4 pt-2">
                    <label class="flex items-center gap-2 select-none cursor-pointer">
                        <input type="checkbox" name="is_default" value="1" x-model="signatureTemplate.is_default" class="rounded text-brand-blue border-slate-200 focus:ring-brand-blue w-4 h-4">
                        <span class="text-xs font-semibold text-slate-600">Set as default signature for campaigns</span>
                    </label>
                    <div class="flex items-center gap-2">
                        <button type="button" @click="sigFormOpen = false" class="bg-white border border-slate-200 text-slate-600 px-4 py-2 rounded-xl text-xs font-bold hover:bg-slate-50 transition-colors uppercase tracking-wider">Cancel</button>
                        <button type="submit" class="bg-brand-blue text-white px-5 py-2.5 rounded-xl text-xs font-bold hover:bg-blue-600 transition-colors shadow-sm uppercase tracking-wider">Save Signature</button>
                    </div>
                </div>
            </form>
        </div>

        {{-- Email Signature Templates List --}}
        <div class="bg-white rounded-3xl shadow-sm border border-slate-100 overflow-hidden">
            <div class="px-6 py-5 border-b border-slate-100 bg-slate-50/50 flex items-center justify-between">
                <div>
                    <h3 class="text-sm font-bold text-slate-800">Email Signatures</h3>
                    <p class="text-[10px] text-slate-400 font-bold uppercase tracking-widest mt-1">Professional email footnotes and contact tags</p>
                </div>
                <button @click="openNewSig()" class="bg-slate-800 text-white px-4 py-2 rounded-xl text-xs font-bold hover:bg-slate-950 transition-colors uppercase tracking-wider shadow-sm">
                    Add Signature
                </button>
            </div>
            
            <div class="p-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    @forelse($signatureTemplates as $sig)
                        <div class="border border-slate-150 rounded-2xl p-5 hover:border-brand-blue/30 transition-all group relative bg-white shadow-sm flex flex-col justify-between">
                            <div>
                                <div class="flex justify-between items-start mb-3 gap-2">
                                    <div>
                                        <h5 class="text-xs font-bold text-slate-800">{{ $sig->name }}</h5>
                                        @if(auth()->user()->isAdmin() && (int)$sig->user_id !== (int)auth()->id())
                                            <span class="text-[8px] font-bold text-slate-400 uppercase tracking-widest">Owner: {{ $sig->user_id }}</span>
                                        @endif
                                    </div>
                                    @if($sig->is_default)
                                        <span class="bg-emerald-50 text-emerald-700 border border-emerald-100 px-2 py-0.5 rounded text-[8px] font-bold uppercase tracking-wider">
                                            Default
                                        </span>
                                    @else
                                        <form action="{{ route('settings.templates.signature.default', $sig) }}" method="POST">
                                            @csrf
                                            <button type="submit" class="text-slate-400 hover:text-brand-blue text-[8px] font-bold uppercase tracking-wider hover:underline">
                                                Set Default
                                            </button>
                                        </form>
                                    @endif
                                </div>
                                <div class="text-[10px] text-slate-600 font-mono whitespace-pre-wrap bg-slate-50 border border-slate-100 rounded-xl p-3.5 leading-relaxed min-h-[80px]">
                                    {{ $sig->content }}
                                </div>
                            </div>

                            <div class="flex justify-end gap-2 pt-4 border-t border-slate-100/80 mt-4">
                                <button @click="openEditSig(@js($sig))" class="p-1.5 text-slate-400 hover:text-brand-blue transition-colors rounded-lg hover:bg-slate-50" title="Edit Signature">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"></path></svg>
                                </button>
                                <form action="{{ route('settings.templates.signature.destroy', $sig) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete this signature template?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="p-1.5 text-slate-400 hover:text-red-600 transition-colors rounded-lg hover:bg-slate-50" title="Delete Signature">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                    </button>
                                </form>
                            </div>
                        </div>
                    @empty
                        <div class="col-span-2 text-center py-12 border-2 border-dashed border-slate-100 rounded-3xl">
                            <p class="text-slate-400 text-xs font-semibold">No signatures available.</p>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
