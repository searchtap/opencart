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
    }
}
