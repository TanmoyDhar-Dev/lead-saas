<x-app-layout>
    <x-slot name="header">
        Email Settings
    </x-slot>

    <x-slot name="subheader">
        Configure the identity, company context, and professional networks of campaign senders
    </x-slot>

    <x-slot name="actions">
        <button @click="openNewSender()" class="bg-brand-blue text-white px-5 py-2.5 rounded-xl text-xs font-bold hover:bg-blue-600 transition-all flex items-center shadow-lg shadow-blue-500/20 uppercase tracking-wider">
            <svg class="w-4 h-4 mr-2 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path></svg>
            New Sender Profile
        </button>
    </x-slot>

    <div class="space-y-8" x-data="{
        formOpen: false,
        editMode: false,
        sender: { 
            id: '', 
            name: '', 
            sender_name: '', 
            sender_role: '', 
            sender_company: '', 
            sender_region: '', 
            sender_industry: '', 
            sender_linkedin: '', 
            sender_website: '', 
            sender_eo_chapter: '', 
            is_default: false, 
            status: 'active' 
        },

        openNewSender() {
            this.formOpen = true;
            this.editMode = false;
            this.sender = { 
                id: '', 
                name: '', 
                sender_name: '', 
                sender_role: '', 
                sender_company: '', 
                sender_region: '', 
                sender_industry: '', 
                sender_linkedin: '', 
                sender_website: '', 
                sender_eo_chapter: '', 
                is_default: false, 
                status: 'active' 
            };
            this.$nextTick(() => this.$refs.profileNameInput.focus());
        },
        openEditSender(item) {
            this.formOpen = true;
            this.editMode = true;
            this.sender = { 
                id: item.id, 
                name: item.name, 
                sender_name: item.sender_name, 
                sender_role: item.sender_role || '', 
                sender_company: item.sender_company || '', 
                sender_region: item.sender_region || '', 
                sender_industry: item.sender_industry || '', 
                sender_linkedin: item.sender_linkedin || '', 
                sender_website: item.sender_website || '', 
                sender_eo_chapter: item.sender_eo_chapter || '', 
                is_default: !!item.is_default, 
                status: item.status || 'active' 
            };
            this.$nextTick(() => this.$refs.profileNameInput.focus());
        }
    }">

        {{-- Success Alerts --}}
        @if(session('success'))
            <div class="p-4 bg-emerald-50 border border-emerald-200 text-emerald-700 rounded-2xl text-xs font-semibold flex items-center gap-3">
                <svg class="w-5 h-5 text-emerald-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                <span>{{ session('success') }}</span>
            </div>
        @endif

        {{-- Interactive CRUD Form Drawer --}}
        <div x-show="formOpen" x-collapse x-cloak class="bg-slate-50 border border-slate-200/80 rounded-3xl p-6 space-y-4 shadow-inner">
            <div class="flex items-center justify-between border-b border-slate-200/60 pb-3">
                <h4 class="text-xs font-bold text-slate-800 uppercase tracking-wider" x-text="editMode ? 'Edit Sender Profile' : 'Create Sender Profile'"></h4>
                <button @click="formOpen = false" class="text-slate-400 hover:text-slate-600">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>
            </div>
            
            <form :action="editMode ? '/settings/senders/' + sender.id : '{{ route('settings.senders.store') }}'" method="POST" class="space-y-5">
                @csrf
                <template x-if="editMode">
                    <input type="hidden" name="_method" value="PUT">
                </template>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
                    <div>
                        <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1.5">Profile Label Name</label>
                        <input type="text" name="name" x-ref="profileNameInput" x-model="sender.name" required placeholder="e.g. CEO Main Account"
                               class="w-full bg-white border border-slate-200 rounded-xl text-xs py-2.5 px-3 font-semibold text-slate-700 focus:ring-brand-blue focus:border-brand-blue">
                    </div>
                    <div>
                        <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1.5">Sender Full Name</label>
                        <input type="text" name="sender_name" x-model="sender.sender_name" required placeholder="e.g. John Doe"
                               class="w-full bg-white border border-slate-200 rounded-xl text-xs py-2.5 px-3 font-semibold text-slate-700 focus:ring-brand-blue focus:border-brand-blue">
                    </div>
                    <div>
                        <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1.5">Sender Role</label>
                        <input type="text" name="sender_role" x-model="sender.sender_role" placeholder="e.g. Managing Partner"
                               class="w-full bg-white border border-slate-200 rounded-xl text-xs py-2.5 px-3 font-semibold text-slate-700 focus:ring-brand-blue focus:border-brand-blue">
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-4 gap-5">
                    <div>
                        <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1.5">Company</label>
                        <input type="text" name="sender_company" x-model="sender.sender_company" placeholder="e.g. Acme Corp"
                               class="w-full bg-white border border-slate-200 rounded-xl text-xs py-2.5 px-3 font-semibold text-slate-700 focus:ring-brand-blue focus:border-brand-blue">
                    </div>
                    <div>
                        <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1.5">Region</label>
                        <input type="text" name="sender_region" x-model="sender.sender_region" placeholder="e.g. North America"
                               class="w-full bg-white border border-slate-200 rounded-xl text-xs py-2.5 px-3 font-semibold text-slate-700 focus:ring-brand-blue focus:border-brand-blue">
                    </div>
                    <div>
                        <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1.5">Industry</label>
                        <input type="text" name="sender_industry" x-model="sender.sender_industry" placeholder="e.g. Software & Tech"
                               class="w-full bg-white border border-slate-200 rounded-xl text-xs py-2.5 px-3 font-semibold text-slate-700 focus:ring-brand-blue focus:border-brand-blue">
                    </div>
                    <div>
                        <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1.5">EO Chapter</label>
                        <input type="text" name="sender_eo_chapter" x-model="sender.sender_eo_chapter" placeholder="e.g. EO New York"
                               class="w-full bg-white border border-slate-200 rounded-xl text-xs py-2.5 px-3 font-semibold text-slate-700 focus:ring-brand-blue focus:border-brand-blue">
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
                    <div>
                        <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1.5">LinkedIn Profile URL</label>
                        <input type="url" name="sender_linkedin" x-model="sender.sender_linkedin" placeholder="https://linkedin.com/in/..."
                               class="w-full bg-white border border-slate-200 rounded-xl text-xs py-2.5 px-3 font-semibold text-slate-700 focus:ring-brand-blue focus:border-brand-blue">
                    </div>
                    <div>
                        <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1.5">Website URL</label>
                        <input type="url" name="sender_website" x-model="sender.sender_website" placeholder="https://example.com"
                               class="w-full bg-white border border-slate-200 rounded-xl text-xs py-2.5 px-3 font-semibold text-slate-700 focus:ring-brand-blue focus:border-brand-blue">
                    </div>
                    <div>
                        <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-1.5">Status</label>
                        <select name="status" x-model="sender.status" class="w-full bg-white border border-slate-200 rounded-xl text-xs py-2.5 px-3 font-semibold text-slate-700 focus:ring-brand-blue focus:border-brand-blue">
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                        </select>
                    </div>
                </div>

                <div class="flex items-center justify-between gap-4 pt-2 border-t border-slate-200/60">
                    <label class="flex items-center gap-2 select-none cursor-pointer">
                        <input type="checkbox" name="is_default" value="1" x-model="sender.is_default" class="rounded text-brand-blue border-slate-200 focus:ring-brand-blue w-4 h-4">
                        <span class="text-xs font-semibold text-slate-600">Set as default sender profile for campaigns</span>
                    </label>
                    <div class="flex items-center gap-2">
                        <button type="button" @click="formOpen = false" class="bg-white border border-slate-200 text-slate-600 px-4 py-2 rounded-xl text-xs font-bold hover:bg-slate-50 transition-colors uppercase tracking-wider">Cancel</button>
                        <button type="submit" class="bg-brand-blue text-white px-5 py-2.5 rounded-xl text-xs font-bold hover:bg-blue-600 transition-colors shadow-sm uppercase tracking-wider">Save Profile</button>
                    </div>
                </div>
            </form>
        </div>

        {{-- Senders Listing Grid --}}
        <div class="space-y-4">
            <div class="flex items-center justify-between">
                <div>
                    <h3 class="text-sm font-bold text-slate-800">Sender Identities</h3>
                    <p class="text-[10px] text-slate-400 font-bold uppercase tracking-widest mt-1">Configured accounts for campaign dispatch settings</p>
                </div>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                @forelse($senders as $s)
                    <div class="bg-white rounded-3xl border border-slate-150 p-6 hover:border-brand-blue/30 transition-all flex flex-col justify-between shadow-sm relative group">
                        
                        <div class="space-y-4">
                            {{-- Header Context Card info --}}
                            <div class="flex justify-between items-start border-b border-slate-100 pb-3">
                                <div>
                                    <h4 class="text-sm font-bold text-slate-800 flex items-center gap-2">
                                        {{ $s->name }}
                                        @if($s->is_default)
                                            <span class="bg-emerald-50 text-emerald-700 border border-emerald-100 px-2 py-0.5 rounded text-[8px] font-bold uppercase tracking-wider shrink-0">
                                                Default
                                            </span>
                                        @endif
                                    </h4>
                                    <p class="text-[10px] text-slate-400 font-bold uppercase tracking-widest mt-0.5">{{ $s->sender_name }} — {{ $s->sender_company ?: 'No company context' }}</p>
                                </div>
                                <div class="flex items-center gap-2">
                                    <span class="px-2 py-0.5 rounded text-[8px] font-bold uppercase border tracking-wider {{ $s->status === 'active' ? 'bg-green-50 text-green-700 border-green-100' : 'bg-slate-50 text-slate-500 border-slate-200' }}">
                                        {{ $s->status }}
                                    </span>
                                </div>
                            </div>

                            {{-- Preview stack --}}
                            <div class="grid grid-cols-2 sm:grid-cols-3 gap-3 text-[10px] bg-slate-50/50 border border-slate-100 rounded-2xl p-4">
                                <div class="space-y-0.5">
                                    <span class="block text-[8px] font-bold text-slate-400 uppercase tracking-wider">Role</span>
                                    <span class="font-bold text-slate-700 truncate block">{{ $s->sender_role ?: 'Not specified' }}</span>
                                </div>
                                <div class="space-y-0.5">
                                    <span class="block text-[8px] font-bold text-slate-400 uppercase tracking-wider">Region</span>
                                    <span class="font-bold text-slate-700 truncate block">{{ $s->sender_region ?: 'Not specified' }}</span>
                                </div>
                                <div class="space-y-0.5">
                                    <span class="block text-[8px] font-bold text-slate-400 uppercase tracking-wider">Industry</span>
                                    <span class="font-bold text-slate-700 truncate block">{{ $s->sender_industry ?: 'Not specified' }}</span>
                                </div>
                                <div class="space-y-0.5">
                                    <span class="block text-[8px] font-bold text-slate-400 uppercase tracking-wider">EO Chapter</span>
                                    <span class="font-bold text-slate-700 truncate block">{{ $s->sender_eo_chapter ?: 'Not specified' }}</span>
                                </div>
                                <div class="space-y-0.5">
                                    <span class="block text-[8px] font-bold text-slate-400 uppercase tracking-wider">LinkedIn</span>
                                    @if($s->sender_linkedin)
                                        <a href="{{ $s->sender_linkedin }}" target="_blank" class="text-brand-blue font-bold hover:underline truncate block">View Profile</a>
                                    @else
                                        <span class="text-slate-400 block font-semibold">Not available</span>
                                    @endif
                                </div>
                                <div class="space-y-0.5">
                                    <span class="block text-[8px] font-bold text-slate-400 uppercase tracking-wider">Website</span>
                                    @if($s->sender_website)
                                        <a href="{{ $s->sender_website }}" target="_blank" class="text-brand-blue font-bold hover:underline truncate block">Visit Website</a>
                                    @else
                                        <span class="text-slate-400 block font-semibold">Not available</span>
                                    @endif
                                </div>
                            </div>
                        </div>

                        <div class="flex justify-between items-center pt-4 border-t border-slate-100/80 mt-4">
                            <div>
                                @if(!$s->is_default)
                                    <form action="{{ route('settings.senders.default', $s) }}" method="POST">
                                        @csrf
                                        <button type="submit" class="text-brand-blue hover:underline text-[9px] font-bold uppercase tracking-wider">
                                            Make Default
                                        </button>
                                    </form>
                                @endif
                            </div>
                            <div class="flex items-center gap-2">
                                <button @click="openEditSender(@js($s))" class="p-2 text-slate-400 hover:text-brand-blue transition-colors rounded-lg hover:bg-slate-50" title="Edit Profile">
                                    <svg class="w-4.5 h-4.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"></path></svg>
                                </button>
                                <form action="{{ route('settings.senders.destroy', $s) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete this sender identity?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="p-2 text-slate-400 hover:text-red-600 transition-colors rounded-lg hover:bg-slate-50" title="Delete Profile">
                                        <svg class="w-4.5 h-4.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="col-span-2 text-center py-16 border-2 border-dashed border-slate-100 rounded-3xl bg-white shadow-sm">
                        <p class="text-slate-400 text-xs font-semibold">No sender identities created yet.</p>
                    </div>
                @endforelse
            </div>
        </div>
    </div>
</x-app-layout>
