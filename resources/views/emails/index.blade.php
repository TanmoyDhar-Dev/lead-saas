<x-app-layout>
    <x-slot name="header">
        Correspondence Intelligence Log
    </x-slot>
    <x-slot name="subheader">
        History of AI-Generated Outreaches and Automated Sequences
    </x-slot>
    <x-slot name="actions">
        {{-- Template Manager Button --}}
        <button x-data @click="$dispatch('open-template-manager')" class="bg-white border border-slate-200 text-slate-600 px-4 py-2.5 rounded-xl text-xs font-bold hover:bg-slate-50 transition-all flex items-center shadow-sm">
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 002-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path></svg>
            TEMPLATE ENGINE
        </button>
    </x-slot>

    <div class="space-y-6" x-data="correspondenceManager()">
        <div class="bg-white rounded-3xl p-4 shadow-sm border border-slate-100 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-slate-50/50 border-b border-slate-100">
                            <th class="px-6 py-5 text-[10px] font-bold text-slate-400 uppercase tracking-widest whitespace-nowrap">Timestamp</th>
                            <th class="px-6 py-5 text-[10px] font-bold text-slate-400 uppercase tracking-widest whitespace-nowrap">Recipient Entity</th>
                            <th class="px-6 py-5 text-[10px] font-bold text-slate-400 uppercase tracking-widest whitespace-nowrap">Topic / Subject</th>
                            <th class="px-6 py-5 text-[10px] font-bold text-slate-400 uppercase tracking-widest whitespace-nowrap w-1/3">Body Snippet</th>
                            <th class="px-6 py-5 text-[10px] font-bold text-slate-400 uppercase tracking-widest whitespace-nowrap">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse ($logs as $log)
                        <tr class="hover:bg-slate-50 transition-colors group cursor-pointer" @click="openProfile({{ $log->toJson() }})">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-xs font-bold text-slate-800">{{ $log->updated_at->format('M d, Y') }}</div>
                                <div class="text-[10px] font-medium text-slate-500 mt-0.5">{{ $log->updated_at->format('H:i') }}</div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="text-xs font-bold text-brand-blue">{{ optional($log->lead)->full_name ?? 'Unknown Lead' }}</div>
                                <div class="text-[10px] text-slate-500 mt-0.5">{{ optional($log->lead)->position }} at {{ optional($log->lead)->company_name }}</div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="text-xs font-bold text-slate-700 truncate max-w-xs">{{ $log->email_topic }}</div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="text-[11px] text-slate-500 truncate max-w-sm">{{ Str::limit($log->email_body, 70) }}</div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-[10px] font-bold uppercase {{ $log->email_sent === 'sent' ? 'bg-emerald-50 text-emerald-600' : 'bg-amber-50 text-amber-600' }}">
                                    {{ $log->email_sent }}
                                </span>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="5" class="px-6 py-12 text-center text-slate-400 text-sm">
                                No correspondence logs found in the database.
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($logs->hasPages())
                <div class="p-4 border-t border-slate-100 bg-slate-50/50">
                    {{ $logs->links() }}
                </div>
            @endif
        </div>

        {{-- Intelligence Profile Modal --}}
        <div x-show="profileOpen" style="display: none;" class="fixed inset-0 z-50 flex items-center justify-center p-4 sm:p-6" aria-labelledby="modal-title" role="dialog" aria-modal="true">
            <div x-show="profileOpen" x-transition.opacity class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm" @click="profileOpen = false"></div>
            
            <div x-show="profileOpen" x-transition.scale.origin.bottom class="relative bg-white rounded-2xl shadow-2xl overflow-hidden w-full max-w-3xl max-h-[90vh] flex flex-col">
                <div class="px-6 py-5 border-b border-slate-100 flex items-center justify-between shrink-0 bg-slate-50/80">
                    <h3 class="text-lg font-black text-slate-800 tracking-tight">Correspondence Profile</h3>
                    <button @click="profileOpen = false" class="text-slate-400 hover:text-slate-600 bg-white hover:bg-slate-100 rounded-full p-2 transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                    </button>
                </div>
                
                <div class="p-6 overflow-y-auto flex-1 custom-scrollbar">
                    <template x-if="activeLog">
                        <div class="space-y-6">
                            {{-- Header Meta --}}
                            <div class="flex items-center justify-between">
                                <div>
                                    <h4 class="text-xl font-bold text-slate-900" x-text="activeLog.lead?.full_name || 'Unknown Entity'"></h4>
                                    <p class="text-sm text-slate-500 mt-1" x-text="(activeLog.lead?.position || '') + ' at ' + (activeLog.lead?.company_name || '')"></p>
                                </div>
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold uppercase"
                                      :class="activeLog.email_sent === 'sent' ? 'bg-emerald-50 text-emerald-600 border border-emerald-100' : 'bg-amber-50 text-amber-600 border border-amber-100'"
                                      x-text="activeLog.email_sent"></span>
                            </div>

                            {{-- Subject --}}
                            <div class="bg-slate-50 p-4 rounded-xl border border-slate-100">
                                <label class="text-[10px] font-bold text-slate-400 uppercase tracking-widest block mb-1">Topic</label>
                                <div class="text-sm font-bold text-slate-800" x-text="activeLog.email_topic"></div>
                            </div>

                            {{-- Body --}}
                            <div>
                                <label class="text-[10px] font-bold text-slate-400 uppercase tracking-widest block mb-2">Message Payload</label>
                                <div class="p-5 bg-white border border-slate-200 rounded-xl text-sm text-slate-700 whitespace-pre-wrap leading-relaxed" x-text="activeLog.email_body"></div>
                            </div>

                            {{-- Sender Context --}}
                            <div>
                                <label class="text-[10px] font-bold text-slate-400 uppercase tracking-widest block mb-2">Sender Metadata</label>
                                <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                                    <div class="p-3 bg-slate-50 rounded-lg">
                                        <div class="text-[9px] font-bold text-slate-400 uppercase">Sender</div>
                                        <div class="text-xs font-bold text-slate-700 mt-1" x-text="activeLog.sender_name || '-'"></div>
                                    </div>
                                    <div class="p-3 bg-slate-50 rounded-lg">
                                        <div class="text-[9px] font-bold text-slate-400 uppercase">Role</div>
                                        <div class="text-xs font-bold text-slate-700 mt-1" x-text="activeLog.sender_role || '-'"></div>
                                    </div>
                                    <div class="p-3 bg-slate-50 rounded-lg">
                                        <div class="text-[9px] font-bold text-slate-400 uppercase">Company</div>
                                        <div class="text-xs font-bold text-slate-700 mt-1" x-text="activeLog.sender_company || '-'"></div>
                                    </div>
                                    <div class="p-3 bg-slate-50 rounded-lg">
                                        <div class="text-[9px] font-bold text-slate-400 uppercase">Timestamp</div>
                                        <div class="text-xs font-bold text-slate-700 mt-1" x-text="new Date(activeLog.created_at).toLocaleString()"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </template>
                </div>
            </div>
        </div>
    </div>

    {{-- Template Manager Modal --}}
    <div x-data="templateManager()" x-show="open" style="display: none;" class="fixed inset-0 z-50 flex items-center justify-center p-4 sm:p-6" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div x-show="open" x-transition.opacity class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm" @click="open = false"></div>
        
        <div x-show="open" x-transition.scale.origin.bottom class="relative bg-white rounded-2xl shadow-2xl overflow-hidden w-full max-w-4xl max-h-[90vh] flex flex-col">
            <div class="px-6 py-5 border-b border-slate-100 flex items-center justify-between shrink-0 bg-slate-50/80">
                <h3 class="text-lg font-black text-slate-800 tracking-tight">Template Engine</h3>
                <button @click="open = false" class="text-slate-400 hover:text-slate-600 bg-white hover:bg-slate-100 rounded-full p-2 transition-colors">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>
            </div>
            
            <div class="flex-1 flex flex-col md:flex-row overflow-hidden">
                {{-- Tabs --}}
                <div class="w-full md:w-48 bg-slate-50 border-b md:border-b-0 md:border-r border-slate-200 shrink-0 p-4 space-y-2">
                    <button @click="activeTab = 'body'" :class="activeTab === 'body' ? 'bg-white shadow-sm text-brand-blue font-bold border-l-2 border-brand-blue' : 'text-slate-600 hover:bg-slate-100 font-semibold'" class="w-full text-left px-4 py-2.5 rounded-lg text-sm transition-all">Body Templates</button>
                    <button @click="activeTab = 'signature'" :class="activeTab === 'signature' ? 'bg-white shadow-sm text-brand-blue font-bold border-l-2 border-brand-blue' : 'text-slate-600 hover:bg-slate-100 font-semibold'" class="w-full text-left px-4 py-2.5 rounded-lg text-sm transition-all">Signatures</button>
                    <button @click="activeTab = 'context'" :class="activeTab === 'context' ? 'bg-white shadow-sm text-brand-blue font-bold border-l-2 border-brand-blue' : 'text-slate-600 hover:bg-slate-100 font-semibold'" class="w-full text-left px-4 py-2.5 rounded-lg text-sm transition-all">Sender Context</button>
                </div>
                
                {{-- Content --}}
                <div class="flex-1 p-6 overflow-y-auto custom-scrollbar bg-white">
                    <form action="{{ route('templates.store') }}" method="POST" class="space-y-4">
                        @csrf
                        <input type="hidden" name="type" x-model="activeTab">
                        
                        <div class="bg-slate-50 rounded-xl p-5 border border-slate-100 mb-6">
                            <h4 class="text-sm font-bold text-slate-800 mb-4 flex items-center">
                                <svg class="w-4 h-4 mr-2 text-brand-blue" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                                Create New <span class="ml-1 capitalize" x-text="activeTab"></span>
                            </h4>
                            
                            <div class="space-y-4">
                                <div>
                                    <label class="text-[10px] font-bold text-slate-400 uppercase tracking-widest block mb-1">Template Name</label>
                                    <input type="text" name="name" required class="w-full bg-white border-slate-200 rounded-lg text-sm py-2 px-3 focus:ring-brand-blue focus:border-brand-blue">
                                </div>
                                
                                <div x-show="activeTab === 'body' || activeTab === 'signature'">
                                    <label class="text-[10px] font-bold text-slate-400 uppercase tracking-widest block mb-1">Content</label>
                                    <textarea name="content" rows="4" :required="activeTab === 'body' || activeTab === 'signature'" class="w-full bg-white border-slate-200 rounded-lg text-sm py-2 px-3 focus:ring-brand-blue focus:border-brand-blue custom-scrollbar"></textarea>
                                </div>
                                
                                <div x-show="activeTab === 'context'" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <label class="text-[9px] font-bold text-slate-400 uppercase block mb-1">Sender Name</label>
                                        <input type="text" name="sender_name" class="w-full bg-white border-slate-200 rounded-lg text-sm py-2 px-3">
                                    </div>
                                    <div>
                                        <label class="text-[9px] font-bold text-slate-400 uppercase block mb-1">Sender Role</label>
                                        <input type="text" name="sender_role" class="w-full bg-white border-slate-200 rounded-lg text-sm py-2 px-3">
                                    </div>
                                    <div>
                                        <label class="text-[9px] font-bold text-slate-400 uppercase block mb-1">Company</label>
                                        <input type="text" name="sender_company" class="w-full bg-white border-slate-200 rounded-lg text-sm py-2 px-3">
                                    </div>
                                </div>
                                
                                <div class="flex justify-end pt-2">
                                    <button type="submit" class="bg-brand-blue text-white px-5 py-2 rounded-lg text-xs font-bold hover:bg-blue-600 transition-colors">Save Template</button>
                                </div>
                            </div>
                        </div>
                    </form>
                    
                    <div>
                        <h4 class="text-xs font-bold text-slate-400 uppercase tracking-widest mb-4">Existing Templates</h4>
                        <div class="text-sm text-slate-500 italic p-4 bg-slate-50 rounded-xl border border-slate-100 text-center">
                            Note: Template listing implementation uses standard blade loops inside Alpine.js or requires a JSON data source.
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        function correspondenceManager() {
            return {
                profileOpen: false,
                activeLog: null,
                
                openProfile(log) {
                    this.activeLog = log;
                    this.profileOpen = true;
                }
            }
        }

        function templateManager() {
            return {
                open: false,
                activeTab: 'body',
                init() {
                    window.addEventListener('open-template-manager', () => {
                        this.open = true;
                    });
                }
            }
        }
    </script>
    @endpush
</x-app-layout>
