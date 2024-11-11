<?php
namespace Artwell\Apartments;

use Bitrix\Main\Entity;

class ApartmentTable extends Entity\DataManager
{
    public static function getTableName()
    {
        return 'apartments';
    }

    public static function getMap()
    {
        return [
            (new Entity\IntegerField('ID'))
                ->configurePrimary(true)
                ->configureAutocomplete(true),
            (new Entity\BooleanField('ACTIVE', ['values' => array('N', 'Y')]))
            ->configureDefaultValue('Y'),
            new Entity\StringField('NUMBER'),
            new Entity\IntegerField('BUILDING_ID'),
            /*new Entity\ReferenceField(
                'BUILDING',
                BuildingTable::class,
                ['=this.BUILDING_ID' => 'ref.ID']
            ),*/
            (new Entity\EnumField('STATUS'))
                ->configureValues(['for_sale', 'not_for_sale']),
            new Entity\FloatField('PRICE'),
            new Entity\FloatField('DISCOUNT_PRICE'),
            (new Entity\BooleanField('HAS_DISCOUNT', ['values' => array('N', 'Y')]))
                ->configureDefaultValue('N'),
            (new Entity\TextField('PHOTO_GALLERY'))
                ->configureSerialized(),
        ];
    }
}
