<?php
namespace Only\Site\Agents;

class Iblock
{
    public static function clearOldLogs()
    {
        // Здесь напиши свой агент
        if (\Bitrix\Main\Loader::includeModule('iblock')) {

            $res = \CIBlock::GetList([], ['CODE' => 'LOG']);
            if ($logIblock = $res->Fetch()) {
                $logIblockId = $logIblock['ID'];
                

                $totalCount = \CIBlockElement::GetList(
                    [],
                    ['IBLOCK_ID' => $logIblockId],
                    [],
                    false,
                    []
                );
                

                if ($totalCount > 10) {

                    $rsLogs = \CIBlockElement::GetList(
                        ['TIMESTAMP_X' => 'DESC'],
                        ['IBLOCK_ID' => $logIblockId],
                        false,
                        false,
                        ['ID', 'IBLOCK_ID', 'TIMESTAMP_X']
                    );
                    
                    $counter = 0;
                    $idsToDelete = [];
                    

                    while ($arLog = $rsLogs->Fetch()) {
                        $counter++;
                        if ($counter > 10) {
                            $idsToDelete[] = $arLog['ID'];
                        }
                    }
                    

                    foreach ($idsToDelete as $logId) {
                        \CIBlockElement::Delete($logId);
                    }
                }
            }
        }
        

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
