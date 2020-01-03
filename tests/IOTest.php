<?php

declare(strict_types=1);
include_once __DIR__ . '/stubs/GlobalStubs.php';
include_once __DIR__ . '/stubs/KernelStubs.php';
include_once __DIR__ . '/stubs/ModuleStubs.php';
include_once __DIR__ . '/stubs/MessageStubs.php';

use PHPUnit\Framework\TestCase;

class IOTest extends TestCase
{
    private $IoID = '{7681F2B3-FA3A-D6A1-F890-DAE6E3E9AFB3}';
    public function setUp(): void
    {
        //Reset
        IPS\Kernel::reset();
        //Register our library we need for testing
        IPS\ModuleLoader::loadLibrary(__DIR__ . '/../library.json');
        parent::setUp();
    }

    public function testGetChannels()
    {
        $id = IPS_CreateInstance($this->IoID);
        IPS_SetProperty($id, 'Open', true);
        if (isset($_ENV['COMPUTERNAME'])) {
            IPS_SetProperty($id, 'Host', '127.0.0.1');
            IPS_ApplyChanges($id);
        } else {
            IPS_SetProperty($id, 'Host', '127.0.0.1');
            @IPS_ApplyChanges($id);
        }

        $json = json_encode(
            [
                'Function'=> 'GetChannels',
                'Params'  => []
            ]
        );
        $Channels = IPS\InstanceManager::getInstanceInterface($id)->ForwardData($json);
        if (is_bool($Channels)) {
            $this->assertFalse($Channels);
            $this->
        } else {
            $this->assertGreaterThan(0, count(unserialize($Channels)));
        }
    }
}
