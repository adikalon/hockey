<?php

/**
 * Соединение с базой данных
 */
class DB
{
	static public $table = PARSER_NAME;
	static private $host = 'localhost';
	static private $dbname = 'hockey';
	static private $user = 'root';
	static private $pass = '';
	static private $connect = null;
	
	/**
	 * Дескриптор соединения с БД
	 */
	static public function connect()
	{
		if (null === self::$connect) {
			self::$connect = new PDO('mysql:host='.self::$host.';dbname='.self::$dbname, self::$user, self::$pass);
		}
		return self::$connect;
	}
}