<?php

class ModelExtensionGsSearchtap extends Model {
    public function setStatus($productId, $status = NULL) {
        $query = $this->db->query("select * from gs_searchtap where product_id=" . $productId);
        if($query->rows) {
            $sql = $this->db->query("update gs_searchtap set last_updated_at=now(), last_indexed_at=0 , status='".$status."' where product_id=".$productId);
        }
        else {
            $sql = $this->db->query("insert into gs_searchtap(product_id, last_updated_at, status) values(".$productId.", now(), '$status')");
        }
    }
}