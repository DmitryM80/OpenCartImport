<?php

class ModelCatalogCleoImport extends Model {

    public function getTables()
    {
        $tables = [];
        try {
            $q = 'SHOW TABLES';
            $res = $this->db->query($q);

        } catch (Exception $e) {
            echo 'Tables error: '. $e->getMessage();
            exit();
        }

        $tables = $res->rows;

        return $tables;
    }

    public function getColumns($table='')
    {
        $columns = [];
        if (!empty($table)) {
            $res = $this->db->query('SHOW COLUMNS FROM ' . $table);
            $columns = $res->rows;
        }
        return $columns;
    }

    public function store_db($fields, $csv)
    {
        $products_added = 0;
        $products_updated = 0;

        $brand = 'Cleo';

        $requiredFields = [
            'Изображение',
            'Артикул',
            'Наименование товара',
            'Коллекция',
            'Цвет',
            'Дизайн',
            'Тип печати',
            'Отделка (для фильтра)',
            'Отделка (в описание)',
            'Тип застежки',
            'Тип застежки наволочки',
            'Кол-во предметов в наборе',
            'Материал',
            'Плотность',
            'Состав (только для описания)',
            'Общий размер',
            'Размер пододеяльника',
            'Размер простыни',
            'Размер наволочки',
            'Объем (м3) (в описание)',
            'Вес (кг) (в описание)',
            'Размер упаковки (в описание)',
            'Упаковка комплекта',
            'Цена',
        ];

        $requiredOcProductAttributeFields = [
            'Коллекция',
            'Цвет',
            'Дизайн',
            'Тип печати',
            'Отделка (в описание)',
            'Тип застежки',
            'Тип застежки наволочки',
            'Кол-во предметов в наборе',
            'Материал',
            'Плотность',
            'Состав (только для описания)',
            'Общий размер',
            'Размер пододеяльника',
            'Размер простыни',
            'Размер наволочки',
            'Объем (м3) (в описание)',
            'Вес (кг) (в описание)',
            'Размер упаковки (в описание)',
            'Упаковка комплекта',
        ];
        $requiredOcFilterOptionFields = [
            'Коллекция',
            'Цвет',
            'Дизайн',
            'Тип печати',
            'Отделка (для фильтра)',
            'Тип застежки',
            'Тип застежки наволочки',
            'Кол-во предметов в наборе',
            'Материал',
            'Плотность',
            'Общий размер',
            'Размер пододеяльника',
            'Размер простыни',
            'Размер наволочки',
            'Упаковка комплекта',
        ];

        $productType = "Постельное бельё";
        $product = [];

//        $this->insertIntoOcFilterOptionTables($requiredOcFilterOptionFields);

        foreach ($csv as $item) {
            foreach ($item as $key => $value) {
                if (isset($requiredFields) && in_array($key, $requiredFields)) {
                    $product[$key] = $value;
                }
            }
            $productValues = $this->LoadProductPrice($product);
            if ($productValues['productsUpdated'] === 1) {$products_updated++;}

//            $brandValues = $this->insertIntoManufacturerTables($product, $brand);
//            $brandId = $brandValues['brandId'];
//            $brandName = $brandValues['brandName'];
//
//            $productValues = $this->insertIntoProductTables($product, $brandId, $brandName);
//            if ($productValues['productsAdded'] === 1) {$products_added++;}
//            if ($productValues['productsUpdated'] === 1) {$products_updated++;}
//            $productId = $productValues['productId'];
//
//            $parentCategoryId = $this->insertIntoCategoryTables($productType);
//
//            $this->insertIntoProductToCategoryTable($productId, $parentCategoryId);
//
//            foreach ($requiredOcFilterOptionFields as $optionField) {
//                $this->insertIntoOcFilterOptionTables2($optionField, $product, $productId, $parentCategoryId);
//            }
//
//            foreach ($requiredOcProductAttributeFields as $productAttributeField) {
//                $this->insertIntoOcProductAttributeOptionTables($productAttributeField, $product, $productId);
//            }
//
//            $this->insertIntoOcCategoryToStoreTable($productId, $parentCategoryId);
        }

        return $products_added . '_' . $products_updated;
    }

    public function loadProductPrice($product)
    {
        $productsUpdated = 0;

        $modelQuery = $this->db->query(
            "SELECT `model` FROM `oc_product` 
            WHERE `model`='{$product['Артикул']}'
            LIMIT 1"
        );
        if (!$this->isEmptyQueryResult($modelQuery)) {
            $price = $product['Цена'] * 1.3;
            $this->db->query(
                "UPDATE `oc_product` 
                 SET `price`={$price}
                 WHERE `model`= '{$product['Артикул']}'"
            );

            $productsUpdated++;
        }

        return [
            'productsUpdated'   => $productsUpdated
        ];
    }

    /**
     * @param $requiredOcFilterOptionFields
     *
     * @return void|null
     */
    public function insertIntoOcFilterOptionTables($requiredOcFilterOptionFields)
    {
        if (empty($requiredOcFilterOptionFields)) {
            return null;
        }

        // Creating new attributes in DB
        foreach($requiredOcFilterOptionFields as $field) {

            $field = ($field === 'Отделка (для фильтра)')   ? 'Отделка' : $field;
            $field = ($field === 'Плотность')               ? 'Плотность ткани' : $field;
            $field = ($field === 'Общий размер')            ? 'Размер' : $field;

            $option_id = $this->selectWhereName(
                'option_id',
                'oc_ocfilter_option_description',
                $field);

            if(empty($option_id)) {

                $this->db->query(
                    "INSERT INTO `oc_ocfilter_option`
                            (`type`, `keyword`, `status`, `sort_order`) 
                     VALUES ('checkbox', '{$this->getTranslite($field)}', 1, 17)"
                );

                $ocfilter_option_id = $this->db->getLastId();

                $this->db->query(
                    "INSERT INTO `oc_ocfilter_option_description`
                            (`option_id`, `language_id`, `name`) 
                     VALUES ({$ocfilter_option_id},1,'{$field}')"
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
    }

    /**
     * @param $product
     * @param $brands
     *
     * @return array
     */
    public function insertIntoManufacturerTables($product, $brand)
    {
        $currentProductBrandName = $brand;

        $currentProductBrandId = $this->selectWhereName(
            'manufacturer_id',
            'oc_manufacturer',
            $currentProductBrandName
        );

        if (empty($currentProductBrandId)) {
            $this->db->query(
                "INSERT INTO `oc_manufacturer`
                        (`name`, `image`, `sort_order`) 
                 VALUES ('{$currentProductBrandName}','',0)"
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
        }

        return [
            'brandId'   => $currentProductBrandId,
            'brandName' => $currentProductBrandName
        ];
    }

    /**
     * @param array $product
     * @param integer $brandId
     * @param integer $brandName
     *
     * @return array
     * @throws Exception
     */
    public function insertIntoProductTables($product, $brandId, $brandName)
    {
        $productsAdded = 0;
        $productsUpdated = 0;

        $tags = [
            'Наименование товара',
            'Коллекция',
            'Цвет',
            'Дизайн',
            'Материал',
            'Общий размер',
            'Упаковка комплекта',
        ];

        $tag = "";
        foreach ($tags as $tagProduct) {
            $tag = (!empty($product[$tagProduct])) ? $tag . str_replace(',', '', $product[$tagProduct]) . ', ' : $tag . '';
        }
        $tag = trim($tag,', ');

        //copy images
        if (!empty(trim($product['Изображение']))) {
            $imageName  = $this->copyImage($product['Изображение']);
        } else {
            $imageName  = '';
        }

        //oc_product and oc_product_description
        $currentProductId = $this->db->query(
            "SELECT `product_id` FROM `oc_product`
                 WHERE `model` = '{$product['Артикул']}' 
                 AND `sku` = '{$product['Артикул']}' 
                 LIMIT 1"
        );

        $upc = $this->getUpc($product['Артикул']);
        if ($this->isEmptyQueryResult($currentProductId)) {
            $this->db->query(
                "INSERT INTO `oc_product`
                        (`model`, `sku`, `upc`, `ean`, 
                        `jan`, `isbn`, `mpn`, `location`, 
                        `quantity`, `stock_status_id`, `image`, `manufacturer_id`, 
                        `price`, `tax_class_id`, `date_available`, `status`, 
                        `date_added`, `date_modified`,  `product_stickers`, `import_batch`) 
                 VALUES 
                        ('{$product['Артикул']}','{$product['Артикул']}','{$upc}','',
                        '','','','',
                        100,7,'{$imageName}','{$brandId}',
                        0,1,CURRENT_TIMESTAMP,1,
                         CURRENT_TIMESTAMP,CURRENT_TIMESTAMP,'',CONCAT('cleo_import-', CURRENT_DATE))"
            );

            $productId = $this->db->getlastId();

            $this->db->query(
                "INSERT INTO `oc_product_description`
                        (`product_id`, `language_id`, `name`, `description`, 
                         `care`, `tag`, `meta_title`, `meta_h1`, 
                         `meta_description`, `meta_keyword`) 
                 VALUES 
                    ({$productId},1,'{$product['Наименование товара']}', '',
                     '','{$tag}','{$product['Наименование товара']}','{$product['Наименование товара']}',
                     '{$tag}','{$tag}')"
            );

            $productsAdded++;

        } else {

            $productId = $currentProductId->row['product_id'];

            $this->db->query(
                "UPDATE `oc_product` 
                 SET `manufacturer_id`={$brandId},
                     `upc`='{$upc}',
                     `image`='{$imageName}',
                     `date_modified`=CURRENT_TIMESTAMP, 
                     `import_batch`=CONCAT('cleo_import-', CURRENT_DATE) 
                 WHERE `product_id`= {$productId}"
            );

            $this->db->query(
                "UPDATE `oc_product_description` 
                 SET `name`='{$product['Наименование товара']}',`description`='',`tag`='{$tag}',
                     `meta_title`='{$product['Наименование товара']}',`meta_h1`='{$product['Наименование товара']}',
                     `meta_description`='{$tag}',`meta_keyword`='{$tag}'
                 WHERE `product_id`={$productId}"
            );

            $productsUpdated++;
        }

        // oc_product_image
        if (!empty($imageName)) {
            $this->db->query(
                "INSERT INTO `oc_product_image`
                        (`product_id`, `image`, `sort_order`)
                 VALUES ('{$productId}','{$imageName}',0)"
            );
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
     * @param $ocf_option
     * @param $product
     * @param $productId
     * @param $parentCategoryId
     */
    public function insertIntoOcFilterOptionTables2($ocf_option, $product, $productId, $parentCategoryId)
    {
        $currentOption = $ocf_option;
        $currentOption = ($ocf_option === 'Отделка (для фильтра)') ? 'Отделка' : $currentOption;
        $currentOption = ($ocf_option === 'Плотность') ? 'Плотность ткани' : $currentOption;
        $currentOption = ($ocf_option === 'Общий размер') ? 'Размер' : $currentOption;


        $ocfOptionId = $this->selectWhereName(
            'option_id',
            'oc_ocfilter_option_description',
            $currentOption
        );
//////////////////////
        $currentAttributeValue = str_replace('/', '-', $product[$ocf_option]);
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
             WHERE `option_id` = {$ocfOptionId} AND `keyword` LIKE '{$keyword_frst_sym}%'"
        )->rows;

        foreach ($keywords as $k => $kwd) {
            // Убираем лишние символы из keyword из БД
            $clean_keyword = preg_replace('/\W/', '', $kwd['keyword']);
            if ($clean_keyword === $transformed_keyword) {
                // Если находится подходящий, берем первый
                $ocfOptionKeywordTranslite = $kwd['keyword'];
                break;
            }
        }
/////////////////

        if (!empty($ocfOptionId) && !empty($ocfOptionKeywordTranslite)) {
            $ocfOptionValueIdQuery = $this->db->query(
                "SELECT `value_id` FROM `oc_ocfilter_option_value_description`
                 WHERE `option_id` = {$ocfOptionId} AND `name` = '{$currentAttributeValue}'
                 LIMIT 1"
            );

            if ($this->isEmptyQueryResult($ocfOptionValueIdQuery)) {
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
                $ocfOptionValueId = $ocfOptionValueIdQuery->row['value_id'];

                $this->db->query(
                    "UPDATE `oc_ocfilter_option_value_description` 
                     SET `name`='{$currentAttributeValue}'
                     WHERE `value_id`={$ocfOptionValueId} AND `option_id`={$ocfOptionId}"
                );
            }

            // Аттрибуты для фильтра

            $ocfAttributeId = $ocfOptionId - 10000;
            $attributeNameMod = $this->getTranslite($currentOption).'_'.$ocfAttributeId;

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
                            '{$currentAttributeValue}','{$ocfOptionKeywordTranslite}','{$currentOption}','{$attributeNameMod}')"
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
    }

    /**
     * @param $ocProductAttribute
     * @param $product
     * @param $productId
     */
    public function insertIntoOcProductAttributeOptionTables($ocProductAttribute, $product, $productId)
    {
        $currentOption = $ocProductAttribute;
        $currentOption = ($ocProductAttribute === 'Отделка (в описание)') ? 'Отделка' : $currentOption;
        $currentOption = ($ocProductAttribute === 'Состав (только для описания)') ? 'Состав' : $currentOption;
        $currentOption = ($ocProductAttribute === 'Объем (м3) (в описание)') ? 'Объем (м3)' : $currentOption;
        $currentOption = ($ocProductAttribute === 'Вес (кг) (в описание)') ? 'Вес (кг)' : $currentOption;
        $currentOption = ($ocProductAttribute === 'Размер упаковки (в описание)') ? 'Размер упаковки' : $currentOption;
        $currentOption = ($ocProductAttribute === 'Общий размер') ? 'Размер' : $currentOption;
        // Аттрибуты внизу картинки товара

        $currentAttributeValue = str_replace('/', '-', $product[$ocProductAttribute]);
        if (!empty($currentAttributeValue)) {
            $currentAttributeId = $this->selectWhereName(
                'attribute_id',
                'oc_attribute_description',
                $currentOption
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
                     VALUES ({$current_attribute_id},1,'{$currentOption}')"
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
    private function getUpc($code)
    {
        $result = substr($code,3);
        return $result;
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
        $import_dir = 'cleo_import/';
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
                'К','Л','М','Н','О','П','Р','С','Т','У', 'Ф','Х','Ц','Ч','Ш','Щ','Ъ', 'Ы','Ь', 'Э', 'Ю','Я');
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
}
