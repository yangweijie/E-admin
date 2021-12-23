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
                'max' => 10,
                'text' => ['高', '低'],
                'realtime' => true,
                'calculable' => true,
                'show' => true,
                'inRange' => [
                    'color' => ['#f1f1f1', '#25aed4']
                ],
                'orient' => 'horizontal',
                'bottom' => '1%',
                'left' => '0',
                'itemWidth' => '12px',
                'itemHeight' => '200px'
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
            'zoom'=>1.1,
            'showLegendSymbol'=>false,
            'selectedMode' => 'single',
            'itemStyle' => [
                'normal' => [
                    'areaColor' => '#f1f1f1',
                    'borderColor' => '#fff',
                ],
                'emphasis' => [
                    'areaColor' => '#5CD2B7'
                ]
            ],
            'label' => [
                'show' => true
            ],
            'data' => $data,
        ], $options);
    }
}
