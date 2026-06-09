<?php

namespace Tests\Unit\Traits;

use Illuminate\Support\Facades\File;
use Native\Mobile\Traits\InstallsAndroidSplashScreen;
use Native\Mobile\Traits\InstallsAppIcon;
use Native\Mobile\Traits\UpdatesSplashConfiguration;
use Tests\TestCase;

class PreparesBuildSplashTest extends TestCase
{
    use InstallsAndroidSplashScreen, InstallsAppIcon, UpdatesSplashConfiguration;

    protected string $testProjectPath;

    protected function setUp(): void
    {
        parent::setUp();

        $this->testProjectPath = sys_get_temp_dir().'/nativephp_prepare_build_test_'.uniqid();

        // Set up directory structure
        File::makeDirectory($this->testProjectPath.'/public', 0755, true);
        File::makeDirectory($this->testProjectPath.'/nativephp/android/app/src/main/res', 0755, true);

        // Set up base path for testing
        app()->setBasePath($this->testProjectPath);
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->testProjectPath);
        parent::tearDown();
    }

    public function test_splash_screen_integration_works_in_build_context()
    {
        // Create test splash image
        $this->createTestSplashImage();

        // Test that the method works in a build-like context
        $this->installAndroidSplashScreen();

        // Assert that splash images were generated
        $splashPath = $this->testProjectPath.'/nativephp/android/app/src/main/res/drawable-mdpi/splash.png';
        $this->assertFileExists($splashPath);
    }

    public function test_splash_screen_integration_skips_gracefully_without_image()
    {
        // Don't create any splash image

        // Execute - should not fail even without splash image
        $this->installAndroidSplashScreen();

        // Assert no splash image was generated
        $splashPath = $this->testProjectPath.'/nativephp/android/app/src/main/res/drawable-mdpi/splash.png';
        $this->assertFileDoesNotExist($splashPath);
    }

    public function test_replaces_splash_style_token(): void
    {
        config()->set('nativephp.android.splash.style', 'native');

        $mainActivity = $this->androidJavaPath().'/MainActivity.kt';
        File::ensureDirectoryExists(dirname($mainActivity));
        File::put($mainActivity, 'private val splashStyle = "REPLACE_SPLASH_STYLE"');

        $this->updateSplashStyleConfiguration();

        $this->assertStringContainsString('private val splashStyle = "native"', File::get($mainActivity));
        $this->assertStringNotContainsString('REPLACE_SPLASH_STYLE', File::get($mainActivity));
    }

    public function test_image_style_uses_app_icon(): void
    {
        config()->set('nativephp.android.splash.style', 'image');
        $this->writeSplashThemes();

        $this->updateSplashThemeIcon();

        foreach ($this->splashThemePaths() as $themePath) {
            $this->assertStringContainsString(
                '<item name="windowSplashScreenAnimatedIcon">@mipmap/ic_launcher</item>',
                File::get($themePath)
            );
        }
    }

    public function test_native_style_uses_app_icon(): void
    {
        config()->set('nativephp.android.splash.style', 'native');
        $this->writeSplashThemes();

        $this->updateSplashThemeIcon();

        foreach ($this->splashThemePaths() as $themePath) {
            $this->assertStringContainsString(
                '<item name="windowSplashScreenAnimatedIcon">@mipmap/ic_launcher</item>',
                File::get($themePath)
            );
        }
    }

    public function test_build_applies_splash_background_per_mode(): void
    {
        config()->set('nativephp.android.splash.background', '#123456');
        config()->set('nativephp.android.splash.background_night', '#654321');
        $this->writeSplashThemes();

        $this->updateSplashThemeBackground();

        $light = File::get(base_path('nativephp/android/app/src/main/res/values/themes.xml'));
        $night = File::get(base_path('nativephp/android/app/src/main/res/values-night/themes.xml'));

        $this->assertStringContainsString('<item name="windowSplashScreenBackground">#FF123456</item>', $light);
        $this->assertStringContainsString('<item name="windowSplashScreenBackground">#FF654321</item>', $night);

        // Post-splash window background tracks the splash background to avoid a white flash
        $this->assertStringContainsString('<item name="android:windowBackground">#FF123456</item>', $light);
        $this->assertStringContainsString('<item name="android:windowBackground">#FF654321</item>', $night);
    }

    private function androidJavaPath(): string
    {
        return base_path('nativephp/android/app/src/main/java/com/nativephp/mobile/ui');
    }

    private function splashThemePaths(): array
    {
        return [
            base_path('nativephp/android/app/src/main/res/values/themes.xml'),
            base_path('nativephp/android/app/src/main/res/values-night/themes.xml'),
        ];
    }

    private function writeSplashThemes(): void
    {
        $xml = '<resources>'
            .'<style name="Theme.AndroidPHP" parent="Theme.MaterialComponents.DayNight.DarkActionBar">'
            .'<item name="android:windowBackground">#FFFFFFFF</item>'
            .'</style>'
            .'<style name="Theme.AndroidPHP.Splash" parent="Theme.SplashScreen">'
            .'<item name="windowSplashScreenBackground">#FFFFFFFF</item>'
            .'<item name="windowSplashScreenAnimatedIcon">@drawable/splash</item>'
            .'</style>'
            .'</resources>';

        foreach ($this->splashThemePaths() as $themePath) {
            File::ensureDirectoryExists(dirname($themePath));
            File::put($themePath, $xml);
        }
    }

    /**
     * Create a test splash image
     */
    protected function createTestSplashImage(): void
    {
        $splashPath = $this->testProjectPath.'/public/splash.png';

        $image = imagecreatetruecolor(1080, 1920);
        $blue = imagecolorallocate($image, 0, 100, 200);
        imagefill($image, 0, 0, $blue);

        imagepng($image, $splashPath);
        imagedestroy($image);
    }

    protected function logToFile(string $message): void {}

    protected function info($message): void
    {
        // Mock implementation
    }

    protected function warn($message): void
    {
        // Mock implementation
    }
}
