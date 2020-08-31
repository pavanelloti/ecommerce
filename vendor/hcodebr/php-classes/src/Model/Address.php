<?php 

namespace Hcode\Model;

use \Hcode\DB\Sql;
use \Hcode\Model;

class Address extends Model {

	const SESSION_ERROR = "AddressError";								//CRIANDO UMA SESSAO PARA GUARDAR ERRO

	public static function getCEP($nrcep)								// CRIANDO METODO PARA BUSCAR ENDERECOS AO PASSAR O CPF
	{

		$nrcep = str_replace("-", "", $nrcep);	//RETIRANDO - DO CEP

		$ch = curl_init();						//CURL_INIT = INFORMANDO PHP QUE IREMOS RASTREIAR UMA URL

		curl_setopt($ch, CURLOPT_URL, "http://viacep.com.br/ws/$nrcep/json/");	//COMANDO PARA CHAMADA NO ENDEREÇO URL

		curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);			//OPCAO PARA RETORNAR INF
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);		//OPCAO DE ALTENTICACAO SSL - ESSAS DUAS OPÇOES SÃO OBRIGATÓRIAS

		$data = json_decode(curl_exec($ch), true);	//CRIANDO NOVA VARIAVEL PARA RECEBER RETORNO DE $CH  - COM JSON_DECODE TRUE PARA RECEBER COMO ARRAY E NÃO OBJECTO

		curl_close($ch);						//CURL_CLOSE = FECHANDO O RASTREAMENTO

		return $data;							

	}

	public function loadFromCEP($nrcep)									//CRIANDO METODO PARA ORGANIZAR OS NOMES AO PADRAO DO BANCO DE DADOS
	{

		$data = Address::getCEP($nrcep);	//CARREGANDO METODO COM INFORMAÇOES DO CEP

		if (isset($data['logradouro']) && $data['logradouro']) {	//VALIDANDO SE TEM INFORMACAO NA VARIAVEL DATA 

			$this->setdesaddress($data['logradouro']);				//ORGANIZANDO KEYS COM NOMES DO BANCO
			$this->setdescomplement($data['complemento']);
			$this->setdesdistrict($data['bairro']);
			$this->setdescity($data['localidade']);
			$this->setdesstate($data['uf']);
			$this->setdescountry('Brasil');
			$this->setdeszipcode($nrcep);

		}

	}

	public function save()					//METODO QUE RECEBE AS INFORMACAO DO ENDEREÇO E SALVA NO BANCO VIA PROCEDURE
	{

		$sql = new Sql();

		$results = $sql->select("CALL sp_addresses_save(:idaddress, :idperson, :desaddress, :desnumber, :descomplement, :descity, :desstate, :descountry, :deszipcode, :desdistrict)", [
			':idaddress'=>$this->getidaddress(),
			':idperson'=>$this->getidperson(),
			':desaddress'=>utf8_decode($this->getdesaddress()),
			':desnumber'=>$this->getdesnumber(),
			':descomplement'=>utf8_decode($this->getdescomplement()),
			':descity'=>utf8_decode($this->getdescity()),
			':desstate'=>utf8_decode($this->getdesstate()),
			':descountry'=>utf8_decode($this->getdescountry()),
			':deszipcode'=>$this->getdeszipcode(),
			':desdistrict'=>$this->getdesdistrict()
		]);

		if (count($results) > 0) {
			$this->setData($results[0]);
		}

	}

	public static function setMsgError($msg)
	{

		$_SESSION[Address::SESSION_ERROR] = $msg;

	}

	public static function getMsgError()
	{

		$msg = (isset($_SESSION[Address::SESSION_ERROR])) ? $_SESSION[Address::SESSION_ERROR] : "";

		Address::clearMsgError();

		return $msg;

	}

	public static function clearMsgError()
	{

		$_SESSION[Address::SESSION_ERROR] = NULL;

	}

}

 ?>