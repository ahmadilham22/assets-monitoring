<?php

namespace App\Http\Controllers\DataMaster\Division;

use App\Http\Controllers\Controller;
use App\Http\Requests\DataMaster\DivisionRequest;
use App\Models\DataMaster\Division;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class DivisionController extends Controller
{
    public function index()
    {
        if (request()->ajax()) {
            $query = Division::query()->orderBy('updated_at', 'desc');

            return DataTables::eloquent($query)
                ->addIndexColumn()
                ->addColumn('action', function (Division $division) {
                    return view('pages.data-master.division._action.divisionAction', [
                        'data' => $division,
                    ])->render();
                })
                ->rawColumns(['action'])
                ->make(true);
        }

        return view('pages.data-master.division.index');
    }

    public function store(DivisionRequest $request)
    {
        $divisionId = $request->input('id');

        $division = Division::updateOrCreate(
            ['id' => $divisionId],
            $request->safe()->only(['kode_divisi', 'nama_divisi'])
        );

        return response()->json([
            'success' => true,
            'message' => $divisionId ? 'Data berhasil diperbarui' : 'Data berhasil ditambahkan',
            'data' => $division->refresh(),
        ]);
    }

    public function edit(Request $request)
    {
        $division = Division::find($request->input('id'));

        if (! $division) {
            return response()->json([
                'success' => false,
                'message' => 'Data tidak ditemukan',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $division,
        ]);
    }

    public function destroy(Request $request)
    {
        $division = Division::findOrFail($request->input('id'));

        if ($division->users()->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Divisi tidak bisa dihapus karena masih memiliki user.',
            ], 422);
        }

        $division->delete();

        return response()->json([
            'success' => true,
            'message' => 'Data berhasil dihapus',
        ]);
    }
}
