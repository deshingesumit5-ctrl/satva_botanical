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
                  ->orWhere('payment_reference', 'like', "%{$search}%")
                  ->orWhereHas('customer', function ($cq) use ($search) {
                      $cq->where('name', 'like', "%{$search}%");
                  });
            });
        }

        $receipts = $query->latest()->paginate(10)->withQueryString();

        return view('sales.receipts.index', compact('receipts'));
    }

    public function create()
    {
        $customers = Customer::where('status', 'Active')->orderBy('name')->get();
        $invoices = SalesInvoice::whereIn('payment_status', ['Unpaid', 'Partially Paid'])->with('customer')->get();

        return view('sales.receipts.create', compact('customers', 'invoices'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'sales_invoice_id' => 'nullable|exists:sales_invoices,id',
            'receipt_date' => 'required|date',
            'amount' => 'required|numeric|min:0.01',
            'payment_mode' => 'required|in:Bank Transfer,Cheque,NEFT/RTGS,UPI,Cash',
            'payment_reference' => 'nullable|string|max:100',
            'remarks' => 'nullable|string',
        ]);

        $receiptNumber = $this->docService->generate('RECEIPT');

        $receipt = DB::transaction(function () use ($validated, $receiptNumber) {
            $receiptRecord = CustomerReceipt::create([
                'receipt_number' => $receiptNumber,
                'customer_id' => $validated['customer_id'],
                'sales_invoice_id' => $validated['sales_invoice_id'] ?? null,
                'receipt_date' => $validated['receipt_date'],
                'amount' => $validated['amount'],
                'payment_mode' => $validated['payment_mode'],
                'reference_number' => $validated['payment_reference'] ?? null,
                'remarks' => $validated['remarks'] ?? null,
                'created_by' => Auth::id(),
            ]);

            // Update sales invoice paid amount
            if (!empty($validated['sales_invoice_id'])) {
                $inv = SalesInvoice::find($validated['sales_invoice_id']);
                if ($inv) {
                    $inv->paid_amount += $validated['amount'];
                    $inv->balance_amount = max(0, (float)$inv->total_amount - (float)$inv->paid_amount);
                    if ($inv->paid_amount >= $inv->total_amount) {
                        $inv->payment_status = 'Paid';
                    } else {
                        $inv->payment_status = 'Partially Paid';
                    }
                    $inv->save();
                }
            }

            AuditService::log('accounts', $receiptRecord->receipt_number, 'customer_receipt', null, $receiptRecord->toArray(), 'Recorded customer receipt');
            return $receiptRecord;
        });

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
