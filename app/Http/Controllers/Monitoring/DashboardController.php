<?php

namespace App\Http\Controllers\Monitoring;

use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;

class DashboardController extends Controller
{
    /**
     * Jumlah kartu user per halaman pada widget "Penanggung Jawab Aset".
     * "View More" akan AJAX-fetch halaman berikutnya.
     */
    private const USERS_PER_PAGE = 3;

    public function index()
    {
        $usersPage = $this->getUsersPage(1);

        return view('pages.dashboard.index', [
            'dataset' => $this->getCategorySummary(),
            'dataUsers' => $usersPage['rows'],
            'nextPageUsers' => $usersPage['nextPage'],
        ]);
    }

    public function getUsers(Request $request)
    {
        if (! $request->ajax()) {
            abort(404);
        }

        $page = max(1, (int) $request->input('page', 1));
        $usersPage = $this->getUsersPage($page);

        $htmlUsers = view('pages.dashboard.data-users', [
            'dataUsers' => $usersPage['rows'],
        ])->render();

        return response()->json([
            'htmlUsers' => $htmlUsers,
            'pageUsers' => $page,
            'perPageUsers' => self::USERS_PER_PAGE,
            'nextPageUsers' => $usersPage['nextPage'],
        ]);
    }

    /**
     * Aggregate jumlah aset kondisi Baik/Rusak per kategori untuk
     * horizontal stacked bar chart.
     *
     * 1 query - fixed_assets di-scan sekali pakai COUNT(CASE WHEN ...).
     * Output:
     * - Hanya kategori yang punya minimal 1 aset (Baik+Rusak > 0).
     * - Diurutkan total descending — kategori paling banyak di atas chart.
     *
     * @return array{labels: array<int,string>, baik: array<int,int>, rusak: array<int,int>}
     */
    private function getCategorySummary(): array
    {
        $rows = DB::table('categories as c')
            ->leftJoin('sub_categories as sc', function ($join) {
                $join->on('sc.categories_id', '=', 'c.id')
                    ->whereNull('sc.deleted_at');
            })
            ->leftJoin('fixed_assets as fa', function ($join) {
                $join->on('fa.sub_category_id', '=', 'sc.id')
                    ->whereNull('fa.deleted_at');
            })
            ->whereNull('c.deleted_at')
            ->groupBy('c.id', 'c.nama_kategori')
            ->select([
                'c.nama_kategori',
                DB::raw("COUNT(CASE WHEN fa.kondisi = 'Baik' THEN 1 END) AS baik"),
                DB::raw("COUNT(CASE WHEN fa.kondisi = 'Rusak' THEN 1 END) AS rusak"),
            ])
            ->get()
            ->map(function ($row) {
                $row->baik = (int) $row->baik;
                $row->rusak = (int) $row->rusak;
                $row->total = $row->baik + $row->rusak;
                return $row;
            })
            ->filter(fn ($row) => $row->total > 0)
            ->sortByDesc('total')
            ->values();

        return [
            'labels' => $rows->pluck('nama_kategori')->all(),
            'baik' => $rows->pluck('baik')->all(),
            'rusak' => $rows->pluck('rusak')->all(),
        ];
    }

    /**
     * Ambil 1 halaman user beserta breakdown jumlah aset per kategori.
     *
     * Anti-N+1: total hanya 2 query untuk N user di halaman ini —
     *   1. SELECT user (paginated). LIMIT perPage+1 dipakai untuk
     *      mendeteksi next page tanpa COUNT(*) terpisah.
     *   2. SELECT aggregate (user_id, category) untuk semua user di page
     *      sekali jalan, lalu di-map ke tiap user di memory.
     *
     * @return array{rows: \Illuminate\Support\Collection, nextPage: int|null}
     */
    private function getUsersPage(int $page): array
    {
        $perPage = self::USERS_PER_PAGE;
        $offset = ($page - 1) * $perPage;

        // (1) Paginate user yang punya minimal 1 fixed_asset valid.
        //     Fetch perPage+1 row — kalau jumlah > perPage berarti masih ada
        //     halaman berikutnya (hindari COUNT(*) terpisah yang mahal).
        $userRows = DB::table('users as u')
            ->join('fixed_assets as fa', function ($j) {
                $j->on('fa.user_id', '=', 'u.id')->whereNull('fa.deleted_at');
            })
            ->join('sub_categories as sc', function ($j) {
                $j->on('sc.id', '=', 'fa.sub_category_id')->whereNull('sc.deleted_at');
            })
            ->join('categories as c', function ($j) {
                $j->on('c.id', '=', 'sc.categories_id')->whereNull('c.deleted_at');
            })
            ->whereNull('u.deleted_at')
            ->groupBy('u.id', 'u.nama')
            ->orderBy('u.id')
            ->limit($perPage + 1)
            ->offset($offset)
            ->select('u.id as user_id', 'u.nama as user_name')
            ->get();

        $hasNext = $userRows->count() > $perPage;
        $users = $userRows->take($perPage)->values();
        $userIds = $users->pluck('user_id');

        if ($userIds->isEmpty()) {
            return ['rows' => collect(), 'nextPage' => null];
        }

        // (2) Aggregate count per (user_id, category) dalam 1 query untuk
        //     semua user di halaman ini.
        $countsByUser = $this->getCategoryCountsForUsers($userIds);

        // (3) Tempelkan breakdown ke tiap user — pure in-memory, no extra query.
        $rows = $users->map(function ($user) use ($countsByUser) {
            $user->category = $countsByUser->get($user->user_id, collect())->values();
            return $user;
        });

        return [
            'rows' => $rows,
            'nextPage' => $hasNext ? ($page + 1) : null,
        ];
    }

    /**
     * COUNT(*) per (user_id, category) untuk daftar user yang diberikan.
     * Hasilnya dikelompokkan per user_id supaya gampang di-attach ke user.
     *
     * @param  \Illuminate\Support\Collection<int,int>  $userIds
     * @return \Illuminate\Support\Collection<int,\Illuminate\Support\Collection<int,\stdClass>>
     */
    private function getCategoryCountsForUsers(Collection $userIds): Collection
    {
        return DB::table('fixed_assets as fa')
            ->join('sub_categories as sc', function ($j) {
                $j->on('sc.id', '=', 'fa.sub_category_id')->whereNull('sc.deleted_at');
            })
            ->join('categories as c', function ($j) {
                $j->on('c.id', '=', 'sc.categories_id')->whereNull('c.deleted_at');
            })
            ->whereIn('fa.user_id', $userIds)
            ->whereNull('fa.deleted_at')
            ->groupBy('fa.user_id', 'c.id', 'c.nama_kategori')
            ->orderBy('c.id')
            ->select([
                'fa.user_id',
                'c.id as category_id',
                'c.nama_kategori as category_name',
                DB::raw('COUNT(fa.id) as category_count'),
            ])
            ->get()
            ->groupBy('user_id');
    }
}
