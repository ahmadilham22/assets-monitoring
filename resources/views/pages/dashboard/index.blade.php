@extends('layouts.app')

@section('css')
    <style>
        .swal2-container {
            z-index: 1000;
        }

        .chartBox {
            width: 580px;
            border-radius: 20px;
        }
    </style>
@endsection

@section('content')
    <div class="container flex-grow-1 container-p-y">
        <div class="row mb-3">
            <div class="col-lg-12 mb-3 order-0">
                <div class="card">
                    <div class="d-flex align-items-end">
                        <div class="col-sm-7">
                            <div class="card-body">
                                <h4 class="card-title text-primary mb-5">
                                    Selamat Datang, {{ Auth::user()->nama }}! 🎉
                                </h4>
                            </div>
                        </div>
                        <div class="col-sm-5 text-center text-sm-left">
                            <div class="card-body pb-0 px-0 px-md-4">
                                <img src="../assets/img/illustrations/man-with-laptop-light.png" height="140"
                                    alt="View Badge User" data-app-dark-img="illustrations/man-with-laptop-dark.png"
                                    data-app-light-img="illustrations/man-with-laptop-light.png" />
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            {{-- Chart Kondisi Aset per Kategori --}}
            <div class="col-md-12 mb-4">
                <div class="card">
                    <div class="card-header">
                        <h4>Kondisi Aset per Kategori</h4>
                    </div>
                    <div class="card-body">
                        @if (empty($dataset['labels']))
                            <p class="text-center my-5">No data found</p>
                        @else
                            {{-- Tinggi canvas dihitung berdasarkan jumlah kategori
                                 (28px/baris + padding) supaya bar tetap jelas dibaca
                                 walau kategori banyak. Wrapper di-set scrollable
                                 sebagai safety net. --}}
                            <div style="max-height: 520px; overflow-y: auto;">
                                <div style="height: {{ max(220, count($dataset['labels']) * 28 + 60) }}px;">
                                    <canvas id="kondisiChart"></canvas>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
            {{-- /Chart --}}

            {{-- Kartu Penanggung Jawab --}}
            <div class="col-md-12 mb-4">
                <div class="card">
                    <div class="card-header">
                        <h4>User</h4>
                    </div>
                    <div class="card-body">
                        <div class="row data-user" id="data-wrapper-users">
                            @include('pages.dashboard.data-users')
                        </div>
                        <div class="text-center">
                            <button class="btn btn-primary load-more-data-users"
                                @if ($nextPageUsers === null) style="display:none;" @endif>
                                View More
                            </button>
                            <button class="btn btn-danger load-less-data-users" style="display:none;">
                                View Less
                            </button>
                        </div>
                    </div>
                </div>
            </div>
            {{-- /Kartu Penanggung Jawab --}}
        </div>
    </div>
@endsection

@section('js')
    {{-- Chart.js (UMD bundle — sudah include semua adapter dasar) --}}
    <script src="https://cdn.jsdelivr.net/npm/chart.js/dist/chart.umd.min.js"></script>

    <script>
        // --- Horizontal Stacked Bar Chart ---
        (function () {
            var dataChart = {!! json_encode($dataset) !!};
            var canvas = document.getElementById('kondisiChart');
            if (!canvas || !dataChart.labels.length) return;

            new Chart(canvas, {
                type: 'bar',
                data: {
                    labels: dataChart.labels,
                    datasets: [
                        {
                            label: 'Baik',
                            data: dataChart.baik,
                            backgroundColor: '#28c76f',
                            borderWidth: 0,
                        },
                        {
                            label: 'Rusak',
                            data: dataChart.rusak,
                            backgroundColor: '#ea5455',
                            borderWidth: 0,
                        }
                    ]
                },
                options: {
                    indexAxis: 'y',           // bar horizontal
                    responsive: true,
                    maintainAspectRatio: false,
                    interaction: { intersect: false, mode: 'index' },
                    scales: {
                        x: {
                            stacked: true,
                            beginAtZero: true,
                            ticks: { precision: 0 },
                        },
                        y: { stacked: true }
                    },
                    plugins: {
                        legend: { position: 'top' },
                        tooltip: {
                            callbacks: {
                                footer: function (items) {
                                    var total = items.reduce(function (sum, it) {
                                        return sum + it.parsed.x;
                                    }, 0);
                                    return 'Total: ' + total;
                                }
                            }
                        }
                    }
                }
            });
        })();

        // --- Pagination kartu User ---
        $(document).ready(function () {
            var pageUsers = 1;
            var originalDataUsers = $('#data-wrapper-users').html();

            $.ajaxSetup({
                headers: { 'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content') }
            });

            $('.load-more-data-users').on('click', function () {
                pageUsers++;

                $.ajax({
                    url: '{{ route('home.users') }}',
                    method: 'GET',
                    data: { page: pageUsers },
                    success: function (response) {
                        $('#data-wrapper-users').append(response.htmlUsers);

                        if (response.nextPageUsers === null) {
                            $('.load-more-data-users').hide();
                            $('.load-less-data-users').show();
                        }
                    },
                    error: function (xhr) {
                        console.error('Gagal memuat data user:', xhr.statusText);
                    }
                });
            });

            $('.load-less-data-users').on('click', function () {
                pageUsers = 1;
                $('.load-less-data-users').hide();
                $('.load-more-data-users').show();
                $('#data-wrapper-users').html(originalDataUsers);
            });
        });
    </script>

    @if (session('success'))
        <script>
            Swal.fire({
                icon: 'success',
                title: 'Selamat Datang',
                text: '{{ session('success') }}',
                showConfirmButton: true,
                timer: 3000,
            });
        </script>
    @endif
@endsection
