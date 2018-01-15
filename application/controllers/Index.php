<?php
use \think\Db;
class IndexController extends \Yaf\Controller_Abstract
{
	public function init()
	{
		// print_r(get_defined_constants());die;
	}

	public function indexAction()
	{
		// $data = UserModel::get(['uid'=>64])->toArray();
		$data = Db::table('zs_user')->find(52);
		$this->assign("foo", $data['username']);
		$this->display('index');
	}

	public function testviewAction()
	{
		echo 2222;
		//默认关闭渲染视图，可以设置自动，或者，若开启，又不想渲染视图输出，则return false
		return false;
	}

	private function assign( $key ,$value )
	{
		$this->getView()->assign($key, $value);
	}

	/**
	 * @date   2018-01-04
	 * @author rzliao
	 * @param  [type]     $template 
	 */
	private function displayAbsolute( $template )
	{
		//$template /index/index.html
		$this->getView()->display($template);
	}
}
