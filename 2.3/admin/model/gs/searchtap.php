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

    public function createTable() {
        $sql = "create table IF NOT EXISTS gs_searchtap (product_id int NOT NULL, status varchar(100), primary KEY (product_id))";
        $query = $this->db->query($sql);
    }

    public function getStatus($productId) {
        $query = $this->db->query("select * from gs_searchtap where product_id=" . $productId);
        if($query->rows) {
            if($query->rows[0]["last_indexed_at"] >= $query->rows[0]["last_updated_at"])
                return false;
            else
                return true;
        }
        else {
            $sql = $this->db->query("insert into gs_searchtap(product_id, last_updated_at) values(".$productId.", now())");
            return true;
        }
    }

    public function setStatus($productId, $status = NULL) {
        $query = $this->db->query("select * from gs_searchtap where product_id=" . $productId);
        if($query->rows) {
            $sql = $this->db->query("update gs_searchtap set status='".$status."' where product_id=".$productId);
        }
        else {
            $sql = $this->db->query("insert into gs_searchtap(product_id, status) values($productId, '".$status ."')");
        }
    }

    public function getProductsCount () {
        $sql = $this->db->query("select count(*) as total from gs_searchtap");
        return $sql->rows;
    }

    public function getProducts($limit) {
        $sql = $this->db->query("select * from gs_searchtap limit $limit");
        return $sql->rows;
    }

    public function deleteProducts($limit) {
        $sql = $this->db->query("delete from gs_searchtap limit $limit");
    }
}