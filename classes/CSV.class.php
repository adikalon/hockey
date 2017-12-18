<?php

/**
 * Соединение с CSV
 */
class CSV
{
	/**
	 * Дескриптор соединения с таблиццей
	 */
	static public function connect()
	{
		$connect = new parseCSV();
		$connect->delimiter = ";";
		return $connect;
	}
}
