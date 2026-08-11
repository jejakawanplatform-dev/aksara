{{--
  Aksara — platform pembelajaran berbantuan AI.
  @copyright 2026 jejakawan (https://jejakawan.com)
  @license   MIT
  Clone, fork, and modification are permitted under the MIT License.
  See the LICENSE file in the project root.
--}}
@php
    $schoolName = $schoolName ?? (string) setting('school.name', 'SMP Negeri 1 Aksara');
    $schoolNpsn = (string) setting('school.npsn', '');
    $schoolAddress = (string) setting('school.address', '');
    $schoolPhone = (string) setting('school.phone', '');
    $schoolHeadmaster = (string) setting('school.headmaster', '');
    $initials = collect(preg_split('/\s+/', trim($schoolName)))
        ->filter()
        ->take(2)
        ->map(fn ($w) => mb_strtoupper(mb_substr($w, 0, 1)))
        ->implode('');
    if ($initials === '') {
        $initials = 'AK';
    }

    $metaParts = array_values(array_filter([
        $schoolNpsn !== '' ? 'NPSN '.$schoolNpsn : null,
        $schoolAddress !== '' ? $schoolAddress : null,
        $schoolPhone !== '' ? 'Telp. '.$schoolPhone : null,
    ]));
@endphp
<div class="kop">
    <div class="kop-mark">
        <span class="kop-badge">{{ $initials }}</span>
    </div>
    <div class="kop-body">
        <p class="jenjang">Pemerintah Daerah · Dinas Pendidikan</p>
        <p class="school-name">{{ $schoolName }}</p>
        <p class="meta">
            {{ implode(' · ', $metaParts) }}
            @if($schoolHeadmaster !== '')
                <br>Kepala Sekolah: {{ $schoolHeadmaster }}
            @endif
        </p>
    </div>
</div>
