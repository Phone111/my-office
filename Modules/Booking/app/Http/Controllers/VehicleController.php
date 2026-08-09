<?php

namespace Modules\Booking\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Booking\Models\Vehicle;

class VehicleController extends Controller
{
    /**
     * จัดการรถยนต์ (เฉพาะ admin)
     */
    public function index(): Response
    {
        return Inertia::render('Booking::Vehicles', [
            'vehicles' => Vehicle::latest()->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        Vehicle::create($this->validateData($request));

        return back()->with('success', 'เพิ่มรถยนต์เรียบร้อยแล้ว');
    }

    public function update(Request $request, Vehicle $vehicle): RedirectResponse
    {
        $vehicle->update($this->validateData($request, $vehicle));

        return back()->with('success', 'แก้ไขข้อมูลรถยนต์เรียบร้อยแล้ว');
    }

    public function destroy(Vehicle $vehicle): RedirectResponse
    {
        $vehicle->delete();

        return back()->with('success', 'ลบรถยนต์เรียบร้อยแล้ว');
    }

    private function validateData(Request $request, ?Vehicle $vehicle = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'license_plate' => ['required', 'string', 'max:50', Rule::unique('vehicles', 'license_plate')->ignore($vehicle?->id)],
            'seats' => ['required', 'integer', 'min:1'],
            'is_active' => ['boolean'],
        ], [
            'license_plate.unique' => 'ทะเบียนรถนี้มีอยู่แล้ว',
        ]);
    }
}
