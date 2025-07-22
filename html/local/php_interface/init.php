<?php
// /local/php_interface/init.php

// 1) Подключаем автозагрузку классов из модуля
require_once __DIR__ . '/../modules/dev.site/include.php';

use Only\Site\Handlers\Iblock as Handler;
use Bitrix\Main\Loader;

// 2) Подключаем модуль iblock
if (Loader::includeModule('iblock')) {

    // Создаём объект обработчика (методы нестатические)
    $handler = new Handler();

    // Регистрируем обработчики событий
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

// 3) Регистрируем агент (только один раз)
if (!\CAgent::GetList([], ['MODULE_ID' => '', 'NAME' => '\\Only\\Site\\Agents\\Iblock::clearOldLogs();'])->Fetch()) {
    \CAgent::AddAgent(
        '\\Only\\Site\\Agents\\Iblock::clearOldLogs();',
        '', // модуль
        'N', // не удалять при падении
        3600, // интервал (1 час)
        '',   // дата первого запуска
        'Y',  // активен
        '',   // дата окончания
        30    // сортировка
    );
}
