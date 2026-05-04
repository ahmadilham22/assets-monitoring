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
        // Reset form setiap kali modal ditutup supaya state tidak bocor
        $('#AddCategoryModal').on('hidden.bs.modal', function() {
            $('#add_categoryForm').trigger('reset');
            $('#add_category').prop('disabled', false).html('Save changes');
        });

        $('#EditCategoryModal').on('hidden.bs.modal', function() {
            $('#edit_categoryForm').trigger('reset');
            $('#edit_category_id').val('');
            $('#update_category').prop('disabled', false).html('Save changes');
        });

        // ---- DELETE ----
        $(document).off('click', '#delete_category').on('click', '#delete_category', function(e) {
            e.preventDefault();

            var categoryId = $(this).val();
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
                    data: {
                        id: categoryId
                    },
                    url: 'category/delete/' + categoryId,
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
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: parseAjaxError(xhr, 'Gagal menghapus data.'),
                        });
                    }
                });
            });
        });

        // ---- EDIT (load data ke modal) ----
        $(document).off('click', '#edit_category').on('click', '#edit_category', function(e) {
            e.preventDefault();

            var categoryId = $(this).val();

            $.ajax({
                type: 'GET',
                url: 'category/edit/' + categoryId,
                dataType: 'json',
                success: function(response) {
                    if (!response.success) {
                        Swal.fire({
                            icon: 'error',
                            title: 'Error',
                            text: response.message || 'Data tidak ditemukan',
                        });
                        return;
                    }

                    $('#edit_category_id').val(response.data.id);
                    $('#edit_kode_kategori').val(response.data.kode_kategori);
                    $('#edit_nama_kategori').val(response.data.nama_kategori);
                    $('#EditCategoryModal').modal('show');
                },
                error: function(xhr) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: parseAjaxError(xhr, 'Gagal memuat data.'),
                    });
                }
            });
        });

        // ---- UPDATE ----
        $(document).off('click', '#update_category').on('click', '#update_category', function(e) {
            e.preventDefault();

            var $btn = $(this);
            if ($btn.prop('disabled')) {
                return;
            }
            $btn.prop('disabled', true).html('Menyimpan...');

            var categoryId = $('#edit_category_id').val();
            var data = {
                kode_kategori: $('#edit_kode_kategori').val(),
                nama_kategori: $('#edit_nama_kategori').val(),
            };

            $.ajax({
                type: 'PUT',
                url: 'category/update/' + categoryId,
                data: data,
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        $('#EditCategoryModal').modal('hide');
                        $('#myTable').DataTable().ajax.reload(null, false);
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
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: parseAjaxError(xhr, 'Gagal memperbarui data.'),
                    });
                },
                complete: function() {
                    $btn.prop('disabled', false).html('Save changes');
                }
            });
        });

        // ---- ADD ----
        $(document).off('click', '#add_category').on('click', '#add_category', function(e) {
            e.preventDefault();

            var $btn = $(this);
            if ($btn.prop('disabled')) {
                return;
            }
            $btn.prop('disabled', true).html('Menyimpan...');

            var data = {
                kode_kategori: $('#kode_kategori').val(),
                nama_kategori: $('#nama_kategori').val(),
            };

            $.ajax({
                type: 'POST',
                url: "{{ route('category.store') }}",
                data: data,
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        $('#AddCategoryModal').modal('hide');
                        $('#myTable').DataTable().ajax.reload(null, false);
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
                    Swal.fire({
                        icon: 'error',
                        title: 'Error',
                        text: parseAjaxError(xhr, 'Gagal menambah data.'),
                    });
                },
                complete: function() {
                    $btn.prop('disabled', false).html('Save changes');
                }
            });
        });
    });
</script>
