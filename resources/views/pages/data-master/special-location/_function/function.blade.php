<script>
    $(document).ready(function() {
        // Select2 untuk dropdown Lokasi Umum di dalam modal
        $('#location_id').select2({
            placeholder: 'Pilih...',
            dropdownParent: $('#specificLocation-modal'),
        });

        // Reset form setiap kali modal ditutup, supaya state tidak bocor
        // antar "Tambah" dan "Edit"
        $('#specificLocation-modal').on('hidden.bs.modal', function() {
            $('#specificLocationForm')[0].reset();
            $('#id').val('');
            $('#location_id').val(null).trigger('change');
            $('#btn-save').prop('disabled', false).html('Save changes');
        });

        // Submit handler: .off() dulu supaya tidak akumulasi kalau script
        // ter-include lebih dari sekali, dan guard double-submit via disable button
        $('#specificLocationForm').off('submit').on('submit', function(e) {
            e.preventDefault();

            var $btn = $('#btn-save');
            if ($btn.prop('disabled')) {
                return; // sudah ada request yang jalan
            }
            $btn.prop('disabled', true).html('Menyimpan...');

            var formData = new FormData(this);

            $.ajax({
                type: 'POST',
                url: "{{ route('special-location.store') }}",
                data: formData,
                cache: false,
                contentType: false,
                processData: false,
                success: function(response) {
                    $('#specificLocation-modal').modal('hide');
                    $('#myTable').DataTable().ajax.reload(null, false);

                    if (response.success) {
                        Swal.fire({
                            toast: true,
                            position: 'top-end',
                            timer: 2000,
                            icon: 'success',
                            title: 'Success',
                            text: response.message,
                            showConfirmButton: false,
                        });
                    }
                },
                error: function(xhr) {
                    var message = 'Terjadi kesalahan.';
                    if (xhr.responseJSON) {
                        if (xhr.responseJSON.errors) {
                            // Shape Laravel FormRequest 422
                            message = Object.values(xhr.responseJSON.errors)
                                .map(function(arr) { return arr[0]; })
                                .join('\n');
                        } else if (xhr.responseJSON.message) {
                            message = xhr.responseJSON.message;
                        }
                    }

                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: message,
                    });
                },
                complete: function() {
                    $btn.prop('disabled', false).html('Save changes');
                }
            });
        });
    });

    function add() {
        $('#specificLocationForm')[0].reset();
        $('#id').val('');
        $('#location_id').val(null).trigger('change');
        $('#modalHeader').html('Tambah Sub Lokasi');
        $('#specificLocation-modal').modal('show');
    }

    function editFunc(id) {
        $.ajax({
            type: 'POST',
            url: "{{ route('special-location.edit') }}",
            data: {
                id: id
            },
            dataType: 'json',
            success: function(res) {
                if (!res.success) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: res.message || 'Data tidak ditemukan',
                    });
                    return;
                }

                var data = res.data;
                $('#modalHeader').html('Edit Sub Lokasi');
                $('#id').val(data.id);
                $('#kode_lokasi').val(data.kode_lokasi);
                $('#lokasi_khusus').val(data.lokasi_khusus);
                $('#location_id').val(data.location_id).trigger('change');
                $('#specificLocation-modal').modal('show');
            }
        });
    }

    function deleteFunc(id) {
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
                url: "{{ route('special-location.destroy') }}",
                data: {
                    id: id
                },
                dataType: 'json',
                success: function(res) {
                    $('#myTable').DataTable().ajax.reload(null, false);
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
                    var message = 'Gagal menghapus data.';
                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        message = xhr.responseJSON.message;
                    }
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: message,
                    });
                }
            });
        });
    }
</script>
