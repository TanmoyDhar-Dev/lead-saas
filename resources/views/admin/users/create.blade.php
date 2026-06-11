<x-app-layout>
    <x-slot name="header">
        Create User
    </x-slot>

    <x-slot name="subheader">
        Administration • New Account Provisioning
    </x-slot>

    <div class="max-w-3xl mx-auto space-y-6">
        <div class="bg-white rounded-3xl shadow-sm border border-slate-100 overflow-hidden">
            <div class="p-6 border-b border-slate-100">
                <h3 class="font-bold text-slate-800">Account Details</h3>
                <p class="text-xs text-slate-400 mt-1">Create a new user account with role and usage limits.</p>
            </div>

            <form method="POST" action="{{ route('admin.users.store') }}" class="p-6 space-y-6">
                @csrf

                {{-- Name & Email --}}
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="space-y-1">
                        <label for="name" class="text-[10px] font-bold text-slate-400 uppercase tracking-widest px-1">Full Name *</label>
                        <input type="text" name="name" id="name" value="{{ old('name') }}" required autofocus
                               class="w-full bg-slate-50 border-slate-200 rounded-2xl text-sm focus:ring-brand-blue focus:border-brand-blue py-3 px-4">
                        @error('name') <p class="text-red-500 text-[10px] font-bold mt-1 px-1">{{ $message }}</p> @enderror
                    </div>
                    <div class="space-y-1">
                        <label for="email" class="text-[10px] font-bold text-slate-400 uppercase tracking-widest px-1">Email Address *</label>
                        <input type="email" name="email" id="email" value="{{ old('email') }}" required
                               class="w-full bg-slate-50 border-slate-200 rounded-2xl text-sm focus:ring-brand-blue focus:border-brand-blue py-3 px-4">
                        @error('email') <p class="text-red-500 text-[10px] font-bold mt-1 px-1">{{ $message }}</p> @enderror
                    </div>
                </div>

                {{-- Password --}}
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="space-y-1">
                        <label for="password" class="text-[10px] font-bold text-slate-400 uppercase tracking-widest px-1">Password *</label>
                        <input type="password" name="password" id="password" required
                               class="w-full bg-slate-50 border-slate-200 rounded-2xl text-sm focus:ring-brand-blue focus:border-brand-blue py-3 px-4">
                        @error('password') <p class="text-red-500 text-[10px] font-bold mt-1 px-1">{{ $message }}</p> @enderror
                    </div>
                    <div class="space-y-1">
                        <label for="password_confirmation" class="text-[10px] font-bold text-slate-400 uppercase tracking-widest px-1">Confirm Password *</label>
                        <input type="password" name="password_confirmation" id="password_confirmation" required
                               class="w-full bg-slate-50 border-slate-200 rounded-2xl text-sm focus:ring-brand-blue focus:border-brand-blue py-3 px-4">
                    </div>
                </div>

                {{-- Role & Status --}}
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div class="space-y-1">
                        <label for="role" class="text-[10px] font-bold text-slate-400 uppercase tracking-widest px-1">Role *</label>
                        <select name="role" id="role" required class="w-full bg-slate-50 border-slate-200 rounded-2xl text-sm focus:ring-brand-blue focus:border-brand-blue py-3 px-4">
                            <option value="user" {{ old('role') === 'user' ? 'selected' : '' }}>User</option>
                            <option value="admin" {{ old('role') === 'admin' ? 'selected' : '' }}>Admin</option>
                        </select>
                        @error('role') <p class="text-red-500 text-[10px] font-bold mt-1 px-1">{{ $message }}</p> @enderror
                    </div>
                    <div class="space-y-1">
                        <label for="status" class="text-[10px] font-bold text-slate-400 uppercase tracking-widest px-1">Status *</label>
                        <select name="status" id="status" required class="w-full bg-slate-50 border-slate-200 rounded-2xl text-sm focus:ring-brand-blue focus:border-brand-blue py-3 px-4">
                            <option value="active" {{ old('status', 'active') === 'active' ? 'selected' : '' }}>Active</option>
                            <option value="inactive" {{ old('status') === 'inactive' ? 'selected' : '' }}>Inactive</option>
                            <option value="suspended" {{ old('status') === 'suspended' ? 'selected' : '' }}>Suspended</option>
                        </select>
                        @error('status') <p class="text-red-500 text-[10px] font-bold mt-1 px-1">{{ $message }}</p> @enderror
                    </div>
                </div>

                {{-- Usage Limits --}}
                <div class="border-t border-slate-100 pt-6">
                    <h4 class="font-bold text-slate-700 mb-1">Usage Limits</h4>
                    <p class="text-xs text-slate-400 mb-4">Leave blank for unlimited access.</p>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        @foreach(['lead_search_limit' => 'Search Limit', 'lead_export_limit' => 'Export Limit', 'lead_storage_limit' => 'Storage Limit', 'email_send_limit' => 'Email Limit'] as $field => $label)
                        <div class="space-y-1">
                            <label for="{{ $field }}" class="text-[10px] font-bold text-slate-400 uppercase tracking-widest px-1">{{ $label }}</label>
                            <input type="number" name="{{ $field }}" id="{{ $field }}" min="0" value="{{ old($field) }}"
                                   placeholder="∞"
                                   class="w-full bg-slate-50 border-slate-200 rounded-2xl text-sm focus:ring-brand-blue focus:border-brand-blue py-2.5 px-4">
                        </div>
                        @endforeach
                    </div>
                </div>

                {{-- Notes --}}
                <div class="space-y-1">
                    <label for="notes" class="text-[10px] font-bold text-slate-400 uppercase tracking-widest px-1">Internal Notes</label>
                    <textarea name="notes" id="notes" rows="3" class="w-full bg-slate-50 border-slate-200 rounded-2xl text-sm focus:ring-brand-blue focus:border-brand-blue py-3 px-4">{{ old('notes') }}</textarea>
                </div>

                {{-- Actions --}}
                <div class="flex items-center justify-between pt-4 border-t border-slate-100">
                    <a href="{{ route('admin.users.index') }}" class="text-sm text-slate-500 hover:text-slate-700 font-medium transition-colors">← Back to Users</a>
                    <button type="submit" class="bg-brand-blue hover:bg-blue-600 text-white font-bold py-3 px-8 rounded-2xl transition-all transform active:scale-95 shadow-lg shadow-blue-500/20">
                        CREATE USER
                    </button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>
