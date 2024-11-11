<?php

use Bitrix\Main\Loader;
use Bitrix\Main\Context;
use Bitrix\Main\Application;
use Bitrix\Main\Web\Json;
use Artwell\Apartments\ApartmentTable;
use Artwell\Apartments\BuildingTable;

if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

class ApartmentsListComponent extends CBitrixComponent
{
    public function executeComponent()
    {
        if (!Loader::includeModule('artwell.apartments')) {
            ShowError('Модуль artwell.apartments не установлен');
            return;
        }

        $request = Context::getCurrent()->getRequest();

        if ($request->isAjaxRequest() && $request->getPost('ajax_action') == 'filter') {
            $this->processAjax();
            return;
        }

        $this->arResult['BUILDINGS'] = $this->getBuildings();
        $this->arResult['APARTMENTS'] = $this->getApartments();

        $this->includeComponentTemplate();
    }

    private function getBuildings()
    {
        $buildings = BuildingTable::getList([
            'select' => ['ID', 'NAME'],
            'filter' => ['ACTIVE' => 'Y'],
            'order' => ['NAME' => 'ASC'],
        ])->fetchAll();

        return $buildings;
    }

    private function getApartments($filterParams = [])
    {
        $filter = [
            'ACTIVE' => 'Y',
            'STATUS' => 'for_sale',
        ];

        if (!empty($filterParams['BUILDING_ID'])) {
            $filter['BUILDING_ID'] = $filterParams['BUILDING_ID'];
        }

        if (!empty($filterParams['HAS_DISCOUNT'])) {
            $filter['!DISCOUNT_PRICE'] = false;
        }

        $apartments = ApartmentTable::getList([
            'select' => [
                '*',
                'BUILDING_NAME' => 'BUILDING.NAME',
                'BUILDING_PHOTO_GALLERY' => 'BUILDING.PHOTO_GALLERY',
            ],
            'filter' => $filter,
            'runtime' => [
                new \Bitrix\Main\Entity\ReferenceField(
                    'BUILDING',
                    BuildingTable::class,
                    ['=this.BUILDING_ID' => 'ref.ID'],
                    ['join_type' => 'left']
                ),
            ],
            'order' => ['ID' => 'DESC'],
        ])->fetchAll();

        return $apartments;
    }

    private function processAjax()
    {
        global $APPLICATION;
        $request = Context::getCurrent()->getRequest();

        $filterParams = [
            'BUILDING_ID' => $request->getPost('building_id'),
            'HAS_DISCOUNT' => $request->getPost('has_discount'),
        ];

        $apartments = $this->getApartments($filterParams);

        ob_start();
        foreach ($apartments as $apartment) {
            ?>
            <div class="apartment">
                <h3>Квартира №<?= htmlspecialcharsbx($apartment['NUMBER']) ?>
                    <span class="<?= $apartment['STATUS'] ?>">
                        <?= ($apartment['STATUS'] == 'for_sale') ? 'в продаже' : 'не в продаже' ?>
                    </span>
                </h3>
                <p>Дом: <?= htmlspecialcharsbx($apartment['BUILDING_NAME']) ?></p>
                <?php if ($apartment['DISCOUNT_PRICE']): ?>
                  <p>Цена: <?= number_format($apartment['DISCOUNT_PRICE'], 2, '.', ' ') ?> <span class="old_price"><?= number_format($apartment['PRICE'], 2, '.', ' ') ?></span> руб.</p>
                <?php else: ?>
                  <p>Цена: <?= number_format($apartment['PRICE'], 2, '.', ' ') ?> руб.</p>
                <?endif;?>

                <?php if (!empty($apartment['PHOTO_GALLERY']) or !empty($apartment['BUILDING_PHOTO_GALLERY'])): ?>
                  <?php
                  $apartment_photos = unserialize($apartment['PHOTO_GALLERY']);
                  $building_photos = unserialize($apartment['BUILDING_PHOTO_GALLERY']);
                  ?>
                    <div class="apartment-gallery owl-carousel">
                        <?php foreach ($apartment_photos as $photoId): ?>
                            <?php $photoPath = CFile::GetPath($photoId); ?>
                            <div class="gallery-item">
                                <a href="<?= $photoPath ?>" data-fancybox="gallery-<?=$apartment['ID']?>"><img src="<?= $photoPath ?>" alt="Фото квартиры" style="width: 100%; height: auto;"></a>
                            </div>
                        <?php endforeach; ?>
                        <?php foreach ($building_photos as $photoId): ?>
                            <?php $photoPath = CFile::GetPath($photoId); ?>
                            <div class="gallery-item">
                                <a href="<?= $photoPath ?>" data-fancybox="gallery-<?=$apartment['ID']?>"><img src="<?= $photoPath ?>" alt="Фото квартиры" style="width: 100%; height: auto;"></a>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
            <?php
        }
        $html = ob_get_clean();

        $response = ['html' => $html];

        $APPLICATION->RestartBuffer();
        header('Content-Type: application/json');
        echo Json::encode($response);
        die();
    }
}
