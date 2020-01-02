<?php

declare(strict_types=1);

require_once __DIR__ . '/../libs/ServerWMVModuleBase.php';

/**
 * @property array $SeriesTimers
 * @property array $Sort
 */
class ServerWMCSeriesTimers extends ServerWMVModuleBase
{
    public static $FunctionFilter = 'GetSeriesTimers';
    public static $SeriesTimerKeys = [
        'TimerIDLong',
        'Title',
        'ChannelID',
        'EPGID',
        'Description',
        'StartTime',
        'EndTime',
        'PreRecording',
        'PostRecording',
        'isPreMarginRequired',
        'isPostMarginRequired',
        'WMCPriority',
        'NewEpisodesOnly',
        'AnyChannel',
        'AnyTime',
        'DaysOfWeek',
        'CurrentState',
        'TimerName',
        'GenreType',
        'GenreSubType',
        'RunType',
        'TimerID',
        'KeywordSearch',
        'KeywordIsFulltext',
        'Lifetime',
        'MaximumRecordings',
        'Priority'
    ];

    public function Create()
    {
        //Never delete this line!
        parent::Create();
        $Style = $this->GenerateHTMLStyleProperty();
        $this->RegisterPropertyString('Table', json_encode($Style['Table']));
        $this->RegisterPropertyString('Columns', json_encode($Style['Columns']));
        $this->RegisterPropertyString('Rows', json_encode($Style['Rows']));
        $this->SeriesTimers = [];
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
        $this->RegisterHook('/hook/ServerWMCSeriesTimer/' . $this->InstanceID);
        $this->RefreshHTMLData($this->SeriesTimers);
    }

    public function GetSeriesTimersData()
    {
        return $this->SeriesTimers;
    }

    protected function GetSeriesTimers(array $SeriesTimers)
    {
        foreach ($SeriesTimers as $key => $SeriesTimer) {
            $SeriesTimers[$key] = array_combine(self::$SeriesTimerKeys, array_chunk(explode('|', $SeriesTimer), count(self::$SeriesTimerKeys))[0]);
            $SeriesTimers[$key]['PreRecording'] = $this->ConvertSeconds((int) $SeriesTimers[$key]['PreRecording']);
            $SeriesTimers[$key]['PostRecording'] = $this->ConvertSeconds((int) $SeriesTimers[$key]['PostRecording']);
            $SeriesTimers[$key]['NewEpisodesOnly'] = ($SeriesTimers[$key]['NewEpisodesOnly'] == 'True');
            $SeriesTimers[$key]['AnyChannel'] = ($SeriesTimers[$key]['AnyChannel'] == 'True');
            $SeriesTimers[$key]['AnyTime'] = ($SeriesTimers[$key]['AnyTime'] == 'True');
            $SeriesTimers[$key]['isPreMarginRequired'] = ($SeriesTimers[$key]['isPreMarginRequired'] == 'True');
            $SeriesTimers[$key]['isPostMarginRequired'] = ($SeriesTimers[$key]['isPostMarginRequired'] == 'True');
            $SeriesTimers[$key]['KeywordIsFulltext'] = ($SeriesTimers[$key]['KeywordIsFulltext'] == 'True');

            $SeriesTimers[$key]['TimerIDLong'] = (int) $SeriesTimers[$key]['TimerIDLong'];
            $SeriesTimers[$key]['TimerID'] = (int) $SeriesTimers[$key]['TimerID'];
            $SeriesTimers[$key]['ChannelID'] = (int) $SeriesTimers[$key]['ChannelID'];
            $SeriesTimers[$key]['DaysOfWeek'] = (int) $SeriesTimers[$key]['DaysOfWeek'];
            $SeriesTimers[$key]['CurrentState'] = (int) $SeriesTimers[$key]['CurrentState'];
            $SeriesTimers[$key]['GenreType'] = (int) $SeriesTimers[$key]['GenreType'];
            $SeriesTimers[$key]['GenreSubType'] = (int) $SeriesTimers[$key]['GenreSubType'];
            $SeriesTimers[$key]['Priority'] = (int) $SeriesTimers[$key]['Priority'];
            $SeriesTimers[$key]['WMCPriority'] = (int) $SeriesTimers[$key]['WMCPriority'];
            $SeriesTimers[$key]['EPGID'] = (int) $SeriesTimers[$key]['EPGID'];
            $SeriesTimers[$key]['RunType'] = (int) $SeriesTimers[$key]['RunType'];
            $SeriesTimers[$key]['Lifetime'] = (int) $SeriesTimers[$key]['Lifetime'];
            $SeriesTimers[$key]['MaximumRecordings'] = (int) $SeriesTimers[$key]['MaximumRecordings'];
            $SeriesTimers[$key]['GenreType'] = (int) $SeriesTimers[$key]['GenreType'];

            $SeriesTimers[$key]['iStartTime'] = (int) $SeriesTimers[$key]['StartTime'];
            if ($SeriesTimers[$key]['iStartTime'] == -2208988800) {
                $SeriesTimers[$key]['StartTime'] = '';
            } else {
                $SeriesTimers[$key]['StartTime'] = strftime('%c', (int) $SeriesTimers[$key]['StartTime']);
            }
            $SeriesTimers[$key]['iEndTime'] = (int) $SeriesTimers[$key]['EndTime'];
            if ($SeriesTimers[$key]['iEndTime'] == -2208988800) {
                $SeriesTimers[$key]['EndTime'] = '';
            } else {
                $SeriesTimers[$key]['EndTime'] = strftime('%c', (int) $SeriesTimers[$key]['EndTime']);
            }
        }
        $this->SeriesTimers = $SeriesTimers;
        $this->SendDebug('GetSeriesTimers', $SeriesTimers, 0);
        $this->RefreshHTMLData($SeriesTimers);
    }

    protected function RefreshHTMLData(array $SeriesTimers)
    {
        $vid = @$this->GetIDForIdent('SeriesTimers');
        if ($vid === false) {
            $vid = $this->RegisterVariableString('SeriesTimers', $this->Translate('Series Timers'), '~HTMLBox', 0);
            IPS_SetIcon($vid, 'Database');
        }
        $Sort = $this->Sort;
        if (is_array($Sort)) {
            $Index = $Sort['Index'];
            if ($Sort['Index'] == 'StartTime') {
                $Index = 'iStartTime';
            }
            if ($Sort['Index'] == 'EndTime') {
                $Index = 'iEndTime';
            }
            $sort = array_column($SeriesTimers, $Index);
            array_multisort($sort, $Sort['desc'], $SeriesTimers);
        }
        $HTML = $this->GetTable($SeriesTimers, 'ServerWMCSeriesTimer/', '', '', -1, $Sort);
        $this->SetValue('SeriesTimers', $HTML);

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
                $this->RefreshHTMLData($this->SeriesTimers);
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
                'key'   => 'TimerIDLong',
                'name'  => 'Timer ID Long',
                'show'  => false,
                'width' => 100,
                'color' => 0xffffff,
                'align' => 'center',
                'style' => ''
            ],
            [
                'index' => 1,
                'key'   => 'TimerName',
                'name'  => $this->Translate('Timer Name'),
                'show'  => true,
                'width' => 300,
                'color' => 0xffffff,
                'align' => 'left',
                'style' => ''
            ],
            [
                'index' => 2,
                'key'   => 'ChannelID',
                'name'  => $this->Translate('Channel'),
                'show'  => false,
                'width' => 150,
                'color' => 0xffffff,
                'align' => 'left',
                'style' => ''
            ],
            [
                'index' => 3,
                'key'   => 'EPGID',
                'name'  => $this->Translate('EPG ID'),
                'show'  => false,
                'width' => 100,
                'color' => 0xffffff,
                'align' => 'left',
                'style' => ''
            ],
            [
                'index' => 4,
                'key'   => 'Description',
                'name'  => 'Description',
                'show'  => false,
                'width' => 300,
                'color' => 0xffffff,
                'align' => 'left',
                'style' => ''
            ],
            [
                'index' => 5,
                'key'   => 'StartTime',
                'name'  => 'Time Start',
                'show'  => false,
                'width' => 150,
                'color' => 0xffffff,
                'align' => 'left',
                'style' => ''
            ],
            [
                'index' => 6,
                'key'   => 'EndTime',
                'name'  => 'Time End',
                'show'  => false,
                'width' => 150,
                'color' => 0xffffff,
                'align' => 'left',
                'style' => ''
            ],
            [
                'index' => 7,
                'key'   => 'PreRecording',
                'name'  => 'Pre Recording',
                'show'  => false,
                'width' => 75,
                'color' => 0xffffff,
                'align' => 'left',
                'style' => ''
            ],
            [
                'index' => 8,
                'key'   => 'PostRecording',
                'name'  => 'Post Recording',
                'show'  => false,
                'width' => 75,
                'color' => 0xffffff,
                'align' => 'left',
                'style' => ''
            ],
            [
                'index' => 9,
                'key'   => 'isPreMarginRequired',
                'name'  => 'Pre Margin Required',
                'show'  => false,
                'width' => 75,
                'color' => 0xffffff,
                'align' => 'left',
                'style' => ''
            ],
            [
                'index' => 10,
                'key'   => 'isPostMarginRequired',
                'name'  => 'Post Margin Required ',
                'show'  => false,
                'width' => 75,
                'color' => 0xffffff,
                'align' => 'left',
                'style' => ''
            ],
            [
                'index' => 11,
                'key'   => 'WMCPriority',
                'name'  => 'WMC Priority',
                'show'  => false,
                'width' => 100,
                'color' => 0xffffff,
                'align' => 'left',
                'style' => ''
            ],
            [
                'index' => 12,
                'key'   => 'NewEpisodesOnly',
                'name'  => 'New Episodes Only',
                'show'  => false,
                'width' => 75,
                'color' => 0xffffff,
                'align' => 'left',
                'style' => ''
            ],
            [
                'index' => 13,
                'key'   => 'AnyChannel',
                'name'  => 'Any Channel',
                'show'  => false,
                'width' => 75,
                'color' => 0xffffff,
                'align' => 'left',
                'style' => ''
            ],
            [
                'index' => 14,
                'key'   => 'AnyTime',
                'name'  => 'Any Time',
                'show'  => false,
                'width' => 75,
                'color' => 0xffffff,
                'align' => 'left',
                'style' => ''
            ],
            [
                'index' => 15,
                'key'   => 'DaysOfWeek',
                'name'  => 'Days Of Week',
                'show'  => false,
                'width' => 100,
                'color' => 0xffffff,
                'align' => 'left',
                'style' => ''
            ],
            [
                'index' => 16,
                'key'   => 'CurrentState',
                'name'  => 'Current State',
                'show'  => false,
                'width' => 75,
                'color' => 0xffffff,
                'align' => 'left',
                'style' => ''
            ],
            [
                'index' => 17,
                'key'   => 'Title',
                'name'  => 'Title',
                'show'  => false,
                'width' => 300,
                'color' => 0xffffff,
                'align' => 'left',
                'style' => ''
            ],
            [
                'index' => 18,
                'key'   => 'GenreType',
                'name'  => 'Genre Type',
                'show'  => false,
                'width' => 75,
                'color' => 0xffffff,
                'align' => 'left',
                'style' => ''
            ],
            [
                'index' => 19,
                'key'   => 'GenreSubType',
                'name'  => 'Genre SubType',
                'show'  => false,
                'width' => 75,
                'color' => 0xffffff,
                'align' => 'left',
                'style' => ''
            ],
            [
                'index' => 20,
                'key'   => 'RunType',
                'name'  => 'Run Type',
                'show'  => false,
                'width' => 75,
                'color' => 0xffffff,
                'align' => 'left',
                'style' => ''
            ],
            [
                'index' => 21,
                'key'   => 'TimerID',
                'name'  => 'Timer ID',
                'show'  => false,
                'width' => 75,
                'color' => 0xffffff,
                'align' => 'left',
                'style' => ''
            ],
            [
                'index' => 22,
                'key'   => 'KeywordSearch',
                'name'  => 'Keyword Search',
                'show'  => false,
                'width' => 150,
                'color' => 0xffffff,
                'align' => 'left',
                'style' => ''
            ],
            [
                'index' => 23,
                'key'   => 'KeywordIsFulltext',
                'name'  => 'Keyword Is Fulltext',
                'show'  => false,
                'width' => 75,
                'color' => 0xffffff,
                'align' => 'left',
                'style' => ''
            ],
            [
                'index' => 24,
                'key'   => 'Lifetime',
                'name'  => 'Lifetime',
                'show'  => false,
                'width' => 75,
                'color' => 0xffffff,
                'align' => 'left',
                'style' => ''
            ],
            [
                'index' => 25,
                'key'   => 'MaximumRecordings',
                'name'  => 'Maximum Recordings',
                'show'  => false,
                'width' => 75,
                'color' => 0xffffff,
                'align' => 'left',
                'style' => ''
            ],
            [
                'index' => 26,
                'key'   => 'Priority',
                'name'  => 'Priority',
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
