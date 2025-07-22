<?php

require_once __DIR__ . '/../modules/dev.site/include.php';

use Only\Site\Handlers\Iblock as Handler;
use Bitrix\Main\Loader;


if (Loader::includeModule('iblock')) {


    $handler = new Handler();


    AddEventHandler(
        'iblock',
        'OnAfterIBlockElementAdd',
        [$handler, 'logIblockChange']
    );
    AddEventHandler(
        'iblock',
        'OnAfterIBlockElementUpdate',
        [$handler, 'logIblockChange']
    );
}


if (!\CAgent::GetList([], ['MODULE_ID' => '', 'NAME' => '\\Only\\Site\\Agents\\Iblock::clearOldLogs();'])->Fetch()) {
    \CAgent::AddAgent(
        '\\Only\\Site\\Agents\\Iblock::clearOldLogs();',
        '',
        'N',
        3600,
        '',
        'Y',
        '',
        30
    );
}
