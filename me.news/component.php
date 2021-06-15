<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
require_once($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/classes/general/xml.php');

use \Bitrix\Iblock\Elements\ElementNewsApiTable as ElementNews;
use Bitrix\Highloadblock as HL;
use Bitrix\Iblock\ORM\PropertyValue;

\Bitrix\Main\Loader::includeModule('highloadblock');
$hlblock = HL\HighloadBlockTable::getById(10)->fetch();
$entity = HL\HighloadBlockTable::compileEntity($hlblock);
$entityClass = $entity->getDataClass();

function generateUUID()
{
    $uuid = '';

    $uuid .= sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
        // 32 bits for "time_low"
        mt_rand(0, 0xffff), mt_rand(0, 0xffff),

        // 16 bits for "time_mid"
        mt_rand(0, 0xffff),

        // 16 bits for "time_hi_and_version",
        // four most significant bits holds version number 4
        mt_rand(0, 0x0fff) | 0x4000,

        // 16 bits, 8 bits for "clk_seq_hi_res",
        // 8 bits for "clk_seq_low",
        // two most significant bits holds zero and one for variant DCE1.1
        mt_rand(0, 0x3fff) | 0x8000,

        // 48 bits for "node"
        mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
    );
    return ($uuid);
}

$urls = [];
$elements = ElementNews::getList([
    'select' => ['URL_' => 'URL'],
    'filter' => ['=ACTIVE' => 'Y'],
])->fetchAll();
foreach ($elements as $element) {
    $urls[] = $element['URL_VALUE'];
}

$content = file_get_contents('https://lenta.ru/rss/');
$xml = new CDataXML();
$xml->LoadString($content);

if ($node = $xml->SelectNodes('/rss/channel')) {
    foreach($node->children() as $key => $child){
        if($child->name() === 'item') {

            $news = [];
            foreach ($child->children() as $item) {
                $news[$item->name()] = $item->textContent();
            }
            addElement($news, $entityClass, $urls);
        }
    }
}

function addElement($news, $entityClass, $urls){
    $category = $entityClass::getList(
        [
            'select' => ['UF_TITLE', 'UF_XML_ID'],
            'filter' => ['UF_TITLE' => $news['category']]
        ]
    )->fetch();

    if(array_search($news['link'], $urls) === false) {

        if(!$category){

            $guid = generateUUID();
            $data = [
                "UF_COMMENT" => '',
                "UF_TITLE" => $news['category'],
                "UF_XML_ID" => $guid,
            ];
            $entityClass::add($data);
        }else{
            $guid = $category['UF_XML_ID'];
        }
        $elem = ElementNews::createObject();
        $elem->set('NAME', $news['title']);
        $elem->set('DETAIL_TEXT', $news['description']);
        $elem->set('IBLOCK_ID', 87);
        $elem->set('ACTIVE', 'Y');
        $date = \Bitrix\Main\Type\DateTime::createFromTimestamp(strtotime($news['pubDate']));
        $elem->set('DATE_CREATE', $date);
        $elem->save();

        $id = $elem->getId();
        $elem->set('URL', new PropertyValue($news['link']));
        $elem->set('CATEGORY_LINK', new PropertyValue($guid));
        $elem->save();
    }
}

$elements = ElementNews::getList([
    'select' => ['ID', 'NAME', 'DETAIL_TEXT', 'DATE_CREATE', 'URL_' => 'URL', 'CATEGORY_' => 'CATEGORY_LINK'],
    'filter' => ['=ACTIVE' => 'Y'],
    "cache" => ["ttl" => 3600],
])->fetchAll();

$arResult['elements'] = $elements;
$arResult['category'] = $entityClass::getList([
    'select' => ['UF_TITLE', 'UF_XML_ID'],
    'order' => ['UF_TITLE'],
    "cache" => ["ttl" => 3600],
])->fetchAll();

$this->IncludeComponentTemplate();