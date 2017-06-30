<?php
class IndexController extends \Yaf\Controller_Abstract {
	public function init()
	{
		// print_r(get_defined_constants());die;
	}

	public function indexAction()
	{
		$db  = Db();
		$res = $db->get('zs_login',10);
		echo json_encode($res);
	}
}
