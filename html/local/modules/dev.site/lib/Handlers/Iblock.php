<?php

namespace Only\Site\Handlers;

class Iblock
{
    public function addLog()
    {
        // Здесь напиши свой обработчик
        $eventManager = \Bitrix\Main\EventManager::getInstance();
        

        $eventManager->addEventHandler('iblock', 'OnAfterIBlockElementAdd', [$this, 'logIblockChange']);
        $eventManager->addEventHandler('iblock', 'OnAfterIBlockElementUpdate', [$this, 'logIblockChange']);
    }

    /**
     * @param array $arFields
     */
    public function logIblockChange($arFields)
    
    {
        file_put_contents(
    $_SERVER['DOCUMENT_ROOT'] . '/local/logs/event_handler.log',
    "[" . date('Y-m-d H:i:s') . "] Обработчик сработал: " . print_r($arFields, true) . "\n",
    FILE_APPEND
);


        $logIblock = $this->getLogIblock();
        if (!$logIblock) {
            return;
        }


        if ($arFields['IBLOCK_ID'] == $logIblock['ID']) {
            return;
        }


        $iblock = \CIBlock::GetByID($arFields['IBLOCK_ID'])->Fetch();
        if (!$iblock) {
            return;
        }


        $sectionId = $this->getOrCreateLogSection($logIblock['ID'], $iblock);


        $element = \CIBlockElement::GetByID($arFields['ID'])->Fetch();
        if (!$element) {
            return;
        }


        $description = $this->buildElementPath($element, $iblock);

        // Создаем элемент в логе
        $this->createLogElement($logIblock['ID'], $sectionId, $arFields['ID'], $description);
    }

    /**

     * @return array|false
     */
    private function getLogIblock()
    {
        $res = \CIBlock::GetList([], ['CODE' => 'LOG']);
        return $res->Fetch();
    }

    /**

     * @param int $logIblockId
     * @param array $iblock
     * @return int
     */
    private function getOrCreateLogSection($logIblockId, $iblock)
    {
        // Ищем существующий раздел
        $res = \CIBlockSection::GetList(
            [],
            [
                'IBLOCK_ID' => $logIblockId,
                'CODE' => $iblock['CODE']
            ]
        );
        
        if ($section = $res->Fetch()) {
            return $section['ID'];
        }

        // Создаем новый раздел
        $bs = new \CIBlockSection();
        $arFields = [
            'IBLOCK_ID' => $logIblockId,
            'NAME' => $iblock['NAME'],
            'CODE' => $iblock['CODE'],
            'ACTIVE' => 'Y'
        ];

        $sectionId = $bs->Add($arFields);
        if (!$sectionId) {
            return 0;
        }

        return $sectionId;
    }

    /**
     * Строит путь к элементу в формате: Имя инфоблока -> Путь разделов -> Имя элемента
     * @param array $element
     * @param array $iblock
     * @return string
     */
    private function buildElementPath($element, $iblock)
    {
        $path = [$iblock['NAME']];

        // Если элемент находится в разделе, получаем путь разделов
        if ($element['IBLOCK_SECTION_ID']) {
            $sectionPath = $this->getSectionPath($element['IBLOCK_SECTION_ID']);
            $path = array_merge($path, $sectionPath);
        }

        // Добавляем имя элемента
        $path[] = $element['NAME'];

        return implode(' -> ', $path);
    }

    /**

     * @param int $sectionId
     * @return array
     */
    private function getSectionPath($sectionId)
    {
        $path = [];
        

        $nav = \CIBlockSection::GetNavChain(false, $sectionId);
        
        while ($section = $nav->Fetch()) {
            $path[] = $section['NAME'];
        }

        return $path;
    }

    /**

     * @param int $logIblockId
     * @param int $sectionId
     * @param int $elementId
     * @param string $description
     */
    private function createLogElement($logIblockId, $sectionId, $elementId, $description)
    {
        $el = new \CIBlockElement();
        
        $arFields = [
            'IBLOCK_ID' => $logIblockId,
            'IBLOCK_SECTION_ID' => $sectionId,
            'NAME' => $elementId,
            'ACTIVE' => 'Y',
            'ACTIVE_FROM' => date('d.m.Y H:i:s'),
            'PREVIEW_TEXT' => $description
        ];

        $el->Add($arFields);
    }

    function OnBeforeIBlockElementAddHandler(&$arFields)
    {
        $iQuality = 95;
        $iWidth = 1000;
        $iHeight = 1000;
        /*
         * Получаем пользовательские свойства
         */
        $dbIblockProps = \Bitrix\Iblock\PropertyTable::getList(array(
            'select' => array('*'),
            'filter' => array('IBLOCK_ID' => $arFields['IBLOCK_ID'])
        ));
        /*
         * Выбираем только свойства типа ФАЙЛ (F)
         */
        $arUserFields = [];
        while ($arIblockProps = $dbIblockProps->Fetch()) {
            if ($arIblockProps['PROPERTY_TYPE'] == 'F') {
                $arUserFields[] = $arIblockProps['ID'];
            }
        }
        /*
         * Перебираем и масштабируем изображения
         */
        foreach ($arUserFields as $iFieldId) {
            foreach ($arFields['PROPERTY_VALUES'][$iFieldId] as &$file) {
                if (!empty($file['VALUE']['tmp_name'])) {
                    $sTempName = $file['VALUE']['tmp_name'] . '_temp';
                    $res = \CAllFile::ResizeImageFile(
                        $file['VALUE']['tmp_name'],
                        $sTempName,
                        array("width" => $iWidth, "height" => $iHeight),
                        BX_RESIZE_IMAGE_PROPORTIONAL_ALT,
                        false,
                        $iQuality);
                    if ($res) {
                        rename($sTempName, $file['VALUE']['tmp_name']);
                    }
                }
            }
        }

        if ($arFields['CODE'] == 'brochures') {
            $RU_IBLOCK_ID = \Only\Site\Helpers\IBlock::getIblockID('DOCUMENTS', 'CONTENT_RU');
            $EN_IBLOCK_ID = \Only\Site\Helpers\IBlock::getIblockID('DOCUMENTS', 'CONTENT_EN');
            if ($arFields['IBLOCK_ID'] == $RU_IBLOCK_ID || $arFields['IBLOCK_ID'] == $EN_IBLOCK_ID) {
                \CModule::IncludeModule('iblock');
                $arFiles = [];
                foreach ($arFields['PROPERTY_VALUES'] as $id => &$arValues) {
                    $arProp = \CIBlockProperty::GetByID($id, $arFields['IBLOCK_ID'])->Fetch();
                    if ($arProp['PROPERTY_TYPE'] == 'F' && $arProp['CODE'] == 'FILE') {
                        $key_index = 0;
                        while (isset($arValues['n' . $key_index])) {
                            $arFiles[] = $arValues['n' . $key_index++];
                        }
                    } elseif ($arProp['PROPERTY_TYPE'] == 'L' && $arProp['CODE'] == 'OTHER_LANG' && $arValues[0]['VALUE']) {
                        $arValues[0]['VALUE'] = null;
                        if (!empty($arFiles)) {
                            $OTHER_IBLOCK_ID = $RU_IBLOCK_ID == $arFields['IBLOCK_ID'] ? $EN_IBLOCK_ID : $RU_IBLOCK_ID;
                            $arOtherElement = \CIBlockElement::GetList([],
                                [
                                    'IBLOCK_ID' => $OTHER_IBLOCK_ID,
                                    'CODE' => $arFields['CODE']
                                ], false, false, ['ID'])
                                ->Fetch();
                            if ($arOtherElement) {
                                /** @noinspection PhpDynamicAsStaticMethodCallInspection */
                                \CIBlockElement::SetPropertyValues($arOtherElement['ID'], $OTHER_IBLOCK_ID, $arFiles, 'FILE');
                            }
                        }
                    } elseif ($arProp['PROPERTY_TYPE'] == 'E') {
                        $elementIds = [];
                        foreach ($arValues as &$arValue) {
                            if ($arValue['VALUE']) {
                                $elementIds[] = $arValue['VALUE'];
                                $arValue['VALUE'] = null;
                            }
                        }
                        if (!empty($arFiles && !empty($elementIds))) {
                            $rsElement = \CIBlockElement::GetList([],
                                [
                                    'IBLOCK_ID' => \Only\Site\Helpers\IBlock::getIblockID('PRODUCTS', 'CATALOG_' . $RU_IBLOCK_ID == $arFields['IBLOCK_ID'] ? '_RU' : '_EN'),
                                    'ID' => $elementIds
                                ], false, false, ['ID', 'IBLOCK_ID', 'NAME']);
                            while ($arElement = $rsElement->Fetch()) {
                                /** @noinspection PhpDynamicAsStaticMethodCallInspection */
                                \CIBlockElement::SetPropertyValues($arElement['ID'], $arElement['IBLOCK_ID'], $arFiles, 'FILE');
                            }
                        }
                    }
                }
            }
        }
    }
}