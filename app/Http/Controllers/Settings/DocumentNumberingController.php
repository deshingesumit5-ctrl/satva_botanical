<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\DocumentNumbering;
use App\Services\AuditService;
use Illuminate\Http\Request;

class DocumentNumberingController extends Controller
{
    public function index()
    {
        $numberings = DocumentNumbering::orderBy('document_type')->get();
        return view('settings.document-numbering', compact('numberings'));
    }

    public function update(Request $request)
    {
        $data = $request->input('numbering', []);

        foreach ($data as $id => $row) {
            $num = DocumentNumbering::find($id);
            if ($num) {
                $num->prefix = $row['prefix'] ?? $num->prefix;
                $num->starting_number = (int)($row['starting_number'] ?? $num->starting_number);
                $num->financial_year_reset = isset($row['financial_year_reset']);
                $num->number_format = $row['number_format'] ?? $num->number_format;
                $num->status = $row['status'] ?? $num->status;
                $num->save();
            }
        }

        AuditService::log('settings', 'DOC_NUMBERING', 'update_numbering', null, null, 'Updated document numbering configuration');

        return redirect()->back()->with('success', 'Document numbering rules saved.');
    }
}
