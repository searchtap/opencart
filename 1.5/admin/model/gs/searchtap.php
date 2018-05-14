<?php

class ModelGsSearchtap extends Model {
    public function getTaxablePrice($tax_class_id) {
        $sql = "select t2.rate from " . DB_PREFIX . "tax_rule as t1, " . DB_PREFIX . "tax_rate as t2 where t1.tax_class_id = '" . $tax_class_id . "' and t1.tax_rate_id=t2.tax_rate_id";
        $query = $this->db->query($sql);
        return $query->rows;
    }

    public function getCategories() {
        $sql = $this->db->query("select c1.category_id, c1.parent_id, c2.name from " . DB_PREFIX . "category c1, " . DB_PREFIX . "category_description c2 where c1.category_id = c2.category_id");
        return $sql->rows;
    }
}