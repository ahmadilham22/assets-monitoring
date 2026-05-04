<?php

namespace App\Http\Controllers\DataMaster\Category;

use App\Http\Controllers\Controller;
use App\Http\Requests\DataMaster\CategoryRequest;
use App\Models\DataMaster\Category;
use Yajra\DataTables\Facades\DataTables;

class CategoryController extends Controller
{
    public function index()
    {
        if (request()->ajax()) {
            $query = Category::query()->orderBy('updated_at', 'desc');

            return DataTables::eloquent($query)
                ->addIndexColumn()
                ->addColumn('action', function (Category $category) {
                    return view(
                        'pages.data-master.category._action.categoryAction',
                        ['data' => $category]
                    )->render();
                })
                ->rawColumns(['action'])
                ->make(true);
        }

        return view('pages.data-master.category.index');
    }

    public function store(CategoryRequest $request)
    {
        $category = Category::create($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Data berhasil ditambahkan',
            'data'    => $category,
        ]);
    }

    public function edit($id)
    {
        $category = Category::find($id);

        if (! $category) {
            return response()->json([
                'success' => false,
                'message' => 'Data tidak ditemukan',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data'    => $category,
        ]);
    }

    public function update(CategoryRequest $request, $id)
    {
        $category = Category::findOrFail($id);
        $category->update($request->validated());

        return response()->json([
            'success' => true,
            'message' => 'Data berhasil diperbarui',
            'data'    => $category->refresh(),
        ]);
    }

    public function destroy($id)
    {
        $category = Category::findOrFail($id);

        if ($category->subCategories()->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Kategori tidak bisa dihapus karena masih memiliki sub kategori.',
            ], 422);
        }

        $category->delete();

        return response()->json([
            'success' => true,
            'message' => 'Data berhasil dihapus',
        ]);
    }
}
