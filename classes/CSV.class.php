<?php

/**
 * Соединение с CSV
 */
class CSV
{
	static public $connect = null;
	
	/**
	 * Дескриптор соединения с таблиццей
	 */
	static public function connect()
	{
		if (null === self::$connect) {
			self::$connect = new parseCSV();
			self::$connect->delimiter = ";";
		}
		return self::$connect;
	}
}
