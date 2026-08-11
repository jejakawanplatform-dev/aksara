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

namespace App\Console\Commands;

use Illuminate\Console\Command;

class SeedDemo extends Command
{
    protected $signature = 'aksara:seed-demo';

    protected $description = 'Reset dan seed ulang data demo fiktif untuk workshop Aksara';

    public function handle(): int
    {
        if (! $this->confirm('Ini akan menghapus SEMUA data dan seed ulang. Lanjutkan?', true)) {
            $this->info('Dibatalkan.');

            return self::SUCCESS;
        }

        $this->info('🔄 Mereset database...');
        $this->call('migrate:fresh');

        $this->info('🌱 Seeding data demo...');
        $this->call('db:seed', ['--class' => 'DemoDataSeeder']);

        $this->newLine();
        $this->info('✅ Database berhasil di-reset dengan data demo!');
        $this->info('   Jalankan: php artisan serve');

        return self::SUCCESS;
    }
}
