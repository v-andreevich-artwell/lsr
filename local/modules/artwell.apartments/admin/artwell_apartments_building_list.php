<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_admin_before.php';

use Bitrix\Main\Loader;
use Artwell\Apartments\BuildingTable;

Loader::includeModule('artwell.apartments');

$APPLICATION->SetTitle('Список домов');

// Проверка прав доступа (при необходимости)
if (!$USER->IsAdmin()) {
    $APPLICATION->AuthForm('Доступ запрещен');
}

// Инициализация списка
$sTableID = 'artwell_apartments_building_list';
$oSort = new CAdminSorting($sTableID, 'ID', 'desc');
$lAdmin = new CAdminList($sTableID, $oSort);

// Фильтр
$filterFields = [
    'find_id',
    'find_name',
    'find_active',
];

$lAdmin->InitFilter($filterFields);

$filter = [];

if ($find_id) {
    $filter['ID'] = $find_id;
}

if ($find_name) {
    $filter['%NAME'] = $find_name;
}

if ($find_active) {
    $filter['ACTIVE'] = $find_active;
}

// Обработка действий (удаление, активация и т.д.)
if (($arID = $lAdmin->GroupAction()) && $USER->IsAdmin()) {
    foreach ($arID as $ID) {
        if (strlen($ID) <= 0) {
            continue;
        }

        $ID = intval($ID);

        switch ($lAdmin->GetAction()) {
            case 'delete':
                BuildingTable::delete($ID);
                break;

            case 'activate':
                BuildingTable::update($ID, ['ACTIVE' => 'Y']);
                break;

            case 'deactivate':
                BuildingTable::update($ID, ['ACTIVE' => 'N']);
                break;
        }
    }
}

// Получение данных для отображения
$rsData = BuildingTable::getList([
    'select' => ['*'],
    'filter' => $filter,
    'order' => [$by => $order],
]);

$rsData = new CAdminResult($rsData, $sTableID);

$lAdmin->NavText($rsData->GetNavPrint('Дома'));

// Построение списка
$lAdmin->AddHeaders([
    ['id' => 'ID', 'content' => 'ID', 'sort' => 'ID', 'default' => true],
    ['id' => 'ACTIVE', 'content' => 'Активность', 'sort' => 'ACTIVE', 'default' => true],
    ['id' => 'NAME', 'content' => 'Название', 'sort' => 'NAME', 'default' => true],
]);

while ($arRes = $rsData->Fetch()) {
    $row =& $lAdmin->AddRow($arRes['ID'], $arRes);

    $row->AddViewField('ID', $arRes['ID']);
    $row->AddCheckField('ACTIVE');
    $row->AddInputField('NAME', ['size' => 50]);

    // Действия над элементом
    $arActions = [];

    $arActions[] = [
        'ICON' => 'edit',
        'TEXT' => 'Редактировать',
        'ACTION' => $lAdmin->ActionRedirect('artwell_apartments_building_edit.php?ID=' . $arRes['ID']),
    ];

    $arActions[] = [
        'ICON' => 'delete',
        'TEXT' => 'Удалить',
        'ACTION' => "if(confirm('Вы уверены?')) " . $lAdmin->ActionDoGroup($arRes['ID'], 'delete'),
    ];

    $row->AddActions($arActions);
}

// Групповые действия
$lAdmin->AddGroupActionTable([
    'delete' => 'Удалить',
    'activate' => 'Активировать',
    'deactivate' => 'Деактивировать',
]);

// Кнопка добавления
$aContext = [
    [
        'TEXT' => 'Добавить дом',
        'LINK' => 'artwell_apartments_building_edit.php?lang=' . LANGUAGE_ID,
        'TITLE' => 'Добавить новый дом',
        'ICON' => 'btn_new',
    ],
];

$lAdmin->AddAdminContextMenu($aContext);

// Вывод
$lAdmin->CheckListMode();

require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_admin_after.php';

// Форма фильтра
$oFilter = new CAdminFilter(
    $sTableID . '_filter',
    [
        'ID',
        'Название',
        'Активность',
    ]
);
?>

<form name="find_form" method="GET" action="<?= $APPLICATION->GetCurPage() ?>">
    <?php $oFilter->Begin(); ?>
    <tr>
        <td>ID:</td>
        <td><input type="text" name="find_id" value="<?= htmlspecialcharsbx($find_id) ?>" size="47"></td>
    </tr>
    <tr>
        <td>Название:</td>
        <td><input type="text" name="find_name" value="<?= htmlspecialcharsbx($find_name) ?>" size="47"></td>
    </tr>
    <tr>
        <td>Активность:</td>
        <td>
            <select name="find_active">
                <option value="">(любой)</option>
                <option value="Y" <?= ($find_active == 'Y') ? 'selected' : '' ?>>Активные</option>
                <option value="N" <?= ($find_active == 'N') ? 'selected' : '' ?>>Неактивные</option>
            </select>
        </td>
    </tr>
    <?php
    $oFilter->Buttons(['table_id' => $sTableID, 'url' => $APPLICATION->GetCurPage(), 'form' => 'find_form']);
    $oFilter->End();
    ?>
</form>

<?php
$lAdmin->DisplayList();

require $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/epilog_admin.php';
