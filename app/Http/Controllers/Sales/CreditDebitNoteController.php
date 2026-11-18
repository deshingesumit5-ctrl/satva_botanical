<?php

namespace App\Http\Controllers\Sales;

use App\Http\Controllers\Controller;
use App\Models\CreditDebitNote;
use App\Models\Customer;
use App\Models\Supplier;
use App\Services\AuditService;
use App\Services\DocumentNumberService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CreditDebitNoteController extends Controller
{
    protected DocumentNumberService $docService;

    public function __construct(DocumentNumberService $docService)
    {
        $this->docService = $docService;
    }

    public function index(Request $request)
    {
        $query = CreditDebitNote::with(['customer', 'supplier', 'createdByUser']);

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('note_number', 'like', "%{$search}%")
                  ->orWhere('invoice_reference', 'like', "%{$search}%")
                  ->orWhere('reason', 'like', "%{$search}%");
            });
        }

        if ($type = $request->input('note_type')) {
            $query->where('note_type', $type);
        }

        $notes = $query->latest()->paginate(10)->withQueryString();

        return view('sales.notes.index', compact('notes'));
    }

    public function create()
    {
        $customers = Customer::where('status', 'Active')->orderBy('name')->get();
        $suppliers = Supplier::where('status', 'Active')->orderBy('name')->get();

        return view('sales.notes.create', compact('customers', 'suppliers'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'note_type' => 'required|in:Credit Note,Debit Note',
            'party_type' => 'required|in:Customer,Supplier',
            'customer_id' => 'required_if:party_type,Customer|nullable|exists:customers,id',
            'supplier_id' => 'required_if:party_type,Supplier|nullable|exists:suppliers,id',
            'note_date' => 'required|date',
            'invoice_reference' => 'nullable|string|max:50',
            'amount' => 'required|numeric|min:0.01',
            'reason' => 'required|string|max:1000',
        ]);

        $noteNumber = $this->docService->generate('CDN');

        $note = CreditDebitNote::create([
            'note_number' => $noteNumber,
            'note_type' => $validated['note_type'],
            'party_type' => $validated['party_type'],
            'customer_id' => $validated['party_type'] === 'Customer' ? $validated['customer_id'] : null,
            'supplier_id' => $validated['party_type'] === 'Supplier' ? $validated['supplier_id'] : null,
            'note_date' => $validated['note_date'],
            'invoice_reference' => $validated['invoice_reference'] ?? null,
            'amount' => $validated['amount'],
            'reason' => $validated['reason'],
            'created_by' => Auth::id(),
        ]);

        AuditService::log('accounts', $note->note_number, 'create_credit_debit_note', null, $note->toArray(), "Created {$note->note_type}");

        if ($request->expectsJson()) {
            return response()->json([
                'message' => "{$note->note_type} {$note->note_number} recorded.",
                'redirect' => route('sales.notes.index'),
            ]);
        }

        return redirect()->route('sales.notes.index')->with('success', "{$note->note_type} {$note->note_number} recorded.");
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
