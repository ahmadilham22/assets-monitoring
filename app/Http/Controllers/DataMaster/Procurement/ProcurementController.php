<?php

namespace App\Http\Controllers\DataMaster\Procurement;

use App\Http\Controllers\Controller;
use App\Http\Requests\DataMaster\ProcurementRequest;
use App\Models\DataMaster\Procurement;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class ProcurementController extends Controller
{
    public function index()
    {
        if (request()->ajax()) {
            $query = Procurement::query()->orderBy('updated_at', 'desc');

            return DataTables::eloquent($query)
                ->addIndexColumn()
                ->addColumn('action', function (Procurement $procurement) {
                    return view('pages.data-master.procurement._action.procurementsAction', [
                        'data' => $procurement,
                    ])->render();
                })
                ->rawColumns(['action'])
                ->make(true);
        }

        return view('pages.data-master.procurement.index');
    }

    public function store(ProcurementRequest $request)
    {
        $procurementId = $request->input('id');

        $procurement = Procurement::updateOrCreate(
            ['id' => $procurementId],
            $request->safe()->only(['mitra', 'jenis_pengadaan', 'tahun_pengadaan'])
        );

        return response()->json([
            'success' => true,
            'message' => $procurementId ? 'Data berhasil diperbarui' : 'Data berhasil ditambahkan',
            'data' => $procurement->refresh(),
        ]);
    }

    public function edit(Request $request)
    {
        $procurement = Procurement::find($request->input('id'));

        if (! $procurement) {
            return response()->json([
                'success' => false,
                'message' => 'Data tidak ditemukan',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $procurement,
        ]);
    }

    public function destroy(Request $request)
    {
        $procurement = Procurement::findOrFail($request->input('id'));

        if ($procurement->fixedAssets()->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Pengadaan tidak bisa dihapus karena masih digunakan oleh aset tetap.',
            ], 422);
        }

        $procurement->delete();

        return response()->json([
            'success' => true,
            'message' => 'Data berhasil dihapus',
        ]);
    }
}
