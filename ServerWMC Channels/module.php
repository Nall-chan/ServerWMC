<?php

declare(strict_types=1);

require_once __DIR__ . '/../libs/ServerWMVModuleBase.php';

/**
 * @property array $Sort
 */
class ServerWMCChannels extends ServerWMVModuleBase
{
    public static $FunctionFilter = 'GetChannels';

    public function Create()
    {
        //Never delete this line!
        parent::Create();
        $Style = $this->GenerateHTMLStyleProperty();
        $this->RegisterPropertyString('Table', json_encode($Style['Table']));
        $this->RegisterPropertyString('Columns', json_encode($Style['Columns']));
        $this->RegisterPropertyString('Rows', json_encode($Style['Rows']));
        $this->Sort = true;
        $this->RegisterMessage(0, IPS_KERNELSTARTED);
    }

    public function ApplyChanges()
    {
        //Never delete this line!
        parent::ApplyChanges();
        if (IPS_GetKernelRunlevel() != KR_READY) {
            return;
        }
        $this->RegisterHook('/hook/ServerWMCChannels/' . $this->InstanceID);
        $this->RefreshHTMLData($this->Channels);
    }

    public function GetChannelsData()
    {
        return $this->Channels;
    }

    protected function GetChannels(array $Channels)
    {
        $Channels = parent::GetChannels($Channels);
        $this->RefreshHTMLData($Channels);
    }

    protected function RefreshHTMLData(array $Timers)
    {
        $vid = @$this->GetIDForIdent('Channels');
        if ($vid === false) {
            $vid = $this->RegisterVariableString('Channels', $this->Translate('Channels'), '~HTMLBox', 0);
            IPS_SetIcon($vid, 'Database');
        }
        $Sort = $this->Sort;
        if (is_array($Sort)) {
            $Index = $Sort['Index'];
            /* if ($Sort['Index'] == 'StartTime') {
              $Index = 'iStartTime';
              }
              if ($Sort['Index'] == 'RecordingTime') {
              $Index = 'iRecordingTime';
              } */
            $sort = array_column($Timers, $Index);
            array_multisort($sort, $Sort['desc'], $Timers);
        }
        $HTML = $this->GetTable($Timers, 'ServerWMCChannels/', '', '', -1, $Sort);
        $this->SetValue('Channels', $HTML);

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
        $MySecret = $this->{'WebHookSecretSeriesTimer'};
        $CalcSecret = base64_encode(sha1($MySecret . '0' . $_GET['value'], true));
        //$this->SendDebug('Calc', $CalcSecret, 0);
        //$this->SendDebug('Got', rawurldecode($_GET['Secret']), 0);
        // IPS Bug
        /* if ($CalcSecret != rawurldecode($_GET['Secret'])) {
          echo $this->Translate('Access denied');
          return;
          } */

        switch ($_GET['action']) {
            case 'Sort':
                $this->SetSort($_GET['value']);
                $this->RefreshHTMLData($this->Channels);
                echo 'OK';
                return;
        }
        echo 'Invalid parameters.';
    }

    /**
     * Liefert die Werkeinstellungen für die Eigenschaften Html, Table und Rows.
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
                'key'   => 'TimerID',
                'name'  => 'Timer ID ',
                'show'  => false,
                'width' => 100,
                'color' => 0xffffff,
                'align' => 'center',
                'style' => ''
            ],
            [
                'index' => 1,
                'key'   => 'ChannelID',
                'name'  => $this->Translate('Channel'),
                'show'  => false,
                'width' => 150,
                'color' => 0xffffff,
                'align' => 'left',
                'style' => ''
            ],
            [
                'index' => 2,
                'key'   => 'StartTime',
                'name'  => 'Time Start',
                'show'  => true,
                'width' => 150,
                'color' => 0xffffff,
                'align' => 'left',
                'style' => ''
            ],
            [
                'index' => 3,
                'key'   => 'EndTime',
                'name'  => 'Time End',
                'show'  => true,
                'width' => 150,
                'color' => 0xffffff,
                'align' => 'left',
                'style' => ''
            ],
            [
                'index' => 4,
                'key'   => 'CurrentState',
                'name'  => 'Current State',
                'show'  => false,
                'width' => 75,
                'color' => 0xffffff,
                'align' => 'left',
                'style' => ''
            ],
            [
                'index' => 5,
                'key'   => 'TimerName',
                'name'  => $this->Translate('Timer Name'),
                'show'  => true,
                'width' => 400,
                'color' => 0xffffff,
                'align' => 'left',
                'style' => ''
            ],
            [
                'index' => 6,
                'key'   => 'Directory',
                'name'  => $this->Translate('Directory'),
                'show'  => false,
                'width' => 300,
                'color' => 0xffffff,
                'align' => 'left',
                'style' => ''
            ],
            [
                'index' => 7,
                'key'   => 'Description',
                'name'  => 'Description',
                'show'  => false,
                'width' => 500,
                'color' => 0xffffff,
                'align' => 'left',
                'style' => ''
            ],
            [
                'index' => 8,
                'key'   => 'WMCPriority',
                'name'  => 'WMC Priority',
                'show'  => false,
                'width' => 100,
                'color' => 0xffffff,
                'align' => 'left',
                'style' => ''
            ],
            [
                'index' => 9,
                'key'   => 'IsRecording',
                'name'  => 'Is Recording',
                'show'  => true,
                'width' => 75,
                'color' => 0xffffff,
                'align' => 'left',
                'style' => ''
            ],
            [
                'index' => 10,
                'key'   => 'EPGID',
                'name'  => $this->Translate('EPG ID'),
                'show'  => false,
                'width' => 100,
                'color' => 0xffffff,
                'align' => 'left',
                'style' => ''
            ],
            [
                'index' => 11,
                'key'   => 'PreRecording',
                'name'  => 'Pre Recording',
                'show'  => false,
                'width' => 75,
                'color' => 0xffffff,
                'align' => 'left',
                'style' => ''
            ],
            [
                'index' => 12,
                'key'   => 'PostRecording',
                'name'  => 'Post Recording',
                'show'  => false,
                'width' => 75,
                'color' => 0xffffff,
                'align' => 'left',
                'style' => ''
            ],
            [
                'index' => 13,
                'key'   => 'GenreType',
                'name'  => 'Genre Type',
                'show'  => false,
                'width' => 75,
                'color' => 0xffffff,
                'align' => 'left',
                'style' => ''
            ],
            [
                'index' => 14,
                'key'   => 'GenreSubType',
                'name'  => 'Genre SubType',
                'show'  => false,
                'width' => 75,
                'color' => 0xffffff,
                'align' => 'left',
                'style' => ''
            ],
            [
                'index' => 15,
                'key'   => 'ParentSeriesIDlong',
                'name'  => 'Parent SeriesID long',
                'show'  => false,
                'width' => 100,
                'color' => 0xffffff,
                'align' => 'left',
                'style' => ''
            ],
            [
                'index' => 16,
                'key'   => 'isPreMarginRequired',
                'name'  => 'Pre Margin Required',
                'show'  => false,
                'width' => 75,
                'color' => 0xffffff,
                'align' => 'left',
                'style' => ''
            ],
            [
                'index' => 17,
                'key'   => 'isPostMarginRequired',
                'name'  => 'Post Margin Required ',
                'show'  => false,
                'width' => 75,
                'color' => 0xffffff,
                'align' => 'left',
                'style' => ''
            ],
            [
                'index' => 18,
                'key'   => 'RunType',
                'name'  => 'RunType',
                'show'  => false,
                'width' => 75,
                'color' => 0xffffff,
                'align' => 'left',
                'style' => ''
            ],
            [
                'index' => 19,
                'key'   => 'AnyChannel',
                'name'  => 'Any Channel',
                'show'  => false,
                'width' => 75,
                'color' => 0xffffff,
                'align' => 'left',
                'style' => ''
            ],
            [
                'index' => 20,
                'key'   => 'AnyTime',
                'name'  => 'Any Time',
                'show'  => false,
                'width' => 75,
                'color' => 0xffffff,
                'align' => 'left',
                'style' => ''
            ],
            [
                'index' => 21,
                'key'   => 'DaysOfWeek',
                'name'  => 'Days Of Week',
                'show'  => false,
                'width' => 100,
                'color' => 0xffffff,
                'align' => 'left',
                'style' => ''
            ],
            [
                'index' => 22,
                'key'   => 'ParentSeriesID',
                'name'  => 'Parent SeriesID',
                'show'  => false,
                'width' => 100,
                'color' => 0xffffff,
                'align' => 'left',
                'style' => ''
            ],
            [
                'index' => 23,
                'key'   => 'Lifetime',
                'name'  => 'Lifetime',
                'show'  => false,
                'width' => 75,
                'color' => 0xffffff,
                'align' => 'left',
                'style' => ''
            ],
            [
                'index' => 24,
                'key'   => 'MaximumRecordings',
                'name'  => 'Maximum Recordings',
                'show'  => false,
                'width' => 75,
                'color' => 0xffffff,
                'align' => 'left',
                'style' => ''
            ],
            [
                'index' => 25,
                'key'   => 'Priority',
                'name'  => 'Priority',
                'show'  => false,
                'width' => 75,
                'color' => 0xffffff,
                'align' => 'left',
                'style' => ''
            ],
            [
                'index' => 26,
                'key'   => 'KeywordSearch',
                'name'  => 'Keyword Search',
                'show'  => false,
                'width' => 150,
                'color' => 0xffffff,
                'align' => 'left',
                'style' => ''
            ],
            [
                'index' => 27,
                'key'   => 'KeywordIsFulltext',
                'name'  => 'Keyword Is Fulltext',
                'show'  => false,
                'width' => 75,
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
