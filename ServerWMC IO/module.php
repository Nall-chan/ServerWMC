<?php

declare(strict_types=1);

eval('declare(strict_types=1);namespace ServerWMCIO {?>' . file_get_contents(__DIR__ . '/../libs/helper/DebugHelper.php') . '}');
eval('declare(strict_types=1);namespace ServerWMCIO {?>' . file_get_contents(__DIR__ . '/../libs/helper/BufferHelper.php') . '}');
eval('declare(strict_types=1);namespace ServerWMCIO {?>' . file_get_contents(__DIR__ . '/../libs/helper/SemaphoreHelper.php') . '}');

/**
 * @property string $Host
 * @property int $GetChannels
 * @property int $GetSeriesTimers
 * @property int $GetTimers
 * @property int $GetRecordings
 */
class ServerWMCIO extends IPSModule
{
    use \ServerWMCIO\DebugHelper,
        \ServerWMCIO\BufferHelper,
        \ServerWMCIO\Semaphore;
    public function Create()
    {
        //Never delete this line!
        parent::Create();
        $this->RegisterPropertyBoolean('Open', false);
        $this->RegisterPropertyString('Host', 'localhost');
        $this->RegisterPropertyInteger('Port', 9080);
        $this->RegisterPropertyInteger('Intervall', 5);
        $this->RegisterTimer('Update', 0, 'WMC_Update($_IPS["TARGET"]);');
        $this->Host = '';
        $this->GetChannels = 0;
        $this->GetSeriesTimers = 0;
        $this->GetTimers = 0;
        $this->GetRecordings = 0;
    }

    public function Destroy()
    {
        //Never delete this line!
        parent::Destroy();
    }

    public function ApplyChanges()
    {
        $this->GetChannels = 0;
        $this->GetSeriesTimers = 0;
        $this->GetTimers = 0;
        $this->GetRecordings = 0;
        //Never delete this line!
        parent::ApplyChanges();
        $this->Host = trim($this->ReadPropertyString('Host'));
        if ($this->ReadPropertyBoolean('Open')) {
            if ($this->Host == '') {
                $this->SetTimerInterval('Update', 0);
                $this->SetStatus(IS_INACTIVE);
                return;
            }
            if (!$this->IsServerOnline(false)) {
                return;
            }
            $this->SetStatus(IS_ACTIVE);
            $this->SetTimerInterval('Update', $this->ReadPropertyInteger('Intervall') * 60 * 1000);
            $this->GetChannels();
            $this->GetSeriesTimers();
            $this->GetTimers();
            $this->GetRecordings();
        } else {
            $this->SetTimerInterval('Update', 0);
            $this->SetStatus(IS_INACTIVE);
            return;
        }
    }

    public function Update()
    {
        $this->IsServerOnline();
        $this->GetChannels();
        $this->GetSeriesTimers();
        $this->GetTimers();
        $this->GetRecordings();
    }

    protected function GetChannels()
    {
        return $this->RequestAndForward(__FUNCTION__);
    }

    protected function GetSeriesTimers()
    {
        return $this->RequestAndForward(__FUNCTION__);
    }

    protected function GetTimers()
    {
        return $this->RequestAndForward(__FUNCTION__);
    }

    protected function GetRecordings()
    {
        return $this->RequestAndForward(__FUNCTION__);
    }

    protected function RequestAndForward($Function)
    {
        $Result = $this->Send($Function);
        if ($Result === null) {
            return false;
        }
        array_pop($Result);
        if ($this->{$Function} != count($Result)) {
            $this->{$Function} = count($Result);
            $this->SendToChilds($Function, $Result);
        }
        return $Result;
    }

    protected function RequestInt($Function)
    {
        $Result = $this->Send($Function);
        if ($Result === null) {
            return false;
        }
        return (int) $Result[0];
    }

    protected function IsServerOnline(bool $UseEvents = true)
    {
        $Result = $this->Send('GetServiceStatus', ['2.4.4', strtolower(PHP_OS_FAMILY)]);
        if ($Result != null) {
            if (strtolower($Result[0]) === 'true') {
                return true;
            }
        }
        $this->SetTimerInterval('Update', 0);
        $this->SetStatus(IS_EBASE + 1);
        return false;
    }

    protected function Send(string $Function, array $Params = [])
    {
        $Hostname = $_ENV['COMPUTERNAME'] . '_IPS';
        array_unshift($Params, $Function);
        array_unshift($Params, $Hostname);
        $Request = implode('|', $Params);
        $Request .= '<Client Quit>';
        $Socket = false;
        try {
            $Socket = @stream_socket_client('tcp://192.168.201.253:9080', $errno, $errstr, 2);
            if (!$Socket) {
                throw new Exception($errstr, E_USER_NOTICE);
            }
            stream_set_timeout($Socket, 5);


            $this->SendDebug('Send', $Request, 0);
            fwrite($Socket, $Request);
            $result = stream_get_line($Socket, 1024 * 1024 * 2, '<EOF>');
            if ($result === false) {
                throw new Exception($this->Translate('No anwser from ServerWMC'), E_USER_NOTICE);
            }
            fclose($Socket);
            $this->SendDebug('Receive', $result, 0);
            $lines = explode('<EOL>', $result);
            return $lines;
        } catch (Exception $ex) {
            if ($Socket) {
                fclose($Socket);
            }
            $this->SendDebug('Error', $ex->getMessage(), 0);
            trigger_error($ex->getMessage(), $ex->getCode());
            $this->SetTimerInterval('Update', 0);
            $this->SetStatus(IS_EBASE + 1);
        }
        return null;
    }

    public function ForwardData($JSONString)
    {
        $data = json_decode($JSONString, true);
        unset($data['DataID']);
        $this->SendDebug('Forward', $data, 0);
        if ($this->GetStatus() != IS_ACTIVE) {
            $this->SendDebug('Error', 'not active', 0);
            return serialize(null);
        }
        $result = $this->Send($data['Function'], $data['Params']);
        return serialize($result);
    }

    protected function SendToChilds(string $Function, array $Data)
    {
        $this->SendDataToChildren(
            json_encode(
                    ['DataID'   => '{F4534A2A-49F8-62CA-48C0-27AAB61B415C}',
                            'Function' => $Function,
                            'Data'     => $Data
                        ]
                )
        );
    }
}
