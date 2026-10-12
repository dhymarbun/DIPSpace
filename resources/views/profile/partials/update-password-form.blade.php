<div class="card">
    <p class="card-title">Perbarui Kata Sandi</p>
    <p class="form-hint">Pastikan akun Anda menggunakan kata sandi yang panjang dan acak agar tetap aman.</p>

    @if (session('status') === 'password-updated')
        <div class="alert alert-success">Kata sandi berhasil diperbarui.</div>
    @endif

    <form method="post" action="{{ route('password.update') }}">
        @csrf
        @method('put')

        <div class="form-group">
            <label class="form-label" for="update_password_current_password">Kata Sandi Saat Ini</label>
            <input class="form-control" id="update_password_current_password" name="current_password" type="password" autocomplete="current-password">
            @error('current_password', 'updatePassword')
                <span class="text-danger">{{ $message }}</span>
            @enderror
        </div>

        <div class="form-group">
            <label class="form-label" for="update_password_password">Kata Sandi Baru</label>
            <input class="form-control" id="update_password_password" name="password" type="password" autocomplete="new-password">
            @error('password', 'updatePassword')
                <span class="text-danger">{{ $message }}</span>
            @enderror
        </div>

        <div class="form-group">
            <label class="form-label" for="update_password_password_confirmation">Konfirmasi Kata Sandi Baru</label>
            <input class="form-control" id="update_password_password_confirmation" name="password_confirmation" type="password" autocomplete="new-password">
            @error('password_confirmation', 'updatePassword')
                <span class="text-danger">{{ $message }}</span>
            @enderror
        </div>

        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Simpan</button>
        </div>
    </form>
</div>