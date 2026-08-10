<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Capaian Pembelajaran (CP) & Tujuan Pembelajaran (TP) - {{ $subject->name }}</title>
    <style>
        body { font-family: sans-serif; font-size: 12px; color: #1e293b; margin: 20px; line-height: 1.5; }
        .header { text-align: center; border-bottom: 2px solid #0d9488; padding-bottom: 12px; margin-bottom: 20px; }
        .header h1 { margin: 0; font-size: 18px; color: #0f766e; text-transform: uppercase; }
        .header p { margin: 4px 0 0 0; font-size: 11px; color: #64748b; }
        .meta-table { width: 100%; margin-bottom: 20px; font-size: 11px; border-collapse: collapse; }
        .meta-table td { padding: 4px 8px; }
        .cp-block { margin-bottom: 20px; page-break-inside: avoid; border: 1px solid #e2e8f0; border-radius: 8px; padding: 12px; background: #f8fafc; }
        .cp-title { font-weight: bold; font-size: 13px; color: #0f766e; margin-bottom: 6px; }
        .cp-statement { font-style: italic; margin-bottom: 10px; color: #334155; }
        table.tp-table { width: 100%; border-collapse: collapse; margin-top: 8px; font-size: 11px; }
        table.tp-table th, table.tp-table td { border: 1px solid #cbd5e1; padding: 6px 8px; text-align: left; }
        table.tp-table th { background: #e2e8f0; font-weight: bold; color: #1e293b; }
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
        <h1>Dokumen Capaian Pembelajaran (CP) & Tujuan Pembelajaran (TP)</h1>
        <p>Satuan Pendidikan: {{ $schoolName }} · Kurikulum Merdeka</p>
    </div>

    <table class="meta-table">
        <tr>
            <td width="20%"><strong>Mata Pelajaran:</strong></td>
            <td width="30%">{{ $subject->name }} ({{ $subject->code }})</td>
            <td width="20%"><strong>Fase:</strong></td>
            <td width="30%">Fase {{ $subject->phase ?: 'D' }}</td>
        </tr>
        <tr>
            <td><strong>Jenjang:</strong></td>
            <td>{{ $subject->jenjang ?: 'SMP' }}</td>
            <td><strong>Tanggal Cetak:</strong></td>
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
</body>
</html>
