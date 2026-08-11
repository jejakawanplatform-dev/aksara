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
    <title>Modul Ajar - {{ $plan->topic }}</title>
    @include('exports.partials.styles')
</head>
<body>
    @include('exports.partials.print-button')
    @include('exports.partials.kop')

    <div class="doc-title">
        <h1>Modul Ajar / Rencana Pembelajaran</h1>
        <h2>{{ mb_strtoupper($plan->topic) }}</h2>
        <p>Kurikulum Merdeka · Dicetak {{ now()->translatedFormat('d F Y') }}</p>
    </div>

    <div class="section-title">I. Identitas Modul</div>
    <table class="meta-table">
        <tr>
            <th style="width: 30%;">Mata Pelajaran</th>
            <td>{{ $plan->subject->name ?? '—' }} ({{ $plan->subject->code ?? '—' }})</td>
        </tr>
        <tr>
            <th>Guru Pengampu</th>
            <td>{{ $plan->teacher->name ?? '—' }}</td>
        </tr>
        <tr>
            <th>Kelas / Fase</th>
            <td>Kelas {{ $plan->class->name ?? $plan->grade }} / Fase {{ $plan->phase }}</td>
        </tr>
        <tr>
            <th>Tahun Ajaran / Semester</th>
            <td>{{ $plan->academicYear->name ?? '—' }} / {{ $plan->semester->name ?? '—' }}</td>
        </tr>
        <tr>
            <th>Alokasi Waktu</th>
            <td>{{ $plan->duration_minutes }} Menit</td>
        </tr>
        <tr>
            <th>Status Modul</th>
            <td>{{ $plan->status->label() }}</td>
        </tr>
    </table>

    <div class="section-title">II. Komponen Inti</div>
    <table class="meta-table">
        <tr>
            <th style="width: 30%;">Tujuan Pembelajaran (TP)</th>
            <td>{{ $plan->learning_objectives }}</td>
        </tr>
        <tr>
            <th>Referensi Kurikulum</th>
            <td>{{ $plan->curriculum_reference }}</td>
        </tr>
        @if($plan->student_needs)
            <tr>
                <th>Catatan Kebutuhan Belajar</th>
                <td>{{ $plan->student_needs }}</td>
            </tr>
        @endif
    </table>

    @if($plan->material)
        @php $matContent = $plan->material->content ?? []; @endphp
        <div class="section-title">III. Ringkasan Materi Pembelajaran</div>
        <div class="content-box">
            <strong>Judul Materi:</strong> {{ $matContent['title'] ?? $plan->topic }}
            @if(!empty($matContent['sections']) && is_array($matContent['sections']))
                @foreach($matContent['sections'] as $sec)
                    <div style="margin-top: 8px;">
                        @if(!empty($sec['heading']))
                            <strong style="color: #0d9488;">{{ is_array($sec['heading']) ? implode(' ', $sec['heading']) : $sec['heading'] }}</strong>
                        @endif
                        @if(!empty($sec['body']))
                            @php
                                $bStr = is_array($sec['body'])
                                    ? implode("\n", array_map('strval', $sec['body']))
                                    : (string) $sec['body'];
                            @endphp
                            <p style="margin: 4px 0 0 0; color: #334155;">{{ strip_tags($bStr) }}</p>
                        @endif
                    </div>
                @endforeach
            @endif

            @if(!empty($matContent['reflectionQuestion']))
                @php
                    $refQ = is_array($matContent['reflectionQuestion'])
                        ? implode('; ', array_map('strval', $matContent['reflectionQuestion']))
                        : (string) $matContent['reflectionQuestion'];
                @endphp
                <div style="margin-top: 10px; font-style: italic; color: #475569;">
                    <strong>Pertanyaan Refleksi:</strong> "{{ $refQ }}"
                </div>
            @endif
        </div>
    @endif

    @php $headmaster = (string) setting('school.headmaster', ''); @endphp
    @if($headmaster !== '')
        <div class="sign-block">
            <p>Mengetahui,<br>Kepala Sekolah</p>
            <div class="space"></div>
            <p><strong>{{ $headmaster }}</strong></p>
        </div>
        <div class="clear"></div>
    @endif

    <div class="doc-footer">
        Dokumen dihasilkan oleh Aksara · {{ $schoolName ?? setting('school.name', 'SMP Negeri 1 Aksara') }}
    </div>
</body>
</html>
