<?php

namespace App\Http\Controllers\Sales;

use App\Http\Controllers\Controller;
use App\Models\Grn;
use App\Models\PurchaseInvoice;
use App\Models\PurchaseOrder;
use App\Models\Supplier;
use App\Services\AuditService;
use App\Services\DocumentNumberService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PurchaseInvoiceController extends Controller
{
    protected DocumentNumberService $docService;

    public function __construct(DocumentNumberService $docService)
    {
        $this->docService = $docService;
    }

    public function index(Request $request)
    {
        $query = PurchaseInvoice::with(['supplier', 'grn', 'purchaseOrder']);

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('bill_number', 'like', "%{$search}%")
                  ->orWhere('supplier_invoice_number', 'like', "%{$search}%")
                  ->orWhereHas('supplier', function ($sq) use ($search) {
                      $sq->where('name', 'like', "%{$search}%");
                  });
            });
        }

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        $invoices = $query->latest()->paginate(10)->withQueryString();

        return view('sales.purchase-invoices.index', compact('invoices'));
    }

    public function create()
    {
        $suppliers = Supplier::where('status', 'Active')->orderBy('name')->get();
        $grns = Grn::where('status', 'Completed')->with('supplier')->get();
        $orders = PurchaseOrder::where('status', 'Approved')->with('supplier')->get();

        return view('sales.purchase-invoices.create', compact('suppliers', 'grns', 'orders'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'supplier_id' => 'required|exists:suppliers,id',
            'supplier_invoice_number' => 'required|string|max:50',
            'grn_id' => 'nullable|exists:grns,id',
            'purchase_order_id' => 'nullable|exists:purchase_orders,id',
            'bill_date' => 'required|date',
            'due_date' => 'nullable|date|after_or_equal:bill_date',
            'subtotal' => 'required|numeric|min:0',
            'tax_amount' => 'nullable|numeric|min:0',
            'grand_total' => 'required|numeric|min:0.01',
            'remarks' => 'nullable|string',
        ]);

        $billNumber = $this->docService->generate('PURCHASE_INV');

        $invoice = PurchaseInvoice::create([
            'bill_number' => $billNumber,
            'supplier_id' => $validated['supplier_id'],
            'supplier_invoice_number' => $validated['supplier_invoice_number'],
            'grn_id' => $validated['grn_id'] ?? null,
            'purchase_order_id' => $validated['purchase_order_id'] ?? null,
            'bill_date' => $validated['bill_date'],
            'due_date' => $validated['due_date'] ?? null,
            'subtotal' => $validated['subtotal'],
            'tax_amount' => $validated['tax_amount'] ?? 0,
            'grand_total' => $validated['grand_total'],
            'paid_amount' => 0,
            'status' => 'Unpaid',
            'remarks' => $validated['remarks'] ?? null,
            'created_by' => Auth::id(),
        ]);

        AuditService::log('accounts', $invoice->bill_number, 'create_purchase_invoice', null, $invoice->toArray(), 'Recorded vendor purchase invoice');

        return redirect()->route('sales.purchase-invoices.index')
            ->with('success', "Purchase Invoice {$invoice->bill_number} booked.");
    }

    public function show(PurchaseInvoice $purchaseInvoice)
    {
        $purchaseInvoice->load(['supplier', 'grn', 'purchaseOrder']);
        return view('sales.purchase-invoices.show', compact('purchaseInvoice'));
    }

    public function edit($id = null)
    {
        return redirect()->back();
    }
}
