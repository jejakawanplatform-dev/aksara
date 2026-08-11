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
    <title>Rekap Rencana Pembelajaran (Modul Ajar)</title>
    @include('exports.partials.styles')
</head>
<body>
    @include('exports.partials.print-button')
    @include('exports.partials.kop')

    <div class="doc-title">
        <h1>Rekap Rencana Pembelajaran (Modul Ajar)</h1>
        <p>Kurikulum Merdeka · Tanggal cetak: {{ now()->translatedFormat('d F Y') }}</p>
    </div>

    <table class="data-table">
        <thead>
            <tr>
                <th width="4%">No</th>
                <th width="22%">Topik Pembelajaran</th>
                <th width="15%">Mapel &amp; Rombel</th>
                <th width="10%">Fase / Durasi</th>
                <th width="28%">Tujuan Pembelajaran</th>
                <th width="11%">Guru Pengampu</th>
                <th width="10%">Status</th>
            </tr>
        </thead>
        <tbody>
            @forelse($plans as $index => $plan)
                <tr>
                    <td style="text-align: center;">{{ $index + 1 }}</td>
                    <td><strong>{{ $plan->topic }}</strong></td>
                    <td>
                        {{ $plan->subject->name ?? '—' }}<br>
                        <span style="color: #64748b;">Kelas {{ $plan->class->name ?? $plan->grade }}</span>
                    </td>
                    <td>Fase {{ $plan->phase }}<br>{{ $plan->duration_minutes }} menit</td>
                    <td>{{ $plan->learning_objectives }}</td>
                    <td>{{ $plan->teacher->name ?? '—' }}</td>
                    <td>{{ $plan->status->label() }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" style="text-align: center; color: #94a3b8; padding: 12px;">
                        Belum ada Rencana Pembelajaran.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="doc-footer">
        Dokumen dihasilkan oleh Aksara · {{ $schoolName ?? setting('school.name', 'SMP Negeri 1 Aksara') }}
    </div>
</body>
</html>
