<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Alur Tujuan Pembelajaran (ATP) - {{ $subject->name }}</title>
    <style>
        body { font-family: sans-serif; font-size: 11px; color: #1e293b; margin: 20px; line-height: 1.4; }
        .header { text-align: center; border-bottom: 2px solid #0d9488; padding-bottom: 12px; margin-bottom: 16px; }
        .header h1 { margin: 0; font-size: 16px; color: #0f766e; text-transform: uppercase; }
        .header p { margin: 4px 0 0 0; font-size: 10px; color: #64748b; }
        .meta-table { width: 100%; margin-bottom: 16px; font-size: 11px; border-collapse: collapse; }
        .meta-table td { padding: 4px 8px; }
        table.atp-table { width: 100%; border-collapse: collapse; margin-top: 8px; font-size: 11px; }
        table.atp-table th, table.atp-table td { border: 1px solid #cbd5e1; padding: 6px 8px; text-align: left; }
        table.atp-table th { background: #0f766e; color: white; font-weight: bold; }
        table.atp-table tr:nth-child(even) { background: #f8fafc; }
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
        <h1>Alur Tujuan Pembelajaran (ATP)</h1>
        <p>Satuan Pendidikan: {{ $schoolName }} · Kurikulum Merdeka</p>
    </div>

    <table class="meta-table">
        <tr>
            <td width="20%"><strong>Mata Pelajaran:</strong></td>
            <td width="30%">{{ $subject->name }} ({{ $subject->code }})</td>
            <td width="20%"><strong>Fase / Kelas:</strong></td>
            <td width="30%">Fase {{ $subject->phase ?: 'D' }} {{ $grade ? "(Kelas {$grade})" : '(Semua Kelas)' }}</td>
        </tr>
        <tr>
            <td><strong>Jenjang:</strong></td>
            <td>{{ $subject->jenjang ?: 'SMP' }}</td>
            <td><strong>Tanggal Cetak:</strong></td>
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
</body>
</html>
