{{--
  Aksara — platform pembelajaran berbantuan AI.
  @copyright 2026 jejakawan (https://jejakawan.com)
  @license   MIT
  Clone, fork, and modification are permitted under the MIT License.
  See the LICENSE file in the project root.
--}}
{{-- Shared print/PDF styles for Aksara export documents --}}
<style>
    body {
        font-family: "DejaVu Sans", "Helvetica Neue", Arial, sans-serif;
        font-size: 11px;
        color: #1e293b;
        margin: 18px 22px;
        line-height: 1.5;
    }
    .kop {
        display: table;
        width: 100%;
        border-bottom: 3px double #0f766e;
        padding-bottom: 10px;
        margin-bottom: 14px;
    }
    .kop-mark {
        display: table-cell;
        width: 64px;
        vertical-align: middle;
        text-align: center;
    }
    .kop-badge {
        display: inline-block;
        width: 52px;
        height: 52px;
        border: 2px solid #0f766e;
        border-radius: 50%;
        line-height: 48px;
        font-size: 11px;
        font-weight: 700;
        color: #0f766e;
        letter-spacing: 0.02em;
    }
    .kop-body {
        display: table-cell;
        vertical-align: middle;
        text-align: center;
        padding: 0 8px;
    }
    .kop-body .jenjang {
        margin: 0;
        font-size: 11px;
        font-weight: 700;
        letter-spacing: 0.08em;
        text-transform: uppercase;
        color: #0f766e;
    }
    .kop-body .school-name {
        margin: 2px 0 0;
        font-size: 16px;
        font-weight: 700;
        color: #134e4a;
        text-transform: uppercase;
        line-height: 1.25;
    }
    .kop-body .meta {
        margin: 4px 0 0;
        font-size: 9.5px;
        color: #475569;
        line-height: 1.45;
    }
    .doc-title {
        text-align: center;
        margin: 0 0 14px;
    }
    .doc-title h1 {
        margin: 0;
        font-size: 13px;
        font-weight: 700;
        color: #0f766e;
        text-transform: uppercase;
        letter-spacing: 0.04em;
    }
    .doc-title h2 {
        margin: 4px 0 0;
        font-size: 12px;
        font-weight: 600;
        color: #334155;
    }
    .doc-title p {
        margin: 4px 0 0;
        font-size: 10px;
        color: #64748b;
    }
    .section-title {
        font-weight: 700;
        font-size: 12px;
        color: #0f766e;
        margin-top: 16px;
        margin-bottom: 8px;
        border-bottom: 1px solid #cbd5e1;
        padding-bottom: 4px;
    }
    table.meta-table,
    table.data-table,
    table.tp-table,
    table.atp-table {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 12px;
        font-size: 11px;
    }
    table.meta-table th,
    table.meta-table td,
    table.data-table th,
    table.data-table td,
    table.tp-table th,
    table.tp-table td,
    table.atp-table th,
    table.atp-table td {
        border: 1px solid #cbd5e1;
        padding: 6px 8px;
        text-align: left;
        vertical-align: top;
    }
    table.meta-table th,
    table.data-table th,
    table.tp-table th {
        background: #f1f5f9;
        font-weight: 700;
        color: #0f766e;
    }
    table.data-table th {
        text-transform: uppercase;
        font-size: 9px;
    }
    table.atp-table th {
        background: #0f766e;
        color: #fff;
        font-weight: 700;
    }
    table.atp-table tr:nth-child(even) {
        background: #f8fafc;
    }
    .content-box {
        border: 1px solid #e2e8f0;
        background: #f8fafc;
        border-radius: 6px;
        padding: 12px;
        margin-bottom: 12px;
    }
    .cp-block {
        margin-bottom: 20px;
        page-break-inside: avoid;
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        padding: 12px;
        background: #f8fafc;
    }
    .cp-title {
        font-weight: 700;
        font-size: 13px;
        color: #0f766e;
        margin-bottom: 6px;
    }
    .cp-statement {
        font-style: italic;
        margin-bottom: 10px;
        color: #334155;
    }
    .doc-footer {
        margin-top: 28px;
        font-size: 10px;
        color: #64748b;
        border-top: 1px solid #e2e8f0;
        padding-top: 8px;
    }
    .sign-block {
        width: 42%;
        float: right;
        text-align: center;
        margin-top: 28px;
        font-size: 11px;
        color: #334155;
    }
    .sign-block .space {
        height: 56px;
    }
    .clear {
        clear: both;
    }
    @media print {
        body { margin: 0; }
        .no-print { display: none !important; }
    }
</style>
