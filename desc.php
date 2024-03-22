<?php

namespace App\Modules\Sale\Conditions;

use Bitrix\Main\ArgumentException;
use Bitrix\Main\ObjectPropertyException;
use Bitrix\Main\SystemException;
use CSaleActionCtrlAction;

class AdditionalConditionControlStore extends CSaleActionCtrlAction
{
    /**
     * @return string
     */
    public static function GetClassName(): string
    {
        return __CLASS__;
    }

    /**
     * @return string
     */
    public static function GetControlID(): string
    {
        return 'AdditionalStoreSelectedCondition';
    }

    /**
     * Добавление группы условий и пункта в этой группе
     * @param $arParams
     * @return array
     */
    #[ArrayShape([
        'controlgroup' => "true",
        'group' => "false",
        'label' => "string",
        'showIn' => "array",
        'children' => "array[]"
    ])]
    public static function GetControlShow($arParams): array
    {
        $atoms = static::GetAtomsEx();
        $arResult = [
            'controlgroup' => true,
            'group' => false,
            'label' => 'Ограничение по доставке в ПВЗ',
            'showIn' => static::GetShowIn($arParams['SHOW_IN_GROUPS']),
            'children' => [
                [
                    'controlId' => static::GetControlID(),
                    'group' => false,
                    'label' => 'Выбранный ПВЗ',
                    'showIn' => static::GetShowIn($arParams['SHOW_IN_GROUPS']),
                    'control' => [
                        'Если доставка в ПВЗ',
                        $atoms['PT']
                    ]
                ]
            ]
        ];

        return $arResult;
    }

    /**
     * Формирование данных для визуального представления условия
     * @param bool $strControlID
     * @param bool $boolEx
     * @return array{PT: array{JS: array{id: string, name: string, type: string, values: array, defaultText: string, defaultValue: string, first_option: string}, ATOM: array{ID: string, FIELD_TYPE: string, FIELD_LENGTH: int, MULTIPLE: string, VALIDATE: string}}}
     */
    public static function GetAtomsEx($strControlID = false, $boolEx = false): array
    {
        $boolEx = true === $boolEx; //это было в исходниках и зачем-то нужно, правда...
        $stores = static::getStores();
        $storesData = [];

        foreach ($stores as $store) {
            $storesData[$store['ID']] = "{$store['TITLE']} [{$store['ID']}]";
        }

        $atoms = [
            'PT' => [
                'JS' => [
                    'id' => static::GetControlID(),
                    'name' => 'extra',
                    'type' => 'select',
                    'values' => $storesData,
                    'defaultText' => '...',
                    'defaultValue' => '',
                    'first_option' => '...',
                ],
                'ATOM' => [
                    'ID' => static::GetControlID(),
                    'FIELD_TYPE' => 'string',
                    'FIELD_LENGTH' => 255,
                    'MULTIPLE' => 'N',
                    'VALIDATE' => 'list',
                ]
            ],
        ];

        if (!$boolEx) {
            foreach ($atoms as &$atom) {
                $atom = $atom['JS'];
            }
            if (isset($atom)) {
                unset($atom);
            }
        }

        return $atoms;
    }

    /**
     * @param $arOneCondition
     * @param $arParams
     * @param $arControl
     * @param bool $arSubs
     * @return string
     */
    public static function Generate($arOneCondition, $arParams, $arControl, $arSubs = false): string
    {
        return __CLASS__ . '::applyProductDiscount(' . $arOneCondition[static::GetControlID()] . ')';
    }

    /**
     * Условие применения
     * @param $selectedStore
     * @return bool
     */
    public static function applyProductDiscount($selectedStore): bool
    {
        return $selectedStore == $_SESSION['SELECTED_STOCK_ID'];
    }

    /**
     * Получить список ПВЗ
     * @return array
     * @throws ArgumentException
     * @throws ObjectPropertyException
     * @throws SystemException
     */
    protected static function getStores(): array
    {
        $stores = \Bitrix\Catalog\StoreTable::getList([
            'select' => [
                'ID',
                'TITLE',
            ],
            'filter' => [
                'ACTIVE' => 'Y'
            ],
            'cache' => [
                'ttl' => 3600,
                'cache_joins' => true,
            ]
        ])->fetchAll();

        return $stores;
    }
}
