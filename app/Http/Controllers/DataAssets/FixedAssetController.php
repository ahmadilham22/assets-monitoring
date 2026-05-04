<?php

namespace App\Http\Controllers\DataAssets;

use App\Exports\FixedAsset\MultipleSheetTemplateExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\DataAsset\StoreFixedAssetRequest;
use App\Http\Requests\DataAsset\UpdateFixedAssetRequest;
use App\Imports\MultipleSheetImport;
use App\Models\DataAsset\FixedAsset;
use App\Models\DataMaster\Category;
use App\Models\DataMaster\Division;
use App\Models\DataMaster\History;
use App\Models\DataMaster\Location;
use App\Models\DataMaster\Procurement;
use App\Models\DataMaster\SpecialLocation;
use App\Models\DataMaster\SubCategory;
use App\Models\DataMaster\Unit;
use App\Models\DataMaster\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Maatwebsite\Excel\Facades\Excel;
use Ramsey\Uuid\Uuid;
use SimpleSoftwareIO\QrCode\Facades\QrCode;
use Yajra\DataTables\Facades\DataTables;
use ZipArchive;

class FixedAssetController extends Controller
{
    /**
     * Generate QR code SVG yang mengarah ke halaman publik aset.
     *
     * Pakai SVG karena zero-dependency (tidak butuh imagick/GD extension),
     * file-nya kecil, dan crisp di semua resolusi.
     */
    private function generateQrCode(string $id): string
    {
        $url = url("/show-public/{$id}");
        $fileName = time() . '.' . $id . '.svg';

        $qrCode = QrCode::format('svg')
            ->size(512)
            ->errorCorrection('L')
            ->generate($url);

        Storage::disk('public')->put('qrcodes/' . $fileName, $qrCode);

        return $fileName;
    }

    public function updateSn(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'pk' => ['required', 'exists:fixed_assets,id'],
            'value' => [
                'nullable',
                'string',
                'max:100',
                Rule::unique('fixed_assets', 'kode_sn')
                    ->ignore($request->input('pk'))
                    ->whereNull('deleted_at'),
            ],
        ], [
            'pk.required' => 'Primary key tidak ditemukan.',
            'pk.exists' => 'Data aset tidak ditemukan.',
            'value.unique' => 'Kode SN sudah digunakan.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validasi gagal.',
                'errors' => $validator->errors()->toArray(),
            ], 422);
        }

        FixedAsset::find($request->input('pk'))->update([
            'kode_sn' => $request->input('value'),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Berhasil menyimpan data',
        ]);
    }

    public function updateBmn(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'pk' => ['required', 'exists:fixed_assets,id'],
            'value' => [
                'nullable',
                'string',
                'max:100',
                Rule::unique('fixed_assets', 'kode_bmn')
                    ->ignore($request->input('pk'))
                    ->whereNull('deleted_at'),
            ],
        ], [
            'pk.required' => 'Primary key tidak ditemukan.',
            'pk.exists' => 'Data aset tidak ditemukan.',
            'value.unique' => 'Kode BMN sudah digunakan.',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validasi gagal.',
                'errors' => $validator->errors()->toArray(),
            ], 422);
        }

        FixedAsset::find($request->input('pk'))->update([
            'kode_bmn' => $request->input('value'),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Berhasil menyimpan data',
        ]);
    }

    public function index(Request $request)
    {
        if ($request->ajax()) {
            // True server-side: filter di query, bukan Collection.
            // Eager-load relasi yang dipakai di kolom DataTables.
            //
            // Catatan: kolom harus di-qualify dengan nama tabel
            // (`fixed_assets.xxx`) karena Yajra DataTables akan menambahkan
            // LEFT JOIN ke tabel relasi saat user mengurutkan kolom relasi
            // (mis. `subcategory.category.nama_kategori`). Tanpa qualifier,
            // Postgres akan komplain "column ambiguous" untuk kolom yang
            // ada di lebih dari satu tabel (mis. `updated_at`).
            $query = FixedAsset::query()
                ->with([
                    'subcategory.category',
                    'specificlocation.location',
                    'user',
                    'procurement',
                ])
                ->select('fixed_assets.*')
                ->orderBy('fixed_assets.updated_at', 'desc');

            if ($request->filled('kondisi')) {
                $query->where('fixed_assets.kondisi', $request->input('kondisi'));
            }

            if ($request->filled('kategori')) {
                // Filter by category id melalui relasi sub_category
                $kategoriId = $request->input('kategori');
                $query->whereHas('subcategory', function ($q) use ($kategoriId) {
                    $q->where('categories_id', $kategoriId);
                });
            }

            if ($request->filled('pj')) {
                $query->where('fixed_assets.user_id', $request->input('pj'));
            }

            return DataTables::eloquent($query)
                ->addIndexColumn()
                ->addColumn('action', function (FixedAsset $data) {
                    return view('pages.data-asset.fixed-assets.action.fixedAssetAction', compact('data'))->render();
                })
                ->addColumn('checkbox', function (FixedAsset $data) {
                    return view('pages.data-asset.fixed-assets.action.checkbox', compact('data'))->render();
                })
                ->addColumn('inputSn', function (FixedAsset $data) {
                    return view('pages.data-asset.fixed-assets.action.inputSn', compact('data'))->render();
                })
                ->addColumn('inputBMN', function (FixedAsset $data) {
                    return view('pages.data-asset.fixed-assets.action.inputBMN', compact('data'))->render();
                })
                ->rawColumns(['action', 'checkbox', 'inputSn', 'inputBMN'])
                ->make(true);
        }

        $kondisi = FixedAsset::query()
            ->whereNotNull('kondisi')
            ->distinct()
            ->pluck('kondisi')
            ->toArray();
        $conditions = array_combine($kondisi, $kondisi);

        $subcategories = DB::table('sub_categories AS sc')
            ->join('categories AS c', 'sc.categories_id', '=', 'c.id')
            ->select(
                'sc.categories_id as id',
                DB::raw('MAX(sc.nama_sub_kategori) as nama_sub_kategori'),
                DB::raw('MAX(c.nama_kategori) as nama_kategori')
            )
            ->groupBy('sc.categories_id')
            ->get();

        $users = DB::table('users')
            ->select('id', 'nama')
            ->where('role', 'admin')
            ->whereNotNull('division_id')
            ->whereNull('deleted_at')
            ->get();

        return view('pages.data-asset.fixed-assets.index', compact('conditions', 'users', 'subcategories'));
    }

    public function create()
    {
        $category = Category::all();
        $subCategory = SubCategory::all();
        $subCategories = SubCategory::with('category')->get();
        $location = Location::all();
        $division = Division::all();
        $unit = Unit::all();
        $procurement = Procurement::all();
        $specificLocation = SpecialLocation::with('location')->get();
        $user = User::with('division')
            ->where('role', 'admin')
            ->whereNotNull('division_id')
            ->get();

        return view('pages.data-asset.fixed-assets.create', compact(
            'category',
            'subCategories',
            'subCategory',
            'location',
            'specificLocation',
            'procurement',
            'user',
            'unit'
        ));
    }

    public function storeAjax(StoreFixedAssetRequest $request)
    {
        $validatedData = $request->validated();
        $uuid = Uuid::uuid4()->toString();
        $fixedAsset = null;

        try {
            DB::transaction(function () use ($validatedData, $request, $uuid, &$fixedAsset) {
                $fixedAsset = new FixedAsset($validatedData);
                $fixedAsset->id = $uuid;
                $fixedAsset->save();

                $historyData = [
                    'fixed_asset_id' => $fixedAsset->id,
                    'kondisi' => $request->input('kondisi'),
                ];

                if ($request->hasFile('image')) {
                    $file = $request->file('image');
                    $filename = time() . '.' . $file->getClientOriginalName() . '.' . $file->getClientOriginalExtension();
                    $file->storeAs('public/assetImage', $filename);
                    $historyData['image'] = $filename;
                }

                History::create($historyData);

                // Generate QR code SVG (zero-dep) dan simpan referensi
                $fileName = $this->generateQrCode($fixedAsset->id);
                $fixedAsset->update(['qrcode' => $fileName]);
            });
        } catch (\Throwable $e) {
            Log::error('Gagal menyimpan aset tetap', [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat menyimpan data.',
            ], 500);
        }

        return response()->json([
            'success' => true,
            'message' => 'Berhasil menyimpan data',
            'data' => $fixedAsset->refresh(),
        ]);
    }

    public function edit($id)
    {
        $aset = FixedAsset::findOrFail($id);
        $subCategories = SubCategory::with('category')->get();
        $procurement = Procurement::all();
        $specificLocation = SpecialLocation::with('location')->get();
        $user = User::with('division')->whereNotNull('division_id')->get();
        $unit = Unit::all();

        return view('pages.data-asset.fixed-assets.edit', compact(
            'subCategories',
            'specificLocation',
            'procurement',
            'user',
            'aset',
            'unit'
        ));
    }

    public function update(UpdateFixedAssetRequest $request, $id)
    {
        $aset = FixedAsset::findOrFail($id);
        $validatedData = $request->validated();

        try {
            DB::transaction(function () use ($validatedData, $request, $aset) {
                $historyData = [
                    'fixed_asset_id' => $aset->id,
                    'kondisi' => $request->input('kondisi'),
                ];

                if ($request->hasFile('image')) {
                    $file = $request->file('image');
                    $filename = time() . '.' . $file->getClientOriginalName() . '.' . $file->getClientOriginalExtension();
                    $file->storeAs('public/assetImage', $filename);
                    $historyData['image'] = $filename;
                }

                // Hanya catat history kalau kondisi berubah atau ada image baru
                if ($request->input('kondisi') != $aset->kondisi || $request->hasFile('image')) {
                    History::create($historyData);
                }

                $aset->update($validatedData);
            });
        } catch (\Throwable $e) {
            Log::error('Gagal memperbarui aset tetap', [
                'id' => $id,
                'error' => $e->getMessage(),
            ]);

            return redirect()->back()
                ->withInput()
                ->with('error', 'Terjadi kesalahan saat menyimpan data.');
        }

        return redirect()->route('asset-fixed.index')->with('success', 'Berhasil mengubah data');
    }

    public function show($id)
    {
        $data = FixedAsset::with([
            'subcategory.category',
            'specificlocation.location',
            'user',
            'procurement',
            'unit',
            'histories',
        ])->findOrFail($id);

        $latestHistory = $data->histories->sortByDesc('created_at')->first();

        return view('pages.data-asset.fixed-assets.show', compact('data', 'latestHistory'));
    }

    public function destroy(Request $request, $id)
    {
        $fixedAsset = FixedAsset::findOrFail($id);

        try {
            DB::transaction(function () use ($fixedAsset) {
                // Bersihkan file QR code dari storage publik
                if (! empty($fixedAsset->qrcode)) {
                    Storage::disk('public')->delete('qrcodes/' . $fixedAsset->qrcode);
                }

                // Bersihkan file gambar dari storage
                if (! empty($fixedAsset->image)) {
                    Storage::delete('public/assetImage/' . $fixedAsset->image);
                }

                $fixedAsset->delete();
            });
        } catch (\Throwable $e) {
            Log::error('Gagal menghapus aset tetap', [
                'id' => $id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Gagal menghapus data.',
            ], 500);
        }

        return response()->json([
            'success' => true,
            'message' => 'Data berhasil dihapus',
        ]);
    }

    public function DeleteSelectedAsset(Request $request)
    {
        $assetIds = $request->input('fixedasset_id', []);

        if (empty($assetIds)) {
            return response()->json([
                'success' => false,
                'message' => 'Tidak ada data yang dipilih.',
            ], 422);
        }

        try {
            DB::transaction(function () use ($assetIds) {
                $assets = FixedAsset::whereIn('id', $assetIds)->get();

                foreach ($assets as $fixedAsset) {
                    if (! empty($fixedAsset->image)) {
                        Storage::delete('public/assetImage/' . $fixedAsset->image);
                    }

                    if (! empty($fixedAsset->qrcode)) {
                        Storage::disk('public')->delete('qrcodes/' . $fixedAsset->qrcode);
                    }

                    $fixedAsset->delete();
                }
            });
        } catch (\Throwable $e) {
            Log::error('Gagal menghapus aset tetap (batch)', [
                'ids' => $assetIds,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Gagal menghapus data.',
            ], 500);
        }

        return response()->json([
            'success' => true,
            'message' => 'Berhasil menghapus data',
        ]);
    }

    public function DownloadQrCode($id)
    {
        $data = FixedAsset::find($id);

        if (! $data || ! $data->qrcode) {
            return response()->json(['error' => 'QR Code not found'], 404);
        }

        $path = storage_path('app/public/qrcodes/' . $data->qrcode);

        if (! file_exists($path)) {
            return response()->json(['error' => 'QR Code file not found'], 404);
        }

        return response()->download($path, basename($data->qrcode));
    }

    public function DownloadQrCodeLocation($id)
    {
        $data = FixedAsset::with('specificlocation')->findOrFail($id);

        if (! $data->specificlocation || ! $data->specificlocation->qrcode) {
            return response()->json(['error' => 'QR Code not found'], 404);
        }

        $path = storage_path('app/public/qrcodes/locations/' . $data->specificlocation->qrcode);

        if (! file_exists($path)) {
            return response()->json(['error' => 'QR Code file not found'], 404);
        }

        return response()->download($path, basename($data->specificlocation->qrcode));
    }

    public function downloadSelectedQrCodes(Request $request)
    {
        $selectedIds = $request->input('selectedIds', []);
        $zipFileName = 'selected_qrcodes.zip';
        $zipFilePath = storage_path('app/' . $zipFileName);

        $zip = new ZipArchive;
        if ($zip->open($zipFilePath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            Log::error('Failed to create zip file');

            return response()->json(['error' => 'Failed to create zip file'], 500);
        }

        foreach ($selectedIds as $id) {
            $fixedAsset = FixedAsset::with('specificlocation')->find($id);

            if (! $fixedAsset || ! $fixedAsset->qrcode) {
                continue;
            }

            $qrCodePath = storage_path('app/public/qrcodes/' . $fixedAsset->qrcode);
            if (Storage::disk('public')->exists('qrcodes/' . $fixedAsset->qrcode)) {
                $zip->addFile($qrCodePath, 'qrcodes/' . $fixedAsset->qrcode);
            } else {
                Log::error("QR code file not found for fixed asset: {$fixedAsset->qrcode}");
            }

            if ($fixedAsset->specificlocation && $fixedAsset->specificlocation->qrcode) {
                $locQr = $fixedAsset->specificlocation->qrcode;
                $qrCodePathLocations = storage_path('app/public/qrcodes/locations/' . $locQr);
                if (Storage::disk('public')->exists('qrcodes/locations/' . $locQr)) {
                    $zip->addFile($qrCodePathLocations, 'qrcodes/locations/' . $locQr);
                } else {
                    Log::error("Location QR code file not found for specific location: {$locQr}");
                }
            }
        }
        $zip->close();

        return response()->json(['success' => true, 'zipFilePath' => $zipFilePath]);
    }

    public function downloadSelectedQrCodesZip()
    {
        $zipFileName = 'selected_qrcodes.zip';
        $zipFilePath = storage_path('app/' . $zipFileName);

        if (file_exists($zipFilePath)) {
            return response()->download($zipFilePath, $zipFileName)->deleteFileAfterSend(true);
        }

        return response()->json(['error' => 'ZIP file not found'], 404);
    }

    public function import(Request $request)
    {
        $file = $request->file('file');

        if (! $file) {
            return redirect()->back()->with('error', 'File tidak ditemukan.');
        }

        $namaFile = $file->getClientOriginalName();
        $path = public_path('/storage/AssetExcel/' . $namaFile);
        $file->move('storage/AssetExcel', $namaFile);

        try {
            Excel::import(new MultipleSheetImport(), $path);

            return redirect()->back()->with('success', 'Data berhasil diimpor.');
        } catch (\Throwable $e) {
            Log::error('Gagal import data aset', [
                'error' => $e->getMessage(),
            ]);

            return redirect()->back()->with(
                'error',
                'Terjadi kesalahan saat mengimpor data: ' . $e->getMessage()
            );
        }
    }

    public function exportTemplate()
    {
        return new MultipleSheetTemplateExport;
    }
}
