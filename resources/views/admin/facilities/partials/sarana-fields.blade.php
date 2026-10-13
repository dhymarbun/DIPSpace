@php
    $saranaRows = $sarana ?? [];
    if (! is_array($saranaRows) || count($saranaRows) === 0) {
        // Minimal satu baris kosong agar formulir tidak terlihat hampa.
        $saranaRows = [['icon' => 'box', 'nama' => '', 'keterangan' => '']];
    }
@endphp

<div class="form-group">
    <label class="form-label">Sarana Pendukung</label>
    <span class="form-hint" style="display:block; margin-bottom:10px;">
        Ikon, nama, dan keterangan yang tampil pada bagian &ldquo;Sarana pendukung&rdquo; di halaman detail.
        Kosongkan semua baris untuk memakai daftar otomatis sesuai tipe fasilitas.
    </span>

    @error('sarana') <span class="text-danger" style="display:block; margin-bottom:8px;">{{ $message }}</span> @enderror

    <div id="sarana-rows">
        @foreach ($saranaRows as $i => $row)
            @include('admin.facilities.partials.sarana-row', ['index' => $i, 'row' => $row])
        @endforeach
    </div>

    <button type="button" class="btn btn-outline-secondary" id="sarana-add" style="margin-top:10px;">+ Tambah Sarana</button>
</div>

<template id="sarana-row-template">
    @include('admin.facilities.partials.sarana-row', ['index' => '__INDEX__', 'row' => []])
</template>
