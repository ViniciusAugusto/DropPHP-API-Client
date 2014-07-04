<?php
/**
 * @author Vinicius Augusto Cunha
 * @email viniciusaugustocunha@gmail.com
 * @telefone (44) 9824-6268
 */

require_once("dropbox/DropboxClient.php");

class DropboxClient {
	
	public $app_key, $app_secret, $app_full_access;
	
	public function __construct($path){
		$this->app_key = "XXXXXXXXXXXXXX";
		$this->app_secret = 'XXXXXXXXXXXXXXXXX';
		$this->app_full_access = false;
		$this->path = $path;
	}
	
	/**
	 * @return DropboxClient
	 */
	public function conectaDropbox(){
		
		//instancia api do dropbox
		$dropbox = new DropboxClient($this->getDados(),'pt-br');
		
		// primeiro tentar carregar token de acesso existente
		$access_token = $this->load_token("access");
		if(!empty($access_token)) {
			$dropbox->SetAccessToken($access_token);
		}else if(!empty($_GET['auth_callback'])){ // estamos vindo de página de autenticação do dropbox?
			//em seguida, coloque o nosso símbolo pedido criado anteriormente
			$request_token = $this->load_token($_GET['oauth_token']);
			if(empty($request_token))
				 die('Token não encontrado!!');
		
			// obter & token de acesso da loja, o token pedido não é mais necessário
			$access_token = $dropbox->GetAccessToken($request_token);
			$this->store_token($access_token, "access");
			$this->delete_token($_GET['oauth_token']);
		}
		
		// Verifica se o token de acesso é necessária
		if(!$dropbox->IsAuthorized())
		{
			// redirecionar usuário para página do dropbox auth
			$return_url = "http://".$_SERVER['HTTP_HOST'].$_SERVER['SCRIPT_NAME']."?auth_callback=1";
			$auth_url = $dropbox->BuildAuthorizeUrl($return_url);
			$request_token = $dropbox->GetRequestToken();
			$this->store_token($request_token, $request_token['t']);
				die("Autenticação exigida. <a href='$auth_url'>Clique aqui.</a>");
		}
		
		return $dropbox;
	}
	
	
	public function listarArquivos(){
		
		//instancia um nova conexão com o dropbox
		$dropbox = $this->conectaDropbox();
		
		
		if(is_array($dropbox->GetFiles($this->path,true))){
			//pega os arquivos da pasta do parametro path da classe
			$files = $dropbox->GetFiles($this->path,true);
		}else{
			throw new Exception('Pasta '.$this->path.' não exite!');
		}
		
		
		//verifica se tem arquivos na pastas
		if(!empty($files)) {
			
			$contador = 0;
			foreach ($files as $file){
				
				$contador++;
				$dados = $dropbox->GetMetadata($file->path);
				
				$links[] = array($contador => $dados);
			}
			
			if(count($links) > 0){
				return $links;
			}else{
				throw new Exception('Pasta '.$this->path.' não tem nenhum arquivo !');
			}
			
		}else{
			throw new Exception('Usúario não possui arquivos !');
		}
	}
	
	/**
	 * @return object
	 */
	public function getDadosDropbox(){
		
		return $this->conectaDropbox()->GetAccountInfo();
	}
	
	/**
	 * @return multitype:string boolean 
	 */
	private function getDados(){
		return array('app_key' => $this->app_key,'app_secret' => $this->app_secret,'app_full_access' => $this->app_full_access);
	}
	
	/**
	 * @param unknown $token
	 * @param unknown $name
	 */
	private function store_token($token, $name){
		if(@!file_put_contents("tokens/$name.token", serialize($token)))
			die('<br />Could not store token! <b>Make sure that the directory `tokens` exists and is writable!</b>');
	}
	
	/**
	 * @param unknown $name
	 * @return NULL
	 */
	private function load_token($name){
		if(!file_exists("tokens/$name.token")) return null;
			return @unserialize(@file_get_contents("tokens/$name.token"));
	}
	
	/**
	 * @param unknown $name
	 */
	private function delete_token($name){
		@unlink("tokens/$name.token");
	}
	
	private function enable_implicit_flush(){
		@apache_setenv('no-gzip', 1);
		@ini_set('zlib.output_compression', 0);
		@ini_set('implicit_flush', 1);
		for ($i = 0; $i < ob_get_level(); $i++) { ob_end_flush(); }
		ob_implicit_flush(1);
		echo "<!-- ".str_repeat(' ', 2000)." -->";
	}
	
	/**
	 * @param unknown $limit
	 */
	public function setTimeLimit($limit){
		set_time_limit($limit);
	}
}
