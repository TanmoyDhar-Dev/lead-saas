@php
    $userPayloadFields = [
        'id', 'name', 'email', 'role', 'status',
        'lead_search_limit', 'lead_export_limit', 'lead_storage_limit',
        'email_send_limit', 'notes',
    ];
@endphp
<div class="p-0 overflow-x-auto">
    <table class="w-full text-left border-collapse">
        <thead>
            <tr class="bg-slate-50/50 border-b border-slate-100">
                <th class="px-6 py-5 text-[10px] font-bold text-slate-400 uppercase tracking-widest">User Information</th>
                <th class="px-6 py-5 text-[10px] font-bold text-slate-400 uppercase tracking-widest">Role & Status</th>
                <th class="px-6 py-5 text-[10px] font-bold text-slate-400 uppercase tracking-widest text-center">Limits (Search / Export / Storage / Email)</th>
                <th class="px-6 py-5 text-[10px] font-bold text-slate-400 uppercase tracking-widest">Joined</th>
                <th class="px-6 py-5 text-[10px] font-bold text-slate-400 uppercase tracking-widest text-right">Actions</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
            @forelse ($users as $user)
            <tr class="hover:bg-slate-50 transition-colors group">
                <td class="px-6 py-4">
                    <div class="flex items-center">
                        <div class="w-10 h-10 rounded-xl bg-slate-100 flex items-center justify-center text-slate-500 font-bold text-sm mr-4 group-hover:bg-brand-blue group-hover:text-white transition-all">
                            {{ strtoupper(substr($user->name, 0, 1)) }}
                        </div>
                        <div>
                            <div class="text-sm font-bold text-slate-800">{{ $user->name }}</div>
                            <div class="text-[10px] text-slate-400 font-medium">{{ $user->email }}</div>
                        </div>
                    </div>
                </td>
                <td class="px-6 py-4">
                    <div class="flex flex-col space-y-1">
                        <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold w-fit {{ $user->role === 'admin' ? 'bg-purple-50 text-purple-700' : 'bg-blue-50 text-blue-700' }}">
                            {{ strtoupper($user->role) }}
                        </span>
                        <span @class([
                            'inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold w-fit',
                            'bg-emerald-50 text-emerald-700' => $user->status === 'active',
                            'bg-amber-50 text-amber-700' => $user->status === 'inactive',
                            'bg-red-50 text-red-700' => ! in_array($user->status, ['active', 'inactive'], true),
                        ])>
                            {{ strtoupper($user->status) }}
                        </span>
                    </div>
                </td>
                <td class="px-6 py-4 text-center">
                    @if($user->role !== 'admin')
                        <button @click="openEditLimits(@js($user->only($userPayloadFields)))" class="group/limit">
                            <div class="text-[10px] font-mono text-slate-500 bg-slate-50 px-3 py-1 rounded-lg border border-slate-100 inline-block group-hover/limit:bg-brand-blue group-hover/limit:text-white transition-colors cursor-pointer">
                                {{ $user->lead_search_limit ?? '∞' }} / {{ $user->lead_export_limit ?? '∞' }} / {{ $user->lead_storage_limit ?? '∞' }} / {{ $user->email_send_limit ?? '∞' }}
                            </div>
                        </button>
                    @else
                        <div class="text-[10px] font-bold text-brand-blue bg-blue-50 px-3 py-1 rounded-lg border border-blue-100 inline-block uppercase">
                            UNLIMITED ACCESS
                        </div>
                    @endif
                </td>
                <td class="px-6 py-4 text-xs text-slate-500 font-medium">
                    {{ $user->created_at->format('M d, Y') }}
                </td>
                <td class="px-6 py-4 text-right">
                    <div class="flex justify-end items-center space-x-2">
                        @if($user->role !== 'admin')
                            <button @click="openEditProfile(@js($user->only($userPayloadFields)))" class="p-2 text-slate-400 hover:text-brand-blue transition-colors" title="Edit Profile">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                            </button>
                            
                            <form action="{{ route('admin.users.toggle-status', $user) }}" method="POST" class="inline">
                                @csrf
                                <button type="submit" class="p-2 {{ $user->status === 'active' ? 'text-slate-400 hover:text-amber-600' : 'text-slate-400 hover:text-emerald-600' }} transition-colors" title="{{ $user->status === 'active' ? 'Suspend' : 'Activate' }}">
                                    @if($user->status === 'active')
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"></path></svg>
                                    @else
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path></svg>
                                    @endif
                                </button>
                            </form>

                            <form action="{{ route('admin.users.destroy', $user) }}" method="POST" class="inline" onsubmit="return confirm('Are you sure you want to delete this user?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="p-2 text-slate-400 hover:text-red-600 transition-colors" title="Delete User">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                </button>
                            </form>
                        @else
                            <span class="text-[10px] font-bold text-slate-300 italic uppercase">System Protected</span>
                        @endif
                    </div>
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="5" class="px-6 py-12 text-center text-slate-400 text-sm">No users found matching your criteria.</td>
            </tr>
            @endforelse
        </tbody>
    </table>
</div>

@if($users->hasPages())
<div class="px-6 py-6 border-t border-slate-100 bg-slate-50/30 ajax-pagination">
    {{ $users->links('vendor.pagination.custom-tailwind') }}
</div>
@endif
