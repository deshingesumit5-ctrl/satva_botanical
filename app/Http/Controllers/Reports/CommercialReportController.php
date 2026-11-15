<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use App\Models\PurchaseInvoice;
use App\Models\SalesInvoice;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CommercialReportController extends Controller
{
    public function index(Request $request)
    {
        $salesInvoices = SalesInvoice::with('customer')->latest()->take(25)->get();
        $purchaseInvoices = PurchaseInvoice::with('supplier')->latest()->take(25)->get();

        $totalSales = SalesInvoice::sum('total_amount');
        $totalSalesTax = SalesInvoice::sum(DB::raw('cgst_amount + sgst_amount + igst_amount'));
        $totalPurchases = PurchaseInvoice::sum('total_amount');
        $totalPurchaseTax = PurchaseInvoice::sum(DB::raw('cgst_amount + sgst_amount + igst_amount'));

        return view('reports.commercial', compact(
            'salesInvoices',
            'purchaseInvoices',
            'totalSales',
            'totalSalesTax',
            'totalPurchases',
            'totalPurchaseTax'
        ));
    }

    public function export(Request $request)
    {
        $invoices = SalesInvoice::with('customer')->get();

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="sales_gst_summary_' . date('Y-m-d') . '.csv"',
        ];

        $callback = function () use ($invoices) {
            $file = fopen('php://output', 'w');
            fputcsv($file, ['Invoice Number', 'Customer', 'Date', 'Taxable Value', 'GST Amount', 'Total Amount', 'Status']);
            foreach ($invoices as $inv) {
                fputcsv($file, [
                    $inv->invoice_number,
                    $inv->customer ? $inv->customer->name : '',
                    $inv->invoice_date,
                    $inv->subtotal,
                    $inv->tax_amount,
                    $inv->grand_total,
                    $inv->status,
                ]);
            }
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
