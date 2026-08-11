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
    <title>Capaian Pembelajaran (CP) &amp; Tujuan Pembelajaran (TP) - {{ $subject->name }}</title>
    @include('exports.partials.styles')
</head>
<body>
    @include('exports.partials.print-button')
    @include('exports.partials.kop')

    <div class="doc-title">
        <h1>Dokumen Capaian Pembelajaran (CP) &amp; Tujuan Pembelajaran (TP)</h1>
        <p>Kurikulum Merdeka · Dicetak {{ now()->translatedFormat('d F Y') }}</p>
    </div>

    <table class="meta-table">
        <tr>
            <th style="width: 20%;">Mata Pelajaran</th>
            <td style="width: 30%;">{{ $subject->name }} ({{ $subject->code }})</td>
            <th style="width: 20%;">Fase</th>
            <td style="width: 30%;">Fase {{ $subject->phase ?: 'D' }}</td>
        </tr>
        <tr>
            <th>Jenjang</th>
            <td>{{ $subject->jenjang ?: 'SMP' }}</td>
            <th>Tanggal Cetak</th>
            <td>{{ now()->translatedFormat('d F Y') }}</td>
        </tr>
    </table>

    @foreach($cps as $cp)
        <div class="cp-block">
            <div class="cp-title">ELEMEN {{ $cp->element_code }}: {{ $cp->element_name }}</div>
            <div class="cp-statement">"{{ $cp->statement }}"</div>
            @if($cp->source_note)
                <div style="font-size: 10px; color: #64748b; margin-bottom: 8px;">Sumber: {{ $cp->source_note }}</div>
            @endif

            <table class="tp-table">
                <thead>
                    <tr>
                        <th width="15%">Kode TP</th>
                        <th width="10%">Kelas</th>
                        <th width="75%">Pernyataan Tujuan Pembelajaran (TP)</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($cp->tps as $tp)
                        <tr>
                            <td><strong>{{ $tp->code }}</strong></td>
                            <td style="text-align: center;">{{ $tp->grade ?: '—' }}</td>
                            <td>{{ $tp->statement }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" style="text-align: center; color: #94a3b8;">Belum ada Tujuan Pembelajaran.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    @endforeach

    <div class="doc-footer">
        Dokumen dihasilkan oleh Aksara · {{ $schoolName ?? setting('school.name', 'SMP Negeri 1 Aksara') }}
    </div>
</body>
</html>
