<x-app-layout>
    <x-slot name="header">
        Edit Template
    </x-slot>

    <x-slot name="subheader">
        Update your unified campaign template
    </x-slot>

    <div class="max-w-4xl mx-auto pb-10">
        <div class="bg-white rounded-3xl shadow-sm border border-slate-100 overflow-hidden">
            <div class="p-6 border-b border-slate-100 bg-slate-50/50 flex items-center justify-between">
                <h3 class="text-base font-black text-slate-800 flex items-center">
                    <svg class="w-5 h-5 mr-2 text-brand-blue" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                    Edit Email Template
                </h3>
                @if($template->is_system_sample)
                    <span class="px-2 py-1 bg-blue-100 text-blue-700 rounded-md text-[10px] font-black uppercase tracking-wider flex items-center">
                        System Sample
                    </span>
                @endif
            </div>
            
            <form action="{{ route('templates.update', $template->id) }}" method="POST" class="p-8 space-y-8 bg-white">
                @csrf
                @method('PUT')
                
                @if($errors->any())
                    <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-xl relative shadow-sm">
                        <ul class="list-disc list-inside text-sm font-bold">
                            @foreach($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <div class="space-y-6">
                    <div>
                        <label class="text-[10px] font-bold text-slate-400 uppercase tracking-widest block mb-2">Template Name *</label>
                        <input type="text" name="name" value="{{ old('name', $template->name) }}" required class="w-full bg-slate-50 border-slate-200 rounded-xl text-sm py-3 px-4 focus:ring-brand-blue focus:border-brand-blue transition-all" placeholder="e.g. Initial Outreach v1">
                    </div>
                    <div>
                        <label class="text-[10px] font-bold text-slate-400 uppercase tracking-widest block mb-2">Email Subject *</label>
                        <input type="text" name="subject" value="{{ old('subject', $template->subject) }}" required class="w-full bg-slate-50 border-slate-200 rounded-xl text-sm py-3 px-4 focus:ring-brand-blue focus:border-brand-blue transition-all" placeholder="Quick question regarding...">
                    </div>
                    <div>
                        <label class="text-[10px] font-bold text-slate-400 uppercase tracking-widest block mb-2">Email Body *</label>
                        <textarea name="body" rows="10" required class="w-full bg-slate-50 border-slate-200 rounded-xl text-sm py-4 px-4 focus:ring-brand-blue focus:border-brand-blue custom-scrollbar transition-all" placeholder="Hi {first_name}...">{{ old('body', $template->body) }}</textarea>
                    </div>
                </div>

                <hr class="border-slate-100">

                <div>
                    <h4 class="text-xs font-bold text-slate-800 mb-4 flex items-center">
                        <svg class="w-4 h-4 mr-1.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                        Signature Configuration (Optional)
                    </h4>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div>
                            <label class="text-[9px] font-bold text-slate-400 uppercase block mb-1.5">Name</label>
                            <input type="text" name="signature_name" value="{{ old('signature_name', $template->signature_name) }}" class="w-full bg-slate-50 border-slate-200 rounded-lg text-sm py-2 px-3 focus:ring-brand-blue focus:border-brand-blue">
                        </div>
                        <div>
                            <label class="text-[9px] font-bold text-slate-400 uppercase block mb-1.5">Position</label>
                            <input type="text" name="signature_position" value="{{ old('signature_position', $template->signature_position) }}" class="w-full bg-slate-50 border-slate-200 rounded-lg text-sm py-2 px-3 focus:ring-brand-blue focus:border-brand-blue">
                        </div>
                        <div>
                            <label class="text-[9px] font-bold text-slate-400 uppercase block mb-1.5">Company Name</label>
                            <input type="text" name="signature_company" value="{{ old('signature_company', $template->signature_company) }}" class="w-full bg-slate-50 border-slate-200 rounded-lg text-sm py-2 px-3 focus:ring-brand-blue focus:border-brand-blue">
                        </div>
                        <div>
                            <label class="text-[9px] font-bold text-slate-400 uppercase block mb-1.5">Address</label>
                            <input type="text" name="signature_address" value="{{ old('signature_address', $template->signature_address) }}" class="w-full bg-slate-50 border-slate-200 rounded-lg text-sm py-2 px-3 focus:ring-brand-blue focus:border-brand-blue">
                        </div>
                    </div>
                </div>
                
                <div class="pt-6 flex flex-col sm:flex-row items-center gap-4 border-t border-slate-100">
                    <button type="submit" class="w-full sm:w-auto bg-brand-blue text-white px-8 py-3.5 rounded-xl text-sm font-bold hover:bg-blue-600 transition-all shadow-lg shadow-blue-500/20 active:scale-95">Update Template</button>
                    <a href="{{ route('templates.index') }}" class="w-full sm:w-auto px-8 py-3.5 rounded-xl text-sm font-bold text-slate-600 bg-slate-100 hover:bg-slate-200 transition-colors text-center">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
