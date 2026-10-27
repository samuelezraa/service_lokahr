<?php

namespace App\Http\Controllers;

use App\Models\Period;
use Illuminate\Http\Request;

class PeriodController extends Controller
{
    public function index()
    {
        $periods = Period::latest('start_date')->get();

        $data = $periods->map(function ($p) {
            return [
                'id'            => $p->id,
                'idPeriode'     => $p->code ?? sprintf('22%04d', $p->id),
                'periode'       => $p->name,
                'tanggalAwal'   => $p->start_date ? \Carbon\Carbon::parse($p->start_date)->format('Y-m-d') : '-',
                'tanggalAkhir'  => $p->end_date ? \Carbon\Carbon::parse($p->end_date)->format('Y-m-d') : '-',
                'lateTolerance' => $p->late_tolerance ?? '00:15:00',
                'keterangan'    => $p->description ?? 'Generate Successfully',
                'isClosed'      => $p->is_closed,
                'isActive'      => $p->is_active,
            ];
        });

        return response()->json([
            'success' => true,
            'data'    => $data,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'           => 'required|string|max:255',
            'start_date'     => 'required|date',
            'end_date'       => 'required|date|after_or_equal:start_date',
            'late_tolerance' => 'nullable|string',
            'description'    => 'nullable|string',
            'is_closed'      => 'boolean',
            'is_active'      => 'boolean',
        ]);

        // Auto-generate kode periode jika tidak diinput
        $lastId = Period::max('id') ?? 0;
        $validated['code'] = sprintf('22%04d', $lastId + 1);

        if (empty($validated['description'])) {
            $validated['description'] = 'Generate Successfully';
        }

        $period = Period::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Periode jadwal berhasil dibuat',
            'data'    => [
                'id'            => $period->id,
                'idPeriode'     => $period->code,
                'periode'       => $period->name,
                'tanggalAwal'   => \Carbon\Carbon::parse($period->start_date)->format('Y-m-d'),
                'tanggalAkhir'  => \Carbon\Carbon::parse($period->end_date)->format('Y-m-d'),
                'lateTolerance' => $period->late_tolerance,
                'keterangan'    => $period->description,
                'isClosed'      => $period->is_closed,
                'isActive'      => $period->is_active,
            ],
        ], 201);
    }

    
    public function show($id)
    {
        $period = Period::findOrFail($id);
        return response()->json([
            'success' => true,
            'data'    => $period,
        ]);
    }

    public function update(Request $request, $id)
    {
        $period = Period::findOrFail($id);

        $validated = $request->validate([
            'name'           => 'required|string|max:255',
            'start_date'     => 'required|date',
            'end_date'       => 'required|date|after_or_equal:start_date',
            'late_tolerance' => 'nullable|string',
            'description'    => 'nullable|string',
            'is_closed'      => 'boolean',
            'is_active'      => 'boolean',
        ]);

        $period->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Periode jadwal berhasil diperbarui',
            'data'    => $period,
        ]);
    }

    public function destroy($id)
    {
        $period = Period::findOrFail($id);
        $period->delete();

        return response()->json([
            'success' => true,
            'message' => 'Periode jadwal berhasil dihapus',
        ]);
    }
}