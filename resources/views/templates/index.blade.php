@php
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

    <div class="space-y-8 max-w-7xl mx-auto pb-10" x-data="templateManager()">

        <div class="flex justify-end" x-show="selectedIds.length > 0" x-cloak>
            <button type="button" @click="bulkDeleteSelected()"
                    class="px-5 py-2.5 rounded-xl text-sm font-bold bg-rose-50 text-rose-600 hover:bg-rose-500 hover:text-white border border-rose-100 transition-all">
                Delete Selected (<span x-text="selectedIds.length"></span>)
            </button>
        </div>

        <div class="bg-white rounded-3xl shadow-sm border border-slate-100 overflow-hidden">
            <div class="p-0 lf-table-scroll no-scrollbar">
                <table class="min-w-full w-full table-auto text-left border-collapse">
                    <thead>
                        <tr class="bg-slate-50 border-b border-slate-100">
                            <th class="px-4 py-5 w-10">
                                <input type="checkbox" x-model="selectAll" @change="toggleSelectAll()" class="w-4 h-4 text-brand-blue border-slate-300 rounded focus:ring-brand-blue">
                            </th>
                            <th class="px-6 py-5 text-[10px] font-bold text-slate-400 uppercase tracking-widest whitespace-nowrap">Template Name</th>
                            <th class="px-6 py-5 text-[10px] font-bold text-slate-400 uppercase tracking-widest whitespace-nowrap">Subject</th>
                            <th class="px-6 py-5 text-[10px] font-bold text-slate-400 uppercase tracking-widest text-right whitespace-nowrap lf-sticky-right lf-sticky-head bg-slate-50 border-l border-slate-100">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        @forelse($userTemplates as $template)
                        <tr class="hover:bg-slate-50 transition-colors group relative">
                            <td class="px-4 py-4" @click.stop>
                                <input type="checkbox" value="{{ $template->id }}" x-model="selectedIds" class="template-checkbox w-4 h-4 text-brand-blue border-slate-300 rounded focus:ring-brand-blue">
                            </td>
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
                            <td class="px-6 py-4 text-right lf-sticky-right bg-white group-hover:bg-slate-50 border-l border-slate-100">
                                <div class="flex items-center justify-end space-x-1">
                                    <button type="button" @click="openModal({{ json_encode($template) }})"
                                            class="p-2 text-slate-400 hover:text-slate-700 hover:bg-slate-100 rounded-lg transition-colors"
                                            title="Open">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                                    </button>

                                    <form action="{{ route('templates.default', $template->id) }}" method="POST" class="inline">
                                        @csrf
                                        <button type="submit"
                                                class="p-2 rounded-lg transition-colors {{ $template->is_default ? 'text-amber-400 hover:text-amber-500 hover:bg-amber-50' : 'text-slate-400 hover:text-brand-blue hover:bg-blue-50' }}"
                                                title="{{ $template->is_default ? 'Remove Default' : 'Make Default' }}">
                                            @if($template->is_default)
                                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                                    <path d="M12 2l2.39 7.26H22l-6.19 4.5 2.39 7.26L12 16.52l-6.2 4.5 2.39-7.26L2 9.26h7.61L12 2z"/>
                                                </svg>
                                            @else
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"></path>
                                                </svg>
                                            @endif
                                        </button>
                                    </form>
                                    @if(!$template->is_system_sample || Auth::user()->isAdmin())
                                        <form action="{{ route('templates.destroy', $template->id) }}" method="POST" class="inline"
                                              data-swal-title="Delete this template?"
                                              data-swal-confirm="This template will be permanently removed."
                                              data-swal-confirm-text="Yes, delete">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit"
                                                    class="p-2 text-slate-400 hover:text-red-600 hover:bg-red-50 rounded-lg transition-colors"
                                                    title="Delete">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="4" class="px-6 py-16 text-center">
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
                    <div class="p-6 border-b border-slate-200 flex items-center justify-between bg-slate-50">
                        <h3 class="font-bold text-slate-800 text-lg">Edit Template</h3>
                        <button type="button" @click="showViewModal = false" class="text-slate-400 hover:text-slate-600 p-2 rounded-lg hover:bg-slate-200 transition-all">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                        </button>
                    </div>
                    <div class="p-6 overflow-y-auto max-h-[70vh] bg-slate-50 space-y-4">
                        <div>
                            <label class="text-[10px] font-bold text-slate-400 uppercase tracking-widest block mb-1">Template Name</label>
                            <input type="text" name="name" x-model="viewData.name" :readonly="viewData.is_system_sample && !isAdmin" class="w-full bg-white p-3 rounded-lg border border-slate-200 text-sm font-medium text-slate-800 focus:ring-brand-blue focus:border-brand-blue disabled:bg-slate-50 transition-all" required>
                        </div>
                        <div>
                            <label class="text-[10px] font-bold text-slate-400 uppercase tracking-widest block mb-1">Subject</label>
                            <input type="text" name="subject" x-model="viewData.subject" :readonly="viewData.is_system_sample && !isAdmin" class="w-full bg-white p-3 rounded-lg border border-slate-200 text-sm font-medium text-slate-800 focus:ring-brand-blue focus:border-brand-blue disabled:bg-slate-50 transition-all" required>
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

    @push('scripts')
    <script>
        function templateManager() {
            return {
                showViewModal: false,
                viewData: {},
                isAdmin: @json(auth()->user()->isAdmin()),
                selectedIds: [],
                selectAll: false,
                openModal(template) {
                    this.viewData = template;
                    this.showViewModal = true;
                },
                init() {
                    this.$watch('selectedIds', (val) => {
                        const total = document.querySelectorAll('.template-checkbox').length;
                        this.selectAll = total > 0 && val.length === total;
                    });
                },
                toggleSelectAll() {
                    if (this.selectAll) {
                        this.selectedIds = Array.from(document.querySelectorAll('.template-checkbox')).map(cb => cb.value);
                    } else {
                        this.selectedIds = [];
                    }
                },
                async bulkDeleteSelected() {
                    if (this.selectedIds.length === 0) return;
                    const ok = await window.confirmBulkDelete(this.selectedIds.length, 'template');
                    if (!ok) return;
                    const form = document.createElement('form');
                    form.method = 'POST';
                    form.action = @js(route('templates.bulk-delete'));
                    const csrf = document.createElement('input');
                    csrf.type = 'hidden';
                    csrf.name = '_token';
                    csrf.value = document.querySelector('meta[name="csrf-token"]')?.content || '';
                    form.appendChild(csrf);
                    this.selectedIds.forEach((id) => {
                        const input = document.createElement('input');
                        input.type = 'hidden';
                        input.name = 'ids[]';
                        input.value = id;
                        form.appendChild(input);
                    });
                    document.body.appendChild(form);
                    form.submit();
                },
            };
        }
        window.templateManager = templateManager;
    </script>
    @endpush
</x-app-layout>
