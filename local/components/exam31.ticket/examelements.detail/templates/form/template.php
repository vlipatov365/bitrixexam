<?php B_PROLOG_INCLUDED === true || die();

use Bitrix\Main\Localization\Loc;

/**
 * @var array $arParams
 * @var array $arResult
 */
global $APPLICATION;

$APPLICATION->setTitle($arResult['ELEMENT']['TITLE']);
?>

<?php


$APPLICATION->IncludeComponent(
    'bitrix:ui.sidepanel.wrapper',
    '',
    [
        'POPUP_COMPONENT_NAME' => 'bitrix:ui.form',
        'POPUP_COMPONENT_TEMPLATE_NAME' => '.default',
        'POPUP_COMPONENT_PARAMS' => $arResult['form'],
        'PAGE_MODE' => true,
        'PAGE_MODE_OFF_BACK_URL' => $arResult['LIST_PAGE_URL'],
        'RELOAD_GRID_AFTER_SAVE' => true
    ]
);
$APPLICATION->IncludeComponent(
	'bitrix:ui.form',
	'.default',

);
?>


<?php //if (): ?>
<!--    <p class="ui-slider-paragraph"><a-->
<!--            href="--><?php //= $arResult['LIST_PAGE_URL'] ?><!--">--><?php //= Loc::getMessage('EXAM31_ELEMENT_DETAIL_BACK_TO_LIST') ?><!--</a></p>-->
<?php //endif;
