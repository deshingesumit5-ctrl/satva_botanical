<?php

namespace App\Http\Controllers\Sales;

use App\Http\Controllers\Controller;
use App\Models\PurchaseInvoice;
use App\Models\Supplier;
use App\Models\SupplierPayment;
use App\Services\AuditService;
use App\Services\DocumentNumberService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class SupplierPaymentController extends Controller
{
    protected DocumentNumberService $docService;

    public function __construct(DocumentNumberService $docService)
    {
        $this->docService = $docService;
    }

    public function index(Request $request)
    {
        $query = SupplierPayment::with(['supplier', 'purchaseInvoice', 'paidByUser']);

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('payment_number', 'like', "%{$search}%")
                  ->orWhere('reference_number', 'like', "%{$search}%")
                  ->orWhereHas('supplier', function ($sq) use ($search) {
                      $sq->where('name', 'like', "%{$search}%");
                  });
            });
        }

        $payments = $query->latest()->paginate(10)->withQueryString();

        return view('sales.payments.index', compact('payments'));
    }

    public function create(Request $request)
    {
        $suppliers = Supplier::where('status', 'Active')->orderBy('name')->get();
        $invoices = PurchaseInvoice::whereIn('payment_status', ['Unpaid', 'Partially Paid'])
            ->with('supplier')
            ->latest()
            ->get();
        $selectedInvoiceId = $request->input('invoice_id');

        return view('sales.payments.create', compact('suppliers', 'invoices', 'selectedInvoiceId'));
    }

    public function store(Request $request)
    {
        if (!$request->filled('reference_number') && $request->filled('payment_reference')) {
            $request->merge(['reference_number' => $request->input('payment_reference')]);
        }
        if ($request->input('payment_mode') === 'NEFT/RTGS') {
            $request->merge(['payment_mode' => 'Bank Transfer']);
        }

        $validated = $request->validate([
            'supplier_id' => 'required|exists:suppliers,id',
            'purchase_invoice_id' => 'required|exists:purchase_invoices,id',
            'payment_date' => 'required|date',
            'amount' => 'required|numeric|min:0.01',
            'payment_mode' => 'required|in:Bank Transfer,Cheque,Cash,UPI',
            'reference_number' => 'nullable|string|max:50',
            'remarks' => 'nullable|string',
        ]);

        $paymentNumber = $this->docService->generate('PAYMENT');

        $payment = DB::transaction(function () use ($validated, $paymentNumber) {
            $inv = PurchaseInvoice::lockForUpdate()->findOrFail($validated['purchase_invoice_id']);

            $paymentRecord = SupplierPayment::create([
                'payment_number' => $paymentNumber,
                'supplier_id' => $validated['supplier_id'],
                'purchase_invoice_id' => $inv->id,
                'payment_date' => $validated['payment_date'],
                'amount' => $validated['amount'],
                'payment_mode' => $validated['payment_mode'],
                'reference_number' => $validated['reference_number'] ?? null,
                'remarks' => $validated['remarks'] ?? null,
                'created_by' => Auth::id(),
            ]);

            $inv->paid_amount = (float) $inv->paid_amount + (float) $validated['amount'];
            $inv->balance_amount = max(0, (float) $inv->total_amount - (float) $inv->paid_amount);
            $inv->payment_status = ($inv->paid_amount >= (float) $inv->total_amount) ? 'Paid' : 'Partially Paid';
            $inv->save();

            AuditService::log('accounts', $paymentRecord->payment_number, 'supplier_payment', null, $paymentRecord->toArray(), 'Recorded vendor payment');
            return $paymentRecord;
        });

        if ($request->expectsJson()) {
            return response()->json([
                'message' => "Payment {$payment->payment_number} recorded.",
                'redirect' => route('sales.payments.index'),
            ]);
        }

        return redirect()->route('sales.payments.index')
            ->with('success', "Payment {$payment->payment_number} recorded.");
    }

    public function show($id = null)
    {
        return redirect()->back();
    }

    public function edit($id = null)
    {
        return redirect()->back();
    }
}
