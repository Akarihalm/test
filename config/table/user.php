<?php

use App\Enums\CabinetEnum;
use App\Enums\PrefectureEnum;
use App\Utils\TableConfigManager;

return [
    'table' => 'users',
    'settings' => [
        'soft_delete' => true,
        'by_user' => false,
        'index' => null,
        'unique' => null,
    ],
    'columns' => [
        'line_id' => [
            'label' => 'LINEユーザID',
            'type' => TableConfigManager::TYPE_VARCHAR,
            'digit' => 128,
            'decimal_places' => null,
            'min' => null,
            'max' => null,
            'unique' => true,
            'index' => false,
            'required' => true,
            'enum' => null,
            'default_value' => null,
            'sample_value' => '1234567890onsen',
            'comment' => '',
            'foreign_table' => null,
            'foreign_column' => null,
        ],
        'name' => [
            'label' => '名前',
            'type' => TableConfigManager::TYPE_VARCHAR,
            'digit' => 128,
            'decimal_places' => null,
            'min' => null,
            'max' => null,
            'unique' => false,
            'index' => false,
            'required' => true,
            'enum' => null,
            'default_value' => null,
            'sample_value' => '温泉太郎',
            'comment' => '',
            'foreign_table' => null,
            'foreign_column' => null,
        ],
        'picture' => [
            'label' => '画像URL',
            'type' => TableConfigManager::TYPE_VARCHAR,
            'digit' => 256,
            'decimal_places' => null,
            'min' => null,
            'max' => null,
            'unique' => false,
            'index' => false,
            'required' => false,
            'enum' => null,
            'default_value' => null,
            'sample_value' => 'https://xxxx.yyy',
            'comment' => '',
            'foreign_table' => null,
            'foreign_column' => null,
        ],
    ],
];
