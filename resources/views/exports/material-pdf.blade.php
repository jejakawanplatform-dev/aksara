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
    <title>Bahan Ajar - {{ $title }}</title>
    @include('exports.partials.styles')
    <style>
        .material-body-content {
            font-size: 11px;
            line-height: 1.6;
            color: #1e293b;
        }
        .material-body-content p {
            margin: 0 0 8px 0;
        }
        .material-body-content ul, .material-body-content ol {
            margin: 0 0 8px 0;
            padding-left: 22px;
        }
        .material-body-content li {
            margin-bottom: 3px;
        }
        .material-body-content blockquote {
            margin: 8px 0;
            padding: 6px 12px;
            border-left: 3px solid #0d9488;
            background: #f8fafc;
            font-style: italic;
            color: #475569;
        }
        .material-body-content code {
            font-family: "Courier New", Courier, monospace;
            background: #f1f5f9;
            padding: 1px 4px;
            border-radius: 3px;
            font-size: 10px;
        }
        .material-body-content pre {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            padding: 8px 10px;
            border-radius: 4px;
            overflow-x: auto;
            font-family: "Courier New", Courier, monospace;
            font-size: 10px;
            margin: 8px 0;
        }
        .material-section-box {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 4px;
            padding: 12px 14px;
            margin-bottom: 12px;
            page-break-inside: avoid;
        }
        .material-section-title {
            margin: 0 0 8px 0;
            font-size: 13px;
            font-weight: 700;
            color: #0f766e;
            border-bottom: 1px solid #ccfbf1;
            padding-bottom: 4px;
        }
        .reflection-callout {
            background-color: #f0fdfa;
            border: 1px solid #0d9488;
            border-radius: 4px;
            padding: 12px 14px;
            margin-bottom: 14px;
            page-break-inside: avoid;
        }
    </style>
</head>
<body>
    @include('exports.partials.print-button')
    @include('exports.partials.kop')

    <div class="doc-title">
        <h1>Bahan Ajar / Materi Pembelajaran</h1>
        <h2>{{ mb_strtoupper($title) }}</h2>
        <p>Kurikulum Merdeka · Dicetak {{ now()->translatedFormat('d F Y') }}</p>
    </div>

    <div class="section-title">I. Identitas Bahan Ajar</div>
    <table class="meta-table">
        <tr>
            <th style="width: 30%;">Mata Pelajaran</th>
            <td>{{ $plan['subject'] }} ({{ $plan['code'] }})</td>
        </tr>
        <tr>
            <th>Kelas / Fase</th>
            <td>{{ $plan['className'] }} · Fase {{ $plan['phase'] }}</td>
        </tr>
        <tr>
            <th>Guru Pengampu</th>
            <td>{{ $plan['teacher'] }}</td>
        </tr>
        <tr>
            <th>Alokasi Waktu</th>
            <td>{{ $plan['duration'] }} Menit</td>
        </tr>
        <tr>
            <th>Status Publikasi</th>
            <td>{{ $plan['status'] }}</td>
        </tr>
    </table>

    <div class="section-title">II. Isi Materi Pembelajaran</div>
    @forelse($sections as $index => $sec)
        <div class="material-section-box">
            @if(!empty($sec['heading']))
                <div class="material-section-title">
                    {{ $index + 1 }}. {{ $sec['heading'] }}
                </div>
            @endif
            <div class="material-body-content">
                {!! $sec['body'] !!}
            </div>
        </div>
    @empty
        <div class="content-box">
            <p style="color: #64748b; font-style: italic; margin: 0;">Konten materi belum diisi.</p>
        </div>
    @endforelse

    @if(!empty($reflections))
        <div class="section-title" style="page-break-inside: avoid;">III. Refleksi Pemahaman Siswa</div>
        <div class="reflection-callout">
            <p style="margin: 0 0 8px 0; font-weight: bold; color: #0f766e;">Pertanyaan Refleksi:</p>
            <ol style="margin: 0; padding-left: 20px; font-size: 11px; line-height: 1.6; color: #334155;">
                @foreach($reflections as $refItem)
                    <li style="margin-bottom: 4px;"><em>{{ $refItem }}</em></li>
                @endforeach
            </ol>
        </div>
    @endif

    @php $headmaster = (string) setting('school.headmaster', ''); @endphp
    @if($headmaster !== '')
        <div class="sign-block" style="page-break-inside: avoid;">
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
