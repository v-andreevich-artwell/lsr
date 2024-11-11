<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_admin_before.php';

use Bitrix\Main\Loader;
use Bitrix\Main\Application;
use Artwell\Apartments\ApartmentTable;
use Artwell\Apartments\BuildingTable;

Loader::includeModule('artwell.apartments');

if (!Loader::includeModule('fileman')) {
    CAdminMessage::ShowMessage('Модуль fileman не установлен');
}

$ID = intval($_REQUEST['ID']);
$APPLICATION->SetTitle($ID > 0 ? 'Редактирование квартиры' : 'Добавление квартиры');

if (!$USER->IsAdmin()) {
    $APPLICATION->AuthForm('Доступ запрещен');
}

$request = Application::getInstance()->getContext()->getRequest();

if ($request->isPost() && check_bitrix_sessid()) {
    $fields = [
        'ACTIVE' => $request->getPost('ACTIVE') ? 'Y' : 'N',
        'NUMBER' => $request->getPost('NUMBER'),
        'BUILDING_ID' => $request->getPost('BUILDING_ID'),
        'STATUS' => $request->getPost('STATUS'),
        'PRICE' => $request->getPost('PRICE'),
        'DISCOUNT_PRICE' => $request->getPost('DISCOUNT_PRICE'),
        'HAS_DISCOUNT' => $request->getPost('DISCOUNT_PRICE') ? 'Y' : 'N',
    ];

    if (empty($fields['BUILDING_ID']) || $fields['BUILDING_ID'] == 0) {
        unset($fields['BUILDING_ID']);
    }

    CModule::IncludeModule("iblock"); //необходим для работы \Bitrix\Main\UI\FileInput, используется на строке 57, особенность битрикса
    $photoGalleryIds = [];
    if ($ID > 0) {
        $apartment = ApartmentTable::getById($ID)->fetch();
        $photoGalleryIds = !empty($apartment['PHOTO_GALLERY']) ? unserialize($apartment['PHOTO_GALLERY']) : [];
    }

    $updatedPhotoGalleryIds = [];
    foreach ($photoGalleryIds as $fid) {
        if (!$request->getPost("PHOTO_GALLERY_{$fid}_del")) {
            $updatedPhotoGalleryIds[] = $fid;
        } else {
            \CFile::Delete($fid);
        }
    }

    $newPhotoGalleryIds = $request->getPost('PHOTO_GALLERY') ?: [];
    foreach ($newPhotoGalleryIds as $file) {
        $arFile = \CIBlock::makeFileArray($file);
        $fid = \CFile::SaveFile($arFile, 'apartments');
        if ($fid > 0) {
            $updatedPhotoGalleryIds[] = $fid;
        }
    }

    $fields['PHOTO_GALLERY'] = serialize($updatedPhotoGalleryIds);

    if ($ID > 0) {
        $result = ApartmentTable::update($ID, $fields);
    } else {
        $result = ApartmentTable::add($fields);
        if ($result->isSuccess()) {
            $ID = $result->getId();
        }
    }

    if ($result->isSuccess()) {
        if ($request->getPost('apply')) {
            LocalRedirect('artwell_apartments_apartment_edit.php?ID=' . $ID . '&mess=ok&lang=' . LANGUAGE_ID);
        } else {
            LocalRedirect('artwell_apartments_apartment_list.php?lang=' . LANGUAGE_ID);
        }
    } else {
        $errors = $result->getErrorMessages();
        foreach ($errors as $error) {
            CAdminMessage::ShowMessage($error);
        }
    }
}

if ($ID > 0) {
    $apartment = ApartmentTable::getById($ID)->fetch();
    if (!$apartment) {
        CAdminMessage::ShowMessage('Квартира не найдена');
        $ID = 0;
    } else {
        if (!empty($apartment['PHOTO_GALLERY'])) {
            $apartment['PHOTO_GALLERY'] = unserialize($apartment['PHOTO_GALLERY']);
        } else {
            $apartment['PHOTO_GALLERY'] = [];
        }
    }
} else {
    $apartment = [
        'ACTIVE' => 'Y',
        'STATUS' => 'for_sale',
        'PHOTO_GALLERY' => [],
    ];
}

require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_admin_after.php';

$aTabs = [
    ['DIV' => 'edit1', 'TAB' => 'Квартира', 'TITLE' => 'Данные квартиры'],
];

$tabControl = new CAdminTabControl('tabControl', $aTabs);
?>

<form method="POST" action="<?= $APPLICATION->GetCurPage() ?>" enctype="multipart/form-data" name="post_form">
    <?php
    echo bitrix_sessid_post();
    if ($ID > 0) {
        echo '<input type="hidden" name="ID" value="' . $ID . '">';
    }
    ?>

    <?php $tabControl->Begin(); ?>
    <?php $tabControl->BeginNextTab(); ?>

    <tr>
        <td width="40%">Активность:</td>
        <td width="60%"><input type="checkbox" name="ACTIVE" value="Y" <?= ($apartment['ACTIVE'] == 'Y') ? 'checked' : '' ?>></td>
    </tr>
    <tr>
        <td>Номер квартиры:</td>
        <td><input type="text" name="NUMBER" value="<?= htmlspecialcharsbx($apartment['NUMBER']) ?>" size="50"></td>
    </tr>
    <tr>
        <td>Дом:</td>
        <td>
            <select name="BUILDING_ID">
                <?php if(!$apartment['BUILDING_ID']){?>
                <option value="0">- не выбрано -</option>
                <?php } ?>
                <?php
                $buildings = BuildingTable::getList(['select' => ['ID', 'NAME']]);
                while ($building = $buildings->fetch()) {
                    $selected = ($apartment['BUILDING_ID'] == $building['ID']) ? 'selected' : '';
                    echo '<option value="' . $building['ID'] . '" ' . $selected . '>' . htmlspecialcharsbx($building['NAME']) . '</option>';
                }
                ?>
            </select> <a href="/bitrix/admin/artwell_apartments_building_edit.php" target="_blank">Добавить новый дом</a>
        </td>
    </tr>
    <tr>
        <td>Статус:</td>
        <td>
            <select name="STATUS">
                <option value="for_sale" <?= ($apartment['STATUS'] == 'for_sale') ? 'selected' : '' ?>>В продаже</option>
                <option value="not_for_sale" <?= ($apartment['STATUS'] == 'not_for_sale') ? 'selected' : '' ?>>Не в продаже</option>
            </select>
        </td>
    </tr>
    <tr>
        <td>Стоимость:</td>
        <td><input type="text" name="PRICE" value="<?= htmlspecialcharsbx($apartment['PRICE']) ?>"></td>
    </tr>
    <tr>
        <td>Стоимость со скидкой:</td>
        <td><input type="text" name="DISCOUNT_PRICE" value="<?= htmlspecialcharsbx($apartment['DISCOUNT_PRICE']) ?>"></td>
    </tr>
    <tr>
        <td>Фотогалерея квартиры:</td>
        <td>
            <?php
            $photoGalleryIds = is_array($apartment['PHOTO_GALLERY']) ? $apartment['PHOTO_GALLERY'] : unserialize($apartment['PHOTO_GALLERY']);
            if (!is_array($photoGalleryIds)) {
                $photoGalleryIds = [];
            } else {
              foreach ($photoGalleryIds as $key => $value) {
                $arFiles['PHOTO_GALLERY_'.$value] = $value;
              }
            }

            echo \Bitrix\Main\UI\FileInput::createInstance([
                "name" => "PHOTO_GALLERY[#IND#]",
                "description" => true,
                "upload" => true,
                "medialib" => true,
                "fileDialog" => true,
                "cloud" => true,
                "delete" => true,
                "maxCount" => 10,
                "allowUpload" => "I",
                "allowUploadExt" => "jpg,jpeg,png,gif",
            ])->show($arFiles);
            ?>
        </td>
    </tr>
    <?php $tabControl->Buttons([
        'back_url' => 'artwell_apartments_apartment_list.php?lang=' . LANGUAGE_ID,
    ]); ?>

    <?php $tabControl->End(); ?>
</form>

<?php
require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/epilog_admin.php';
?>
