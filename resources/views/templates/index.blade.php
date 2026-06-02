<x-app-layout>
    <x-slot name="header">
        Template Manager
    </x-slot>

    <x-slot name="subheader">
        Manage your unified campaign templates and signatures
    </x-slot>

    <div class="space-y-8 max-w-7xl mx-auto pb-10">

        @if($errors->any())
            <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-xl relative shadow-sm">
                <ul class="list-disc list-inside text-sm font-bold">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="grid grid-cols-1 xl:grid-cols-3 gap-8">
            
            {{-- Left Column: Create Form --}}
            <div class="xl:col-span-1">
                <div class="bg-white rounded-3xl shadow-sm border border-slate-100 overflow-hidden sticky top-24">
                    <div class="p-6 border-b border-slate-100 bg-slate-50/50">
                        <h3 class="text-base font-black text-slate-800 flex items-center">
                            <svg class="w-5 h-5 mr-2 text-brand-blue" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                            Create New Template
                        </h3>
                    </div>
                    
                    <form action="{{ route('templates.store') }}" method="POST" class="p-6 space-y-6 bg-white">
                        @csrf
                        
                        {{-- Core Fields --}}
                        <div class="space-y-4">
                            <div>
                                <label class="text-[10px] font-bold text-slate-400 uppercase tracking-widest block mb-2">Template Name *</label>
                                <input type="text" name="name" required class="w-full bg-slate-50 border-slate-200 rounded-xl text-sm py-2.5 px-4 focus:ring-brand-blue focus:border-brand-blue transition-all" placeholder="e.g. Initial Outreach v1">
                            </div>
                            <div>
                                <label class="text-[10px] font-bold text-slate-400 uppercase tracking-widest block mb-2">Email Subject *</label>
                                <input type="text" name="subject" required class="w-full bg-slate-50 border-slate-200 rounded-xl text-sm py-2.5 px-4 focus:ring-brand-blue focus:border-brand-blue transition-all" placeholder="Quick question regarding...">
                            </div>
                            <div>
                                <label class="text-[10px] font-bold text-slate-400 uppercase tracking-widest block mb-2">Email Body *</label>
                                <textarea name="body" rows="6" required class="w-full bg-slate-50 border-slate-200 rounded-xl text-sm py-3 px-4 focus:ring-brand-blue focus:border-brand-blue custom-scrollbar transition-all" placeholder="Hi {first_name}..."></textarea>
                            </div>
                        </div>

                        <hr class="border-slate-100">

                        {{-- Signature Section --}}
                        <div>
                            <h4 class="text-xs font-bold text-slate-800 mb-4 flex items-center">
                                <svg class="w-4 h-4 mr-1.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                                Signature Configuration (Optional)
                            </h4>
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="text-[9px] font-bold text-slate-400 uppercase block mb-1.5">Name</label>
                                    <input type="text" name="signature_name" class="w-full bg-slate-50 border-slate-200 rounded-lg text-sm py-2 px-3 focus:ring-brand-blue focus:border-brand-blue">
                                </div>
                                <div>
                                    <label class="text-[9px] font-bold text-slate-400 uppercase block mb-1.5">Position</label>
                                    <input type="text" name="signature_position" class="w-full bg-slate-50 border-slate-200 rounded-lg text-sm py-2 px-3 focus:ring-brand-blue focus:border-brand-blue">
                                </div>
                                <div>
                                    <label class="text-[9px] font-bold text-slate-400 uppercase block mb-1.5">Company Name</label>
                                    <input type="text" name="signature_company" class="w-full bg-slate-50 border-slate-200 rounded-lg text-sm py-2 px-3 focus:ring-brand-blue focus:border-brand-blue">
                                </div>
                                <div>
                                    <label class="text-[9px] font-bold text-slate-400 uppercase block mb-1.5">Address</label>
                                    <input type="text" name="signature_address" class="w-full bg-slate-50 border-slate-200 rounded-lg text-sm py-2 px-3 focus:ring-brand-blue focus:border-brand-blue">
                                </div>
                            </div>
                        </div>
                        
                        <div class="pt-2">
                            <button type="submit" class="w-full bg-brand-blue text-white px-6 py-3 rounded-xl text-xs font-bold hover:bg-blue-600 transition-all shadow-lg shadow-blue-500/20 active:scale-95">Save Template</button>
                        </div>
                    </form>
                </div>
            </div>

            {{-- Right Column: Saved Templates List --}}
            <div class="xl:col-span-2 space-y-4">
                <div class="flex items-center justify-between mb-2 px-1">
                    <h3 class="text-sm font-black text-slate-800 uppercase tracking-widest">Saved Templates</h3>
                    <span class="text-xs font-bold text-slate-400">{{ $templates->count() }} templates</span>
                </div>

                @forelse($templates as $template)
                    <div class="bg-white border border-slate-200 rounded-3xl p-6 hover:shadow-lg hover:shadow-slate-200/50 transition-all group relative overflow-hidden">
                        {{-- Default Badge Strip --}}
                        @if($template->is_default)
                            <div class="absolute top-0 left-0 w-1.5 h-full bg-emerald-400"></div>
                        @endif

                        <div class="flex flex-col md:flex-row gap-6">
                            {{-- Info Content --}}
                            <div class="flex-1 space-y-4">
                                <div class="flex items-center space-x-3">
                                    <h4 class="text-lg font-black text-slate-900">{{ $template->name }}</h4>
                                    @if($template->is_default)
                                        <span class="px-2.5 py-1 bg-emerald-50 text-emerald-600 rounded-md text-[10px] font-black uppercase tracking-wider border border-emerald-100 flex items-center">
                                            <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                                            Active Default
                                        </span>
                                    @endif
                                </div>
                                
                                <div class="bg-slate-50 rounded-xl p-4 border border-slate-100">
                                    <div class="mb-2">
                                        <span class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Subject:</span>
                                        <span class="text-sm font-bold text-slate-700 ml-1">{{ $template->subject }}</span>
                                    </div>
                                    <div>
                                        <span class="text-[10px] font-bold text-slate-400 uppercase tracking-widest block mb-1">Body Preview:</span>
                                        <p class="text-sm text-slate-600 leading-relaxed">{{ Str::limit($template->body, 120) }}</p>
                                    </div>
                                </div>

                                {{-- Signature Preview --}}
                                @if($template->signature_name || $template->signature_position || $template->signature_company)
                                    <div class="flex items-center gap-4 text-xs font-medium text-slate-500 bg-white border border-slate-100 rounded-lg py-2 px-4 shadow-sm inline-flex">
                                        <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536m-2.036-5.036a2.5 2.5 0 113.536 3.536L6.5 21.036H3v-3.572L16.732 3.732z"></path></svg>
                                        <span>{{ $template->signature_name }}</span>
                                        @if($template->signature_position)<span class="text-slate-300">•</span> <span>{{ $template->signature_position }}</span>@endif
                                        @if($template->signature_company)<span class="text-slate-300">•</span> <span>{{ $template->signature_company }}</span>@endif
                                    </div>
                                @endif
                            </div>
                            
                            {{-- Action Buttons --}}
                            <div class="flex flex-row md:flex-col gap-2 shrink-0 md:w-32 justify-end md:justify-start">
                                @if(!$template->is_default)
                                    <form action="{{ route('templates.default', $template->id) }}" method="POST" class="w-full">
                                        @csrf
                                        <button type="submit" class="w-full px-4 py-2 text-xs font-bold text-brand-blue bg-blue-50 hover:bg-blue-100 hover:text-blue-700 rounded-xl transition-colors border border-blue-100 text-center shadow-sm">
                                            Make Default
                                        </button>
                                    </form>
                                @endif
                                <form action="{{ route('templates.destroy', $template->id) }}" method="POST" onsubmit="return confirm('Delete this template permanently?');" class="w-full">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="w-full px-4 py-2 text-xs font-bold text-red-600 bg-red-50 hover:bg-red-100 hover:text-red-700 rounded-xl transition-colors border border-red-100 text-center shadow-sm">
                                        Delete
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="text-center py-16 bg-white rounded-3xl border border-dashed border-slate-300 shadow-sm">
                        <div class="w-16 h-16 bg-slate-50 rounded-full flex items-center justify-center mx-auto mb-4">
                            <svg class="w-8 h-8 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 002-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path></svg>
                        </div>
                        <h4 class="text-base font-bold text-slate-700 mb-1">No Templates Found</h4>
                        <p class="text-sm text-slate-500 font-medium max-w-sm mx-auto">Create your first unified email template using the form on the left to start automating your outreach campaigns.</p>
                    </div>
                @endforelse
            </div>
        </div>
    </div>
</x-app-layout>
