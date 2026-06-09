<?php

namespace Tests\Unit\Traits;

use Native\Mobile\Traits\InstallsAndroid;
use Tests\TestCase;

class InstallsAndroidThemeSplashTest extends TestCase
{
    use InstallsAndroid;

    public function test_theme_xml_contains_splash_theme(): void
    {
        $method = new \ReflectionMethod($this, 'renderThemeXml');
        $method->setAccessible(true);

        $xml = $method->invoke($this, '#FF000000', '#FFFFFFFF', '#FFFFFFFF');

        $this->assertStringContainsString('Theme.AndroidPHP.Splash', $xml);
        $this->assertStringContainsString('windowSplashScreenBackground', $xml);
        $this->assertStringContainsString('postSplashScreenTheme', $xml);
        // Default system-splash icon is the app launcher icon (a centered, circle-masked
        // icon) — not the full-bleed @drawable/splash image, which looks wrong in the circle.
        $this->assertStringContainsString('@mipmap/ic_launcher', $xml);
        $this->assertStringContainsString('#FFFFFFFF', $xml); // background applied
        // The post-splash theme's window background matches the splash background so the
        // handoff from system splash to content doesn't flash white.
        $this->assertStringContainsString('android:windowBackground', $xml);
    }
}
