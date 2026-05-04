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
        $('#AddUserModal').on('hidden.bs.modal', function() {
            $('#userForm').trigger('reset');
            $('#add_user').prop('disabled', false).html('Save changes');
        });

        $('#EditUserModal').on('hidden.bs.modal', function() {
            $('#edit_userForm').trigger('reset');
            $('#edit_user_id').val('');
            $('#update_user').prop('disabled', false).html('Save changes');
        });

        // ---- DELETE ----
        $(document).off('click', '#delete_user').on('click', '#delete_user', function(e) {
            e.preventDefault();

            var userId = $(this).val();
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
                        id: userId
                    },
                    url: 'user/delete/' + userId,
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
        $(document).off('click', '#edit_user').on('click', '#edit_user', function(e) {
            e.preventDefault();

            var userId = $(this).val();

            $.ajax({
                type: 'GET',
                url: 'user/edit/' + userId,
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

                    $('#edit_user_id').val(response.data.id);
                    $('#edit_nama').val(response.data.nama);
                    $('#edit_email').val(response.data.email);
                    $('#edit_password').val('');
                    $('#edit_role').val(response.data.role);
                    $('#edit_division_id').val(response.data.division_id);
                    $('#EditUserModal').modal('show');
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
        $(document).off('click', '#update_user').on('click', '#update_user', function(e) {
            e.preventDefault();

            var $btn = $(this);
            if ($btn.prop('disabled')) {
                return;
            }
            $btn.prop('disabled', true).html('Menyimpan...');

            var userId = $('#edit_user_id').val();
            var data = {
                nama: $('#edit_nama').val(),
                email: $('#edit_email').val(),
                password: $('#edit_password').val(),
                role: $('#edit_role').val(),
                division_id: $('#edit_division_id').val(),
            };

            $.ajax({
                type: 'PUT',
                url: 'user/update/' + userId,
                data: data,
                dataType: 'json',
                headers: {
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
                },
                success: function(response) {
                    if (response.success) {
                        $('#EditUserModal').modal('hide');
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
        $(document).off('click', '#add_user').on('click', '#add_user', function(e) {
            e.preventDefault();

            var $btn = $(this);
            if ($btn.prop('disabled')) {
                return;
            }
            $btn.prop('disabled', true).html('Menyimpan...');

            var data = {
                nama: $('#nama').val(),
                email: $('#email').val(),
                password: $('#password').val(),
                role: $('#role').val(),
                division_id: $('#division_id').val(),
            };

            $.ajax({
                type: 'POST',
                url: "{{ route('user.store') }}",
                data: data,
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        $('#AddUserModal').modal('hide');
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
