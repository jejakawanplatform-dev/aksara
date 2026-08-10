<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Modul Ajar - {{ $plan->topic }}</title>
    <style>
        body { font-family: sans-serif; font-size: 11px; color: #1e293b; margin: 20px; line-height: 1.5; }
        .header { text-align: center; border-bottom: 2px solid #0d9488; padding-bottom: 12px; margin-bottom: 20px; }
        .header h1 { margin: 0; font-size: 16px; color: #0f766e; text-transform: uppercase; }
        .header h2 { margin: 4px 0 0 0; font-size: 14px; color: #0d9488; }
        .header p { margin: 4px 0 0 0; font-size: 10px; color: #64748b; }
        .section-title { font-weight: bold; font-size: 12px; color: #0f766e; margin-top: 16px; margin-bottom: 8px; border-bottom: 1px solid #cbd5e1; padding-bottom: 4px; }
        table.meta-table { width: 100%; border-collapse: collapse; margin-bottom: 16px; font-size: 11px; }
        table.meta-table th, table.meta-table td { border: 1px solid #cbd5e1; padding: 6px 10px; text-align: left; }
        table.meta-table th { background: #f1f5f9; font-weight: bold; color: #0f766e; width: 30%; }
        .content-box { border: 1px solid #e2e8f0; background: #f8fafc; border-radius: 6px; padding: 12px; margin-bottom: 12px; }
        @media print {
            body { margin: 0; }
            .no-print { display: none; }
        }
    </style>
</head>
<body>
    <div class="no-print" style="margin-bottom: 16px; text-align: right;">
        <button onclick="window.print()" style="background: #0d9488; color: white; border: none; padding: 8px 16px; border-radius: 6px; font-weight: bold; cursor: pointer;">
            🖨️ Cetak / Simpan PDF
        </button>
    </div>

    <div class="header">
        <h1>MODUL AJAR / RENCANA PEMBELAJARAN</h1>
        <h2>{{ mb_strtoupper($plan->topic) }}</h2>
        <p>Satuan Pendidikan: {{ $schoolName }} · Kurikulum Merdeka</p>
    </div>

    <div class="section-title">I. IDENTITAS MODUL</div>
    <table class="meta-table">
        <tr>
            <th>Mata Pelajaran</th>
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

    <div class="section-title">II. KOMPONEN INTI</div>
    <table class="meta-table">
        <tr>
            <th>Tujuan Pembelajaran (TP)</th>
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
        <div class="section-title">III. RINGKASAN MATERI PEMBELAJARAN</div>
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
                                $bStr = is_array($sec['body']) ? implode("\n", array_map('strval', $sec['body'])) : (string) $sec['body'];
                            @endphp
                            <p style="margin: 4px 0 0 0; color: #334155;">{{ strip_tags($bStr) }}</p>
                        @endif
                    </div>
                @endforeach
            @endif

            @if(!empty($matContent['reflectionQuestion']))
                @php
                    $refQ = is_array($matContent['reflectionQuestion']) 
                        ? implode("; ", array_map('strval', $matContent['reflectionQuestion'])) 
                        : (string) $matContent['reflectionQuestion'];
                @endphp
                <div style="margin-top: 10px; font-style: italic; color: #475569;">
                    <strong>Pertanyaan Refleksi:</strong> "{{ $refQ }}"
                </div>
            @endif
        </div>
    @endif
</body>
</html>
