<?php

namespace App\Http\Controllers\Purchase;

use App\Http\Controllers\Controller;
use App\Models\Department;
use App\Models\Material;
use App\Models\Plant;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderItem;
use App\Models\PurchaseRequisition;
use App\Models\PurchaseRequisitionItem;
use App\Models\Uom;
use App\Services\AuditService;
use App\Services\DocumentNumberService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PurchaseRequisitionController extends Controller
{
    protected DocumentNumberService $docService;

    public function __construct(DocumentNumberService $docService)
    {
        $this->docService = $docService;
    }

    public function index(Request $request)
    {
        $query = PurchaseRequisition::with(['plant', 'department', 'requestedByUser', 'items.material']);

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('pr_number', 'like', "%{$search}%")
                  ->orWhere('remarks', 'like', "%{$search}%");
            });
        }

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        $requisitions = $query->latest()->paginate(10)->withQueryString();

        return view('purchase.requisitions.index', compact('requisitions'));
    }

    public function create()
    {
        $plants = Plant::where('status', 'Active')->get();
        $departments = Department::where('status', 'Active')->get();
        $materials = Material::where('status', 'Active')->with('baseUom')->orderBy('name')->get();
        $uoms = Uom::where('status', 'Active')->where('is_stock_unit', true)->get();

        return view('purchase.requisitions.create', compact('plants', 'departments', 'materials', 'uoms'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'plant_id' => 'required|exists:plants,id',
            'department_id' => 'required|exists:departments,id',
            'required_by_date' => 'required|date',
            'priority' => 'required|in:Low,Medium,High,Urgent',
            'remarks' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.material_id' => 'required|exists:materials,id',
            'items.*.quantity' => 'required|numeric|min:0.001',
            'items.*.uom_id' => 'required|exists:uoms,id',
            'items.*.required_date' => 'nullable|date',
            'items.*.remarks' => 'nullable|string',
        ]);

        $prNumber = $this->docService->generate('PR');

        $pr = DB::transaction(function () use ($validated, $prNumber) {
            $requisition = PurchaseRequisition::create([
                'pr_number' => $prNumber,
                'plant_id' => $validated['plant_id'],
                'department_id' => $validated['department_id'],
                'requested_by' => Auth::id(),
                'required_by_date' => $validated['required_by_date'],
                'priority' => $validated['priority'],
                'status' => 'Draft',
                'remarks' => $validated['remarks'] ?? null,
            ]);

            foreach ($validated['items'] as $item) {
                PurchaseRequisitionItem::create([
                    'purchase_requisition_id' => $requisition->id,
                    'material_id' => $item['material_id'],
                    'quantity' => $item['quantity'],
                    'uom_id' => $item['uom_id'],
                    'required_date' => $item['required_date'] ?? $validated['required_by_date'],
                    'remarks' => $item['remarks'] ?? null,
                ]);
            }

            AuditService::log('purchase', $requisition->pr_number, 'create_pr', null, $requisition->toArray(), 'Created purchase requisition');
            return $requisition;
        });

        return redirect()->route('purchase.requisitions.show', $pr->id)->with('success', "Purchase Requisition {$pr->pr_number} created.");
    }

    public function show(PurchaseRequisition $requisition)
    {
        $requisition->load(['plant', 'department', 'requestedByUser', 'approvedByUser', 'items.material', 'items.uom']);
        return view('purchase.requisitions.show', compact('requisition'));
    }

    public function edit(PurchaseRequisition $requisition)
    {
        if ($requisition->status !== 'Draft') {
            return redirect()->route('purchase.requisitions.show', $requisition->id)->with('warning', 'Only Draft requisitions can be edited.');
        }

        $requisition->load('items');
        $plants = Plant::where('status', 'Active')->get();
        $departments = Department::where('status', 'Active')->get();
        $materials = Material::where('status', 'Active')->with('baseUom')->orderBy('name')->get();
        $uoms = Uom::where('status', 'Active')->where('is_stock_unit', true)->get();

        return view('purchase.requisitions.edit', compact('requisition', 'plants', 'departments', 'materials', 'uoms'));
    }

    public function update(Request $request, PurchaseRequisition $requisition)
    {
        if ($requisition->status !== 'Draft') {
            return back()->with('error', 'Only Draft requisitions can be updated.');
        }

        $validated = $request->validate([
            'plant_id' => 'required|exists:plants,id',
            'department_id' => 'required|exists:departments,id',
            'required_by_date' => 'required|date',
            'priority' => 'required|in:Low,Medium,High,Urgent',
            'remarks' => 'nullable|string',
            'items' => 'required|array|min:1',
            'items.*.material_id' => 'required|exists:materials,id',
            'items.*.quantity' => 'required|numeric|min:0.001',
            'items.*.uom_id' => 'required|exists:uoms,id',
            'items.*.required_date' => 'nullable|date',
            'items.*.remarks' => 'nullable|string',
        ]);

        DB::transaction(function () use ($requisition, $validated) {
            $requisition->update([
                'plant_id' => $validated['plant_id'],
                'department_id' => $validated['department_id'],
                'required_by_date' => $validated['required_by_date'],
                'priority' => $validated['priority'],
                'remarks' => $validated['remarks'] ?? null,
            ]);

            // Replace items
            $requisition->items()->delete();
            foreach ($validated['items'] as $item) {
                PurchaseRequisitionItem::create([
                    'purchase_requisition_id' => $requisition->id,
                    'material_id' => $item['material_id'],
                    'quantity' => $item['quantity'],
                    'uom_id' => $item['uom_id'],
                    'required_date' => $item['required_date'] ?? $validated['required_by_date'],
                    'remarks' => $item['remarks'] ?? null,
                ]);
            }

            AuditService::log('purchase', $requisition->pr_number, 'update_pr', null, null, 'Updated purchase requisition');
        });

        return redirect()->route('purchase.requisitions.show', $requisition->id)->with('success', 'Purchase Requisition updated.');
    }

    public function destroy(PurchaseRequisition $requisition)
    {
        if ($requisition->status !== 'Draft') {
            return back()->with('error', 'Only Draft requisitions can be deleted.');
        }

        $code = $requisition->pr_number;
        $requisition->items()->delete();
        $requisition->delete();

        AuditService::log('purchase', $code, 'delete_pr', null, null, 'Deleted purchase requisition');

        return redirect()->route('purchase.requisitions.index')->with('success', 'Purchase Requisition deleted.');
    }

    public function submit(PurchaseRequisition $requisition)
    {
        if ($requisition->status !== 'Draft') {
            return back()->with('error', 'Only Draft requisitions can be submitted.');
        }

        $requisition->status = 'Pending Approval';
        $requisition->save();

        AuditService::log('purchase', $requisition->pr_number, 'submit_pr', null, null, 'Submitted PR for approval');

        return redirect()->route('purchase.requisitions.show', $requisition->id)->with('success', 'Requisition submitted for approval.');
    }

    public function approve(PurchaseRequisition $requisition)
    {
        if ($requisition->status !== 'Pending Approval') {
            return back()->with('error', 'Only Pending Approval requisitions can be approved.');
        }

        $requisition->status = 'Approved';
        $requisition->approved_by = Auth::id();
        $requisition->approved_at = now();
        $requisition->save();

        AuditService::log('purchase', $requisition->pr_number, 'approve_pr', null, null, 'Approved PR');

        return redirect()->route('purchase.requisitions.show', $requisition->id)->with('success', 'Purchase Requisition approved.');
    }

    public function reject(Request $request, PurchaseRequisition $requisition)
    {
        $request->validate(['reason' => 'required|string|max:500']);

        $requisition->status = 'Rejected';
        $requisition->remarks = ($requisition->remarks ? $requisition->remarks . "\n" : '') . "Rejection reason: " . $request->input('reason');
        $requisition->save();

        AuditService::log('purchase', $requisition->pr_number, 'reject_pr', null, ['reason' => $request->input('reason')], 'Rejected PR');

        return redirect()->route('purchase.requisitions.show', $requisition->id)->with('success', 'Purchase Requisition rejected.');
    }

    public function convertToPo(PurchaseRequisition $requisition)
    {
        if ($requisition->status !== 'Approved') {
            return back()->with('error', 'Only Approved requisitions can be converted to Purchase Orders.');
        }

        return redirect()->route('purchase.orders.create', ['pr_id' => $requisition->id]);
    }
}
