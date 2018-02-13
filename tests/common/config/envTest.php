<?php
namespace tests\common\config;

use PHPUnit\Framework\TestCase;

/**
 * Created by PhpStorm.
 * User: henryzhu
 * Date: 16-10-27
 * Time: 下午6:54
 * Email: henryzxj1989@gmail.com
 */
class envTest extends TestCase
{
    public function testGetEnvServerIp()
    {
        $this->assertNotEmpty(ENV_SERVER_IP);
    }

    public function testGetEnvServerLocalIp()
    {
        $this->assertNotEmpty(ENV_SERVER_LOCAL_IP);
    }

    public function testGetEnvRedisHost()
    {
        $this->assertNotEmpty(ENV_REDIS_HOST);
    }

    public function testGetEnvRedisPort()
    {
        $this->assertNotEmpty(ENV_REDIS_PORT);
    }
}