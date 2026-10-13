<div class="sarana-row" data-sarana-row>
    <div class="sarana-row-field sarana-row-field-icon">
        <label class="form-label">Ikon</label>
        <select name="sarana[{{ $index }}][icon]" class="form-control">
            @foreach (\App\Models\Facility::SARANA_ICONS as $key => $label)
                <option value="{{ $key }}" @selected(($row['icon'] ?? 'box') === $key)>{{ $label }}</option>
            @endforeach
        </select>
    </div>

    <div class="sarana-row-field sarana-row-field-nama">
        <label class="form-label">Nama</label>
        <input type="text" name="sarana[{{ $index }}][nama]" class="form-control {{ $errors->has('sarana.'.$index.'.nama') ? 'is-invalid' : '' }}" value="{{ $row['nama'] ?? '' }}" maxlength="60" placeholder="cth. Proyektor &amp; layar">
        @error('sarana.'.$index.'.nama') <span class="text-danger">{{ $message }}</span> @enderror
    </div>

    <div class="sarana-row-field sarana-row-field-keterangan">
        <label class="form-label">Keterangan</label>
        <input type="text" name="sarana[{{ $index }}][keterangan]" class="form-control {{ $errors->has('sarana.'.$index.'.keterangan') ? 'is-invalid' : '' }}" value="{{ $row['keterangan'] ?? '' }}" maxlength="100" placeholder="cth. Untuk presentasi materi">
        @error('sarana.'.$index.'.keterangan') <span class="text-danger">{{ $message }}</span> @enderror
    </div>

    <button type="button" class="sarana-row-remove" data-sarana-remove title="Hapus baris">&times;</button>
</div>
