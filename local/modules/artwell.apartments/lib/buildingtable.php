<?php
namespace Artwell\Apartments;

use Bitrix\Main\Entity;

class BuildingTable extends Entity\DataManager
{
    public static function getTableName()
    {
        return 'buildings';
    }

    public static function getMap()
    {
        return [
            (new Entity\IntegerField('ID'))
                ->configurePrimary(true)
                ->configureAutocomplete(true),
            (new Entity\BooleanField('ACTIVE', ['values' => array('N', 'Y')]))
                ->configureDefaultValue('Y'),
            new Entity\StringField('NAME'),
            (new Entity\TextField('PHOTO_GALLERY'))
                ->configureSerialized(),
        ];
    }
}
