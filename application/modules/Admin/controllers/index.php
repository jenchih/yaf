<?php
use \think\Db;
class IndexController extends \Yaf\Controller_Abstract
{
	public function init()
	{
		\Yaf\Registry::set("session_prefix",'admin');
	}

	public function indexAction()
	{
		echo "<h1>这是admin模块</h1>";
	}
}
