<?php
use think\Db;
class IndexController extends \Yaf\Controller_Abstract {
	public function init()
	{
		// print_r(get_defined_constants());die;
	}

	public function indexAction()
	{
		$res = Db::table('zs_user')->find();
		dump($res);die;
	}
}
