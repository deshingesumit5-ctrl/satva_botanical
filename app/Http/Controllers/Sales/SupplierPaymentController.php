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
                  ->orWhere('payment_reference', 'like', "%{$search}%")
                  ->orWhereHas('supplier', function ($sq) use ($search) {
                      $sq->where('name', 'like', "%{$search}%");
                  });
            });
        }

        $payments = $query->latest()->paginate(10)->withQueryString();

        return view('sales.payments.index', compact('payments'));
    }

    public function create()
    {
        $suppliers = Supplier::where('status', 'Active')->orderBy('name')->get();
        $invoices = PurchaseInvoice::whereIn('payment_status', ['Unpaid', 'Partially Paid'])->with('supplier')->get();

        return view('sales.payments.create', compact('suppliers', 'invoices'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'supplier_id' => 'required|exists:suppliers,id',
            'purchase_invoice_id' => 'nullable|exists:purchase_invoices,id',
            'payment_date' => 'required|date',
            'amount' => 'required|numeric|min:0.01',
            'payment_mode' => 'required|in:Bank Transfer,Cheque,NEFT/RTGS,UPI,Cash',
            'payment_reference' => 'nullable|string|max:100',
            'remarks' => 'nullable|string',
        ]);

        $paymentNumber = $this->docService->generate('PAYMENT');

        $payment = DB::transaction(function () use ($validated, $paymentNumber) {
            $paymentRecord = SupplierPayment::create([
                'payment_number' => $paymentNumber,
                'supplier_id' => $validated['supplier_id'],
                'purchase_invoice_id' => $validated['purchase_invoice_id'] ?? null,
                'payment_date' => $validated['payment_date'],
                'amount' => $validated['amount'],
                'payment_mode' => $validated['payment_mode'],
                'reference_number' => $validated['payment_reference'] ?? null,
                'remarks' => $validated['remarks'] ?? null,
                'created_by' => Auth::id(),
            ]);

            // Update purchase invoice paid amount
            if (!empty($validated['purchase_invoice_id'])) {
                $inv = PurchaseInvoice::find($validated['purchase_invoice_id']);
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

            AuditService::log('accounts', $paymentRecord->payment_number, 'supplier_payment', null, $paymentRecord->toArray(), 'Recorded vendor payment');
            return $paymentRecord;
        });

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
