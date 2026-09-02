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

        <div class="bg-white rounded-3xl p-6 shadow-sm border border-slate-100">
            <div class="flex flex-col lg:flex-row items-end gap-4">
                <div class="flex-1 w-full">
                    <label class="block text-[8px] font-black text-slate-400 uppercase tracking-widest mb-2">Search</label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none">
                            <svg class="h-4 w-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                        </span>
                        <input type="text" x-model="filters.q" @input.debounce.300ms="fetchLeads(1)"
                               placeholder="Search organization, contact, email, phone..."
                               class="w-full bg-slate-50 border border-slate-200 rounded-xl text-sm font-medium text-slate-800 focus:ring-2 focus:ring-brand-blue outline-none py-3 pl-10 pr-10 transition-all">
                        <button type="button" x-show="filters.q" @click="filters.q = ''; fetchLeads(1)" class="absolute inset-y-0 right-0 pr-3 flex items-center text-slate-400 hover:text-slate-600">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                        </button>
                    </div>
                </div>

                <div class="w-full lg:w-72 relative" @click.outside="closeCategoryDropdown()">
                    <label class="block text-[8px] font-black text-slate-400 uppercase tracking-widest mb-2">Category</label>
                    <div class="relative">
                        <input type="text"
                               x-ref="categorySearchInput"
                               x-model="categorySearch"
                               @focus="openCategoryDropdown()"
                               @input="categoryDropdownOpen = true"
                               @keydown.escape.prevent="closeCategoryDropdown()"
                               @keydown.enter.prevent="selectFirstFilteredCategory()"
                               placeholder="All Categories"
                               class="w-full bg-slate-50 border border-slate-200 rounded-xl text-sm font-medium text-slate-800 focus:ring-2 focus:ring-brand-blue outline-none py-3 pl-4 pr-10 transition-all"
                               autocomplete="off">
                        <button type="button"
                                tabindex="-1"
                                @click="toggleCategoryDropdown()"
                                class="absolute inset-y-0 right-0 pr-3 flex items-center text-slate-400 hover:text-slate-600">
                            <svg class="w-4 h-4 transition-transform" :class="categoryDropdownOpen ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                        </button>
                    </div>

                    <div x-show="categoryDropdownOpen"
                         x-cloak
                         class="absolute left-0 right-0 top-full mt-2 z-40 bg-white border border-slate-200 rounded-2xl shadow-xl overflow-hidden">
                        <div class="max-h-64 overflow-y-auto p-1.5">
                            <button type="button"
                                    @mousedown.prevent="selectCategory('')"
                                    :class="!filters.category ? 'bg-blue-50 text-brand-blue' : 'text-slate-700 hover:bg-slate-50'"
                                    class="w-full text-left px-3 py-2.5 rounded-xl text-xs font-bold transition-colors">
                                All Categories
                            </button>
                            <template x-for="cat in filteredCategories" :key="'dd-'+cat.id">
                                <button type="button"
                                        @mousedown.prevent="selectCategory(cat.id)"
                                        :class="filters.category === cat.id ? 'bg-blue-50 text-brand-blue' : 'text-slate-700 hover:bg-slate-50'"
                                        class="w-full text-left px-3 py-2.5 rounded-xl text-xs font-bold transition-colors truncate"
                                        x-text="cat.name"></button>
                            </template>
                            <p x-show="filteredCategories.length === 0" class="px-3 py-4 text-center text-[11px] text-slate-400 font-medium">No categories found</p>
                        </div>
                    </div>
                </div>

                <div class="w-full lg:w-auto flex items-end gap-2">
                    <button type="button"
                            x-show="selectedLeadIds.length > 0"
                            x-cloak
                            @click="bulkDeleteSelected()"
                            class="px-5 py-3 rounded-xl text-sm font-bold bg-rose-50 text-rose-600 hover:bg-rose-500 hover:text-white border border-rose-100 transition-all whitespace-nowrap">
                        Delete (<span x-text="selectedLeadIds.length"></span>)
                    </button>
                    <button type="button"
                            @click="openOutreachModal()"
                            :disabled="selectedLeadIds.length === 0"
                            :class="selectedLeadIds.length === 0 ? 'opacity-50 cursor-not-allowed bg-slate-300 text-slate-500 shadow-none' : 'bg-brand-blue text-white hover:bg-blue-600 shadow-lg shadow-blue-500/20'"
                            class="px-5 py-3 rounded-xl text-sm font-bold transition-all whitespace-nowrap">
                        Email Outreach (<span x-text="selectedLeadIds.length"></span>)
                    </button>
                </div>
            </div>
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
                        <p class="text-[10px] text-slate-400 mt-3 uppercase tracking-widest font-bold">Expected: MD/CEO, Email (required) · Optional: Organization Name, Salutation, CC, Cell/Phone, Address</p>
                    </div>

                    <div class="flex items-center justify-center gap-1 -mt-1 text-xs text-slate-500 font-medium">
                        <span>Download sample file:</span>
                        <a href="{{ route('leads.import.template.download', ['format' => 'csv']) }}"
                           download
                           class="font-bold text-brand-blue hover:text-blue-700 hover:underline"
                           @click.stop>CSV</a>
                        <span class="text-slate-300">/</span>
                        <a href="{{ route('leads.import.template.download', ['format' => 'xlsx']) }}"
                           download
                           class="font-bold text-brand-blue hover:text-blue-700 hover:underline"
                           @click.stop>Excel</a>
                    </div>

                    <div x-show="selectedFile" class="rounded-xl bg-emerald-50 border border-emerald-100 px-4 py-3 flex items-center justify-between">
                        <div class="min-w-0">
                            <p class="text-sm font-bold text-emerald-800 truncate" x-text="selectedFile?.name"></p>
                            <p class="text-[11px] text-emerald-600" x-text="fileSizeLabel"></p>
                        </div>
                        <button type="button" @click="clearFile()" class="text-emerald-600 hover:text-emerald-800 text-xs font-bold">Remove</button>
                    </div>

                    <div>
                        <label class="text-xs font-bold text-slate-400 mb-2 block">Apply tags/categories to this import batch?</label>
                        <p class="text-[11px] text-slate-400 mb-3">Optional · every created lead in this file will get the selected tags</p>

                        <div class="relative" @click.outside="importCategoryDropdownOpen = false">
                            <div class="relative">
                                <input type="text"
                                       x-ref="importCategorySearchInput"
                                       x-model="importCategorySearch"
                                       @focus="importCategoryDropdownOpen = true"
                                       @input="importCategoryDropdownOpen = true"
                                       @keydown.escape.prevent="importCategoryDropdownOpen = false"
                                       @keydown.enter.prevent="pickFirstImportCategory()"
                                       placeholder="Type to find a category..."
                                       class="w-full bg-slate-50 border border-slate-200 rounded-xl text-sm font-medium text-slate-800 focus:ring-2 focus:ring-brand-blue outline-none py-2.5 pl-4 pr-10 transition-all"
                                       autocomplete="off">
                                <button type="button"
                                        tabindex="-1"
                                        @click="importCategoryDropdownOpen = !importCategoryDropdownOpen; if (importCategoryDropdownOpen) $nextTick(() => $refs.importCategorySearchInput?.focus())"
                                        class="absolute inset-y-0 right-0 pr-3 flex items-center text-slate-400 hover:text-slate-600">
                                    <svg class="w-4 h-4 transition-transform" :class="importCategoryDropdownOpen ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
                                </button>
                            </div>

                            <div x-show="importCategoryDropdownOpen"
                                 x-cloak
                                 class="absolute left-0 right-0 top-full mt-2 z-40 bg-white border border-slate-200 rounded-2xl shadow-xl overflow-hidden">
                                <div class="max-h-56 overflow-y-auto p-1.5">
                                    <template x-for="cat in filteredImportCategories" :key="'import-dd-'+cat.id">
                                        <button type="button"
                                                @mousedown.prevent="addImportCategory(cat.id)"
                                                :class="importCategoryIds.includes(cat.id) ? 'bg-blue-50 text-brand-blue' : 'text-slate-700 hover:bg-slate-50'"
                                                class="w-full text-left px-3 py-2.5 rounded-xl text-xs font-bold transition-colors truncate"
                                                x-text="cat.name"></button>
                                    </template>
                                    <button type="button"
                                            x-show="canCreateImportCategory"
                                            @mousedown.prevent="addImportCategoryName()"
                                            class="w-full text-left px-3 py-2.5 rounded-xl text-xs font-bold text-amber-700 hover:bg-amber-50 transition-colors truncate">
                                        Create “<span x-text="importCategorySearch.trim()"></span>”
                                    </button>
                                    <p x-show="filteredImportCategories.length === 0 && !canCreateImportCategory"
                                       class="px-3 py-4 text-center text-[11px] text-slate-400 font-medium">No categories found</p>
                                </div>
                            </div>
                        </div>

                        <div class="flex flex-wrap gap-2 mt-3" x-show="importCategoryIds.length > 0 || importCategoryNames.length > 0">
                            <template x-for="id in importCategoryIds" :key="'sel-'+id">
                                <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-[11px] font-bold bg-blue-50 text-brand-blue border border-blue-100">
                                    <span x-text="categoryNameById(id)"></span>
                                    <button type="button" @click="toggleImportCategory(id)" class="hover:text-blue-900" title="Remove">&times;</button>
                                </span>
                            </template>
                            <template x-for="(name, idx) in importCategoryNames" :key="'new-cat-'+name">
                                <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-[11px] font-bold bg-amber-50 text-amber-700 border border-amber-100">
                                    <span x-text="name"></span>
                                    <button type="button" @click="importCategoryNames.splice(idx, 1)" class="hover:text-amber-900" title="Remove">&times;</button>
                                </span>
                            </template>
                        </div>
                    </div>

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
                        <p class="text-xs text-slate-400 mt-1">Templates only · Microsoft Graph</p>
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
                                <label class="text-[10px] font-bold text-slate-400 uppercase tracking-widest block mb-1">To</label>
                                <input type="text"
                                       readonly
                                       :value="outreachToPreview()"
                                       :title="outreachToPreview()"
                                       placeholder="No primary email on selected leads"
                                       class="w-full bg-slate-50 border-slate-200 rounded-xl text-sm text-slate-700 focus:ring-0 focus:border-slate-200 py-3 px-4 cursor-default">
                                {{-- <p class="text-[10px] text-slate-400 mt-1">Primary email per lead · used as the main recipient</p>
                                 --}}
                            </div>

                            <div>
                                <label class="text-[10px] font-bold text-slate-400 uppercase tracking-widest block mb-1">CC</label>
                                <input type="text"
                                       readonly
                                       :value="outreachCcPreview()"
                                       :title="outreachCcPreview()"
                                       placeholder="No secondary emails on selected leads"
                                       class="w-full bg-slate-50 border-slate-200 rounded-xl text-sm text-slate-700 focus:ring-0 focus:border-slate-200 py-3 px-4 cursor-default">
                                <p class="text-[10px] text-slate-400 mt-1">Per lead from import · each recipient only gets their own secondary emails</p>
                            </div>

                            <div>
                                <label class="text-[10px] font-bold text-slate-400 uppercase tracking-widest block mb-1">Additional CC (all recipients)</label>
                                <input type="text"
                                       name="cc_emails"
                                       x-model="outreachForm.cc_emails"
                                       placeholder="optional@example.com"
                                       class="w-full bg-slate-50 border-slate-200 rounded-xl text-sm focus:ring-brand-blue focus:border-brand-blue py-3 px-4">
                                <p class="text-[10px] text-slate-400 mt-1">Optional comma-separated addresses added to every email</p>
                            </div>

                            <div>
                                <label class="text-[10px] font-bold text-slate-400 uppercase tracking-widest block mb-1">Email Subject *</label>
                                <input type="text" name="subject" x-model="outreachForm.subject" required class="w-full bg-slate-50 border-slate-200 rounded-xl text-sm focus:ring-brand-blue focus:border-brand-blue py-3 px-4">
                            </div>

                            <div>
                                <label class="text-xs font-bold text-slate-400">Body *</label>
                                <textarea name="body" x-model="outreachForm.body" rows="6" required class="w-full bg-slate-50 border-slate-200 rounded-xl mt-1 p-4 text-sm focus:ring-brand-blue focus:border-brand-blue"></textarea>
                                <p class="text-[10px] text-slate-400 mt-1">Placeholders: @{{salutation}}, @{{fullName}}, @{{companyName}}, @{{email}}, @{{address}}</p>
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
                            <button type="button"
                                    @click="openSaveTemplateModal()"
                                    class="px-6 py-2.5 text-sm font-bold text-brand-blue bg-blue-50 rounded-xl hover:bg-blue-100 border border-blue-100">
                                Save as Template
                            </button>
                            <button type="button" @click="outreachOpen = false" class="px-6 py-2.5 text-sm font-bold text-slate-600 bg-slate-100 rounded-xl hover:bg-slate-200">Cancel</button>
                            <button type="submit" class="px-6 py-2.5 text-sm font-bold text-white bg-brand-blue rounded-xl hover:bg-blue-600 shadow-lg shadow-blue-500/30">Send Outreach</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        {{-- Save as Template modal (from Selected Leads outreach) --}}
        <div x-show="saveTemplateOpen" x-cloak class="fixed inset-0 z-[60] flex items-center justify-center p-4" @keydown.escape.window="closeSaveTemplateModal()">
            <div @click="closeSaveTemplateModal()" class="fixed inset-0 bg-black/50 backdrop-blur-sm"></div>
            <div class="bg-white rounded-2xl shadow-2xl w-full max-w-md relative z-10 overflow-hidden" @click.stop>
                <div class="p-6 border-b border-slate-100">
                    <h3 class="text-lg font-bold text-slate-800">Save as Template</h3>
                    <p class="text-sm text-slate-500 mt-1">Save the current subject, body, and signature for reuse in future campaigns.</p>
                </div>
                <form @submit.prevent="saveOutreachAsTemplate()" class="p-6 space-y-4">
                    <div>
                        <label class="text-[10px] font-bold text-slate-400 uppercase tracking-widest block mb-2">Template Name *</label>
                        <input type="text"
                               x-model="saveTemplateName"
                               required
                               maxlength="255"
                               placeholder="e.g. Initial Outreach v1"
                               class="w-full bg-slate-50 border-slate-200 rounded-xl text-sm py-3 px-4 focus:ring-brand-blue focus:border-brand-blue"
                               :class="saveTemplateError ? 'border-rose-300 ring-1 ring-rose-200' : ''">
                        <p x-show="saveTemplateError" x-text="saveTemplateError" class="text-xs font-semibold text-rose-600 mt-2"></p>
                    </div>
                    <div class="flex justify-end gap-3 pt-2">
                        <button type="button"
                                @click="closeSaveTemplateModal()"
                                class="px-5 py-2.5 rounded-xl text-sm font-bold text-slate-500 hover:bg-slate-100">
                            Cancel
                        </button>
                        <button type="submit"
                                :disabled="saveTemplateSaving || !saveTemplateName.trim()"
                                :class="saveTemplateSaving || !saveTemplateName.trim() ? 'opacity-50 cursor-not-allowed' : 'hover:bg-blue-600'"
                                class="px-6 py-2.5 rounded-xl text-sm font-bold text-white bg-brand-blue shadow-lg shadow-blue-500/20">
                            <span x-show="!saveTemplateSaving">Save Template</span>
                            <span x-show="saveTemplateSaving">Saving…</span>
                        </button>
                    </div>
                </form>
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
                        <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Salutation</p>
                        <p class="text-sm font-medium text-slate-700 mt-1" x-text="detailData?.salutation || '—'"></p>
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
                            <label class="text-[10px] font-bold text-slate-400 uppercase tracking-widest block mb-1">Salutation</label>
                            <input type="text" x-model="editForm.salutation" placeholder="Bhai / Apu / Bro" class="w-full bg-slate-50 border-slate-200 rounded-xl text-sm py-2.5 focus:ring-brand-blue focus:border-brand-blue">
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
                filters: {
                    q: @js(request('q', '')),
                    category: @js($categoryId ?? ''),
                    page: @js((int) request('page', 1)),
                },
                categories: @json(($leadCategories ?? collect())->values()),
                categoryDropdownOpen: false,
                categorySearch: @js(
                    ($categoryId ?? '') !== ''
                        ? (($leadCategories ?? collect())->firstWhere('id', $categoryId)?->name ?? '')
                        : ''
                ),
                importOpen: false,
                dragOver: false,
                selectedFile: null,
                importing: false,
                importCategoryIds: [],
                importCategoryNames: [],
                importCategorySearch: '',
                importCategoryDropdownOpen: false,
                detailOpen: false,
                detailLoading: false,
                detailData: null,
                editOpen: false,
                editSaving: false,
                editId: null,
                editForm: {
                    organization_name: '',
                    contact_name: '',
                    salutation: '',
                    address: '',
                    emails: [''],
                    phones: [],
                },
                selectedLeadIds: [],
                selectedLeadsCache: {},
                selectAll: false,
                outreachOpen: false,
                saveTemplateOpen: false,
                saveTemplateName: '',
                saveTemplateSaving: false,
                saveTemplateError: '',
                outlookConnected: @json($outlookConnected),
                templatesData: @json($templates ?? []),
                selectedTemplate: '',
                outreachFiles: [],
                outreachForm: {
                    subject: '',
                    body: '',
                    cc_emails: '',
                    sender_name: '',
                    sender_role: '',
                    sender_company: '',
                    sender_address: '',
                },
                init() {
                    window.importedLeadsPage = this;

                    const defaultTemplate = this.templatesData.find(t => t.is_default);
                    if (defaultTemplate) {
                        this.selectedTemplate = defaultTemplate.id;
                        this.applyTemplate();
                    }

                    this.cacheVisibleSelectedLeads();

                    this.$watch('selectedLeadIds', (val) => {
                        this.cacheVisibleSelectedLeads();
                        const pageIds = this.currentPageLeadIds();
                        this.selectAll = pageIds.length > 0 && pageIds.every((id) => val.includes(id));
                    });
                },

                currentPageLeadIds() {
                    return Array.from(document.querySelectorAll('.imported-lead-checkbox')).map((cb) => cb.value);
                },

                cacheVisibleSelectedLeads() {
                    const next = { ...this.selectedLeadsCache };
                    document.querySelectorAll('.imported-lead-checkbox').forEach((cb) => {
                        next[cb.value] = {
                            org: cb.dataset.org || next[cb.value]?.org || 'Lead',
                            contact: cb.dataset.contact || next[cb.value]?.contact || '',
                            to: cb.dataset.to || next[cb.value]?.to || '',
                            cc: cb.dataset.cc || next[cb.value]?.cc || '',
                        };
                    });
                    this.selectedLeadsCache = next;
                },

                get selectedCategoryLabel() {
                    if (!this.filters.category) return 'All Categories';
                    const cat = this.categories.find(c => String(c.id) === String(this.filters.category));
                    return cat?.name || 'All Categories';
                },

                get filteredCategories() {
                    const q = (this.categorySearch || '').trim().toLowerCase();
                    let list = this.categories;
                    if (q) {
                        list = list.filter(c => (c.name || '').toLowerCase().includes(q));
                    }
                    return list.slice(0, 20);
                },

                openCategoryDropdown() {
                    this.categoryDropdownOpen = true;
                    this.categorySearch = '';
                    this.$nextTick(() => this.$refs.categorySearchInput?.select?.());
                },

                toggleCategoryDropdown() {
                    if (this.categoryDropdownOpen) {
                        this.closeCategoryDropdown();
                    } else {
                        this.openCategoryDropdown();
                        this.$nextTick(() => this.$refs.categorySearchInput?.focus());
                    }
                },

                closeCategoryDropdown() {
                    this.categoryDropdownOpen = false;
                    this.categorySearch = this.filters.category ? this.selectedCategoryLabel : '';
                },

                selectCategory(id) {
                    this.filters.category = id || '';
                    this.categoryDropdownOpen = false;
                    this.categorySearch = id ? (this.categories.find(c => String(c.id) === String(id))?.name || '') : '';
                    this.fetchLeads(1);
                },

                selectFirstFilteredCategory() {
                    if (this.filteredCategories.length > 0) {
                        this.selectCategory(this.filteredCategories[0].id);
                    } else if (!(this.categorySearch || '').trim()) {
                        this.selectCategory('');
                    }
                },

                async bulkDeleteSelected() {
                    if (this.selectedLeadIds.length === 0) return;
                    const ok = await window.confirmBulkDelete(this.selectedLeadIds.length, 'imported lead');
                    if (!ok) return;

                    const form = document.createElement('form');
                    form.method = 'POST';
                    form.action = @js(route('imported-leads.bulk-delete'));

                    const csrf = document.createElement('input');
                    csrf.type = 'hidden';
                    csrf.name = '_token';
                    csrf.value = document.querySelector('meta[name="csrf-token"]')?.content || '';
                    form.appendChild(csrf);

                    this.selectedLeadIds.forEach((id) => {
                        const input = document.createElement('input');
                        input.type = 'hidden';
                        input.name = 'ids[]';
                        input.value = id;
                        form.appendChild(input);
                    });

                    document.body.appendChild(form);
                    form.submit();
                },

                toggleSelectAll() {
                    const pageIds = this.currentPageLeadIds();
                    this.cacheVisibleSelectedLeads();

                    if (this.selectAll) {
                        this.selectedLeadIds = [...new Set([...this.selectedLeadIds, ...pageIds])];
                    } else {
                        this.selectedLeadIds = this.selectedLeadIds.filter((id) => !pageIds.includes(id));
                    }
                },

                leadLabel(id) {
                    const cached = this.selectedLeadsCache[id];
                    if (cached) {
                        return {
                            org: cached.org || 'Lead',
                            contact: cached.contact || '',
                        };
                    }

                    const cb = document.querySelector('.imported-lead-checkbox[value="' + id + '"]');
                    return {
                        org: cb?.dataset?.org || 'Lead',
                        contact: cb?.dataset?.contact || '',
                    };
                },

                outreachToPreview() {
                    return this.selectedLeadIds
                        .map((id) => this.selectedLeadsCache[id]?.to || '')
                        .map((email) => String(email || '').trim())
                        .filter(Boolean)
                        .join(', ');
                },

                outreachCcPreview() {
                    return this.selectedLeadIds
                        .flatMap((id) => String(this.selectedLeadsCache[id]?.cc || '').split(','))
                        .map((email) => email.trim())
                        .filter(Boolean)
                        .filter((email, index, all) => all.findIndex((e) => e.toLowerCase() === email.toLowerCase()) === index)
                        .join(', ');
                },

                openOutreachModal() {
                    if (this.selectedLeadIds.length === 0) return;
                    if (!this.outlookConnected) {
                        window.toast?.warning('Connect Microsoft Outlook first under Integrations.');
                        return;
                    }
                    this.cacheVisibleSelectedLeads();
                    this.outreachFiles = [];
                    this.outreachForm.cc_emails = '';
                    this.saveTemplateOpen = false;
                    this.saveTemplateName = '';
                    this.saveTemplateError = '';
                    this.outreachOpen = true;
                },

                openSaveTemplateModal() {
                    const subject = (this.outreachForm.subject || '').trim();
                    const body = (this.outreachForm.body || '').trim();

                    if (!subject || !body) {
                        window.toast?.warning('Add an email subject and body before saving as a template.');
                        return;
                    }

                    this.saveTemplateName = '';
                    this.saveTemplateError = '';
                    this.saveTemplateOpen = true;
                },

                closeSaveTemplateModal() {
                    if (this.saveTemplateSaving) {
                        return;
                    }

                    this.saveTemplateOpen = false;
                    this.saveTemplateName = '';
                    this.saveTemplateError = '';
                },

                async saveOutreachAsTemplate() {
                    const name = (this.saveTemplateName || '').trim();
                    if (!name) {
                        this.saveTemplateError = 'Template name is required.';
                        return;
                    }

                    const subject = (this.outreachForm.subject || '').trim();
                    const body = (this.outreachForm.body || '').trim();
                    if (!subject || !body) {
                        this.saveTemplateError = 'Subject and body are required.';
                        return;
                    }

                    this.saveTemplateSaving = true;
                    this.saveTemplateError = '';

                    try {
                        const response = await fetch(@js(url('/templates/from-outreach')), {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                            },
                            body: JSON.stringify({
                                name,
                                subject,
                                body,
                                signature_name: (this.outreachForm.sender_name || '').trim() || null,
                                signature_position: (this.outreachForm.sender_role || '').trim() || null,
                                signature_company: (this.outreachForm.sender_company || '').trim() || null,
                                signature_address: (this.outreachForm.sender_address || '').trim() || null,
                            }),
                        });

                        const data = await response.json().catch(() => ({}));

                        if (!response.ok) {
                            const nameError = data?.errors?.name?.[0];
                            this.saveTemplateError = nameError || data?.message || 'Could not save template.';
                            return;
                        }

                        const template = data.template;
                        if (template) {
                            const exists = this.templatesData.some((t) => String(t.id) === String(template.id));
                            if (!exists) {
                                this.templatesData = [...this.templatesData, template];
                            }
                            this.selectedTemplate = template.id;
                        }

                        this.saveTemplateSaving = false;
                        this.saveTemplateOpen = false;
                        this.saveTemplateName = '';
                        this.saveTemplateError = '';
                        window.toast?.success(data.message || 'Template saved successfully.');
                    } catch (error) {
                        this.saveTemplateError = 'Could not save template. Please try again.';
                    } finally {
                        this.saveTemplateSaving = false;
                    }
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
                    this.importCategoryIds = [];
                    this.importCategoryNames = [];
                    this.importCategorySearch = '';
                    this.importCategoryDropdownOpen = false;
                },
                closeImportModal() {
                    this.importOpen = false;
                    this.clearFile();
                    this.importCategoryIds = [];
                    this.importCategoryNames = [];
                    this.importCategorySearch = '';
                    this.importCategoryDropdownOpen = false;
                },
                get filteredImportCategories() {
                    const q = (this.importCategorySearch || '').trim().toLowerCase();
                    let list = this.categories;
                    if (q) {
                        list = list.filter(c => (c.name || '').toLowerCase().includes(q));
                    }
                    return list.slice(0, 20);
                },
                get canCreateImportCategory() {
                    const name = (this.importCategorySearch || '').trim();
                    if (!name) return false;
                    const existsAsCategory = this.categories.some(c => c.name.toLowerCase() === name.toLowerCase());
                    const existsInNew = this.importCategoryNames.some(n => n.toLowerCase() === name.toLowerCase());
                    return !existsAsCategory && !existsInNew;
                },
                categoryNameById(id) {
                    return this.categories.find(c => String(c.id) === String(id))?.name || 'Category';
                },
                addImportCategory(id) {
                    if (!this.importCategoryIds.includes(id)) {
                        this.importCategoryIds.push(id);
                    }
                    this.importCategorySearch = '';
                    this.importCategoryDropdownOpen = false;
                },
                toggleImportCategory(id) {
                    const idx = this.importCategoryIds.indexOf(id);
                    if (idx === -1) {
                        this.importCategoryIds.push(id);
                    } else {
                        this.importCategoryIds.splice(idx, 1);
                    }
                },
                addImportCategoryName() {
                    const name = (this.importCategorySearch || '').trim();
                    if (!name) return;

                    const existsAsCategory = this.categories.find(c => c.name.toLowerCase() === name.toLowerCase());
                    if (existsAsCategory) {
                        this.addImportCategory(existsAsCategory.id);
                        return;
                    }

                    if (!this.importCategoryNames.some(n => n.toLowerCase() === name.toLowerCase())) {
                        this.importCategoryNames.push(name);
                    }
                    this.importCategorySearch = '';
                    this.importCategoryDropdownOpen = false;
                },
                pickFirstImportCategory() {
                    if (this.filteredImportCategories.length > 0) {
                        this.addImportCategory(this.filteredImportCategories[0].id);
                    } else if (this.canCreateImportCategory) {
                        this.addImportCategoryName();
                    }
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
                    if (!file) return;
                    const name = file.name.toLowerCase();
                    if (!(/\.(csv|xlsx|xls)$/.test(name))) {
                        window.toast?.error('Only CSV, XLSX, and XLS files are allowed.');
                        this.clearFile();
                        return;
                    }
                    if (file.size > 10 * 1024 * 1024) {
                        window.toast?.error('File size must be 10 MB or less.');
                        this.clearFile();
                        return;
                    }
                    this.selectedFile = file;
                },
                buildImportFormData() {
                    const formData = new FormData();
                    formData.append('file', this.selectedFile);
                    this.importCategoryIds.forEach(id => formData.append('category_ids[]', id));
                    this.importCategoryNames.forEach(name => formData.append('category_names[]', name));
                    return formData;
                },
                importRequestHeaders() {
                    return {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                    };
                },
                escapeHtml(value) {
                    return String(value ?? '')
                        .replace(/&/g, '&amp;')
                        .replace(/</g, '&lt;')
                        .replace(/>/g, '&gt;')
                        .replace(/"/g, '&quot;')
                        .replace(/'/g, '&#39;');
                },
                async checkImportMissingData() {
                    const res = await fetch(@js(route('imported-leads.import.validate')), {
                        method: 'POST',
                        headers: this.importRequestHeaders(),
                        body: this.buildImportFormData(),
                        credentials: 'same-origin',
                    });
                    const data = await res.json();
                    if (!res.ok || !data.success) {
                        throw new Error(data.message || Object.values(data.errors || {}).flat()[0] || 'Import validation failed.');
                    }

                    return {
                        issues: Array.isArray(data.missing_issues) ? data.missing_issues : [],
                        issueRows: Number(data.missing_issue_rows) || 0,
                        totalRows: Number(data.total_rows) || 0,
                        validCount: Number(data.valid_count) || 0,
                        skipCount: Number(data.skip_count) || 0,
                        truncated: Boolean(data.missing_truncated),
                    };
                },
                async performImport() {
                    const res = await fetch(@js(route('imported-leads.import')), {
                        method: 'POST',
                        headers: this.importRequestHeaders(),
                        body: this.buildImportFormData(),
                        credentials: 'same-origin',
                    });
                    const data = await res.json();
                    if (!res.ok || !data.success) {
                        throw new Error(data.message || Object.values(data.errors || {}).flat()[0] || 'Import failed.');
                    }

                    window.toast?.success(data.message || 'Import complete.');

                    if (Array.isArray(data.categories)) {
                        this.categories = data.categories;
                    }
                    this.clearFile();
                    this.importCategoryIds = [];
                    this.importCategoryNames = [];
                    this.importCategorySearch = '';
                    this.importCategoryDropdownOpen = false;
                    await this.fetchLeads();
                    this.closeImportModal();
                },
                async submitImport() {
                    if (!this.selectedFile || this.importing) return;

                    try {
                        this.importing = true;
                        const missingData = await this.checkImportMissingData();
                        const missingIssues = missingData.issues;

                        if (missingIssues.length > 0) {
                            this.importing = false;
                            const issueList = missingIssues
                                .map((issue) => {
                                    const cols = (issue.columns || []).map(c => this.escapeHtml(c)).join(', ');
                                    return `<li>Row ${issue.row} has missing ${cols}</li>`;
                                })
                                .join('');
                            const moreHtml = missingData.truncated
                                ? `<span class="lf-swal-missing-more">+${missingData.totalRows - missingIssues.length} more rows not shown</span>`
                                : '';

                            const statsHtml = `<div class="lf-swal-missing-stats">`
                                + `<span class="lf-swal-stat lf-swal-stat--valid"><strong>${missingData.validCount}</strong> valid lead${missingData.validCount !== 1 ? 's' : ''} will be imported </span>`
                                + `<span class="lf-swal-stat lf-swal-stat--skip"><strong>${missingData.skipCount}</strong> lead${missingData.skipCount !== 1 ? 's' : ''} will be skipped</span>`
                                + `</div>`;

                            const result = await window.Swal.fire({
                                icon: 'warning',
                                title: 'Missing Data Detected',
                                html: statsHtml
                                    + `<span class="lf-swal-missing-intro">The following rows have missing data:</span>`
                                    + `<ul class="lf-swal-missing-list">${issueList}</ul>${moreHtml}`
                                    + `<span class="lf-swal-missing-footer">Do you want to proceed anyway?</span>`,
                                showCancelButton: true,
                                confirmButtonText: 'Yes, proceed',
                                cancelButtonText: 'No, cancel',
                            });

                            if (!result.isConfirmed) {
                                return;
                            }

                            this.importing = true;
                        }

                        await this.performImport();
                    } catch (err) {
                        window.toast?.error(err.message || 'Import failed.');
                    } finally {
                        this.importing = false;
                    }
                },

                async fetchLeads(page = null) {
                    this.loading = true;
                    if (page !== null) {
                        this.filters.page = page;
                    }
                    try {
                        const params = new URLSearchParams();
                        if (this.filters.q) params.set('q', this.filters.q);
                        if (this.filters.category) params.set('category', this.filters.category);
                        if (this.filters.page && Number(this.filters.page) > 1) {
                            params.set('page', String(this.filters.page));
                        }
                        const qs = params.toString();
                        const res = await fetch(@js(route('imported-leads.index')) + (qs ? '?' + qs : ''), {
                            headers: { 'X-Requested-With': 'XMLHttpRequest' },
                            credentials: 'same-origin',
                        });
                        const html = await res.text();
                        const el = document.getElementById('imported-leads-table');
                        el.innerHTML = html;
                        if (window.Alpine?.initTree) window.Alpine.initTree(el);

                        this.cacheVisibleSelectedLeads();
                        const pageIds = this.currentPageLeadIds();
                        this.selectAll = pageIds.length > 0 && pageIds.every((id) => this.selectedLeadIds.includes(id));

                        window.history.replaceState({}, '', @js(route('imported-leads.index')) + (qs ? '?' + qs : ''));
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
                        window.toast?.error('Unable to load lead details.');
                    } finally {
                        this.detailLoading = false;
                    }
                },

                async openEdit(id) {
                    this.editOpen = true;
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
                            salutation: data.salutation || '',
                            address: data.address || '',
                            emails: (data.emails || []).map(e => e.email),
                            phones: (data.phones || []).map(p => p.phone),
                        };
                        if (!this.editForm.emails.length) this.editForm.emails = [''];
                    } catch {
                        this.editOpen = false;
                        window.toast?.error('Unable to load lead for editing.');
                    }
                },

                async submitEdit() {
                    if (this.editSaving || !this.editId) return;
                    this.editSaving = true;

                    const payload = {
                        organization_name: this.editForm.organization_name,
                        contact_name: this.editForm.contact_name,
                        salutation: this.editForm.salutation,
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
                        window.toast?.success(data.message || 'Imported lead updated.');
                        await this.fetchLeads();
                    } catch (err) {
                        window.toast?.error(err.message || 'Update failed.');
                    } finally {
                        this.editSaving = false;
                    }
                },
            };
        }
        window.importedLeadManager = importedLeadManager;

        document.addEventListener('click', function (e) {
            if (e.target.closest('#imported-leads-table .pagination a')) {
                e.preventDefault();
                const link = e.target.closest('a');
                if (!link?.href) return;
                const url = new URL(link.href);
                const page = url.searchParams.get('page') || '1';
                if (window.importedLeadsPage && typeof window.importedLeadsPage.fetchLeads === 'function') {
                    window.importedLeadsPage.fetchLeads(page);
                } else {
                    window.location.href = link.href;
                }
            }
        });
    </script>
    @endpush
</x-app-layout>
