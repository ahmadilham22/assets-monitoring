<?php

namespace App\Http\Controllers\DataMaster\SubCategory;

use App\Http\Controllers\Controller;
use App\Http\Requests\DataMaster\SubCategoryRequest;
use App\Models\DataMaster\Category;
use App\Models\DataMaster\SubCategory;
use Yajra\DataTables\Facades\DataTables;

class SubCategoryController extends Controller
{
    public function index()
    {
        if (request()->ajax()) {
            $query = SubCategory::query()
                ->with('category:id,nama_kategori')
                ->orderBy('updated_at', 'desc');

            return DataTables::eloquent($query)
                ->addIndexColumn()
                ->addColumn('action', function (SubCategory $subCategory) {
                    return view('pages.data-master.sub-category._action.subCategoryAction', [
                        'data' => $subCategory,
                    ])->render();
                })
                ->rawColumns(['action'])
                ->make(true);
        }

        $data = Category::orderBy('nama_kategori')->get(['id', 'nama_kategori']);

        return view('pages.data-master.sub-category.index', compact('data'));
    }

    public function store(SubCategoryRequest $request)
    {
        $subCategory = SubCategory::create($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Data berhasil ditambahkan',
            'data' => $subCategory,
        ]);
    }

    public function edit($id)
    {
        $subCategory = SubCategory::find($id);

        if (! $subCategory) {
            return response()->json([
                'success' => false,
                'message' => 'Data tidak ditemukan',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $subCategory,
        ]);
    }

    public function update(SubCategoryRequest $request, $id)
    {
        $subCategory = SubCategory::findOrFail($id);
        $subCategory->update($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Data berhasil diperbarui',
            'data' => $subCategory->refresh(),
        ]);
    }

    public function destroy($id)
    {
        $subCategory = SubCategory::findOrFail($id);

        if ($subCategory->fixedAssets()->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Sub kategori tidak bisa dihapus karena masih digunakan oleh aset tetap.',
            ], 422);
        }

        $subCategory->delete();

        return response()->json([
            'success' => true,
            'message' => 'Data berhasil dihapus',
        ]);
    }
}
