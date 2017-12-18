<?php

/**
 * Запросы на сервер
 */
class Request
{
	/**
	 * Стандартные настройки курла
	 */
	static private $curlOptions = [
		CURLOPT_FOLLOWLOCATION => true,
		CURLOPT_ENCODING => "",
		CURLOPT_HTTPHEADER => [
			"User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/60.0.3112.101 Safari/537.36",
		],
	];
	
	/**
	 * Запрос курлом
	 */
	static public function curl($link = false, $pause = false, $options = false, $proxy = false)
	{
		if ($pause) {
			if (!is_numeric($pause)) {
				Logger::send('|CURL|ERROR| - Параметр паузы не является числом');
				return false;
			}
			sleep((int)$pause);
		}
		if (!$link) {
			Logger::send('|CURL|ERROR| - URL не передан');
			return false;
		}
		if (!$options) {
			$options = self::$curlOptions;
		}
		$options[CURLOPT_URL] = $link;
		$options[CURLOPT_RETURNTRANSFER] = true;
		if ($proxy) {
			$options[CURLOPT_PROXY] = $proxy;
		}
		$curl = curl_init();
		curl_setopt_array($curl, $options);
		$result = curl_exec($curl);
		curl_close($curl);
		unset($curl);
		if (!$result) {
			Logger::send("|CURL|ERROR| - Нет ответа от $link");
			return false;
		}
		return $result;
	}
}