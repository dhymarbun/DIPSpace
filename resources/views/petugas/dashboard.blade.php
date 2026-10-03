@extends('layouts.main')

@section('title', 'Antrian Petugas - DIPSpace')

@section('content')
    <h1 class="page-title">Antrian yang Menunggu Diproses</h1>

    @if (session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif
    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <!-- ========== RESERVASI ========== -->
    <div class="card">
        <p class="card-title">Reservasi</p>
        
        <div style="margin-bottom: 15px; display: flex; gap: 10px; flex-wrap: wrap;">
            <a href="?status=" class="btn {{ !request('status') ? 'btn-primary' : 'btn-outline-secondary' }}">Semua</a>
            <a href="?status=pending" class="btn {{ request('status') === 'pending' ? 'btn-primary' : 'btn-outline-secondary' }}">Pending</a>
            <a href="?status=approved" class="btn {{ request('status') === 'approved' ? 'btn-primary' : 'btn-outline-secondary' }}">Approved</a>
            <a href="?status=rejected" class="btn {{ request('status') === 'rejected' ? 'btn-primary' : 'btn-outline-secondary' }}">Rejected</a>
            <a href="?status=cancelled" class="btn {{ request('status') === 'cancelled' ? 'btn-primary' : 'btn-outline-secondary' }}">Cancelled</a>
        </div>

        <div class="table-wrap">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Fasilitas</th>
                        <th>Lokasi</th>
                        <th>Peminjam</th>
                        <th>Mulai</th>
                        <th>Selesai</th>
                        <th>Status</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($pendingReservations as $res)
                        <tr>
                            <td>{{ $res->facility->nama }}</td>
                            <td>{{ $res->facility->lokasi }}</td>
                            <td>{{ $res->user->email }}</td>
                            <td>{{ $res->start_time->format('Y-m-d H:i:s') }}</td>
                            <td>{{ $res->end_time->format('Y-m-d H:i:s') }}</td>
                            <td>
                                <span class="badge badge-{{ $res->status }}">
                                    {{ ucfirst($res->status) }}
                                </span>
                                @if ($res->status == 'cancelled' && $res->cancellation_reason)
                                    <br><small style="color:#666;">{{ Str::limit($res->cancellation_reason, 30) }}</small>
                                @endif
                            </td>
                            <td>
                                @if ($res->status == 'pending')
                                    <form method="post" action="{{ route('petugas.processReservation') }}" style="display:flex; gap:8px;">
                                        @csrf
                                        <input type="hidden" name="id" value="{{ $res->id }}">
                                        <button class="btn btn-sm btn-success" name="action" value="approve" type="submit" onclick="return confirm('Apakah Anda yakin ingin menyetujui?')">Ya</button>
                                        <button class="btn btn-sm btn-outline-danger" name="action" value="reject" type="submit" onclick="return confirm('Apakah Anda yakin ingin menolak?')">Tidak</button>
                                    </form>
                                @elseif ($res->status == 'approved')
                                    <button type="button" class="btn btn-sm btn-outline-danger" onclick="document.getElementById('cancel-res-{{ $res->id }}').style.display='block'">Batalkan</button>
                                    <div id="cancel-res-{{ $res->id }}" style="display:none; margin-top:8px;">
                                        <form method="post" action="{{ route('petugas.cancelReservation') }}">
                                            @csrf
                                            <input type="hidden" name="id" value="{{ $res->id }}">
                                            <input type="text" name="cancellation_reason" class="form-control" placeholder="Alasan pembatalan..." required style="margin-bottom:8px;">
                                            <button type="submit" class="btn btn-sm btn-danger">Konfirmasi Pembatalan</button>
                                        </form>
                                    </div>
                                @else
                                    -
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr class="empty-row"><td colspan="7">Tidak ada reservasi.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- ========== LAPORAN KERUSAKAN ========== -->
    <div class="card">
        <p class="card-title">Laporan Kerusakan</p>
        
        <div style="margin-bottom: 15px; display: flex; gap: 10px; flex-wrap: wrap;">
            <a href="?status=" class="btn {{ !request('status') ? 'btn-primary' : 'btn-outline-secondary' }}">Semua</a>
            <a href="?status=pending" class="btn {{ request('status') === 'pending' ? 'btn-primary' : 'btn-outline-secondary' }}">Pending</a>
            <a href="?status=in_progress" class="btn {{ request('status') === 'in_progress' ? 'btn-primary' : 'btn-outline-secondary' }}">In Progress</a>
            <a href="?status=resolved" class="btn {{ request('status') === 'resolved' ? 'btn-primary' : 'btn-outline-secondary' }}">Resolved</a>
            <a href="?status=rejected" class="btn {{ request('status') === 'rejected' ? 'btn-primary' : 'btn-outline-secondary' }}">Rejected</a>
        </div>

        <div class="table-wrap">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Fasilitas</th>
                        <th>Lokasi</th>
                        <th>Pelapor</th>
                        <th>Kategori</th>
                        <th>Tanggal</th>
                        <th>Deskripsi</th>
                        <th>Foto</th>
                        <th>Status</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($pendingReports as $rep)
                        <tr>
                            <td>{{ $rep->facility->nama }}</td>
                            <td>{{ $rep->facility->lokasi }}</td>
                            <td>{{ $rep->user->email }}</td>
                            <td>{{ ucfirst($rep->category) }}</td>
                            <td>{{ $rep->report_date->format('Y-m-d') }}</td>
                            <td>{{ $rep->description }}</td>
                            <td>
                                @if ($rep->photo_path)
                                    <a href="{{ asset($rep->photo_path) }}" target="_blank" rel="noopener" style="color:var(--navy); font-weight:600;">Lihat</a>
                                @else
                                    -
                                @endif
                            </td>
                            <td>
                                <span class="badge badge-{{ $rep->status }}">
                                    {{ ucfirst(str_replace('_', ' ', $rep->status)) }}
                                </span>
                                @if ($rep->resolution_notes)
                                    <br><small style="color:#666;">{{ Str::limit($rep->resolution_notes, 30) }}</small>
                                @endif
                            </td>
                            <td>
                                @if ($rep->status == 'pending')
                                    <form method="post" action="{{ route('petugas.processReport') }}" style="display:flex; gap:8px;">
                                        @csrf
                                        <input type="hidden" name="id" value="{{ $rep->id }}">
                                        <button class="btn btn-sm btn-success" name="action" value="start_report" type="submit" onclick="return confirm('Apakah Anda yakin ingin memproses laporan ini?')">Proses</button>
                                        <button class="btn btn-sm btn-outline-danger" name="action" value="reject_report" type="submit" onclick="return confirm('Apakah Anda yakin ingin menolak laporan ini?')">Tolak</button>
                                    </form>
                                @elseif ($rep->status == 'in_progress')
                                    <button type="button" class="btn btn-sm btn-primary" onclick="document.getElementById('status-rep-{{ $rep->id }}').style.display='block'">Ubah Status</button>
                                    <div id="status-rep-{{ $rep->id }}" style="display:none; margin-top:8px;">
                                        <form method="post" action="{{ route('petugas.updateReportStatus') }}">
                                            @csrf
                                            <input type="hidden" name="id" value="{{ $rep->id }}">
                                            <select name="status" class="form-control" required style="margin-bottom:8px;">
                                                <option value="in_progress">In Progress</option>
                                                <option value="resolved">Selesai</option>
                                                <option value="rejected">Ditolak</option>
                                            </select>
                                            <input type="text" name="resolution_notes" class="form-control" placeholder="Catatan resolusi (wajib jika selesai/ditolak)..." style="margin-bottom:8px;">
                                            <button type="submit" class="btn btn-sm btn-primary">Simpan</button>
                                        </form>
                                    </div>
                                    @if ($rep->facility->status == 'aktif')
                                        <br>
                                        <form method="post" action="{{ route('petugas.markFacilityRepair', $rep->facility->id) }}" style="margin-top:4px;">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-outline-warning" onclick="return confirm('Tandai fasilitas ini dalam perbaikan?')">Tandai Perbaikan</button>
                                        </form>
                                    @endif
                                @elseif ($rep->status == 'resolved')
                                    @if ($rep->facility->status == 'dalam_perbaikan')
                                        <form method="post" action="{{ route('petugas.reactivateFacility', $rep->facility->id) }}">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-success" onclick="return confirm('Aktifkan fasilitas ini kembali?')">Aktifkan Fasilitas</button>
                                        </form>
                                    @else
                                        -
                                    @endif
                                @else
                                    -
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr class="empty-row"><td colspan="9">Tidak ada laporan.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
