<?php 

use \Hcode\PageAdmin;
use \Hcode\Model\User;

$app->get("/admin/users/:iduser/password", function($iduser){

	User::verifyLogin();

	$user = new User();

	$user->get((int)$iduser);

	$page = new PageAdmin();

	$page->setTpl("users-password", [
		"user"=>$user->getValues(),
		"msgError"=>User::getError(),
		"msgSuccess"=>User::getSuccess()
	]);

});

$app->post("/admin/users/:iduser/password", function($iduser){

	User::verifyLogin();

	if (!isset($_POST['despassword']) || $_POST['despassword']==='') {

		User::setError("Preencha a nova senha.");
		header("Location: /admin/users/$iduser/password");
		exit;

	}

	if (!isset($_POST['despassword-confirm']) || $_POST['despassword-confirm']==='') {

		User::setError("Preencha a confirmação da nova senha.");
		header("Location: /admin/users/$iduser/password");
		exit;

	}

	if ($_POST['despassword'] !== $_POST['despassword-confirm']) {

		User::setError("Confirme corretamente as senhas.");
		header("Location: /admin/users/$iduser/password");
		exit;

	}

	$user = new User();

	$user->get((int)$iduser);

	$user->setPassword($_POST['despassword']);

	User::setSuccess("Senha alterada com sucesso.");

	header("Location: /admin/users/$iduser/password");
	exit;

});


$app->get("/admin/users", function() {

	User::verifyLogin();

	$search = (isset($_GET['search'])) ? $_GET['search'] : "";		//VERIFICANDO SE SEARCH FOI INFORMADO SE NÃO DECLARADO ENVIAR VAZIO
	$page = (isset($_GET['page'])) ? (int)$_GET['page'] : 1; 		//VERIFICANDO QUAL PAGE USUÁRIO CLICOU SE NÃO CLICADO ENTÃO ENVIAR 1

	if ($search != '') { 											//FILTRO DA QUERY - SE VARIAVEL SEARCH FOR DIFERENTE DE VAZIO 

		$pagination = User::getPageSearch($search, $page);			//APLICA ESSE METODO

	} else {														//SENÃO APLICA METODO ABAIXO

		$pagination = User::getPage($page);							//INICIANDO METODO QUE BUSCA E FAZ A PAGINAÇÃO DOS USUARIOS

	}

	$pages = [];													// CRIANDO ARRAI PARA RECEBER ELEMENTOS COMO LINK E TEXTO DA PAGINACAO

	for ($x = 0; $x < $pagination['pages']; $x++)		//CALCULO PARA VERIFICAR QUANTOS PAGINAS E GERAR MAIS SE AINDA TIVER CONTEÚDO DENTRO DE PAGINATION['PAGES']
	{

		array_push($pages, [										//JOGANDO AS INFORMAÇÕES PARA DENTRO DO ARRAI CRIADO
			'href'=>'/admin/users?'.http_build_query([ 				//LINK PARA ONDE A PAGINA LEVA
				'page'=>$x+1,										//SOMANDO SE PRECISA DE MAIS PAGINA
				'search'=>$search 									//SEARCH
			]),
			'text'=>$x+1											//NUMERO DA PAGINA
		]);

	}

	$page = new PageAdmin();					

	$page->setTpl("users", array(				
		"users"=>$pagination['data'],
		"search"=>$search,						//PASSANDO A VARIAVEL SEARCH QUE USUÁRIO INFORMOU
		"pages"=>$pages 						//PASSANDO VARIAVEL COM TAS AS PAGINAS
	));

});

$app->get("/admin/users/create", function() {

	User::verifyLogin();

	$page = new PageAdmin();

	$page->setTpl("users-create");

});

$app->get("/admin/users/:iduser/delete", function($iduser) {

	User::verifyLogin();	

	$user = new User();

	$user->get((int)$iduser);

	$user->delete();

	header("Location: /admin/users");
	exit;

});

$app->get("/admin/users/:iduser", function($iduser) {

	User::verifyLogin();

	$user = new User();

	$user->get((int)$iduser);

	$page = new PageAdmin();

	$page->setTpl("users-update", array(
		"user"=>$user->getValues()
	));

});

$app->post("/admin/users/create", function() {

	User::verifyLogin();

	$user = new User();

	$_POST["inadmin"] = (isset($_POST["inadmin"]))?1:0;

	$_POST['despassword'] = User::getPasswordHash($_POST['despassword']);

	$user->setData($_POST);

	$user->save();

	header("Location: /admin/users");
	exit;

});

$app->post("/admin/users/:iduser", function($iduser) {

	User::verifyLogin();

	$user = new User();

	$_POST["inadmin"] = (isset($_POST["inadmin"]))?1:0;

	$user->get((int)$iduser);

	$user->setData($_POST);

	$user->update();	

	header("Location: /admin/users");
	exit;

});

 ?>