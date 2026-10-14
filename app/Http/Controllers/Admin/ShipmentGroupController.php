<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Batch;
use App\Models\OrderStatus;
use App\Models\ShipmentGroup;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ShipmentGroupController extends Controller
{
    public function index(Batch $batch)
    {
        $batch->load(['shipmentGroups.currentStatus', 'shipmentGroups.items']);

        return view('admin.batches.shipments', [
            'batch' => $batch,
            'statuses' => OrderStatus::query()
                ->whereIn('scope', ['batch', 'all'])
                ->where('is_active', true)
                ->orderBy('sequence')
                ->get(),
        ]);
    }

    public function store(Request $request, Batch $batch)
    {
        $batch->shipmentGroups()->create($this->validated($request));

        return back()->with('status', 'Shipment group berhasil ditambahkan.');
    }

    public function update(Request $request, Batch $batch, ShipmentGroup $shipmentGroup)
    {
        abort_unless($shipmentGroup->batch_id === $batch->id, 404);
        $shipmentGroup->update($this->validated($request));

        return back()->with('status', 'Shipment group berhasil diperbarui.');
    }

    public function destroy(Batch $batch, ShipmentGroup $shipmentGroup)
    {
        abort_unless($shipmentGroup->batch_id === $batch->id, 404);

        if ($shipmentGroup->items()->exists()) {
            throw ValidationException::withMessages([
                'shipment_group' => 'Shipment group masih dipakai oleh item pesanan. Pindahkan item terlebih dahulu sebelum menghapusnya.',
            ]);
        }

        $shipmentGroup->delete();

        return back()->with('status', 'Shipment group dihapus.');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'method' => ['required', Rule::in(['ems', 'air_cargo', 'sea_cargo', 'handcarry', 'online_handcarry', 'other'])],
            'tracking_number' => ['nullable', 'string', 'max:255'],
            'shipment_code' => ['nullable', 'string', 'max:255'],
            'current_status_id' => [
                'nullable',
                Rule::exists('order_statuses', 'id')->where(fn ($query) => $query
                    ->whereIn('scope', ['batch', 'all'])
                    ->where('is_active', true)),
            ],
            'details_url' => ['nullable', 'url', 'max:2048'],
            'last_co_at' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);
    }
}
