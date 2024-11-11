<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_admin_before.php';

use Bitrix\Main\Loader;
use Bitrix\Main\Application;
use Artwell\Apartments\BuildingTable;

Loader::includeModule('artwell.apartments');

$ID = intval($_REQUEST['ID']);
$APPLICATION->SetTitle($ID > 0 ? 'Редактирование дома' : 'Добавление дома');

if (!$USER->IsAdmin()) {
    $APPLICATION->AuthForm('Доступ запрещен');
}

$request = Application::getInstance()->getContext()->getRequest();

if ($request->isPost() && check_bitrix_sessid()) {
    $fields = [
        'ACTIVE' => $request->getPost('ACTIVE') ? 'Y' : 'N',
        'NAME' => $request->getPost('NAME'),
    ];

    CModule::IncludeModule("iblock");  //необходим для работы \Bitrix\Main\UI\FileInput, используется на строке 44, особенность битрикса
    $photoGalleryIds = $request->getPost('PHOTO_GALLERY') ?: [];
    $photoGalleryIds = [];
    if ($ID > 0) {
        $building = BuildingTable::getById($ID)->fetch();
        $photoGalleryIds = !empty($building['PHOTO_GALLERY']) ? unserialize($building['PHOTO_GALLERY']) : [];
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
        $fid = \CFile::SaveFile($arFile, 'buildings');
        if ($fid > 0) {
            $updatedPhotoGalleryIds[] = $fid;
        }
    }

    $fields['PHOTO_GALLERY'] = serialize($updatedPhotoGalleryIds);

    if ($ID > 0) {
        $result = BuildingTable::update($ID, $fields);
    } else {
        $result = BuildingTable::add($fields);
        if ($result->isSuccess()) {
            $ID = $result->getId();
        }
    }

    if ($result->isSuccess()) {
        if ($request->getPost('apply')) {
            LocalRedirect('artwell_apartments_building_edit.php?ID=' . $ID . '&mess=ok&lang=' . LANGUAGE_ID);
        } else {
            LocalRedirect('artwell_apartments_building_list.php?lang=' . LANGUAGE_ID);
        }
    } else {
        $errors = $result->getErrorMessages();
        foreach ($errors as $error) {
            CAdminMessage::ShowMessage($error);
        }
    }
}

if ($ID > 0) {
    $building = BuildingTable::getById($ID)->fetch();
    if (!$building) {
        CAdminMessage::ShowMessage('Дом не найден');
        $ID = 0;
    } else {
        if (!empty($building['PHOTO_GALLERY'])) {
            $building['PHOTO_GALLERY'] = unserialize($building['PHOTO_GALLERY']);
        } else {
            $building['PHOTO_GALLERY'] = [];
        }
    }
} else {
    $building = [
        'ACTIVE' => 'Y',
        'PHOTO_GALLERY' => [],
    ];
}

require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_admin_after.php';

$aTabs = [
    ['DIV' => 'edit1', 'TAB' => 'Дом', 'TITLE' => 'Данные дома'],
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
        <td width="60%"><input type="checkbox" name="ACTIVE" value="Y" <?= ($building['ACTIVE'] == 'Y') ? 'checked' : '' ?>></td>
    </tr>
    <tr>
        <td>Название:</td>
        <td><input type="text" name="NAME" value="<?= htmlspecialcharsbx($building['NAME']) ?>" size="50"></td>
    </tr>
    <tr>
        <td>Фотогалерея дома:</td>
        <td>
            <?php
            $photoGalleryIds = is_array($building['PHOTO_GALLERY']) ? $building['PHOTO_GALLERY'] : unserialize($building['PHOTO_GALLERY']);
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
        'back_url' => 'artwell_apartments_building_list.php?lang=' . LANGUAGE_ID,
    ]); ?>

    <?php $tabControl->End(); ?>
</form>

<?php
require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/epilog_admin.php';
