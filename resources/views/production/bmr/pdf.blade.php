<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>BMR - {{ $batch->batch_no ?? $batch->batch_number }}</title>
    <style>
        body { font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; font-size: 11px; color: #333; line-height: 1.3; margin: 0; padding: 15px; }
        .header { border-bottom: 2px solid #0f9f6e; padding-bottom: 8px; margin-bottom: 15px; }
        .title { font-size: 16px; font-weight: bold; color: #0f9f6e; }
        .bmr-no { font-size: 14px; font-weight: bold; text-align: right; }
        .meta-table { width: 100%; border-collapse: collapse; margin-bottom: 15px; }
        .meta-table td { border: 1px solid #cbd5e1; padding: 6px; }
        .sec-title { font-size: 12px; font-weight: bold; background: #f1f5f9; padding: 5px; margin-top: 15px; margin-bottom: 8px; border-left: 3px solid #0f9f6e; }
        .data-table { width: 100%; border-collapse: collapse; margin-bottom: 12px; }
        .data-table th, .data-table td { border: 1px solid #cbd5e1; padding: 6px; text-align: left; font-size: 10px; }
        .data-table th { background: #f8fafc; font-weight: bold; }
        .signatures { margin-top: 40px; width: 100%; }
        .signatures td { width: 33.3%; text-align: center; padding-top: 40px; border-top: 1px solid #94a3b8; font-size: 10px; }
    </style>
</head>
<body>
    <div class="header">
        <table style="width: 100%;">
            <tr>
                <td>
                    <div class="title">{{ $company->company_name ?? 'SATTVA BOTANICAL PVT LTD' }}</div>
                    <div>{{ $company->address ?? 'Plot No. 12, Extraction Industrial Area' }}</div>
                    <div>Ayush / Drug License: {{ $company->drug_license_no ?? 'AYUSH-MH-1029' }}</div>
                </td>
                <td style="text-align: right;">
                    <div class="bmr-no">BATCH MANUFACTURING RECORD</div>
                    <div style="font-weight: bold; color: #0f9f6e; font-size: 13px;">{{ $batch->batch_no ?? $batch->batch_number }}</div>
                    <div>Status: {{ $batch->status }}</div>
                </td>
            </tr>
        </table>
    </div>

    <div class="sec-title">1. BATCH IDENTIFICATION</div>
    <table class="meta-table">
        <tr>
            <td><strong>Product:</strong> {{ $batch->product->name ?? '-' }}</td>
            <td><strong>Batch Size:</strong> {{ $batch->planned_quantity ?? $batch->batch_size }} {{ $batch->uom->symbol ?? 'KG' }}</td>
        </tr>
        <tr>
            <td><strong>Recipe:</strong> {{ $batch->recipe->name ?? '-' }} (v{{ $batch->recipeVersion->version_number ?? '1.0' }})</td>
            <td><strong>Mfg Date:</strong> {{ $batch->start_date ? \Carbon\Carbon::parse($batch->start_date)->format('d-m-Y') : date('d-m-Y') }}</td>
        </tr>
    </table>

    <div class="sec-title">2. MANUFACTURING STAGE LOG</div>
    <table class="data-table">
        <thead>
            <tr>
                <th width="30">Seq</th>
                <th>Process Stage</th>
                <th>Equipment Vessel</th>
                <th>Start Time</th>
                <th>End Time</th>
                <th>Stage Status</th>
            </tr>
        </thead>
        <tbody>
            @foreach($batch->stageExecutions()->orderBy('sequence_no')->get() as $se)
            <tr>
                <td style="text-align: center;">{{ $se->sequence_no }}</td>
                <td><strong>{{ $se->stage->name ?? '-' }}</strong></td>
                <td>{{ $se->equipment->name ?? 'Standard Cleaned' }}</td>
                <td>{{ $se->started_at ? \Carbon\Carbon::parse($se->started_at)->format('d-m-Y H:i') : '-' }}</td>
                <td>{{ $se->completed_at ? \Carbon\Carbon::parse($se->completed_at)->format('d-m-Y H:i') : '-' }}</td>
                <td>{{ $se->status }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div class="signatures">
        <table style="width: 100%;">
            <tr>
                <td>Production Chemist</td>
                <td>QA / QC Head</td>
                <td>Plant Manager</td>
            </tr>
        </table>
    </div>
</body>
</html>