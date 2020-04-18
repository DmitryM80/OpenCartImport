<?php

class ModelCatalogTexdesignstImport extends Model {

    private $supplierName = 'texdesign';
    private $dbName = 'igorgalae2';//igorgalae2|pastelio

    public function store_db($csv)
    {
        $products_added = 0;
        $products_updated = 0;

        $requiredFields = [
            'Наименование товара для поиска',
            'Наименование товара',
            'Цена',
            'Артикул',
            'Размер (см)',
            'Рисунок',
            'Материал',
            'Состав материала',
            'Режим стирки КПБ',
            'Наличие резинки',
            'Плотность ткани',
            'Стиль',
            'Производитель',
            'Страна производитель',
            'Особенность',
            'Размер упаковки',
            'Вес в упаковке',
            'Высота матраса',
            'Цвет',
            'Изображение 1',
            'Изображение 2',
            'Изображение 3',
        ];

        $requiredOcProductAttributeFields = [
            'Размер (см)',
            'Рисунок',
            'Материал',
            'Состав материала',
            'Режим стирки КПБ',
            'Наличие резинки',
            'Плотность ткани',
            'Стиль',
            'Страна производитель',
            'Особенность',
            'Размер упаковки',
            'Вес в упаковке',
            'Высота матраса',
            'Цвет',
        ];

        $requiredOcFilterOptionFields = [
            'Размер (см)',
            'Рисунок',
            'Материал',
            'Наличие резинки',
            'Плотность ткани',
            'Стиль',
            'Страна производитель',
            'Особенность',
            'Высота матраса',
            'Цвет',
        ];

        $tags = [
            'Наименование товара',
            'Рисунок',
            'Материал',
            'Страна производитель',
            'Стиль',
            'Цвет',
            'Производитель',
        ];

        $product = [];
        $productType = "Простыни";
        $imageQuantity = 3;

        $this->createTable($this->dbName, 'product_search', 'search_name');

        foreach ($csv as $item) {
            foreach ($item as $key => $value) {
                if (isset($requiredFields) && in_array($key, $requiredFields)) {
                    $value = str_replace("'", '&#39;', $value);
                    $product[$key] = $value;
                }
            }

            $upc = $this->getUpc($product['Наименование товара']);

            $brandValues = $this->insertIntoManufacturerTables($product['Производитель'], 0);
            $brandId = $brandValues['brandId'];

            $productValues = $this->insertIntoProductTables(
                $product,
                $product['Наименование товара'],
                $product['Артикул'],
                $product['Цена'],
                $product['Изображение 1'],
                100,
                $brandId,
                $tags,
                $upc
            );
            if ($productValues['productsAdded'] === 1) {$products_added++;}
            if ($productValues['productsUpdated'] === 1) {$products_updated++;}
            $productId = $productValues['productId'];

            $this->insertIntoImageProductTable($product, $productId, $imageQuantity);

            $this->insertIntoProductSearchTable($productId, $product['Наименование товара для поиска']);

            $parentCategoryId = $this->insertIntoCategoryTables($productType);

            $this->insertIntoProductToCategoryTable($productId, $parentCategoryId);

            foreach ($requiredOcFilterOptionFields as $productFilterField) {
                $currentFilter = $productFilterField;
                $currentFilter = ($currentFilter === 'Размер (см)') ? 'Размер' : $currentFilter;
                $currentFilterValue = $product[$productFilterField];
                $this->insertIntoOcFilterOptionTables($currentFilter);
                $this->insertIntoOcFilterOptionTables2($currentFilter, $currentFilterValue, $productId, $parentCategoryId);
            }

            foreach ($requiredOcProductAttributeFields as $productAttributeField) {
                $currentAttribute = $productAttributeField;
                $currentAttribute = ($currentAttribute === 'Размер (см)') ? 'Размер' : $currentAttribute;
                $currentAttributeValue = $product[$productAttributeField];
                $this->insertIntoOcProductAttributeTables($currentAttribute, $currentAttributeValue, $productId);
            }

            $this->insertIntoOcCategoryToStoreTable($productId, $parentCategoryId);
        }

        return $products_added . '_' . $products_updated;
    }

    /**
     * @param $brand
     * @param $sortOrderBrand
     *
     * @return array
     */
    public function insertIntoManufacturerTables($brand, $currentProductBrandSortOrder)
    {
        $currentProductBrandName = trim($brand);

        if (empty($currentProductBrandName)) {
            return [
                'brandId' => 0,
            ];
        }

        $currentProductBrandId = $this->selectWhereName(
            'manufacturer_id',
            'oc_manufacturer',
            $currentProductBrandName
        );

        if (empty($currentProductBrandId)) {
            $this->db->query(
                "INSERT INTO `oc_manufacturer`
                        (`name`, `image`, `sort_order`) 
                 VALUES ('{$currentProductBrandName}','',{$currentProductBrandSortOrder})"
            );

            $currentProductBrandId = $this->db->getLastId();

            $this->db->query(
                "INSERT INTO `oc_manufacturer_description`
                        (`manufacturer_id`, `language_id`, `name`, `description`, 
                        `meta_title`, `meta_h1`, `meta_description`, `meta_keyword`) 
                 VALUES 
                        ('{$currentProductBrandId}',1,'{$currentProductBrandName}','{$currentProductBrandName}',
                        '{$currentProductBrandName}','{$currentProductBrandName}','{$currentProductBrandName}','{$currentProductBrandName}')"
            );

            $this->db->query(
                "INSERT INTO `oc_manufacturer_to_store`
                        (`manufacturer_id`, `store_id`) 
                 VALUES ('{$currentProductBrandId}',0)"
            );
        } else {
            $this->db->query(
                "UPDATE `oc_manufacturer` 
                 SET `sort_order`={$currentProductBrandSortOrder} 
                 WHERE `manufacturer_id`={$currentProductBrandId}"
            );
        }

        return [
            'brandId' => $currentProductBrandId,
        ];
    }

    /**
     * @param $requiredOcFilterOptionFields
     *
     * @return void|null
     */
    public function insertIntoOcFilterOptionTables($currentFilterValue)
    {
        // Creating new attribute in DB

        $option_id = $this->selectWhereName(
            'option_id',
            'oc_ocfilter_option_description',
            $currentFilterValue);

        if(empty($option_id)) {

            $this->db->query(
                "INSERT INTO `oc_ocfilter_option`
                        (`type`, `keyword`, `status`, `sort_order`) 
                 VALUES ('checkbox', '{$this->getTranslite($currentFilterValue)}', 1, 17)"
            );

            $ocfilter_option_id = $this->db->getLastId();

            $this->db->query(
                "INSERT INTO `oc_ocfilter_option_description`
                        (`option_id`, `language_id`, `name`) 
                 VALUES ({$ocfilter_option_id},1,'{$currentFilterValue}')"
            );

            $ocFilterOptionToStoreId = $this->selectWhere(
                'option_id',
                'oc_ocfilter_option_to_store',
                'option_id',
                $ocfilter_option_id
            );

            if(empty($ocFilterOptionToStoreId)) {
                $this->db->query(
                    "INSERT INTO `oc_ocfilter_option_to_store`
                            (`option_id`, `store_id`) 
                     VALUES ('{$ocfilter_option_id}' ,0)"
                );
            }
        }
    }

    /**
     * @param $product
     * @param $productName
     * @param $productSku
     * @param $productPrice
     * @param $productMainImage
     * @param $productQuantity
     * @param $brandId
     * @param $tags
     *
     * @return array
     */
    public function insertIntoProductTables(
        $product,
        $productName,
        $productSku,
        $productPrice,
        $productMainImage,
        $productQuantity,
        $brandId,
        $tags,
        $upc
    )
    {
        $productsAdded = 0;
        $productsUpdated = 0;

        $tag = "";
        foreach ($tags as $tagProduct) {
            $tag = (!empty($product[$tagProduct])) ? $tag . str_replace(',', '', $product[$tagProduct]) . ', ' : $tag . '';
        }
        $tag = trim($tag,', ');

        //copy images
        if (!empty(trim($productMainImage))) {
            $imageName  = $this->copyImage($productMainImage);
            if ($imageName === null) $imageName = '';
        } else {
            $imageName  = '';
        }

        //oc_product and oc_product_description
        $currentProductId = $this->db->query(
            "SELECT `product_id` FROM `oc_product`
                 WHERE `model` = '{$productSku}' 
                 AND `sku` = '{$productSku}' 
                 LIMIT 1"
        );

        if ($this->isEmptyQueryResult($currentProductId)) {
            $this->db->query(
                "INSERT INTO `oc_product`
                        (`model`, `sku`, `upc`, `ean`, 
                        `jan`, `isbn`, `mpn`, `location`, 
                        `quantity`, `stock_status_id`, `image`, `manufacturer_id`, 
                        `price`, `tax_class_id`, `date_available`, `status`, 
                        `date_added`, `date_modified`,  `product_stickers`, `import_batch`) 
                 VALUES 
                        ('{$productSku}','{$productSku}','{$upc}','',
                        '','','','',
                        100,7,'{$imageName}','{$brandId}',
                        {$productPrice},1,CURRENT_TIMESTAMP,1,
                        CURRENT_TIMESTAMP,CURRENT_TIMESTAMP,'',CONCAT('{$this->supplierName}_import-', CURRENT_DATE))"
            );
//                        100,7,'','{$brandId}',

            $productId = $this->db->getlastId();

            $this->db->query(
                "INSERT INTO `oc_product_description`
                        (`product_id`, `language_id`, `name`, `description`, 
                         `care`, `tag`, `meta_title`, `meta_h1`, 
                         `meta_description`, `meta_keyword`) 
                 VALUES 
                    ({$productId},1,'{$productName}','',
                     '','{$tag}','{$productName}','{$productName}',
                     '{$tag}','{$tag}')"
            );

            $productsAdded++;

        } else {

            $productId = $currentProductId->row['product_id'];

            $this->db->query(
                "UPDATE `oc_product` 
                 SET `manufacturer_id`={$brandId},
                     `price`='{$productPrice}',
                     `image`='{$imageName}',
                     `upc`='{$upc}',
                     `quantity`={$productQuantity},
                     `date_modified`=CURRENT_TIMESTAMP, 
                     `import_batch`=CONCAT('{$this->supplierName}_import-', CURRENT_DATE) 
                 WHERE `product_id`= {$productId}"
            );


            $this->db->query(
                "UPDATE `oc_product_description` 
                 SET `name`='{$productName}',`tag`='{$tag}',
                     `meta_title`='{$productName}',`meta_h1`='{$productName}',
                     `meta_description`='{$tag}',`meta_keyword`='{$tag}'
                 WHERE `product_id`={$productId}"
            );

            $productsUpdated++;
        }

        // oc_product_image
        if (!empty($imageName)) {
            $productImageQuery = $this->db->query(
                "SELECT `product_id` FROM `oc_product_image`
                     WHERE `product_id` = '{$productId}' 
                     AND `image` = '{$imageName}' 
                     LIMIT 1"
            );

            if ($this->isEmptyQueryResult($productImageQuery)) {
                $this->db->query(
                    "INSERT INTO `oc_product_image`
                        (`product_id`, `image`, `sort_order`)
                 VALUES ('{$productId}','{$imageName}',0)"
                );
            }
        }

        //oc_product_to_store
        $productToStoreId = $this->db->query(
            "SELECT `product_id` 
             FROM `oc_product_to_store`
             WHERE `product_id` = '{$productId}'
             LIMIT 1"
        );

        if ($this->isEmptyQueryResult($productToStoreId)) {
            $this->db->query(
                "INSERT INTO `oc_product_to_store`
                        (`product_id`, `store_id`) 
                 VALUES ({$productId}, 0)"
            );
        }
        
        return [
            'productId'         => $productId,
            'productsAdded'     => $productsAdded,
            'productsUpdated'   => $productsUpdated
        ];
    }

    /**
     * @param $parentCategoryName
     *
     * @return int|null
     */
    public function insertIntoCategoryTables($parentCategoryName)
    {
        $parentCategoryId = $this->selectWhereName(
            'category_id',
            'oc_category_description',
            $parentCategoryName
        );

        if (empty($parentCategoryId)) {
            //oc_category
            $this->db->query(
                "INSERT INTO `oc_category`
                        (`menu_ico`, `parent_id`, `top`, `column`,
                         `width`, `height`,`column_card`, `sort_order`,
                         `status`, `date_added`, `date_modified`) 
                 VALUES('',0,0,1,
                        0,0,0,0,
                        1, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)"
            );

            $parentCategoryId = $this->db->getlastId();
            
            //oc_category_description
            $this->db->query(
                "INSERT INTO `oc_category_description`
                        (`category_id`, `language_id`, `name`, `description`, 
                         `meta_title`, `meta_h1`, `meta_description`, `meta_keyword`)
                 VALUES ({$parentCategoryId}, 1, '{$parentCategoryName}','{$parentCategoryName}',
                         '{$parentCategoryName}','{$parentCategoryName}','{$parentCategoryName}','{$parentCategoryName}')"
            );

            //oc_category_path
            $this->db->query(
                "INSERT INTO `oc_category_path`
                        (`category_id`, `path_id`, `level`)
                 VALUES ({$parentCategoryId},{$parentCategoryId},0)"
            );

            //oc_category_to_layout
            $this->db->query(
                "INSERT INTO `oc_category_to_layout`
                        (`category_id`, `store_id`, `layout_id`)
                 VALUES ({$parentCategoryId},0,0)"
            );

            //oc_category_to_store
            $this->db->query(
                "INSERT INTO `oc_category_to_store`
                        (`category_id`, `store_id`)
                 VALUES ({$parentCategoryId},0)"
            );
            
        }
        return $parentCategoryId;
    }

    /**
     * @param $productId
     * @param $parentCategoryId
     */
    public function insertIntoProductToCategoryTable($productId, $parentCategoryId)
    {
        //oc_product_to_category
        $productToCategoryId = $this->db->query(
            "SELECT `product_id` 
             FROM `oc_product_to_category`
             WHERE `product_id` = {$productId} AND `category_id` = {$parentCategoryId} 
             LIMIT 1"
        );

        if ($this->isEmptyQueryResult($productToCategoryId)) {
            $this->db->query(
                "INSERT INTO `oc_product_to_category`
                        (`product_id`, `category_id`, `main_category`) 
                 VALUES ({$productId},{$parentCategoryId},1)"
            );
        } else {
            $this->db->query(
                "UPDATE `oc_product_to_category` 
                 SET `category_id`={$parentCategoryId},`main_category`=1 
                 WHERE `product_id`={$productId}"
            );
        }
    }

    /**
     * @param $currentFilter
     * @param $currentFilterValue
     * @param $productId
     * @param $parentCategoryId
     */
    public function insertIntoOcFilterOptionTables2($currentFilter, $currentFilterValue, $productId, $parentCategoryId)
    {
        $ocfOptionId = $this->selectWhereName(
            'option_id',
            'oc_ocfilter_option_description',
            $currentFilter
        );
//////////////////////
        $currentAttributeValue = str_replace('/', '-', $currentFilterValue);
        $ocfOptionKeywordTranslite = $this->getTranslite(trim($currentAttributeValue));

        $keyword_frst_sym = '!';

        if (!empty($ocfOptionKeywordTranslite)) {
            $keyword_frst_sym = $ocfOptionKeywordTranslite[0];
        }

        // Убираем лишние символы из keyword
        $transformed_keyword = preg_replace('/\W/', '', $ocfOptionKeywordTranslite);

        $keywords = $this->db->query(
            "SELECT `keyword` 
             FROM `oc_ocfilter_option_value`
             WHERE `option_id` = '{$ocfOptionId}' AND `keyword` LIKE '{$keyword_frst_sym}%'"
        )->rows;

        foreach($keywords as $k => $kwd)
        {
            // Убираем лишние символы из keyword из БД
            $clean_keyword = preg_replace('/\W/', '', $kwd['keyword']) ;
            if($clean_keyword === $transformed_keyword)
            {
                // Если находится подходящий, берем первый
                $ocfOptionKeywordTranslite = $kwd['keyword'];
                break;
            }
        }
/////////////////

        if(!empty($ocfOptionId) && !empty($ocfOptionKeywordTranslite)) {

            $ocfOptionValueIdQuery = $this->db->query(
                "SELECT `value_id` FROM `oc_ocfilter_option_value_description`
                 WHERE `option_id` = {$ocfOptionId} AND `name` = '{$currentAttributeValue}'
                 LIMIT 1"
            );

            if($this->isEmptyQueryResult($ocfOptionValueIdQuery)) {
                //oc_ocfilter_option_value
                $this->db->query(
                    "INSERT INTO `oc_ocfilter_option_value`
                            (`option_id`, `keyword`) 
                     VALUES ({$ocfOptionId},'{$ocfOptionKeywordTranslite}')"
                );

                $ocfOptionValueId = $this->db->getLastId();

                //oc_ocfilter_option_value_description
                $this->db->query(
                    "INSERT INTO `oc_ocfilter_option_value_description`
                            (`value_id`, `option_id`, `language_id`, `name`) 
                     VALUES ({$ocfOptionValueId},{$ocfOptionId},1,'{$currentAttributeValue}')"
                );

            } else {
                // TODO переделать
                $ocfOptionValueId = ($currentAttributeValue === 'Цветы') ? '4292657636' : $ocfOptionValueIdQuery->row['value_id'];

                $this->db->query(
                    "UPDATE `oc_ocfilter_option_value_description` 
                     SET `name`='{$currentAttributeValue}'
                     WHERE `value_id`={$ocfOptionValueId} AND `option_id`={$ocfOptionId}"
                );
            }

            // Аттрибуты для фильтра

            $ocfAttributeId = $ocfOptionId - 10000;
            $attributeNameMod = $this->getTranslite($currentFilter).'_'.$ocfAttributeId;

            //oc_oct_filter_product_attribute
            $octfilterProductAttribute = $this->db->query(
                "SELECT `attribute_value_mod` 
                 FROM `oc_oct_filter_product_attribute`
                 WHERE `product_id` = {$productId} AND `attribute_id` = {$ocfAttributeId} 
                 LIMIT 1"
            );
            if ($this->isEmptyQueryResult($octfilterProductAttribute)) {
                $this->db->query(
                    "INSERT INTO `oc_oct_filter_product_attribute`
                            (`attribute_id`, `product_id`, `attribute_group_id`, `language_id`,
                            `attribute_value`, `attribute_value_mod`, `attribute_name`, `attribute_name_mod`) 
                     VALUES ({$ocfAttributeId},{$productId},16,1,
                            '{$currentAttributeValue}','{$ocfOptionKeywordTranslite}','{$currentFilter}','{$attributeNameMod}')"
                );

            } else {
                $this->db->query(
                    "UPDATE `oc_oct_filter_product_attribute` 
                     SET `attribute_value_mod`='{$ocfOptionKeywordTranslite}', `attribute_value`='{$currentAttributeValue}'
                     WHERE `product_id` = {$productId} AND `attribute_id` = {$ocfAttributeId}"
                );
            }
            //oc_ocfilter_option_value_to_product
            $ocfilterOptionValueToProductId = $this->db->query(
                "SELECT `ocfilter_option_value_to_product_id` 
                 FROM `oc_ocfilter_option_value_to_product`
                 WHERE `option_id` = {$ocfOptionId} AND `product_id` = {$productId} 
                 LIMIT 1"
            );

            if ($this->isEmptyQueryResult($ocfilterOptionValueToProductId)) {
                $this->db->query(
                    "INSERT INTO `oc_ocfilter_option_value_to_product`
                            (`product_id`, `option_id`, `value_id`) 
                     VALUES ({$productId},{$ocfOptionId},{$ocfOptionValueId})"
                );
            } else {
                $this->db->query(
                    "UPDATE `oc_ocfilter_option_value_to_product` 
                     SET `product_id`={$productId},`option_id`={$ocfOptionId},`value_id`={$ocfOptionValueId} 
                     WHERE `ocfilter_option_value_to_product_id` = {$ocfilterOptionValueToProductId->row['ocfilter_option_value_to_product_id']}"
                );
            }
            //oc_ocfilter_option_to_category
            $ocFilterOptionToCategoryIdQuery = $this->db->query(
                "SELECT `option_id` 
                 FROM `oc_ocfilter_option_to_category` 
                 WHERE `option_id` = {$ocfOptionId} AND `category_id` = {$parentCategoryId} LIMIT 1"
            );

            if ($this->isEmptyQueryResult($ocFilterOptionToCategoryIdQuery)) {
                $this->db->query(
                    "INSERT INTO `oc_ocfilter_option_to_category`
                            (`option_id`, `category_id`) 
                     VALUES ({$ocfOptionId},{$parentCategoryId})"
                );
            }
        }

        // Аттрибуты внизу картинки товара

        if (!empty($currentAttributeValue)) {
            $currentAttributeId = $this->selectWhereName(
                'attribute_id',
                'oc_attribute_description',
                $currentFilter
            );

            // oc_product_attribute
            if (!empty($currentAttributeId)) {
                $existing_attr2product_id = $this->db->query(
                    "SELECT `attribute_id` 
                     FROM `oc_product_attribute` 
                     WHERE `product_id`={$productId} AND `attribute_id`={$currentAttributeId}"
                );

                if($this->isEmptyQueryResult($existing_attr2product_id)) {
                    $this->db->query(
                        "INSERT INTO `oc_product_attribute`
                                (`product_id`, `attribute_id`, `language_id`, `text`)
                         VALUES ({$productId},{$currentAttributeId},1,'{$currentAttributeValue}')"
                    );
                } else {
                    $this->db->query(
                        "UPDATE `oc_product_attribute` 
                         SET `text`='{$currentAttributeValue}' 
                         WHERE `product_id`={$productId} AND `attribute_id`={$currentAttributeId}"
                    );
                }
            } else {
                // oc_attribute
                $this->db->query(
                    "INSERT INTO `oc_attribute`
                            (`attribute_group_id`, `sort_order`) 
                     VALUES (16,0)"
                );

                $current_attribute_id = $this->db->getLastId();

                // oc_attribute_description
                $this->db->query(
                    "INSERT INTO `oc_attribute_description`
                            (`attribute_id`, `language_id`, `name`) 
                     VALUES ({$current_attribute_id},1,'{$currentFilter}')"
                );

                // oc_product_attribute
                $this->db->query(
                    "INSERT INTO `oc_product_attribute`
                            (`product_id`, `attribute_id`, `language_id`, `text`)
                     VALUES ({$productId},{$current_attribute_id},1,'{$currentAttributeValue}')"
                );
            }
        }

    }

    /**
     * @param $ocProductAttribute
     * @param $product
     * @param $productId
     */
    public function insertIntoOcProductAttributeTables($currentAttribute, $currentAttributeValue, $productId)
    {
        // Аттрибуты внизу картинки товара

        $currentAttributeValue = str_replace('/', '-', $currentAttributeValue);
        if (!empty($currentAttributeValue)) {
            $currentAttributeId = $this->selectWhereName(
                'attribute_id',
                'oc_attribute_description',
                $currentAttribute
            );

            // oc_product_attribute
            if (!empty($currentAttributeId)) {
                $existing_attr2product_id = $this->db->query(
                    "SELECT `attribute_id` 
                     FROM `oc_product_attribute` 
                     WHERE `product_id`={$productId} AND `attribute_id`={$currentAttributeId}"
                );

                if($this->isEmptyQueryResult($existing_attr2product_id)) {
                    $this->db->query(
                        "INSERT INTO `oc_product_attribute`
                                (`product_id`, `attribute_id`, `language_id`, `text`)
                         VALUES ({$productId},{$currentAttributeId},1,'{$currentAttributeValue}')"
                    );
                } else {
                    $this->db->query(
                        "UPDATE `oc_product_attribute` 
                         SET `text`='{$currentAttributeValue}' 
                         WHERE `product_id`={$productId} AND `attribute_id`={$currentAttributeId}"
                    );
                }
            } else {
                // oc_attribute
                $this->db->query(
                    "INSERT INTO `oc_attribute`
                            (`attribute_group_id`, `sort_order`) 
                     VALUES (16,0)"
                );

                $current_attribute_id = $this->db->getLastId();

                // oc_attribute_description
                $this->db->query(
                    "INSERT INTO `oc_attribute_description`
                            (`attribute_id`, `language_id`, `name`) 
                     VALUES ({$current_attribute_id},1,'{$currentAttribute}')"
                );

                // oc_product_attribute
                $this->db->query(
                    "INSERT INTO `oc_product_attribute`
                            (`product_id`, `attribute_id`, `language_id`, `text`)
                     VALUES ({$productId},{$current_attribute_id},1,'{$currentAttributeValue}')"
                );
            }
        }

    }

    /**
     * @param $productId
     * @param $parentCategoryId
     */
    public function insertIntoOcCategoryToStoreTable($productId, $parentCategoryId)
    {
        if(!empty($parentCategoryId) && !empty($productId)) {
            // Связь категория - магазин
            $ocCategoryToStore = $this->db->query(
                "SELECT `category_id` 
                 FROM `oc_category_to_store`
                 WHERE `category_id` = {$parentCategoryId}
                 LIMIT 1"
             );

            if($this->isEmptyQueryResult($ocCategoryToStore)) {
                $this->db->query(
                    "INSERT INTO `oc_category_to_store`
                            (`category_id`, `store_id`)
                     VALUES ({$parentCategoryId}, 0)"
                );
            }
        }
    }

    /**
     * @param $nameProduct
     *
     * @return string
     */
    private function getUpc($productName)
    {
        $words = explode(',', $productName);
        $firstItems = explode(' ', $words[0]);
        $secondItems = explode(' ', $words[1]);
        $thirdItems = explode(' ', $words[2]);
        $result = [];
        foreach ($firstItems as $item) {
            $result[] = substr($item, 0, 2);
        }
        foreach ($secondItems as $item) {
            $result[] = substr($item, 0, 4);
        }
        foreach ($thirdItems as $item) {
            $result[] = substr($item, 0, 4);
        }
        return implode('', $result);
    }
    
    /**
     * @param $data
     * @param $table
     * @param $field
     * @param $value
     *
     * @return integer|null
     */
    private function selectWhere($data, $table, $field, $value)
    {
        $query = $this->db->query(
            "SELECT `{$data}` 
             FROM `{$table}`
             WHERE `{$field}` = '{$value}' 
             LIMIT 1"
        );
        
        if($query->num_rows != 0) {
            return $query->row[$data];
        }
        
        return null;
    }

    /**
     * @param $data
     * @param $table
     * @param $value
     *
     * @return integer|null
     */
    private function selectWhereName($data, $table, $value)
    {
        $query = $this->db->query(
            "SELECT `{$data}` 
             FROM `{$table}`
             WHERE `name` = '{$value}' 
             LIMIT 1"
        );

        if($query->num_rows != 0) {
            return $query->row[$data];
        }

        return null;
    }

    /**
     * @param $queryResult
     *
     * @return bool
     */
    private function isEmptyQueryResult($queryResult)
    {
        return $queryResult->num_rows === 0;
    }

    /**
     * @param $img
     *
     * @return string|null
     * @throws Exception
     */
    private function copyImage($img)
    {
        $name = null;
        $import_dir = $this->supplierName . "_import/";
        $img_name = pathinfo($img, PATHINFO_FILENAME) .'.'. pathinfo($img, PATHINFO_EXTENSION);
        $img_dir = DIR_IMAGE . $import_dir;
        $img_path = $img_dir . $img_name;

        if(file_exists($img_path))
        {
            $name = $import_dir.$img_name;
        }
        else
        {
            if( ! file_exists($img_dir)) {
                mkdir($img_dir, 0755);
            } else {
                chmod($img_dir, 0755);
            }


            $image = file_get_contents($img);

            if ($image === false){
                return null;
            }

            if($image)
            {
                $name = $img_name;
                $new_file = fopen($img_dir . $name, 'w');
                fwrite($new_file, $image);
                fclose($new_file);
                $name = $import_dir . $name;
            }
            else
            {
                throw new Exception('File error');
            }
        }

        return $name;
    }

    /**
     * @param  null  $string
     *
     * @return string|string[]|null
     */
    private function getTranslite($string = null)
    {
        if($string !== null) {
            $cyr  = array('а','б','в','г','д','е','ё','ж','з','и','й','к','л','м','н','о','п','р','с','т','у', 
                    'ф','х','ц','ч','ш','щ','ъ', 'ы','ь', 'э', 'ю','я','А','Б','В','Г','Д','Е','Ж','З','И','Й',
                    'К','Л','М','Н','О','П','Р','С','Т','У', 'Ф','Х','Ц','Ч','Ш','Щ','Ъ', 'Ы','Ь', 'Э', 'Ю','Я',);
            $lat = array( 'a','b','v','g','d','e','io','zh','z','i','j','k','l','m','n','o','p','r','s','t','u',
                    'f' ,'h' ,'ts' ,'ch','sh' ,'sht' ,'a', 'i', '', 'e' ,'yu' ,'ja','A','B','V','G','D','E','Zh',
                    'Z','I','Y','K','L','M','N','O','P','R','S','T','U',
                    'F' ,'H' ,'Ts' ,'Ch','Sh' ,'Sht' ,'A' ,'I' ,'Yu' ,'Ja');
            $lower = mb_strtolower($string);
            $word_sp = str_replace($cyr, $lat, $lower);
            $string = str_replace(' ', '-', $word_sp);
            $result = str_replace('/', '-', $string);
        }

        return $result;
    }

    /**
     * @param $dbName
     * @param $tableName
     */
    public function createVideoTable($dbName, $tableName)
    {
        $showTable = $this->db->query("SHOW TABLES FROM `{$dbName}` like 'oc_{$tableName}'");

        if ($this->isEmptyQueryResult($showTable)) {
            $this->db->query(
                "CREATE TABLE IF NOT EXISTS oc_{$tableName} 
                    ({$tableName}_id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                    product_id VARCHAR(100) NULL DEFAULT NULL,
                    video VARCHAR(200) NULL DEFAULT NULL)"
            );
        }
    }

    /**
     * @param $productId
     * @param $productVideo
     */
    private function insertIntoProductVideoTable($productId, $productVideo)
    {
        if (!empty(trim($productVideo))) {
            $productVideoQuery = $this->db->query(
                "SELECT `product_id` FROM `oc_product_video`
                 WHERE `product_id` = '{$productId}' 
                 AND `video` = '{$productVideo}' 
                 LIMIT 1"
            );

            if ($this->isEmptyQueryResult($productVideoQuery)) {
                $this->db->query(
                    "INSERT INTO `oc_product_video`
                            (`product_id`, `video`)
                     VALUES ('{$productId}','{$productVideo}')"
                );
            }
        }
    }

    /**
     * @param $product
     * @param $productId
     * @param $imagesQuantity
     */
    public function insertIntoImageProductTable($product, $productId, $imagesQuantity)
    {
        for ($i = 2; $i <= $imagesQuantity; $i++) {
            $item = 'Изображение ' . $i;
            if (!empty(trim($product[$item]))) {
                $image = $this->copyImage($product[$item]);
                if ($image === null) return null;

                $productImageQuery = $this->db->query(
                    "SELECT `product_id` FROM `oc_product_image`
                     WHERE `product_id` = '{$productId}' 
                     AND `image` = '{$image}' 
                     LIMIT 1"
                );

                if ($this->isEmptyQueryResult($productImageQuery)) {
                    $this->db->query(
                        "INSERT INTO `oc_product_image`
                                (`product_id`, `image`, `sort_order`)
                         VALUES ('{$productId}','{$image}',0)"
                    );
                }
            }
        }
    }

    /**
     * @param $dbName
     * @param $tableName
     */
    private function createTable($dbName, $tableName, $fieldName)
    {
        $showTable = $this->db->query("SHOW TABLES FROM `{$dbName}` like 'oc_{$tableName}'");

        if ($this->isEmptyQueryResult($showTable)) {
            $this->db->query(
                "CREATE TABLE IF NOT EXISTS oc_{$tableName} 
                    ({$tableName}_id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                    product_id VARCHAR(100) NULL DEFAULT NULL,
                    {$fieldName} VARCHAR(200) NULL DEFAULT NULL)"
            );
        }
    }

    /**
     * @param $productId
     * @param $searchName
     */
    private function insertIntoProductSearchTable($productId, $searchName)
    {
        $productSearchQuery = $this->db->query(
            "SELECT `product_search_id` 
             FROM `oc_product_search` 
             WHERE `product_id`='{$productId}' AND `search_name`='{$searchName}'"
        );

        if (!empty(trim($searchName)) and $this->isEmptyQueryResult($productSearchQuery)) {
            $this->db->query(
                "INSERT INTO `oc_product_search`
                        (`product_id`, `search_name`)
                 VALUES ('{$productId}','{$searchName}')"
            );
        }
    }
}
