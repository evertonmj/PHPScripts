<?php
/**
* Este script adiciona membros automaticamente ao OptmizeMember sempre que eles efetuarem pagamentos no PagSeguro
* As URL's de notificacao do PagSeguro devem apontar para este script
* Autor: Everton Mendonça / evertonmj@gmail.com
* Novembro de 2014
*/
//header("Content-Type: text/html; charset=ISO-8859-1");
/**
* Parametros de configuracao
*/
define("TOKEN", 'xxxxxxxxxxxxxxxxxxxxxxxxxxxx'); //Token do PagSeguro
define("PASSWORD", '123456'); //Senha Padrão para os Usuarios Criados
define("GATEWAY_PAG", 'pagseguro');
define("API_KEY", 'xxxxxxxxxxxxxxxxxxxxxxxxxxxx'); // Encontre em: `optimizeMember -> API / Scripting -> Remote Operations API -› API Key`

/**
	Esta classe faz a comunicacao com o pagseguro, recebendo as notificacoes enviadas por ele e recebendo os dados
*/
class PagSeguroNpi{	
	private $timeout = 10;	
	public function notificationPost() {
		$postdata = "Comando=validar&Token=".TOKEN;
		foreach ($_POST as $key => $value) {
			$valued    = $this->clearStr($value);
			$postdata .= "&$key=$valued";
		}
		return $this->verify($postdata);
	}	
	private function clearStr($str) {
		if (!get_magic_quotes_gpc()) {
			$str = addslashes($str);
		}
		return $str;
	}	
	private function verify($data) {
		$curl = curl_init();
		curl_setopt($curl, CURLOPT_URL, "https://pagseguro.uol.com.br/pagseguro-ws/checkout/NPI.jhtml");
		curl_setopt($curl, CURLOPT_POST, true);
		curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curl, CURLOPT_HEADER, false);
		curl_setopt($curl, CURLOPT_TIMEOUT, $this->timeout);
		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
		$result = trim(curl_exec($curl));
		curl_close($curl);
		return $result;
	}
}
/**
* Caso existam dados recebidos
*/
if (count($_POST) > 0) {	
	$npi = new PagSeguroNpi();
	$result = $npi->notificationPost();	
	$transacaoID = isset($_POST["TransacaoID"]) ? $_POST["TransacaoID"] : "";	
	$CliNome = $_POST["CliNome"];
	$emailCliente = $_POST["CliEmail"];
	$StatusTra = $_POST["StatusTransacao"];	
	$ProdID = $_POST["ProdID_1"];	
	$ProdValor = $_POST["ProdValor_1"];
	$ProdDescricao_1 = $_POST["ProdDescricao_1"];
	
	$name_split = preg_split('/\s+/', $CliNome);
	
	if(count($name_split) >= 2) {
		$first_name = $name_split[0];
		$last_name = $name_split[1];
	} else {
		$first_name = $CliNome;
		$last_name = "";
	}

	if ($result == "VERIFICADO") {

		$op["op"] = "create_user"; // Operacao feita via OptmizeMember.

		$op["api_key"] = API_KEY;

		$op["data"] = array (
			"user_login" => $emailCliente,
			"user_email" => $emailCliente,

			// Todos os campos abaixo sao opcionais

			"modify_if_login_exists" => "1", // Opcional. Atualiza os dados caso o login ja exista na base de dados
				// 1 - Atualizar/ 0 - Nao Atualizar

			"user_pass" => PASSWORD, // Senha padrao para usuarios criados. Se for deixado em branco sera gerada uma senha automatica

			"first_name" => $first_name, // Primeiro Nome 
			"last_name" => $last_name, // Ultimo nome

			"optimizemember_level" => "1", // Nivel do membro no OptmizeMember. O valor padrao é 0 (Free Subscriber)
			//"optimizemember_ccaps" => "music,videos", // Caracteristicas especificas separadas por virgula

			//"optimizemember_registration_ip" => "123.456.789.100", // IP de registro do usuario. Se for deixado em branco sera preenchido no primeiro login do usuario

			"optimizemember_subscr_gateway" => GATEWAY_PAG, // Descricao do gateway de pagamento: (paypal|alipay|authnet|ccbill|clickbank|google).
			"optimizemember_subscr_id" => $transacaoID, // ID da transacao feita pelo usuario

			"optimizemember_custom" => "javahelp.me", // Opcional. Caso seja informado devera conter o nome do dominio principal.

			"optimizemember_auto_eot_time" => "2030-12-25", // Data de expiracao da conta. Pode ser qualqur valor da funcao PHP ``strtotime()`` (i.e. YYYY-MM-DD).

			//"custom_fields" => array ("meu_campo" => "algum valor qualquer."), // Campos customizados

			"optimizemember_notes" => "Nota administrativa. Usuario criado atraves da API", //Anotacoes genericas

			"opt_in" => "1", // Optional. A non-zero value tells optimizeMember to attempt to process any List Servers you've configured in the Dashboard area.
				// This may result in your mailing list provider sending the User/Member a subscription confirmation email (i.e. ... please confirm your subscription).

			"notification" => "1", // Determina se o usuario criado sera notificado com informacoes de usuario e senha atraves do email informado. Alem disso o administrador do site tambem sera notificado.
				//0 - Nao Notifica | 1 - Notifica
		);

		$post_data = stream_context_create (array ("http" => array ("method" => "POST", "header" => "Content-type: application/x-www-form-urlencoded", "content" => "optimizemember_pro_remote_op=" . urlencode (serialize ($op)))));
		
		//Substitua "meusite.com.br" pela url do seu site
		$result = trim (file_get_contents ("http://meusite.com.br/?optimizemember_pro_remote_op=1", false, $post_data));

		if (!empty ($result) && !preg_match ("/^Erro\:/i", $result) && is_array ($user = @unserialize ($result)))
			echo "Sucesso. Novo usuario criado com sucesso: " . $user["ID"];
		else
			echo "Erro ao chamar a API: " . $result;
	}
}
?>