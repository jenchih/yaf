<?php
use \think\Db;
class IndexController extends \Yaf\Controller_Abstract
{
	public function init()
	{
		\Yaf\Dispatcher::getInstance()->autoRender(false);
	}

	public function indexAction()
	{
		echo "<h1>这是admin模块</h1>";
	}
}
