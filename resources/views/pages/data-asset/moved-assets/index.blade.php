@extends('layouts.app')


@section('content')
    <div class="container flex-grow-1 mt-3 w-100">
        <div class="row">
            <div class="col-lg-12 order-0">
                <div class="card">
                    <div class="card-body">
                        <h4 class="mb-4">Aset Berjalan</h4>
                        <a href="{{ route('asset-moved.create') }}" class="btn btn-primary">Tambah
                            Data
                        </a>
                        <div class="table-responsive text-nowrap mt-2">
                            <table id="myTable" class="table table-bordered table-sm w-100">
                                <thead>
                                    <tr>
                                        <th>No</th>
                                        <th>Nama</th>
                                        <th>Kategori</th>
                                        <th>Jumlah Barang</th>
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

@push('addon-script')
    <script>
        $(document).ready(function() {
            $('#myTable').DataTable({
                processing: true,
                responsive: true,
                serverSide: true,
                order: [[1, 'asc']],
                ajax: "{{ route('asset-moved.index') }}",
                columns: [{
                        data: 'DT_RowIndex',
                        name: 'DT_RowIndex',
                        orderable: false,
                        searchable: false,
                    }, {
                        data: 'nama',
                        name: 'nama'
                    },
                    {
                        data: 'kategori',
                        name: 'kategori'
                    },
                    {
                        data: 'jumlah_barang',
                        name: 'jumlah_barang',
                    },
                    {
                        data: 'lokasi',
                        name: 'lokasi'
                    },
                    {
                        data: 'kondisi',
                        name: 'kondisi'
                    },
                    {
                        data: 'penanggung_jawab',
                        name: 'penanggung_jawab'
                    },
                    {
                        data: 'action',
                        name: 'action',
                        orderable: false,
                        searchable: false,
                    },
                ],
            });
        });
    </script>
@endpush
