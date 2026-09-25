@extends('layouts.main')

@section('title', 'DIPSpace - Daftar Fasilitas')

@section('content')
    <h1 class="page-title">Cek Ketersediaan Ruangan</h1>

    <div class="card" style="padding:0; overflow:hidden;">
        <div class="table-wrap" style="border:none; border-radius:0;">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Nama</th>
                        <th>Tipe</th>
                        <th>Lokasi</th>
                        <th>Kapasitas</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($facilities as $facility)
                        <tr>
                            <td>{{ $facility->nama }}</td>
                            <td>{{ $facility->tipe }}</td>
                            <td>{{ $facility->lokasi }}</td>
                            <td>{{ $facility->kapasitas }}</td>
                            <td>
                                <span class="badge badge-{{ $facility->status }}">
                                    {{ ucwords(str_replace('_', ' ', $facility->status)) }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr class="empty-row">
                            <td colspan="5">Belum ada fasilitas.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="card">
        <p class="card-title">Jadwal Reservasi Fasilitas</p>
        
        <div class="form-group" style="margin-bottom: 20px;">
            <label class="form-label" for="calendar_facility_id">Pilih Fasilitas</label>
            <select class="form-control" id="calendar_facility_id">
                <option value="">Semua Fasilitas</option>
                @foreach ($facilities as $facility)
                    <option value="{{ $facility->id }}">{{ $facility->nama }} — {{ $facility->lokasi }}</option>
                @endforeach
            </select>
        </div>

        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <button type="button" id="prev_month" class="btn btn-outline-secondary" style="padding: 8px 12px;">← Sebelumnya</button>
            <h3 id="calendar_month_year" style="margin: 0; font-size: 1.2rem; font-weight: 600;"></h3>
            <button type="button" id="next_month" class="btn btn-outline-secondary" style="padding: 8px 12px;">Selanjutnya →</button>
        </div>

        <div id="calendar_container" style="border: 1px solid #e5e7eb; border-radius: 8px; overflow: hidden;">
            <div style="display: grid; grid-template-columns: repeat(7, 1fr); background: #f9fafb;">
                <div style="padding: 12px; text-align: center; font-weight: 600; border-right: 1px solid #e5e7eb; border-bottom: 1px solid #e5e7eb;">Sen</div>
                <div style="padding: 12px; text-align: center; font-weight: 600; border-right: 1px solid #e5e7eb; border-bottom: 1px solid #e5e7eb;">Sel</div>
                <div style="padding: 12px; text-align: center; font-weight: 600; border-right: 1px solid #e5e7eb; border-bottom: 1px solid #e5e7eb;">Rab</div>
                <div style="padding: 12px; text-align: center; font-weight: 600; border-right: 1px solid #e5e7eb; border-bottom: 1px solid #e5e7eb;">Kam</div>
                <div style="padding: 12px; text-align: center; font-weight: 600; border-right: 1px solid #e5e7eb; border-bottom: 1px solid #e5e7eb;">Jum</div>
                <div style="padding: 12px; text-align: center; font-weight: 600; border-right: 1px solid #e5e7eb; border-bottom: 1px solid #e5e7eb;">Sab</div>
                <div style="padding: 12px; text-align: center; font-weight: 600; border-bottom: 1px solid #e5e7eb;">Min</div>
            </div>
            <div id="calendar_grid" style="display: grid; grid-template-columns: repeat(7, 1fr);"></div>
        </div>
    </div>

    <script>
        let currentMonth = new Date().getMonth() + 1;
        let currentYear = new Date().getFullYear();
        let selectedFacilityId = '';

        const monthNames = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];

        function updateCalendar() {
            const monthYearEl = document.getElementById('calendar_month_year');
            monthYearEl.textContent = `${monthNames[currentMonth - 1]} ${currentYear}`;
            fetchSchedule();
        }

        function fetchSchedule() {
            if (!selectedFacilityId) {
                renderCalendar({});
                return;
            }

            fetch(`/api/reservations/schedule?facility_id=${selectedFacilityId}&month=${currentMonth}&year=${currentYear}`)
                .then(res => res.json())
                .then(data => {
                    const grouped = {};
                    data.reservations.forEach(res => {
                        if (!grouped[res.date]) grouped[res.date] = [];
                        grouped[res.date].push(res);
                    });
                    renderCalendar(grouped);
                })
                .catch(err => console.error('Error:', err));
        }

        function renderCalendar(reservations) {
            const firstDay = new Date(currentYear, currentMonth - 1, 1);
            const startDate = new Date(firstDay);
            startDate.setDate(startDate.getDate() - firstDay.getDay());

            const grid = document.getElementById('calendar_grid');
            grid.innerHTML = '';

            for (let i = 0; i < 42; i++) {
                const cellDate = new Date(startDate);
                cellDate.setDate(cellDate.getDate() + i);
                const dateStr = cellDate.getFullYear() + '-' + String(cellDate.getMonth() + 1).padStart(2, '0') + '-' + String(cellDate.getDate()).padStart(2, '0');
                const isCurrentMonth = cellDate.getMonth() === currentMonth - 1;

                const cell = document.createElement('div');
                cell.style.cssText = `
                    padding: 12px;
                    border-right: 1px solid #e5e7eb;
                    border-bottom: 1px solid #e5e7eb;
                    min-height: 120px;
                    background: ${isCurrentMonth ? '#fff' : '#f9fafb'};
                    color: ${isCurrentMonth ? '#000' : '#999'};
                `;

                const dateDiv = document.createElement('div');
                dateDiv.style.cssText = 'font-weight: 600; margin-bottom: 8px; font-size: 14px;';
                dateDiv.textContent = cellDate.getDate();

                const slotsDiv = document.createElement('div');
                slotsDiv.style.cssText = 'font-size: 12px; line-height: 1.4;';

                if (reservations[dateStr]) {
                    reservations[dateStr].forEach(res => {
                        const badge = document.createElement('span');
                        const badgeClass = res.status === 'approved' ? 'badge-approved' : 'badge-pending';
                        badge.className = `badge ${badgeClass}`;
                        badge.style.cssText = 'display: block; margin-bottom: 4px; padding: 2px 6px; font-size: 11px; border-radius: 4px;';
                        badge.textContent = `${res.start_time}-${res.end_time}`;
                        slotsDiv.appendChild(badge);
                    });
                }

                cell.appendChild(dateDiv);
                cell.appendChild(slotsDiv);
                grid.appendChild(cell);
            }
        }

        document.getElementById('calendar_facility_id').addEventListener('change', function(e) {
            selectedFacilityId = e.target.value;
            updateCalendar();
        });

        document.getElementById('prev_month').addEventListener('click', function() {
            currentMonth--;
            if (currentMonth < 1) {
                currentMonth = 12;
                currentYear--;
            }
            updateCalendar();
        });

        document.getElementById('next_month').addEventListener('click', function() {
            currentMonth++;
            if (currentMonth > 12) {
                currentMonth = 1;
                currentYear++;
            }
            updateCalendar();
        });

        updateCalendar();
    </script>
@endsection
