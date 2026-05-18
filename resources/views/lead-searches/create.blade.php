<x-app-layout>
    <x-slot name="header">
        Lead Hunter
    </x-slot>

    <x-slot name="subheader">
        eGSales AI Intelligence • Start New Extraction
    </x-slot>

    <div class="max-w-3xl mx-auto space-y-6" x-data="{ volume: 50, volumeInvalid: false }">
        {{-- Info Banner --}}
        <div class="bg-blue-50 border border-blue-100 p-5 rounded-2xl flex items-start">
            <div class="w-10 h-10 bg-blue-100 rounded-xl flex items-center justify-center text-blue-600 mr-4 shrink-0">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
            </div>
            <div>
                <h4 class="text-sm font-bold text-blue-900 mb-1">Targeted Lead Extraction</h4>
                <p class="text-xs text-blue-700 leading-relaxed">
                    Define your criteria below. Our n8n pipeline will scrape LinkedIn profiles matching your query. Leads will appear in the <b>Leads</b> section as they are collected.
                </p>
            </div>
        </div>

        {{-- Search Form --}}
        <div class="bg-white rounded-3xl shadow-sm border border-slate-100 overflow-hidden">
            <div class="p-6 border-b border-slate-100">
                <h3 class="font-bold text-slate-800">Configure Hunter Parameters</h3>
                <p class="text-xs text-slate-400 mt-1">Maximum 100 leads per individual search query.</p>
            </div>

            <form method="POST" action="{{ route('lead-searches.store') }}" class="p-6 space-y-6">
                @csrf

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    {{-- Country --}}
                    <div class="space-y-1">
                        <label for="country" class="text-[10px] font-bold text-slate-400 uppercase tracking-widest px-1">Country *</label>
                        <input type="text" name="country" id="country" value="{{ old('country') }}" required
                               placeholder="e.g. United States"
                               class="w-full bg-slate-50 border-slate-200 rounded-2xl text-sm focus:ring-brand-blue focus:border-brand-blue py-3 px-4">
                        @error('country') <p class="text-red-500 text-[10px] font-bold mt-1 px-1">{{ $message }}</p> @enderror
                    </div>

                    {{-- City --}}
                    <div class="space-y-1">
                        <label for="city" class="text-[10px] font-bold text-slate-400 uppercase tracking-widest px-1">City *</label>
                        <input type="text" name="city" id="city" value="{{ old('city') }}" required
                               placeholder="e.g. California"
                               class="w-full bg-slate-50 border-slate-200 rounded-2xl text-sm focus:ring-brand-blue focus:border-brand-blue py-3 px-4">
                        @error('city') <p class="text-red-500 text-[10px] font-bold mt-1 px-1">{{ $message }}</p> @enderror
                    </div>

                    {{-- Industry --}}
                    <div class="space-y-1">
                        <label for="industry" class="text-[10px] font-bold text-slate-400 uppercase tracking-widest px-1">Industry</label>
                        <input type="text" name="industry" id="industry" value="{{ old('industry') }}"
                               placeholder="e.g. Artificial Intelligence"
                               class="w-full bg-slate-50 border-slate-200 rounded-2xl text-sm focus:ring-brand-blue focus:border-brand-blue py-3 px-4">
                        @error('industry') <p class="text-red-500 text-[10px] font-bold mt-1 px-1">{{ $message }}</p> @enderror
                    </div>

                    {{-- Position --}}
                    <div class="space-y-1">
                        <label for="position" class="text-[10px] font-bold text-slate-400 uppercase tracking-widest px-1">Position / Role</label>
                        <input type="text" name="position" id="position" value="{{ old('position') }}"
                               placeholder='e.g. "CEO" OR "Founder"'
                               class="w-full bg-slate-50 border-slate-200 rounded-2xl text-sm focus:ring-brand-blue focus:border-brand-blue py-3 px-4">
                        <p class="text-[10px] text-slate-400 px-1 mt-1">Use OR to combine roles.</p>
                        @error('position') <p class="text-red-500 text-[10px] font-bold mt-1 px-1">{{ $message }}</p> @enderror
                    </div>

                    {{-- Volume --}}
                    <div class="space-y-1 col-span-1 md:col-span-2">
                        <label for="volume" class="text-[10px] font-bold text-slate-400 uppercase tracking-widest px-1">Volume (Max 100) *</label>
                        <div class="relative">
                            <input type="number" name="volume" id="volume" 
                                   x-model.number="volume"
                                   @input="volumeInvalid = (volume < 1 || volume > 100 || !volume)"
                                   required
                                   placeholder="50"
                                   :class="volumeInvalid ? 'border-red-500 ring-red-100' : 'border-slate-200 focus:ring-brand-blue'"
                                   class="w-full bg-slate-50 rounded-2xl text-sm py-3 px-4 transition-all">
                            <div class="absolute right-4 top-1/2 -translate-y-1/2 text-[10px] font-bold text-slate-400">LEADS</div>
                        </div>
                        <template x-if="volumeInvalid">
                            <p class="text-red-500 text-[10px] font-bold mt-1 px-1">Maximum 100 leads per search.</p>
                        </template>
                        @error('volume') <p class="text-red-500 text-[10px] font-bold mt-1 px-1">{{ $message }}</p> @enderror
                    </div>
                </div>

                <div class="flex items-center justify-between pt-4 border-t border-slate-100">
                    <a href="{{ route('lead-searches.index') }}" class="text-sm text-slate-500 hover:text-slate-700 font-medium transition-colors">← Cancel</a>
                    <button type="submit" 
                            :disabled="volumeInvalid"
                            :class="volumeInvalid ? 'opacity-50 cursor-not-allowed bg-slate-400' : 'bg-brand-blue hover:bg-blue-600 shadow-blue-500/20'"
                            class="text-white font-bold py-3 px-8 rounded-2xl transition-all transform active:scale-95 shadow-lg flex items-center">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                        RUN HUNTER
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
