<?php

namespace App\Http\Controllers\Sales;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\CustomerReceipt;
use App\Models\SalesInvoice;
use App\Services\AuditService;
use App\Services\DocumentNumberService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class CustomerReceiptController extends Controller
{
    protected DocumentNumberService $docService;

    public function __construct(DocumentNumberService $docService)
    {
        $this->docService = $docService;
    }

    public function index(Request $request)
    {
        $query = CustomerReceipt::with(['customer', 'salesInvoice', 'receivedByUser']);

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('receipt_number', 'like', "%{$search}%")
                  ->orWhere('reference_number', 'like', "%{$search}%")
                  ->orWhereHas('customer', function ($cq) use ($search) {
                      $cq->where('name', 'like', "%{$search}%");
                  });
            });
        }

        $receipts = $query->latest()->paginate(10)->withQueryString();

        return view('sales.receipts.index', compact('receipts'));
    }

    public function create(Request $request)
    {
        $customers = Customer::where('status', 'Active')->orderBy('name')->get();
        $invoices = SalesInvoice::whereIn('payment_status', ['Unpaid', 'Partially Paid'])
            ->with('customer')
            ->latest()
            ->get();
        $selectedInvoiceId = $request->input('invoice_id');

        return view('sales.receipts.create', compact('customers', 'invoices', 'selectedInvoiceId'));
    }

    public function store(Request $request)
    {
        if (!$request->filled('reference_number') && $request->filled('payment_reference')) {
            $request->merge(['reference_number' => $request->input('payment_reference')]);
        }

        $validated = $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'sales_invoice_id' => 'required|exists:sales_invoices,id',
            'receipt_date' => 'required|date',
            'amount' => 'required|numeric|min:0.01',
            'payment_mode' => 'required|in:Bank Transfer,Cheque,Cash,UPI',
            'reference_number' => 'nullable|string|max:50',
            'remarks' => 'nullable|string',
        ]);

        $receiptNumber = $this->docService->generate('RECEIPT');

        $receipt = DB::transaction(function () use ($validated, $receiptNumber) {
            $inv = SalesInvoice::lockForUpdate()->findOrFail($validated['sales_invoice_id']);

            $receiptRecord = CustomerReceipt::create([
                'receipt_number' => $receiptNumber,
                'customer_id' => $validated['customer_id'],
                'sales_invoice_id' => $inv->id,
                'receipt_date' => $validated['receipt_date'],
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

            AuditService::log('accounts', $receiptRecord->receipt_number, 'customer_receipt', null, $receiptRecord->toArray(), 'Recorded customer receipt');
            return $receiptRecord;
        });

        if ($request->expectsJson()) {
            return response()->json([
                'message' => "Receipt {$receipt->receipt_number} recorded.",
                'redirect' => route('sales.receipts.index'),
            ]);
        }

        return redirect()->route('sales.receipts.index')
            ->with('success', "Receipt {$receipt->receipt_number} recorded.");
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
