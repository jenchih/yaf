<?php
use \swoole_websocket_server;
define ("APPLICATION_PATH", __DIR__ . "/../application");
class Websocketserver
{
	public static $instance;
	private $server;
	private $application;
	private $logger;
	private $_code     = 400;
	private $_message  = 'fail';
	private $_data     = [];
	private $_fd       = [];

	public function __construct() {
		// $this->logger = new Log;
		$this->application = new \Yaf\Application(APPLICATION_PATH."/../conf/application.ini");
		$this->application->bootstrap();
		$config = \Yaf\Registry::get("config")->websocketserver;
		$host = $config->host;
		$port = $config->port;
		register_shutdown_function([$this, "shutdown"]);


		if( $config->ssl_key_file ) 
		{
			$this->server = new swoole_websocket_server($host, $port, SWOOLE_PROCESS, SWOOLE_SOCK_TCP | SWOOLE_SSL);
			$this->server->set(array(
				'ssl_cert_file' => $config->ssl_cert_file,
				'ssl_key_file' => $config->ssl_key_file,
			));
		}
		else
		{
			$this->server = new swoole_websocket_server($host, $port);
		}


		$this->server->on('open', [$this, 'OnOpen']);
		$this->server->on('message', [$this, 'OnMessage']);
		$this->server->on('close', [$this, 'OnClose']);
		$this->server->on('request', [$this, 'OnRequest']);

		$this->server->start();
	}

	public function OnOpen(swoole_websocket_server $server, $request)
	{
		if( isset( $request->get ) ) $_GET = $request->get;
		if( isset( $request->post ) ) $_POST = $request->post;
		if( isset( $request->cookie ) ) $_COOKIE = $request->cookie;

		$header         = $request->header;
		$request_server = $request->server;

		ob_start();
		session_id($_COOKIE['PHPSESSID']);
		session_reset();
	}

	public function OnMessage(swoole_websocket_server $server, $frame)
	{
		// echo "receive from {$frame->fd}:{$frame->data},opcode:{$frame->opcode},fin:{$frame->finish}\n";
		// $server->push($frame->fd, "this is server");
		$data      = json_decode($frame->data, true);
		$this->_fd = $frame->fd;
		// $this->logger->log( 'swocketserver#OnMessage', json_encode($data), 0 , 1668,'', 2);
		try {
			if( $data != null || !isset($data['c']) || !isset($data['f']) )
			{
				$module     = isset($data['m']) ? $data['m'] : 'boss';
				$class      = $data['c'];
				$func       = $data['f'];
				$sendArgs   = $data['args'];

				$yaf_request = new \Yaf\Request\Http();
				$yaf_request->setControllerName($class);
				$yaf_request->setActionName($func);
				$yaf_request->setParam( '_server', $this->server );
				$yaf_request->setParam( '_fd', $frame->fd );
				foreach( $sendArgs as $key => $value ){
					$yaf_request->setParam( $key, $value );
				}
	
				$this->application->getDispatcher()->dispatch($yaf_request);

			}
			else
			{
				$this->_message = 'error params';
				$this->returnData();
			}
		} catch (\Throwable $e){
			$err = 'Error Message: '.$e->getMessage().' IN ('.$e->getFile().'{'.$e->getLine().'})';
			$this->_code    = 500;
			$this->_message = $err;
			$this->returnData();
		}
	}

	public function OnClose(swoole_websocket_server $server, $fd)
	{
		// echo "client {$fd} closed\n";
	}

	public function OnRequest( $request, $response )
	{
		$view = new \Yaf\View\Simple(APPLICATION_PATH.'/views');
		$html = $view->render('error/404.html');
		$response->status(404);
		$response->end($html);
		// 接收http请求从get获取message参数的值，给用户推送
		// $this->server->connections 遍历所有websocket连接用户的fd，给所有用户推送
		// foreach ($this->server->connections as $fd) {
		// 	$this->server->push($fd, $request->get['message']);
		// }
	}

	public function returnData()
	{
		$data = [
			'code'    => $this->_code, 
			'message' => $this->_message, 
			'data'    => $this->_data, 
		];
		$data = json_encode($data);
		$this->server->push( $this->_fd, $data );
	}

	public function shutdown()
	{
		ob_implicit_flush(0);
		$data = ob_get_clean();
		if( empty($data) ) $data = json_encode(['code' => 400, 'message'=>'close connection']);
		$this->server->push( $this->_fd, $data );
		$this->server->close( $this->_fd );
	}

	public static function getInstance() {
		if (!self::$instance) {
			self::$instance = new Websocketserver;
		}
		return self::$instance;
	}
}

Websocketserver::getInstance();
