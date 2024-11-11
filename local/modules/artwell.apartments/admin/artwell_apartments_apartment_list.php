<?php
require_once $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_admin_before.php';

use Bitrix\Main\Loader;
use Artwell\Apartments\ApartmentTable;
use Artwell\Apartments\BuildingTable;

Loader::includeModule('artwell.apartments');

$APPLICATION->SetTitle('Список квартир');

// Проверка прав доступа (при необходимости)
if (!$USER->IsAdmin()) {
    $APPLICATION->AuthForm('Доступ запрещен');
}

// Инициализация списка
$sTableID = 'artwell_apartments_apartment_list';
$oSort = new CAdminSorting($sTableID, 'ID', 'desc');
$lAdmin = new CAdminList($sTableID, $oSort);

// Фильтр
$filterFields = [
    'find_id',
    'find_number',
    'find_building_id',
    'find_status',
    'find_active',
];

$lAdmin->InitFilter($filterFields);

$filter = [];

if ($find_id) {
    $filter['ID'] = $find_id;
}

if ($find_number) {
    $filter['%NUMBER'] = $find_number;
}

if ($find_building_id) {
    $filter['BUILDING_ID'] = $find_building_id;
}

if ($find_status) {
    $filter['STATUS'] = $find_status;
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
                ApartmentTable::delete($ID);
                break;

            case 'activate':
                ApartmentTable::update($ID, ['ACTIVE' => 'Y']);
                break;

            case 'deactivate':
                ApartmentTable::update($ID, ['ACTIVE' => 'N']);
                break;
        }
    }
}

// Получение данных для отображения
$rsData = ApartmentTable::getList([
    'select' => ['*', 'BUILDING_NAME' => 'BUILDING.NAME'],
    'filter' => $filter,
    'order' => [$by => $order],
    'runtime' => [
        new \Bitrix\Main\Entity\ReferenceField(
            'BUILDING',
            BuildingTable::class,
            ['=this.BUILDING_ID' => 'ref.ID'],
            ['join_type' => 'left']
        ),
    ],
]);

$rsData = new CAdminResult($rsData, $sTableID);

$lAdmin->NavText($rsData->GetNavPrint('Квартиры'));

// Построение списка
$lAdmin->AddHeaders([
    ['id' => 'ID', 'content' => 'ID', 'sort' => 'ID', 'default' => true],
    ['id' => 'ACTIVE', 'content' => 'Активность', 'sort' => 'ACTIVE', 'default' => true],
    ['id' => 'NUMBER', 'content' => 'Номер квартиры', 'sort' => 'NUMBER', 'default' => true],
    ['id' => 'BUILDING_NAME', 'content' => 'Дом', 'sort' => 'BUILDING.NAME', 'default' => true],
    ['id' => 'STATUS', 'content' => 'Статус', 'sort' => 'STATUS', 'default' => true],
    ['id' => 'PRICE', 'content' => 'Стоимость', 'sort' => 'PRICE', 'default' => true],
    ['id' => 'DISCOUNT_PRICE', 'content' => 'Стоимость со скидкой', 'sort' => 'DISCOUNT_PRICE', 'default' => true],
]);

while ($arRes = $rsData->Fetch()) {
    $row =& $lAdmin->AddRow($arRes['ID'], $arRes);

    $row->AddViewField('ID', $arRes['ID']);
    $row->AddCheckField('ACTIVE');
    $row->AddInputField('NUMBER', ['size' => 20]);
    $row->AddViewField('BUILDING_NAME', htmlspecialcharsbx($arRes['BUILDING_NAME']));
    $row->AddSelectField('STATUS', ['for_sale' => 'В продаже', 'not_for_sale' => 'Не в продаже']);
    $row->AddInputField('PRICE');
    $row->AddInputField('DISCOUNT_PRICE');

    // Действия над элементом
    $arActions = [];

    $arActions[] = [
        'ICON' => 'edit',
        'TEXT' => 'Редактировать',
        'ACTION' => $lAdmin->ActionRedirect('artwell_apartments_apartment_edit.php?ID=' . $arRes['ID']),
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
        'TEXT' => 'Добавить квартиру',
        'LINK' => 'artwell_apartments_apartment_edit.php?lang=' . LANGUAGE_ID,
        'TITLE' => 'Добавить новую квартиру',
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
        'Номер квартиры',
        'Дом',
        'Статус',
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
        <td>Номер квартиры:</td>
        <td><input type="text" name="find_number" value="<?= htmlspecialcharsbx($find_number) ?>" size="47"></td>
    </tr>
    <tr>
        <td>Дом:</td>
        <td>
            <select name="find_building_id">
                <option value="">(любой)</option>
                <?php
                $buildings = BuildingTable::getList(['select' => ['ID', 'NAME']]);
                while ($building = $buildings->fetch()) {
                    $selected = ($find_building_id == $building['ID']) ? 'selected' : '';
                    echo '<option value="' . $building['ID'] . '" ' . $selected . '>' . htmlspecialcharsbx($building['NAME']) . '</option>';
                }
                ?>
            </select>
        </td>
    </tr>
    <tr>
        <td>Статус:</td>
        <td>
            <select name="find_status">
                <option value="">(любой)</option>
                <option value="for_sale" <?= ($find_status == 'for_sale') ? 'selected' : '' ?>>В продаже</option>
                <option value="not_for_sale" <?= ($find_status == 'not_for_sale') ? 'selected' : '' ?>>Не в продаже</option>
            </select>
        </td>
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
