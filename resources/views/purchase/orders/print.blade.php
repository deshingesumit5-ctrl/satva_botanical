<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Purchase Order - {{ $order->po_number }}</title>
    <style>
        body { font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; font-size: 12px; color: #333; line-height: 1.4; margin: 0; padding: 20px; }
        .header { border-bottom: 2px solid #0f9f6e; padding-bottom: 10px; margin-bottom: 20px; }
        .company-title { font-size: 20px; font-weight: bold; color: #0f9f6e; }
        .po-title { font-size: 18px; font-weight: bold; text-align: right; color: #1e293b; }
        .meta-table { width: 100%; margin-bottom: 20px; }
        .meta-table td { vertical-align: top; width: 50%; }
        .items-table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
        .items-table th { background: #f1f5f9; border: 1px solid #cbd5e1; padding: 8px; text-align: left; font-size: 11px; }
        .items-table td { border: 1px solid #cbd5e1; padding: 8px; font-size: 11px; }
        .total-box { float: right; width: 40%; margin-bottom: 30px; }
        .total-box table { width: 100%; border-collapse: collapse; }
        .total-box td { padding: 5px; }
        .signatures { clear: both; margin-top: 50px; width: 100%; }
        .signatures td { width: 50%; text-align: center; padding-top: 50px; border-top: 1px solid #cbd5e1; }
    </style>
</head>
<body>
    <div class="header">
        <table style="width: 100%;">
            <tr>
                <td>
                    <div class="company-title">{{ $company->company_name ?? 'SATTVA BOTANICAL PVT LTD' }}</div>
                    <div>{{ $company->address ?? 'Plot No. 12, Extraction Industrial Area' }}</div>
                    <div>{{ $company->city ?? 'Satara' }}, {{ $company->state ?? 'Maharashtra' }} - {{ $company->pincode ?? '415001' }}</div>
                    <div>GSTIN: {{ $company->gstin ?? '27ABCDE1234F1Z5' }} | Email: {{ $company->email ?? 'info@sattvabotanical.local' }}</div>
                </td>
                <td style="text-align: right;">
                    <div class="po-title">PURCHASE ORDER</div>
                    <div style="font-size: 14px; font-weight: bold; color: #0f9f6e;">{{ $order->po_number }}</div>
                    <div>Date: {{ $order->po_date ? \Carbon\Carbon::parse($order->po_date)->format('d-m-Y') : date('d-m-Y') }}</div>
                    <div>Status: {{ $order->status }}</div>
                </td>
            </tr>
        </table>
    </div>

    <table class="meta-table">
        <tr>
            <td>
                <strong>VENDOR / SUPPLIER:</strong><br>
                <strong>{{ $order->supplier->name ?? '-' }}</strong><br>
                {{ $order->supplier->address_line ?? '' }}<br>
                {{ $order->supplier->city ?? '' }}, {{ $order->supplier->state ?? '' }}<br>
                GSTIN: {{ $order->supplier->gstin ?? 'N/A' }} | PAN: {{ $order->supplier->pan ?? 'N/A' }}<br>
                Contact: {{ $order->supplier->contact_person ?? '' }} ({{ $order->supplier->mobile ?? '' }})
            </td>
            <td>
                <strong>DELIVERY SITE / PLANT:</strong><br>
                <strong>{{ $order->plant->name ?? '-' }}</strong><br>
                {{ $order->plant->address ?? '' }}<br>
                GSTIN: {{ $order->plant->gstin ?? '' }}<br><br>
                <strong>Expected Delivery:</strong> {{ $order->expected_delivery_date ? \Carbon\Carbon::parse($order->expected_delivery_date)->format('d-m-Y') : '-' }}<br>
                <strong>Payment Terms:</strong> {{ $order->payment_terms ?? 'Net 30 Days' }}
            </td>
        </tr>
    </table>

    <table class="items-table">
        <thead>
            <tr>
                <th width="30">#</th>
                <th>Material Description</th>
                <th width="60">Code</th>
                <th width="80" style="text-align: right;">Quantity</th>
                <th width="50">UOM</th>
                <th width="80" style="text-align: right;">Rate (₹)</th>
                <th width="60" style="text-align: right;">Tax %</th>
                <th width="100" style="text-align: right;">Total (₹)</th>
            </tr>
        </thead>
        <tbody>
            @foreach($order->items as $idx => $it)
            <tr>
                <td>{{ $idx + 1 }}</td>
                <td><strong>{{ $it->material->name ?? '-' }}</strong></td>
                <td>{{ $it->material->material_code ?? '-' }}</td>
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
        <table>
            <tr>
                <td style="text-align: right; font-weight: bold; font-size: 13px;">Grand Total (INR):</td>
                <td style="text-align: right; font-weight: bold; font-size: 14px; color: #0f9f6e;">₹{{ number_format($order->total_amount, 2) }}</td>
            </tr>
        </table>
    </div>

    <div class="signatures">
        <table style="width: 100%;">
            <tr>
                <td>Prepared By / Purchase Officer</td>
                <td>Authorized Signatory / Plant Head</td>
            </tr>
        </table>
    </div>
</body>
</html>