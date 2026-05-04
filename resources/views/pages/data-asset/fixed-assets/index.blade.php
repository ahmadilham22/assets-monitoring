@extends('layouts.app')

@section('css')
    {{-- <link href="//cdnjs.cloudflare.com/ajax/libs/x-editable/1.5.0/jquery-editable/css/jquery-editable.css" rel="stylesheet" />
    <script src="//cdnjs.cloudflare.com/ajax/libs/x-editable/1.5.0/jquery-editable/js/jquery-editable-poshytip.min.js">
    </script> --}}
@endsection


@section('content')
    <div class="container flex-grow-1 mt-3 w-100">
        <div class="row">
            <div class="col-lg-12 order-0">
                <div class="card">
                    <div class="card-body">
                        <h4 class="mb-4">Aset Tetap</h4>
                        @if (Auth::user()->role == 'super_admin' || Auth::user()->role == 'admin')
                            <div class="row">
                                <div class="col-12">
                                    <p class="d-inline-flex gap-2">
                                        <a href="{{ route('asset-fixed.create') }}" class="btn btn-primary mt-2"><i
                                                class="fa-solid fa-plus me-2"></i> Tambah
                                            Data
                                        </a>
                                    </p>
                                    <button class="btn btn-secondary" type="button" data-bs-toggle="collapse"
                                        data-bs-target="#collapseExample" aria-expanded="false"
                                        aria-controls="collapseExample">
                                        <i class="fa-solid fa-filter me-2"></i> Filter Data
                                    </button>
                                    <button id="resetFilter" class="btn btn-warning"><i
                                            class="fa-solid fa-arrows-rotate me-2"></i>
                                        Reset
                                        Filter</button>
                                    {{-- <button type="button" class="btn btn-success" data-bs-toggle="modal"
                                        data-bs-target="#ImportData">
                                        <i class="fa-solid fa-file-import me-2"></i>
                                        Import Data
                                    </button> --}}
                                    @if (Auth::user()->role == 'super_admin')
                                        <button id="deleteAsset" class="btn btn-danger" disabled><i
                                                class="fa-solid fa-trash me-2"></i>
                                            Delete Data</button>
                                    @endif
                                </div>
                            </div>
                        @endif
                        <div class="collapse" id="collapseExample">
                            <div class="row d-flex">
                                <div class="col-lg-3 col-md-6 col-sm-12">
                                    <div class="form-group">
                                        <label class="form-label">Kondisi : </label>
                                        <select id="kondisiFilter" class="form-select form-select-sm filter"
                                            aria-label="Large select example">
                                            <option value="">Tampilkan Semua</option>
                                            @foreach ($conditions as $condition)
                                                <option value="{{ $condition }}" id="condition_{{ $condition }}">
                                                    {{ $condition }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                <div
                                    class="col-lg-3
                                                    col-md-6 col-sm-12">
                                    <div class="form-group">
                                        <label class="form-label">Kategori : </label>
                                        <select id="kategoriFilter" class="form-select form-select-sm filter"
                                            aria-label="Large select example">
                                            <option value="">Tampilkan Semua</option>
                                            @foreach ($subcategories as $item)
                                                <option value="{{ $item->id }}" id="id_category_{{ $item->id }}">
                                                    {{ $item->nama_kategori }} </option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                                <div class="col-lg-3 col-md-6 col-sm-12">
                                    <div class="form-group">
                                        <label class="form-label">Penanggung Jawab : </label>
                                        <select id="pjFilter" class="form-select form-select-sm filter"
                                            aria-label="Large select example">
                                            <option value="">Tampilkan Semua</option>
                                            @foreach ($users as $user)
                                                <option value="{{ $user->id }}" id="id_user_{{ $user->id }}">
                                                    {{ $user->nama }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="table-responsive text-nowrap mt-2">
                            <table id="myTable" class="table table-bordered table-sm w-100">
                                <thead>
                                    <tr>
                                        <th>
                                            <input class="form-check-input p-2" type="checkbox" value=""
                                                id="main_checkbox" name="main_checkbox">
                                        </th>
                                        <th>No</th>
                                        <th>Kode BMN</th>
                                        <th>Kode SN</th>
                                        <th>Kategori</th>
                                        <th>Sub Kategori</th>
                                        <th>Lokasi</th>
                                        <th>Kondisi</th>
                                        <th>Penanggung Jawab</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody class="table-border-bottom-0">
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @include('pages.data-asset.fixed-assets.action.modal-import')
@endsection

@section('js')
    <script>
        // Helper untuk parse error dari FormRequest:
        // - validation error: {message, errors: {field: [msg, ...]}}
        // - other error: {message}
        function parseAjaxError(xhr, fallback) {
            var message = fallback || 'Terjadi kesalahan.';
            if (xhr && xhr.responseJSON) {
                if (xhr.responseJSON.errors) {
                    message = Object.values(xhr.responseJSON.errors)
                        .map(function(arr) { return arr[0]; })
                        .join('\n');
                } else if (xhr.responseJSON.message) {
                    message = xhr.responseJSON.message;
                }
            }
            return message;
        }

        $(document).ready(function() {
            // Ambil pra-filter dari query string supaya halaman bisa di-link langsung
            var params = new URLSearchParams(window.location.search);
            var kondisi = params.get('kondisi') || '';
            var kategori = params.get('id_category') || '';
            var pj = params.get('id_user') || '';

            if (kategori) {
                $('#kategoriFilter').val(kategori);
            }
            if (pj) {
                $('#pjFilter').val(pj);
            }
            if (kondisi) {
                $('#kondisiFilter').val(kondisi);
            }

            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                }
            });

            // Toggle tombol "Delete Data" berdasarkan jumlah checkbox terpilih
            function syncDeleteAllButton() {
                var anyChecked = $('input[name="fixedassetcheckbox"]:checked').length > 0;
                $('button#deleteAsset').prop('disabled', !anyChecked);
            }

            function resetHeaderCheckbox() {
                $('input[name="main_checkbox"]').prop('checked', false);
            }

            // Setting up DataTable (server-side)
            var table = $('#myTable').DataTable({
                processing: true,
                responsive: true,
                serverSide: true,
                order: [[4, 'asc']],
                lengthMenu: [
                    [10, 25, 50, 100, -1],
                    [10, 25, 50, 100, "All"]
                ],
                ajax: {
                    url: '{{ route('asset-fixed.index') }}',
                    data: function(d) {
                        d.kondisi = kondisi;
                        d.kategori = kategori;
                        d.pj = pj;
                    }
                },
                columns: [
                    { data: 'checkbox', name: 'checkbox', orderable: false, searchable: false },
                    { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false },
                    { data: 'inputBMN', name: 'inputBMN', orderable: false, searchable: false },
                    { data: 'inputSn', name: 'inputSn', orderable: false, searchable: false },
                    {
                        data: 'subcategory.category.nama_kategori',
                        name: 'subcategory.category.nama_kategori',
                    },
                    {
                        data: 'subcategory.nama_sub_kategori',
                        name: 'subcategory.nama_sub_kategori',
                    },
                    {
                        data: 'specificlocation.location.lokasi_umum',
                        name: 'specificlocation.location.lokasi_umum',
                    },
                    { data: 'kondisi', name: 'kondisi' },
                    { data: 'user.nama', name: 'user.nama' },
                    { data: 'action', name: 'action', orderable: false, searchable: false },
                ],
            });

            $.fn.editable.defaults.mode = 'inline';

            // Bind x-editable setelah tiap draw (sekali per draw, bukan dua handler terpisah)
            table.on('draw.dt', function() {
                $('.editablesn').each(function() {
                    $(this).editable({
                        url: "{{ route('asset-fixed.update.sn') }}",
                        type: 'text',
                        name: 'kode_sn',
                        title: 'Masukan data',
                        emptytext: '<input type="text" placeholder="Masukan Kode" style="width:110px"/>',
                        success: function(response) {
                            Swal.fire({
                                toast: true,
                                position: 'top-end',
                                timer: 2000,
                                icon: 'success',
                                title: 'Success',
                                text: response.message,
                                showConfirmButton: false,
                            });
                        },
                        error: function(xhr) {
                            // x-editable expect string return = error message yang ditampilkan inline
                            return parseAjaxError(xhr, 'Gagal menyimpan kode SN.');
                        }
                    });
                });

                $('.editablebmn').each(function() {
                    $(this).editable({
                        url: "{{ route('asset-fixed.update.bmn') }}",
                        type: 'text',
                        name: 'kode_bmn',
                        title: 'Masukan data',
                        emptytext: '<input type="text" placeholder="Masukan Kode" style="width:110px"/>',
                        success: function(response) {
                            Swal.fire({
                                toast: true,
                                position: 'top-end',
                                timer: 2000,
                                icon: 'success',
                                title: 'Success',
                                text: response.message,
                                showConfirmButton: false,
                            });
                        },
                        error: function(xhr) {
                            return parseAjaxError(xhr, 'Gagal menyimpan kode BMN.');
                        }
                    });
                });
            });

            // Filter
            $('.filter').on('change', function() {
                kondisi = $('#kondisiFilter').val();
                kategori = $('#kategoriFilter').val();
                pj = $('#pjFilter').val();
                table.ajax.reload(null, false);
            });

            // Reset filter
            $('#resetFilter').on('click', function() {
                $('#kondisiFilter').val('');
                $('#kategoriFilter').val('');
                $('#pjFilter').val('');
                kondisi = '';
                kategori = '';
                pj = '';
                table.search('').ajax.reload();
            });

            // Reset checkbox header tiap kali tabel di-redraw
            table.on('draw', function() {
                resetHeaderCheckbox();
                syncDeleteAllButton();
            });

            // Hapus individual
            $(document).off('click', '#delete_asset').on('click', '#delete_asset', function(e) {
                e.preventDefault();

                var assetId = $(this).val();
                Swal.fire({
                    title: 'Apakah Anda yakin?',
                    text: 'Data yang dihapus tidak dapat dikembalikan!',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#3085d6',
                    confirmButtonText: 'Ya, Hapus!',
                    cancelButtonText: 'Batal'
                }).then(function(result) {
                    if (!result.isConfirmed) {
                        return;
                    }

                    $.ajax({
                        type: 'DELETE',
                        url: "{{ route('asset-fixed.destroy', ['id' => '__ID__']) }}".replace('__ID__', assetId),
                        dataType: 'json',
                        success: function(res) {
                            table.ajax.reload(null, false);
                            Swal.fire({
                                toast: true,
                                position: 'top-end',
                                timer: 2000,
                                icon: 'success',
                                title: 'Success',
                                text: res.message,
                                showConfirmButton: false,
                            });
                        },
                        error: function(xhr) {
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: parseAjaxError(xhr, 'Gagal menghapus data.'),
                            });
                        }
                    });
                });
            });

            // Master checkbox: check/uncheck semua
            $(document).on('change', 'input[name="main_checkbox"]', function() {
                var checked = this.checked;
                $('input[name="fixedassetcheckbox"]').prop('checked', checked);
                syncDeleteAllButton();
            });

            // Sinkronisasi master checkbox dengan checkbox individual
            $(document).on('change', 'input[name="fixedassetcheckbox"]', function() {
                var total = $('input[name="fixedassetcheckbox"]').length;
                var checked = $('input[name="fixedassetcheckbox"]:checked').length;
                $('input[name="main_checkbox"]').prop('checked', total > 0 && total === checked);
                syncDeleteAllButton();
            });

            // Hapus terpilih (batch)
            $(document).off('click', 'button#deleteAsset').on('click', 'button#deleteAsset', function() {
                var $btn = $(this);
                if ($btn.prop('disabled')) {
                    return;
                }

                var checkedAsset = [];
                $('input[name="fixedassetcheckbox"]:checked').each(function() {
                    checkedAsset.push($(this).data('id'));
                });

                if (checkedAsset.length === 0) {
                    return;
                }

                Swal.fire({
                    title: 'Apakah Anda yakin?',
                    text: 'Data yang dihapus tidak dapat dikembalikan!',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#3085d6',
                    confirmButtonText: 'Ya, Hapus!',
                    cancelButtonText: 'Batal'
                }).then(function(result) {
                    if (!result.isConfirmed) {
                        return;
                    }

                    $btn.prop('disabled', true);

                    $.ajax({
                        type: 'POST',
                        url: "{{ route('asset-fixed.destroy.selected') }}",
                        data: { fixedasset_id: checkedAsset },
                        dataType: 'json',
                        success: function(data) {
                            if (data.success) {
                                table.ajax.reload(null, false);
                                Swal.fire({
                                    toast: true,
                                    position: 'top-end',
                                    timer: 2000,
                                    icon: 'success',
                                    title: 'Success',
                                    text: data.message,
                                    showConfirmButton: false,
                                });
                            }
                        },
                        error: function(xhr) {
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: parseAjaxError(xhr, 'Gagal menghapus data.'),
                            });
                        },
                        complete: function() {
                            // syncDeleteAllButton() akan handle status enable/disable
                            // setelah table.ajax.reload selesai (event 'draw')
                        }
                    });
                });
            });
        });
    </script>
    <script></script>
    @if (session('success'))
        <script>
            // Menampilkan SweetAlert jika session flash success ada
            Swal.fire({
                toast: true,
                position: "top-end",
                timer: 2000,
                icon: 'success',
                title: 'Berhasil',
                text: '{{ session('success') }}',
                showConfirmButton: false,
            });
        </script>
    @endif
    @if (session('error'))
        <script>
            // Menampilkan SweetAlert jika session flash success ada
            Swal.fire({
                toast: true,
                position: "top-end",
                timer: 20000,
                icon: 'success',
                title: 'Error',
                text: '{{ session('error') }}',
                showConfirmButton: true,
            });
        </script>
    @endif
    @include('pages.data-asset.fixed-assets._function.function')
@endsection
