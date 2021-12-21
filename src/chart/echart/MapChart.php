<?php

namespace Eadmin\chart\echart;

use Eadmin\chart\Color;
use Eadmin\chart\EchartAbstract;

class MapChart extends EchartAbstract
{
    public function __construct($height, $width)
    {
        parent::__construct($height, $width);
        $this->options = [
            'title' => [
                'text' => '',
            ],
            'tooltip' => [
                'trigger' => 'item',
                'formatter' => '{b}: {c}',
            ],
            'legend' => [
                'data' => [],
            ],

            'visualMap' => [
                'min' => 0,
                'max' => 2500,
                'text' => ['高', '低'],
                'realtime' => false,
                'calculable' => true,
                'show' => true,
                'inRange' => [
                    'color' => ['#e0ffff', '#006edd']
                ]
            ],

            'series' => []
        ];
    }

    public function series(string $name, array $data, array $options = [])
    {
        $this->legend[] = $name;
        $this->series[] = array_merge([
            'name' => $name,
            'type' => 'map',
            'map' => 'china',
            'selectedMode' => 'single',
            'roam' => false,
            'label' => [
                'show' => true
            ],
            'data' => $data,
        ], $options);
    }
}
