@php
    $systemSample = $templates->where('is_system_sample', true)->first();
    $userTemplates = $templates->where('is_system_sample', false);
@endphp

<x-app-layout>
    <x-slot name="header">
        Template Manager
    </x-slot>

    <x-slot name="subheader">
        Manage your unified campaign templates and signatures
    </x-slot>

    <x-slot name="actions">
        <a href="{{ route('templates.create') }}" class="bg-brand-blue text-white px-6 py-2.5 rounded-xl text-sm font-bold hover:bg-blue-600 transition-colors shadow-lg shadow-blue-500/20 flex items-center">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
            Create New Template
        </a>
    </x-slot>

    <div class="space-y-8 max-w-7xl mx-auto pb-10" x-data="{ showViewModal: false, viewData: {}, isAdmin: {{ auth()->user()->isAdmin() ? 'true' : 'false' }}, openModal(template) { this.viewData = template; this.showViewModal = true; } }">

        {{-- @if(session('success'))
            <div class="bg-emerald-50 border border-emerald-200 p-4 rounded-2xl flex items-start">
                <svg class="w-5 h-5 text-emerald-500 mr-3 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                <div class="flex-1">
                    <h3 class="text-emerald-800 text-sm font-bold">Success</h3>
                    <p class="text-emerald-600 text-xs mt-1">{{ session('success') }}</p>
                </div>
            </div>
        @endif --}}

        @if($systemSample)
            <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 flex justify-between items-center mb-6">
                <span class="text-blue-800 text-sm font-medium">💡 <strong>Best Practice Guide:</strong> Not sure where to start? Check out our high-converting outreach sample.</span>
                <button @click="openModal({{ json_encode($systemSample) }})" class="bg-white text-brand-blue border border-blue-200 px-4 py-2 rounded-lg text-xs font-bold hover:bg-blue-50 transition-colors shadow-sm">View Sample</button>
            </div>
        @endif

        <div class="bg-white rounded-3xl shadow-sm border border-slate-100 overflow-hidden">
            <div class="p-0 overflow-x-auto">
                <table class="min-w-full w-full table-auto text-left border-collapse">
                    <thead>
                        <tr class="bg-slate-50/50 border-b border-slate-100">
                            <th class="px-6 py-5 text-[10px] font-bold text-slate-400 uppercase tracking-widest whitespace-nowrap">Template Name</th>
                            <th class="px-6 py-5 text-[10px] font-bold text-slate-400 uppercase tracking-widest whitespace-nowrap">Subject</th>
                            <th class="px-6 py-5 text-[10px] font-bold text-slate-400 uppercase tracking-widest text-right whitespace-nowrap sticky right-0 z-10 bg-slate-50/90 backdrop-blur border-l border-slate-100">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($userTemplates as $template)
                        <tr class="hover:bg-slate-50 transition-colors group relative">
                            <td class="px-6 py-4">
                                <div class="flex items-center space-x-3">
                                    <h4 class="text-sm font-bold text-slate-900">{{ $template->name }}</h4>
                                    @if($template->is_default)
                                        <span class="px-2 py-1 bg-emerald-50 text-emerald-600 rounded-md text-[10px] font-black uppercase tracking-wider border border-emerald-100 flex items-center">
                                            <svg class="w-3 h-3 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                                            Active Default
                                        </span>
                                    @endif
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="text-sm font-medium text-slate-600">{{ $template->subject }}</div>
                            </td>
                            <td class="px-6 py-4 text-right sticky right-0 z-10 bg-white group-hover:bg-slate-50 border-l border-slate-100">
                                <div class="flex items-center justify-end space-x-2">
                                    <button @click="openModal({{ json_encode($template) }})" class="text-xs font-bold text-slate-500 hover:text-slate-800 px-3 py-1.5 rounded hover:bg-slate-100 transition-colors">Open</button>

                                    @if(!$template->is_default)
                                        <form action="{{ route('templates.default', $template->id) }}" method="POST" class="inline">
                                            @csrf
                                            <button type="submit" class="text-xs font-bold text-brand-blue hover:text-blue-700 px-3 py-1.5 rounded hover:bg-blue-50 transition-colors">
                                                Make Default
                                            </button>
                                        </form>
                                    @endif
                                    
                                    @if(!$template->is_system_sample || Auth::user()->isAdmin())
                                        <form action="{{ route('templates.destroy', $template->id) }}" method="POST" onsubmit="return confirm('Delete this template permanently?');" class="inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-xs font-bold text-red-500 hover:text-red-700 px-3 py-1.5 rounded hover:bg-red-50 transition-colors">
                                                Delete
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="3" class="px-6 py-16 text-center">
                                <div class="w-16 h-16 bg-slate-50 rounded-full flex items-center justify-center mx-auto mb-4">
                                    <svg class="w-8 h-8 text-slate-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 002-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path></svg>
                                </div>
                                <h4 class="text-base font-bold text-slate-700 mb-1">No Templates Found</h4>
                                <p class="text-sm text-slate-500 font-medium max-w-sm mx-auto">Click "Create New Template" to start automating your outreach campaigns.</p>
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Unified Modal Form --}}
        <div x-show="showViewModal" style="display: none;" class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div class="w-full max-w-2xl bg-white rounded-xl shadow-2xl border border-slate-200 overflow-hidden relative" @click.away="showViewModal = false">
                <form :action="'/templates/' + viewData.id" method="POST">
                    @csrf
                    @method('PUT')
                    <input type="hidden" name="name" :value="viewData.name">
                    <div class="p-6 border-b border-slate-200 flex items-center justify-between bg-slate-50">
                        <h3 class="font-bold text-slate-800 text-lg" x-text="viewData.name"></h3>
                        <button type="button" @click="showViewModal = false" class="text-slate-400 hover:text-slate-600 p-2 rounded-lg hover:bg-slate-200 transition-all">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                        </button>
                    </div>
                    <div class="p-6 overflow-y-auto max-h-[70vh] bg-slate-50 space-y-4">
                        <div>
                            <label class="text-[10px] font-bold text-slate-400 uppercase tracking-widest block mb-1">Subject</label>
                            <input type="text" name="subject" x-model="viewData.subject" :readonly="viewData.is_system_sample && !isAdmin" class="w-full bg-white p-3 rounded-lg border border-slate-200 text-sm font-medium text-slate-800 focus:ring-brand-blue focus:border-brand-blue disabled:bg-slate-50 transition-all">
                        </div>
                        <div>
                            <label class="text-[10px] font-bold text-slate-400 uppercase tracking-widest block mb-1">Body Preview</label>
                            <textarea name="body" rows="8" x-model="viewData.body" :readonly="viewData.is_system_sample && !isAdmin" class="w-full bg-white p-4 rounded-lg border border-slate-200 text-sm font-medium text-slate-800 whitespace-pre-wrap font-sans focus:ring-brand-blue focus:border-brand-blue disabled:bg-slate-50 transition-all custom-scrollbar"></textarea>
                        </div>
                    </div>
                    <div class="p-4 border-t border-slate-200 bg-white flex justify-end gap-3">
                        <button type="button" @click="showViewModal = false" class="px-6 py-2.5 text-sm font-bold text-slate-600 bg-slate-100 rounded-xl hover:bg-slate-200 transition-all">Close</button>
                        <button type="submit" x-show="!(viewData.is_system_sample && !isAdmin)" class="px-6 py-2.5 text-sm font-bold text-white bg-brand-blue rounded-xl hover:bg-blue-600 shadow-lg shadow-blue-500/20 active:scale-95 transition-all">Save Changes</button>
                    </div>
                </form>
            </div>
        </div>

    </div>
</x-app-layout>
