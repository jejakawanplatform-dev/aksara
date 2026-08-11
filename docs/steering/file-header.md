# File header — Aksara

Source-of-truth untuk header hak cipta di file source. Wajib dipatuhi manusia dan **semua agen** (Cursor, Claude Code, Copilot, Continue, handoff, dll.).

## Identitas

| Item | Nilai |
|---|---|
| Aplikasi | Aksara |
| Pengembang | jejakawan |
| Web | https://jejakawan.com |
| Lisensi | MIT (`LICENSE` di root) |
| Kebijakan | Clone, fork, dan modifikasi diizinkan (MIT); sertakan notice copyright |

Juga lihat: `NOTICE`, `docs/steering/handover.md`, ADR-013 di `decision-log.md`.

## Scope

**Pasang header pada:** `app/`, `bootstrap/*.php` (bukan cache), `config/`, `database/{migrations,seeders,factories}/`, `routes/`, `resources/js/`, `resources/css/`, `resources/views/`, `tests/`.

**Jangan sentuh:** `vendor/`, `node_modules/`, `public/build/`, `storage/`, `.env*`, lockfile, binary, cache.

## Aturan

1. File source **baru** di scope wajib punya header sesuai jenis file di bawah.
2. Jangan duplikasi bila sudah ada marker `@copyright`, `SPDX-License-Identifier`, atau `Aksara —`.
3. Jangan ubah `LICENSE` tanpa ADR di `decision-log.md`.
4. Header **ringkas** — bukan teks lisensi penuh.

## Template PHP

```php
<?php

/**
 * Aksara — platform pembelajaran berbantuan AI.
 *
 * @copyright 2026 jejakawan (https://jejakawan.com)
 * @license   MIT
 *
 * Clone, fork, and modification are permitted under the MIT License.
 * See the LICENSE file in the project root.
 */
```

Letakkan segera setelah `<?php` (dan setelah `declare(strict_types=1);` bila ada).

## Template Vue (baris pertama file)

```html
<!--
  Aksara — platform pembelajaran berbantuan AI.
  @copyright 2026 jejakawan (https://jejakawan.com)
  @license   MIT
  Clone, fork, and modification are permitted under the MIT License.
  See the LICENSE file in the project root.
-->
```

## Template JavaScript / CSS

```js
/**
 * Aksara — platform pembelajaran berbantuan AI.
 *
 * @copyright 2026 jejakawan (https://jejakawan.com)
 * @license   MIT
 *
 * Clone, fork, and modification are permitted under the MIT License.
 * See the LICENSE file in the project root.
 */
```

## Template Blade

```blade
{{--
  Aksara — platform pembelajaran berbantuan AI.
  @copyright 2026 jejakawan (https://jejakawan.com)
  @license   MIT
  Clone, fork, and modification are permitted under the MIT License.
  See the LICENSE file in the project root.
--}}
```

## Markdown / docs

Tidak wajib header penuh. Cukup merujuk `LICENSE` / `NOTICE` bila relevan.
