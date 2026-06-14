<x-app-layout>
    <x-slot name="header">
        Users and Plans
    </x-slot>

    <x-slot name="actions">
        <a href="{{ route('admin.users.create') }}" class="bg-brand-blue text-white px-6 py-2 rounded-xl text-sm font-bold hover:bg-blue-600 transition-colors flex items-center shadow-lg shadow-blue-500/20">
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path></svg>
            CREATE USER
        </a>
    </x-slot>

    <div class="space-y-8" x-data="userManagement()">
        <!-- Filters Panel -->
        <div class="bg-white rounded-3xl p-6 shadow-sm border border-slate-100">
            <div class="flex flex-col md:flex-row items-end space-y-4 md:space-y-0 md:space-x-4">
                <div class="flex-1 w-full">
                    <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-2">Search User</label>
                    <input type="text" x-model="filters.search" @input.debounce.300ms="fetchUsers()" placeholder="Name or email..." class="w-full bg-slate-50 border-slate-200 rounded-xl text-sm focus:ring-brand-blue focus:border-brand-blue py-2.5">
                </div>
                <div class="w-full md:w-48">
                    <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-2">Role</label>
                    <select x-model="filters.role" @change="fetchUsers()" class="w-full bg-slate-50 border-slate-200 rounded-xl text-sm focus:ring-brand-blue focus:border-brand-blue py-2.5">
                        <option value="">All Roles</option>
                        <option value="admin">Admin</option>
                        <option value="user">User</option>
                    </select>
                </div>
                <div class="w-full md:w-48">
                    <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-2">Status</label>
                    <select x-model="filters.status" @change="fetchUsers()" class="w-full bg-slate-50 border-slate-200 rounded-xl text-sm focus:ring-brand-blue focus:border-brand-blue py-2.5">
                        <option value="">All Statuses</option>
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                        <option value="suspended">Suspended</option>
                    </select>
                </div>
                <div class="flex space-x-2 w-full md:w-auto">
                    <button @click="resetFilters()" class="flex-1 md:flex-none bg-white border border-slate-200 text-slate-600 font-bold py-2.5 px-6 rounded-xl text-sm hover:bg-slate-50 transition-colors text-center">
                        RESET
                    </button>
                </div>
            </div>
        </div>

        <!-- Users Table Container -->
        <div class="bg-white rounded-3xl shadow-sm border border-slate-100 overflow-hidden relative">
            <div x-show="tableLoading" class="absolute inset-0 bg-white/60 backdrop-blur-[1px] flex items-center justify-center z-20" x-cloak>
                <div class="animate-spin w-8 h-8 border-4 border-brand-blue border-t-transparent rounded-full"></div>
            </div>
            <div id="users-table-body">
                @include('admin.users.partials.table', ['users' => $users])
            </div>
        </div>

        <!-- Edit Profile Modal -->
        <div x-show="editProfileOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4" x-cloak>
            <div @click="editProfileOpen = false" class="fixed inset-0 bg-black/60 backdrop-blur-sm transition-opacity"></div>
            <div class="bg-white rounded-3xl shadow-2xl w-full max-w-lg relative z-10 overflow-hidden transform transition-all"
                 x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
                 x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95">
                <div class="p-6 border-b border-slate-100 flex justify-between items-center bg-slate-50/50">
                    <h3 class="text-lg font-bold text-slate-800">Edit User Profile</h3>
                    <button @click="editProfileOpen = false" class="text-slate-400 hover:text-slate-600 transition-colors">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                    </button>
                </div>
                <form :action="'/admin/users/' + selectedUser.id" method="POST" class="p-6 space-y-4">
                    @csrf
                    @method('PUT')
                    
                    <div class="grid grid-cols-2 gap-4">
                        <div class="col-span-2">
                            <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-2">Full Name</label>
                            <input type="text" name="name" x-model="selectedUser.name" required class="w-full bg-slate-50 border-slate-200 rounded-xl text-sm focus:ring-brand-blue focus:border-brand-blue py-2.5">
                        </div>
                        <div class="col-span-2">
                            <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-2">Email Address</label>
                            <input type="email" name="email" x-model="selectedUser.email" required class="w-full bg-slate-50 border-slate-200 rounded-xl text-sm focus:ring-brand-blue focus:border-brand-blue py-2.5">
                        </div>
                        <div>
                            <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-2">Role</label>
                            <select name="role" x-model="selectedUser.role" class="w-full bg-slate-50 border-slate-200 rounded-xl text-sm focus:ring-brand-blue focus:border-brand-blue py-2.5">
                                <option value="user">User</option>
                                <option value="admin">Admin</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-2">Status</label>
                            <select name="status" x-model="selectedUser.status" class="w-full bg-slate-50 border-slate-200 rounded-xl text-sm focus:ring-brand-blue focus:border-brand-blue py-2.5">
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                                <option value="suspended">Suspended</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-2">New Password (Optional)</label>
                            <input type="password" name="password" placeholder="Leave blank to keep current" class="w-full bg-slate-50 border-slate-200 rounded-xl text-sm focus:ring-brand-blue focus:border-brand-blue py-2.5">
                        </div>
                        <div>
                            <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-2">Confirm Password</label>
                            <input type="password" name="password_confirmation" placeholder="Repeat new password" class="w-full bg-slate-50 border-slate-200 rounded-xl text-sm focus:ring-brand-blue focus:border-brand-blue py-2.5">
                        </div>
                    </div>

                    <div class="pt-4 flex justify-end space-x-3">
                        <button type="button" @click="editProfileOpen = false" class="px-6 py-2.5 rounded-xl text-sm font-bold text-slate-500 hover:bg-slate-100 transition-colors">CANCEL</button>
                        <button type="submit" class="bg-brand-blue text-white px-8 py-2.5 rounded-xl text-sm font-bold hover:bg-blue-600 transition-colors shadow-lg shadow-blue-500/20">UPDATE PROFILE</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Edit Limits Modal -->
        <div x-show="editLimitsOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4" x-cloak>
            <div @click="editLimitsOpen = false" class="fixed inset-0 bg-black/60 backdrop-blur-sm transition-opacity"></div>
            <div class="bg-white rounded-3xl shadow-2xl w-full max-w-2xl relative z-10 overflow-hidden transform transition-all"
                 x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
                 x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95">
                <div class="p-6 border-b border-slate-100 flex justify-between items-center bg-slate-50/50">
                    <div>
                        <h3 class="text-lg font-bold text-slate-800">Usage Limits & Plan</h3>
                        <p class="text-xs text-slate-400">Configure boundaries for <span class="font-bold text-slate-600" x-text="selectedUser.name"></span></p>
                    </div>
                    <button @click="editLimitsOpen = false" class="text-slate-400 hover:text-slate-600 transition-colors">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                    </button>
                </div>
                <form :action="'/admin/users/' + selectedUser.id" method="POST" class="p-6">
                    @csrf
                    @method('PUT')
                    
                    {{-- Hidden fields to preserve profile info if needed, or controller can handle partial --}}
                    <input type="hidden" name="name" :value="selectedUser.name">
                    <input type="hidden" name="email" :value="selectedUser.email">
                    <input type="hidden" name="role" :value="selectedUser.role">
                    <input type="hidden" name="status" :value="selectedUser.status">

                    <div class="grid grid-cols-2 md:grid-cols-3 gap-6">
                        <div>
                            <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-2">Lead Search Limit</label>
                            <input type="number" name="lead_search_limit" x-model="selectedUser.lead_search_limit" min="0" class="w-full bg-slate-50 border-slate-200 rounded-xl text-sm focus:ring-brand-blue focus:border-brand-blue py-2.5">
                        </div>
                        <div>
                            <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-2">Lead Export Limit</label>
                            <input type="number" name="lead_export_limit" x-model="selectedUser.lead_export_limit" min="0" class="w-full bg-slate-50 border-slate-200 rounded-xl text-sm focus:ring-brand-blue focus:border-brand-blue py-2.5">
                        </div>
                        <div>
                            <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-2">Lead Storage Limit</label>
                            <input type="number" name="lead_storage_limit" x-model="selectedUser.lead_storage_limit" min="0" class="w-full bg-slate-50 border-slate-200 rounded-xl text-sm focus:ring-brand-blue focus:border-brand-blue py-2.5">
                        </div>

                        <div>
                            <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-2">Email Send Limit</label>
                            <input type="number" name="email_send_limit" x-model="selectedUser.email_send_limit" min="0" class="w-full bg-slate-50 border-slate-200 rounded-xl text-sm focus:ring-brand-blue focus:border-brand-blue py-2.5">
                        </div>
                    </div>

                    <div class="mt-6">
                        <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-2">Internal Notes</label>
                        <textarea name="notes" x-model="selectedUser.notes" rows="3" class="w-full bg-slate-50 border-slate-200 rounded-xl text-sm focus:ring-brand-blue focus:border-brand-blue py-2.5"></textarea>
                    </div>

                    <div class="pt-6 flex justify-end space-x-3">
                        <button type="button" @click="editLimitsOpen = false" class="px-6 py-2.5 rounded-xl text-sm font-bold text-slate-500 hover:bg-slate-100 transition-colors">CANCEL</button>
                        <button type="submit" class="bg-slate-800 text-white px-8 py-2.5 rounded-xl text-sm font-bold hover:bg-slate-900 transition-colors shadow-lg shadow-slate-500/20">SAVE LIMITS</button>
                    </div>
                </form>
            </div>
        </div>
        <!-- Plan Management Modal -->
        <div x-show="showPlanModal" class="fixed inset-0 z-50 flex items-center justify-center bg-black bg-opacity-50" x-cloak>
            <div @click="showPlanModal = false" class="fixed inset-0 bg-black/60 backdrop-blur-sm transition-opacity"></div>
            <div class="bg-white/90 backdrop-blur-md rounded-3xl shadow-2xl w-full max-w-lg relative z-10 overflow-hidden transform transition-all border border-white/20"
                 x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 scale-95" x-transition:enter-end="opacity-100 scale-100"
                 x-transition:leave="transition ease-in duration-150" x-transition:leave-start="opacity-100 scale-100" x-transition:leave-end="opacity-0 scale-95">
                <div class="p-6 border-b border-slate-100/50 flex justify-between items-center bg-slate-50/30">
                    <h3 class="text-lg font-bold text-slate-800">Manage Plan Quotas - <span x-text="selectedUserName"></span></h3>
                    <button @click="showPlanModal = false" class="text-slate-400 hover:text-slate-600 transition-colors">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                    </button>
                </div>
                <form action="{{ route('admin.users.update-plan') }}" method="POST" class="p-6 space-y-4">
                    @csrf
                    <input type="hidden" name="user_id" x-model="selectedUserId">
                    
                    <div class="grid grid-cols-1 gap-4">
                        <div>
                            <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-2">Search Limit</label>
                            <input type="number" name="search_limit" x-model.number="searchLimit" required min="0" class="w-full bg-slate-50/50 border-slate-200/50 rounded-xl text-sm focus:ring-brand-blue focus:border-brand-blue py-2.5">
                        </div>
                        <div>
                            <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-2">Expiration Date (Min 1 Month)</label>
                            <input type="date" name="expiry_date" x-model="expiryDate" required min="{{ now()->addMonth()->format('Y-m-d') }}" class="w-full bg-slate-50/50 border-slate-200/50 rounded-xl text-sm focus:ring-brand-blue focus:border-brand-blue py-2.5">
                        </div>
                    </div>

                    <div class="pt-2">
                        <label class="block text-[10px] font-bold text-slate-400 uppercase tracking-widest mb-2">Quick Duration Presets</label>
                        <div class="flex flex-wrap gap-2">
                            <button @click.prevent="setPresetMonths(1)" class="bg-blue-50 text-brand-blue border border-blue-100 font-bold py-1.5 px-3 rounded-lg text-xs hover:bg-blue-100 transition-colors">1 Month</button>
                            <button @click.prevent="setPresetMonths(3)" class="bg-blue-50 text-brand-blue border border-blue-100 font-bold py-1.5 px-3 rounded-lg text-xs hover:bg-blue-100 transition-colors">3 Months</button>
                            <button @click.prevent="setPresetMonths(6)" class="bg-blue-50 text-brand-blue border border-blue-100 font-bold py-1.5 px-3 rounded-lg text-xs hover:bg-blue-100 transition-colors">6 Months</button>
                            <button @click.prevent="setPresetMonths(12)" class="bg-blue-50 text-brand-blue border border-blue-100 font-bold py-1.5 px-3 rounded-lg text-xs hover:bg-blue-100 transition-colors">12 Months</button>
                        </div>
                    </div>

                    <div class="pt-4 flex justify-end space-x-3">
                        <button type="button" @click="showPlanModal = false" class="px-6 py-2.5 rounded-xl text-sm font-bold text-slate-500 hover:bg-slate-100 transition-colors">Cancel</button>
                        <button type="submit" class="bg-brand-blue text-white px-8 py-2.5 rounded-xl text-sm font-bold hover:bg-blue-600 transition-colors shadow-lg shadow-blue-500/20">Save Plan Parameters</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        function userManagement() {
            return {
                editProfileOpen: false,
                editLimitsOpen: false,
                tableLoading: false,
                selectedUser: {},
                filters: {
                    search: '{{ request('search') }}',
                    role: '{{ request('role') }}',
                    status: '{{ request('status') }}'
                },

                init() {
                    // Handle pagination clicks via AJAX
                    document.addEventListener('click', (e) => {
                        const link = e.target.closest('.ajax-pagination a');
                        if (link) {
                            e.preventDefault();
                            this.fetchUsers(link.href);
                        }
                    });
                },

                async fetchUsers(url = null) {
                    this.tableLoading = true;
                    try {
                        const baseUrl = url || window.location.pathname;
                        const params = new URLSearchParams(this.filters);
                        const targetUrl = url ? url : `${baseUrl}?${params.toString()}`;

                        const response = await fetch(targetUrl, {
                            headers: {
                                'X-Requested-With': 'XMLHttpRequest'
                            }
                        });
                        if (response.ok) {
                            const html = await response.text();
                            const tableBody = document.getElementById('users-table-body');
                            tableBody.innerHTML = html;

                            if (window.Alpine && typeof window.Alpine.initTree === 'function') {
                                window.Alpine.initTree(tableBody);
                            }

                            // Update browser URL without refresh
                            if (!url) {
                                window.history.pushState({}, '', targetUrl);
                            } else {
                                window.history.pushState({}, '', url);
                            }
                        }
                    } catch (e) {
                        console.error('Failed to fetch users:', e);
                    } finally {
                        this.tableLoading = false;
                    }
                },

                resetFilters() {
                    this.filters = { search: '', role: '', status: '' };
                    this.fetchUsers();
                },
                
                openEditProfile(user) {
                    this.selectedUser = JSON.parse(JSON.stringify(user));
                    this.editProfileOpen = true;
                },
                
                openEditLimits(user) {
                    this.selectedUser = JSON.parse(JSON.stringify(user));
                    this.editLimitsOpen = true;
                },

                showPlanModal: false, 
                selectedUserId: '', 
                selectedUserName: '',
                searchLimit: 0, 
                expiryDate: '',
                setPresetMonths(months) {
                    let d = new Date();
                    d.setMonth(d.getMonth() + months);
                    this.expiryDate = d.toISOString().split('T')[0];
                }
            }
        }
    </script>
</x-app-layout>
