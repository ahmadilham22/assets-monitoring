@extends('layouts.app')


@section('content')
    <div class="container flex-grow-1 mt-3 w-100">
        <div class="row">
            <div class="col-lg-12 order-0">
                <div class="card">
                    <div class="card-body">
                        <h4 class="mb-4">Aset Tetap</h4>
                        {{-- Filter --}}
                        <div class="row">
                            <div class="col-12">
                                <p class="d-inline-flex gap-2">
                                    <button class="btn btn-secondary" type="button" data-bs-toggle="collapse"
                                        data-bs-target="#collapseExample" aria-expanded="false"
                                        aria-controls="collapseExample">
                                        <i class="fa-solid fa-filter me-2"></i> Filter Data
                                    </button>
                                    <button id="resetFilter" class="btn btn-warning"><i
                                            class="fa-solid fa-arrows-rotate me-2"></i>
                                        Reset
                                        Filter</button>
                                </p>
                                {{-- <button class="btn btn-success dropdown-toggle" type="button" data-bs-toggle="dropdown"
                                    aria-expanded="false"><i class="fa-solid fa-file-export me-2"></i>
                                    Export Data
                                </button>
                                <ul class="dropdown-menu">
                                    <li><a id="sheet1" href="{{ route('report.export') }}" class="dropdown-item">Sheet
                                            1</a></li>
                                </ul> --}}
                                {{-- <button class="btn btn-success dropdown-toggle" type="button" data-bs-toggle="dropdown"
                                    aria-expanded="false"><i class="fa-solid fa-file-export me-2"></i>
                                    Export Data
                                </button> --}}
                                <a id="sheet1" href="{{ route('report.export') }}" class="btn btn-success"><i
                                        class="fa-solid fa-file-export me-2"></i>
                                    Export Data</a>
                                <button type="button" class="btn btn-info" id="downloadReport" disabled><i
                                        class="fa-solid fa-download me-2"></i>
                                    Download
                                </button>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-10">
                                <div class="collapse" id="collapseExample">
                                    <div class="row mt-3 mb-4 d-flex">
                                        <div class="col-lg-3 col-md-6 col-sm-12">
                                            <div class="form-group">
                                                <label class="form-label">Kondisi : </label>
                                                <select id="kondisiFilter" class="form-select form-select-sm filter"
                                                    aria-label="Large select example">
                                                    <option value="">Tampilkan Semua</option>
                                                    @foreach ($conditions as $condition)
                                                        <option value="{{ $condition }}"
                                                            id="condition_{{ $condition }}">{{ $condition }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-lg-3 col-md-6 col-sm-12">
                                            <div class="form-group">
                                                <label class="form-label">Kategori : </label>
                                                <select id="kategoriFilter" class="form-select form-select-sm filter"
                                                    aria-label="Large select example">
                                                    <option value="">Tampilkan Semua</option>
                                                    @foreach ($subcategories as $item)
                                                        <option value="{{ $item->id }}"
                                                            id="id_category_{{ $item->id }}">
                                                            {{ $item->nama_kategori }}
                                                        </option>
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
                                                        <option value="{{ $user->id }}"
                                                            id="id_user_{{ $user->id }}">{{ $user->nama }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-lg-3 col-md-6 col-sm-12">
                                            <div class="form-group">
                                                <label class="form-label">Periode : </label>
                                                <select id="periodeFilter" class="form-select form-select-sm filter"
                                                    aria-label="Large select example">
                                                    <option value="">Tampilkan Semua</option>
                                                    @foreach ($periods as $period)
                                                        <option value="{{ $period }}"
                                                            id="period_{{ $period }}">{{ $period }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="table-responsive text-nowrap mt-2">
                            <table id="myTable" class="table table-bordered table-sm w-100">
                                <thead>
                                    <tr>
                                        <th>
                                            <input name="maincheckboxreport[]" class="form-check-input checkboxreport p-2"
                                                type="checkbox" value="" id="maincheckboxreport">
                                        </th>
                                        <th>No</th>
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
@endsection

@section('js')
    <script>
        // Helper parse error dari FormRequest/JSON response umum:
        // - validation: {message, errors: {field: [msg, ...]}}
        // - generic:    {message}
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
            // Pra-filter dari query string supaya halaman bisa di-link langsung
            // (mis. dari dashboard / grafik).
            var params = new URLSearchParams(window.location.search);
            var kondisi = params.get('kondisi') || '';
            var kategori = params.get('id_category') || '';
            var pj = params.get('id_user') || '';
            var periode = '';
            var sn = [];

            if (kondisi) $('#kondisiFilter').val(kondisi);
            if (kategori) $('#kategoriFilter').val(kategori);
            if (pj) $('#pjFilter').val(pj);

            $.ajaxSetup({
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                }
            });

            // Susun ulang href export dari filter saat ini + daftar sn (kalau ada)
            function buildExportHref() {
                var qs = $.param({
                    kondisi: kondisi,
                    kategori: kategori,
                    pj: pj,
                    periode: periode,
                    sn: sn.length ? sn.join(',') : ''
                });
                $('#sheet1').attr('href', "{{ route('report.export') }}?" + qs);
            }

            // DataTable — server-side via Eloquent
            var table = $('#myTable').DataTable({
                processing: true,
                responsive: true,
                serverSide: true,
                order: [[3, 'asc']],
                lengthMenu: [
                    [10, 25, 50, 100, -1],
                    [10, 25, 50, 100, "All"]
                ],
                ajax: {
                    url: '{{ route('report.index') }}',
                    data: function(d) {
                        d.kondisi = kondisi;
                        d.kategori = kategori;
                        d.pj = pj;
                        d.periode = periode;
                    }
                },
                columns: [
                    { data: 'checkbox', name: 'checkbox', orderable: false, searchable: false },
                    { data: 'DT_RowIndex', name: 'DT_RowIndex', orderable: false, searchable: false },
                    { data: 'kode_sn', name: 'kode_sn' },
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

            // Filter — reload pakai .ajax.reload(null, false) supaya page tidak reset
            $('.filter').on('change', function() {
                kondisi = $('#kondisiFilter').val();
                kategori = $('#kategoriFilter').val();
                pj = $('#pjFilter').val();
                periode = $('#periodeFilter').val();
                buildExportHref();
                table.ajax.reload(null, false);
            });

            // Reset filter
            $('#resetFilter').on('click', function() {
                $('#kondisiFilter').val('');
                $('#kategoriFilter').val('');
                $('#pjFilter').val('');
                $('#periodeFilter').val('');

                kondisi = '';
                kategori = '';
                pj = '';
                periode = '';
                sn = [];
                buildExportHref();
                table.search('').ajax.reload();
            });

            // --- Checkbox & download QR ---

            function resetHeaderCheckbox() {
                $('input[name="maincheckboxreport[]"]').prop('checked', false);
            }

            function syncDownloadButton() {
                var anyChecked = $('input[name="checkboxreport[]"]:checked').length > 0;
                $('button#downloadReport').prop('disabled', !anyChecked);
            }

            // Kumpulkan sn yang dicentang lalu rebuild href export
            function refreshSelectedSn() {
                sn = $('input[name="checkboxreport[]"]:checked').map(function() {
                    return $(this).val();
                }).get();
                buildExportHref();
            }

            // Reset checkbox header tiap redraw & rebuild state
            table.on('draw', function() {
                resetHeaderCheckbox();
                sn = [];
                buildExportHref();
                syncDownloadButton();
            });

            // Master checkbox (di thead) toggle semua row
            $(document).on('change', 'input[name="maincheckboxreport[]"]', function() {
                var checked = this.checked;
                $('input[name="checkboxreport[]"]').prop('checked', checked);
                refreshSelectedSn();
                syncDownloadButton();
            });

            // Individual checkbox → sinkronkan master + download button
            $(document).on('change', 'input[name="checkboxreport[]"]', function() {
                var total = $('input[name="checkboxreport[]"]').length;
                var checked = $('input[name="checkboxreport[]"]:checked').length;
                $('input[name="maincheckboxreport[]"]').prop('checked', total > 0 && total === checked);
                refreshSelectedSn();
                syncDownloadButton();
            });

            // Download QR code (ZIP) untuk asset terpilih
            $(document).off('click', '#downloadReport').on('click', '#downloadReport', function() {
                var $btn = $(this);
                if ($btn.prop('disabled')) {
                    return;
                }

                var selectedIds = $('input[name="checkboxreport[]"]:checked').map(function() {
                    return $(this).val();
                }).get();

                if (selectedIds.length === 0) {
                    return;
                }

                $btn.prop('disabled', true);

                $.ajax({
                    url: '{{ route('download-selected-qrcodes') }}',
                    type: 'POST',
                    data: { selectedIds: selectedIds },
                    success: function() {
                        window.location.href = '{{ route('download-selected-qrcodes-zip') }}';
                    },
                    error: function(xhr) {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: parseAjaxError(xhr, 'Gagal menyiapkan QR code.'),
                        });
                    },
                    complete: function() {
                        syncDownloadButton();
                    }
                });
            });

            // Initial state
            buildExportHref();
        });
    </script>
    @include('pages.data-asset.fixed-assets._function.function')
@endsection

@push('addon-script')
@endpush
