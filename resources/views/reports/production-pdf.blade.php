<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Production Yield Report</title>
    <style>
        body { font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; font-size: 10px; color: #333; margin: 0; padding: 15px; }
        .header { border-bottom: 2px solid #0f9f6e; padding-bottom: 8px; margin-bottom: 12px; }
        .title { font-size: 16px; font-weight: bold; color: #0f9f6e; }
        .table { width: 100%; border-collapse: collapse; }
        .table th, .table td { border: 1px solid #cbd5e1; padding: 6px; text-align: left; }
        .table th { background: #f8fafc; font-weight: bold; }
    </style>
</head>
<body>
    <div class="header">
        <table style="width: 100%;">
            <tr>
                <td>
                    <div class="title">{{ $company->company_name ?? 'SATTVA BOTANICAL PVT LTD' }}</div>
                    <div>Manufacturing Production Yield & Audit Report</div>
                </td>
                <td style="text-align: right;">Generated: {{ date('d-m-Y H:i') }}</td>
            </tr>
        </table>
    </div>

    <table class="table">
        <thead>
            <tr>
                <th>#</th>
                <th>Batch Number</th>
                <th>Product Description</th>
                <th>Planned Output</th>
                <th>Actual Output</th>
                <th>Yield %</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            @foreach($batches as $idx => $b)
            @php
                $planned = (float)($b->planned_quantity ?? $b->batch_size);
                $actual = (float)($b->actual_quantity ?? 0);
                $yield = $planned > 0 ? round(($actual / $planned) * 100, 1) : 0;
            @endphp
            <tr>
                <td>{{ $idx + 1 }}</td>
                <td><strong>{{ $b->batch_no ?? $b->batch_number }}</strong></td>
                <td>{{ $b->product->name ?? '-' }}</td>
                <td>{{ $planned }}</td>
                <td>{{ $actual }}</td>
                <td>{{ $yield }}%</td>
                <td>{{ $b->status }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>