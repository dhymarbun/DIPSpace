<div class="card">
    <p class="card-title">Informasi Profil</p>
    <p class="form-hint">Perbarui nama dan alamat email akun Anda.</p>

    @if (session('status') === 'profile-updated')
        <div class="alert alert-success">Profil berhasil diperbarui.</div>
    @endif

    <form method="post" action="{{ route('profile.update') }}">
        @csrf
        @method('patch')

        <div class="form-group">
            <label class="form-label" for="name">Nama</label>
            <input class="form-control" id="name" name="name" type="text" value="{{ old('name', $user->name) }}" required autofocus autocomplete="name">
            @error('name')
                <span class="text-danger">{{ $message }}</span>
            @enderror
        </div>

        <div class="form-group">
            <label class="form-label" for="email">Email</label>
            <input class="form-control" id="email" name="email" type="email" value="{{ old('email', $user->email) }}" required autocomplete="username">
            @error('email')
                <span class="text-danger">{{ $message }}</span>
            @enderror
        </div>

        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Simpan</button>
        </div>
    </form>
</div>