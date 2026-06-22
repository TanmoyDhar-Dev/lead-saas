<x-app-layout>
    <x-slot name="header">
        Users and Plans
    </x-slot>

    <x-slot name="actions">
        <a href="{{ route('admin.users.create') }}" class="bg-indigo-600 text-white px-6 py-3 rounded-xl text-[10px] font-black uppercase tracking-widest hover:bg-indigo-700 transition-all flex items-center shadow-lg shadow-indigo-500/20 active:scale-95">
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path></svg>
            Create New User
        </a>
    </x-slot>

    <div class="space-y-6" x-data="userManagement()">
        <!-- Filters Panel -->
        <div class="bg-white rounded-[2rem] p-6 shadow-sm border border-slate-200/60">
            <div class="flex flex-col md:flex-row items-end gap-4">
                <div class="flex-1 w-full">
                    <label class="block text-[8px] font-black text-slate-400 uppercase tracking-widest mb-2">Search User</label>
                    <input type="text" x-model="filters.search" @input.debounce.300ms="fetchUsers()" placeholder="Name or email..." class="w-full bg-slate-50 border border-slate-200 rounded-xl text-xs font-bold focus:ring-2 focus:ring-indigo-500 outline-none py-3 px-4 transition-all">
                </div>
                <div class="w-full md:w-48">
                    <label class="block text-[8px] font-black text-slate-400 uppercase tracking-widest mb-2">Role</label>
                    <select x-model="filters.role" @change="fetchUsers()" class="w-full bg-slate-50 border border-slate-200 rounded-xl text-xs font-bold focus:ring-2 focus:ring-indigo-500 outline-none py-3 px-4 transition-all">
                        <option value="">All Roles</option>
                        <option value="admin">Admin</option>
                        <option value="user">User</option>
                    </select>
                </div>
                <div class="w-full md:w-48">
                    <label class="block text-[8px] font-black text-slate-400 uppercase tracking-widest mb-2">Status</label>
                    <select x-model="filters.status" @change="fetchUsers()" class="w-full bg-slate-50 border border-slate-200 rounded-xl text-xs font-bold focus:ring-2 focus:ring-indigo-500 outline-none py-3 px-4 transition-all">
                        <option value="">All Statuses</option>
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                        <option value="suspended">Suspended</option>
                    </select>
                </div>
                <div class="flex gap-2 w-full md:w-auto">
                    <button @click="resetFilters()" class="flex-1 md:flex-none bg-white border border-slate-200 text-slate-600 font-black py-3 px-6 rounded-xl text-[10px] uppercase tracking-widest hover:bg-slate-50 transition-colors text-center">
                        Reset
                    </button>
                </div>
            </div>
        </div>

        <!-- Users Table Container -->
        <div class="bg-white rounded-[2rem] shadow-sm border border-slate-200/60 overflow-hidden relative">
            <div x-show="tableLoading" class="absolute inset-0 bg-white/60 backdrop-blur-[1px] flex items-center justify-center z-20" x-cloak>
                <div class="animate-spin w-8 h-8 border-4 border-indigo-600 border-t-transparent rounded-full"></div>
            </div>
            <div id="users-table-body">
                @include('admin.users.partials.table', ['users' => $users])
            </div>
        </div>

        {{-- Modals Backdrop & Wrapper --}}
        <div id="modal-backdrop" class="hidden fixed inset-0 z-[60] flex items-center justify-center bg-gray-900/50 backdrop-blur-sm">
            
            {{-- Status Modal --}}
            <div id="status-modal" class="hidden w-full max-w-sm bg-white rounded-[2rem] z-[70] shadow-2xl p-8 border border-slate-100 relative">
                <h3 class="text-base font-black text-slate-900 uppercase tracking-widest mb-1 italic">Security <span class="text-indigo-600">Guard</span></h3>
                <p id="status-email" class="text-[10px] text-slate-400 font-bold uppercase mb-6"></p>
                <form id="status-form" method="POST" class="space-y-4">
                    @csrf
                    <select name="status" class="w-full px-4 py-3.5 bg-slate-50 border border-slate-200 rounded-xl font-bold text-xs text-slate-700 focus:ring-2 focus:ring-indigo-500 outline-none transition-all">
                        <option value="Active (Paid)">Active (Paid)</option>
                        <option value="Inactive (Revoke Access)">Inactive (Revoke Access)</option>
                        <option value="Past Due (Payment Failed)">Past Due (Payment Failed)</option>
                    </select>
                    <div class="flex flex-col gap-2 pt-2">
                        <button type="submit" class="w-full bg-slate-950 text-white py-3.5 rounded-xl font-black uppercase text-[10px] tracking-widest shadow-lg hover:bg-indigo-600 transition-all">Update Access</button>
                        <button type="button" onclick="closeModals()" class="w-full py-2 text-slate-400 font-bold text-[10px] uppercase">Cancel</button>
                    </div>
                </form>
            </div>

            {{-- Limit Modal --}}
            <div id="limit-modal" class="hidden w-full max-w-sm bg-white rounded-[2rem] z-[70] shadow-2xl p-8 border border-slate-100 relative">
                <h3 class="text-base font-black text-slate-900 uppercase tracking-widest mb-8 text-center">Quota <span class="text-amber-500">Adjustment</span></h3>
                <form id="limit-form" method="POST" class="space-y-4">
                    @csrf
                    <div class="grid grid-cols-2 gap-4">
                        <div class="text-center">
                            <label class="text-[8px] font-black text-slate-400 uppercase block mb-1">Query Limit</label>
                            <input type="number" name="query_limit" id="query-limit-input" class="w-full px-3 py-4 bg-slate-50 border border-slate-200 rounded-xl font-black text-lg text-center focus:ring-2 focus:ring-indigo-500 outline-none">
                        </div>
                        <div class="text-center">
                            <label class="text-[8px] font-black text-slate-400 uppercase block mb-1">Lead Limit</label>
                            <input type="number" name="profile_limit" id="profile-limit-input" class="w-full px-3 py-4 bg-slate-50 border border-slate-200 rounded-xl font-black text-lg text-center focus:ring-2 focus:ring-amber-500 outline-none">
                        </div>
                    </div>
                    <button type="submit" class="w-full bg-slate-950 text-white py-3.5 rounded-xl font-black uppercase text-[10px] tracking-widest shadow-lg hover:bg-amber-600 transition-all mt-4">Apply Quotas</button>
                    <button type="button" onclick="closeModals()" class="w-full py-2 text-slate-400 font-bold text-[10px] uppercase">Discard</button>
                </form>
            </div>

            {{-- Billing Modal --}}
            <div id="payment-modal" x-data="{ mode: 'extend' }" style="display: none;" class="w-full max-w-md bg-white rounded-[2rem] z-[70] shadow-2xl p-10 border border-slate-100 relative">
                <div class="flex justify-between items-center mb-8">
                    <h3 class="text-base font-black text-slate-900 uppercase tracking-widest italic">Billing <span class="text-emerald-600">Action</span></h3>
                    <div class="flex bg-slate-100 p-1 rounded-xl">
                        <button type="button" @click="mode = 'extend'" :class="mode === 'extend' ? 'bg-white shadow-sm text-indigo-600' : 'text-slate-500'" class="px-3 py-1.5 rounded-lg text-[9px] font-black uppercase tracking-widest transition-all">Renew</button>
                        <button type="button" @click="mode = 'edit'" :class="mode === 'edit' ? 'bg-white shadow-sm text-amber-600' : 'text-slate-500'" class="px-3 py-1.5 rounded-lg text-[9px] font-black uppercase tracking-widest transition-all">Edit</button>
                    </div>
                </div>

                <form id="payment-form" method="POST" class="space-y-6">
                    @csrf
                    <input type="hidden" name="update_mode" :value="mode">
                    <p id="payment-email" class="text-[10px] text-indigo-600 font-black uppercase text-center bg-indigo-50 py-2 rounded-lg"></p>
                    
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="text-[8px] font-black text-slate-400 uppercase mb-1 block">Amount (USD)</label>
                            <input type="number" name="amount" step="0.01" value="0.00" class="w-full p-3 bg-slate-50 border border-slate-200 rounded-xl text-xs font-bold focus:ring-2 focus:ring-emerald-500 outline-none">
                        </div>
                        <div>
                            <label class="text-[8px] font-black text-slate-400 uppercase mb-1 block">Gateway</label>
                            <select name="method" class="w-full p-3 bg-slate-50 border border-slate-200 rounded-xl text-[10px] font-black uppercase outline-none">
                                <option value="PayPal">PayPal</option>
                                <option value="Stripe">Stripe</option>
                                <option value="Bank Transfer">Bank Transfer</option>
                            </select>
                        </div>
                    </div>

                    <div x-show="mode === 'extend'" class="animate-fadeIn">
                        <label class="text-[8px] font-black text-slate-400 uppercase mb-1 block">Extension Period</label>
                        <select name="duration_extended" class="w-full p-3 bg-indigo-50/50 border border-indigo-100 rounded-xl text-[10px] font-black text-indigo-600 outline-none">
                            <option value="">None (Payment Only)</option>
                            <option value="1 Month">+ 30 Days Access</option>
                            <option value="3 Months">+ 90 Days Access</option>
                            <option value="1 Year">+ 365 Days Access</option>
                        </select>
                    </div>

                    <div x-show="mode === 'edit'" class="animate-fadeIn">
                        <label class="text-[8px] font-black text-amber-600 uppercase mb-1 block">Override Expiry</label>
                        <input type="date" name="manual_expiry" class="w-full p-3 bg-amber-50/50 border border-amber-100 rounded-xl text-xs font-bold text-amber-600 outline-none">
                    </div>

                    <div class="pt-4 flex flex-col gap-2">
                        <button type="submit" class="w-full bg-slate-950 text-white py-4 rounded-xl font-black uppercase text-[10px] tracking-[0.2em] shadow-lg hover:bg-emerald-600 transition-all">Commit Transaction</button>
                        <button type="button" onclick="closeModals()" class="w-full py-2 text-slate-400 font-bold text-[10px] uppercase">Abort</button>
                    </div>
                </form>
            </div>

            {{-- Delete Modal --}}
            <div id="delete-modal" class="hidden w-full max-w-xs bg-white rounded-[2rem] z-[70] shadow-2xl p-8 border border-slate-100 text-center relative">
                <h3 class="text-sm font-black text-slate-900 uppercase tracking-widest mb-2">Delete User?</h3>
                <p id="delete-email" class="text-[10px] text-slate-400 font-bold mb-8"></p>
                <form id="delete-form" method="POST" class="space-y-3">
                    @csrf @method('DELETE')
                    <button type="submit" class="w-full bg-rose-500 text-white py-3 rounded-xl font-black uppercase text-[10px] tracking-widest shadow-xl">Confirm Purge</button>
                    <button type="button" onclick="closeModals()" class="w-full text-slate-400 text-[10px] font-bold uppercase">Cancel</button>
                </form>
            </div>
        </div>
    </div>

    <script>
        const backdrop = document.getElementById('modal-backdrop');
        const modalIds = ['status-modal', 'limit-modal', 'payment-modal', 'delete-modal'];

        function showModal(id) {
            const el = document.getElementById(id);
            backdrop.classList.remove('hidden');
            el.classList.remove('hidden');
            el.style.display = 'block';
        }

        function closeModals() {
            backdrop.classList.add('hidden');
            modalIds.forEach(id => {
                const el = document.getElementById(id);
                if (el) {
                    el.classList.add('hidden');
                    el.style.display = 'none';
                }
            });
        }

        function openStatusModal(userId, email) {
            document.getElementById('status-email').innerText = email;
            document.getElementById('status-form').action = `/admin/users/${userId}/status`;
            showModal('status-modal');
        }

        function openLimitModal(userId, name, queryLimit, profileLimit) {
            document.getElementById('limit-form').action = `/admin/users/${userId}/limit`;
            document.getElementById('query-limit-input').value = queryLimit;
            document.getElementById('profile-limit-input').value = profileLimit;
            showModal('limit-modal');
        }

        function openPaymentModal(userId, email) {
            document.getElementById('payment-email').innerText = email;
            document.getElementById('payment-form').action = `/admin/users/${userId}/payment`;
            showModal('payment-modal');
        }

        function openDeleteModal(userId, email) {
            document.getElementById('delete-email').innerText = email;
            document.getElementById('delete-form').action = `/admin/users/${userId}`;
            showModal('delete-modal');
        }

        backdrop.addEventListener('click', (e) => {
            if (e.target === backdrop) {
                closeModals();
            }
        });

        function userManagement() {
            return {
                tableLoading: false,
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
                }
            }
        }
    </script>

    <style>
        .no-scrollbar::-webkit-scrollbar { display: none; }
        .no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
        @keyframes fadeInUp { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
        .animate-fadeInUp { animation: fadeInUp 0.4s ease-out; }
    </style>
</x-app-layout>
