<?php 

use \Hcode\PageAdmin;
use \Hcode\model\User;
use \Hcode\model\Product;

$app->get("/admin/products", function() {

	User::verifyLogin();
	$products = Product::listAll();
	$page = new PageAdmin();
	$page->setTpl("products", array(
		"products"=>$products
	));

});

$app->get("/admin/products/create", function() {

	User::verifyLogin();
	$page = new PageAdmin();
	$page->setTpl("products-create");

});

$app->post("/admin/products/create", function () {

 	User::verifyLogin();
	$product = new Product();
 	$product->setData($_POST);
	$product->save();
	header("Location: /admin/products");
 	exit;

});

$app->get("/admin/products/:idproduct", function($idproduct) {
	
	User::verifyLogin();
	$product = new Product();
	$product->get((int)$idproduct);
	$page = new PageAdmin();
	$page->setTpl("products-update", [
		'product'=>$product->getValues()
	]);

});

$app->get("/admin/products/:idproduct/delete", function($idproduct) {

	User::verifyLogin();
	$product = new Product();
	$product->get((int)$idproduct);
	$product->delete();
	header("Location: /admin/products");
	exit;
	
});

$app->post("/admin/products/:idproduct", function($idproduct) {

	User::verifyLogin();
	$product = new Product();
	$product->get((int)$idproduct);
	$product->setData($_POST);
	$product->update();
	$product->setPhoto($_FILES["file"]);
	header("location: /admin/products");
	exit;

});

?>