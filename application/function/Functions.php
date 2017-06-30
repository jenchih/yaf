<?php

function Db()
{
	return Db\MysqliDb::getInstance();
}

/**
 * @desc   格式化输出
 * @author rzliao
 * @date   2015-11-28
 */
function dump($var, $echo = true, $label = null)
{
    $label = (null === $label) ? '' : rtrim($label) . ':';
    ob_start();
    var_dump($var);
    $output = ob_get_clean();
    $output = preg_replace('/\]\=\>\n(\s+)/m', '] => ', $output);
    if (IS_CLI) {
        $output = PHP_EOL . $label . $output . PHP_EOL;
    } else {
    	header('content-type:text/html;charset=utf-8');
    	echo "<pre style='font-size:18px;'>";
        if (!extension_loaded('xdebug')) {
            $output = htmlspecialchars($output, ENT_QUOTES);
        }
        $output = '<pre>' . $label . $output . '</pre>';
    }
    if ($echo) {
        echo ($output);
        return null;
    } else {
        return $output;
    }
}

/**
 * @desc   根据时间戳返回过去时间多久的描述
 * @author rzliao
 * @date   2015-11-28
 * @param  int  $time 时间戳
 */
function from_time($time){
	$way = time() - $time;
		$r = '';
	if($way < 60){
		$r = '刚刚';
	}elseif($way >= 60 && $way <3600){
		$r = floor($way/60).'分钟前';
	}elseif($way >=3600 && $way <86400){
		$r = floor($way/3600).'小时前';
	}elseif($way >=86400 && $way <2592000){
		$r = floor($way/86400).'天前';
	}elseif($way >=2592000 && $way <15552000){
		$r = floor($way/2592000).'个月前';
	}
	return $r;
}

/**
 * @desc   随机生成字符串
 * @author rzliao
 * @date   2015-11-28
 * @param  integer    $length [需要的字符长度]
 * @return string
 */
function generateRandomString($length = 10) { 
	$characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ'; 
	$randomString = ''; 
	for ($i = 0; $i < $length; $i++) { 
		$randomString .= $characters[rand(0, strlen($characters) - 1)]; 
	} 
	return $randomString; 
}

/**
 * @desc   获取文件的后缀名
 * @author rzliao
 * @date   2015-11-28
 * @param  string     $fileName [文件名可包含路径]
 */
function getFileExt( $fileName = ''){
	return substr( strrchr( $fileName , '.'), 1);
}

/**
 * @desc   获取文件大小后转化成方便读的文字格式
 * @author rzliao
 * @date   2016-06-28
 * @param  integer    $size 
 * @return string
 */
function formatSize($size) {
	$sizes = [" Bytes", " KB", " MB", " GB", " TB", " PB", " EB", " ZB", " YB"];
	return $size == 0 ? 'n/a' : round($size/pow(1024, ($i = floor(log($size, 1024)))), 2) . $sizes[$i];
}

/**
 * @desc   数字转人名币整数
 * @author rzliao
 * @date   2016-06-29
 * @param  [type]     $num [description]
 * @return [type]          [description]
 */
function num2rmb ($num) {
	$c1 = "零壹贰叁肆伍陆柒捌玖";
	$c2 = "分角元拾佰仟万拾佰仟亿";
	$num = round($num, 2);
	$num = $num * 100;
	if (strlen($num) > 10) {
		return "oh,sorry,the number is too long!";
	}
	$i = 0;
	$c = "";
	while (1) {
		if ($i == 0) {
			$n = substr($num, strlen($num)-1, 1);
		} else {
			$n = $num % 10;
		}
		$p1 = substr($c1, 3 * $n, 3);
		$p2 = substr($c2, 3 * $i, 3);
		if ($n != '0' || ($n == '0' && ($p2 == '亿' || $p2 == '万' || $p2 == '元'))) {
			$c = $p1 . $p2 . $c;
		} else {
			$c = $p1 . $c;
		}
		$i = $i + 1;
		$num = $num / 10;
		$num = (int)$num;
		if ($num == 0) {
			break;
		}
	}
	$j = 0;
	$slen = strlen($c);
	while ($j < $slen) {
		$m = substr($c, $j, 6);
		if ($m == '零元' || $m == '零万' || $m == '零亿' || $m == '零零') {
			$left = substr($c, 0, $j);
			$right = substr($c, $j + 3);
			$c = $left . $right;
			$j = $j-3;
			$slen = $slen-3;
		}
		$j = $j + 3;
	}
	if (substr($c, strlen($c)-3, 3) == '零') {
		$c = substr($c, 0, strlen($c)-3);
	}
	return $c . "整";
}

/**
* 加密字符串
* @param string $str 字符串
* @param string $key 加密key
* @param integer $expire 有效期（秒）     
* @return string
*/
function encrypt($data, $key, $expire = 0) {

	$expire = sprintf('%010d', $expire ? $expire + time():0);
	$key  = md5($key);
	$data = base64_encode($expire.$data);
	$x    = 0;
	$len  = strlen($data);
	$l    = strlen($key);
	$char = $str    =   '';

	for ($i = 0; $i < $len; $i++) {
		if ($x == $l) $x = 0;
		$char .= substr($key, $x, 1);
		$x++;
	}

	for ($i = 0; $i < $len; $i++) {
		$str .= chr(ord(substr($data, $i, 1)) + (ord(substr($char, $i, 1)))%256);
	}
	return str_replace(array('+','/','='),array('-','_',''),base64_encode($str));
}

/**
* 解密字符串
* @param string $str 字符串
* @param string $key 加密key
* @return string
*/
function decrypt($data, $key) {
	$key    = md5($key);
	$data   = str_replace(array('-','_'),array('+','/'),$data);
	$mod4   = strlen($data) % 4;
	if ($mod4) {
		$data .= substr('====', $mod4);
	}
	$data   = base64_decode($data);

	$x      = 0;
	$len    = strlen($data);
	$l      = strlen($key);
	$char   = $str = '';

	for ($i = 0; $i < $len; $i++) {
		if ($x == $l) $x = 0;
		$char .= substr($key, $x, 1);
		$x++;
	}

	for ($i = 0; $i < $len; $i++) {
		if (ord(substr($data, $i, 1))<ord(substr($char, $i, 1))) {
			$str .= chr((ord(substr($data, $i, 1)) + 256) - ord(substr($char, $i, 1)));
		}else{
			$str .= chr(ord(substr($data, $i, 1)) - ord(substr($char, $i, 1)));
		}
	}
	$data   = base64_decode($str);
	$expire = substr($data,0,10);
	if($expire > 0 && $expire < time()) {
		return '';
	}
	$data   = substr($data,10);
	return $data;
}

function genUuid(){
	return md5(uniqid(md5(microtime(true)),true));
}

function getHttpStatusCode($num)
{
    $httpStatusCodes = array(
        100 => "HTTP/1.1 100 Continue",
        101 => "HTTP/1.1 101 Switching Protocols",
        200 => "HTTP/1.1 200 OK",
        201 => "HTTP/1.1 201 Created",
        202 => "HTTP/1.1 202 Accepted",
        203 => "HTTP/1.1 203 Non-Authoritative Information",
        204 => "HTTP/1.1 204 No Content",
        205 => "HTTP/1.1 205 Reset Content",
        206 => "HTTP/1.1 206 Partial Content",
        300 => "HTTP/1.1 300 Multiple Choices",
        301 => "HTTP/1.1 301 Moved Permanently",
        302 => "HTTP/1.1 302 Found",
        303 => "HTTP/1.1 303 See Other",
        304 => "HTTP/1.1 304 Not Modified",
        305 => "HTTP/1.1 305 Use Proxy",
        307 => "HTTP/1.1 307 Temporary Redirect",
        400 => "HTTP/1.1 400 Bad Request",
        401 => "HTTP/1.1 401 Unauthorized",
        402 => "HTTP/1.1 402 Payment Required",
        403 => "HTTP/1.1 403 Forbidden",
        404 => "HTTP/1.1 404 Not Found",
        405 => "HTTP/1.1 405 Method Not Allowed",
        406 => "HTTP/1.1 406 Not Acceptable",
        407 => "HTTP/1.1 407 Proxy Authentication Required",
        408 => "HTTP/1.1 408 Request Time-out",
        409 => "HTTP/1.1 409 Conflict",
        410 => "HTTP/1.1 410 Gone",
        411 => "HTTP/1.1 411 Length Required",
        412 => "HTTP/1.1 412 Precondition Failed",
        413 => "HTTP/1.1 413 Request Entity Too Large",
        414 => "HTTP/1.1 414 Request-URI Too Large",
        415 => "HTTP/1.1 415 Unsupported Media Type",
        416 => "HTTP/1.1 416 Requested range not satisfiable",
        417 => "HTTP/1.1 417 Expectation Failed",
        500 => "HTTP/1.1 500 Internal Server Error",
        501 => "HTTP/1.1 501 Not Implemented",
        502 => "HTTP/1.1 502 Bad Gateway",
        503 => "HTTP/1.1 503 Service Unavailable",
        504 => "HTTP/1.1 504 Gateway Time-out"
    );

    return isset($httpStatusCodes[$num]) ? $httpStatusCodes[$num] : '';
}