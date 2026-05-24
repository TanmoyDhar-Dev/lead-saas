<x-app-layout>
    <x-slot name="header">
        Lead Hunter
    </x-slot>

    <x-slot name="subheader">
        eGSales AI Intelligence • Start New Extraction
    </x-slot>

    <div class="max-w-3xl mx-auto space-y-6" x-data="leadHunterForm()">
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

        {{-- Success / Error Feedback --}}
        <div x-show="successMessage" x-transition x-cloak class="bg-emerald-50 border border-emerald-200 p-4 rounded-2xl flex items-start">
            <svg class="w-5 h-5 text-emerald-500 mr-3 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
            <p class="text-sm text-emerald-800 font-medium" x-text="successMessage"></p>
        </div>

        <div x-show="errorMessage" x-transition x-cloak class="bg-red-50 border border-red-200 p-4 rounded-2xl flex items-start">
            <svg class="w-5 h-5 text-red-500 mr-3 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
            <p class="text-sm text-red-800 font-medium" x-text="errorMessage"></p>
        </div>

        {{-- Search Form --}}
        <div class="bg-white rounded-3xl shadow-sm border border-slate-100 overflow-hidden relative">
            
            {{-- Loading Overlay --}}
            <div x-show="isSubmitting" x-transition.opacity
                 class="absolute inset-0 z-50 bg-white/80 backdrop-blur-sm flex flex-col items-center justify-center rounded-3xl border border-blue-100">
                <div class="w-12 h-12 border-4 border-brand-blue border-t-transparent rounded-full animate-spin mb-4 shadow-lg shadow-blue-500/20"></div>
                <h3 class="text-lg font-bold text-slate-800" x-text="currentStatusMessage"></h3>
                <p class="text-xs text-slate-500 font-medium mt-2">Please wait, contacting n8n worker...</p>
            </div>

            <div class="p-6 border-b border-slate-100">
                <h3 class="font-bold text-slate-800">Configure Hunter Parameters</h3>
                <p class="text-xs text-slate-400 mt-1">Maximum 100 leads per individual search query.</p>
            </div>

            <form method="POST" action="{{ route('lead-searches.store') }}" @submit.prevent="submitForm" class="p-6 space-y-6">
                @csrf

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    {{-- Target Location --}}
                    <div class="space-y-1 col-span-1 md:col-span-2">
                        <label for="target_location" class="text-[10px] font-bold text-slate-400 uppercase tracking-widest px-1">Target Location *</label>
                        <select name="target_location" id="target_location" x-model="formData.target_location" required
                               class="w-full bg-slate-50 border-slate-200 rounded-2xl text-sm focus:ring-brand-blue focus:border-brand-blue py-3 px-4 transition-all uppercase">
                            <option value="" disabled selected>SELECT A TARGET LOCATION...</option>
                            <template x-for="loc in locations" :key="loc">
                                <option :value="loc" x-text="loc.toUpperCase()"></option>
                            </template>
                        </select>
                    </div>

                    {{-- Industry --}}
                    <div class="space-y-1">
                        <label for="industry" class="text-[10px] font-bold text-slate-400 uppercase tracking-widest px-1">Industry</label>
                        <input type="text" name="industry" id="industry" x-model="formData.industry"
                               placeholder="e.g. Artificial Intelligence"
                               class="w-full bg-slate-50 border-slate-200 rounded-2xl text-sm focus:ring-brand-blue focus:border-brand-blue py-3 px-4 transition-all"
                               @input="formData.industry = formData.industry.toLowerCase()">
                    </div>

                    {{-- Position --}}
                    <div class="space-y-1">
                        <label for="position" class="text-[10px] font-bold text-slate-400 uppercase tracking-widest px-1">Position / Role</label>
                        <input type="text" name="position" id="position" x-model="formData.position"
                               placeholder='e.g. "CEO" OR "Founder"'
                               class="w-full bg-slate-50 border-slate-200 rounded-2xl text-sm focus:ring-brand-blue focus:border-brand-blue py-3 px-4 transition-all"
                               @input="formData.position = formData.position.toLowerCase()">
                        <p class="text-[10px] text-slate-400 px-1 mt-1">Use OR to combine roles.</p>
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
                    </div>
                </div>

                <div class="flex items-center justify-between pt-4 border-t border-slate-100">
                    <a href="{{ route('lead-searches.index') }}" class="text-sm text-slate-500 hover:text-slate-700 font-medium transition-colors">← Cancel</a>
                    <button type="submit" 
                            :disabled="volumeInvalid || isSubmitting"
                            :class="volumeInvalid || isSubmitting ? 'opacity-50 cursor-not-allowed bg-slate-400' : 'bg-brand-blue hover:bg-blue-600 shadow-blue-500/20'"
                            class="text-white font-bold py-3 px-8 rounded-2xl transition-all transform active:scale-95 shadow-lg flex items-center">
                        <svg x-show="!isSubmitting" class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                        <svg x-show="isSubmitting" class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                        <span x-text="isSubmitting ? 'PROCESSING...' : 'RUN HUNTER'"></span>
                    </button>
                </div>
            </form>
        </div>
    </div>

    @push('scripts')
    <script>
        function leadHunterForm() {
            return {
                volume: 50,
                volumeInvalid: false,
                isSubmitting: false,
                statusIndex: 0,
                statusMessages: ['Processing...', 'Running Hunter...', 'Searching leads...', 'Awaiting n8n response...'],
                statusInterval: null,
                successMessage: '',
                errorMessage: '',
                locations: [],
                formData: {
                    target_location: '',
                    industry: '',
                    position: ''
                },

                get currentStatusMessage() {
                    return this.statusMessages[this.statusIndex];
                },

                init() {
                    // Initialize validity
                    this.$watch('volume', value => {
                        this.volumeInvalid = (value < 1 || value > 100 || !value);
                    });
                    
                    // Fetch locations list
                    fetch('/locations.json')
                        .then(r => r.json())
                        .then(d => {
                            this.locations = d;
                        })
                        .catch(e => console.error("Error loading locations", e));
                },

                submitForm(event) {
                    if (this.volumeInvalid || this.isSubmitting) return;

                    this.isSubmitting = true;
                    this.successMessage = '';
                    this.errorMessage = '';
                    this.statusIndex = 0;

                    // Rotate messages
                    this.statusInterval = setInterval(() => {
                        this.statusIndex = (this.statusIndex + 1) % this.statusMessages.length;
                    }, 1200);

                    const formElement = event.target;
                    const data = new FormData(formElement);
                    
                    // Final lowercasing enforcement before sending
                    data.set('target_location', (data.get('target_location') || '').toLowerCase().trim());
                    data.set('industry', (data.get('industry') || '').toLowerCase().trim());
                    data.set('position', (data.get('position') || '').toLowerCase().trim());
                    data.set('volume', this.volume);

                    fetch(formElement.action, {
                        method: 'POST',
                        body: data,
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest',
                            'Accept': 'application/json'
                        }
                    })
                    .then(async response => {
                        clearInterval(this.statusInterval);
                        
                        let result;
                        try {
                            result = await response.json();
                        } catch (e) {
                            throw new Error('Invalid JSON response from server');
                        }
                        
                        if (response.ok) {
                            this.successMessage = result.message || 'Lead Hunter started successfully!';
                            
                            // Show success for 1.5 seconds, then redirect
                            setTimeout(() => {
                                if (result.redirect) {
                                    window.location.href = result.redirect;
                                }
                            }, 1500);
                        } else {
                            this.isSubmitting = false;
                            if (response.status === 422 && result.errors) {
                                // Validation error
                                this.errorMessage = Object.values(result.errors).flat().join(' ');
                            } else {
                                this.errorMessage = result.error || 'An error occurred during submission.';
                            }
                        }
                    })
                    .catch(error => {
                        clearInterval(this.statusInterval);
                        this.isSubmitting = false;
                        this.errorMessage = 'A network error occurred. Please try again later.';
                    });
                }
            }
        }
    </script>
    @endpush
</x-app-layout>
