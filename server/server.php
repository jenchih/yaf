<?php
class HttpServer
{
	public static $instance;
	public $http;
	private $application;
	private $_port = 9501;
	private $_isHTTPS = false;
	public function __construct()
	{
		if( $this->_isHTTPS ){
			$serv = new swoole_http_server("0.0.0.0", $this->_port, SWOOLE_PROCESS, SWOOLE_SOCK_TCP | SWOOLE_SSL);
			$serv->set([
				'ssl_cert_file' => 'dir/ssl.crt',
				'ssl_key_file' => 'dir/ssl.key',
				'open_http2_protocol' => true,
			]);
		}else{
			$http = new swoole_http_server("0.0.0.0", $this->_port);
		}
		$http->set([
			'worker_num'    => 16,
			'daemonize'     => true, //测试调试时，可关闭，来在客户端显示错误信息
			'max_request'   => 10000,
			'dispatch_mode' => 1
		]);
		define('APPLICATION_PATH', dirname(dirname(__DIR__)). "/yaf/application");
		$this->application = new \Yaf\Application(APPLICATION_PATH."/../conf/application.ini");
		$this->application->bootstrap();
		$http->on('Request',array($this , 'onRequest'));
		$http->start();
	}

	public function onRequest($request, $response)
	{
		$response->status('200');
		$server  = $request->server;
		$header  = $request->header;

		if( isset( $request->get ) ) $_GET = $request->get;
		if( isset( $request->post ) ) $_POST = $request->post;
		if( isset( $request->cookie ) ) $_COOKIE = $request->cookie;
			
		// $_FILES  = $request->files;
		if( isset($_COOKIE['PHPSESSID']) ){
			session_id($_COOKIE['PHPSESSID']);
			session_reset();
		}

		if( $server['request_uri'] == '/favicon.ico' ) exit();  //过滤浏览器请求
		$header['host'] = str_replace(':'.$this->_port, '', $header['host']);//如果端口号是80，就不用要此句代码

		ob_start();
		try {
			$yaf_request = new \Yaf\Request\Http($server['request_uri']);
			$yaf_request->setBaseUri($header['host']);
			$this->application->getDispatcher()->dispatch($yaf_request);
		} catch ( Yaf_Exception $e ) {

		}
		$result = ob_get_contents();//捕获运行中输出的数据
		ob_end_clean();
		$response->end($result);
	}

	public static function getInstance() {
		if (!self::$instance) {
			self::$instance = new HttpServer;
		}
		return self::$instance;
	}
}
HttpServer::getInstance();
