<script>
    $(document).ready(function() {
        // Reset form setiap kali modal ditutup supaya state tidak bocor
        $('#unit-model').on('hidden.bs.modal', function() {
            $('#unitForm')[0].reset();
            $('#id').val('');
            $('#btn-save').prop('disabled', false).html('Save changes');
        });

        // Submit handler: off().on() supaya tidak akumulasi binding,
        // dan guard double-submit via disable button.
        $('#unitForm').off('submit').on('submit', function(e) {
            e.preventDefault();

            var $btn = $('#btn-save');
            if ($btn.prop('disabled')) {
                return;
            }
            $btn.prop('disabled', true).html('Menyimpan...');

            var formData = new FormData(this);

            $.ajax({
                type: 'POST',
                url: "{{ route('unit.store') }}",
                data: formData,
                cache: false,
                contentType: false,
                processData: false,
                success: function(response) {
                    $('#unit-model').modal('hide');
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
        $('#unitForm').trigger('reset');
        $('#id').val('');
        $('#modalHeader').html('Tambah Satuan');
        $('#unit-model').modal('show');
    }

    function editFunc(id) {
        $.ajax({
            type: 'POST',
            url: "{{ route('unit.edit') }}",
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
                $('#modalHeader').html('Edit Satuan');
                $('#id').val(data.id);
                $('#nama').val(data.nama);
                $('#unit-model').modal('show');
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
                url: "{{ route('unit.destroy') }}",
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
