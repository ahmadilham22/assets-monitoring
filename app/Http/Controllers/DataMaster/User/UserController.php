<?php

namespace App\Http\Controllers\DataMaster\User;

use App\Http\Controllers\Controller;
use App\Http\Requests\DataMaster\StoreUserRequest;
use App\Http\Requests\DataMaster\UpdateUserRequest;
use App\Models\DataMaster\Division;
use App\Models\DataMaster\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Ramsey\Uuid\Uuid;
use Yajra\DataTables\Facades\DataTables;

class UserController extends Controller
{
    public function index()
    {
        if (request()->ajax()) {
            $query = User::query()
                ->with('division:id,nama_divisi')
                ->orderBy('nama', 'asc');

            return DataTables::eloquent($query)
                ->addIndexColumn()
                ->addColumn('action', function (User $user) {
                    return view('pages.data-master.user._actions.usersAction', [
                        'data' => $user,
                    ])->render();
                })
                ->rawColumns(['action'])
                ->make(true);
        }

        $division = Division::orderBy('nama_divisi')->get(['id', 'nama_divisi']);

        return view('pages.data-master.user.index', compact('division'));
    }

    public function store(StoreUserRequest $request)
    {
        $user = User::create([
            'id' => Uuid::uuid4()->toString(),
            'nama' => $request->input('nama'),
            'email' => $request->input('email'),
            'password' => Hash::make($request->input('password')),
            'role' => $request->input('role'),
            'division_id' => $request->input('division_id'),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Data berhasil ditambahkan',
            'data' => $user,
        ]);
    }

    public function edit($id)
    {
        $user = User::find($id);

        if (! $user) {
            return response()->json([
                'success' => false,
                'message' => 'Data tidak ditemukan',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $user,
        ]);
    }

    public function update(UpdateUserRequest $request, $id)
    {
        $user = User::findOrFail($id);

        $user->fill([
            'nama' => $request->input('nama'),
            'email' => $request->input('email'),
            'role' => $request->input('role'),
            'division_id' => $request->input('division_id'),
        ]);

        if ($request->filled('password')) {
            $user->password = Hash::make($request->input('password'));
        }

        $user->save();

        return response()->json([
            'success' => true,
            'message' => 'Data berhasil diperbarui',
            'data' => $user->refresh(),
        ]);
    }

    public function destroy($id)
    {
        $user = User::findOrFail($id);

        if (Auth::id() === $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Tidak bisa menghapus akun yang sedang login.',
            ], 422);
        }

        if ($user->fixedAssets()->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'User tidak bisa dihapus karena masih menjadi penanggung jawab aset tetap.',
            ], 422);
        }

        $user->delete();

        return response()->json([
            'success' => true,
            'message' => 'Data berhasil dihapus',
        ]);
    }

    public function detail($id)
    {
        $data = User::with('division')->findOrFail($id);
        $divisions = Division::orderBy('nama_divisi')->get();

        return view('pages.data-master.user.user-detail', compact('data', 'divisions'));
    }

    public function updateProfile(Request $request, $id)
    {
        $validated = $request->validate([
            'nama' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'division_id' => ['nullable', 'integer', 'exists:divisions,id'],
            'jabatan' => ['nullable', 'string', 'max:255'],
            'photo' => ['nullable', 'image', 'mimes:jpeg,png,jpg,gif', 'max:2048'],
            'password' => ['nullable', 'string', 'min:8'],
        ]);

        $user = User::findOrFail($id);
        $user->fill(collect($validated)->except(['photo', 'password'])->toArray());

        if ($request->hasFile('photo')) {
            // Hapus foto lama
            if ($user->photo) {
                Storage::delete('public/userImage/' . $user->photo);
            }

            $file = $request->file('photo');
            $filename = time() . '.' . $file->getClientOriginalExtension();
            $file->storeAs('public/userImage', $filename);
            $user->photo = $filename;
        }

        if ($request->filled('password')) {
            $user->password = Hash::make($request->input('password'));
        }

        $user->save();

        return back()->with('success', 'Berhasil memperbarui data');
    }
}
