<?php if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die(); ?>
<?
CJSCore::Init(["jquery"]);
$this->addExternalCss($templateFolder."/owl/owl.carousel.min.css");
$this->addExternalCss($templateFolder."/owl/owl.theme.default.min.css");
$this->addExternalJS($templateFolder."/owl/owl.carousel.min.js");

$this->addExternalCss($templateFolder."/fancybox/fancybox.css");
$this->addExternalJS($templateFolder."/fancybox/fancybox.umd.js");
?>
<form id="filterForm">
    <label for="buildingFilter">Дом:</label>
    <select name="building_id" id="buildingFilter">
        <option value="">Все</option>
        <?php foreach ($arResult['BUILDINGS'] as $building): ?>
            <option value="<?= $building['ID'] ?>"><?= htmlspecialcharsbx($building['NAME']) ?></option>
        <?php endforeach; ?>
    </select>

    <label>
        <input type="checkbox" name="has_discount" id="discountFilter" value="1">
        Только со скидкой
    </label>
</form>

<div id="apartmentList">
    <?php foreach ($arResult['APARTMENTS'] as $apartment): ?>
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
    <?php endforeach; ?>
</div>

<script>
    $(document).ready(function(){
        $(".owl-carousel").owlCarousel({
            items: 3,
            loop: false,
            margin: 10,
            nav: true,
            dots: true
        });
        Fancybox.bind(".gallery-item a");
    });

    document.getElementById('filterForm').addEventListener('change', function () {
        var formData = new FormData(this);
        formData.append('ajax_action', 'filter');

        fetch('<?= $APPLICATION->GetCurPage() ?>', {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.json())
        .then(data => {
            document.getElementById('apartmentList').innerHTML = data.html;

            $(".owl-carousel").owlCarousel({
                items: 3,
                loop: false,
                margin: 10,
                nav: true,
                dots: true
            });
        })
        .catch(error => console.error('Ошибка:', error));
    });
</script>
