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
        // Auto-suspend expired users before loading the view
        $expiredUsers = User::where('role', '!=', 'admin')
            ->where('status', 'active')
            ->whereHas('userPlan', function ($query) {
                $query->whereNotNull('expiry_date')->where('expiry_date', '<', now());
            })->get();
            
        foreach ($expiredUsers as $expired) {
            $expired->update(['status' => 'suspended']);
        }

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

        $users = $query->with('userPlan')->orderBy('created_at', 'desc')->paginate(20)->withQueryString();

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

        // If admin manually activates an expired user, extend their plan by 1 month automatically
        if ($user->status === 'active' && $user->userPlan && $user->userPlan->expiry_date && now()->greaterThan($user->userPlan->expiry_date)) {
            $user->userPlan->update([
                'expiry_date' => now()->addMonth()
            ]);
            return back()->with('success', 'User activated and plan automatically extended by 1 month.');
        }

        return back()->with('success', "User status updated to {$user->status}.");
    }

    public function updatePlan(Request $request)
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'search_limit' => 'required|integer|min:0',
            'lead_limit' => 'required|integer|min:0',
            'expiry_date' => 'nullable|date',
            'payment_status' => 'required|string|in:paid,pending',
        ]);

        $updateData = [
            'search_limit' => $validated['search_limit'],
            'lead_limit' => $validated['lead_limit'],
        ];

        // Only update expiry_date if it was actually provided
        if (!empty($validated['expiry_date'])) {
            $updateData['expiry_date'] = $validated['expiry_date'];
        }

        \Illuminate\Support\Facades\DB::transaction(function () use ($validated, $updateData) {
            \App\Models\UserPlan::updateOrCreate(
                ['user_id' => $validated['user_id']],
                $updateData
            );

            if ($validated['payment_status'] === 'paid') {
                \App\Models\BillingHistory::create([
                    'user_id' => $validated['user_id'],
                    'amount' => null, // Or logic to calculate amount
                    'currency' => 'USD',
                    'description' => 'Manual Plan Upgrade',
                    'status' => 'paid',
                    'paid_at' => now(),
                ]);
            }
        });

        return back()->with('success', 'User plan updated successfully.');
    }

    public function updateStatus(Request $request, User $user)
    {
        $statusMap = [
            'Active (Paid)' => \App\Models\UserPlan::SECURITY_ACTIVE_PAID,
            'Inactive (Revoke Access)' => \App\Models\UserPlan::SECURITY_INACTIVE_REVOKED,
            'Past Due (Payment Failed)' => \App\Models\UserPlan::SECURITY_PAST_DUE,
        ];
        $securityStatus = $statusMap[$request->status] ?? \App\Models\UserPlan::SECURITY_ACTIVE_PAID;
        
        $user->userPlan()->updateOrCreate(
            ['user_id' => $user->id],
            ['security_status' => $securityStatus]
        );
        
        return back()->with('success', "Status updated to {$request->status}.");
    }

    public function updateLimit(Request $request, User $user)
    {
        $request->validate([
            'query_limit' => 'required|integer|min:0',
            'profile_limit' => 'required|integer|min:0',
        ]);
        
        $user->userPlan()->updateOrCreate(
            ['user_id' => $user->id],
            [
                'search_limit' => (int) $request->query_limit,
                'lead_limit' => (int) $request->profile_limit,
            ]
        );
        
        return back()->with('success', "Quotas for {$user->name} updated: {$request->query_limit} Queries / {$request->profile_limit} Leads.");
    }

    public function updatePayment(Request $request, User $user)
    {
        $request->validate([
            'amount'            => 'required|numeric|min:0',
            'method'            => 'required|string',
            'update_mode'       => 'required|string', 
            'duration_extended' => 'nullable|string',
            'manual_expiry'     => 'nullable|date',
        ]);

        $newExpiry = null;

        $newExpiry = null;

        if ($request->update_mode === 'extend') {
            if ($request->filled('duration_extended')) {
                $currentExpiry = ($user->userPlan && $user->userPlan->expiry_date && $user->userPlan->expiry_date->isFuture()) 
                    ? \Carbon\Carbon::parse($user->userPlan->expiry_date) 
                    : now();

                $newExpiry = match ($request->duration_extended) {
                    '1 Month'  => $currentExpiry->addMonth(),
                    '3 Months' => $currentExpiry->addMonths(3),
                    '1 Year'   => $currentExpiry->addYear(),
                    default    => null,
                };
            }
        } else {
            if ($request->manual_expiry) {
                $newExpiry = \Carbon\Carbon::parse($request->manual_expiry);
            }
        }

        $targetDateFormatted = $newExpiry ? $newExpiry->format('d M Y') : (($user->userPlan && $user->userPlan->expiry_date) ? $user->userPlan->expiry_date->format('d M Y') : 'N/A');
        
        if ($request->update_mode === 'extend') {
            $subNote = $request->filled('duration_extended') ? "+ {$request->duration_extended}" : "Payment Only";
        } else {
            $subNote = "Manual Edit";
        }
        
        $combinedNote = "{$targetDateFormatted}|{$subNote}";

        \App\Models\BillingHistory::create([
            'user_id'       => $user->id,
            'amount'        => $request->amount ?? 0.00,
            'currency'      => 'USD',
            'gateway'       => $request->method,
            'description'   => $request->update_mode === 'extend' ? 'Manual Plan Extension' : 'Plan Expiry Adjustment',
            'duration_note' => $combinedNote,
            'status'        => 'Paid',
            'paid_at'       => now(),
        ]);

        if ($newExpiry) {
            $user->userPlan()->updateOrCreate(
                ['user_id' => $user->id],
                [
                    'expiry_date'     => $newExpiry,
                    'security_status' => \App\Models\UserPlan::SECURITY_ACTIVE_PAID
                ]
            );
        }

        return back()->with('success', "Account validity updated to " . $targetDateFormatted);
    }
}

