<?php

namespace Exam31\Ticket\ORM;

use Bitrix\Main\Entity\Query\Join;
use Bitrix\Main\Entity\StringField;
use Bitrix\Main\Entity\Validator\Length;
use Bitrix\Main\ORM\Data\DataManager;
use Bitrix\Main\ORM\Fields\IntegerField;
use Bitrix\Main\ORM\Fields\Relations\Reference;
use Exam31\Ticket\SomeElementTable;

/**
 * class SomeElementInfo
 *
 * @author  Vyacheslav Lipatov
 */
class SomeElementInfoTable extends DataManager
{
    public static function getTableName()
    {
        return 'exam31_ticket_someelement_info';
    }

    public static function getMap()
    {
        return [
            (new IntegerField('ID'))
                ->configurePrimary()
                ->configureAutocomplete(),
            (new StringField(
                'TITLE',
                [
                    'validation' => function() {
                        return [
                            new Length(1, 250)
                        ];
                    }
                ]
            )),
            (new IntegerField('ELEMENT_ID'))
                ->configureRequired(),
            (new Reference(
                'ELEMENT',
                SomeElementTable::class,
                Join::on('this.ELEMENT_ID', 'ref.ID')
            ))->configureJoinType(Join::TYPE_INNER)
        ];
    }
}
