<?php 

namespace Hcode\Model;

use \Hcode\DB\Sql;
use \Hcode\Model;

class User extends Model {

	const SESSION = "User";

	public static function login($login, $password) 
	{

		$sql = new Sql();

		$results = $sql->select("SELECT * FROM tb_users WHERE deslogin = :LOGIN", array(":LOGIN"=>$login));
			if (count($results) === 0)
			{
				throw \Exception("Usuário inexistente ou Senha Inválido"); 
			}

			$data = $results[0];

			if (password_verify($password, $data["despassword"]) === true)
			{

				$user = new User();

				$user->setdata($data);

				$_SESSION[User::SESSION] = $user->getValues();

				return $user;

			}else{
				throw \Exception("Usuário inexistente ou Senha Inválido"); 	
			}


	}

	public static function verifyLogin($inadmin = true)
	{

		if (
			!isset($_SESSION[User::SESSION]) #verificar se não foi definida
			||
			!$_SESSION[User::SESSION] #Verificar se esta vazia
			||
			!(int)$_SESSION[User::SESSION]["iduser"] > 0 #Verificar se é maior que zero então é usuário
			||
			(bool)$_SESSION[User::SESSION]["inadmin"] !== $inadmin #Verificar se tem acesso ao setor admin
		   ){

			header("Location: /admin/login");
			exit;
			}

	}

	public static function logout()
	{

		$_SESSION[User::SESSION] = NULL;
	}

}

 ?>