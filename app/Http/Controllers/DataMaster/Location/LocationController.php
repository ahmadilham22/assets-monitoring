<?php

namespace App\Http\Controllers\DataMaster\Location;

use App\Http\Controllers\Controller;
use App\Http\Requests\DataMaster\LocationRequest;
use App\Models\DataMaster\Location;
use Illuminate\Http\Request;
use Yajra\DataTables\Facades\DataTables;

class LocationController extends Controller
{
    public function index()
    {
        if (request()->ajax()) {
            $query = Location::query()->orderBy('updated_at', 'desc');

            return DataTables::eloquent($query)
                ->addIndexColumn()
                ->addColumn('action', function (Location $location) {
                    return view('pages.data-master.location._action.locationAction', [
                        'data' => $location,
                    ])->render();
                })
                ->rawColumns(['action'])
                ->make(true);
        }

        return view('pages.data-master.location.index');
    }

    public function store(LocationRequest $request)
    {
        $locationId = $request->input('id');

        $location = Location::updateOrCreate(
            ['id' => $locationId],
            $request->safe()->only(['kode_lokasi', 'lokasi_umum'])
        );

        return response()->json([
            'success' => true,
            'message' => $locationId ? 'Data berhasil diperbarui' : 'Data berhasil ditambahkan',
            'data' => $location->refresh(),
        ]);
    }

    public function edit(Request $request)
    {
        $location = Location::find($request->input('id'));

        if (! $location) {
            return response()->json([
                'success' => false,
                'message' => 'Data tidak ditemukan',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $location,
        ]);
    }

    public function destroy(Request $request)
    {
        $location = Location::findOrFail($request->input('id'));

        if ($location->specialLocations()->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Lokasi tidak bisa dihapus karena masih memiliki sub lokasi.',
            ], 422);
        }

        $location->delete();

        return response()->json([
            'success' => true,
            'message' => 'Data berhasil dihapus',
        ]);
    }
}
