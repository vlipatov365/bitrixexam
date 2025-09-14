<?php
B_PROLOG_INCLUDED === true || die();

use Bitrix\Main\Grid\Options;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\Loader;
use Bitrix\Main\ORM\Query\Query;
use Bitrix\Main\ORM\Query\QueryHelper;
use Bitrix\Main\Type\DateTime;

use Bitrix\Main\Error;
use Bitrix\Main\Errorable;
use Bitrix\Main\ErrorCollection;
use Bitrix\Main\ErrorableImplementation;

use Bitrix\UI\Buttons\AddButton;
use Bitrix\UI\Buttons\JsCode;
use Exam31\Ticket\ORM\SomeElementTable;

class ExamElementsListComponent extends CBitrixComponent implements Errorable
{
	use ErrorableImplementation;
	protected const DEFAULT_PAGE_SIZE = 20;
	protected const GRID_ID = 'EXAM31_GRID_ELEMENT';
	protected const FILTER_ID = 'EXAM31_FILTER_ELEMENT';

	public function __construct($component = null)
	{
		parent::__construct($component);
		$this->errorCollection = new ErrorCollection();
	}

	public function onPrepareComponentParams($arParams): array
	{
		if (!Loader::includeModule('exam31.ticket'))
		{
			$this->errorCollection->setError(
				new Error(Loc::getMessage('EXAM31_TICKET_MODULE_NOT_INSTALLED'))
			);
			return $arParams;
		}

		$arParams['ELEMENT_COUNT'] = (int) $arParams['ELEMENT_COUNT'];
		if ($arParams['ELEMENT_COUNT'] <= 0)
		{
			$arParams['ELEMENT_COUNT'] = static::DEFAULT_PAGE_SIZE;
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

	public function executeComponent(): void
	{
		if ($this->hasErrors())
		{
			$this->displayErrors();
			return;
		}
        [$this->arResult['SORT'], $this->arResult['NAV']] = $this->prepareSortAndNav();
		$this->arResult['ITEMS'] = $this->getSomeElementList();
		$this->arResult['grid'] = $this->prepareGrid($this->arResult['ITEMS']);
        $this->arResult['FILTER'] = $this->prepareFilter();
        $this->arResult['ADD_BUTTON'] = $this->getAddButton();

		$this->includeComponentTemplate();

		global $APPLICATION;
		$APPLICATION->SetTitle(Loc::getMessage('EXAM31_ELEMENTS_LIST_PAGE_TITLE'));
	}

	protected function getSomeElementList(): array
	{
        $query = SomeElementTable::query()
            ->setSelect(['ID']);
        $this->enrichQueryFiler($query);

        $this->arResult['NAV']->setRecordCount(
            $query->exec()->getSelectedRowsCount()
        );

        $query = SomeElementTable::query()
            ->setSelect(['*', 'INFO']);
        $this->enrichQueryFiler($query);

        $query->setLimit($this->arResult['NAV']->getLimit())
            ->setOffset($this->arResult['NAV']->getOffset());
        $elementsCollection = QueryHelper::decompose($query, true, true);
        if (!$elementsCollection) {
            return [];
        }

		$preparedItems = [];
		foreach ($elementsCollection as $item)
		{
            $preparedItem = [
                'ID' => $item->getId(),
                'DATE_MODIFY' => $item->getDateModify() instanceof DateTime
                    ? $item->getDateModify()->toString()
                    : null,
                'DETAIL_URL' => $this->getDetailPageUrl($item->getId()),
                'INFO_URL' => $this->getInfoPageUrl($item->getId()),
                'ACTIVE' => $item->getActive(),
                'TITLE' => $item->getTitle(),
                'TEXT' => $item->getText(),
                'INFO_COUNT' => $item->getInfo()->count(),
            ];
            
			$preparedItems[] = $preparedItem;
		}
		return $preparedItems;
	}

	protected function prepareGrid($items): array
	{
		return [
			'GRID_ID' => static::GRID_ID,
			'COLUMNS' => $this->getGridColums(),
			'ROWS' => $this->getGridRows($items),
			'TOTAL_ROWS_COUNT' => count($items),
            'NAV_OBJECT' => $this->arResult['NAV'],
			'SHOW_ROW_CHECKBOXES' => false,
            'SHOW_CHECK_ALL_CHECKBOXES' => true,
            'SHOW_ROW_ACTIONS_MENU'     => true,
            'SHOW_GRID_SETTINGS_MENU'   => true,
            'SHOW_NAVIGATION_PANEL'     => true,
            'SHOW_PAGINATION'           => true,
            'SHOW_SELECTED_COUNTER'     => true,
            'SHOW_TOTAL_COUNTER'        => true,
            'SHOW_PAGESIZE'             => true,
            'SHOW_ACTION_PANEL'         => true,
            'PAGE_SIZES' => [
                ['NAME' => "5", 'VALUE' => '5'],
                ['NAME' => '10', 'VALUE' => '10'],
                ['NAME' => '20', 'VALUE' => '20'],
                ['NAME' => '50', 'VALUE' => '50'],
                ['NAME' => '100', 'VALUE' => '100'],
            ],
			'AJAX_MODE' => 'Y',
			'AJAX_OPTION_JUMP' => 'Y',
			'AJAX_OPTION_HISTORY' => 'Y',
            'ALLOW_COLUMNS_SORT'        => true,
            'ALLOW_COLUMNS_RESIZE'      => true,
            'ALLOW_HORIZONTAL_SCROLL'   => true,
            'ALLOW_SORT'                => true,
            'ALLOW_PIN_HEADER'          => true,
        ];
	}

	protected function getGridColums(): array
	{
		$fieldsLabel = SomeElementTable::getFieldsDisplayLabel();
		return [
			['id' => 'ACTIVE', 'default' => true, 'name' => $fieldsLabel['ACTIVE'] ?? 'ACTIVE'],
			['id' => 'ID', 'default' => true, 'name' => $fieldsLabel['ID'] ?? 'ID'],
			['id' => 'DATE_MODIFY', 'default' => true, 'name' => $fieldsLabel['DATE_MODIFY'] ?? 'DATE_MODIFY'],
			['id' => 'TITLE', 'default' => true, 'name' => $fieldsLabel['TITLE'] ?? 'TITLE'],
			['id' => 'TEXT', 'default' => true, 'name' => $fieldsLabel['TEXT'] ?? 'TEXT'],
			['id' => 'DETAIL', 'default' => true, 'name' => Loc::getMessage('EXAM31_ELEMENTS_LIST_GRIG_COLUMN_DETAIL_NAME')],
			['id' => 'INFO', 'default' => true, 'name' => Loc::getMessage('EXAM31_ELEMENTS_LIST_GRIG_COLUMN_DETAIL_INFO')],
		];
	}
	protected function getGridRows(array $items): array
	{
		if (empty($items))
		{
			return [];
		}

		$rows = [];
		foreach ($items as $key => $item)
		{
			$rows[$key] = [
				'id' => $item["ID"],
				'columns' => [
					'ID' => $item["ID"],
					'DATE_MODIFY' => $item["DATE_MODIFY"],
					'TITLE' => $item["TITLE"],
					'TEXT' => $item["TEXT"],
					'ACTIVE' => $item["ACTIVE"] ? 'Y' : 'N',
					'DETAIL' => $this->getDetailHTMLLink($item["DETAIL_URL"]),
					'INFO' => $this->getInfoHTMLLink($item["INFO_COUNT"], $item['INFO_URL']),
				],
                'actions' => [
                    [
                        'ICON' => 'view',
                        'TEXT' => Loc::getMessage('EXAM31_ELEMENTS_LIST_GRIG_COLUMN_DETAIL_NAME'),
                        'ONCLICK' => $this->getDetailSliderLink($item["DETAIL_URL"]),
                    ],
                    [
                        'ICON' => 'view',
                        'TEXT' => Loc::getMessage('EXAM31_ELEMENTS_LIST_GRIG_COLUMN_DETAIL_INFO'),
                        'ONCLICK' => $this->getInfoHTMLLink($item["INFO_COUNT"], $item['INFO_URL']),
                    ],
                ],
			];
		}
		return $rows;
	}

	protected function getDetailPageUrl(int $id): string
	{
		return str_replace('#ELEMENT_ID#', $id, $this->arParams['DETAIL_PAGE_URL']);
	}
	protected function getInfoPageUrl(int $id): string
	{
		return str_replace('#ID#', $id, $this->arParams['INFO_PAGE_URL']);
	}
	protected function getDetailHTMLLink(string $detail_url): string
	{
		return "<a href='' onclick='{$this->getDetailSliderLink($detail_url)}; return false;'>"
            . Loc::getMessage('EXAM31_ELEMENTS_LIST_GRIG_COLUMN_DETAIL_NAME')
            . "</a>";
	}
	protected function getInfoHTMLLink(int $infoCount, string $infoUrl): string
	{
		return "<a href='' onclick='{$this->getInfoSliderLink($infoUrl)};return false;'>"
            . Loc::getMessage('EXAM31_ELEMENTS_LIST_GRIG_COLUMN_DETAIL_INFO_COUNT', ['#COUNT#' => $infoCount])
            . "</a>";
	}

    private function prepareFilter(): array
    {
        return [
            'FILTER_ID' => self::FILTER_ID,
            'GRID_ID' => self::GRID_ID,
            'FILTER' => [
                [
                    'id' => 'TITLE',
                    'name' => Loc::getMessage('EXAM31_ELEMENTS_LIST_FILTER_FIELD_NAME'),
                    'type' => 'string',
                    'default' => true,
                ],
            ],
            'ENABLE_LABEL' => true,
        ];
    }

    private function enrichQueryFiler(Query &$query): void
    {
        $filterOptions = new Bitrix\Main\UI\Filter\Options(self::FILTER_ID);
        if (isset($filterOptions->getFilter()['TITLE'])) {
            $query->addFilter('%TITLE', $filterOptions->getFilter()['TITLE']);
        }
    }

    private function prepareSortAndNav(): array
    {
        $gridOptions = new Options(self::GRID_ID);
        $sort = $gridOptions->getSorting(['sort' => ['ID' => 'ASC']]);
        $navParams = $gridOptions->GetNavParams();
        $nav = new Bitrix\Main\UI\PageNavigation(self::GRID_ID);
        $nav->setPageSize(
                $navParams['nPageSize'] ?? 20
            )
            ->initFromUri();

        return [$sort, $nav];
    }

    private function getDetailSliderLink($detailUrl): string
    {
        return "BX.SidePanel.Instance.open(".'"'.$detailUrl.'"'.", {
		    width: 600,
		    allowChangeHistory: false,
		    events:{
		        onClose: function(event) {
		            BX.Main.gridManager.getInstanceById(".'"'.self::GRID_ID.'"'.").reload();
		        }
		    }
		});";
    }

    private function getAddButton(): AddButton
    {
        return new AddButton(
            [
                'TEXT' => 'Добавить',
                'click' => new JsCode($this->getDetailSliderLink(
                    $this->getDetailPageUrl('0')
                ))
            ]
        );
    }

    private function getInfoSliderLink($infoUrl): string
    {
        return "BX.SidePanel.Instance.open(".'"'.$infoUrl.'"'.", {
		    width: 600,
		    allowChangeHistory: false,
		    events:{
		        onClose: function(event) {
		            BX.Main.gridManager.getInstanceById(".'"'.self::GRID_ID.'"'.").reload();
		        }
		    }
		});";
    }
}