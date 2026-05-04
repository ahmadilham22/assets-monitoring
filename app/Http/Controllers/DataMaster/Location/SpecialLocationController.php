<?php

namespace App\Http\Controllers\DataMaster\Location;

use App\Http\Controllers\Controller;
use App\Http\Requests\DataMaster\SpecificLocationRequest;
use App\Models\DataMaster\Location;
use App\Models\DataMaster\SpecialLocation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Yajra\DataTables\Facades\DataTables;

class SpecialLocationController extends Controller
{
    public function index()
    {
        if (request()->ajax()) {
            $query = SpecialLocation::query()
                ->with('location:id,lokasi_umum')
                ->orderBy('updated_at', 'desc');

            return DataTables::eloquent($query)
                ->addIndexColumn()
                ->addColumn('action', function (SpecialLocation $specialLocation) {
                    return view('pages.data-master.special-location._action.spesificLocationAction', [
                        'data' => $specialLocation,
                    ])->render();
                })
                ->addColumn('qrcode', function (SpecialLocation $specialLocation) {
                    return view('pages.data-master.special-location._action.qrcode', [
                        'data' => $specialLocation,
                    ])->render();
                })
                ->rawColumns(['action', 'qrcode'])
                ->make(true);
        }

        $data = Location::orderBy('lokasi_umum')->get(['id', 'kode_lokasi', 'lokasi_umum']);

        return view('pages.data-master.special-location.index', compact('data'));
    }

    public function store(SpecificLocationRequest $request)
    {
        $specialLocationId = $request->input('id');
        $isNew = empty($specialLocationId);

        $attributes = $request->safe()->only(['location_id', 'kode_lokasi', 'lokasi_khusus']);

        // Generate QR code SEBELUM insert, supaya kalau render gagal
        // row tidak pernah ter-insert (bukan setengah jadi seperti sebelumnya).
        if ($isNew) {
            $attributes['qrcode'] = $this->generateQrCode($attributes['kode_lokasi']);
        }

        $specialLocation = SpecialLocation::updateOrCreate(
            ['id' => $specialLocationId],
            $attributes
        );

        return response()->json([
            'success' => true,
            'message' => $isNew ? 'Data berhasil ditambahkan' : 'Data berhasil diperbarui',
            'data' => $specialLocation->refresh(),
        ]);
    }

    public function edit(Request $request)
    {
        $specialLocation = SpecialLocation::find($request->input('id'));

        if (! $specialLocation) {
            return response()->json([
                'success' => false,
                'message' => 'Data tidak ditemukan',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $specialLocation,
        ]);
    }

    public function destroy(Request $request)
    {
        $specialLocation = SpecialLocation::findOrFail($request->input('id'));

        if ($specialLocation->fixedAssets()->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Sub lokasi tidak bisa dihapus karena masih digunakan oleh aset tetap.',
            ], 422);
        }

        // Bersihkan file QR code dari storage sebelum soft-delete row
        if ($specialLocation->qrcode) {
            Storage::disk('public')->delete('qrcodes/locations/' . $specialLocation->qrcode);
        }

        $specialLocation->delete();

        return response()->json([
            'success' => true,
            'message' => 'Data berhasil dihapus',
        ]);
    }

    /**
     * Generate QR code SVG yang mengarah ke daftar aset publik per sub lokasi.
     *
     * Pakai SVG karena zero-dependency (tidak butuh imagick/GD extension),
     * file-nya kecil, dan crisp di semua resolusi.
     */
    private function generateQrCode(string $kodeLokasi): string
    {
        $url = url("/list-public/?kode_lokasi={$kodeLokasi}");
        $fileName = time() . '.' . $kodeLokasi . '.svg';

        $qrCode = QrCode::format('svg')
            ->size(512)
            ->errorCorrection('L')
            ->generate($url);

        Storage::disk('public')->put('qrcodes/locations/' . $fileName, $qrCode);

        return $fileName;
    }
}
