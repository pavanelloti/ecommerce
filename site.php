<?php 

use \Hcode\Page;
use \Hcode\model\Product;
use \Hcode\model\Category;

$app->get('/', function() {
    
    $products = Product::listAll();
    $products = Product::checkList($products);
    $page = new Page();
    $page->setTpl("index", [
    	'products'=>Product::checkList($products)
    ]);

});

$app->get("/categories/:idcategory", function($idcategory) {

	$category = new Category();
	$category->get((int)$idcategory);
	$page = new Page();
	$page->setTpl("category", array(
		"category"=>$category->getValues(),
		"products"=>[]
	));

});

?>