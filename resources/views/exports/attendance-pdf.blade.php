{{--
  Aksara — platform pembelajaran berbantuan AI.
  @copyright 2026 jejakawan (https://jejakawan.com)
  @license   MIT
  Clone, fork, and modification are permitted under the MIT License.
  See the LICENSE file in the project root.
--}}
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Rekap Kehadiran - Kelas {{ $class['name'] }}</title>
    @include('exports.partials.styles')
    <style>
        @page {
            size: A4 landscape;
            margin: 12mm 14mm;
        }
        body {
            max-width: 100%;
            font-size: 10px;
        }
        .attendance-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
            font-size: 10px;
        }
        .attendance-table th,
        .attendance-table td {
            border: 1px solid #cbd5e1;
            padding: 5px 6px;
        }
        .attendance-table th {
            background: #0f766e;
            color: #ffffff;
            font-weight: 700;
            text-align: center;
            font-size: 9.5px;
            text-transform: uppercase;
            letter-spacing: 0.03em;
        }
        .attendance-table tr:nth-child(even) {
            background-color: #f8fafc;
        }
        .center {
            text-align: center;
        }
        .right {
            text-align: right;
        }
        .warning-text {
            color: #dc2626;
            font-weight: bold;
        }
        .stats-grid {
            display: table;
            width: 100%;
            margin-top: 12px;
            margin-bottom: 12px;
            border-collapse: separate;
            border-spacing: 8px 0;
            page-break-inside: avoid;
        }
        .stats-cell {
            display: table-cell;
            background: #f0fdfa;
            border: 1px solid #99f6e4;
            border-radius: 4px;
            padding: 8px 12px;
            text-align: center;
            width: 33.33%;
        }
        .stats-val {
            font-size: 14px;
            font-weight: bold;
            color: #0f766e;
        }
        .stats-lbl {
            font-size: 9px;
            color: #475569;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            margin-top: 2px;
        }
        .signatures {
            display: table;
            width: 100%;
            margin-top: 24px;
            page-break-inside: avoid;
        }
        .sig-col {
            display: table-cell;
            width: 50%;
            text-align: center;
            font-size: 10px;
            color: #334155;
            vertical-align: top;
        }
        .sig-space {
            height: 52px;
        }
    </style>
</head>
<body>
    @include('exports.partials.print-button')
    @include('exports.partials.kop')

    <div class="doc-title">
        <h1>Rekapitulasi Kehadiran Siswa</h1>
        <h2>KELAS {{ mb_strtoupper($class['name']) }} (TINGKAT {{ $class['grade'] }})</h2>
        <p>
            Cakupan: {{ $selectedPlanTopic ? 'Pertemuan: ' . $selectedPlanTopic : 'Semua Pertemuan (' . $plansCount . ' Rencana Ajar)' }}
            · Dicetak {{ now()->translatedFormat('d F Y') }}
        </p>
    </div>

    <table class="meta-table">
        <tr>
            <th style="width: 20%;">Kelas / Rombel</th>
            <td style="width: 30%;">{{ $class['name'] }} (Tingkat {{ $class['grade'] }})</td>
            <th style="width: 20%;">Wali Kelas</th>
            <td style="width: 30%;">{{ $class['homeroom'] }}</td>
        </tr>
        <tr>
            <th>Dicetak Oleh</th>
            <td>{{ $actorName }}</td>
            <th>Total Pertemuan</th>
            <td>{{ $plansCount }} Pertemuan</td>
        </tr>
    </table>

    <table class="attendance-table">
        <thead>
            <tr>
                <th style="width: 35px;">No</th>
                <th style="width: 50px;">ID</th>
                <th style="text-align: left;">Nama Siswa</th>
                <th style="width: 65px;">Hadir (H)</th>
                <th style="width: 60px;">Izin (I)</th>
                <th style="width: 60px;">Sakit (S)</th>
                <th style="width: 65px;">Alpha (A)</th>
                <th style="width: 65px;">Total</th>
                <th style="width: 80px;">% Hadir</th>
                <th style="width: 105px;">Keterangan</th>
            </tr>
        </thead>
        <tbody>
            @forelse($summaryRows as $idx => $row)
                <tr>
                    <td class="center">{{ $idx + 1 }}</td>
                    <td class="center">#{{ $row['studentId'] }}</td>
                    <td style="font-weight: 500;">{{ $row['studentName'] }}</td>
                    <td class="center" style="color: #0f766e; font-weight: bold;">{{ $row['hadir'] }}</td>
                    <td class="center">{{ $row['izin'] }}</td>
                    <td class="center">{{ $row['sakit'] }}</td>
                    <td class="center" style="{{ $row['alpha'] > 0 ? 'color: #dc2626; font-weight: bold;' : '' }}">{{ $row['alpha'] }}</td>
                    <td class="center font-bold">{{ $row['total'] }}</td>
                    <td class="center {{ $row['status'] === 'Perlu Perhatian' ? 'warning-text' : '' }}">
                        {{ $row['pct'] }}%
                    </td>
                    <td class="center {{ $row['status'] === 'Perlu Perhatian' ? 'warning-text' : '' }}">
                        {{ $row['status'] }}
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="10" class="center" style="padding: 16px; color: #64748b; font-style: italic;">
                        Tidak ada data kehadiran siswa yang ditemukan.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="stats-grid">
        <div class="stats-cell">
            <div class="stats-val">{{ $stats['totalStudents'] }}</div>
            <div class="stats-lbl">Total Siswa Terdaftar</div>
        </div>
        <div class="stats-cell">
            <div class="stats-val">{{ $stats['avgPct'] }}%</div>
            <div class="stats-lbl">Rata-rata Kehadiran Rombel</div>
        </div>
        <div class="stats-cell" style="{{ $stats['warningCount'] > 0 ? 'background: #fef2f2; border-color: #fecaca;' : '' }}">
            <div class="stats-val" style="{{ $stats['warningCount'] > 0 ? 'color: #dc2626;' : '' }}">{{ $stats['warningCount'] }} Siswa</div>
            <div class="stats-lbl">Perlu Perhatian (&lt; 75%)</div>
        </div>
    </div>

    <div class="signatures">
        <div class="sig-col">
            <p>Mengetahui,<br>Kepala Sekolah</p>
            <div class="sig-space"></div>
            <p><strong>{{ $headmaster !== '' ? $headmaster : '—' }}</strong></p>
        </div>
        <div class="sig-col">
            <p>{{ now()->translatedFormat('d F Y') }}<br>Guru Pengampu / Wali Kelas</p>
            <div class="sig-space"></div>
            <p><strong>{{ $actorName }}</strong></p>
        </div>
    </div>

    <div class="doc-footer">
        Dokumen dihasilkan oleh Aksara · {{ $schoolName }}
    </div>
</body>
</html>
