<?php
//北美的可以排课的
echo "agruments:" ."\n";



/** Include PHPExcel_IOFactory */
error_reporting(E_ALL);
ini_set('display_errors', TRUE);
ini_set('display_startup_errors', TRUE);
date_default_timezone_set('Asia/Shanghai');

include 'common.php';

//if(!empty($argv)) var_export($argv);

/**
try{
p($updateSql);
$query  = $pdo->prepare($updateSql);
$result = $query->execute();
} catch(Exception $e){
$e->getErrorMessage();
}
storelogs($log_path,$updateSql.'; '); //todo
 */

define('EOL',(PHP_SAPI == 'cli') ? PHP_EOL : '<br />');


include './PHPExcel/Classes/PHPExcel/IOFactory.php';

//读取的 Excel文件
//$xslx = empty($argv[1]) ? 'xll.xls' : $argv[1];
//var_export($xslx);
$xslx = 'xll.xls';

$monthEnKey = array(
    'JAN'=> '01', 'FEB'=> '02', 'MAR'=> '03', 'APR'=> '04', 'MAY'=> '05', 'JUNE'=> '06',
    'JULY'=> '07', 'AUG'=> '08', 'SEPT'=> '09', 'OCT'=> '10', 'NOV'=> '11', 'DEC'=> '12'
);
$monthKeyEn = array_flip($monthEnKey);

//校验是 Excel2005 or Excel2007
$inputFileName = $xslx;

$inputFileType = PHPExcel_IOFactory::identify($inputFileName);
$objReader = PHPExcel_IOFactory::createReader($inputFileType);

$sheetList = $objReader->listWorksheetNames($inputFileName);
$sheetInfo = $objReader->listWorksheetInfo($inputFileName);

//echo 'File Type:', PHP_EOL;
//var_dump($inputFileType);
//
echo 'Worksheet Names:', PHP_EOL;
var_export($sheetList);
//
//echo 'Worksheet Names:', PHP_EOL;
//var_dump($sheetInfo);

//获取日期及sheet name 列表
$dateList = array();
$xslxDate = array();
foreach($sheetList as $key=>$val){
    $date = '';
    $date = substr($val, 0, 10);
    $dateList[$date]['date']       = $date;
    $xslxDate[] = $date;
    $dateList[$date]['sheet_name'] = $val;
}


$arr = array(
    1  => 'A', 2  => 'B', 3    => 'C', 4 => 'D', 5 => 'E', 6 => 'F',
    7  => 'G', 8  => 'H', 9    => 'I', 10 => 'J', 11 => 'K', 12 => 'L',
    13 => 'M', 14 => 'N', 15   => 'O', 16 => 'P', 17 => 'Q', 18 => 'R',
    19 => 'S', 20 => 'T', 21   => 'U', 22 => 'V', 23 => 'W', 24 => 'X',
    25 => 'Y', 26 => 'Z', 27   => 'AA', 28=> 'AB', 29=> 'AC', 30=> 'AD',
    31 => 'AE',32 => 'AF',33   => 'AG', 34=> 'AH'
);

//根据行数生成 array todo
//$letter = array('A','B', 'C','D','E','F','G','H','I','J',);
$arrLetterToKey = array_flip($arr);


//读取所有的work sheet的内容
$dateArr = $sheetList;
//p($dateArr);
if(empty($dateArr)) exit('without sheetlist');
$collectArr   = '';
foreach($dateList as $dateKey => $dateVal){
    var_export($dateVal['sheet_name']);
    $currentSheetDate   = $dateVal['date'];
    $PHPExcel           = $objReader->load($inputFileName); // 文档名称
    $objWorksheet       = $PHPExcel->setActiveSheetIndexByName($dateVal['sheet_name']);
    $highestRow         = $objWorksheet->getHighestRow(); // 取得总行数
    $highestColumn      = $objWorksheet->getHighestColumn(); // 取得总列数

    //时间段数组
    $res        = array();
    $courseList = array();
    $val        = array();
    for ($row = 3; $row <= $highestRow; $row++) {
        for ($column = 0; $column < ($arrLetterToKey[$highestColumn]); $column++) {
            $val = $objWorksheet->getCellByColumnAndRow($column, $row)->getValue();
            if(empty($val)) continue;

            if($column == 0){
                $timeArr[] = $val;
                $splitArr  = explode('-', $val);
                //时间段
                $peroidArr[] = array(
                    'start_time' => trim($splitArr[0]),
                    'end_time'   => trim($splitArr[1])
                );

                //timeArr + periodArr;
                $fullTimeArr[] = array(
                    'period_time_string' => $val,
                    'period_time_arr'    => array(
                        'start_time' => trim($splitArr[0]),
                        'end_time'   => trim($splitArr[1])
                    )
                );
                continue;
            }
            $res[$row-3][$column] = $val;
        }
    }

    //教师列表
    $teacher_list = array_shift($res);

    //获取教师Id
    $teacher_key_name_list = '';
    foreach($teacher_list as $tk=>$tl){
        $username = trim($tl);
        $nick     = trim($tl);
        $sql      = '';
        $sql      = "select distinct id as teacher_id , level_id, username, nick from nk_admin_member where username like '".$username."%'";
        $sql     .= " or nick like '%".$username."%'";

        try{
            $query    = $pdo->prepare($sql);
            $query->execute();
            $row      = $query->fetch(PDO::FETCH_ASSOC);
        } catch (Exception $e){
            print $e->getMessage();
            exit;
        }

        //存储操作日志的情况
        if(empty($row)){
            storelogs(  __DIR__."/Logs/".date('Ymd',time())."error_log.txt", date("Y-m-d H:i:s").' ['.$tl.'] '.$sql);
            continue;
        } else {
            storelogs(  __DIR__."/Logs/".date('Ymd',time())."success_log.txt", date("Y-m-d H:i:s").' ['.$tl.'] '.$sql);
        }

        //组装需要的数据
        if(!empty($row['teacher_id'])){
            $teacher_key_name_list["$tk"]['teacher_id']    = $row['teacher_id'];
            $teacher_key_name_list["$tk"]['username']      = $row['username'];
            $teacher_key_name_list["$tk"]['schedule_date'] = $currentSheetDate;
            $teacher_key_name_list["$tk"]['create_time']   = date('Y-m-d H:i:s');
            $teacher_key_name_list["$tk"]['modify_time']   = date('Y-m-d H:i:s');
        }

    }

    //teacher_id schedule   key=>1 - n
//    p($teacher_key_name_list,1);

    //课表记录
    $courseList  = $res;
    $peroidArrTmp = '';
    foreach($courseList as $ck=>$cv){    //1 -n 跟 teacher list 对应
        //输出一个时间
        $peroidArrTmp     = array_shift($peroidArr);
        $start_time = $peroidArrTmp['start_time'];
        $end_time   = $peroidArrTmp['end_time'];
        foreach($cv as $sk=>$sv){
            // $sk 跟  $tk 对应
            //可以选课时间
            $teacher_id    = $schedule_date ='';

            $teacher_id    = $teacher_key_name_list["$sk"]['teacher_id'];
            $schedule_date = $teacher_key_name_list["$sk"]['schedule_date'];
            $teacher_name  = $teacher_key_name_list["$sk"]['username'];

            if(!empty($teacher_id)){
                $collectArr[$teacher_id][] = array(
                    'teacher_id'     => $teacher_id,
                    'schedule_date'  => $schedule_date,
                    'start_time'     => $start_time,
                    'end_time'       => $end_time,
                    'status'         => $sv,
                    'teacher_name'   => $teacher_name,
                    'create_time'    => date("Y-m-d H:i:s"),
                    'modify_time'    => date("Y-m-d H:i:s")
                );
            }
        }
    }
}

//清理 N 、 Booked 参数
$finalCollectArr = array();
if(!empty($collectArr)){
    foreach($collectArr as $key=>$val){
        foreach($val as $k=>$v){
            if($v['status'] == 'Y'){
                $finalCollectArr[$key][] = $v;
            }
        }
    }
}

//teacher_id  schedule_date  start_time end_time create_time modify_time
//北美教师 数据更新
//if($argv[2] == 'NORTH'){
foreach($finalCollectArr as $key=>$val){
    foreach($val as $k=>$v){
        $insertSql = 'INSERT INTO nk_teacher_schedule (teacher_id, schedule_date, start_time, end_time, create_time,modify_time) VALUES ( "' . $v['teacher_id'] . '", "'.$v['schedule_date'] .'", "'.$v['start_time'].'","'.$v['end_time'].'","'.$v['create_time'].'", "'.$v['modify_time'].'");';
        storelogs(  __DIR__."/Logs/".date('Ymd',time())."all_sql.txt", $insertSql);
        storelogs(  __DIR__."/Logs/".date('Ymd',time())."sql.txt", '[ '.date("Y-m-d H:i:s").' ] '.$insertSql);
        try{
            $query    = $pdo->prepare($insertSql);
            $result = $query->execute();
        } catch (Exception $e){
            storelogs(  __DIR__."/Logs/".date('Ymd',time())."north_insert_error_sql.txt", '[ '.date("Y-m-d H:i:s").' ] '.$e->getMessage());
            print $e->getMessage();
            exit;
        }
    }
}
//}

//p($fullTimeArr);
//p($xslxDate);

//phili
//if($argv[2] = 'PHILI'){
//
//
$sql = "select distinct `id`, `local_nation`,`nick`, `username`,`status`, `center_id`, `nationality`
        from nk_admin_member where level_id=4 and status=1  and local_nation like '%菲律宾%'";
//echo $sql."\n"; exit;
$query = $pdo->prepare($sql);
$query->execute();
$row = $query->fetchAll(PDO::FETCH_ASSOC);

$dateTimeList = array();
foreach($xslxDate as $date){
    foreach($fullTimeArr as $timeKey=>$timeValue){
        $start_time = $timeValue['period_time_arr']['start_time'];
        $end_time   = $timeValue['period_time_arr']['end_time'];
        $schedule_date = $date;

        $kkk = $schedule_date.'_'.$start_time;

        $dateTimeList[$kkk] = array(
            'start_time'    => $start_time,
            'end_time'      => $end_time,
            'schedule_date' => $schedule_date,
            'create_time'   => date("Y-m-d H:i:s"),
            'modify_time'   => date("Y-m-d H:i:s")
        );
    }
}


foreach($dateTimeList as $key=>$value){
    foreach($row as $k=>$v){
        $teacher_id    = $v['id'];

        $schedule_date = $value['schedule_date'];
        $create_time   = $value['create_time'];
        $modify_time   = $value['modify_time'];
        $start_time    = $value['start_time'];
        $end_time      = $value['end_time'];

        $insertSql  = 'INSERT INTO nk_teacher_schedule (teacher_id, schedule_date, start_time, end_time, create_time,modify_time)
          VALUES ( "' . $teacher_id . '", "'.$schedule_date .'", "'.$start_time.'","'.$end_time.'","'.$create_time.'", "'.$modify_time.'");';
        storelogs(  __DIR__."/Logs/".date('Ymd',time())."all_sql.txt", $insertSql);
        storelogs(  __DIR__."/Logs/".date('Ymd',time())."Phil_sql.txt", '[ '.date("Y-m-d H:i:s").' ] '.$insertSql);
        echo $insertSql."\n";
        try{
            $query    = $pdo->prepare($insertSql);
            $result = $query->execute();
        } catch (Exception $e){
            storelogs(  __DIR__."/Logs/".date('Ymd',time())."Phil_insert_error_sql.txt", '[ '.date("Y-m-d H:i:s").' ] '.$e->getMessage());
            print $e->getMessage();
            exit;
        }
    }
}















