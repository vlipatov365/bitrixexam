<?php
use Bitrix\Main\SystemException;
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Engine\Contract\Controllerable;
use Bitrix\Main\Engine\Response\AjaxJson;
use Bitrix\Main\Error;
use Bitrix\Main\Errorable;
use Bitrix\Main\ErrorCollection;
use Bitrix\Main\ErrorableImplementation;

use Exam31\Ticket\ORM\SomeElementTable;

class ExamElementsDetailComponent extends CBitrixComponent implements Controllerable, Errorable
{
	use ErrorableImplementation;
	private ?int $elementId = null;
	
	public function __construct($component = null)
	{
		parent::__construct($component);
		$this->errorCollection = new ErrorCollection();
	}

	function onPrepareComponentParams($arParams)
	{
		if (!Loader::includeModule('exam31.ticket'))
		{
			$this->errorCollection->setError(
				new Error(Loc::getMessage('EXAM31_TICKET_MODULE_NOT_INSTALLED'))
			);
			return $arParams;
		}

		if (isset($arParams['ELEMENT_ID']))
		{
			$this->elementId = (int) $arParams['ELEMENT_ID'] ?: null;
		}

		return $arParams;
	}

	private function displayErrors(): void
	{
		foreach ($this->getErrors() as $error)
		{
			ShowError($error->getMessage());
		}
	}

	function executeComponent(): void
	{
		if ($this->hasErrors())
		{
			$this->displayErrors();
			return;
		}

		$this->arResult['ELEMENT'] = $this->getEntityData();
		$this->arResult['form'] = $this->PrepareForm($this->arResult['ELEMENT']);
		$this->arResult['LIST_PAGE_URL'] = $this->arParams['LIST_PAGE_URL'];
		$this->arResult['DETAIL_PAGE_URL'] = $this->arParams['DETAIL_PAGE_URL'];

		$this->includeComponentTemplate();

		global $APPLICATION;
		$APPLICATION->SetTitle(Loc::getMessage('EXAM31_ELEMENT_DETAIL_TITLE', ['#ID#' => $this->arResult['ELEMENT']['ID']]));
	}

	protected function PrepareForm($element): array
	{
		return [
			'MODULE_ID' => null,
			'CONFIG_ID' => null,
			'GUID' => 'GUIDSomeElement',
			'ENTITY_TYPE_NAME' => 'SomeElement',

			'ENTITY_CONFIG_EDITABLE' => true,
			'READ_ONLY' => false,
			'ENABLE_CONFIG_CONTROL' => false,

			'ENTITY_ID' => $this->elementId,

			'ENTITY_FIELDS' => $this->getEntityFields(),
			'ENTITY_CONFIG' => $this->getEntityConfig(),
			'ENTITY_DATA' => $element,
			'ENTITY_CONTROLLERS' => [],

			'COMPONENT_AJAX_DATA' => [
				'COMPONENT_NAME' => $this->getName(),
				'SIGNED_PARAMETERS' => $this->getSignedParameters()
			],
		];
	}
	protected function getEntityConfig(): array
	{
		return [
			[
				'type' => 'column',
				'name' => 'default_column',
				'elements' => [
					[
						'name' => 'main',
						'title' => $this->elementId ? Loc::getMessage('EXAM31_ELEMENT_DETAIL_TITLE', ['#ID#' => $this->elementId]) : Loc::getMessage('EXAM31_ELEMENT_DETAIL_TITLE_NEW'),

						'type' => 'section',
						'elements' => [
							['name' => 'ID'],
							['name' => 'DATE_MODIFY'],							
							['name' => 'ACTIVE'],
							['name' => 'TITLE'],
							['name' => 'TEXT'],
						]
					],
				]
			]
		];
	}

	protected function getEntityFields(): array
	{
		$fieldsLabel = SomeElementTable::getFieldsDisplayLabel();

		return [
			[
				'name' => 'ID',
				'title' => $fieldsLabel['ID'] ?? 'ID',
				'editable' => false,
				'type' => 'text',
			],
			[
				'name' => 'DATE_MODIFY',
				'title' => $fieldsLabel['DATE_MODIFY'] ?? 'DATE_MODIFY',
				'editable' => false,
				'type' => 'datetime',
			],			
			[
				'name' => 'ACTIVE',
				'title' => $fieldsLabel['ACTIVE'] ?? 'ACTIVE',
				'editable' => true,
				'type' => 'boolean',
			],
			[
				'name' => 'TITLE',
				'title' => $fieldsLabel['TITLE'] ?? 'TITLE',
				'editable' => true,
				'type' => 'text',
			],
			[
				'name' => 'TEXT',
				'title' => $fieldsLabel['TEXT'] ?? 'TEXT',
				'editable' => true,
				'type' => 'textarea',
			],
		];
	}

	protected function getEntityData(): array
	{
		if (!$this->elementId)
		{
			return [];
		}

        $elementObject = SomeElementTable::getById($this->elementId)->fetchObject();

		return [
			'ID' => $elementObject->getId(),
			'DATE_MODIFY' => $elementObject->getDateModify()->toString(),
			'TITLE' => $elementObject->getTitle(),
			'TEXT' => $elementObject->getText(),
			'ACTIVE' => $elementObject->getActive() ? 'Y' : 'N',
		];
    }

	public function saveAction(array $data): AjaxJson
	{
		try
		{
            $tableFields = array_map(fn($field) => $field->getName(), SomeElementTable::getEntity()->getScalarFields());
            $data = array_intersect_key($data, $tableFields);
            if (isset($data['ACTIVE'])) {
                $data['ACTIVE'] = $data['ACTIVE'] === 'Y';
            }
            $result = SomeElementTable::update($this->elementId, $data);

            if (!$result->isSuccess()) {
                throw new SystemException(implode('; ', $result->getErrorMessages()));
            }



			return AjaxJson::createSuccess($this->getEntityData());
		}
		catch (SystemException $exception)
		{
			$this->errorCollection->setError(new Error($exception->getMessage()));
			return AjaxJson::createError($this->errorCollection);
		}
	}

	public function configureActions(): array
	{
		return [];
	}
	protected function listKeysSignedParameters(): array
	{
		return ['ELEMENT_ID', 'DETAIL_PAGE_URL'];
	}

	protected function getDetailPageUrl(int $id): string
	{
		return str_replace('#ELEMENT_ID#', $id, $this->arParams['DETAIL_PAGE_URL']);
	}
}