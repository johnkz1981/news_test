<? if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
?>
<?
/**
 * Вывел категории из хайлоадблоков и за тем по внешнему ключу выводил элементы.
 * Кэширования использовал внутри выборки элементов.
 * При добавлении элемента и категории проверяю элемент, чтоб небыла дублирования.
 * В идеале нужно было реализовать этот компонент в классе для большей удобочитаемости
 */
/** @var array $arResult */
?>
    <table border="1">
        <caption>Новости</caption>
        <tr>
            <th>Категории</th>
            <th>Дата</th>
            <th>Наименование</th>
            <th>URL</th>
            <th>Онисание</th>
        </tr>
<?
foreach ($arResult['category'] as $category) {
    foreach ($arResult['elements'] as $element){
        if($category['UF_XML_ID'] === $element['CATEGORY_VALUE']){
           ?>
            <tr>
                <td><?=$category['UF_TITLE']?></td>
                <td><?=$element['DATE_CREATE']?></td>
                <td><?=$element['NAME']?></td>
                <td><?=$element['URL_VALUE']?></td>
                <td><?=$element['DETAIL_TEXT']?></td>
            </tr>
            <?
        }
    }
}
?>
    </table>


