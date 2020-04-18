<?php

ini_set('max_execution_time', 300); // работает долго

require "path_to_current_directory/vendor/autoload.php";
use KubAT\PhpSimple\HtmlDomParser;


$mioletto_cats = array(
    'bedclothes' => array(
        'category_name' => 'Постельное бельё',
        'category_link' => 'https://mioletto.su/postelnoe-bele/',
        'page_num' => 12
    ),  // 12 
    'children' => array(
        'category_name' => 'Детское',
        'category_link' => 'https://mioletto.su/detskoe/',
        'page_num' => 2
    ),   // 2
    'bedcover' => array(
        'category_name' => 'Покрывала',
        'category_link' => 'https://mioletto.su/pokryvala/',
        'page_num' => 1
    ),  // 0
    'var_duvet_covers' => array(
        'category_name' => 'Разное / пододеяльники',
        'category_link' => 'https://mioletto.su/raznoe/pododeyalniki/',
        'page_num' => 2
    ),  // 2
    'pillowcases' => array(
        'category_name' => 'Разное / наволочки',
        'category_link' => 'https://mioletto.su/raznoe/navolochki/',
        'page_num' => 1       
    ),  // 0
    'var_bedsheets' => array(
        'category_name' => 'Разное / простыни',
        'category_link' => 'https://mioletto.su/raznoe/prostyni/',
        'page_num' => 4           
    ),  // 4
    'var_decor_pillows' => array(
        'category_name' => 'Разное / декоративные подушки',
        'category_link' => 'https://mioletto.su/raznoe/dekorativnye-podushki/',
        'page_num' => 1   
    ),  // 0
    'var_decor_pillowcases' => array(
        'category_name' => 'Разное / декоративные наволочки',
        'category_link' => 'https://mioletto.su/raznoe/dekorativnye-navolochki/',
        'page_num' => 1
    )  // 0
);

$mioletto_test = array(
    
    'bedcover' => array(
        'category_name' => 'Покрывала',
        'category_link' => 'https://mioletto.su/pokryvala/',
        'page_num' => 1
    ),  // 0
    'var_duvet_covers' => array(
        'category_name' => 'Разное / пододеяльники',
        'category_link' => 'https://mioletto.su/raznoe/pododeyalniki/',
        'page_num' => 2
    ),  // 2
);



function getProducts($cats_array)
{
    $siteBaseUrl = 'https://mioletto.su';
    
    

    foreach ($cats_array as $category)
    {
        $productLinks = array();

        $categoryUrl    = $category['category_link'];
        $numPages       = $category['page_num'];

        for ($n = 1; $n <= $numPages; $n++)
        {
            $pageUrl = $categoryUrl .'?page='. $n;
            $productLinks[] = getProductLinks($pageUrl);
        }

        if (count($productLinks) > 1)
        {
            $links_array = array_merge(...$productLinks);   // php 5.6+
            $productLinks = $links_array;
        } else {
            $productLinks = $productLinks[0];
        }
        

        $main_fields = array(
            'Название товара',
            'Категория',
            'Артикул',
            'Цена',
            'На складе',
            'Описание',
            'Изображения'
        );

        $properties_list = array(
            'Размер',
            'Материал',
            'Размер одеяла',
            'Пододеяльник',
            'Простыня',
            'Наволочки',
            'Состав',
            'Цвет',
            'Рисунок',
            'Кружево',
            'Упаковка',
            'Рисунок 3D',
            'Однотонное',
            'Вес',
            'Производитель',
            'Страна производства',
            'Рекомендуемые'
        );

        

        $products = [];

        // $count = 0;

        foreach ($productLinks as $productPage)
        {
            // if($count < 3) {
            
            $html = HtmlDomParser::file_get_html($siteBaseUrl . $productPage);
            
            
            
            $currentItemData = [];

            $currentItemData[$main_fields[0]] = getName($html);
            $currentItemData[$main_fields[1]] = $category['category_name'];
            $currentItemData[$main_fields[2]] = getSku($html);
            $currentItemData[$main_fields[3]] = getPrice($html);
            $currentItemData[$main_fields[4]] = getStock($html);
            $currentItemData[$main_fields[5]] = getDescription($html);
            $currentItemData[$main_fields[6]] = getImages($siteBaseUrl, $html);

            foreach ($properties_list as $property)
            {
                $currentItemData[$property] = '';
            }

            $properties = getProperties($html);

            foreach ($properties_list as $prop)
            {
                foreach ($properties as $key => $value)
                {
                    if (rtrim($key, ':') == $prop)
                        $currentItemData[$prop] = $value;
                }

            }

            $currentItemData['Рекомендуемые'] = getRelated($html);

            // $count++;
            
            
            
           
            $cat_products[] = $currentItemData;
            // }
            

            
        }
        $products[] = $cat_products;

    }

    $headers = array_merge($main_fields, $properties_list);
    
    products2csv($headers, $products[0]);
}


function getProductLinks($link)
{
    $result = array();
    $html = HtmlDomParser::file_get_html($link);

    foreach ($html->find('h5 a') as $element)
    {
        $link = $element->href;
        $result[] = $link;
    }

    return $result;
}

function getName($page)
{
    $name = $page->find('div[itemprop="name"]');
    $title = $name[0]->innertext;
    
    return $title;
}

function getSku($page)
{
    $hint = $page->find('span.hint');
    $sku = $hint[0]->innertext;

    return $sku;
}

function getPrice($page)
{
    $price_div = $page->find('.price');
    $html_price = $price_div[0]->innertext;
    $price = strip_tags($html_price);
    $price = preg_replace('/\W/', '', $price);

    return $price;
}

function getStock($page)
{
    $stock_div = $page->find('.stocks strong');
    $stock_html = $stock_div[0]->innertext;
    return strip_tags($stock_html);
}

function getDescription($page)
{
    $description = '';
    $desc_paragraph = $page->find('.description p');

    for ($k = 0; $k < count($desc_paragraph); $k++)
    {
        $descr_part = $desc_paragraph[$k]->innertext;
        if ($descr_part != '')
        {
            $html_description = str_replace('<br>', '; ', $descr_part);
            $notags_descr = strip_tags($html_description);
            $no2space_descr = preg_replace('/\s\s+/', '', $notags_descr);
            $desc_array[] = $no2space_descr;
            $description = implode( ' ',  $desc_array);
        }
    }

    return $description;
}

function getProperties($page)
{
    $result = [];

    $propNames = $page->find('td.name');
    $propValues = $page->find('td.value');

    for ($i = 0; $i < count($propNames); $i++)
    {
        $result[trim($propNames[$i]->innertext)] = trim($propValues[$i]->innertext);        
    }

    return $result;
}

function getImages($baseUrl = '', $page)
{
    $img_links = [];
    $img_div = $page->find('div.image a');

    for ($i = 0; $i < count($img_div); $i++)
    {
        $img_link = $img_div[$i]->href;
        if ( ! in_array($baseUrl . $img_link, $img_links))
        {
            $img_links[$i] = $baseUrl . $img_link;
        }
    }

    return implode(', ', $img_links);
}

function getRelated($page)
{
    $related = '';
    $related_arr = [];

    foreach ($page->find('ul.related-bxslider li a') as $rel_prod)
    {
        if (($rel_prod->title != 'Сравнить') && ($rel_prod->title != ''))
        {
            if ( ! in_array($rel_prod->title, $related_arr))
            {
                $related_arr[] = $rel_prod->title;
            }                
        }
        
    }
    $related = implode('; ', $related_arr);

    return $related;
}

function products2csv($headers, $products)
{
    $fh = fopen('Mioletto2.csv', 'w');
    fputcsv($fh, $headers);

    foreach ($products as $product)
    {
        fputcsv($fh, $product);
    }

    fclose($fh);
}

getProducts($mioletto_cats);