<?php 

use \Hcode\Page;
use \Hcode\Model\Product;
use \Hcode\Model\Category;
use \Hcode\Model\Cart;
use \Hcode\Model\Address;
use \Hcode\Model\User;
use \Hcode\Model\Order;
use \Hcode\Model\OrderStatus;

$app->get('/', function() {

	$products = Product::listAll();

	$page = new Page();

	$page->setTpl("index", [
		'products'=>Product::checkList($products)
	]);

});

$app->get("/categories/:idcategory", function($idcategory){

	$page = (isset($_GET['page'])) ? (int)$_GET['page'] : 1;

	$category = new Category();

	$category->get((int)$idcategory);

	$pagination = $category->getProductsPage($page);

	$pages = [];

	for ($i=1; $i <= $pagination['pages']; $i++) { 
		array_push($pages, [
			'link'=>'/categories/'.$category->getidcategory().'?page='.$i,
			'page'=>$i
		]);
	}

	$page = new Page();

	$page->setTpl("category", [
		'category'=>$category->getValues(),
		'products'=>$pagination["data"],
		'pages'=>$pages
	]);

});

$app->get("/products/:desurl", function($desurl){

	$product = new Product();

	$product->getFromURL($desurl);

	$page = new Page();

	$page->setTpl("product-detail", [
		'product'=>$product->getValues(),
		'categories'=>$product->getCategories()
	]);

});

$app->get("/cart", function(){

	$cart = Cart::getFromSession();

	$page = new Page();

	$page->setTpl("cart", [
		'cart'=>$cart->getValues(),
		'products'=>$cart->getProducts(),
		'error'=>Cart::getMsgError()
	]);

});

$app->get("/cart/:idproduct/add", function($idproduct){

	$product = new Product();

	$product->get((int)$idproduct);

	$cart = Cart::getFromSession();

	$qtd = (isset($_GET['qtd'])) ? (int)$_GET['qtd'] : 1;

	for ($i = 0; $i < $qtd; $i++) {
		
		$cart->addProduct($product);

	}

	header("Location: /cart");
	exit;

});

$app->get("/cart/:idproduct/minus", function($idproduct){

	$product = new Product();

	$product->get((int)$idproduct);

	$cart = Cart::getFromSession();

	$cart->removeProduct($product);

	header("Location: /cart");
	exit;

});

$app->get("/cart/:idproduct/remove", function($idproduct){

	$product = new Product();

	$product->get((int)$idproduct);

	$cart = Cart::getFromSession();

	$cart->removeProduct($product, true);

	header("Location: /cart");
	exit;

});

$app->post("/cart/freight", function(){

	$cart = Cart::getFromSession();

	$cart->setFreight($_POST['zipcode']);

	header("Location: /cart");
	exit;

});

$app->get("/checkout", function(){						//CRIANDO ROTA DO CHECKOUT

	User::verifyLogin(false);							//Chamando funcao que Verificando se está logado (área não admin)

	$address = new Address();							//INICIANDO NOVA CLASSE ADDRESS
	$cart = Cart::getFromSession();						//CHAMANDO METODO COM AS INFORMACAO DO CARRINHO NA SESSAO

	if (!isset($_GET['zipcode'])) {						//VERIFICANDO SE NÃO TEM CEP INFORMADO - SE FALSE

		$_GET['zipcode'] = $cart->getdeszipcode();		//ENTAO CARREGUE O QUE TEM NA SESSAO DO CARRINHO

	}

	if (isset($_GET['zipcode'])) {						//VERIFICANDO SE ALGUM CEP JÁ FOI INFORMADO - SE TRUE

		$address->loadFromCEP($_GET['zipcode']); 		//CHAMANDO METODO COM INF DO CEP INFORMADO 

		$cart->setdeszipcode($_GET['zipcode']);			//INFORMANDO NOVO CEP PARA SESSAO CARRINHO

		$cart->save();									//SALVANDO CARRINHO

		$cart->getCalculateTotal();						//RECALCULANDO TOTAL APOS TROCA DE CEP

	}

	if (!$address->getdesaddress()) $address->setdesaddress('');		//SE OS CAMPOS NÃO ESTIVEREM DEFINIDO ENTÃO DEFINA VAZIO MESMO
	if (!$address->getdesnumber()) $address->setdesnumber('');
	if (!$address->getdescomplement()) $address->setdescomplement('');
	if (!$address->getdesdistrict()) $address->setdesdistrict('');
	if (!$address->getdescity()) $address->setdescity('');
	if (!$address->getdesstate()) $address->setdesstate('');
	if (!$address->getdescountry()) $address->setdescountry('');
	if (!$address->getdeszipcode()) $address->setdeszipcode('');

	$page = new Page();						//INICIANDO NOVA CLASSE PAGINA

	$page->setTpl("checkout", [				//SETANDO INF A CARREGAR PARA TEMPLATE DA PAGINA
		'cart'=>$cart->getValues(),			//CARREGANDO INFORMACAO DO CARRINHO
		'address'=>$address->getValues(),	//CARREGANDO ENDERECO 
		'products'=>$cart->getProducts(),	//CARREGANDO PRODUTOS QUE ESTAO NO CARRINHO
		'error'=>Address::getMsgError()		//CARREGANDO MENSAGEM DE ERRO
	]);

});

$app->post("/checkout", function(){												//ROTA PARA RECEBER FORM 

	User::verifyLogin(false);													//Chamando funcao que Verificando se está logado (área não admin)

	if (!isset($_POST['zipcode']) || $_POST['zipcode'] === '') {				//VALIDANDO SE INFORMACAO FOI PASSADO NO POST
		Address::setMsgError("Informe o CEP.");									//MENSAGEM DE ERRO NA TELA
		header('Location: /checkout');											//REDIRECIONANDO APÓS ERRO
		exit;
	}

	if (!isset($_POST['desaddress']) || $_POST['desaddress'] === '') {
		Address::setMsgError("Informe o endereço.");
		header('Location: /checkout');
		exit;
	}

	if (!isset($_POST['desnumber']) || $_POST['desnumber'] === '') {
		Address::setMsgError("Informe número.");
		header('Location: /checkout');
		exit;
	}

	if (!isset($_POST['desdistrict']) || $_POST['desdistrict'] === '') {
		Address::setMsgError("Informe o bairro.");
		header('Location: /checkout');
		exit;
	}

	if (!isset($_POST['descity']) || $_POST['descity'] === '') {
		Address::setMsgError("Informe a cidade.");
		header('Location: /checkout');
		exit;
	}

	if (!isset($_POST['desstate']) || $_POST['desstate'] === '') {
		Address::setMsgError("Informe o estado.");
		header('Location: /checkout');
		exit;
	}

	if (!isset($_POST['descountry']) || $_POST['descountry'] === '') {
		Address::setMsgError("Informe o país.");
		header('Location: /checkout');
		exit;
	}

	$user = User::getFromSession();											//CHAMANDO METODO QUE CARREGA INFORMAÇÃO DO USUER NA SESSÃO

	$address = new Address();												//INSTANCIANDO NOVA CLASSE

	$_POST['deszipcode'] = $_POST['zipcode'];		//SOBRESCREVENDO INF COM NOME DE CAMPOS !=
	$_POST['idperson'] = $user->getidperson();		//CARREGANDO INFORMACAO ADICIONAL PARA SALVAR NO BANCO

	$address->setData($_POST);				//CARREGANDO METODO QUE ORGANIZARA INF RECEBIDA VIA POST PARA BANCO

	$address->save();						//CARREGANDO METODO QUE SALVA INF NO BANCO

	$cart = Cart::getFromSession();			//CHAMANDO METODO QUE RECUPERA INFORMAÇÕES DO CART NA SESSAO

	$cart->getCalculateTotal();				//CHAMDNO FUNCAO QUE CALCULA TOTAL DO CARINHO

	$order = new Order();					//INSTANCIANDO NOVA CLASSE

	$order->setData([								//PASSANDO PARAMETROS PARA METODO setData
		'idcart'=>$cart->getidcart(),
		'idaddress'=>$address->getidaddress(),
		'iduser'=>$user->getiduser(),
		'idstatus'=>OrderStatus::EM_ABERTO,
		'vltotal'=>$cart->getvltotal()

	]);

	$order->save();

	header("Location: /order/".$order->getidorder());
	exit;
	
});

$app->get("/order/:idorder", function($idorder) {

	User::verifyLogin(false);

	$order = new Order();

	$order->get((int)$idorder);  					//CHAMANDO METODO PARA CARREGAR DO BANCO INF DA ENCOMENDA

	$page = new Page();

	$page->setTpl("payment", [
		'order'=>$order->getValues()
	]);

});

$app->get("/login", function(){

	$page = new Page();

	$page->setTpl("login", [
		'error'=>User::getError(),
		'errorRegister'=>User::getErrorRegister(),
		'registerValues'=>(isset($_SESSION['registerValues'])) ? $_SESSION['registerValues'] : [
			'name'=>'',
			'email'=>'',
			'phone'=>''
		]
	]);

});

$app->post("/login", function(){

	try {

		User::login($_POST['login'], $_POST['password']);

	} catch(Exception $e) {

		User::setError($e->getMessage());

	}

	header("Location: /checkout");
	exit;

});

$app->get("/logout", function(){

	User::logout();

	header("Location: /login");
	exit;

});

$app->post("/register", function() {

	$_SESSION['registerValues'] = $_POST;

	if (!isset($_POST['name']) || $_POST['name'] == '' ) {
		User::setErrorRegister("Preencha o Campo Nome.");
		header("Location: /login");
		exit;
	}

	if (!isset($_POST['email']) || $_POST['email'] == '' ) {
		User::setErrorRegister("Preencha o Campo E-Mail.");
		header("Location: /login");
		exit;
	}

	if (!isset($_POST['password']) || $_POST['password'] == '' ) {
		User::setErrorRegister("Digite uma Senha.");
		header("Location: /login");
		exit;
	}

	if (User::checkLoginExists($_POST['email']) === true) {
		User::setErrorRegister("Já existe uma conta registrada com esse endereço de e-mail.");
		header("Location: /login");
		exit;
	}

	$user = new User();

	$user->setData([
		'inadmin'=>0,
		'deslogin'=>$_POST["email"],
		'desperson'=>$_POST["name"],
		'desemail'=>$_POST["email"],
		'despassword'=>$_POST["password"],
		'nrphone'=>$_POST["phone"]
	]);

	$user->save();

	User::login($_POST['email'], $_POST['password']);

	header("Location: /checkout");
	exit;


});

$app->get("/forgot", function() {

	$page = new Page();

	$page->setTpl("forgot");	

});

$app->post("/forgot", function(){

	$user = User::getForgot($_POST["email"], false);

	header("Location: /forgot/sent");
	exit;

});

$app->get("/forgot/sent", function(){

	$page = new Page();

	$page->setTpl("forgot-sent");	

});


$app->get("/forgot/reset", function(){

	$user = User::validForgotDecrypt($_GET["code"]);

	$page = new Page();

	$page->setTpl("forgot-reset", array(
		"name"=>$user["desperson"],
		"code"=>$_GET["code"]
	));

});

$app->post("/forgot/reset", function(){

	$forgot = User::validForgotDecrypt($_POST["code"]);	

	User::setFogotUsed($forgot["idrecovery"]);

	$user = new User();

	$user->get((int)$forgot["iduser"]);

	$password = $_POST["password"];

	$user->setPassword($password);

	$page = new Page();

	$page->setTpl("forgot-reset-success");

});

$app->get("/profile", function() {			#criando Rota para Minha Conta

	User::verifyLogin(false);				#Chamando funcao que Verificando se está logado (área não admin)

	$user = User::getFromSession();			#Chamando funcao que carrega informações do usuário na Sessão

	$page = new Page();						#iniciando nova classe Pagina

	$page->setTpl("profile", [				#Setando o que carregar para Pagina
		'user'=>$user->getvalues(),			#Informações do Usuário
		'profileMsg'=>User::getSuccess(),	#Chamando funcao GET para ler mensagens de Alerta na tela
		'profileError'=>User::getError()	#Chamando funcao GET para ler mensagens de Erro na Tela
	]);

});

$app->post("/profile", function(){			#Recebendo formulário de alteração via metodo POST

	User::verifyLogin(false);				#Verificando se está logado (área não admin)

	if (!isset($_POST['desperson']) || $_POST['desperson'] === '') {	#Validando se informação foi alterada corretamente
		User::setError("Preencha o seu nome.");							#informando erro 	
		header('Location: /profile');									#redirecionando após erro
		exit;
	}

	if (!isset($_POST['desemail']) || $_POST['desemail'] === '') {		#Validando se informação foi alterada corretamente
		User::setError("Preencha o seu e-mail.");						#informando erro 	
		header('Location: /profile');									#redirecionando após erro
		exit;
	}

	$user = User::getFromSession();										#Carregando informações do usuário da Sessão

	if  ($_POST['desemail'] !== $user->getdesemail()) {					#verificando se teve alteração no e-mail
		
		if (User::checkLoginExists($_POST['desemail'])) {				#se tiver alteração então verifique se email já existe no banco 

			User::setError("Este endereço de e-mail já está cadastrado.");	#mensagem erro
			header('Location: /profile');									#redirecionando após erro
			exit;

		}

	}
	$_POST['iduser'] = $user->getiduser();
	$_POST['inadmin'] = $user->getinadmin();								#Sobrecarregando informações que não podem ser alteradas
	$_POST['deslogin'] = $_POST['desemail'];								#S


	$user->setData($_POST);													//Organizando todos os dados para o update

	$user->update();														//Salvando alteração no Banco

	User::setSuccess("Dados alterados com sucesso!");						//Mensagem de Alerta 

	header('Location: /profile');			# redirecionando após salvar
	exit;

});

$app->get("/order/:idorder", function($idorder){

	User::verifyLogin(false);				

	$order = new Order();

	$order->get((int)$idorder);				//recebendo idorder para carregar para o template

	$page = new Page();

	$page->setTpl("payment", [
		'order'=>$order->getValues()
	]);

});

$app->get("/boleto/:idorder", function($idorder){

	User::verifyLogin(false);

	$order = new Order();

	$order->get((int)$idorder);

	// DADOS DO BOLETO PARA O SEU CLIENTE
	$dias_de_prazo_para_pagamento = 10;
	$taxa_boleto = 5.00;
	$data_venc = date("d/m/Y", time() + ($dias_de_prazo_para_pagamento * 86400));  // Prazo de X dias OU informe data: "13/04/2006"; 

	$valor_cobrado = formatPrice($order->getvltotal()); // Valor - REGRA: Sem pontos na milhar e tanto faz com "." ou "," ou com 1 ou 2 ou sem casa decimal
	$valor_cobrado = str_replace(".", "", $valor_cobrado);
	$valor_cobrado = str_replace(",", ".",$valor_cobrado);
	$valor_boleto=number_format($valor_cobrado+$taxa_boleto, 2, ',', '');

	$dadosboleto["nosso_numero"] = $order->getidorder();  // Nosso numero - REGRA: Máximo de 8 caracteres!
	$dadosboleto["numero_documento"] = $order->getidorder();	// Num do pedido ou nosso numero
	$dadosboleto["data_vencimento"] = $data_venc; // Data de Vencimento do Boleto - REGRA: Formato DD/MM/AAAA
	$dadosboleto["data_documento"] = date("d/m/Y"); // Data de emissão do Boleto
	$dadosboleto["data_processamento"] = date("d/m/Y"); // Data de processamento do boleto (opcional)
	$dadosboleto["valor_boleto"] = $valor_boleto; 	// Valor do Boleto - REGRA: Com vírgula e sempre com duas casas depois da virgula

	// DADOS DO SEU CLIENTE
	$dadosboleto["sacado"] = $order->getdesperson();
	$dadosboleto["endereco1"] = $order->getdesaddress() . " " . $order->getdesdistrict();
	$dadosboleto["endereco2"] = $order->getdescity() . " - " . $order->getdesstate() . " - " . $order->getdescountry() . " -  CEP: " . $order->getdeszipcode();

	// INFORMACOES PARA O CLIENTE
	$dadosboleto["demonstrativo1"] = "Pagamento de Compra na Loja Hcode E-commerce";
	$dadosboleto["demonstrativo2"] = "Taxa bancária - R$ 0,00";
	$dadosboleto["demonstrativo3"] = "";
	$dadosboleto["instrucoes1"] = "- Sr. Caixa, cobrar multa de 2% após o vencimento";
	$dadosboleto["instrucoes2"] = "- Receber até 10 dias após o vencimento";
	$dadosboleto["instrucoes3"] = "- Em caso de dúvidas entre em contato conosco: suporte@hcode.com.br";
	$dadosboleto["instrucoes4"] = "&nbsp; Emitido pelo sistema Projeto Loja Hcode E-commerce - www.hcode.com.br";

	// DADOS OPCIONAIS DE ACORDO COM O BANCO OU CLIENTE
	$dadosboleto["quantidade"] = "";
	$dadosboleto["valor_unitario"] = "";
	$dadosboleto["aceite"] = "";		
	$dadosboleto["especie"] = "R$";
	$dadosboleto["especie_doc"] = "";


	// ---------------------- DADOS FIXOS DE CONFIGURAÇÃO DO SEU BOLETO --------------- //


	// DADOS DA SUA CONTA - ITAÚ
	$dadosboleto["agencia"] = "1690"; // Num da agencia, sem digito
	$dadosboleto["conta"] = "48781";	// Num da conta, sem digito
	$dadosboleto["conta_dv"] = "2"; 	// Digito do Num da conta

	// DADOS PERSONALIZADOS - ITAÚ
	$dadosboleto["carteira"] = "175";  // Código da Carteira: pode ser 175, 174, 104, 109, 178, ou 157

	// SEUS DADOS
	$dadosboleto["identificacao"] = "Hcode Treinamentos";
	$dadosboleto["cpf_cnpj"] = "24.700.731/0001-08";
	$dadosboleto["endereco"] = "Rua Ademar Saraiva Leão, 234 - Alvarenga, 09853-120";
	$dadosboleto["cidade_uf"] = "São Bernardo do Campo - SP";
	$dadosboleto["cedente"] = "HCODE TREINAMENTOS LTDA - ME";

	// NÃO ALTERAR!
	$path = $_SERVER['DOCUMENT_ROOT'] . DIRECTORY_SEPARATOR . "res" . DIRECTORY_SEPARATOR . "boletophp" . DIRECTORY_SEPARATOR . "include" . DIRECTORY_SEPARATOR;

	require_once($path . "funcoes_itau.php");
	require_once($path . "layout_itau.php");

});

$app->get("/profile/orders", function(){ 			//CRIANDO ROTA PARA VER PEDIDOS

	User::verifyLogin(false);

	$user = User::getFromSession();

	$page = new Page();

	$page->setTpl("profile-orders", [
		'orders'=>$user->getOrders()
	]);

});

$app->get("/profile/orders/:idorder", function($idorder){

	User::verifyLogin(false);

	$order = new Order();

	$order->get((int)$idorder);

	$cart = new Cart();

	$cart->get((int)$order->getidcart());

	$cart->getCalculateTotal();

	$page = new Page();

	$page->setTpl("profile-orders-detail", [
		'order'=>$order->getValues(),
		'cart'=>$cart->getValues(),
		'products'=>$cart->getProducts()
	]);	

});

$app->get("/profile/change-password", function(){

	User::verifyLogin(false);

	$page = new Page();

	$page->setTpl("profile-change-password", [
		'changePassError'=>User::getError(),
		'changePassSuccess'=>User::getSuccess()
	]);

});

$app->post("/profile/change-password", function(){

	User::verifyLogin(false);
	$user = User::getFromSession();

	if (!isset($_POST['current_pass']) || $_POST['current_pass'] === '') {

		User::setError("Digite a senha atual.");
		header("Location: /profile/change-password");
		exit;

	}

	if (!($_POST['current_pass'] === $user->getdespassword())) {
		
		User::setError("A senha está inválida.");
		header("Location: /profile/change-password");
		exit;			

	}

	if (!isset($_POST['new_pass']) || $_POST['new_pass'] === '') {

		User::setError("Digite a nova senha.");
		header("Location: /profile/change-password");
		exit;

	}

	if (!isset($_POST['new_pass_confirm']) || $_POST['new_pass_confirm'] === '') {

		User::setError("Confirme a nova senha.");
		header("Location: /profile/change-password");
		exit;

	}

	if ($_POST['current_pass'] === $_POST['new_pass']) {

		User::setError("A sua nova senha deve ser diferente da atual.");
		header("Location: /profile/change-password");
		exit;		

	}

	#$user->setdespassword($_POST['new_pass']);

	$user->setPassword($_POST['new_pass']);

	User::setSuccess("Senha alterada com sucesso.");

	header("Location: /profile/change-password");
	exit;

});

?>