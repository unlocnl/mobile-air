<?php

namespace Native\Mobile\Traits;

use Illuminate\Support\Facades\File;

trait UpdatesSplashConfiguration
{
    /**
     * Replace the splash style token in MainActivity.kt
     */
    protected function updateSplashStyleConfiguration(): void
    {
        $mainActivityPath = base_path('nativephp/android/app/src/main/java/com/nativephp/mobile/ui/MainActivity.kt');

        if (! File::exists($mainActivityPath)) {
            return;
        }

        $content = File::get($mainActivityPath);
        $style = config('nativephp.android.splash.style', 'image');
        if (! in_array($style, ['image', 'native'], true)) {
            $style = 'image';
        }

        if (str_contains($content, 'REPLACE_SPLASH_STYLE')) {
            $content = str_replace('REPLACE_SPLASH_STYLE', $style, $content);
        } else {
            $content = preg_replace(
                '/val\s+splashStyle\s*=\s*"[^"]*"/',
                'val splashStyle = "'.$style.'"',
                $content
            );
        }

        File::put($mainActivityPath, $content);
    }

    /**
     * Point the system-splash icon at the app launcher icon in both styles.
     * The broad regex also heals themes still referencing a previous icon.
     */
    protected function updateSplashThemeIcon(): void
    {
        $icon = '@mipmap/ic_launcher';

        $themePaths = [
            base_path('nativephp/android/app/src/main/res/values/themes.xml'),
            base_path('nativephp/android/app/src/main/res/values-night/themes.xml'),
        ];

        foreach ($themePaths as $themePath) {
            if (! File::exists($themePath)) {
                continue;
            }

            $content = File::get($themePath);
            $content = preg_replace(
                '/(<item name="windowSplashScreenAnimatedIcon">)@[a-zA-Z]+\/[^<]*(<\/item>)/',
                '${1}'.$icon.'${2}',
                $content
            );

            File::put($themePath, $content);
        }
    }

    /**
     * Apply the configured splash background to the theme at build time.
     * writeAndroidTheme() only runs at install, so this keeps it in sync on rebuilds.
     */
    protected function updateSplashThemeBackground(): void
    {
        $map = [
            base_path('nativephp/android/app/src/main/res/values/themes.xml') => $this->normalizeSplashColor(
                config('nativephp.android.splash.background') ?: '#FFFFFF'
            ),
            base_path('nativephp/android/app/src/main/res/values-night/themes.xml') => $this->normalizeSplashColor(
                config('nativephp.android.splash.background_night') ?: '#000000'
            ),
        ];

        foreach ($map as $themePath => $color) {
            if (! File::exists($themePath)) {
                continue;
            }

            $content = File::get($themePath);

            // windowBackground tracks the splash color too, so the handoff doesn't flash white
            foreach (['windowSplashScreenBackground', 'android:windowBackground'] as $attr) {
                $content = preg_replace(
                    '/(<item name="'.preg_quote($attr, '/').'">)[^<]*(<\/item>)/',
                    '${1}'.$color.'${2}',
                    $content
                );
            }

            File::put($themePath, $content);
        }
    }

    /**
     * Normalize a hex color to opaque #AARRGGBB; fall back to white on invalid input.
     */
    private function normalizeSplashColor(string $value): string
    {
        if (! preg_match('/^#(?:[0-9a-fA-F]{6}|[0-9a-fA-F]{8})$/', $value)) {
            $value = '#FFFFFF';
        }

        $hex = strtoupper(ltrim($value, '#'));

        return '#'.(strlen($hex) === 6 ? 'FF'.$hex : $hex);
    }
}
