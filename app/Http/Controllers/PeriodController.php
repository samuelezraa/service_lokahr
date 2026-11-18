<?php

namespace App\Http\Controllers;

use App\Models\Period;
use Illuminate\Http\Request;
use Carbon\Carbon;

class PeriodController extends Controller
{
    /**
     * GET /api/periods
     * Mendukung Search & Server-Side Pagination
     */
    public function index(Request $request)
    {
        $query = Period::latest('start_date');

        // 1. Filter Search (berdasarkan nama periode atau kode)
        if ($request->has('search') && !empty($request->search)) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%");
            });
        }

        // 2. Server-Side Pagination
        $perPage = (int) $request->input('per_page', 25);
        $paginated = $query->paginate($perPage);

        // 3. Map Response (Format Tanggal: d-m-Y -> DD-MM-YYYY)
        $mappedItems = collect($paginated->items())->map(function ($p) {
            return [
                'id'            => $p->id,
                'idPeriode'     => $p->code ?? sprintf('22%04d', $p->id),
                'periode'       => $p->name,
                'tanggalAwal'   => $p->start_date ? Carbon::parse($p->start_date)->format('d-m-Y') : '-',
                'tanggalAkhir'  => $p->end_date ? Carbon::parse($p->end_date)->format('d-m-Y') : '-',
                'lateTolerance' => $p->late_tolerance ?? '00:15:00',
                'keterangan'    => $p->description ?? 'Generate Successfully',
                'isClosed'      => (bool) $p->is_closed,
                'isActive'      => (bool) $p->is_active,
            ];
        });

        return response()->json([
            'success' => true,
            'data'    => $mappedItems,
            'meta'    => [
                'current_page' => $paginated->currentPage(),
                'from'         => $paginated->firstItem(),
                'last_page'    => $paginated->lastPage(),
                'per_page'     => $paginated->perPage(),
                'to'           => $paginated->lastItem(),
                'total'        => $paginated->total(),
            ]
        ]);
    }

    /**
     * POST /api/periods
     */
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
                'tanggalAwal'   => Carbon::parse($period->start_date)->format('d-m-Y'),
                'tanggalAkhir'  => Carbon::parse($period->end_date)->format('d-m-Y'),
                'lateTolerance' => $period->late_tolerance,
                'keterangan'    => $period->description,
                'isClosed'      => (bool) $period->is_closed,
                'isActive'      => (bool) $period->is_active,
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