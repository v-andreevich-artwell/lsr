<?php
defined('B_PROLOG_INCLUDED') and (B_PROLOG_INCLUDED === true) or die();
use Bitrix\Main\Localization\Loc;
Loc::loadMessages(__FILE__);
$aMenu = [
        'parent_menu' => 'global_menu_content',
        'sort' => 400,
        'text' => "Дома и апартаменты",
        'title' => "",
        "icon" => "form_menu_icon",
        "page_icon" => "form_page_icon",
        "items_id" => "menu_apartments", 
        "items" => []
];

$aMenu['items'][] = [
    'text' => 'Реестр апартаментов',
    'url' => 'artwell_apartments_apartment_list.php',
    'more_url' => ['artwell_apartments_apartment_edit.php']
];

$aMenu['items'][] = [
    'text' => 'Реестр домов',
    'url' => 'artwell_apartments_building_list.php',
    'more_url' => ['artwell_apartments_building_edit.php']
];
return $aMenu;
