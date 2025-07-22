<?php
namespace Only\Site\Agents;

class Iblock
{
    public static function clearOldLogs()
    {
        // Здесь напиши свой агент
        if (\Bitrix\Main\Loader::includeModule('iblock')) {
            // Получаем ID инфоблока LOG
            $res = \CIBlock::GetList([], ['CODE' => 'LOG']);
            if ($logIblock = $res->Fetch()) {
                $logIblockId = $logIblock['ID'];
                
                // Получаем общее количество элементов в логе
                $totalCount = \CIBlockElement::GetList(
                    [],
                    ['IBLOCK_ID' => $logIblockId],
                    [],
                    false,
                    []
                );
                
                // Если элементов больше 10, удаляем старые
                if ($totalCount > 10) {
                    // Получаем все элементы, отсортированные по дате изменения (новые первыми)
                    $rsLogs = \CIBlockElement::GetList(
                        ['TIMESTAMP_X' => 'DESC'],
                        ['IBLOCK_ID' => $logIblockId],
                        false,
                        false,
                        ['ID', 'IBLOCK_ID', 'TIMESTAMP_X']
                    );
                    
                    $counter = 0;
                    $idsToDelete = [];
                    
                    // Пропускаем первые 10 элементов (самые новые), остальные помечаем на удаление
                    while ($arLog = $rsLogs->Fetch()) {
                        $counter++;
                        if ($counter > 10) {
                            $idsToDelete[] = $arLog['ID'];
                        }
                    }
                    
                    // Удаляем старые логи
                    foreach ($idsToDelete as $logId) {
                        \CIBlockElement::Delete($logId);
                    }
                }
            }
        }
        
        // Возвращаем строку для повторного запуска агента через час (3600 секунд)
        return '\\' . __CLASS__ . '::' . __FUNCTION__ . '();';
    }

    public static function example()
    {
        global $DB;
        if (\Bitrix\Main\Loader::includeModule('iblock')) {
            $iblockId = \Only\Site\Helpers\IBlock::getIblockID('QUARRIES_SEARCH', 'SYSTEM');
            $format = $DB->DateFormatToPHP(\CLang::GetDateFormat('SHORT'));
            $rsLogs = \CIBlockElement::GetList(['TIMESTAMP_X' => 'ASC'], [
                'IBLOCK_ID' => $iblockId,
                '<TIMESTAMP_X' => date($format, strtotime('-1 months')),
            ], false, false, ['ID', 'IBLOCK_ID']);
            while ($arLog = $rsLogs->Fetch()) {
                \CIBlockElement::Delete($arLog['ID']);
            }
        }
        return '\\' . __CLASS__ . '::' . __FUNCTION__ . '();';
    }
}