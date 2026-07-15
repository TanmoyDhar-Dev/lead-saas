<x-app-layout>
    <x-slot name="header">
        Imported Leads
    </x-slot>

    <x-slot name="subheader">
        Manually uploaded contact lists · Your imports only
    </x-slot>

    <x-slot name="actions">
        <button type="button" onclick="document.getElementById('import-open-btn')?.click()"
                class="bg-brand-blue text-white px-6 py-2.5 rounded-xl text-sm font-bold hover:bg-blue-600 transition-colors shadow-lg shadow-blue-500/20 flex items-center">
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"></path></svg>
            Import Leads
        </button>
    </x-slot>

    <div class="space-y-6" x-data="importedLeadManager()">
        <button id="import-open-btn" type="button" class="hidden" @click="openImportModal()"></button>

        <div class="bg-white rounded-3xl p-4 shadow-sm border border-slate-100 flex flex-col lg:flex-row lg:items-center justify-between gap-4">
            <div class="relative flex-1 max-w-xl">
                <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none">
                    <svg class="h-4 w-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                </span>
                <input type="text" x-model="filters.q" @input.debounce.300ms="fetchLeads()"
                       placeholder="Search organization, contact, email, phone..."
                       class="block w-full pl-10 pr-10 py-2 bg-slate-50 border-slate-200 rounded-xl text-sm focus:ring-brand-blue focus:border-brand-blue">
                <button x-show="filters.q" @click="filters.q = ''; fetchLeads()" class="absolute inset-y-0 right-0 pr-3 flex items-center text-slate-400 hover:text-slate-600">
                    <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>
            </div>
            {{-- <button type="button" @click="openImportModal()"
                    class="px-6 py-2 rounded-xl text-sm font-bold bg-white border border-slate-200 text-slate-700 hover:bg-slate-50 transition-all flex items-center h-[42px]">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"></path></svg>
                Import
            </button> --}}
            <button type="button"
                    @click="openOutreachModal()"
                    :disabled="selectedLeadIds.length === 0"
                    :class="selectedLeadIds.length === 0 ? 'opacity-50 cursor-not-allowed bg-slate-300 text-slate-500 shadow-none' : 'bg-brand-blue text-white hover:bg-blue-600 shadow-lg shadow-blue-500/20'"
                    class="px-6 py-2 rounded-xl text-sm font-bold transition-all flex items-center h-[42px]">
                Email Outreach (<span x-text="selectedLeadIds.length"></span>)
            </button>
        </div>

        @unless($outlookConnected)
        <div class="rounded-2xl border border-amber-100 bg-amber-50 px-5 py-3 text-sm text-amber-800 font-medium">
            Connect Microsoft Outlook under Integrations before sending or drafting outreach.
        </div>
        @endunless

        <div class="bg-white rounded-3xl shadow-sm border border-slate-100 overflow-hidden relative" :class="loading ? 'opacity-60' : ''">
            <div id="imported-leads-table">
                @include('imported-leads.partials.table', ['importedLeads' => $importedLeads])
            </div>
        </div>

        {{-- Import Modal --}}
        <div x-show="importOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div @click="closeImportModal()" class="fixed inset-0 bg-black/60 backdrop-blur-sm"></div>
            <div class="bg-white rounded-3xl shadow-2xl w-full max-w-lg relative z-10 overflow-hidden"
                 @click.stop
                 x-transition>
                <div class="p-6 border-b border-slate-100 flex justify-between items-center bg-slate-50/50">
                    <div>
                        <h3 class="text-lg font-bold text-slate-800">Import Leads</h3>
                        <p class="text-xs text-slate-400 mt-1">CSV or Excel · max 10 MB · up to 5,000 rows</p>
                    </div>
                    <button @click="closeImportModal()" class="text-slate-400 hover:text-slate-600">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                    </button>
                </div>

                <form @submit.prevent="submitImport()" class="p-6 space-y-4">
                    <div class="relative rounded-2xl border-2 border-dashed p-8 text-center transition-colors"
                         :class="dragOver ? 'border-brand-blue bg-blue-50' : 'border-slate-200 bg-slate-50'"
                         @dragover.prevent="dragOver = true"
                         @dragleave.prevent="dragOver = false"
                         @drop.prevent="onDrop($event)">
                        <input type="file" x-ref="fileInput" accept=".csv,.xlsx,.xls,text/csv,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet,application/vnd.ms-excel"
                               class="absolute inset-0 w-full h-full opacity-0 cursor-pointer"
                               @change="onFileSelected($event)">
                        <svg class="mx-auto h-10 w-10 text-slate-300 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path></svg>
                        <p class="text-sm font-bold text-slate-700">Drag & drop your file here</p>
                        <p class="text-xs text-slate-400 mt-1">or click to select from your device</p>
                        <p class="text-[10px] text-slate-400 mt-3 uppercase tracking-widest font-bold">Expected: Organization, MD/CEO, Email, Cell/Phone, Address</p>
                    </div>

                    <div x-show="selectedFile" class="rounded-xl bg-emerald-50 border border-emerald-100 px-4 py-3 flex items-center justify-between">
                        <div class="min-w-0">
                            <p class="text-sm font-bold text-emerald-800 truncate" x-text="selectedFile?.name"></p>
                            <p class="text-[11px] text-emerald-600" x-text="fileSizeLabel"></p>
                        </div>
                        <button type="button" @click="clearFile()" class="text-emerald-600 hover:text-emerald-800 text-xs font-bold">Remove</button>
                    </div>

                    <p x-show="importError" class="text-sm text-red-600 font-medium" x-text="importError"></p>
                    <p x-show="importSuccess" class="text-sm text-emerald-600 font-medium" x-text="importSuccess"></p>

                    <div class="flex justify-end gap-3 pt-2">
                        <button type="button" @click="closeImportModal()" class="px-5 py-2.5 rounded-xl text-sm font-bold text-slate-500 hover:bg-slate-100">Cancel</button>
                        <button type="submit" :disabled="!selectedFile || importing"
                                :class="!selectedFile || importing ? 'opacity-50 cursor-not-allowed' : 'hover:bg-blue-600'"
                                class="px-6 py-2.5 rounded-xl text-sm font-bold text-white bg-brand-blue shadow-lg shadow-blue-500/20">
                            <span x-show="!importing">Upload & Import</span>
                            <span x-show="importing">Importing…</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>

        {{-- Outreach Modal --}}
        <div x-show="outreachOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/50 backdrop-blur-sm p-4" @click.self="outreachOpen = false">
            <div class="w-full max-w-5xl h-[90vh] bg-white rounded-xl flex overflow-hidden shadow-2xl relative" @click.stop>
                <div class="w-1/3 bg-gray-50 flex flex-col border-r border-slate-200">
                    <div class="p-6 border-b border-slate-200 bg-white shrink-0">
                        <h3 class="font-bold text-slate-800">Selected Leads</h3>
                        <div class="mt-4 text-2xl font-black text-brand-blue" x-text="selectedLeadIds.length"></div>
                        <p class="text-xs text-slate-400 mt-1">Templates only · Microsoft Graph · no n8n</p>
                    </div>
                    <div class="flex-1 overflow-y-auto p-4">
                        <template x-for="id in selectedLeadIds" :key="id">
                            <div class="p-3 bg-white border border-slate-200 rounded-xl mb-2">
                                <div class="text-sm font-bold text-slate-800" x-text="leadLabel(id).org"></div>
                                <div class="text-[11px] text-slate-500 mt-0.5" x-text="leadLabel(id).contact"></div>
                            </div>
                        </template>
                    </div>
                </div>

                <div class="w-2/3 flex flex-col bg-white">
                    <form action="{{ route('imported-leads.outreach') }}" method="POST" enctype="multipart/form-data" class="flex-1 flex flex-col overflow-hidden">
                        @csrf
                        <template x-for="id in selectedLeadIds" :key="'oid'+id">
                            <input type="hidden" name="imported_lead_ids[]" :value="id">
                        </template>

                        <div class="flex-1 overflow-y-auto p-6 md:p-8 space-y-6">
                            <div>
                                <label class="text-[10px] font-bold text-slate-400 uppercase tracking-widest block mb-3">Delivery Mode *</label>
                                <div class="grid grid-cols-2 gap-3">
                                    <label class="cursor-pointer">
                                        <input type="radio" name="delivery_mode" value="Save as Draft" class="peer sr-only" required checked>
                                        <div class="p-3 bg-white border border-slate-200 rounded-xl peer-checked:border-brand-blue peer-checked:bg-blue-50 transition-all text-center">
                                            <span class="text-xs font-bold text-slate-700 peer-checked:text-brand-blue">Save as Draft</span>
                                        </div>
                                    </label>
                                    <label class="cursor-pointer">
                                        <input type="radio" name="delivery_mode" value="Send Immediately" class="peer sr-only">
                                        <div class="p-3 bg-white border border-slate-200 rounded-xl peer-checked:border-brand-blue peer-checked:bg-blue-50 transition-all text-center">
                                            <span class="text-xs font-bold text-slate-700 peer-checked:text-brand-blue">Send Immediately</span>
                                        </div>
                                    </label>
                                </div>
                            </div>

                            <div>
                                <label class="text-xs font-bold text-slate-400">Template</label>
                                <select x-model="selectedTemplate"
                                        @change="applyTemplate()"
                                        class="w-full bg-slate-50 border-slate-200 rounded-xl py-3 mt-1 text-sm focus:ring-brand-blue focus:border-brand-blue">
                                    <option value="">Custom</option>
                                    <template x-for="t in templatesData" :key="t.id">
                                        <option :value="t.id" x-text="t.name"></option>
                                    </template>
                                </select>
                            </div>

                            <div>
                                <label class="text-[10px] font-bold text-slate-400 uppercase tracking-widest block mb-1">Email Subject *</label>
                                <input type="text" name="subject" x-model="outreachForm.subject" required class="w-full bg-slate-50 border-slate-200 rounded-xl text-sm focus:ring-brand-blue focus:border-brand-blue py-3 px-4">
                            </div>

                            <div>
                                <label class="text-xs font-bold text-slate-400">Body *</label>
                                <textarea name="body" x-model="outreachForm.body" rows="6" required class="w-full bg-slate-50 border-slate-200 rounded-xl mt-1 p-4 text-sm focus:ring-brand-blue focus:border-brand-blue"></textarea>
                                <p class="text-[10px] text-slate-400 mt-1">Placeholders: @{{fullName}}, @{{companyName}}, @{{email}}, @{{address}}</p>
                            </div>

                            <div>
                                <label class="text-xs font-bold text-slate-400 mb-2 block">Signature</label>
                                <div class="grid grid-cols-2 gap-4">
                                    <input name="sender_name" x-model="outreachForm.sender_name" placeholder="Sender Name" class="bg-slate-50 border-slate-200 rounded-xl p-3 text-sm focus:ring-brand-blue focus:border-brand-blue">
                                    <input name="sender_role" x-model="outreachForm.sender_role" placeholder="Sender Role" class="bg-slate-50 border-slate-200 rounded-xl p-3 text-sm focus:ring-brand-blue focus:border-brand-blue">
                                    <input name="sender_company" x-model="outreachForm.sender_company" placeholder="Sender Company" class="bg-slate-50 border-slate-200 rounded-xl p-3 text-sm focus:ring-brand-blue focus:border-brand-blue">
                                    <input name="sender_address" x-model="outreachForm.sender_address" placeholder="Sender Address" class="bg-slate-50 border-slate-200 rounded-xl p-3 text-sm focus:ring-brand-blue focus:border-brand-blue">
                                </div>
                            </div>

                            <div>
                                <label class="text-xs font-bold text-slate-400 mb-2 block">Attachments (Optional)</label>
                                <div class="w-full flex items-center justify-center p-6 border-2 border-dashed border-slate-300 rounded-xl bg-slate-50 hover:bg-slate-100 hover:border-brand-blue transition-colors cursor-pointer relative">
                                    <input type="file" name="attachments[]" multiple accept=".pdf,.doc,.docx,image/*" @change="outreachFiles = $event.target.files" class="absolute inset-0 w-full h-full opacity-0 cursor-pointer">
                                    <div class="text-center pointer-events-none">
                                        <svg class="mx-auto h-8 w-8 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"></path></svg>
                                        <span class="mt-2 block text-sm font-semibold text-slate-700">Drop files here or click to upload</span>
                                        <span class="mt-1 block text-[10px] text-slate-400">PDF, DOC, DOCX, JPG, PNG · max 5 MB each</span>
                                    </div>
                                </div>
                                <div x-show="outreachFiles.length > 0" class="mt-2 text-xs font-bold text-brand-blue">
                                    <span x-text="outreachFiles.length + ' file(s) selected'"></span>
                                </div>
                            </div>
                        </div>

                        <div class="p-4 md:p-6 border-t border-slate-200 bg-white flex justify-end gap-3 shrink-0">
                            <button type="button" @click="outreachOpen = false" class="px-6 py-2.5 text-sm font-bold text-slate-600 bg-slate-100 rounded-xl hover:bg-slate-200">Cancel</button>
                            <button type="submit" class="px-6 py-2.5 text-sm font-bold text-white bg-brand-blue rounded-xl hover:bg-blue-600 shadow-lg shadow-blue-500/30">Send Outreach</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        {{-- Detail Modal --}}
        <div x-show="detailOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div @click="detailOpen = false" class="fixed inset-0 bg-black/60 backdrop-blur-sm"></div>
            <div class="bg-white rounded-3xl shadow-2xl w-full max-w-xl relative z-10 overflow-hidden max-h-[85vh] flex flex-col" @click.stop>
                <div class="p-6 border-b border-slate-100 flex justify-between items-center bg-slate-50/50 shrink-0">
                    <h3 class="text-lg font-bold text-slate-800">Lead Details</h3>
                    <button @click="detailOpen = false" class="text-slate-400 hover:text-slate-600">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                    </button>
                </div>
                <div class="p-6 overflow-y-auto space-y-4" x-show="!detailLoading && detailData">
                    <div>
                        <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Organization</p>
                        <p class="text-sm font-bold text-slate-800 mt-1" x-text="detailData?.organization_name || '—'"></p>
                    </div>
                    <div>
                        <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Contact (MD/CEO)</p>
                        <p class="text-sm font-medium text-slate-700 mt-1" x-text="detailData?.contact_name || '—'"></p>
                    </div>
                    <div>
                        <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Address</p>
                        <p class="text-sm text-slate-600 mt-1" x-text="detailData?.address || '—'"></p>
                    </div>
                    <div>
                        <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-2">Emails</p>
                        <template x-for="email in (detailData?.emails || [])" :key="email.id">
                            <div class="flex items-center gap-2 mb-1">
                                <span class="text-sm text-slate-700" x-text="email.email"></span>
                                <span x-show="email.is_primary" class="text-[9px] font-bold uppercase bg-blue-50 text-brand-blue px-1.5 py-0.5 rounded">Primary</span>
                            </div>
                        </template>
                    </div>
                    <div>
                        <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-2">Phones</p>
                        <template x-if="!(detailData?.phones || []).length">
                            <p class="text-sm text-slate-400">—</p>
                        </template>
                        <template x-for="phone in (detailData?.phones || [])" :key="phone.id">
                            <div class="flex items-center gap-2 mb-1">
                                <span class="text-sm text-slate-700" x-text="phone.phone"></span>
                                <span x-show="phone.is_primary" class="text-[9px] font-bold uppercase bg-blue-50 text-brand-blue px-1.5 py-0.5 rounded">Primary</span>
                            </div>
                        </template>
                    </div>
                    <div class="pt-2 border-t border-slate-100 text-[11px] text-slate-400">
                        Imported <span x-text="detailData?.created_at"></span>
                        <span x-show="detailData?.original_filename"> · from <span x-text="detailData?.original_filename"></span></span>
                    </div>
                </div>
                <div class="p-10 text-center" x-show="detailLoading">
                    <div class="animate-spin w-8 h-8 border-4 border-brand-blue border-t-transparent rounded-full mx-auto"></div>
                </div>
            </div>
        </div>

        {{-- Edit Modal --}}
        <div x-show="editOpen" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div @click="editOpen = false" class="fixed inset-0 bg-black/60 backdrop-blur-sm"></div>
            <div class="bg-white rounded-3xl shadow-2xl w-full max-w-xl relative z-10 overflow-hidden max-h-[90vh] flex flex-col" @click.stop>
                <div class="p-6 border-b border-slate-100 flex justify-between items-center bg-slate-50/50 shrink-0">
                    <h3 class="text-lg font-bold text-slate-800">Edit Imported Lead</h3>
                    <button @click="editOpen = false" class="text-slate-400 hover:text-slate-600">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                    </button>
                </div>
                <form @submit.prevent="submitEdit()" class="flex-1 flex flex-col overflow-hidden">
                    <div class="p-6 space-y-4 overflow-y-auto flex-1">
                        <div>
                            <label class="text-[10px] font-bold text-slate-400 uppercase tracking-widest block mb-1">Organization Name</label>
                            <input type="text" x-model="editForm.organization_name" class="w-full bg-slate-50 border-slate-200 rounded-xl text-sm py-2.5 focus:ring-brand-blue focus:border-brand-blue">
                        </div>
                        <div>
                            <label class="text-[10px] font-bold text-slate-400 uppercase tracking-widest block mb-1">Contact (MD/CEO)</label>
                            <input type="text" x-model="editForm.contact_name" class="w-full bg-slate-50 border-slate-200 rounded-xl text-sm py-2.5 focus:ring-brand-blue focus:border-brand-blue">
                        </div>
                        <div>
                            <label class="text-[10px] font-bold text-slate-400 uppercase tracking-widest block mb-1">Address</label>
                            <textarea x-model="editForm.address" rows="2" class="w-full bg-slate-50 border-slate-200 rounded-xl text-sm py-2.5 focus:ring-brand-blue focus:border-brand-blue"></textarea>
                        </div>
                        <div>
                            <div class="flex items-center justify-between mb-2">
                                <label class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Emails *</label>
                                <button type="button" @click="editForm.emails.push('')" class="text-[11px] font-bold text-brand-blue">+ Add email</button>
                            </div>
                            <template x-for="(email, index) in editForm.emails" :key="'e'+index">
                                <div class="flex gap-2 mb-2">
                                    <input type="email" x-model="editForm.emails[index]" required class="flex-1 bg-slate-50 border-slate-200 rounded-xl text-sm py-2 focus:ring-brand-blue focus:border-brand-blue">
                                    <button type="button" @click="editForm.emails.splice(index, 1)" x-show="editForm.emails.length > 1" class="px-3 text-red-500 text-xs font-bold">Remove</button>
                                </div>
                            </template>
                        </div>
                        <div>
                            <div class="flex items-center justify-between mb-2">
                                <label class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Phones</label>
                                <button type="button" @click="editForm.phones.push('')" class="text-[11px] font-bold text-brand-blue">+ Add phone</button>
                            </div>
                            <template x-for="(phone, index) in editForm.phones" :key="'p'+index">
                                <div class="flex gap-2 mb-2">
                                    <input type="text" x-model="editForm.phones[index]" class="flex-1 bg-slate-50 border-slate-200 rounded-xl text-sm py-2 focus:ring-brand-blue focus:border-brand-blue">
                                    <button type="button" @click="editForm.phones.splice(index, 1)" class="px-3 text-red-500 text-xs font-bold">Remove</button>
                                </div>
                            </template>
                            <p x-show="editForm.phones.length === 0" class="text-xs text-slate-400">No phones. Click + Add phone.</p>
                        </div>
                        <p x-show="editError" class="text-sm text-red-600 font-medium" x-text="editError"></p>
                    </div>
                    <div class="p-4 border-t border-slate-100 flex justify-end gap-3 shrink-0">
                        <button type="button" @click="editOpen = false" class="px-5 py-2.5 rounded-xl text-sm font-bold text-slate-500 hover:bg-slate-100">Cancel</button>
                        <button type="submit" :disabled="editSaving" class="px-6 py-2.5 rounded-xl text-sm font-bold text-white bg-brand-blue hover:bg-blue-600">
                            <span x-text="editSaving ? 'Saving…' : 'Save Changes'"></span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    @push('scripts')
    <script>
        function importedLeadManager() {
            return {
                loading: false,
                filters: { q: @js(request('q', '')) },
                importOpen: false,
                dragOver: false,
                selectedFile: null,
                importing: false,
                importError: null,
                importSuccess: null,
                detailOpen: false,
                detailLoading: false,
                detailData: null,
                editOpen: false,
                editSaving: false,
                editError: null,
                editId: null,
                editForm: {
                    organization_name: '',
                    contact_name: '',
                    address: '',
                    emails: [''],
                    phones: [],
                },
                selectedLeadIds: [],
                selectAll: false,
                outreachOpen: false,
                outlookConnected: @json($outlookConnected),
                templatesData: @json($templates ?? []),
                selectedTemplate: '',
                outreachFiles: [],
                outreachForm: {
                    subject: '',
                    body: '',
                    sender_name: '',
                    sender_role: '',
                    sender_company: '',
                    sender_address: '',
                },

                init() {
                    const defaultTemplate = this.templatesData.find(t => t.is_default);
                    if (defaultTemplate) {
                        this.selectedTemplate = defaultTemplate.id;
                        this.applyTemplate();
                    }

                    this.$watch('selectedLeadIds', (val) => {
                        const totalCheckboxes = document.querySelectorAll('.imported-lead-checkbox').length;
                        this.selectAll = totalCheckboxes > 0 && val.length === totalCheckboxes;
                    });
                },

                toggleSelectAll() {
                    if (this.selectAll) {
                        const checkboxes = document.querySelectorAll('.imported-lead-checkbox');
                        this.selectedLeadIds = Array.from(checkboxes).map(cb => cb.value);
                    } else {
                        this.selectedLeadIds = [];
                    }
                },

                leadLabel(id) {
                    const cb = document.querySelector('.imported-lead-checkbox[value="' + id + '"]');
                    return {
                        org: cb?.dataset?.org || 'Lead',
                        contact: cb?.dataset?.contact || '',
                    };
                },

                openOutreachModal() {
                    if (this.selectedLeadIds.length === 0) return;
                    if (!this.outlookConnected) {
                        alert('Connect Microsoft Outlook first under Integrations.');
                        return;
                    }
                    this.outreachFiles = [];
                    this.outreachOpen = true;
                },

                applyTemplate() {
                    const t = this.templatesData.find(temp => String(temp.id) === String(this.selectedTemplate));
                    if (t) {
                        this.outreachForm.subject = t.subject || '';
                        this.outreachForm.body = t.body || '';
                        this.outreachForm.sender_name = t.signature_name || '';
                        this.outreachForm.sender_role = t.signature_position || '';
                        this.outreachForm.sender_company = t.signature_company || '';
                        this.outreachForm.sender_address = t.signature_address || '';
                    } else {
                        this.outreachForm.subject = '';
                        this.outreachForm.body = '';
                        this.outreachForm.sender_name = '';
                        this.outreachForm.sender_role = '';
                        this.outreachForm.sender_company = '';
                        this.outreachForm.sender_address = '';
                    }
                },

                get fileSizeLabel() {
                    if (!this.selectedFile) return '';
                    const bytes = this.selectedFile.size;
                    if (bytes < 1024) {
                        return bytes + ' B';
                    }
                    if (bytes < 1024 * 1024) {
                        return (bytes / 1024).toFixed(1) + ' KB';
                    }
                    return (bytes / (1024 * 1024)).toFixed(2) + ' MB';
                },

                openImportModal() {
                    this.importOpen = true;
                    this.importError = null;
                    this.importSuccess = null;
                },
                closeImportModal() {
                    this.importOpen = false;
                    this.clearFile();
                    this.importError = null;
                },
                clearFile() {
                    this.selectedFile = null;
                    if (this.$refs.fileInput) this.$refs.fileInput.value = '';
                },
                onFileSelected(e) {
                    const file = e.target.files?.[0];
                    this.setFile(file);
                },
                onDrop(e) {
                    this.dragOver = false;
                    const file = e.dataTransfer.files?.[0];
                    this.setFile(file);
                },
                setFile(file) {
                    this.importError = null;
                    this.importSuccess = null;
                    if (!file) return;
                    const name = file.name.toLowerCase();
                    if (!(/\.(csv|xlsx|xls)$/.test(name))) {
                        this.importError = 'Only CSV, XLSX, and XLS files are allowed.';
                        this.clearFile();
                        return;
                    }
                    if (file.size > 10 * 1024 * 1024) {
                        this.importError = 'File size must be 10 MB or less.';
                        this.clearFile();
                        return;
                    }
                    this.selectedFile = file;
                },
                async submitImport() {
                    if (!this.selectedFile || this.importing) return;
                    this.importing = true;
                    this.importError = null;
                    this.importSuccess = null;

                    const formData = new FormData();
                    formData.append('file', this.selectedFile);

                    try {
                        const res = await fetch(@js(route('imported-leads.import')), {
                            method: 'POST',
                            headers: {
                                'X-Requested-With': 'XMLHttpRequest',
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                            },
                            body: formData,
                            credentials: 'same-origin',
                        });
                        const data = await res.json();
                        if (!res.ok || !data.success) {
                            throw new Error(data.message || Object.values(data.errors || {}).flat()[0] || 'Import failed.');
                        }
                        this.importSuccess = data.message;
                        this.clearFile();
                        await this.fetchLeads();
                        setTimeout(() => this.closeImportModal(), 1200);
                    } catch (err) {
                        this.importError = err.message || 'Import failed.';
                    } finally {
                        this.importing = false;
                    }
                },

                async fetchLeads() {
                    this.loading = true;
                    try {
                        const params = new URLSearchParams(this.filters);
                        const res = await fetch(@js(route('imported-leads.index')) + '?' + params.toString(), {
                            headers: { 'X-Requested-With': 'XMLHttpRequest' },
                            credentials: 'same-origin',
                        });
                        const html = await res.text();
                        const el = document.getElementById('imported-leads-table');
                        el.innerHTML = html;
                        this.selectedLeadIds = [];
                        this.selectAll = false;
                        if (window.Alpine?.initTree) window.Alpine.initTree(el);
                        window.history.replaceState({}, '', @js(route('imported-leads.index')) + (params.toString() ? '?' + params.toString() : ''));
                    } finally {
                        this.loading = false;
                    }
                },

                async openDetail(id) {
                    this.detailOpen = true;
                    this.detailLoading = true;
                    this.detailData = null;
                    try {
                        const res = await fetch(@js(url('/imported-leads')) + '/' + id, {
                            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                            credentials: 'same-origin',
                        });
                        if (!res.ok) throw new Error('Failed to load');
                        this.detailData = await res.json();
                    } catch {
                        this.detailOpen = false;
                        alert('Unable to load lead details.');
                    } finally {
                        this.detailLoading = false;
                    }
                },

                async openEdit(id) {
                    this.editOpen = true;
                    this.editError = null;
                    this.editId = id;
                    this.editSaving = false;
                    try {
                        const res = await fetch(@js(url('/imported-leads')) + '/' + id, {
                            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                            credentials: 'same-origin',
                        });
                        if (!res.ok) throw new Error('Failed to load');
                        const data = await res.json();
                        this.editForm = {
                            organization_name: data.organization_name || '',
                            contact_name: data.contact_name || '',
                            address: data.address || '',
                            emails: (data.emails || []).map(e => e.email),
                            phones: (data.phones || []).map(p => p.phone),
                        };
                        if (!this.editForm.emails.length) this.editForm.emails = [''];
                    } catch {
                        this.editOpen = false;
                        alert('Unable to load lead for editing.');
                    }
                },

                async submitEdit() {
                    if (this.editSaving || !this.editId) return;
                    this.editSaving = true;
                    this.editError = null;

                    const payload = {
                        organization_name: this.editForm.organization_name,
                        contact_name: this.editForm.contact_name,
                        address: this.editForm.address,
                        emails: this.editForm.emails.filter(e => e && e.trim()),
                        phones: this.editForm.phones.filter(p => p && p.trim()),
                    };

                    try {
                        const res = await fetch(@js(url('/imported-leads')) + '/' + this.editId, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-Requested-With': 'XMLHttpRequest',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                            },
                            body: JSON.stringify({ ...payload, _method: 'PUT' }),
                            credentials: 'same-origin',
                        });
                        const data = await res.json().catch(() => ({}));
                        if (!res.ok) {
                            const msg = data.message || Object.values(data.errors || {}).flat()[0] || 'Update failed.';
                            throw new Error(msg);
                        }
                        this.editOpen = false;
                        await this.fetchLeads();
                    } catch (err) {
                        this.editError = err.message || 'Update failed.';
                    } finally {
                        this.editSaving = false;
                    }
                },
            };
        }
        window.importedLeadManager = importedLeadManager;

        document.addEventListener('click', function (e) {
            if (e.target.closest('#imported-leads-table .pagination a') && window.Alpine) {
                e.preventDefault();
                const link = e.target.closest('a');
                const url = new URL(link.href);
                const root = document.querySelector('[x-data]');
                // Handled via fetchLeads with page from URL in next iteration - direct navigation for pagination is fine
                window.location.href = link.href;
            }
        });
    </script>
    @endpush
</x-app-layout>
