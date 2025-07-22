<?php

\Bitrix\Main\Loader::registerAutoLoadClasses(null, [
    'Only\Site\Handlers\Iblock' => '/local/modules/dev.site/lib/Handlers/Iblock.php',
    'Only\Site\Agents\Iblock' => '/local/modules/dev.site/lib/Agents/Iblock.php',
]);

/**
 * PSR‑0‑автозагрузка для Only\Site\*
 */
spl_autoload_register(function($className) {
    // проверяем, что это наш namespace
    $className = ltrim($className, '\\');
    $parts = explode('\\', $className);
    if ($parts[0] !== 'Only' || $parts[1] !== 's1') {
        return;
    }
    // строим путь: /local/modules/dev.site/lib/<Agents|Handlers>/...php
    $relative = implode('/', array_slice($parts, 2)) . '.php';
    $file = __DIR__ . '/lib/' . $relative;
    if (file_exists($file)) {
        require_once $file;
    }
});
