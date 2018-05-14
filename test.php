<?php

error_reporting(E_ALL ^ E_NOTICE);
require_once 'excel_reader2.php';
$data = new Spreadsheet_Excel_Reader("NORTH_TEACHER_COURSE_SCHEDULE.xls");
 echo $data->dump(true,true,0);
?>