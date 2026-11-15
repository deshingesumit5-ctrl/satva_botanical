<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Tax Invoice - {{ $invoice->invoice_number }}</title>
    <style>
        body { font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; font-size: 11px; color: #333; line-height: 1.4; margin: 0; padding: 20px; }
        .header { border-bottom: 2px solid #0f9f6e; padding-bottom: 10px; margin-bottom: 20px; }
        .company-title { font-size: 18px; font-weight: bold; color: #0f9f6e; }
        .inv-title { font-size: 16px; font-weight: bold; text-align: right; }
        .meta-table { width: 100%; margin-bottom: 20px; }
        .meta-table td { width: 50%; vertical-align: top; font-size: 11px; }
        .items-table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        .items-table th, .items-table td { border: 1px solid #cbd5e1; padding: 7px; text-align: left; font-size: 10px; }
        .items-table th { background: #f8fafc; font-weight: bold; }
        .total-box { float: right; width: 40%; margin-bottom: 30px; }
        .signatures { clear: both; margin-top: 50px; width: 100%; }
        .signatures td { width: 50%; text-align: center; padding-top: 40px; border-top: 1px solid #cbd5e1; font-size: 10px; }
    </style>
</head>
<body>
    <div class="header">
        <table style="width: 100%;">
            <tr>
                <td>
                    <div class="company-title">{{ $company->company_name ?? 'SATTVA BOTANICAL PVT LTD' }}</div>
                    <div>{{ $company->address ?? 'Plot No. 12, Extraction Industrial Area' }}</div>
                    <div>GSTIN: {{ $company->gstin ?? '27ABCDE1234F1Z5' }} | PAN: {{ $company->pan ?? 'ABCDE1234F' }}</div>
                </td>
                <td style="text-align: right;">
                    <div class="inv-title">TAX INVOICE</div>
                    <div style="font-weight: bold; color: #0f9f6e; font-size: 13px;">{{ $invoice->invoice_number }}</div>
                    <div>Date: {{ $invoice->invoice_date ? \Carbon\Carbon::parse($invoice->invoice_date)->format('d-m-Y') : date('d-m-Y') }}</div>
                    <div>Due Date: {{ $invoice->due_date ? \Carbon\Carbon::parse($invoice->due_date)->format('d-m-Y') : '-' }}</div>
                </td>
            </tr>
        </table>
    </div>

    <table class="meta-table">
        <tr>
            <td>
                <strong>BILLED TO:</strong><br>
                <strong>{{ $invoice->customer->name ?? '-' }}</strong><br>
                {{ $invoice->customer->billing_address ?? '' }}<br>
                {{ $invoice->customer->city ?? '' }}, {{ $invoice->customer->state ?? '' }}<br>
                GSTIN: {{ $invoice->customer->gstin ?? 'N/A' }} | PAN: {{ $invoice->customer->pan ?? 'N/A' }}
            </td>
            <td>
                <strong>DISPATCH DETAILS:</strong><br>
                Challan Ref: {{ $invoice->dispatch->dispatch_number ?? 'Direct' }}<br>
                Payment Terms: {{ $invoice->payment_terms ?? 'Net 30 Days' }}
            </td>
        </tr>
    </table>

    <table class="items-table">
        <thead>
            <tr>
                <th width="30">#</th>
                <th>Item Description</th>
                <th width="80" style="text-align: right;">Quantity</th>
                <th width="50">UOM</th>
                <th width="80" style="text-align: right;">Rate (₹)</th>
                <th width="60" style="text-align: right;">Tax %</th>
                <th width="100" style="text-align: right;">Amount (₹)</th>
            </tr>
        </thead>
        <tbody>
            @foreach($invoice->items as $idx => $it)
            <tr>
                <td>{{ $idx + 1 }}</td>
                <td><strong>{{ $it->product->name ?? '-' }}</strong></td>
                <td style="text-align: right;">{{ number_format($it->quantity, 3) }}</td>
                <td>{{ $it->uom->symbol ?? '' }}</td>
                <td style="text-align: right;">{{ number_format($it->unit_price, 2) }}</td>
                <td style="text-align: right;">{{ number_format($it->tax_percent, 1) }}%</td>
                <td style="text-align: right;">{{ number_format($it->total_amount, 2) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="total-box">
        <table style="width: 100%;">
            <tr>
                <td style="text-align: right; font-weight: bold; font-size: 13px;">Grand Total (INR):</td>
                <td style="text-align: right; font-weight: bold; font-size: 14px; color: #0f9f6e;">₹{{ number_format($invoice->total_amount, 2) }}</td>
            </tr>
        </table>
    </div>

    <div class="signatures">
        <table style="width: 100%;">
            <tr>
                <td>Customer Acceptance</td>
                <td>For {{ $company->company_name ?? 'Sattva Botanical Pvt Ltd' }}</td>
            </tr>
        </table>
    </div>
</body>
</html>