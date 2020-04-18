<?php

class ModelCatalogKingsilkImport extends Model
{

    public function store_db($csv)
    {
        $requiredFields = [
            'sku'
        ];

        $tables = [
            'oc_product_to_store',
            'oc_product_to_category',
            'oc_ocfilter_option_value_to_product',
            'oc_oct_filter_product_attribute',
            'oc_oct_filter_product_manufacturer',
            'oc_product_attribute',
            'oc_product_description',
            'oc_product_image',
            'oc_product',
        ];
        $product = [];

        foreach ($csv as $item) {
            foreach ($item as $key => $value) {
                if (isset($requiredFields) && in_array($key, $requiredFields)) {
                    $value = str_replace("'", '&#39;', $value);
                    $product[$key] = $value;
                }
            }

            $productId = $this->selectWhere('product_id', 'oc_product', 'sku', $product['sku']);

            if ($productId === null) continue;

            $this->deleteProductFromAliasTable($productId);

            foreach ($tables as $table) {
                $this->deleteProductFromTable($productId, $table);
            }
        }

        return '0_0';
    }

    public function deleteProductFromAliasTable($productId)
    {
        $this->db->query(
            "DELETE FROM `oc_url_alias` WHERE `query`='product_id={$productId}'"
        );
    }

    public function deleteProductFromTable($productId, $tableName)
    {
        $this->db->query(
            "DELETE FROM `{$tableName}` WHERE `product_id`={$productId}"
        );
    }

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
}