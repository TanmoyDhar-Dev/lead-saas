<x-app-layout>
    <x-slot name="header">
        Lead Hunter
    </x-slot>

    <x-slot name="subheader">
        eGSales AI Intelligence • Start New Extraction
    </x-slot>

    @php
        $user = auth()->user();
        $plan = $user->userPlan;
        $limitReached = $user->role !== 'admin' && $plan && $plan->search_limit > 0 && $plan->searches_used >= $plan->search_limit;
    @endphp

    <div class="max-w-3xl mx-auto space-y-4" x-data="leadHunterForm()">
        {{-- Info Banner --}}
        @if($limitReached)
            <div class="bg-red-50 border border-red-200 p-5 rounded-2xl flex items-start">
                <div class="w-10 h-10 bg-red-100 rounded-xl flex items-center justify-center text-red-600 mr-4 shrink-0">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
                </div>
                <div>
                    <h4 class="text-sm font-bold text-red-900 mb-1">Search Limit Reached</h4>
                    <p class="text-xs text-red-700 leading-relaxed">
                        You have reached your maximum allotted searches ({{ $plan->search_limit }}). You cannot start new extractions. Please contact your administrator to upgrade your plan.
                    </p>
                </div>
            </div>
        @else
            <div class="bg-blue-50 border border-blue-100 p-5 rounded-2xl flex items-start">
                <div class="w-10 h-10 bg-blue-100 rounded-xl flex items-center justify-center text-blue-600 mr-4 shrink-0">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                </div>
                <div>
                    <h4 class="text-sm font-bold text-blue-900 mb-1">Targeted Lead Extraction</h4>
                    <p class="text-xs text-blue-700 leading-relaxed">
                        Define your criteria below. Our pipeline will scrape LinkedIn profiles matching your query. Leads will appear in the <b>Leads</b> section as they are collected.
                    </p>
                </div>
            </div>
        @endif

        {{-- Search Form --}}
        <div class="bg-white rounded-3xl shadow-sm border border-slate-100 overflow-hidden relative">
            
            {{-- Loading Overlay --}}
            <div x-show="isSubmitting" x-transition.opacity
                 class="absolute inset-0 z-50 bg-white/80 backdrop-blur-sm flex flex-col items-center justify-center rounded-3xl border border-blue-100">
                <div class="w-12 h-12 border-4 border-brand-blue border-t-transparent rounded-full animate-spin mb-4 shadow-lg shadow-blue-500/20"></div>
                <h3 class="text-lg font-bold text-slate-800" x-text="currentStatusMessage"></h3>
                <p class="text-xs text-slate-500 font-medium mt-2">Please wait, contacting n8n worker...</p>
            </div>

            <div class="px-6 py-4 border-b border-slate-100">
                <h3 class="font-bold text-slate-800">Configure Hunter Parameters</h3>
                <p class="text-xs text-slate-400 mt-1">Maximum 100 leads per individual search query.</p>
            </div>

            <form method="POST" action="{{ route('lead-searches.store') }}" @submit.prevent="submitForm" class="px-6 py-4 space-y-4">
                @csrf

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    {{-- Target Location (searchable, must match locations.json) --}}
                    <div class="space-y-1 col-span-1 md:col-span-2">
                        <label for="target_location" class="text-[10px] font-bold text-slate-400 uppercase tracking-widest px-1">Target Location *</label>
                        <div class="relative" @click.outside="locationDropdownOpen = false">
                            <input type="hidden" name="target_location" :value="formData.target_location" required>
                            <div class="relative">
                                <input type="text"
                                       id="target_location"
                                       x-ref="locationSearchInput"
                                       x-model="locationSearch"
                                       @focus="locationDropdownOpen = true"
                                       @input="onLocationSearchInput()"
                                       @keydown.escape.prevent="locationDropdownOpen = false"
                                       @keydown.enter.prevent="pickFirstFilteredLocation()"
                                       @keydown.arrow-down.prevent="highlightNextLocation()"
                                       @keydown.arrow-up.prevent="highlightPrevLocation()"
                                       placeholder="Type to search a location..."
                                       autocomplete="off"
                                       :class="locationInvalid ? 'border-red-500 ring-1 ring-red-100' : 'border-slate-200 focus:ring-brand-blue focus:border-brand-blue'"
                                       class="w-full bg-slate-50 rounded-2xl text-sm py-3 pl-4 pr-10 transition-all">
                                <button type="button"
                                        tabindex="-1"
                                        @click="locationDropdownOpen = !locationDropdownOpen; if (locationDropdownOpen) $nextTick(() => $refs.locationSearchInput?.focus())"
                                        class="absolute inset-y-0 right-0 pr-3 flex items-center text-slate-400 hover:text-slate-600">
                                    <svg class="w-4 h-4 transition-transform" :class="locationDropdownOpen ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                                </button>
                            </div>

                            <div x-show="locationDropdownOpen"
                                 x-cloak
                                 class="absolute left-0 right-0 top-full mt-2 z-40 bg-white border border-slate-200 rounded-2xl shadow-xl overflow-hidden">
                                <div class="max-h-64 overflow-y-auto p-1.5">
                                    <template x-for="(loc, idx) in filteredLocations" :key="loc">
                                        <button type="button"
                                                @mousedown.prevent="selectLocation(loc)"
                                                :class="(formData.target_location === loc || highlightedLocationIndex === idx) ? 'bg-blue-50 text-brand-blue' : 'text-slate-700 hover:bg-slate-50'"
                                                class="w-full text-left px-3 py-2.5 rounded-xl text-xs font-bold transition-colors truncate uppercase"
                                                x-text="loc"></button>
                                    </template>
                                    <p x-show="filteredLocations.length === 0" class="px-3 py-4 text-center text-[11px] text-slate-400 font-medium">
                                        No matching locations
                                    </p>
                                </div>
                            </div>
                        </div>
                        <p x-show="locationInvalid" class="text-red-500 text-[10px] font-bold mt-1 px-1" x-cloak>
                            Select a location from the list only.
                        </p>
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
                            <input type="number" name="volume" id="volume" min="1" max="100" step="1"
                                   x-model.number="volume"
                                   @keydown="if(['-','+','e','E','.'].includes($event.key)) $event.preventDefault()"
                                   @input="if(volume !== '' && volume !== null) { if(volume < 1) volume = 1; if(volume > 100) volume = 100; } volumeInvalid = !volume;"
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
                    
                    @if($limitReached)
                        <button type="button" disabled class="opacity-50 cursor-not-allowed bg-red-500 text-white font-bold py-3 px-8 rounded-2xl shadow-lg flex items-center">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path></svg>
                            <span>LIMIT REACHED</span>
                        </button>
                    @else
                        <button type="submit" 
                                :disabled="volumeInvalid || isSubmitting || !formData.target_location || locationInvalid"
                                :class="volumeInvalid || isSubmitting || !formData.target_location || locationInvalid ? 'opacity-50 cursor-not-allowed bg-slate-400' : 'bg-brand-blue hover:bg-blue-600 shadow-blue-500/20'"
                                class="text-white font-bold py-3 px-8 rounded-2xl transition-all transform active:scale-95 shadow-lg flex items-center">
                            <svg x-show="!isSubmitting" class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                            <svg x-show="isSubmitting" class="animate-spin -ml-1 mr-3 h-5 w-5 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                            <span x-text="isSubmitting ? 'PROCESSING...' : 'RUN HUNTER'"></span>
                        </button>
                    @endif
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
                locationSearch: '',
                locationDropdownOpen: false,
                locationInvalid: false,
                highlightedLocationIndex: 0,
                formData: {
                    target_location: '',
                    industry: '',
                    position: ''
                },

                get currentStatusMessage() {
                    return this.statusMessages[this.statusIndex];
                },

                get filteredLocations() {
                    const q = (this.locationSearch || '').trim().toLowerCase();
                    let list = this.locations;
                    if (q) {
                        list = list.filter(loc => String(loc).toLowerCase().includes(q));
                    }
                    return list.slice(0, 20);
                },

                init() {
                    this.$watch('volume', value => {
                        this.volumeInvalid = (value < 1 || value > 100 || !value);
                    });

                    fetch('/locations.json')
                        .then(r => r.json())
                        .then(d => {
                            this.locations = Array.isArray(d) ? d : [];
                        })
                        .catch(e => console.error('Error loading locations', e));
                },

                onLocationSearchInput() {
                    this.locationDropdownOpen = true;
                    this.highlightedLocationIndex = 0;
                    const q = (this.locationSearch || '').trim().toLowerCase();
                    const exact = this.locations.find(loc => String(loc).toLowerCase() === q);
                    if (exact) {
                        this.formData.target_location = exact;
                        this.locationInvalid = false;
                    } else {
                        this.formData.target_location = '';
                        this.locationInvalid = q !== '';
                    }
                },

                selectLocation(loc) {
                    this.formData.target_location = loc;
                    this.locationSearch = loc;
                    this.locationInvalid = false;
                    this.locationDropdownOpen = false;
                    this.highlightedLocationIndex = 0;
                },

                pickFirstFilteredLocation() {
                    const list = this.filteredLocations;
                    if (!list.length) {
                        this.locationInvalid = true;
                        this.formData.target_location = '';
                        return;
                    }
                    const idx = Math.min(Math.max(this.highlightedLocationIndex, 0), list.length - 1);
                    this.selectLocation(list[idx]);
                },

                highlightNextLocation() {
                    this.locationDropdownOpen = true;
                    const max = this.filteredLocations.length - 1;
                    if (max < 0) return;
                    this.highlightedLocationIndex = Math.min(this.highlightedLocationIndex + 1, max);
                },

                highlightPrevLocation() {
                    this.locationDropdownOpen = true;
                    this.highlightedLocationIndex = Math.max(this.highlightedLocationIndex - 1, 0);
                },

                submitForm(event) {
                    if (this.volumeInvalid || this.isSubmitting) return;

                    const selected = (this.formData.target_location || '').trim().toLowerCase();
                    const isValidLocation = this.locations.some(loc => String(loc).toLowerCase() === selected);
                    if (!selected || !isValidLocation) {
                        this.locationInvalid = true;
                        this.locationDropdownOpen = true;
                        window.toast?.error('Please select a valid target location from the list.');
                        return;
                    }

                    this.isSubmitting = true;
                    this.successMessage = '';
                    this.errorMessage = '';
                    this.statusIndex = 0;

                    this.statusInterval = setInterval(() => {
                        this.statusIndex = (this.statusIndex + 1) % this.statusMessages.length;
                    }, 1200);

                    const formElement = event.target;
                    const data = new FormData(formElement);

                    data.set('target_location', this.formData.target_location.toLowerCase().trim());
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
                            window.toast?.success(this.successMessage);

                            setTimeout(() => {
                                if (result.redirect) {
                                    window.location.href = result.redirect;
                                }
                            }, 1200);
                        } else {
                            this.isSubmitting = false;
                            if (response.status === 422 && result.errors) {
                                this.errorMessage = Object.values(result.errors).flat().join(' ');
                            } else {
                                this.errorMessage = result.error || 'An error occurred during submission.';
                            }
                            window.toast?.error(this.errorMessage);
                        }
                    })
                    .catch(error => {
                        clearInterval(this.statusInterval);
                        this.isSubmitting = false;
                        this.errorMessage = 'A network error occurred. Please try again later.';
                        window.toast?.error(this.errorMessage);
                    });
                }
            }
        }
    </script>
    @endpush
</x-app-layout>
