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
                $q->where('internal_invoice_number', 'like', "%{$search}%")
                  ->orWhere('supplier_invoice_number', 'like', "%{$search}%")
                  ->orWhereHas('supplier', function ($sq) use ($search) {
                      $sq->where('name', 'like', "%{$search}%");
                  });
            });
        }

        if ($status = $request->input('status')) {
            $query->where('payment_status', $status);
        }

        $invoices = $query->latest()->paginate(10)->withQueryString();

        return view('sales.purchase-invoices.index', compact('invoices'));
    }

    public function create()
    {
        $suppliers = Supplier::where('status', 'Active')->orderBy('name')->get();
        $grns = Grn::where('status', 'Completed')->with('supplier')->latest()->get();
        $orders = PurchaseOrder::where('status', 'Approved')->with('supplier')->latest()->get();

        return view('sales.purchase-invoices.create', compact('suppliers', 'grns', 'orders'));
    }

    public function store(Request $request)
    {
        if (!$request->filled('supplier_invoice_number') && $request->filled('invoice_number')) {
            $request->merge(['supplier_invoice_number' => $request->input('invoice_number')]);
        }
        if (!$request->filled('invoice_date') && $request->filled('bill_date')) {
            $request->merge(['invoice_date' => $request->input('bill_date')]);
        }
        if (!$request->filled('taxable_amount') && $request->filled('subtotal')) {
            $request->merge(['taxable_amount' => $request->input('subtotal')]);
        }
        if (!$request->filled('total_amount') && $request->filled('grand_total')) {
            $request->merge(['total_amount' => $request->input('grand_total')]);
        }

        $validated = $request->validate([
            'supplier_id' => 'required|exists:suppliers,id',
            'supplier_invoice_number' => 'required|string|max:50',
            'grn_id' => 'nullable|exists:grns,id',
            'purchase_order_id' => 'nullable|exists:purchase_orders,id',
            'invoice_date' => 'required|date',
            'taxable_amount' => 'required|numeric|min:0',
            'other_charges' => 'nullable|numeric|min:0',
            'total_amount' => 'required|numeric|min:0.01|gte:taxable_amount',
        ]);

        $internalNumber = $this->docService->generate('PURCHASE_INV');
        $grn = !empty($validated['grn_id']) ? Grn::find($validated['grn_id']) : null;
        $poId = $validated['purchase_order_id'] ?? $grn?->purchase_order_id;

        $taxable = (float) $validated['taxable_amount'];
        $otherCharges = (float) ($validated['other_charges'] ?? 0);
        $totalAmount = (float) $validated['total_amount'];
        $taxDiff = max(0, round($totalAmount - $taxable - $otherCharges, 2));
        $cgst = round($taxDiff / 2, 2);
        $sgst = round($taxDiff - $cgst, 2);

        $invoice = PurchaseInvoice::create([
            'internal_invoice_number' => $internalNumber,
            'supplier_invoice_number' => $validated['supplier_invoice_number'],
            'supplier_id' => $validated['supplier_id'],
            'grn_id' => $validated['grn_id'] ?? null,
            'purchase_order_id' => $poId,
            'invoice_date' => $validated['invoice_date'],
            'taxable_amount' => $taxable,
            'discount_amount' => 0,
            'cgst_amount' => $cgst,
            'sgst_amount' => $sgst,
            'igst_amount' => 0,
            'other_charges' => $otherCharges,
            'total_amount' => $totalAmount,
            'paid_amount' => 0,
            'balance_amount' => $totalAmount,
            'payment_status' => 'Unpaid',
            'created_by' => Auth::id(),
        ]);

        AuditService::log('accounts', $invoice->internal_invoice_number, 'create_purchase_invoice', null, $invoice->toArray(), 'Recorded vendor purchase invoice');

        if ($request->expectsJson()) {
            return response()->json([
                'message' => "Purchase Invoice {$invoice->internal_invoice_number} booked.",
                'redirect' => route('sales.purchase-invoices.index'),
            ]);
        }

        return redirect()->route('sales.purchase-invoices.index')
            ->with('success', "Purchase Invoice {$invoice->internal_invoice_number} booked.");
    }

    public function show(PurchaseInvoice $purchaseInvoice)
    {
        $purchaseInvoice->load(['supplier', 'grn', 'purchaseOrder', 'payments']);
        return view('sales.purchase-invoices.show', compact('purchaseInvoice'));
    }

    public function edit($id = null)
    {
        return redirect()->back();
    }
}
