<?php

namespace App\Http\Controllers\DataMaster;

use App\Http\Controllers\Controller;
use App\Http\Requests\DataMaster\UnitRequest;
use App\Models\DataMaster\Unit;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class UnitController extends Controller
{
    public function index()
    {
        if (request()->ajax()) {
            $query = Unit::query()->orderBy('updated_at', 'desc');

            return DataTables::eloquent($query)
                ->addIndexColumn()
                ->addColumn('action', function (Unit $unit) {
                    return view('pages.data-master.unit._action.unitAction', [
                        'data' => $unit,
                    ])->render();
                })
                ->rawColumns(['action'])
                ->make(true);
        }

        return view('pages.data-master.unit.index');
    }

    public function store(UnitRequest $request)
    {
        $unitId = $request->input('id');

        $unit = Unit::updateOrCreate(
            ['id' => $unitId],
            $request->safe()->only(['nama'])
        );

        return response()->json([
            'success' => true,
            'message' => $unitId ? 'Data berhasil diperbarui' : 'Data berhasil ditambahkan',
            'data' => $unit->refresh(),
        ]);
    }

    public function edit(Request $request)
    {
        $unit = Unit::find($request->input('id'));

        if (! $unit) {
            return response()->json([
                'success' => false,
                'message' => 'Data tidak ditemukan',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $unit,
        ]);
    }

    public function destroy(Request $request)
    {
        $unit = Unit::findOrFail($request->input('id'));

        if ($unit->fixedAssets()->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Unit tidak bisa dihapus karena masih digunakan oleh aset tetap.',
            ], 422);
        }

        $unit->delete();

        return response()->json([
            'success' => true,
            'message' => 'Data berhasil dihapus',
        ]);
    }
}
