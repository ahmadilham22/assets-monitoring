<?php

namespace App\Http\Controllers\Report;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Exports\Report\ReportExport;
use App\Http\Controllers\Controller;
use App\Models\DataAsset\FixedAsset;
use Maatwebsite\Excel\Facades\Excel;
use Yajra\DataTables\Facades\DataTables;

class ReportController extends Controller
{
    public function index(Request $request)
    {
        if ($request->ajax()) {
            // Server-side DataTables via Eloquent.
            // Select eksplisit supaya Postgres tidak "ambiguous column" ketika
            // DataTables JOIN tabel relasi untuk sorting/search.
            $query = FixedAsset::with([
                'subcategory.category',
                'specificlocation.location',
                'user',
                'procurement',
            ])
                ->select('fixed_assets.*')
                ->orderBy('fixed_assets.updated_at', 'desc');

            // Filter di level query, bukan Collection::where() (yang kemarin bug
            // karena pakai dotted path).
            if ($request->filled('kondisi')) {
                $query->where('fixed_assets.kondisi', $request->input('kondisi'));
            }

            if ($request->filled('kategori')) {
                $kategori = $request->input('kategori');
                $query->whereHas('subcategory', function ($q) use ($kategori) {
                    $q->where('categories_id', $kategori);
                });
            }

            if ($request->filled('pj')) {
                $query->where('fixed_assets.user_id', $request->input('pj'));
            }

            if ($request->filled('periode')) {
                $query->where('fixed_assets.tahun_perolehan', $request->input('periode'));
            }

            return DataTables::eloquent($query)
                ->addColumn('action', function ($data) {
                    return view('pages.report._action.reportAction', compact('data'));
                })
                ->addColumn('checkbox', function ($data) {
                    return view('pages.report._action.chechbox', compact('data'));
                })
                ->rawColumns(['action', 'checkbox'])
                ->addIndexColumn()
                ->make(true);
        }

        // Data untuk dropdown filter
        $kondisi = FixedAsset::query()
            ->whereNotNull('kondisi')
            ->distinct()
            ->pluck('kondisi')
            ->toArray();

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
            ->get();

        $periode = FixedAsset::query()
            ->whereNotNull('tahun_perolehan')
            ->distinct()
            ->pluck('tahun_perolehan')
            ->toArray();

        $conditions = array_combine($kondisi, $kondisi);
        $periods = array_combine($periode, $periode);

        return view('pages.report.index', compact('conditions', 'users', 'subcategories', 'periods'));
    }

    public function listPublic(Request $request)
    {
        if ($request->ajax()) {
            $query = FixedAsset::with([
                'subcategory.category',
                'specificlocation.location',
                'user',
                'procurement',
            ])
                ->select('fixed_assets.*')
                ->orderBy('fixed_assets.updated_at', 'desc');

            if ($request->filled('kode_lokasi')) {
                $kodeLokasi = $request->query('kode_lokasi');
                $query->whereHas('specificlocation', function ($q) use ($kodeLokasi) {
                    $q->where('kode_lokasi', $kodeLokasi);
                });
            }

            return DataTables::eloquent($query)
                ->addIndexColumn()
                ->make(true);
        }

        return view('pages.report.list-public');
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

        return view('pages.report.show', compact('data', 'latestHistory'));
    }

    public function showPublic($id)
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

        return view('pages.report.show-public', compact('data', 'latestHistory'));
    }

    public function export(Request $request)
    {
        // Ambil filter dari query params yang disusun JS ketika user pilih filter
        // atau centang checkbox di tabel.
        $kategori = $request->query('kategori');
        $kondisi = $request->query('kondisi');
        $pj = $request->query('pj');
        $periode = $request->query('periode');
        $id = $request->query('sn');

        $idArray = $id ? explode(',', $id) : [];

        $params = [$kategori, $kondisi, $pj, $idArray, $periode];

        return Excel::download(new ReportExport($params), 'data.xlsx');
    }
}
