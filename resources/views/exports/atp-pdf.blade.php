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
    <title>Alur Tujuan Pembelajaran (ATP) - {{ $subject->name }}</title>
    @include('exports.partials.styles')
</head>
<body>
    @include('exports.partials.print-button')
    @include('exports.partials.kop')

    <div class="doc-title">
        <h1>Alur Tujuan Pembelajaran (ATP)</h1>
        <p>Kurikulum Merdeka · Dicetak {{ now()->translatedFormat('d F Y') }}</p>
    </div>

    <table class="meta-table">
        <tr>
            <th style="width: 20%;">Mata Pelajaran</th>
            <td style="width: 30%;">{{ $subject->name }} ({{ $subject->code }})</td>
            <th style="width: 20%;">Fase / Kelas</th>
            <td style="width: 30%;">
                Fase {{ $subject->phase ?: 'D' }}
                {{ $grade ? "(Kelas {$grade})" : '(Semua Kelas)' }}
            </td>
        </tr>
        <tr>
            <th>Jenjang</th>
            <td>{{ $subject->jenjang ?: 'SMP' }}</td>
            <th>Tanggal Cetak</th>
            <td>{{ now()->translatedFormat('d F Y') }}</td>
        </tr>
    </table>

    <table class="atp-table">
        <thead>
            <tr>
                <th width="6%">No</th>
                <th width="8%">Kelas</th>
                <th width="12%">Semester</th>
                <th width="22%">Judul Unit</th>
                <th width="14%">Kode TP</th>
                <th width="32%">Pernyataan TP</th>
                <th width="6%">JP</th>
            </tr>
        </thead>
        <tbody>
            @forelse($atpItems as $item)
                <tr>
                    <td style="text-align: center;">{{ $item->sequence }}</td>
                    <td style="text-align: center;">{{ $item->grade }}</td>
                    <td>{{ $item->semester?->name ?: '—' }}</td>
                    <td>{{ $item->unit_title ?: '—' }}</td>
                    <td><strong>{{ $item->tp?->code ?: '—' }}</strong></td>
                    <td>{{ $item->tp?->statement ?: '—' }}</td>
                    <td style="text-align: center;">{{ $item->estimated_meetings ?: '—' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" style="text-align: center; padding: 16px; color: #94a3b8;">Belum ada item ATP.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="doc-footer">
        Dokumen dihasilkan oleh Aksara · {{ $schoolName ?? setting('school.name', 'SMP Negeri 1 Aksara') }}
    </div>
</body>
</html>
