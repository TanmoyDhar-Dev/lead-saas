<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\BillingHistory;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;

class BillingController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();
        
        // Base Query
        $query = BillingHistory::with('user')->latest();

        if (!$user->is_admin) {
            // FOR USERS: Restrict to own records
            $query->where('user_id', $user->id);
        } else {
            // FOR ADMINS: Keep everything visible and allow filters
            if ($request->user_id) {
                $query->where('user_id', $request->user_id);
            }
            if ($request->status) {
                $query->where('status', $request->status);
            }
        }

        $payments = $query->paginate(15);
        
        // Recalculate stats based on the new filtered query
        $totalRevenue = $query->clone()->where(function($q) {
            $q->where('status', 'Paid')->orWhere('status', 'paid');
        })->sum('amount');
        
        $paidCount = $query->clone()->where(function($q) {
            $q->where('status', 'Paid')->orWhere('status', 'paid');
        })->count();
        
        $lastPaid = $query->clone()->where(function($q) {
            $q->where('status', 'Paid')->orWhere('status', 'paid');
        })->first();

        $users = $user->is_admin ? User::where('role', '!=', 'admin')->get() : collect();

        if ($request->ajax()) {
            return response()->json([
                'table' => view('billing.partials.table', compact('payments'))->render(),
                'totalRevenue' => '$' . number_format($totalRevenue, 2),
                'paidCount' => $paidCount,
                'lastPaid' => $lastPaid ? $lastPaid->created_at->format('M d, Y') : '—',
                'lastPaidSub' => $lastPaid ? '$' . number_format($lastPaid->amount, 2) . ' • ' . $lastPaid->gateway : '',
            ]);
        }

        return view('billing.index', compact('payments', 'users', 'totalRevenue', 'paidCount', 'lastPaid'));
    }

    public function downloadInvoice(BillingHistory $billingHistory)
    {
        $user = auth()->user();

        // SECURITY CHECK: If not an admin, they can ONLY download their own invoice
        if (!$user->is_admin && $billingHistory->user_id !== $user->id) {
            abort(403, 'Unauthorized access to this document.');
        }

        $billingHistory->load('user');

        $invoiceNo = 'INV-' . $billingHistory->created_at->format('Ymd') . '-' . str_pad((string) $billingHistory->id, 6, '0', STR_PAD_LEFT);

        return Pdf::loadView('admin.billing.invoice_pdf', [
            'payment'   => $billingHistory,
            'invoiceNo' => $invoiceNo,
            'company'   => [
                'name'  => 'eGSales AI',
                'email' => 'eGSalesai@egeneration.co',
            ],
        ])->setPaper('a4')->download($invoiceNo . '.pdf');
    }

    public function destroy(BillingHistory $billingHistory): RedirectResponse
    {
        // HARD SECURITY CHECK: Only Admins can delete history
        if (!auth()->user()->is_admin) {
            abort(403, 'Unauthorized. Only administrators can remove billing records.');
        }

        $billingHistory->delete();

        return back()->with('success', "Billing record successfully removed from system.");
    }
}
