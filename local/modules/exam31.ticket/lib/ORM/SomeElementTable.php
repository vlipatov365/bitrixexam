<?php
namespace Exam31\Ticket\ORM;

use Bitrix\Main\Entity;
use Bitrix\Main\Entity\Validator\Length;
use Bitrix\Main\ORM\Fields\Relations\OneToMany;
use Bitrix\Main\ORM\Fields\Relations\Reference;
use Bitrix\Main\ORM\Query\Join;
use Bitrix\Main\Type\DateTime;
use Bitrix\Main\Localization\Loc;

class SomeElementTable extends Entity\DataManager
{
	static function getTableName(): string
	{
		return 'exam31_ticket_someelement';
	}
	static function getMap(): array
	{
		return array(
			(new Entity\IntegerField('ID'))
				->configurePrimary()
				->configureAutocomplete(),
			(new Entity\BooleanField('ACTIVE'))
				->configureRequired(),
			(new Entity\DatetimeField('DATE_MODIFY'))
				->configureRequired()
				->configureDefaultValue(new DateTime()),
			(new Entity\StringField(
                'TITLE',
                [
                    'validation' => function() {
                        return [
                            new Length(1, 250)
                        ];
                    }
                ]
            ))
				->configureRequired(),
			new Entity\TextField('TEXT'),
            (new OneToMany(
                'INFO',
                SomeElementInfoTable::class,
                'ELEMENT'
            ))->configureJoinType(Join::TYPE_LEFT)
		);
	}

	static function getFieldsDisplayLabel(): array
	{
		$fields = SomeElementTable::getMap();
		$res = [];
		foreach ($fields as $field)
		{
			$title = $field->getTitle();
			$res[$title] = Loc::getMessage("EXAM31_SOMEELEMENT_{$title}_FIELD_LABEL") ?? $title;
		}
		return $res;
	}
}