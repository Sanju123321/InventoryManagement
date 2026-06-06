<?php

namespace App\Http\Controllers;

use App\Models\Firm;
use App\Services\ActivityLogService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class FirmController extends Controller
{
    public function index()
    {
        $firms = Firm::where('company_id', auth()->user()->company_id)
            ->latest()
            ->paginate(15);

        return view('firms.index', compact('firms'));
    }

    public function create()
    {
        return view('firms.create');
    }

    public function store(Request $request)
    {
        $companyId = auth()->user()->company_id;

        $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('firms', 'name')->where('company_id', $companyId),
            ],
            'gst' => 'nullable|string|max:20',
            'address' => 'nullable|string|max:1000',
            'status' => 'required|in:active,inactive',
            'mobile_number' => 'nullable|string|max:20',
        ]);

        Firm::create([
            'company_id' => $companyId,
            'name' => $request->name,
            'gst' => $request->gst,
            'address' => $request->address,
            'status' => $request->status,
            'mobile_number' => $request->mobile_number,
        ]);

        ActivityLogService::log('firm.created', "Firm '{$request->name}' added.");

        return redirect()->route('firms.index')->with('success', 'Firm created successfully.');
    }

    public function edit(Firm $firm)
    {
        $this->authorizeFirm($firm);

        return view('firms.edit', compact('firm'));
    }

    public function update(Request $request, Firm $firm)
    {
        $this->authorizeFirm($firm);

        $companyId = auth()->user()->company_id;

        $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('firms', 'name')->where('company_id', $companyId)->ignore($firm->id),
            ],
            'gst' => 'nullable|string|max:20',
            'address' => 'nullable|string|max:1000',
            'status' => 'required|in:active,inactive',
            'mobile_number' => 'nullable|string|max:20',
        ]);

        $firm->update($request->only('name', 'gst', 'address', 'status', 'mobile_number'));

        ActivityLogService::log('firm.updated', "Firm '{$firm->name}' updated.");

        return redirect()->route('firms.index')->with('success', 'Firm updated successfully.');
    }

    public function destroy(Firm $firm)
    {
        $this->authorizeFirm($firm);

        if ($firm->salesOrders()->exists()) {
            return back()->withErrors(['firm' => 'Cannot delete firm linked to sales orders.']);
        }

        $name = $firm->name;
        $firm->delete();

        ActivityLogService::log('firm.deleted', "Firm '{$name}' deleted.");

        return redirect()->route('firms.index')->with('success', 'Firm deleted successfully.');
    }

    private function authorizeFirm(Firm $firm): void
    {
        abort_unless($firm->company_id === auth()->user()->company_id, 403);
    }
}
