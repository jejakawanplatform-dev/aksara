<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Rekap Rencana Pembelajaran (Modul Ajar)</title>
    <style>
        body { font-family: sans-serif; font-size: 11px; color: #1e293b; margin: 20px; line-height: 1.4; }
        .header { text-align: center; border-bottom: 2px solid #0d9488; padding-bottom: 12px; margin-bottom: 16px; }
        .header h1 { margin: 0; font-size: 16px; color: #0f766e; text-transform: uppercase; }
        .header p { margin: 4px 0 0 0; font-size: 10px; color: #64748b; }
        table.data-table { width: 100%; border-collapse: collapse; margin-top: 10px; font-size: 10px; }
        table.data-table th, table.data-table td { border: 1px solid #cbd5e1; padding: 6px 8px; text-align: left; }
        table.data-table th { background: #f1f5f9; font-weight: bold; color: #0f766e; text-transform: uppercase; font-size: 9px; }
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
        <h1>Rekap Rencana Pembelajaran (Modul Ajar)</h1>
        <p>Satuan Pendidikan: {{ $schoolName }} · Kurikulum Merdeka · Tanggal Cetak: {{ now()->translatedFormat('d F Y') }}</p>
    </div>

    <table class="data-table">
        <thead>
            <tr>
                <th width="4%">No</th>
                <th width="22%">Topik Pembelajaran</th>
                <th width="15%">Mapel & Rombel</th>
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
                    <td>{{ $plan->subject->name ?? '—' }}<br><span style="color: #64748b;">Kelas {{ $plan->class->name ?? $plan->grade }}</span></td>
                    <td>Fase {{ $plan->phase }}<br>{{ $plan->duration_minutes }} menit</td>
                    <td>{{ $plan->learning_objectives }}</td>
                    <td>{{ $plan->teacher->name ?? '—' }}</td>
                    <td>{{ $plan->status->label() }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" style="text-align: center; color: #94a3b8; padding: 12px;">Belum ada Rencana Pembelajaran.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
