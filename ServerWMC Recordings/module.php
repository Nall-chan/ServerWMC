<?php

declare(strict_types=1);

require_once __DIR__ . '/../libs/ServerWMVModuleBase.php';

/**
 * @property array $Recordings
 * @property array $Sort
 */
class ServerWMCRecordings extends ServerWMVModuleBase
{
    public static $FunctionFilter = 'GetRecordings';
    public static $RecordingKeys = [
        'RecordingID',
        'Title',
        'File',
        'Directory',
        'PlotOutline',
        'Plot',
        'ChannelName',
        'IconPath',
        'ThumbnailPath',
        'RecordingTime',
        'Duration',
        'Priority',
        'Lifetime',
        'GenreType',
        'GenreSubType'
    ];

    public function Create()
    {
        //Never delete this line!
        parent::Create();
        $Style = $this->GenerateHTMLStyleProperty();
        $this->RegisterPropertyString('Table', json_encode($Style['Table']));
        $this->RegisterPropertyString('Columns', json_encode($Style['Columns']));
        $this->RegisterPropertyString('Rows', json_encode($Style['Rows']));
        $this->Recordings = [];
        $this->Sort = true;
        $this->RegisterMessage(0, IPS_KERNELSTARTED);
    }

    public function Destroy()
    {
        if (!IPS_InstanceExists($this->InstanceID)) {
            $this->UnregisterHook('/hook/ServerWMCRecoring/' . $this->InstanceID);
        }
        parent::Destroy();
    }

    public function ApplyChanges()
    {
        //Never delete this line!
        parent::ApplyChanges();
        if (IPS_GetKernelRunlevel() != KR_READY) {
            return;
        }
        $this->RegisterHook('/hook/ServerWMCRecoring/' . $this->InstanceID);
        $this->RefreshHTMLData($this->Recordings);
    }

    public function GetRecordingsData()
    {
        return $this->Recordings;
    }

    protected function GetRecordings(array $Recordings)
    {
        foreach ($Recordings as $key => $Recording) {
            $Recordings[$key] = array_combine(self::$RecordingKeys, array_chunk(explode('|', $Recording), count(self::$RecordingKeys))[0]);
            $Recordings[$key]['PlotShort'] = substr($Recordings[$key]['Plot'], 0, 50);
            $Recordings[$key]['iDuration'] = (int) $Recordings[$key]['Duration'];
            $Recordings[$key]['Duration'] = $this->ConvertSeconds((int) $Recordings[$key]['Duration']);
            $Recordings[$key]['iRecordingTime'] = (int) $Recordings[$key]['RecordingTime'];
            $Recordings[$key]['RecordingTime'] = strftime('%c', (int) $Recordings[$key]['RecordingTime']);
        }
        $this->Recordings = $Recordings;
        $this->SendDebug('GetRecordings', $Recordings, 0);
        $this->RefreshHTMLData($Recordings);
    }

    protected function RefreshHTMLData(array $Recordings)
    {
        $vid = @$this->GetIDForIdent('Recordings');
        if ($vid === false) {
            $vid = $this->RegisterVariableString('Recordings', $this->Translate('Recordings'), '~HTMLBox', 0);
            IPS_SetIcon($vid, 'Database');
        }
        $Sort = $this->Sort;
        if (is_array($Sort)) {
            $Index = $Sort['Index'];
            if ($Sort['Index'] == 'Duration') {
                $Index = 'iDuration';
            }
            if ($Sort['Index'] == 'RecordingTime') {
                $Index = 'iRecordingTime';
            }
            $sort = array_column($Recordings, $Index);
            array_multisort($sort, $Sort['desc'], $Recordings);
        }
        $HTML = $this->GetTable($Recordings, 'ServerWMCRecoring/', '', '', -1, $Sort);
        $this->SetValue('Recordings', $HTML);

        return true;
    }

    protected function ProcessHookData()
    {
        http_response_code(200);
        header('Connection: close');
        header('Server: Symcon ' . IPS_GetKernelVersion());
        header('X-Powered-By: ServerWMC Module');
        header('Expires: 0');
        header('Cache-Control: no-cache');
        header('Content-Type: text/plain');

        if ((!isset($_GET['action'])) || (!isset($_GET['value'])) || (!isset($_GET['Secret']))) {
            echo 'Invalid parameters.';
            return;
        }
        $MySecret = $this->{'WebHookSecretRecording'};
        $CalcSecret = base64_encode(sha1($MySecret . '0' . $_GET['value'], true));
        //$this->SendDebug('Calc', $CalcSecret, 0);
        //$this->SendDebug('Got', rawurldecode($_GET['Secret']), 0);

        // IPS Bug
        /*if ($CalcSecret != rawurldecode($_GET['Secret'])) {
            echo $this->Translate('Access denied');
            return;
        }*/

        switch ($_GET['action']) {
            case 'Sort':
                $this->SetSort($_GET['value']);
                $this->RefreshHTMLData($this->Recordings);
                echo 'OK';
                return;
        }
        echo 'Invalid parameters.';
    }

    /**
     * Liefert die Werkeinstellungen fÃ¼r die Eigenschaften Html, Table und Rows.
     *
     * @return array
     */
    private function GenerateHTMLStyleProperty()
    {
        $NewTableConfig = [
            [
                'tag'   => '<table>',
                'style' => 'margin:0 auto; font-size:0.8em;'],
            [
                'tag'   => '<thead>',
                'style' => ''],
            [
                'tag'   => '<tbody>',
                'style' => '']
        ];
        $NewColumnsConfig = [
            [
                'index' => 0,
                'key'   => 'RecordingID',
                'name'  => 'Recording ID',
                'show'  => false,
                'width' => 100,
                'color' => 0xffffff,
                'align' => 'center',
                'style' => ''
            ],
            [
                'index' => 1,
                'key'   => 'Title',
                'name'  => $this->Translate('Title'),
                'show'  => true,
                'width' => 300,
                'color' => 0xffffff,
                'align' => 'left',
                'style' => ''
            ],
            [
                'index' => 2,
                'key'   => 'File',
                'name'  => $this->Translate('File'),
                'show'  => false,
                'width' => 300,
                'color' => 0xffffff,
                'align' => 'left',
                'style' => ''
            ],
            [
                'index' => 3,
                'key'   => 'Directory',
                'name'  => $this->Translate('Directory'),
                'show'  => false,
                'width' => 300,
                'color' => 0xffffff,
                'align' => 'left',
                'style' => ''
            ],
            [
                'index' => 4,
                'key'   => 'PlotOutline',
                'name'  => 'Plot Outline',
                'show'  => false,
                'width' => 300,
                'color' => 0xffffff,
                'align' => 'left',
                'style' => ''
            ],
            [
                'index' => 5,
                'key'   => 'PlotShort',
                'name'  => 'Plot Short',
                'show'  => true,
                'width' => 400,
                'color' => 0xffffff,
                'align' => 'left',
                'style' => ''
            ],
            [
                'index' => 6,
                'key'   => 'Plot',
                'name'  => 'Plot',
                'show'  => false,
                'width' => 300,
                'color' => 0xffffff,
                'align' => 'left',
                'style' => ''
            ],
            [
                'index' => 7,
                'key'   => 'ChannelName',
                'name'  => 'Channel Name',
                'show'  => true,
                'width' => 150,
                'color' => 0xffffff,
                'align' => 'left',
                'style' => ''
            ],
            [
                'index' => 8,
                'key'   => 'IconPath',
                'name'  => 'Icon',
                'show'  => false,
                'width' => 100,
                'color' => 0xffffff,
                'align' => 'left',
                'style' => ''
            ],
            [
                'index' => 9,
                'key'   => 'ThumbnailPath',
                'name'  => 'Thumbnail',
                'show'  => false,
                'width' => 100,
                'color' => 0xffffff,
                'align' => 'left',
                'style' => ''
            ],
            [
                'index' => 10,
                'key'   => 'RecordingTime',
                'name'  => 'Recording Time ',
                'show'  => true,
                'width' => 200,
                'color' => 0xffffff,
                'align' => 'left',
                'style' => ''
            ],
            [
                'index' => 11,
                'key'   => 'Duration',
                'name'  => 'Duration',
                'show'  => true,
                'width' => 100,
                'color' => 0xffffff,
                'align' => 'left',
                'style' => ''
            ],
            [
                'index' => 12,
                'key'   => 'Priority',
                'name'  => 'Priority',
                'show'  => false,
                'width' => 100,
                'color' => 0xffffff,
                'align' => 'left',
                'style' => ''
            ],
            [
                'index' => 13,
                'key'   => 'Lifetime',
                'name'  => 'Lifetime',
                'show'  => false,
                'width' => 100,
                'color' => 0xffffff,
                'align' => 'left',
                'style' => ''
            ],
            [
                'index' => 14,
                'key'   => 'GenreType',
                'name'  => 'Genre Type',
                'show'  => false,
                'width' => 100,
                'color' => 0xffffff,
                'align' => 'left',
                'style' => ''
            ],
            [
                'index' => 15,
                'key'   => 'GenreSubType',
                'name'  => 'Genre SubType',
                'show'  => false,
                'width' => 100,
                'color' => 0xffffff,
                'align' => 'left',
                'style' => ''
            ]
        ];
        $NewRowsConfig = [
            [
                'row'     => 'odd',
                'name'    => $this->Translate('odd'),
                'bgcolor' => 0x000000,
                'color'   => 0xffffff,
                'style'   => ''
            ],
            [
                'row'     => 'even',
                'name'    => $this->Translate('even'),
                'bgcolor' => 0x080808,
                'color'   => 0xffffff,
                'style'   => ''
            ]
        ];
        return ['Table' => $NewTableConfig, 'Columns' => $NewColumnsConfig, 'Rows' => $NewRowsConfig];
    }
}
