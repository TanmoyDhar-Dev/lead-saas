<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class AdminUserController extends Controller
{
    public function index(Request $request)
    {
        $query = User::query();

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'ilike', "%{$search}%")
                  ->orWhere('email', 'ilike', "%{$search}%");
            });
        }

        if ($role = $request->input('role')) {
            $query->where('role', $role);
        }

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        $users = $query->orderBy('created_at', 'desc')->paginate(20)->withQueryString();

        if ($request->ajax()) {
            return view('admin.users.partials.table', compact('users'));
        }

        return view('admin.users.index', compact('users'));
    }

    public function create()
    {
        return view('admin.users.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'confirmed', Password::defaults()],
            'role' => ['required', 'in:admin,user'],
            'status' => ['required', 'in:active,inactive,suspended'],
            'lead_search_limit' => ['nullable', 'integer', 'min:0'],
            'lead_export_limit' => ['nullable', 'integer', 'min:0'],
            'lead_storage_limit' => ['nullable', 'integer', 'min:0'],
            'email_send_limit' => ['nullable', 'integer', 'min:0'],
            'notes' => ['nullable', 'string'],
        ]);

        $validated['password'] = Hash::make($validated['password']);
        $validated['created_by'] = auth()->id();
        $validated['email_verified_at'] = now();

        User::create($validated);

        return redirect()->route('admin.users.index')->with('success', 'User created successfully.');
    }

    public function edit(User $user)
    {
        return redirect()->route('admin.users.index');
    }

    public function update(Request $request, User $user)
    {
        if ($user->isAdmin()) {
            return redirect()->route('admin.users.index')->with('error', 'Admin users are protected and cannot be modified here.');
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email,'.$user->id],
            'password' => ['nullable', 'confirmed', Password::defaults()],
            'role' => ['required', 'in:admin,user'],
            'status' => ['required', 'in:active,inactive,suspended'],
            'lead_search_limit' => ['nullable', 'integer', 'min:0'],
            'lead_export_limit' => ['nullable', 'integer', 'min:0'],
            'lead_storage_limit' => ['nullable', 'integer', 'min:0'],
            'email_send_limit' => ['nullable', 'integer', 'min:0'],
            'notes' => ['nullable', 'string'],
        ]);

        if ($user->id === auth()->id() && ($validated['role'] !== 'admin' || $validated['status'] !== 'active')) {
            $adminCount = User::where('role', 'admin')->where('status', 'active')->count();
            if ($adminCount <= 1) {
                return back()->with('error', 'You cannot demote or deactivate yourself as you are the only active admin.');
            }
        }

        if (!empty($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        } else {
            unset($validated['password']);
        }

        $user->update($validated);

        return redirect()->route('admin.users.index')->with('success', 'User updated successfully.');
    }

    public function destroy(User $user)
    {
        if ($user->isAdmin()) {
            return back()->with('error', 'Admin users have permanent access and cannot be deleted.');
        }

        try {
            $user->delete();
            return redirect()->route('admin.users.index')->with('success', 'User deleted successfully.');
        } catch (\Exception $e) {
            $user->update(['status' => 'suspended']);
            return redirect()->route('admin.users.index')->with('error', 'Could not delete user because they have related records. User has been suspended instead.');
        }
    }

    public function toggleStatus(User $user)
    {
        if ($user->isAdmin()) {
            return back()->with('error', 'Admin status is permanent and cannot be toggled.');
        }

        $user->status = $user->status === 'active' ? 'suspended' : 'active';
        $user->save();

        return back()->with('success', "User status updated to {$user->status}.");
    }
}
