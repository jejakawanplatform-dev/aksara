<?php

use App\Services\SettingService;

if (!function_exists('setting')) {
    /**
     * Global helper untuk mengambil nilai setting sistem (cache-backed).
     */
    function setting(?string $key = null, mixed $default = null): mixed
    {
        $service = app(SettingService::class);

        if (is_null($key)) {
            return $service;
        }

        return $service->get($key, $default);
    }
}
