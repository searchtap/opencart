<?php

class ControllerModuleSearchtap extends Controller
{
    private $collectionName;
    private $adminKey;
    public $category_array = [];
    protected $log;

    public function install()
    {

    }

    private $error = array(); // This is used to set the errors, if any.

    public function index()
    {
        $this->language->load('module/searchtap'); // Loading the language file of helloworld

        $this->document->setTitle($this->language->get('heading_title')); // Set the title of the page to the heading title in the Language file i.e., Hello World

        $this->load->model('setting/setting'); // Load the Setting Model  (All of the OpenCart Module & General Settings are saved using this Model )

        if (($this->request->server['REQUEST_METHOD'] == 'POST') && (isset($_POST['reindex']))) {
            $this->cronJob();

            $this->session->data['success'] = $this->language->get('reindex_success'); // To display the success text on data save

            $this->redirect($this->url->link('extension/module', 'token=' . $this->session->data['token'], 'SSL')); // Redirect to the Module Listing
        }

        if (($this->request->server['REQUEST_METHOD'] == 'POST')) { // Start If: Validates and check if data is coming by save (POST) method
            $this->model_setting_setting->editSetting('searchtap', $this->request->post);      // Parse all the coming data to Setting Model to save it in database.

            $this->session->data['success'] = $this->language->get('text_success'); // To display the success text on data save

            $this->redirect($this->url->link('extension/module', 'token=' . $this->session->data['token'], 'SSL')); // Redirect to the Module Listing
        } // End If

        /*Assign the language data for parsing it to view*/
        $this->data['heading_title'] = $this->language->get('heading_title');

        $this->data['collection_name'] = $this->language->get('collection_name');
        $this->data['admin_key'] = $this->language->get('admin_key');
        $this->data['search_key'] = $this->language->get('search_key');

        $this->data['button_save'] = $this->language->get('button_save');
        $this->data['button_cancel'] = $this->language->get('button_cancel');
        $this->data['button_add_module'] = $this->language->get('button_add_module');
        $this->data['button_remove'] = $this->language->get('button_remove');


        /*This Block returns the warning if any*/
        if (isset($this->error['warning'])) {
            $this->data['error_warning'] = $this->error['warning'];
        } else {
            $this->data['error_warning'] = '';
        }
        /*End Block*/

        /*This Block returns the error code if any*/
        if (isset($this->error['code'])) {
            $this->data['error_code'] = $this->error['code'];
        } else {
            $this->data['error_code'] = '';
        }
        /*End Block*/


        /* Making of Breadcrumbs to be displayed on site*/
        $this->data['breadcrumbs'] = array();

        $this->data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_home'),
            'href' => $this->url->link('common/home', 'token=' . $this->session->data['token'], 'SSL'),
            'separator' => false
        );

        $this->data['breadcrumbs'][] = array(
            'text' => $this->language->get('text_module'),
            'href' => $this->url->link('extension/module', 'token=' . $this->session->data['token'], 'SSL'),
            'separator' => ' :: '
        );

        $this->data['breadcrumbs'][] = array(
            'text' => $this->language->get('heading_title'),
            'href' => $this->url->link('module/searchtap', 'token=' . $this->session->data['token'], 'SSL'),
            'separator' => ' :: '
        );

        /* End Breadcrumb Block*/

        $this->data['action'] = $this->url->link('module/searchtap', 'token=' . $this->session->data['token'], 'SSL'); // URL to be directed when the save button is pressed

        $this->data['cancel'] = $this->url->link('extension/module', 'token=' . $this->session->data['token'], 'SSL'); // URL to be redirected when cancel button is pressed


        /* This block checks, if the hello world text field is set it parses it to view otherwise get the default hello world text field from the database and parse it*/

        if (isset($this->request->post['st_collection'])) {
            $this->data['st_collection'] = $this->request->post['st_collection'];
        } else {
            $this->data['st_collection'] = $this->config->get('st_collection');
        }
        if (isset($this->request->post['st_admin_key'])) {
            $this->data['st_admin_key'] = $this->request->post['st_admin_key'];
        } else {
            $this->data['st_admin_key'] = $this->config->get('st_admin_key');
        }
        if (isset($this->request->post['st_search_key'])) {
            $this->data['st_search_key'] = $this->request->post['st_search_key'];
        } else {
            $this->data['st_search_key'] = $this->config->get('st_search_key');
        }
        /* End Block*/

        $this->data['modules'] = array();

        /* This block parses the Module Settings such as Layout, Position,Status & Order Status to the view*/
//        if (isset($this->request->post['searchtap_module'])) {
//            $this->data['modules'] = $this->request->post['searchtap_module'];
//        } elseif ($this->config->get('searchtap_module')) {
//            $this->data['modules'] = $this->config->get('searchtap_module');
//        }
        /* End Block*/

        $this->load->model('design/layout'); // Loading the Design Layout Models

        $this->data['layouts'] = $this->model_design_layout->getLayouts(); // Getting all the Layouts available on system

        $this->template = 'module/searchtap.tpl'; // Loading the helloworld.tpl template
        $this->children = array(
            'common/header',
            'common/footer'
        );  // Adding children to our default template i.e., helloworld.tpl

        $this->response->setOutput($this->render()); // Rendering the Output
    }

    public function validate()
    {
    }

    public function getCollection()
    {
        $this->collectionName = $this->config->get('st_collection');
        $this->adminKey = $this->config->get('st_admin_key');
    }

    public function cronJob()
    {

        $this->getCollection();
        $this->log = new Log('searchtap.log');

        $this->load->model('catalog/product');
        $this->load->model('catalog/category');
        $this->load->model('localisation/currency');
        $this->load->model('catalog/manufacturer');
        $this->load->model('gs/searchtap');
        $this->category_array = $this->model_gs_searchtap->getCategories();

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

            $this->searchtapCurlRequest($productJson);

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

        //get product name, description and meta-keyword
        $description = $this->model_catalog_product->getProductDescriptions($productId);

       //get product styles for filter

       $styles = [];
       $style_ids = explode(",", $description[1]["style"]);
       if(count($style_ids) > 0)
       foreach($style_ids as $id) {
            $stylesArray = $this->model_gs_searchtap->getStyles($id);
            if(isset($stylesArray[0]))
                $styles[] = $stylesArray[0]["name"];
        }

        //get manufacturer name
        $manufacturer = "";

        $manufacturerArray = $this->model_catalog_manufacturer->getManufacturer($product["manufacturer_id"]);
        if ($manufacturerArray)
            $manufacturer = $manufacturerArray["name"];

        //get product price with tax
//        $priceWithTax = $this->tax->calculate($product["price"], $product["tax_class_id"], $this->config->get('config_tax'));

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

//        get product tags
//        $productTags = $this->model_catalog_product->getProductTags($productId);
//        foreach ($productTags as $tag) {
//            $tags = explode(",", $tag);
//        }

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
            $categoryLastLevel[] = htmlspecialchars_decode($this->model_catalog_category->getCategoryDescriptions($catId)[1]["name"]);
        }

        //get category mapping with their parent category
        $pathArray = [];
        $_category_level = [];
        foreach ($categoryId as $id) {
            //check whether category is enabled or not
            $cat_status = $this->model_catalog_category->getCategory($id)["status"];
            if (!$cat_status)
                continue;

            $flag = true;
            $path = "";
            //$path = $this->model_catalog_category->getCategoryDescriptions($id)[1]["name"];
            $parentId = $id;
            while ($flag) {
                foreach ($this->category_array as $cat) {
                    if ($cat["category_id"] == $parentId) {
                        if ($path == "")
                            $path = htmlspecialchars_decode($cat["name"]);
                        else
                            $path = htmlspecialchars_decode($cat["name"]) . "|||" . $path;

                        $parentId = $cat["parent_id"];
                    }
                }
                if (!$parentId) {
                    $flag = false;
                }
            }
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
            if (isset($attr["name"]))
                $productAttributes[$attr["name"]] = $attr["product_attribute_description"][1]["text"];
        }

        //get product options
        $options = $this->model_gs_searchtap->getProductOptions($productId);
        $color = [];
        foreach($options as $opt) {
            if($opt["name"] == "Frame type") {

            if (isset($opt["option_value"])) {

             foreach ($opt["option_value"] as $value) {
                        $color[] = $value["name"];
                 }
            }
            }
        }

        foreach ($options as $opt) {

            $variations = [];
            $childCount = 0;

            if($opt["name"] != "Frame type") {
            if (isset($opt["option_value"]))
                foreach ($opt["option_value"] as $value) {

                    $val = "";
                    if(strtolower($value["name"]) == "small")
                        $val = "S";
                    else if(strtolower($value["name"]) == "medium")
                        $val = "M";
                    else if(strtolower($value["name"]) == "large")
                        $val = "L";
                    else if(strtolower($value["name"]) == "extra large")
                        $val = "XL";
                    else
                        $val = $value["name"];

                    $temp = [
                        "price" => (float)$value["price"],
                        "value" => $val,
                        "quantity" => (int)$value["quantity"]
                    ];

                    $variations[$childCount] = $temp;
                    $childCount++;
                }

            $optValue = [
                "image" => $opt["image"],
                "variations" => $variations,
                "type" => $opt["name"]
            ];

            if($opt["name"] == "Framed" || $opt["name"] == "Canvas Frame")
                $optValue["color"] = $color;

            $productOptions[] = isset($optValue) ? $optValue : [];
            }
        }

        //get product URL
        $url = new Url(HTTP_CATALOG, $this->config->get('config_secure') ? HTTP_CATALOG : HTTPS_CATALOG);
        if ($this->config->get('config_seo_url')) {
            require_once('../catalog/controller/common/seo_url.php');
            $rewriter = new ControllerCommonSeoUrl($this->registry);
            $url->addRewrite($rewriter);
        }
        $productURL = htmlspecialchars_decode($url->link('product/product', 'product_id=' . $product["product_id"]));


        //get Artist

        $artist = "";
        $artistArray = $this->model_gs_searchtap->getArtist($productId);
        if(isset($artistArray["artist_name"]))
            $artist = $artistArray["artist_name"];

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
            "tags" => array_unique(explode(",", $product["tag"])),
            "_categories" => $categoryLastLevel,
            "discounted_price" => $discounted_price,
            "category_path" => $pathArray,
            'url' => $productURL,
            'options' => $productOptions,
            'type' => $product['type'],
            'artist' => $artist,
            'styles' => $styles
        ];

        return array_merge($product_array, $productAttributes, $prices, $discountedPrices, $_category_level);
    }

    public function searchtapCurlRequest($product_json)
    {
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

        echo curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);
        if ($err) {
            var_dump($err);
        }
    }

    public function searchtapCurlDeleteRequest($productIds)
    {
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
}