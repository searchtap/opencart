<?php

class ControllerExtensionModuleSearchtap extends Controller
{
    private $collectionName;
    private $adminKey;
    public $category_array = [];

    public function install()
    {
        $this->load->model('gs/searchtap');
        $this->model_gs_searchtap->createTable();

        //Registering events
        $this->load->model('extension/event');
        $this->model_extension_event->addEvent('st_product_edit', 'admin/model/catalog/product/editProduct/after', 'extension/module/searchtap/editProduct');
        $this->model_extension_event->addEvent('st_product_add', 'admin/model/catalog/product/addProduct/after', 'extension/module/searchtap/addProduct');
        $this->model_extension_event->addEvent('st_product_delete', 'admin/model/catalog/product/deleteProduct/after', 'extension/module/searchtap/deleteProduct');
        $this->model_extension_event->addEvent('st_order_delete', 'catalog/model/checkout/order/deleteOrder/after', 'extension/module/searchtap/deleteOrder');
        $this->model_extension_event->addEvent('st_order_add', 'catalog/model/checkout/order/addOrder/after', 'extension/module/searchtap/addOrder');
    }

    public function uninstall()
    {
        $this->load->model('extension/event');
        $this->model_extension_event->deleteEvent('st_product_edit');
        $this->model_extension_event->deleteEvent('st_product_add');
        $this->model_extension_event->deleteEvent('st_product_delete');
        $this->model_extension_event->deleteEvent('st_order_delete');
        $this->model_extension_event->deleteEvent('st_order_add');
    }

    public function index()
    {
        $this->load->language('extension/module/searchtap');
        $this->document->setTitle($this->language->get('heading_title'));
        $this->load->model('setting/setting');

        if (($this->request->server['REQUEST_METHOD'] == 'POST')) {
            $this->model_setting_setting->editSetting('searchtap', $this->request->post);
            $this->session->data['success'] = $this->language->get('text_success');

            $this->response->redirect($this->url->link('extension/extension', 'token=' . $this->session->data['token'], true));
        }

        $data['heading_title'] = $this->language->get('heading_title');
        $data['text_edit'] = $this->language->get('text_edit');
        $data['collection_name'] = $this->language->get('collection_name');
        $data['admin_key'] = $this->language->get('admin_key');
        $data['button_save'] = $this->language->get('button_save');
        $data['button_cancel'] = $this->language->get('button_cancel');

        if (isset($this->error['warning'])) {
            $data['error_warning'] = $this->error['warning'];
        } else {
            $data['error_warning'] = '';
        }

        $data['breadcrumbs'] = array();

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/dashboard', 'token=' . $this->session->data['token'], true)
        );

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_module'),
            'href' => $this->url->link('extension/extension', 'token=' . $this->session->data['token'], true)
        );

        $data['breadcrumbs'][] = array(
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('extension/module/searchtap', 'token=' . $this->session->data['token'], true)
        );

        $data['action'] = $this->url->link('extension/module/searchtap', 'token=' . $this->session->data['token'], true);

        $data['cancel'] = $this->url->link('extension/extension', 'token=' . $this->session->data['token'], true);

        if (isset($this->request->post['searchtap_collection'])) {
            $data['searchtap_collection'] = $this->request->post['searchtap_collection'];
        } else {
            $data['searchtap_collection'] = $this->config->get('searchtap_collection');
        }

        if (isset($this->request->post['searchtap_admin_key'])) {
            $data['searchtap_admin_key'] = $this->request->post['searchtap_admin_key'];
        } else {
            $data['searchtap_admin_key'] = $this->config->get('searchtap_admin_key');
        }

        $data['header'] = $this->load->controller('common/header');

        $data['column_left'] = $this->load->controller('common/column_left');

        $data['footer'] = $this->load->controller('common/footer');

        $this->response->setOutput($this->load->view('extension/module/searchtap', $data));
    }

    public function loadModels() {
        $this->load->model('catalog/product');
        $this->load->model('catalog/category');
        $this->load->model('localisation/currency');
        $this->load->model('catalog/manufacturer');
        $this->load->model('catalog/attribute');
        $this->load->model('gs/searchtap');
        $this->category_array = $this->model_gs_searchtap->getCategories();
    }

    public function cronFullIndexer()
    {
        $this->log = new Log('searchtap.log');

        $this->loadModels();

        $productSteps = 1000;

        $totalProducts = (int)$this->model_catalog_product->getTotalProducts(["filter_status" => "1"]);
        $this->log->write('Total products that needs to be indexed = ' . $totalProducts);

        //Index all enabled products
        for ($i = 0; $i < $totalProducts; $i += $productSteps) {

            $data = array(
                'sort' => 'p.date_added',
                'order' => 'DESC',
                'start' => $i,
                'limit' => $productSteps,
                'filter_status' => "1"
            );

            $collection = $this->model_catalog_product->getProducts($data);

            $this->log->write('Fetched ' . ($i + $productSteps) . " products from database");

            $product_array = [];

            foreach ($collection as $product) {
                $product_array[] = $this->productJSON($product);
            }

            $productJson = json_encode($product_array);

            $res = $this->searchtapCurlRequest($productJson);

            unset($product_array);
            unset($productJson);

            $this->log->write('Indexed ' . ($i + $productSteps) . " products");
        }

        $this->log->write('Indexing completed');


        //Remove all disabled products
        $totalRemovableProducts = (int)$this->model_catalog_product->getTotalProducts(["filter_status" => "0"]);

        $this->log->write('Total products that needs to be removed = ' . $totalRemovableProducts);

        for ($i = 0; $i < $totalRemovableProducts; $i += $productSteps) {

            $data = array(
                'start' => $i,
                'limit' => $productSteps,
                'filter_status' => "0"
            );

            $collection = $this->model_catalog_product->getProducts($data);

            $productIds = [];

            foreach ($collection as $product) {
                $productIds[] = $product["product_id"];
            }

            $this->searchtapCurlDeleteRequest($productIds);
        }

        $this->log->write('Deleted products from searchtap');
    }

    public function cronIndexer() {
        $this->log = new Log('searchtap.log');

        $this->loadModels();
        $product_array = [];
        $delete_product = [];
        $productStep = 1000;

        $productCount = (int)$this->model_gs_searchtap->getProductsCount()[0]["total"];

        $this->log->write('Total products in the table that need to be indexed = ' . $productCount);

        for($i = 0; $i < $productCount; $i += $productStep) {

            $products = $this->model_gs_searchtap->getProducts($productStep);

            foreach ($products as $product) {
                if ($product["status"] === "index")
                    $product_array[] = $this->productJSON($this->model_catalog_product->getProduct($product["product_id"]));
                else if ($product["status"] === "delete")
                    $delete_product[] = $product["product_id"];
            }

            if ($product_array)
                $this->searchtapCurlRequest(json_encode($product_array));
            if ($delete_product)
                $this->searchtapCurlDeleteRequest($delete_product);

            $this->model_gs_searchtap->deleteProducts($productStep);

            $this->log->write('Processed 1000 products');
        }

        $this->log->write('Indexing completed !!');
    }

    public function productJSON($product)
    {
        $productId = $product["product_id"];
        $images = [];
        $tags = [];
        $categoryLastLevel = [];
        $productAttributes = [];
        $productOptions = [];
        $prices = [];
        $discountedPrices = [];

        //get product URL
        $url = new Url(HTTP_CATALOG, $this->config->get('config_secure') ? HTTP_CATALOG : HTTPS_CATALOG);
        if ($this->config->get('config_seo_url')) {
            require_once('../catalog/controller/startup/seo_url.php');
            $rewriter = new ControllerStartupSeoUrl($this->registry);
            $url->addRewrite($rewriter);
        }
        $productURL = htmlspecialchars_decode($url->link('product/product', 'product_id=' . $product["product_id"]));

        //get product name, description and meta-keyword
        $description = $this->model_catalog_product->getProductDescriptions($productId);

        //get manufacturer name
        $manufacturer = "";

        $manufacturerArray = $this->model_catalog_manufacturer->getManufacturer($product["manufacturer_id"]);
        if ($manufacturerArray)
            $manufacturer = $manufacturerArray["name"];

        //get product price with tax
        $priceWithTax = $this->tax->calculate($product["price"], $product["tax_class_id"], $this->config->get('config_tax'));

        //get product special price
        $specialPrice = $this->model_catalog_product->getProductSpecials($productId);

        $discounted_price = (float)$product["price"];

        if (isset($specialPrice[0])) {
            $time = time();
            $currentDate = date('Y-m-d H:i:s', $time);

            if ($specialPrice[0]["date_start"] == 0 || $specialPrice[0]["date_end"] == 0) {
                $discounted_price = (float)$specialPrice[0]["price"];
            } else if ($currentDate >= $specialPrice[0]["date_start"] && $currentDate <= $specialPrice[0]["date_end"]) {
                $discounted_price = (float)$specialPrice[0]["price"];
            }
        }

        //get different currencies
        $productPrice = (float)$product["price"];

        $currencies = $this->model_localisation_currency->getCurrencies();
        foreach ($currencies as $currency) {
            $prices["price_" . $currency["code"]] = round($productPrice * $currency["value"], 2);
            $discountedPrices["discounted_price_" . $currency["code"]] = round($discounted_price * $currency["value"], 2);
        }

        //get product tags
        $tags = $description[1]["tag"] ? array_map('trim', array_unique(explode(",", $description[1]["tag"]))) : [];

        //get product images
        $images[0] = $product["image"];

        $productImages = $this->model_catalog_product->getProductImages($productId);
        foreach ($productImages as $image) {
            $images[] = $image["image"];
        }

        //get product categories
        $categoryId = [];
        $categories = $this->model_catalog_product->getProductCategories($productId);

        foreach ($categories as $catId) {
            $categoryId[] = $catId;
            $catName = htmlspecialchars_decode($this->model_catalog_category->getCategoryDescriptions($catId)[1]["name"]);
            if($catName != "More")
                $categoryLastLevel[] = $catName;
        }

        //get category mapping with their parent category
        $pathArray = [];
        $_category_level = [];

        foreach ($categoryId as $id) {
            //check whether category is enabled or not
            $cat_status =  $this->model_catalog_category->getCategory($id)["status"];
            if (!$cat_status)
                continue;

            $flag = true;
            $path = "";
            $parentId = $id;
            while ($flag) {
                foreach ($this->category_array as $cat) {
                    if ($cat["category_id"] == $parentId) {
                        if ($path == "" && htmlspecialchars_decode($cat["name"]) != "More")
                            $path = htmlspecialchars_decode($cat["name"]);
                        else if(htmlspecialchars_decode($cat["name"]) != "More")
                            $path = htmlspecialchars_decode($cat["name"]) . "|||" . $path;

                        $parentId = $cat["parent_id"];
                    }
                }
                if (!$parentId) {
                    $flag = false;
                }
            }
            if($path != "")
                $pathArray[] = $path;
        }

        foreach ($pathArray as $path) {
            $category = explode("|||", $path);

            for ($i = 0; $i < count($category); $i++) {
                if (isset($_category_level["_category_level_" . ($i + 1)])) {
                    if (!in_array($category[$i], $_category_level["_category_level_" . ($i + 1)]))
                        $_category_level["_category_level_" . ($i + 1)][] = $category[$i];
                } else
                    $_category_level["_category_level_" . ($i + 1)][] = $category[$i];
            }
        }

        //get custom attributes
        $attributes = $this->model_catalog_product->getProductAttributes($productId);
        foreach ($attributes as $attr) {
            $name = $this->model_catalog_attribute->getAttribute($attr["attribute_id"])["name"];
            $productAttributes[$name] = $attr["product_attribute_description"][1]["text"];
        }

        //get product options
        $options = $this->model_catalog_product->getProductOptions($productId);
        foreach ($options as $opt) {
            if (isset($opt["product_option_value"]))
                foreach ($opt["product_option_value"] as $value) {
                    $optValue[] = $this->model_catalog_product->getProductOptionValue($productId, $value["product_option_value_id"])["name"];
                }
            $productOptions[$opt["name"]] = isset($optValue) ? $optValue : [];
        }

        $product_array = [
            "id" => (int)$productId,
            "sku" => $product["sku"],
            "model" => $product["model"],
            "price" => (float)$product["price"],
            "status" => (int)$product["status"],
            "created_at" => strtotime($product["date_added"]),
            "stock_qty" => (int)$product["quantity"],
            "shipping" => $product["shipping"],
            "name" => strip_tags(htmlspecialchars_decode($description[1]["name"])),
            "meta_keyword" => $description[1]["meta_keyword"],
            "description" => strip_tags(htmlspecialchars_decode($description[1]["description"])),
            "manufacturer" => $manufacturer,
            "images" => $images,
            "tags" => $tags,
            "_categories" => $categoryLastLevel,
            "discounted_price" => $discounted_price,
            "category_path" => $pathArray,
            'url' => $productURL,
            'viewed' => (int)$product["viewed"]
        ];

        //return $product_array;
        return array_merge($product_array, $productAttributes, $productOptions, $prices, $discountedPrices, $_category_level);
    }

    public function getCollection()
    {
        $this->collectionName = $this->config->get('searchtap_collection');
        $this->adminKey = $this->config->get('searchtap_admin_key');

//        $this->collectionName = "beta";
//        $this->adminKey = "cdafd64d38077aa5af4f6ea8830dbed3d384d5b897157b7c66ce8b77dc10230d4259e2d9d7340adfcce430b6b8cc5583fc463308ba0b042775aa82917fbb48dade22d309de56abee7a0a52d8a509515b7a7a7c25e97082205b3c73b92a77899ef981921c3df38be0b7d1fb0cd731b2d4204a6d3236293ab3752fea9fc4362743";
    }

    public function searchtapCurlRequest($product_json)
    {
        $this->getCollection();
        $this->collectionName = $this->config->get('searchtap_collection');
        $this->adminKey = $this->config->get('searchtap_admin_key');

        $curl = curl_init();
        curl_setopt_array($curl, array(
            CURLOPT_URL => "https://api.searchtap.io/v1/collections/" . $this->collectionName,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_CAINFO => "",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "POST",
            CURLOPT_POSTFIELDS => $product_json,
            CURLOPT_HTTPHEADER => array(
                "cache-control: no-cache",
                "content-type: application/json",
                "x-auth-token: " . $this->adminKey
            ),
        ));
        curl_exec($curl);
        $err = curl_error($curl);

        $response = curl_getinfo($curl, CURLINFO_HTTP_CODE);

        curl_close($curl);
        if ($err) {
            var_dump($err);
        }

        return $response;
    }

    public function searchtapCurlDeleteRequest($productIds)
    {
        $this->getCollection();
        $curl = curl_init();
        $data_json = json_encode($productIds);

        curl_setopt_array($curl, array(
            CURLOPT_URL => "https://api.searchtap.io/v1/collections/" . $this->collectionName . "/delete",
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => 2,
            CURLOPT_CAINFO => "",
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => "",
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => "DELETE",
            CURLOPT_POSTFIELDS => $data_json,
            CURLOPT_HTTPHEADER => array(
                "cache-control: no-cache",
                "content-type: application/json",
                "x-auth-token: " . $this->adminKey
            ),
        ));
        $exec = curl_exec($curl);
        $err = curl_error($curl);
        curl_close($curl);
        if ($err) {
        }
        return;
    }

    public function editProduct($route, $product)
    {
        $productId = $product[0];
        $this->load->model('gs/searchtap');
        $this->model_gs_searchtap->setStatus($productId, "index");
    }

    public function deleteProduct($route, $product)
    {
        $productId = $product[0];
        $this->load->model('gs/searchtap');
        $this->model_gs_searchtap->setStatus($productId, "delete");
    }
}
