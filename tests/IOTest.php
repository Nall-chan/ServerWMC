<?php

declare(strict_types=1);
include_once __DIR__ . '/stubs/GlobalStubs.php';
include_once __DIR__ . '/stubs/KernelStubs.php';
include_once __DIR__ . '/stubs/ModuleStubs.php';
include_once __DIR__ . '/stubs/MessageStubs.php';

use PHPUnit\Framework\TestCase;

class IOTest extends TestCase
{
    private static $IoID = '{7681F2B3-FA3A-D6A1-F890-DAE6E3E9AFB3}';
    private static $InstanceId = 0;

    public static function setUpBeforeClass(): void
    {
        //Reset
        IPS\Kernel::reset();
        //Register our library we need for testing
        IPS\ModuleLoader::loadLibrary(__DIR__ . '/../library.json');

        $id = IPS_CreateInstance(self::$IoID);
        IPS_SetProperty($id, 'Open', true);
        if (isset($_ENV['COMPUTERNAME'])) {
            IPS_SetProperty($id, 'Host', '192.168.201.253');
            IPS_ApplyChanges($id);
        } else {
            IPS_SetProperty($id, 'Host', '127.0.0.1');
            @IPS_ApplyChanges($id);
        }
        self::$InstanceId = $id;
    }

    public function setUp(): void
    {
        parent::setUp();
    }

    public function testGetChannels()
    {
        $json = json_encode(
            [
                'Function'=> 'GetChannels',
                'Params'  => []
            ]
        );
        $Channels = IPS\InstanceManager::getInstanceInterface(self::$InstanceId)->ForwardData($json);
        if (is_bool($Channels)) {
            $this->assertFalse($Channels);
        } else {
            $this->assertGreaterThan(0, count(unserialize($Channels)));
            print_r(count(unserialize($Channels)));
        }
    }
}
