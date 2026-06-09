<?php

namespace Tests\Unit\Config;

use Tests\TestCase;

class SplashConfigTest extends TestCase
{
    public function test_splash_config_defaults(): void
    {
        $config = require __DIR__.'/../../../config/nativephp.php';

        $this->assertSame('image', $config['android']['splash']['style']);
        $this->assertSame('#FFFFFF', $config['android']['splash']['background']);
        $this->assertSame('#000000', $config['android']['splash']['background_night']);
    }
}
