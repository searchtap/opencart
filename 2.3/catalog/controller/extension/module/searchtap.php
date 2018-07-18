<?php

class ControllerExtensionModuleSearchtap extends Controller {
    public function addOrder($route, $data) {
        $products = $data[0]["products"];
        foreach($products as $product) {
            $this->load->model('extension/gs/searchtap');
            $this->model_extension_gs_searchtap->setStatus($product["product_id"], "index");
        }
    }

    public function deleteOrder($route, $data) {
        $this->log->write('searchtap delete order event');
        $log = new Log('searchtap.log');
        $log->write($data);
//        $productId = $product[0];
//        $this->load->model('gs/searchtap');
//        $this->model_gs_searchtap->setStatus($productId, "index");
    }
}
