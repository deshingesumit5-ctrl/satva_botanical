<?php

namespace App\Http\Controllers\Sales;

use App\Http\Controllers\Controller;
use App\Models\CompanySetting;
use App\Models\Customer;
use App\Models\Dispatch;
use App\Models\Product;
use App\Models\SalesInvoice;
use App\Models\SalesInvoiceItem;
use App\Models\SalesOrder;
use App\Services\AuditService;
use App\Services\DocumentNumberService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class SalesInvoiceController extends Controller
{
    protected DocumentNumberService $docService;

    public function __construct(DocumentNumberService $docService)
    {
        $this->docService = $docService;
    }

    public function index(Request $request)
    {
        $query = SalesInvoice::with(['customer', 'dispatch', 'salesOrder', 'items.product']);

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('invoice_number', 'like', "%{$search}%")
                  ->orWhereHas('customer', function ($cq) use ($search) {
                      $cq->where('name', 'like', "%{$search}%");
                  });
            });
        }

        if ($status = $request->input('status')) {
            $query->where('payment_status', $status);
        }

        $invoices = $query->latest()->paginate(10)->withQueryString();

        return view('sales.invoices.index', compact('invoices'));
    }

    public function create(Request $request)
    {
        $customers = Customer::where('status', 'Active')->orderBy('name')->get();
        $dispatches = Dispatch::with(['customer', 'salesOrder.items.product'])->latest()->get();

        $selectedDispatch = null;
        if ($dispatchId = $request->input('dispatch_id')) {
            $selectedDispatch = Dispatch::with([
                'customer',
                'items.product.baseUom',
                'items.uom',
                'items.salesOrderItem',
                'salesOrder.items',
            ])->find($dispatchId);
        }

        return view('sales.invoices.create', compact('customers', 'dispatches', 'selectedDispatch'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'dispatch_id' => 'required|exists:dispatches,id',
            'sales_order_id' => 'nullable|exists:sales_orders,id',
            'invoice_date' => 'required|date',
            'due_date' => 'nullable|date|after_or_equal:invoice_date',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|numeric|min:0.001',
            'items.*.uom_id' => 'required|exists:uoms,id',
            'items.*.unit_price' => 'required|numeric|min:0',
            'items.*.tax_percent' => 'nullable|numeric|min:0|max:100',
        ]);

        $invoiceNumber = $this->docService->generate('SALES_INV');

        $invoice = DB::transaction(function () use ($validated, $invoiceNumber) {
            $dispatch = !empty($validated['dispatch_id']) ? Dispatch::find($validated['dispatch_id']) : null;
            $salesOrderId = $validated['sales_order_id'] ?? $dispatch?->sales_order_id;

            $taxableTotal = 0;
            $taxTotal = 0;

            foreach ($validated['items'] as $item) {
                $lineSub = round((float) $item['quantity'] * (float) $item['unit_price'], 2);
                $taxRate = (float) ($item['tax_percent'] ?? 0);
                $lineTax = round($lineSub * ($taxRate / 100), 2);

                $taxableTotal += $lineSub;
                $taxTotal += $lineTax;
            }

            $cgst = round($taxTotal / 2, 2);
            $sgst = round($taxTotal - $cgst, 2);
            $grandTotal = $taxableTotal + $taxTotal;

            $inv = SalesInvoice::create([
                'invoice_number' => $invoiceNumber,
                'customer_id' => $validated['customer_id'],
                'dispatch_id' => $validated['dispatch_id'] ?? null,
                'sales_order_id' => $salesOrderId,
                'invoice_date' => $validated['invoice_date'],
                'due_date' => $validated['due_date'] ?? null,
                'taxable_amount' => $taxableTotal,
                'discount_amount' => 0,
                'cgst_amount' => $cgst,
                'sgst_amount' => $sgst,
                'igst_amount' => 0,
                'total_amount' => $grandTotal,
                'paid_amount' => 0,
                'balance_amount' => $grandTotal,
                'payment_status' => 'Unpaid',
                'created_by' => Auth::id(),
            ]);

            foreach ($validated['items'] as $item) {
                $product = Product::findOrFail($item['product_id']);
                $qty = (float) $item['quantity'];
                $rate = (float) $item['unit_price'];
                $lineSub = round($qty * $rate, 2);
                $taxRate = (float) ($item['tax_percent'] ?? 0);
                $lineTax = round($lineSub * ($taxRate / 100), 2);

                SalesInvoiceItem::create([
                    'sales_invoice_id' => $inv->id,
                    'product_id' => $product->id,
                    'item_name' => $product->name,
                    'quantity' => $qty,
                    'uom_id' => $item['uom_id'],
                    'rate' => $rate,
                    'discount_percent' => 0,
                    'taxable_amount' => $lineSub,
                    'gst_rate' => $taxRate,
                    'gst_amount' => $lineTax,
                    'total_amount' => $lineSub + $lineTax,
                ]);
            }

            if ($dispatch) {
                $dispatch->invoice_reference = $inv->invoice_number;
                $dispatch->save();
            }

            if ($salesOrderId) {
                $order = SalesOrder::find($salesOrderId);
                if ($order && $order->status === 'Fully Dispatched') {
                    $order->status = 'Invoiced';
                    $order->save();
                }
            }

            AuditService::log('sales', $inv->invoice_number, 'create_sales_invoice', null, $inv->toArray(), 'Generated sales invoice');
            return $inv;
        });

        if ($request->expectsJson()) {
            return response()->json([
                'message' => "Sales Invoice {$invoice->invoice_number} generated.",
                'redirect' => route('sales.invoices.index'),
            ]);
        }

        return redirect()->route('sales.invoices.show', $invoice->id)
            ->with('success', "Sales Invoice {$invoice->invoice_number} generated.");
    }

    public function show(SalesInvoice $invoice)
    {
        $invoice->load(['customer', 'dispatch', 'salesOrder', 'items.product', 'items.uom']);
        return view('sales.invoices.show', compact('invoice'));
    }

    public function pdf(SalesInvoice $invoice)
    {
        $invoice->load(['customer', 'dispatch', 'salesOrder', 'items.product', 'items.uom']);
        $company = CompanySetting::first();

        $pdf = Pdf::loadView('sales.invoices.pdf', compact('invoice', 'company'));
        return $pdf->download("INVOICE_{$invoice->invoice_number}.pdf");
    }

    public function edit($id = null)
    {
        return redirect()->back();
    }
}
