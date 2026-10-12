<div class="card">
    <p class="card-title">Hapus Akun</p>
    <p class="form-hint">Setelah akun dihapus, seluruh data dan sumber dayanya akan terhapus permanen. Masukkan kata sandi untuk mengonfirmasi penghapusan akun.</p>

    <form method="post" action="{{ route('profile.destroy') }}" onsubmit="return confirm('Apakah Anda yakin ingin menghapus akun ini? Tindakan ini tidak dapat dibatalkan.');">
        @csrf
        @method('delete')

        <div class="form-group">
            <label class="form-label" for="password">Kata Sandi</label>
            <input class="form-control" id="password" name="password" type="password" placeholder="Kata sandi" required autocomplete="current-password">
            @error('password', 'userDeletion')
                <span class="text-danger">{{ $message }}</span>
            @enderror
        </div>

        <div class="form-actions">
            <button type="submit" class="btn btn-danger">Hapus Akun</button>
        </div>
    </form>
</div>