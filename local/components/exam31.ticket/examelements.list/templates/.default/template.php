<?php B_PROLOG_INCLUDED === true || die();

use \Bitrix\Main\Localization\Loc;
use Bitrix\UI\Buttons\AddButton;
use Bitrix\UI\Buttons\JsCode;
use Bitrix\UI\Toolbar\Facade\Toolbar;

/**
 * @global CMain $APPLICATION
 * @var array $arParams
 * @var array $arResult
 * @var CBitrixComponent $component
 */

global $APPLICATION;

$APPLICATION->setTitle(Loc::getMessage('EXAM_ELEMENTS_LIST_ELEMENTS_NAME'));
?>


<?php
Toolbar::addFilter($arResult['FILTER']);
Toolbar::addButton($arResult['ADD_BUTTON']);


$APPLICATION->IncludeComponent(
	'bitrix:main.ui.grid',
	'',
	$arResult["grid"],
	$component
);