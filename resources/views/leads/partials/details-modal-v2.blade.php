{{-- Reusable Lead Details Modal V2 --}}
<div x-show="modalOpen"
     x-cloak
     class="fixed inset-0 z-[9999] flex items-center justify-center p-4 sm:p-6">
    
    {{-- Backdrop --}}
    <div x-show="modalOpen" 
         x-transition:enter="transition ease-out duration-300" 
         x-transition:enter-start="opacity-0" 
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-200" 
         x-transition:leave-start="opacity-100" 
         x-transition:leave-end="opacity-0"
         @click="closeModal()" 
         class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm"></div>

    {{-- Modal Content --}}
    <div x-show="modalOpen"
         x-transition:enter="transition ease-out duration-300" 
         x-transition:enter-start="opacity-0 scale-95" 
         x-transition:enter-end="opacity-100 scale-100"
         x-transition:leave="transition ease-in duration-200" 
         x-transition:leave-start="opacity-100 scale-100" 
         x-transition:leave-end="opacity-0 scale-95"
         class="relative bg-white rounded-[2.5rem] shadow-2xl w-full max-w-6xl max-h-[92vh] overflow-hidden flex flex-col">
        
        {{-- Loading State --}}
        <div x-show="modalLoading" class="p-20 text-center flex flex-col items-center justify-center">
            <div class="animate-spin w-10 h-10 border-4 border-brand-blue border-t-transparent rounded-full mb-4"></div>
            <p class="text-slate-400 font-bold text-xs uppercase tracking-widest">Retrieving Intelligence...</p>
        </div>

        <div x-show="!modalLoading && modalError" class="p-12 text-center max-w-lg mx-auto">
            <p class="text-red-600 text-sm font-medium" x-text="modalError"></p>
            <button type="button" @click="closeModal()" class="mt-6 px-5 py-2.5 rounded-xl bg-slate-100 text-slate-700 text-xs font-bold hover:bg-slate-200">Close</button>
        </div>

        {{-- Content --}}
        <div x-show="!modalLoading && modalData && !modalError" class="flex flex-col h-full overflow-hidden">
            {{-- Header --}}
            <div class="p-6 border-b border-slate-100 bg-slate-50/50 flex items-center justify-between shrink-0">
                <div class="flex items-center">
                    <div class="w-14 h-14 rounded-2xl bg-brand-blue flex items-center justify-center text-white font-bold text-xl mr-5 shadow-lg shadow-blue-500/20">
                        <span x-text="modalData?.person_name?.charAt(0) || '?'"></span>
                    </div>
                    <div>
                        <h3 class="text-xl font-bold text-slate-800" x-text="modalData?.person_name || 'Unknown Lead'"></h3>
                        <div class="flex items-center text-xs text-slate-400 font-medium mt-0.5">
                            <span class="text-brand-blue font-bold mr-2 uppercase tracking-wide" x-text="modalData?.company_name || 'No Company'"></span>
                            <span class="mx-2">•</span>
                            <span x-text="modalData?.position_by_apifiapi || modalData?.position_by_search_param || 'No Position'"></span>
                        </div>
                    </div>
                </div>
                <button type="button" @click="closeModal()" class="w-10 h-10 flex items-center justify-center rounded-xl bg-white border border-slate-200 text-slate-400 hover:text-slate-600 hover:border-slate-300 transition-all">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>
            </div>

            {{-- Body --}}
            <div class="p-8 overflow-y-auto custom-scrollbar">
                <div class="space-y-10">
                    {{-- Section 1: Person --}}
                    <div>
                        <h4 class="text-[10px] font-bold text-slate-400 uppercase tracking-[0.2em] mb-4 flex items-center">
                            <span class="w-8 h-px bg-slate-200 mr-3"></span>
                            Person Information
                        </h4>
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                            <template x-for="field in getSectionFields('person')" :key="field.key">
                                <div class="bg-slate-50/50 border border-slate-100 rounded-xl p-4 transition-all">
                                    <label class="text-[9px] font-bold text-slate-400 uppercase tracking-widest block mb-1.5" x-text="field.label"></label>
                                    <template x-if="field.isUrl && field.value">
                                        <a :href="field.value" target="_blank" class="text-xs font-bold text-brand-blue hover:underline break-all block" x-text="field.value"></a>
                                    </template>
                                    <template x-if="!field.isUrl || !field.value">
                                        <p class="text-xs font-medium text-slate-700 leading-relaxed break-words" x-text="field.value || 'Not available'"></p>
                                    </template>
                                </div>
                            </template>
                        </div>
                    </div>

                    {{-- Section 2: Company --}}
                    <div>
                        <h4 class="text-[10px] font-bold text-slate-400 uppercase tracking-[0.2em] mb-4 flex items-center">
                            <span class="w-8 h-px bg-slate-200 mr-3"></span>
                            Company Information
                        </h4>
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                            <template x-for="field in getSectionFields('company')" :key="field.key">
                                <div class="bg-slate-50/50 border border-slate-100 rounded-xl p-4 transition-all">
                                    <label class="text-[9px] font-bold text-slate-400 uppercase tracking-widest block mb-1.5" x-text="field.label"></label>
                                    <template x-if="field.isUrl && field.value">
                                        <a :href="field.value" target="_blank" class="text-xs font-bold text-brand-blue hover:underline break-all block" x-text="field.value"></a>
                                    </template>
                                    <template x-if="!field.isUrl || !field.value">
                                        <p class="text-xs font-medium text-slate-700 leading-relaxed break-words" x-text="field.value || 'Not available'"></p>
                                    </template>
                                </div>
                            </template>
                        </div>
                    </div>

                    {{-- Section 3: Search & Enrichment --}}
                    <div>
                        <h4 class="text-[10px] font-bold text-slate-400 uppercase tracking-[0.2em] mb-4 flex items-center">
                            <span class="w-8 h-px bg-slate-200 mr-3"></span>
                            Search & Enrichment
                        </h4>
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                            <template x-for="field in getSectionFields('search')" :key="field.key">
                                <div class="bg-slate-50/50 border border-slate-100 rounded-xl p-4 transition-all">
                                    <label class="text-[9px] font-bold text-slate-400 uppercase tracking-widest block mb-1.5" x-text="field.label"></label>
                                    <p class="text-xs font-medium text-slate-700 leading-relaxed" x-text="field.value || 'Not available'"></p>
                                </div>
                            </template>
                        </div>
                    </div>

                    {{-- Section 4: Status --}}
                    <div>
                        <h4 class="text-[10px] font-bold text-slate-400 uppercase tracking-[0.2em] mb-4 flex items-center">
                            <span class="w-8 h-px bg-slate-200 mr-3"></span>
                            Status Information
                        </h4>
                        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                            <template x-for="field in getSectionFields('status')" :key="field.key">
                                <div class="bg-slate-50/50 border border-slate-100 rounded-xl p-4 transition-all">
                                    <label class="text-[9px] font-bold text-slate-400 uppercase tracking-widest block mb-1.5" x-text="field.label"></label>
                                    <p class="text-xs font-medium text-slate-700 leading-relaxed" x-text="field.value || 'Not available'"></p>
                                </div>
                            </template>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Footer --}}
            <div class="p-6 border-t border-slate-100 bg-slate-50/50 flex items-center justify-end shrink-0">
                <div class="flex space-x-3">
                    <template x-if="modalData?.personal__linkdin_url">
                        <a :href="modalData.personal__linkdin_url" target="_blank" class="bg-[#0077b5] text-white px-6 py-2.5 rounded-xl text-xs font-bold flex items-center shadow-lg shadow-blue-500/10 hover:brightness-110 transition-all uppercase tracking-wide">
                            LinkedIn Profile
                        </a>
                    </template>
                    
                    @if(Auth::user()->isAdmin())
                    <form :action="'/leads/' + modalLeadId" method="POST" onsubmit="return confirm('Delete this lead permanently?');">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="bg-red-50 text-red-500 border border-red-100 px-6 py-2.5 rounded-xl text-xs font-bold hover:bg-red-500 hover:text-white transition-all uppercase tracking-wide">
                            Delete Lead
                        </button>
                    </form>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

