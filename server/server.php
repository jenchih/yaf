<?php
class HttpServer
{
	public static $instance;
	public $http_server;
	private $response;
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
			$http_server = new swoole_http_server("0.0.0.0", $this->_port,SWOOLE_PROCESS);
		}
		$http_server->set([
			'worker_num'       => 8,
			'task_worker_num'  => 16,
			'daemonize'        => true, //测试调试时，可关闭，来在客户端显示错误信息
			'max_request'      => 5000,
			"task_max_request" => 10,
		]);
		define('APPLICATION_PATH', dirname(dirname(__DIR__)). "/yaf/application");
		$this->application = new \Yaf\Application(APPLICATION_PATH."/../conf/application.ini");
		$this->application->bootstrap();
		$http_server->on('Request',array($this , 'onRequest'));
		$http_server->on('Task',array($this , 'onTask'));
		$http_server->on('Finish',array($this , 'onFinish'));
		register_shutdown_function([$this, "shutdown"]);
		$http_server->start();
	}

	public function onRequest($request, $response)
	{
		$this->response = $response;
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

		if( $server['request_uri'] == '/favicon.ico' ){
			//过滤浏览器请求
			header("Content-type: image/x-icon");
			$ico = base64_decode($this->getIco());
			$response->end($ico);die;
		}
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

	public function onTask()
	{

	}

	public function onFinish()
	{

	}

	public function shutdown()
	{
		ob_implicit_flush(0);
		$data = ob_get_clean();
		if( empty($data) ) $data = "<h1>500</h1>";
		$this->response->end($data);
	}

	public static function getInstance() {
		if (!self::$instance) {
			self::$instance = new HttpServer;
		}
		return self::$instance;
	}

	public function getIco()
	{
		return 'AAABAAEAMDAAAAEAIACoJQAAFgAAACgAAAAwAAAAYAAAAAEAIAAAAAAAACQAAAAAAAAAAAAAAAAAAAAAAAD////////////////+/v7///////////////////////////////////////7+/v/+/v7/vb29/wwMDP9VVVX/9/f3//7+/v/+/v7//v7+//////////////////7+/v/+/v7//f39/3V1df8GBgb/q6ur//7+/v/+/v7//v7+///////+/v7////////////+/v7////////////+/v7////////////+/v7//v7+///////+/v7//v7+/////////////v7+///////+/v7//v7+//7+/v///////v7+//7+/v///////v7+///////+/v7/7e3t/zMzM/8WFhb/1tbW//7+/v/+/v7//v7+//7+/v/+/v7//v7+//z8/P/7+/v/6enp/yYmJv8XFxf/3d3d//v7+//+/v7//v7+//7+/v/+/v7//v7+//7+/v/+/v7//v7+//7+/v/////////////////////////////////+/v7//v7+//7+/v///////////////////////v7+/////////////v7+//7+/v/+/v7//v7+//7+/v/+/v7//v7+/4GBgf8CAgL/hYWF//X19f/t7e3/29vb/7+/v/+hoaH/g4OD/3Jycv9fX1//ODg4/wAAAP8UFBT/WVlZ/2dnZ/+Li4v/srKy/9ra2v/x8fH//Pz8//7+/v/+/v7//v7+//7+/v/+/v7//v7+//////////////////7+/v///////////////////////v7+/////////////////////////////v7+//7+/v/+/v7//v7+//7+/v/+/v7//Pz8/8bGxv8VFRX/Ghoa/0pKSv8pKSn/FRUV/wMDA/8BAQH/BwcH/wwMDP8RERH/FhYW/xcXF/8WFhb/ExMT/w4ODv8EBAT/AQEB/xMTE/85OTn/eHh4/729vf/z8/P//v7+//7+/v///////v7+/////////////v7+///////////////////////////////////////+/v7///////7+/v///////v7+//7+/v/+/v7//f39/+7u7v+/v7//dnZ2/y0tLf8EBAT/BAQE/w4ODv8iIiL/RERE/2xsbP+Ojo7/rq6u/8HBwf/Pz8//2NjY/9vb2//Z2dn/09PT/8bGxv+hoaH/eHh4/0NDQ/8dHR3/BwcH/woKCv9OTk7/zc3N//39/f///////////////////////////////////////////////////////v7+//7+/v///////v7+//7+/v/+/v7///////7+/v/p6en/mJiY/z4+Pv8KCgr/BAQE/yIiIv9eXl7/mJiY/8LCwv/i4uL/+fn5//7+/v/+/v7//v7+//7+/v/+/v7//v7+//7+/v/+/v7//v3+//z8/v/8/P7//v7+//b29v/a2tr/paWk/01NTf8HBwf/IiIi/6enp//4+Pj///////7+/v/+/v7//v7+//7+/v///////v7+/////////////////////////////v7+//7+/v/+/v7//f39/8nJyf88PDz/BQUF/xAQEP9WVlb/pKSk/+Dg4P/6+vr///////////////////////////////////////3+/v/+/v3//v3+//39/v/y8Pv/2ND6/8a59v/FuPf/2tL5//Tz/P/+/f7//v7+//Hx8v+NjY3/ExMT/wwMDP+SkpL/+vr6//7+/v/+/v7//v7+/////////////////////////////v7+///////+/v7//f39//7+/v/6+vr/oaGh/x4eHv8GBgb/Tk5O/7y8vP/39/f//v7+//7+/v/+/v7//v7+///////////////////////+/v7///////7+/v/+/v3//v7+/+vp+v+fifP/Yz7r/08n6f9OJun/ZkTs/7Gf9v/29P3//v7+//7+/v/6+vr/qqqq/xcXF/8SEhL/u7u7//39/f/+/v7//v7+//////////////////7+/v////////////7+/v/+/v7//v7+//v7+/+Xl5f/Dg4O/xYWFv+VlZX/8vLy//7+/v/+/v7//v7+//7+/v/+/v7///////7+/v/+/v7///////7+/v/////////////+/v/+/v7//v3+/9DJ+f9cNev/Ngfm/zIG5f80BuX/PBDn/4hs8f/t6vz//v3+//7+/v/+/v7/+/v7/42Njf8GBgb/RkZG//Ly8v/+/v7///////7+/v/+/v7//////////////////v7+///////+/v7//v7+/8HBwf8RERH/Ghoa/7W1tf/8/Pz//v7+//7+/v/+/v7//v7+//7+/v/+/v7//v7+///////+/v7//v7+///////+/v7///////7+/f/9/v7//v79/+vl/P+Zg/L/Yj7q/1Qt6/9dNez/gmPu/8q++f/5+P3//v7+//7+/v/+/v7//v7+//Dw8P9CQkL/CAgI/7W1tf/+/v7//v7+///////+/v7////////////+/v7//v7+///////+/v7/8vLy/0VFRf8JCQn/oqKi//39/f/+/v7//v7+//7+/v/+/v7///////7+/v/////////////////+/v7//v7+//7+/v/+/v7//v7+//7+/v/9/P7//v79//38/f/z7/3/3tb6/9LI+P/a0Pr/6+j7//v7/f/+/v3//v7+//7+/v/+/v7///////7+/v+0tLT/CwsL/1JSUv/39/f//v7+/////////////v7+//7+/v/////////////////+/v7/srKy/wMDA/9KSkr/9vb2//7+/v/+/v7//v7+//7+/v///////////////////////////////////////v7+//7+/v/+/v7//v7+/9XV1f90c3T/Tk9O/2ZnZf/ExMT//f39//39/f/8/f3//v7+//7+/v/+/v3//v7+//7+/v/////////////////u7u7/MDAw/xMTE//Y2Nj//v7+///////////////////////////////////////4+Pj/T09P/woKCv+6urr//v7+//7+/v/+/v7//v7+//7+/v///////////////////////v7+/////////////v7+//39/f/9/f3/ubm5/w4ODv8AAAD/AAAA/wAAAf8HBwf/f4B///v5+//+/v3//v7+//79/v/+/v7//v7+//7+/v/+/v7////////////+/v7/ZGRk/wEBAf+np6f//v7+///////+/v7//v7+///////+/v7//v7+//7+/v/n5+b/Ghoa/zAwMf/w8PH/9PL9/+vn+//v7P3//Pv+//7+/v/+/v7//v7+//7+/v///////v7+//7+/v/+/v7//v7+//7+/v/39/f/Li4u/wAAAP8AAAD/CQkJ/wMDA/8AAAD/BgcG/5eYmf/9/v3//v7+//7//f/+/v7///7////+//////////////7+/v//////qqqq/wcHB/95eXn//Pz8///////+/v7//v7+//7+/v/+/v7//v7+//7+/v+0tLT/AgMD/19dYP/Rxvr/inDv/21J7f9+X+7/0sn4//39/v/+/v7//Pz8//r6+v/+/v7//v7+//7+/v/+/v7//v7+//7+/v/Ozs7/BwcH/wAAAP81NTX/xMTE/3d3d/8BAQH/BQUF/yIiIv/f39///v7+//////////////////7+/v/////////////////+/v7/zs7O/xEREf9OTk7/9/f3//7+/v/////////////////+/v7//v7+//7+/v+Kior/AwMD/25ojf9nQ+//Ngrm/zEG5P9AFuf/rpz3//n5/P/Pz8//dXV1/1dXV/+JiYn/2tra//z8/P/+/v7//v7+//7+/v+/v7//BQUF/wAAAP+UlJT//v7+/97e3v9UVFT/ra2t/4CAgP+NjY3//v7+///////////////////////////////////////+/v7/4uLi/x0dHf81NTX/8vLy///////////////////////+/v7//v7+//39/f95eXn/CAYI/4yDrv9mQO//SRzp/1Us6/+Lb/D/4dv5/7S0tP8dHR3/AAAA/wAAAP8AAAD/IyMj/6qqqv/7+/v//v7+//7+/v/Nzc3/BwcH/wAAAP86Ojr/xsbG/6Kiov/t7e3//v7+//v7+/+vr6//7Ozs///////+/v7////////////////////////////+/v7/6+vr/ycnJ/8sLCz/8PDw///////////////////////+/v7//v7+//39/f95eXn/CAgI/7GwtP/Vzfj/v6/3/8/D+v/t6/z/5ubn/zIxMv8AAAD/AgIC/xQUFP8ICAj/AAAA/xEREf+vr6///f39//7+/v/v7+//Gxsb/wAAAP8AAAD/DAwM/2BgYP/+/v7//v7+//7+/v/Ly8v/zc3N/////////////v7+//7+/v///////v7+///////+/v7/7Ozs/ycnJ/8sLCz/8PDw//7+/v///////v7+///////+/v7//v7+//z8/P95eXn/CAgI/7Sztf/9/f7//P39//39/P/+/f3/wMDB/woKCv8AAAD/R0dH/87Ozv+UlJT/DAwM/wAAAP8kJCT/3d3d//7+/v/+/v7/YGBg/wAAAP8AAAD/AAAA/zs7O//39/f//v7+//39/f+ampr/r6+v//7+/v/+/v7//v7+///////////////////////+/v7/5+fn/yIiIv8wMDD/8fHx/////////////////////////////v7+//39/f+AgID/BgYG/6iop//+/v3//v7+//7+/f/+/v7/tra1/wYGB/8AAAD/cnJy//v7+//m5ub/IiIi/wAAAP8AAAD/b29v//r6+v/+/v7/xcXF/wgICP8AAAD/AAAA/wYGBv9wcHD/zMzM/6ampv8lJSX/q6ur//7+/v/+/v7//v7+//7+/v/+/v7////////////+/v7/2dnZ/xYWFv9CQkL/9fX1///////+/v7////////////+/v7//v7+//7+/v+bm5v/AgMD/4qKiv/+/v7//v7+//7+/v/+/v7/0tPR/xQTFP8BAQH/Ozs7/7a2tv9wcHD/BgYG/wAAAP8AAAD/FxcX/+Dg4P/+/v7/+/v7/1tbW/8AAAD/AAAA/wAAAP8CAgL/EBAQ/wgICP8LCwv/u7u7//7+/v/+/v7//v7+//7+/v/////////////////+/v7/xsbG/w4ODv9hYWH/+vr6//7+/v////////////7+/v/+/v7//v7+//7+/v/Dw8P/BAQF/1dXV//8/fz//v7+//7+/v/+/v7/8/Pz/0VGRv9FRUX/4eHh//X19f/U1NT/ODg4/wAAAP8BAQH/AAAA/6ampv/+/v7//v7+/+bm5v8vLy//AAAA/wAAAP8AAAD/AAAA/wAAAP8UFBT/mpqa/4ODg/+tra3/9/f3//7+/v/+/v7////////////+/v7/oaGh/wUFBf+SkpL//v7+///////+/v7////////////+/v7//v7+//7+/v/s7Oz/JSUl/yEhIf/l5eX//v7+//7+/v/+/v7//v7+/6Wlpf+RkZH//v7+//7+/v/+/v7/p6en/wcHB/8AAAD/AAAA/319ff/9/f3//v7+//7+/v/e3t7/SUlJ/wICAv8AAAD/AAAA/wAAAP8CAgL/BgYG/wEBAf8ODg7/vLy8//7+/v/+/v7//v7+//7+/v/+/v7/bW1t/wsLC//Kysr//v7+///////+/v7//v7+//7+/v/+/v7//v7+//7+/v/8/Pz/bm5u/wUFBf+Wlpb//v7+//7+/v/+/v7//v7+//Dw8P+kpKT/9/f3//7+/v/+/v7/rq6u/wgICP8AAAD/AAAA/319ff/9/f3//v7+//7+/v/+/v7/9/f3/7y8vP9ycnL/VFRU/w8PD/8BAQH/Ojo6/6Wlpf+goKD/8PDw//7+/v/+/v7////////////n5+f/KCgo/zo6Ov/x8fH///////7+/v/+/v7////////////+/v7//v7+///////+/v7/0tLS/xEREf8hISH/29vb//7+/v/+/v7//v7+//7+/v/b29v/mJiY/9XV1f/Dw8P/MTEx/wAAAP8AAAD/AQEB/6qqqv/+/v7//v7+//7+/v/+/v7//v7+//7+/v/9/f3/nZ2d/wMDA/88PDz/6+vr//7+/v/+/v7//v7+//7+/v/+/v7//v7+//7+/v+Ghob/AwMD/5KSkv/9/f3//v7+///////+/v7//v7+///////+/v7////////////+/v7/+/v7/4ODg/8CAgL/SEhI/+zs7P/+/v7//v7+//7+/v/+/v7/zMzM/zs7O/8FBQX/AAAA/wEBAf8AAAD/Kysr/+rq6v/+/v7//v7+//7+/v/+/v7//v7+//7+/v/q6ur/Jycn/xUVFf/Nzc3///////////////////////7+/v/+/v7//v7+/9fX1/8gICD/HR0d/93d3f/+/v7//v7+//////////////////////////////////7+/v/+/v7//v7+//Dw8P9SUlL/BAQE/2BgYP/y8vL//v7+//7+/v/+/v7//v7+/+rq6v94eHj/Ghoa/wAAAP8AAAD/bGxs/8HBwf/t7e3//v7+//7+/v/+/v7//v7+//7+/v+pqan/AgIC/2FhYf/8/Pz//v7+///////+/v7///////7+/v/+/v7/8PDw/09PT/8DAwP/iIiI//z8/P/+/v7//v7+//7+/v/////////////////+/v7//v7+///////+/v7//v7+//7+/v/h4eH/Nzc3/wYGBv9sbGz/7e3t//7+/v/+/v7//v7+//z8/P/CwsL/Ozs7/wAAAP8BAQH/AQEB/wUFBf81NTX/ysrK//39/f/+/v7//v7+//j4+P9MTEz/DAwM/7u7u//+/v7//v7+//////////////////7+/v/6+vr/gYGB/wUFBf9NTU3/7e3t//7+/v/+/v7//v7+/////////////////////////////v7+///////+/v7//v7+//7+/v/+/v7/09PT/zY2Nv8EBAT/WFhY/+Hh4f/+/v7//v7+/9XV1f8eHh7/CQkJ/zk5Of95eXn/iIiI/1paWv8HBwf/NTU1//T09P/+/v7//v7+/97e3v8ODg7/MzMz//T09P/+/v7//v7+//7+/v/+/v7//v7+//z8/P+ioqL/Dg4O/ygoKP/V1dX//v7+//7+/v/+/v7//v7+//////////////////////////////////7+/v///////v7+/////////////v7+/9zc3P9MTEz/BAQE/zo6Ov+/v7//+vr6/+fn5/9tbW3/o6Oj//Dw8P/+/v7//v7+//j4+P95eXn/XV1d//b29v/+/v7//f39/+jo6P9ERET/lpaW//7+/v/+/v7//v7+//7+/v/+/v7//Pz8/7m5uf8aGhr/FxcX/7W1tf/+/v7//v7+/////////////v7+/////////////////////////////////////////////v7+/////////////v7+//7+/v/n5+f/aWlp/wkJCf8VFRX/c3Nz/9zc3P/7+/v//f39//7+/v/+/v7//v7+//7+/v/6+vr/9vb2//7+/v/+/v7//v7+//7+/v/y8vL/+vr6//7+/v/+/v7//v7+//7+/v/7+/v/r6+v/yAgIP8NDQ3/mpqa//v7+//+/v7//////////////////////////////////////////////////////////////////v7+//7+/v///////v7+//7+/v/+/v7/9fX1/6SkpP8uLi7/BAQE/yUlJf+AgID/19fX//z8/P/+/v7//v7+//7+/v/+/v7//v7+//7+/v/+/v7//v7+//7+/v/+/v7//v7+//7+/v/+/v7//v7+//n5+f+NjY3/FRUV/xISEv+UlJT/+fn5//7+/v/+/v7////////////////////////////+/v7////////////+/v7//v7+//7+/v///////v7+/////////////v7+//7+/v/+/v7//v7+//39/f/j4+P/gYGB/x0dHf8DAwP/Ghoa/19fX/+vr6//4+Pj//n5+f/+/v7//v7+//7+/v/+/v7//v7+//7+/v/+/v7/+Pj4/+Pj4//j4+P//v7+//Pz8/9FRUX/AAAA/1BQUP/y8vL//v7+//7+/v/+/v7////////////+/v7///////7+/v////////////7+/v///////v7+//////////////////////////////////////////////////7+/v/+/v7//Pz8/97e3v+IiIj/Li4u/wICAv8HBwf/Ghoa/0FBQf9kZGT/w8PD//7+/v/r6+v/b29v/29vb/9cXFz/PDw8/xsbG/8oKCj/0NDQ//7+/v/Kysr/Kysr/wgICP97e3v/9vb2//7+/v/+/v7////////////+/v7////////////+/v7////////////+/v7//v7+//7+/v///////v7+//7+/v/////////////////+/v7////////////+/v7//v7+//7+/v/9/f3/7e3t/7m5uf9sbGz/MjIy/w8PD/8AAAD/XFxc//39/f/CwsL/AwMD/wEBAf8EBAT/FRUV/yMjI/8CAgL/bm5u//39/f/9/f3/z8/P/ywsLP8GBgb/iIiI//r6+v/+/v7//v7+///////////////////////+/v7///////7+/v/////////////////////////////////////////////////////////////////+/v7//v7+//7+/v/+/v7//v7+//7+/v/9/f3/+Pj4/7W1tf8HBwf/YmJi//z8/P/39/f/Ojo6/wUFBf+dnZ3/6urq/97e3v8gICD/HBwc/9jY2P/+/v7//v7+/83Nzf8gICD/DAwM/6enp//+/v7////////////////////////////////////////////////////////////+/v7////////////////////////////+/v7////////////+/v7//v7+///////+/v7////////////+/v7//v7+/83Nzf8JCQn/VFRU//39/f/+/v7/srKy/wICAv9VVVX//Pz8//39/f+Li4v/AQEB/2ZmZv/7+/v//v7+//7+/v+5ubn/ExMT/xYWFv/Nzc3//v7+//7+/v/+/v7//v7+///////+/v7//v7+/////////////v7+//7+/v///////v7+//7+/v///////////////////////////////////////////////////////////////////////v7+/9ra2v8PDw//QEBA//z8/P/+/v7/8/Pz/ycnJ/8ZGRn/5eXl//7+/v/o6Oj/Ly8v/w8PD/+4uLj//v7+//7+/v/+/v7/lJSU/wYGBv9hYWH/+Pj4//7+/v/+/v7///////7+/v////////////7+/v///////v7+/////////////////////////////////////////////v7+///////////////////////////////////////+/v7//v7+//Ly8v8jIyP/HR0d/+/v7//+/v7//Pz8/2dnZ/8ICAj/uLi4//7+/v/+/v7/rKys/woKCv85OTn/6Ojo//7+/v/+/v7/6urq/zAwMP8ZGRn/2dnZ//7+/v/+/v7////////////////////////////+/v7//v7+//////////////////////////////////////////////////////////////////////////////////////////////////7+/v9VVVX/AwMD/8TExP/+/v7//v7+/6SkpP8BAQH/f39/////////////9vb2/2BgYP8FBQX/cHBw//X19f/+/v7//v7+/2xsbP8EBAT/tLS0//7+/v/+/v7//v7+/////////////////////////////v7+//7+/v////////////7+/v////////////////////////////7+/v///////v7+//7+/v///////////////////////v7+//7+/v+ioqL/AwMD/2BgYP/9/f3//v7+/8zMzP8CAgL/Wlpa//7+/v/+/v7//v7+/97e3v80NDT/BwcH/3x8fP/09PT//v7+/3V1df8CAgL/rq6u//7+/v/+/v7//v7+///////+/v7///////7+/v///////////////////////v7+/////////////////////////////v7+//7+/v///////v7+//7+/v/////////////////+/v7//v7+//7+/v/v7+//LS0t/wgICP/CwsL//v7+/+Li4v8TExP/ODg4//v7+//+/v7//v7+//7+/v/Nzc3/LCws/wcHB/9iYmL/qqqq/yoqKv8RERH/zc3N//7+/v/+/v7//v7+/////////////v7+/////////////////////////////v7+/////////////v7+/////////////////////////////////////////////////////////////v7+//7+/v/+/v7/ra2t/wYGBv8yMjL/6Ojo//Dw8P8kJCT/Jycn//Pz8//+/v7//v7+//7+/v/9/f3/zc3N/z09Pf8EBAT/BQUF/wcHB/95eXn/9/f3//7+/v/+/v7//v7+/////////////////////////////v7+//7+/v///////////////////////////////////////v7+///////+/v7//////////////////////////////////v7+//7+/v/+/v7/+vr6/2ZmZv8BAQH/S0tL/8nJyf8aGhr/LS0t//b29v/+/v7//v7+//7+/v/+/v7//v7+/+jo6P+cnJz/d3d3/6mpqf/19fX//v7+//39/f/+/v7////////////////////////////+/v7//////////////////////////////////v7+//////////////////7+/v////////////////////////////7+/v/+/v7///////7+/v/+/v7//v7+/+/v7/9NTU3/AQEB/xQUFP8DAwP/ZWVl//7+/v/+/v7///////7+/v/+/v7//v7+//7+/v/+/v7//v7+//7+/v/+/v7//v7+//7+/v/+/v7///////7+/v/+/v7///////7+/v///////v7+/////////////v7+///////////////////////////////////////////////////////+/v7//v7+///////+/v7//v7+///////+/v7///////7+/v/v7+//iIiI/y0tLf88PDz/2NjY//7+/v/+/v7//v7+///////+/v7////////////+/v7////////////+/v7////////////+/v7////////////+/v7////////////+/v7//v7+//7+/v///////v7+/////////////v7+/////////////v7+//7+/v/+/v7///////7+/v///////v7+///////+/v7//v7+//////////////////7+/v/+/v7//f39//Ly8v/09PT//v7+//7+/v/+/v7//v7+//7+/v/+/v7///////7+/v/+/v7///////7+/v/////////////////+/v7///////7+/v/+/v7///////7+/v////////////////////////////7+/v////////////////////////////////////////////7+/v/+/v7//v7+//7+/v/+/v7///////7+/v/+/v7///////7+/v/+/v7//v7+//7+/v/+/v7//v7+//7+/v/+/v7//v7+//7+/v/+/v7//v7+/////////////v7+//7+/v/+/v7//v7+//7+/v/+/v7//v7+/////////////v7+//7+/v8AAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAAA=';
	}
}
HttpServer::getInstance();
