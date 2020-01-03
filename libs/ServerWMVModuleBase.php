<?php

declare(strict_types=1);

eval('declare(strict_types=1);namespace ServerWMVModuleBase {?>' . file_get_contents(__DIR__ . '/../libs/helper/DebugHelper.php') . '}');
eval('declare(strict_types=1);namespace ServerWMVModuleBase {?>' . file_get_contents(__DIR__ . '/../libs/helper/BufferHelper.php') . '}');
eval('declare(strict_types=1);namespace ServerWMVModuleBase {?>' . file_get_contents(__DIR__ . '/../libs/helper/WebhookHelper.php') . '}');

/**
 * @property array $Channels
 */
class ServerWMVModuleBase extends IPSModule
{
    use \ServerWMVModuleBase\DebugHelper;
    use
        \ServerWMVModuleBase\BufferHelper;
    use
        \ServerWMVModuleBase\WebhookHelper;
    public static $FunctionFilter = '';

    public function Create()
    {
        //Never delete this line!
        parent::Create();
        $this->Channels = [];
        $this->ConnectParent('{7681F2B3-FA3A-D6A1-F890-DAE6E3E9AFB3}');
    }

    public function Destroy()
    {
        //Never delete this line!
        parent::Destroy();
    }

    public function ApplyChanges()
    {
        //Never delete this line!
        parent::ApplyChanges();
        $this->SetReceiveDataFilter('.*("Function":"GetChannels"|"Function":"' . static::$FunctionFilter . '").*');
    }

    public function MessageSink($TimeStamp, $SenderID, $Message, $Data)
    {
        switch ($Message) {
            case IPS_KERNELSTARTED:
                IPS_RequestAction($this->InstanceID, 'KernelReady', true);
                break;
        }
    }

    public function ReceiveData($JSONString)
    {
        $data = json_decode($JSONString, true);
        unset($data['DataID']);
        $this->SendDebug('Receive', $data, 0);
        if ($data['Function'] == static::$FunctionFilter) {
            $this->{static::$FunctionFilter}($data['Data']);
            return;
        }
        if ($data['Function'] == 'GetChannels') {
            $this->GetChannels($data['Data']);
            return;
        }
    }

    public function RequestAction($Ident, $Value)
    {
        if ($Ident == 'KernelReady') {
            return $this->KernelReady();
        }
    }

    protected function Send(string $Function, array $Params = [])
    {
        if (!$this->HasActiveParent()) {
            return null;
        }
        if (count($Params) == 0) {
            $this->SendDebug('Send:' . $Function, '', 0);
        } else {
            $this->SendDebug('Send:' . $Function, $Params, 0);
        }
        $Result = $this->SendDataToParent(
                json_encode(
                        [
                            'DataID'   => '{59C978F7-9E10-2E66-2F66-CE762F9EEC97}',
                            'Function' => $Function,
                            'Params'   => $Params
                        ]
        ));
        if ($Result === false) {
            return null;
        }
        $this->SendDebug('Response', unserialize($Result), 0);
        return unserialize($Result);
    }

    protected function GetChannels(array $Channels)
    {
        foreach ($Channels as $key => $Channel) {
            $Channels[$key] = explode('|', $Channel);
        }
        $this->SendDebug('GetChannels', $Channels, 0);
        return true;
    }

    /**
     * Liefert den Header der HTML-Tabelle.
     *
     * @param array $Config Die Kofiguration der Tabelle
     *
     * @return string HTML-String
     */
    protected function GetTableHeader($Config_Table, $Config_Columns, $Sort, $HookPrefix, $NewSecret)
    {
        $table = '';
        // Kopf der Tabelle erzeugen
        $table .= '<table style="' . $Config_Table['<table>'] . '">' . PHP_EOL;
        // JS Rückkanal erzeugen
        $table .= '<script type="text/javascript" id="script' . $this->InstanceID . '">
function xhrGet' . $this->InstanceID . '(o)
{
    var HTTP = new XMLHttpRequest();
    HTTP.open(\'GET\',o.url,true);
    HTTP.send();
    HTTP.addEventListener(\'load\', function()
    {
        if (HTTP.status >= 200 && HTTP.status < 300)
        {
            if (HTTP.responseText !== \'OK\')
                sendError' . $this->InstanceID . '(HTTP.responseText);
        } else {
            sendError' . $this->InstanceID . '(HTTP.statusText);
        }
    });
}

function sendError' . $this->InstanceID . '(data)
{
var notify = document.getElementsByClassName("ipsNotifications")[0];
var newDiv = document.createElement("div");
newDiv.innerHTML =\'<div style="height:auto; visibility: hidden; overflow: hidden; transition: height 500ms ease-in 0s" class="ipsNotification"><div class="spacer"></div><div class="message icon error" onclick="document.getElementsByClassName(\\\'ipsNotifications\\\')[0].removeChild(this.parentNode);"><div class="ipsIconClose"></div><div class="content"><div class="title">Fehler</div><div class="text">\' + data + \'</div></div></div></div>\';
if (notify.childElementCount === 0)
	var thisDiv = notify.appendChild(newDiv.firstChild);
else
	var thisDiv = notify.insertBefore(newDiv.firstChild,notify.childNodes[0]);
var newheight = window.getComputedStyle(thisDiv, null)["height"];
thisDiv.style.height = "0px";
thisDiv.style.visibility = "visible";
function sleep (time) {
  return new Promise((resolve) => setTimeout(resolve, time));
}
sleep(10).then(() => {
	thisDiv.style.height = newheight;
});
}

</script>';
        $table .= '<colgroup>' . PHP_EOL;
        $colgroup = [];
        foreach ($Config_Columns as $Column) {
            if ($Column['show'] !== true) {
                continue;
            }
            $colgroup[$Column['index']] = '<col width="' . $Column['width'] . 'em" />' . PHP_EOL;
        }
        ksort($colgroup);
        $table .= implode('', $colgroup) . '</colgroup>' . PHP_EOL;
        $table .= '<thead style="' . $Config_Table['<thead>'] . '">' . PHP_EOL;
        $table .= '<tr>';
        $th = [];
        foreach ($Config_Columns as $Column) {
            if ($Column['show'] !== true) {
                continue;
            }
            $ThStyle = [];
            if ($Column['color'] >= 0) {
                $ThStyle[] = 'color:#' . substr('000000' . dechex($Column['color']), -6);
            }
            $ThStyle[] = 'text-align:' . $Column['align'];
            $ThStyle[] = $Column['style'];
            $Icon = '';
            $JS = '';
            if (is_array($Sort)) {
                if ($Column['key'] == $Sort['Index']) {
                    if ($Sort['desc'] == SORT_ASC) {
                        $Icon = '<div class="iconMediumSpinner ipsIconHollowArrowDown" style="background-position: center center;"></div>';
                    } else {
                        $Icon = '<div class="iconMediumSpinner ipsIconHollowArrowUp" style="background-position: center center;"></div>';
                    }
                }
            }
            if ($Sort !== null) {
                $LineSecret = rawurlencode(base64_encode(sha1($NewSecret . '0' . $Column['key'], true)));
                $JS = ' onclick="eval(document.getElementById(\'script' . $this->InstanceID . '\').innerHTML.toString()); window.xhrGet' . $this->InstanceID . '({ url: \'hook/' . $HookPrefix . $this->InstanceID . '?action=Sort&value=' . $Column['key'] . '&Secret=' . $LineSecret . '\' });"';
            }
            $th[$Column['index']] = '<th style="' . implode(';', $ThStyle) . ';"' . $JS . '>' . $Column['name'] . $Icon . '</th>';
        }
        ksort($th);
        $table .= implode('', $th) . '</tr>' . PHP_EOL;
        $table .= '</thead>' . PHP_EOL;
        $table .= '<tbody style="' . $Config_Table['<tbody>'] . '">' . PHP_EOL;
        return $table;
    }

    /**
     * Liefert den Inhalt der HTML-Box für ein Tabelle.
     *
     * @param array  $Data        Die Nutzdaten der Tabelle.
     * @param string $HookPrefix  Der Prefix des Webhook.
     * @param string $HookType    Ein String welcher als Parameter Type im Webhook übergeben wird.
     * @param string $HookId      Der Index aus dem Array $Data welcher die Nutzdaten (Parameter ID) des Webhook enthält.
     * @param int    $CurrentLine Die Aktuelle Zeile welche als Aktiv erzeugt werden soll.
     *
     * @return string Der HTML-String.
     */
    protected function GetTable($Data, $HookPrefix, $HookType, $HookId, $CurrentLine, $Sort = null)
    {
        $Config_Table = array_column(json_decode($this->ReadPropertyString('Table'), true), 'style', 'tag');
        $Config_Columns = json_decode($this->ReadPropertyString('Columns'), true);
        $Config_Rows = json_decode($this->ReadPropertyString('Rows'), true);
        $Config_Rows_BgColor = array_column($Config_Rows, 'bgcolor', 'row');
        $Config_Rows_Color = array_column($Config_Rows, 'color', 'row');
        $Config_Rows_Style = array_column($Config_Rows, 'style', 'row');

        $NewSecret = base64_encode(openssl_random_pseudo_bytes(12));
        $this->{'WebHookSecret' . $HookType} = $NewSecret;
        $HTMLData = $this->GetTableHeader($Config_Table, $Config_Columns, $Sort, $HookPrefix, $NewSecret);
        $pos = 0;
        if (count($Data) > 0) {
            foreach ($Data as $Line) {
                $Line['Position'] = $pos + 1;
                $LineIndex = ($pos % 2 ? 'odd' : 'even');
                $TrStyle = [];
                if ($Config_Rows_BgColor[$LineIndex] >= 0) {
                    $TrStyle[] = 'background-color:#' . substr('000000' . dechex($Config_Rows_BgColor[$LineIndex]), -6);
                }
                if ($Config_Rows_Color[$LineIndex] >= 0) {
                    $TrStyle[] = 'color:#' . substr('000000' . dechex($Config_Rows_Color[$LineIndex]), -6);
                }
                $TdStyle[] = $Config_Rows_Style[$LineIndex];
                $JS = '';
                if ($HookId != '') {
                    $LineSecret = rawurlencode(base64_encode(sha1($NewSecret . '0' . $Line[$HookId], true)));
                    $JS = ' onclick="eval(document.getElementById(\'script' . $this->InstanceID . '\').innerHTML.toString()); window.xhrGet' . $this->InstanceID . '({ url: \'hook/' . $HookPrefix . $this->InstanceID . '?action=' . $HookType . '&value=' . $Line[$HookId] . '&Secret=' . $LineSecret . '\' });"';
                }
                $HTMLData .= '<tr style="' . implode(';', $TrStyle) . ';"' . $JS . '>';

                $td = [];
                foreach ($Config_Columns as $Column) {
                    if ($Column['show'] !== true) {
                        continue;
                    }
                    if (!array_key_exists($Column['key'], $Line)) {
                        $Line[$Column['key']] = '';
                    }
                    $TdStyle = [];
                    $TdStyle[] = 'text-align:' . $Column['align'];
                    $TdStyle[] = $Column['style'];

                    $td[$Column['index']] = '<td style="' . implode(';', $TdStyle) . ';">' . (string) $Line[$Column['key']] . '</td>';
                }
                ksort($td);
                $HTMLData .= implode('', $td) . '</tr>';
                $HTMLData .= '</tr>' . PHP_EOL;
                $pos++;
            }
        }
        $HTMLData .= $this->GetTableFooter();
        return $HTMLData;
    }

    /**
     * Liefert den Footer der HTML-Tabelle.
     *
     * @return string HTML-String
     */
    protected function GetTableFooter()
    {
        $table = '</tbody>' . PHP_EOL;
        $table .= '</table>' . PHP_EOL;
        return $table;
    }

    protected function ConvertSeconds(int $Time)
    {
        if ($Time > 3600) {
            return sprintf('%02d:%02d:%02d', ($Time / 3600), ($Time / 60 % 60), $Time % 60);
        } else {
            return sprintf('%02d:%02d', ($Time / 60 % 60), $Time % 60);
        }
    }

    protected function SetSort($Index)
    {
        $Sort = $this->Sort;
        if ($Sort == null) {
            $Sort = [
                'Index' => $Index,
                'desc'  => SORT_ASC
            ];
        } else {
            if ($Sort['Index'] != $Index) {
                $Sort = [
                    'Index' => $Index,
                    'desc'  => SORT_ASC
                ];
            } else {
                if ($Sort['desc'] == SORT_ASC) {
                    $Sort['desc'] = SORT_DESC;
                } else {
                    $Sort = true;
                }
            }
        }
        $this->Sort = $Sort;
    }

    private function KernelReady()
    {
        $this->ApplyChanges();
    }
}
