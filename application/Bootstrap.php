<?php
/**
 * 所有在Bootstrap类中, 以_init开头的方法, 都会被Ap调用,
 * 这些方法, 都接受一个参数:Yaf_Dispatcher $dispatcher
 * 调用的次序, 和申明的次序相同
 */
class Bootstrap extends \Yaf\Bootstrap_Abstract
{
	protected $config;
	public function _initConfig( \Yaf\Dispatcher $dispatcher )
	{
		$this->config = \Yaf\Application::app()->getConfig();
		\Yaf\Registry::set("config", $this->config);
		$dispatcher->disableView();  //开启后，不自动加载视图
		$dispatcher->setErrorHandler([$this,"myErrorHandler"]);
	}

	public function _initSession($dispatcher) {
		Yaf\Session::getInstance()->start();
	}

	public function _initAuto(\Yaf\Dispatcher $dispatcher)
	{
		$fileload = Yaf\Loader::getInstance();
		$fileload->import(__DIR__."/../vendor/autoload.php");
		// $fileload->setLibraryPath(__DIR__."/../vendor",true);
		// $fileload->autoload('autoload');
	}

	public function _initPlugin(\Yaf\Dispatcher $dispatcher)
	{
		$AutoloadPlugin = new AutoloadPlugin();
		$dispatcher->registerPlugin($AutoloadPlugin);
	}
	
	public function _initDb()
	{
		\think\facade\Db::setConfig([
		    // 默认数据连接标识
		    'default'     => 'mysql',
		    // 数据库连接信息
		    'connections' => [
		        'mysql' => $this->config->tpdatabase->toArray()
		    ],
		]);
	}

	public function _initBase()
	{
		// 系统常量
		defined('DS') or define('DS', DIRECTORY_SEPARATOR);
		defined('APPDEBUG') or define('APPDEBUG', $this->config->application->appdebug);

		// 环境常量
		define('IS_CGI', strpos(PHP_SAPI, 'cgi') === 0 ? 1 : 0);
		define('IS_WIN', strstr(PHP_OS, 'WIN') ? 1 : 0);
		define('IS_MAC', strstr(PHP_OS, 'Darwin') ? 1 : 0);
		define('IS_CLI', PHP_SAPI == 'cli' ? 1 : 0);
		define('IS_AJAX', (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') ? true : false);
		define('NOW_TIME', $_SERVER['REQUEST_TIME']);
		define('REQUEST_METHOD', IS_CLI ? 'GET' : $_SERVER['REQUEST_METHOD']);
		define('IS_GET', REQUEST_METHOD == 'GET' ? true : false);
		define('IS_POST', REQUEST_METHOD == 'POST' ? true : false);
		define('IS_PUT', REQUEST_METHOD == 'PUT' ? true : false);
		define('IS_DELETE', REQUEST_METHOD == 'DELETE' ? true : false);
	}

	public function myErrorHandler($errno, $errstr, $errfile, $errline, $errcontext)
	{
		//可记录日志
		switch ($errno) {
			case YAF\ERR\NOTFOUND\CONTROLLER:
			case YAF\ERR\NOTFOUND\MODULE:
			case YAF\ERR\NOTFOUND\ACTION:
				// header("404 Not Found ");
				echo "<h1>404 Not Found</h1>";
			break;
			default:
				echo "Unknown error type: [$errno]--- $errstr ---$errfile ---- $errline  <br />\n";
			break;
		}
		return true;  //继续执行可执行的代码
	}
}
