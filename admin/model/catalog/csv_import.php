<?php

class ModelCatalogCsvImport extends Model {

   
    

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

        
        $columns = '';
        $ignore = array();
        $csv_item = array();
        $exists_cat = '';
        $exists_parent_cat = '';
        $cats_added = 0;
        $new_product_id = null;
        $products_added = 0;
        $products_updated = 0;
        $ocf_fields = array();
        $ocf_filter_options = array();
        $table_exists = null;
        
        $added_product_id = null;
        $category_added_before = null;

        foreach ($fields as $k => $field)
        {

           
            // Отделка	Тип простыни	Тип печати	Упаковка комплекта	Тип застежки
            if($field == 'Отделка' || $field == 'Тип простыни' || $field == 'Тип печати' || $field == 'Упаковка комплекта' 
            || $field == 'Тип застежки' || $field == 'country' || $field == 'filler' || $field == 'duvet' || $field == 'pillowcase' 
            || $field == 'sheet')
            {
                $ocf_fields[] = $field;
            }
            
            
            
            if ($field != 'cat_id' && $field != 'par_cat_id'
             && $field != 'name' && $field != 'article' && $field != 'article_and_param' && $field != 'article_and_param_val'
              && $field != 'price' && $field != 'count_goods' && $field != 'textile' && $field != 'color_count' && $field != 'base_color' 
              && $field != 'pillowcase' && $field != 'duvet' && $field != 'sheet' && $field != 'param_value' && $field != 'consist' 
              && $field != 'pict_style' && $field != 'country' && $field != 'brand' && $field != 'img_src' && $field != 'img_name' && $field != 'density'
              && $field != 'Отделка' && $field != 'Тип простыни' && $field != 'Тип печати' && $field != 'Упаковка комплекта' 
            && $field != 'Тип застежки' && $field != 'param_name' && $field != 'param_value' && $field != 'filler')
            {
                $ignore[] = $field;
                continue;
                
            }
            
            
           
            $columns .= '`'. $field .'`'.',';


        }

        


        if ($columns)
        {

            /* $brands = array(
                    'Famille'               => 'Фамилье'
                ,   'Valtery'               => 'Вальтери'
                ,   'Сайлид'                => 'Сайлид'
                ,   'IRYA'                  => 'IRYA'
                ,   'Чебоксарский Текстиль' => 'Чебоксарский Текстиль'
                ,   'Пиллоу'                => 'ПИЛЛОУ'
                ,   'Roseberry'             => 'ROSEBERRY'
                ,   'OdaModa'               => 'OdaModa'
                ,   'Incalpaca TPX'         => 'INCALPACA TPX'
            ); */
    
            // Attributes
            // поле в импортируемой таблице => id аттрибута в БД

            $attr2id = array(
                    'textile'       => '12'
                ,   'color_count'   => '20'
                ,   'base_color'    => '21'
                ,   'pillowcase'    => '23'
                ,   'duvet'         => '22'
                ,   'sheet'         => '24'
                ,   'param_value'   => '26'
                ,   'consist'       => '15'
                ,   'pict_style'    => '13'
                ,   'country'       => '14'
                ,   'density'       => '25'
            );

            if ($ocf_fields)
            {
                // Creating new attributes in DB
                $res = '';
                $new_attr_ids = array();
                
                foreach($ocf_fields as $attr)
                {

                    
                    if($attr == 'filler') $attr = 'Наполнитель';
                    if($attr == 'country') $attr = 'Страна производитель';
                    if($attr == 'duvet') $search_option = 'Размер одеяла';
                    if($attr == 'pillowcase') $search_option = 'Размер наволочки';
                    if($attr == 'sheet') $search_option = 'Размер простыни';
                    

                    $ocf_filter_options[] = $attr;
                    
                    $translit_attr = $this->translit($attr);
                    
                    $ocfilter_option_id = $this->name_exists('option_id', 'oc_ocfilter_option_description', $attr);

                    // Attributes 
                    // dd($attr);


                    if( ! $ocfilter_option_id)
                    {
                        

                        $ocfilter_option_query = 'INSERT INTO `oc_ocfilter_option`
                        (`type`, `keyword`, `status`, `sort_order`) 
                        VALUES ("checkbox", "'. $translit_attr .'", 1, 17)';

                        $this->db->query($ocfilter_option_query);
                        $ocfilter_option_id = $this->db->getLastId();

                        $ocfilter_option_desc_query = 'INSERT INTO `oc_ocfilter_option_description`
                        (`option_id`, `language_id`, `name`) 
                        VALUES ("'. $ocfilter_option_id .'",1,"'. $attr .'")';

                        $this->db->query($ocfilter_option_desc_query);


                        $ocfilter_opt2store_id = $this->exists('option_id', 'oc_ocfilter_option_to_store', 'option_id', $ocfilter_option_id);

                        if( ! $ocfilter_opt2store_id)
                        {
                            $ocfilter_option2store_query = 'INSERT INTO `oc_ocfilter_option_to_store`
                            (`option_id`, `store_id`) 
                            VALUES ("'. $ocfilter_option_id .'",0)';

                            $this->db->query($ocfilter_option2store_query);
                        }
                    }
                }

                // dd($ocf_filter_options);
            }
            

            
            

            foreach ($csv as $item)
            {
                $added_parent_category_id = null;
                $added_category_id = null;


                foreach ($item as $k => $v)
                {

                    

                    if (is_array($ignore) && in_array($k, $ignore)) continue;
                    
                    $csv_item[$k] = $v;

                }

                
                //--------------    Товары   -------------//

                // Товар существует?
                               
                $product_exists = $this->db->query('SELECT `product_id` FROM `oc_product` AS cnt 
                WHERE `model` = "'. $csv_item['article_and_param_val'] .'" AND `sku` = "'. $csv_item['article_and_param'] .'" 
                LIMIT 1');


                // Brand
                // $product_brand = isset($brands[$csv_item['brand']]) ? $brands[$csv_item['brand']] : $csv_item['brand'];
                

                // $product_brand_id = $this->name_exists('manufacturer_id', 'oc_manufacturer', $product_brand);                   
                $product_brand_id = $this->name_exists('manufacturer_id', 'oc_manufacturer', $csv_item['brand']);  
                                

                if( ! $product_brand_id)
                {
                    $csv_brand_exists = $this->db->query('SELECT `manufacturer_id` FROM `oc_manufacturer` AS cnt 
                    WHERE `name` = "'. $csv_item['brand'] .'"
                    LIMIT 1');


                    if($csv_brand_exists->num_rows == 0)
                    {
                        $this->db->query('INSERT INTO `oc_manufacturer`
                        (`name`, `image`, `sort_order`) 
                        VALUES 
                        ("'. $csv_item['brand'] .'","",0)');                            

                        $product_brand_id = $this->db->getLastId();
                        
                        $this->db->query('INSERT INTO `oc_manufacturer_description`
                        (`manufacturer_id`, `language_id`, `name`, `description`, `meta_title`, `meta_h1`, `meta_description`, `meta_keyword`) 
                        VALUES 
                        ('. $product_brand_id .', 1,"'. $csv_item['brand'] .'","'. $csv_item['brand'] .'","'. $csv_item['brand'] .'",
                        "'. $csv_item['brand'] .'","'. $csv_item['brand'] .'","'. $csv_item['brand'] .'")');

                        $this->db->query('INSERT INTO `oc_manufacturer_to_store`
                        (`manufacturer_id`, `store_id`) 
                        VALUES ('. $product_brand_id .',0)');
                    }
                    
                }
                /* else
                {
                    $update_brand_descr_query = 'UPDATE `oc_manufacturer_description` 
                    SET `description`="'. $csv_item['brand'] .'",`meta_title`="'. $csv_item['brand'] .'",`meta_h1`="'. $csv_item['brand'] .'",`meta_description`="'. $csv_item['brand'] .'",`meta_keyword`="'. $csv_item['brand'] .'" 
                    WHERE `manufacturer_id` = '.$product_brand_id;

                    $this->db->query($update_brand_descr_query);
                } */


                // Image
                $image = $this->copyImage($csv_item['img_src']);


                if($product_exists->num_rows == 0)
                {
                    $query_result = $this->db->query('INSERT INTO `oc_product`(                        
                    `product_id`, `model`, `sku`, `upc`, `ean`, `jan`, `isbn`, `mpn`, `location`, `quantity`,   
                    `stock_status_id`, `image`, `manufacturer_id`, `price`, `tax_class_id`,                    
                    `date_available`, `status`, `date_added`, `date_modified`,  `product_stickers`, `import_batch`
                    ) VALUES (          
                    NULL,"' . $csv_item['article_and_param_val'] . '","' . $csv_item['article_and_param'] . '","' . $csv_item['article'] . '",
                    "","","","", "", "' . $csv_item['count_goods'] . '", 7,"' . $image . '",
                    "' . $product_brand_id . '","' . $csv_item['price'] . '",1,                    
                    CURRENT_TIMESTAMP, 1, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP, "", CONCAT("csv_import-", CURRENT_DATE))');
                    $product_id = $this->db->getLastId();
                }
                else
                {
                    $product_id = $product_exists->row['product_id'];

                    $query_result = $this->db->query('UPDATE `oc_product` 
                    SET `model` = "'.$csv_item['article_and_param_val'].'", `sku`="'.$csv_item['article_and_param'].'",
                    `upc`="'.$csv_item['article'].'", `quantity`="'.$csv_item['count_goods'].'", 
                    `image`="'.$image.'", `manufacturer_id`="'.$product_brand_id.'", `price`="'.$csv_item['price'].'", 
                    `date_modified`=CURRENT_TIMESTAMP, `import_batch`=CONCAT("csv_import-", CURRENT_DATE) 
                    WHERE `product_id`= '.$product_id .' ');

                    $added_product_id = $product_id;
                }

                    


                    if($query_result)
                    {
                        if ($product_exists->num_rows == 0)
                        {
                            $new_product_id = $this->db->getlastId();

                            // Product description
                            $query_result = $this->db->query('INSERT INTO `oc_product_description`(
                                    `product_id`, `language_id`, `name`, `description`, `care`, `tag`,
                                    `meta_title`, `meta_h1`, `meta_description`, `meta_keyword`
                                ) VALUES (
                                    ' . $new_product_id . ',1, "' . $csv_item['name'] . '","","","",
                                    "' . $csv_item['name'] . '","' . $csv_item['name'] . '","","")');

                            if ($query_result) {
                                $added_product_id = $new_product_id;
                                $products_added++;

                                if (!$this->product2store_check($added_product_id))
                                    $this->product2store_add($added_product_id);

                            }


                            // Изображение в дополнительную таблицу
                            $this->db->query('INSERT INTO `oc_product_image` (`product_id`, `image`, `sort_order`) 
                                VALUES ("' . $added_product_id . '","' . $image . '",0)');


                            // Добавляем аттрибуты
                            $attr_string = '';

                            foreach ($attr2id as $key => $item) {
                                if (!empty(trim($csv_item[$key], ' ')))
                                    $attr_string .= '(' . $added_product_id . ',"' . $item . '",1,"' . $csv_item[$key] . '"),';

                            }

                            $attr_string = trim($attr_string, ',');

                            $attr_query = 'INSERT INTO `oc_product_attribute`
                                    (`product_id`, `attribute_id`, `language_id`, `text`) 
                                    VALUES ' . $attr_string . ';';

                            $this->db->query($attr_query);
                        }
                        else
                        {
                            $update_description_query = 'UPDATE `oc_product_description` 
                            SET `name`="'.$csv_item['name'].'",`meta_title`="' . $csv_item['name'] . '",
                            `meta_h1`="' . $csv_item['name'] . '" 
                            WHERE `product_id`='.$product_id.' ';

                            $this->db->query($update_description_query);

                            $products_updated++;
                        }
                        
                        

                    }


                //--------------    Категории   -------------//
                $cat_count = 0;

                if( ! $table_exists)
                {
                    $create_catesgories_table_query = 'CREATE TABLE IF NOT EXISTS oc_cat_id 
                    (id INT(6) UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                    cat_name VARCHAR(100) NULL DEFAULT NULL,
                    parent_cat_id VARCHAR(100) NULL DEFAULT NULL)';
                    
                    $this->db->query($create_catesgories_table_query);
                    $table_exists = 1;
                }
                


                // Существует ли в таблице oc_category_description родительская категория
                $exists_parent_cat = $this->db->query('SELECT `category_id` FROM `oc_category_description` AS cnt 
                    WHERE `name` = "' . $csv_item['par_cat_id'] . '" 
                    LIMIT 1');

                                                   
                
                
                if ($exists_parent_cat->num_rows != 0) {
                    $parent_cat_id = $exists_parent_cat->row['category_id'];    // Существует - берем categoty_id
                } else {
                    // Не существует - добавляем как новую родительскую
                    $parent_cat_result = $this->db->query(
                        'INSERT INTO `oc_category`
                            (`category_id`, `image`, `image_big`, `menu_ico`, `parent_id`, `top`, `column`, `width`, `height`, 
                            `column_card`, `sort_order`, `status`, `date_added`, `date_modified`) 
                            VALUES(NULL, NULL, NULL, "", 0, 0, 1, 0, 0, 
                            0, 0, 1, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)'
                    );

                    if ($parent_cat_result) {
                        $new_parent_id = $this->db->getlastId();
                        $parent_field_value = $this->db->escape($csv_item['par_cat_id']);


                        // Category description
                        $parent_description_result = $this->db->query('INSERT INTO `oc_category_description`
                            (`category_id`, `language_id`, `name`, `description`, `meta_title`, `meta_h1`, `meta_description`, `meta_keyword`)
                            VALUES (' . $new_parent_id . ', 1, "' . $parent_field_value . '","' . $parent_field_value . '","' . $parent_field_value . '","' . $parent_field_value . '","' . $parent_field_value . '","' . $parent_field_value . '")');

                        if ($parent_description_result)
                        {
                            $added_parent_category_id = $parent_cat_id = $new_parent_id;


                            // Связь категория - магазин
                            $cat2store_exists = $this->db->query('SELECT `category_id` FROM `oc_category_to_store`
                                WHERE `category_id` = "'. $added_parent_category_id .'" LIMIT 1');              
                            
                            
                            if($cat2store_exists->num_rows == 0)
                            {                    
                                $category2store = $this->db->query('INSERT INTO `oc_category_to_store`(
                                    `category_id`, `store_id`
                                    ) VALUES (
                                    '. $added_parent_category_id .', 0)');
                            }
                            
                        }
                    }
                    
                }



                // Проверка существования product to category
                $prod2cat_exists = $this->db->query('SELECT `product_id` FROM `oc_product_to_category` AS cnt 
                WHERE `product_id` = '.$added_product_id.' AND `category_id` = '. $parent_cat_id .' 
                LIMIT 1');

                if ($prod2cat_exists->num_rows == 0)
                {
                    $product2category_query = 'INSERT INTO `oc_product_to_category`
                        (`product_id`, `category_id`, `main_category`) 
                        VALUES 
                        ('.$added_product_id.','.$parent_cat_id.',1)';

                    $this->db->query($product2category_query);
                }
                else
                {
                    $prod2cat_prod_id = $prod2cat_exists->row['product_id'];

                    $prod2cat_upd_query = 'UPDATE `oc_product_to_category` 
                        SET `product_id`='.$prod2cat_prod_id.',`category_id`='.$parent_cat_id.',`main_category`=1 
                        WHERE `product_id`='.$prod2cat_prod_id;

                    $this->db->query($prod2cat_upd_query);
                }
                

                



                // Существует ли категория в таблице описания

                $category_id = $this->exists('id', 'oc_cat_id', 'cat_name', $csv_item['cat_id']);

                
                if ( ! $category_id)
                {        
                    $query_result = $this->db->query('INSERT INTO `oc_cat_id`
                    (`cat_name`, `parent_cat_id`) 
                    VALUES ("'.$csv_item['cat_id'].'", '. $parent_cat_id .')');
                    
                }
                
                $new_product_id = $new_product_id ? $new_product_id : $added_product_id;

                // $tags = $csv_item['name'].','.$csv_item['article'].','.$csv_item['article_and_param'].','.$product_brand.','.$csv_item['cat_id'];
                $tags = $csv_item['name'].','.$csv_item['article'].','.$csv_item['article_and_param'].','.$csv_item['brand'].','.$csv_item['cat_id'];
                

                $this->db->query('UPDATE `oc_product_description` 
                SET `description`="'.$csv_item['name'].'",`tag`="'.$tags.'", `meta_description`="'.$tags.'",`meta_keyword`="'.$tags.'" 
                WHERE `product_id` = '.$new_product_id);

                // dd($product_id);

                // OC Filter

                foreach($ocf_fields as $ocf_option)
                {
                    
                    $search_option = $ocf_option;

                    if($ocf_option == 'filler')
                    {
                        $search_option = 'Наполнитель';
                    }
                    if($ocf_option == 'country')
                    {
                        $search_option = 'Страна производитель';
                    }
                    
                    if($ocf_option == 'duvet')
                    {
                        $search_option = 'Размер одеяла';
                    }
                    
                    if($ocf_option == 'pillowcase')
                    {
                        $search_option = 'Размер наволочки';
                    }
                    
                    if($ocf_option == 'sheet')
                    {
                        $search_option = 'Размер простыни';
                    }



                    
                   
                    $ocf_option_keyword = $this->translit(trim($csv_item[$ocf_option]));

                    $keyword_frst_sym = '!';

                    if( !empty($ocf_option_keyword)) $keyword_frst_sym = $ocf_option_keyword[0];

                    // Убираем лишние символы из keyword
                    $transformed_keyword = preg_replace('/\W/', '', $ocf_option_keyword);                  
                    
                    
                    $ocf_option_option_id = $this->name_exists('option_id', 'oc_ocfilter_option_description', $search_option);



                    $dbtable_keyword_query = 'SELECT `keyword` FROM `oc_ocfilter_option_value`
                    WHERE `option_id` = '.$ocf_option_option_id.' AND `keyword` LIKE "'.$keyword_frst_sym.'%"';

                    $keywords = $this->db->query($dbtable_keyword_query)->rows;

                    foreach($keywords as $k => $kwd)
                    {
                        // Убираем лишние символы из keyword из БД
                        $clean_keyword = preg_replace('/\W/', '', $kwd['keyword']) ;
                        if($clean_keyword == $transformed_keyword) 
                        {
                            // Если находится подходящий, берем первый
                            $ocf_option_keyword = $kwd['keyword'];                            
                            break;
                        }
                    }
                    

                    
                    

                    if($ocf_option_option_id && (!empty($ocf_option_keyword)))
                    {
                        $ocf_option_value_id_query = $this->db->query('SELECT `value_id` FROM `oc_ocfilter_option_value` AS cnt 
                            WHERE `option_id` = '.$ocf_option_option_id.' AND `keyword` = "'.$ocf_option_keyword.'" LIMIT 1');

                        if($ocf_option_value_id_query->num_rows == 0)
                        {

                            $oc_ocfilter_option_value_id_query = 'INSERT INTO `oc_ocfilter_option_value`
                            (`option_id`, `keyword`) 
                            VALUES ('.$ocf_option_option_id.',"'.$ocf_option_keyword.'")';

                            

                            $this->db->query($oc_ocfilter_option_value_id_query);

                            $ocf_option_value_id = $this->db->getLastId();



                            $oc_ocfilter_option_value_desc_query = 'INSERT INTO `oc_ocfilter_option_value_description`
                            (`value_id`, `option_id`, `language_id`, `name`) 
                            VALUES ("'.$ocf_option_value_id.'","'.$ocf_option_option_id.'",1,"'.$csv_item[$ocf_option].'")';

                            $this->db->query($oc_ocfilter_option_value_desc_query);


                            

                        }
                        else
                        {
                            $ocf_option_value_id = $ocf_option_value_id_query->row['value_id'];
                        }

                        // Аттрибуты для фильтра
                            
                        $ocf_attribute_id = $ocf_option_option_id - 10000;
                        $attribute_name_mod = $this->translit($search_option).'_'.$ocf_attribute_id;
                    
                        $oc_oct_filter_product_attribute_query = 'INSERT INTO `oc_oct_filter_product_attribute`
                            (`attribute_id`, `product_id`, `attribute_group_id`, `language_id`, `attribute_value`,
                            `attribute_value_mod`, `attribute_name`, `attribute_name_mod`, `sort_order`) 
                            VALUES ('.$ocf_attribute_id.','.$added_product_id.',16,1,"'.$csv_item[$ocf_option].'","'.$ocf_option_keyword.'",
                            "'.$search_option.'","'.$attribute_name_mod.'",6)';

                        

                        $this->db->query($oc_oct_filter_product_attribute_query);


                        
                        $oc_filter_option_value2prod_id = $this->db->query('SELECT `ocfilter_option_value_to_product_id` 
                            FROM `oc_ocfilter_option_value_to_product` AS cnt 
                            WHERE `option_id` = '.$ocf_option_option_id.' AND `product_id` = '.$added_product_id.' AND `value_id` = ' . $ocf_option_value_id . ' 
                            LIMIT 1');
                        

                        if ($oc_filter_option_value2prod_id->num_rows == 0)
                        {
                            $oc_option2product_query = 'INSERT INTO `oc_ocfilter_option_value_to_product`
                            (`product_id`, `option_id`, `value_id`) 
                            VALUES (' . $added_product_id . ',' . $ocf_option_option_id . ',' . $ocf_option_value_id . ')';

                            $this->db->query($oc_option2product_query);
                        }
                        else
                        {
                            $oc_option2product_query = 'UPDATE `oc_ocfilter_option_value_to_product` 
                            SET `product_id`='.$new_product_id.',`option_id`='.$ocf_option_option_id.',`value_id`='.$ocf_option_value_id.' 
                            WHERE `ocfilter_option_value_to_product_id` = '.$oc_filter_option_value2prod_id->row['ocfilter_option_value_to_product_id'];

                        }


                        $ocfilter_opt2category_id_query = $this->db->query('SELECT `option_id` FROM `oc_ocfilter_option_to_category` AS cnt 
                            WHERE `option_id` = '.$ocf_option_option_id.' AND `category_id` = '.$parent_cat_id.' LIMIT 1');

                        

                        if ($ocfilter_opt2category_id_query->num_rows == 0)
                        {
                            $oc_option2category_query = 'INSERT INTO `oc_ocfilter_option_to_category`
                            (`option_id`, `category_id`) 
                            VALUES ('.$ocf_option_option_id.','.$parent_cat_id.')';

                            $this->db->query($oc_option2category_query);
                        }


                         

                    }

                    // Аттрибуты внизу картинки товара
                    $current_attr_value = trim($csv_item[$ocf_option]);
                    
                    if(!empty($current_attr_value))
                    {
                        $existing_attribute_id = $this->name_exists('attribute_id','oc_attribute_description',$search_option);

                        
                        if($existing_attribute_id)
                        {
                            $existing_attr2product_id_query = 'SELECT `attribute_id` FROM `oc_product_attribute` 
                            WHERE `product_id`='.$product_id.' AND `attribute_id`='.$existing_attribute_id.' AND `text`="'.$current_attr_value.'"';
                            $existing_attr2product_id = $this->db->query($existing_attr2product_id_query);
                                                        

                            if($existing_attr2product_id->num_rows != 0)
                            {                                
                                $upd_attr2product_query = 'UPDATE `oc_product_attribute` 
                                SET `text`="'.$current_attr_value.'" 
                                WHERE `product_id`='.$product_id.' AND `attribute_id`='.$existing_attr2product_id->row['attribute_id'];
                                
                                $this->db->query($upd_attr2product_query);
                            }
                            else
                            {
                                $exists_attr2prod = $this->db->query('SELECT `product_id` FROM `oc_product_attribute` AS cnt
                                                                    WHERE `product_id`='.$product_id.' AND `attribute_id`='.$existing_attribute_id.'
                                                                    LIMIT 1');

                                if($exists_attr2prod->num_rows != 0)
                                {
                                    $update_attr_value_query = 'UPDATE `oc_product_attribute` 
                                    SET `text`="'.$current_attr_value.'" 
                                    WHERE `product_id`='.$product_id.' AND `attribute_id`='.$existing_attribute_id;
                                    $this->db->query($update_attr_value_query);
                                }
                                else
                                {
                                    $add_attribute2product_query = 'INSERT INTO `oc_product_attribute`(`product_id`, `attribute_id`, `language_id`, `text`) 
                                    VALUES ('.$product_id.','.$existing_attribute_id.',1,"'.$current_attr_value.'")';
                                    $this->db->query($add_attribute2product_query);
                                }
                            }                        
                        }
                        else
                        {
                            $add_attribute_query = 'INSERT INTO `oc_attribute`(`attribute_group_id`, `sort_order`) 
                            VALUES (16,0)';
                            $this->db->query($add_attribute_query);
                            $current_attribute_id = $this->db->getLastId();

                            $add_attribute_descr_query = 'INSERT INTO `oc_attribute_description`(`attribute_id`, `language_id`, `name`) 
                            VALUES ('.$current_attribute_id.',1,"'.$search_option.'")';                            
                            $this->db->query($add_attribute_descr_query);

                            $add_attribute2product_query = 'INSERT INTO `oc_product_attribute`(`product_id`, `attribute_id`, `language_id`, `text`) 
                            VALUES ('.$product_id.','.$current_attribute_id.',1,"'.$current_attr_value.'")';
                            $this->db->query($add_attribute2product_query);
                        }
                    }

                }


                
                if(($category_id || $parent_cat_id) && $added_product_id)
                {
                    $current_category_id = $category_id ? $category_id : $parent_cat_id;

                    
                    // Связь категория - магазин
                    $cat2store_exists = $this->db->query('SELECT `category_id` FROM `oc_category_to_store`
                        WHERE `category_id` = "'. $current_category_id .'" LIMIT 1');            
                    
                    
                    if($cat2store_exists->num_rows == 0)
                    {                    
                        $category2store = $this->db->query('INSERT INTO `oc_category_to_store`(
                            `category_id`, `store_id`
                            ) VALUES (
                            '. $current_category_id .', 0)');
                    }
                }                

            }                
           
        }
        return $products_added .'_'.$products_updated;
    }




    private function product2store_check ($product_id)
    {       
        
        $prod2store_exists = $this->db->query('SELECT `product_id` 
            FROM `oc_product_to_store`
            WHERE `product_id` = "'. $product_id .'" LIMIT 1');   
        $prod2store = ($prod2store_exists->num_rows == 0) ? false : true;             

        return $prod2store;
    }
    
    private function product2store_add ($product_id)
    {
        $this->db->query('INSERT INTO `oc_product_to_store`
            (`product_id`, `store_id`) 
            VALUES ('. $product_id .', 0)');
        return;
    }


    
    private function copyImage($img)
    {
        $name = null;
        $import_dir = 'csv_import/';
        $img_name = pathinfo($img, PATHINFO_FILENAME) .'.'. pathinfo($img, PATHINFO_EXTENSION);
        $img_dir = DIR_IMAGE . $import_dir;
        $img_path = $img_dir . $img_name;

        if(file_exists($img_path))
        {
            $name = $import_dir.$img_name;
        }
        else
        {
            if( ! file_exists($img_dir))            
                mkdir($img_dir, 0755);
            
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


    private function exists($data, $table, $field, $value)
    {
        $result = null;
        $query = $this->db->query('SELECT `'. $data .'` FROM `'. $table .'` AS cnt
            WHERE `'.$field.'` = "'. $value .'" 
            LIMIT 1');
        if($query->num_rows != 0)
            $result = $query->row[$data];
        
        return $result;
    }
    
    private function name_exists($data, $table, $value)
    {
        $result = null;
        $query = $this->db->query('SELECT `'. $data .'` FROM `'. $table .'` AS cnt
            WHERE `name` = "'. $value .'" 
            LIMIT 1');
        if($query->num_rows != 0)
            $result = $query->row[$data];
        
        return $result;
    }


    private function translit($string = null)
    {
        if($string != null)
        {
            $cyr  = array('а','б','в','г','д','е','ё','ж','з','и','й','к','л','м','н','о','п','р','с','т','у', 
                    'ф','х','ц','ч','ш','щ','ъ', 'ы','ь', 'э', 'ю','я','А','Б','В','Г','Д','Е','Ж','З','И','Й',
                    'К','Л','М','Н','О','П','Р','С','Т','У', 'Ф','Х','Ц','Ч','Ш','Щ','Ъ', 'Ы','Ь', 'Э', 'Ю','Я');
            $lat = array( 'a','b','v','g','d','e','io','zh','z','i','j','k','l','m','n','o','p','r','s','t','u',
                    'f' ,'h' ,'ts' ,'ch','sh' ,'sht' ,'a', 'y', 'y', 'e' ,'ju' ,'ja','A','B','V','G','D','E','Zh',
                    'Z','I','Y','K','L','M','N','O','P','R','S','T','U',
                    'F' ,'H' ,'Ts' ,'Ch','Sh' ,'Sht' ,'A' ,'Y' ,'Ju' ,'Ja');
            $lower = mb_strtolower($string);
            $word_sp = str_replace($cyr, $lat, $lower);
            $string = str_replace(' ', '-', $word_sp);
        }

        return $string;        
    }


}
