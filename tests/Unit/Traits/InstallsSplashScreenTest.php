<?php

namespace Tests\Unit\Traits;

use Illuminate\Support\Facades\File;
use Native\Mobile\Traits\InstallsSplashScreen;
use Tests\TestCase;

class InstallsSplashScreenTest extends TestCase
{
    use InstallsSplashScreen;

    protected string $projectPath;

    protected function setUp(): void
    {
        parent::setUp();
        $this->projectPath = sys_get_temp_dir().'/nativephp_ios_splash_'.uniqid();
        File::makeDirectory($this->projectPath.'/public', 0755, true);
        File::makeDirectory($this->projectPath.'/nativephp/ios/NativePHP/Assets.xcassets', 0755, true);
        app()->setBasePath($this->projectPath);
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->projectPath);
        parent::tearDown();
    }

    public function test_svg_splash_writes_vector_contents_json(): void
    {
        File::put($this->projectPath.'/public/splash.svg', '<svg xmlns="http://www.w3.org/2000/svg" width="100" height="100"><rect width="100" height="100"/></svg>');

        $this->installIosSplashScreen();

        $imageset = $this->projectPath.'/nativephp/ios/NativePHP/Assets.xcassets/LaunchImage.imageset';
        $this->assertFileExists($imageset.'/splash.svg');
        $json = json_decode(File::get($imageset.'/Contents.json'), true);

        $this->assertTrue($json['properties']['preserves-vector-representation']);
        $this->assertSame('splash.svg', $json['images'][0]['filename']);
    }

    public function test_svg_takes_precedence_and_skips_png_path(): void
    {
        File::put($this->projectPath.'/public/splash.svg', '<svg xmlns="http://www.w3.org/2000/svg" width="100" height="100"></svg>');
        File::put($this->projectPath.'/public/splash.png', 'not-a-real-png');

        $this->installIosSplashScreen();

        $imageset = $this->projectPath.'/nativephp/ios/NativePHP/Assets.xcassets/LaunchImage.imageset';
        $this->assertFileExists($imageset.'/splash.svg');
        $this->assertFileDoesNotExist($imageset.'/splash.png');
    }
}
