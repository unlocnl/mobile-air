<?php

namespace Tests\Unit\Resources;

use Tests\TestCase;

class AndroidSplashResourcesTest extends TestCase
{
    private function resource(string $relative): string
    {
        return file_get_contents(__DIR__.'/../../../resources/androidstudio/'.$relative);
    }

    public function test_core_splashscreen_in_version_catalog(): void
    {
        $toml = $this->resource('gradle/libs.versions.toml');
        $this->assertStringContainsString('androidx-core-splashscreen', $toml);
    }

    public function test_core_splashscreen_dependency_in_gradle(): void
    {
        $gradle = $this->resource('app/build.gradle.kts');
        $this->assertStringContainsString('libs.androidx.core.splashscreen', $gradle);
    }

    public function test_manifest_uses_splash_theme(): void
    {
        $manifest = $this->resource('app/src/main/AndroidManifest.xml');
        $this->assertStringContainsString('@style/Theme.AndroidPHP.Splash', $manifest);
    }

    public function test_mainactivity_wires_native_splash(): void
    {
        $kt = $this->resource('app/src/main/java/com/nativephp/mobile/ui/MainActivity.kt');
        $this->assertStringContainsString('installSplashScreen()', $kt);
        $this->assertStringContainsString('setKeepOnScreenCondition', $kt);
        $this->assertStringContainsString('REPLACE_SPLASH_STYLE', $kt);
    }

    public function test_placeholder_splash_drawable_exists_and_is_vector(): void
    {
        $path = __DIR__.'/../../../resources/androidstudio/app/src/main/res/drawable/splash.xml';
        $this->assertFileExists($path);

        $doc = simplexml_load_string(file_get_contents($path));
        $this->assertNotFalse($doc, 'splash.xml must be valid XML');
        $this->assertEquals('vector', $doc->getName(), 'splash.xml root element must be <vector>');
    }
}
