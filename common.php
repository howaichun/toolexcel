<?php
/**
 * Created by IntelliJ IDEA.
 * User: heweijun
 * Date: 2018/5/12
 * Time: 下午3:11
 */


/**数据库连接开始****/
$dbname = 'sincewin_my';
$dsn = 'mysql:host=127.0.0.1;dbname='.$dbname;
$username = 'root';
$password = 'root';
$options = array(
    PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8',
);
try{
    $pdo = new PDO($dsn, $username, $password, $options);
//默认这个不是长连接，如果需要数据库长连接，需要最后加一个参数：array(PDO::ATTR_PERSISTENT => true) 变成这样
//    $pdo = new PDO($dsn, $username, $password,
//        array(
//            PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8',
//            PDO::ATTR_PERSISTENT => true
//        )
//    );
    echo "数据库连接成功!\n";
}catch (PDOException $e) {
    die ("Error!: " . $e->getMessage() . "<br/>");
}
/***数据库连接判断结束***/



//var_dump(date("Y-m-d H:i:s"));
function p($arr, $status=false) {
    echo "<pre>"; var_dump($arr); echo "</pre>";
    if($status) exit;
}


function currentPath(){
    $path = __DIR__;
    return $path;
}
/**
 * @decription 建立文件
 * @param  string $aimUrl
 * @param  boolean $overWrite 该参数控制是否覆盖原文件
 * @return boolean
 */
function createFile($aimUrl, $overWrite = false) {
    if (file_exists_case($aimUrl) && $overWrite == false) {
        return false;
    } elseif (file_exists_case($aimUrl) && $overWrite == true) {
        unlinkFile($aimUrl);
    }
    $aimDir = dirname($aimUrl);
    createDir($aimDir);
    touch($aimUrl);
    return true;
}
/**
 * 建立文件夹
 * @param string $aimUrl
 * @return viod
 */
function createDir($aimUrl) {
    $aimUrl = str_replace('', '/', $aimUrl);
    $aimDir = '';
    $arr = explode('/', $aimUrl);
    $result = true;
    foreach ($arr as $str) {
        $aimDir .= $str . '/';
        if (!file_exists_case($aimDir)) {
            @$result = mkdir($aimDir,0777);
        }
    }
    return $result;
}

function storelogs($filepath,$word){
    if(!file_exists_case($filepath)){
        $tmp =	createFile($filepath);
    }
    $fp = fopen($filepath,"a");
    flock($fp, LOCK_EX) ;
    fwrite($fp,$word."\r\n");
    flock($fp, LOCK_UN);
    fclose($fp);
}


/**
 * 区分大小写的文件存在判断
 * @param string $filename 文件地址
 * @return boolean
 */
function file_exists_case($filename) {
    if (is_file($filename)) {
        //if (IS_WIN && APP_DEBUG) {
            if (basename(realpath($filename)) != basename($filename))
                return false;
//        }
        return true;
    }
    return false;
}