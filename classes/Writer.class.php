<?php

/**
 * Запись полученных данных
 */
class Writer
{
	/**
	 * Обновит или создаст новую запись в CSV
	 *
	 * @param array $data Данные для записи ['поле' => 'значение']
	 * @param array $where Имена полей из $data, по которым проверяется уникальность
	 * @param array $updated Поле-статус, поле-идентификатор. Для self::deleteNoUpdated()
	 * @return 1 Запись добавлена
	 * @return 2 Запись обновлена
	 */
	static public function insertOrUpdateCSV($data, $where, $updated = false)
	{
		self::issetCSV($data);
		$id = self::getIssetInCSV($data, $where);
		if ($id === false) {
			CSV::connect()->save(CSV_NAME, [$data], true);
			return 1;
		} else {
			if (is_array($updated)) {
				$data[$updated[0]] = 1;
				CSV::connect()->parse(CSV_NAME);
				CSV::connect()->data[$id] = $data;
				CSV::connect()->save();
				self::updatedStatusCSV($updated[0], $updated[1], $data[$updated[1]]);
				return 2;
			} else {
				CSV::connect()->parse(CSV_NAME);
				CSV::connect()->data[$id] = $data;
				CSV::connect()->save();
				return 2;
			}
		}
	}
	
	/**
	 * Проверяет наличие CSV файла и создает его в случае отсутствия
	 */
	static private function issetCSV($fields) {
		if (!file_exists(CSV) or !is_dir(CSV)) {
			mkdir(CSV);
		}
		if (!file_exists(CSV_NAME) or is_dir(CSV_NAME)) {
			$header = [];
			foreach ($fields as $key => $value) {
				$header[] = $key;
			}
			CSV::connect()->save(CSV_NAME, [$header], true);
		}
	}
	
	/**
	 * Обновляет статус-поля в CSV
	 */
	static private function updatedStatusCSV($updated, $updated_field, $ident) {
		CSV::connect()->parse(CSV_NAME);
		$find = CSV::connect()->data;
		foreach ($find as $id => $good) {
			if ($good[$updated] != 1 and $good[$updated_field] == $ident) {
				$good[$updated] = 2;
				CSV::connect()->parse(CSV_NAME);
				CSV::connect()->data[$id] = $good;
				CSV::connect()->save();
			}
		}
	}
	
	/**
	 * Проверяет наличие записи в csv таблице
	 *
	 * @param array $data Данные для записи ['поле' => 'значение']
	 * @param array $where Имена полей из $data, по которым проверяется уникальность
	 * @return false Запись не найдена
	 * @return int id записи
	 */
	static private function getIssetInCSV($data, $where) {
		$count = count($where);
		CSV::connect()->parse(CSV_NAME);
		$find = CSV::connect()->data;
		foreach ($find as $id => $good) {
			$i = 0;
			foreach ($where as $key) {
				if ($good[$key] == $data[$key]) {
					$i++;
				}
			}
			if ($i >= $count) {
				return $id;
			}
		}
		return false;
	}
	
	/**
	 * Удаляет записи, которые не обновились в CSV. Только для записей с общим идентификатором
	 *
	 * Работает в связке с self::insertOrUpdateCSV() и параметром $updated
	 *
	 * @param string $ident Идентификатор записи
	 * @param string $identField Имя поля, в котором искать $ident
	 * @param string $updatedField Имя поля-cтатуса
	 */
	static public function deleteNoUpdatedCSV($ident, $identField, $updatedField)
	{

		//$sql = "DELETE FROM ".DB::$table." WHERE $updatedField=2 AND $identField=$ident";
		
		CSV::connect()->parse(CSV_NAME);
		$findTwo = CSV::connect()->data;
		foreach ($findTwo as $id => $good) {
			if ($good[$identField] == $ident and $good[$updatedField] == 2) {
				$erase = [];
				foreach ($good as $k => $v) {
					$erase[$k] = '';
				}
				CSV::connect()->parse(CSV_NAME);
				CSV::connect()->data[$id] = $erase;
				CSV::connect()->save();
			}
		}
		CSV::connect()->parse(CSV_NAME);
		$find = CSV::connect()->data;
		foreach ($find as $id => $good) {
			if ($good[$identField] == $ident) {
				$good[$updatedField] = 0;
				CSV::connect()->parse(CSV_NAME);
				CSV::connect()->data[$id] = $good;
				CSV::connect()->save();
			}
		}
	}

	/**
	 * Обновит или создаст новую запись в MySQL
	 *
	 * @param array $data Данные для записи ['поле' => 'значение']
	 * @param array $where Имена полей из $data, по которым проверяется уникальность
	 * @param array $updated Поле-статус, поле-идентификатор. Для self::deleteNoUpdated()
	 * @return 1 Запись добавлена
	 * @return 2 Запись обновлена
	 */
	static public function insertOrUpdate($data, $where, $updated = false)
	{
		$sql = "SELECT * FROM ".DB::$table." WHERE ".self::whereQuery($data, $where)." LIMIT 1";
		$find = DB::connect()->query($sql);
		$row = $find->fetch();
		if ($row) {
			if (is_array($updated)) {
				$data[$updated[0]] = 1;
				DB::connect()->exec(self::updateQuery($data, $where));
				$sql = "UPDATE ".DB::$table." SET ".$updated[0]."=2 WHERE ".$updated[0]."!=1 AND ".$updated[1]."=".$data[$updated[1]];
				DB::connect()->exec($sql);
				return 2;
			} else {
				DB::connect()->exec(self::updateQuery($data, $where));
				return 2;
			}
		} else {
			DB::connect()->exec(self::insertQuery($data));
			return 1;
		}
	}
	
	/**
	 * Удаляет записи, которые не обновились. Только для записей с общим идентификатором
	 *
	 * Работает в связке с self::insertOrUpdate() и параметром $updated
	 *
	 * @param string $ident Идентификатор записи
	 * @param string $identField Имя поля, в котором искать $ident
	 * @param string $updatedField Имя поля-cтатуса
	 */
	static public function deleteNoUpdated($ident, $identField, $updatedField)
	{
		$ident = DB::connect()->quote($ident);
		$sql = "DELETE FROM ".DB::$table." WHERE $updatedField=2 AND $identField=$ident";
		DB::connect()->exec($sql);
		$sql = "UPDATE ".DB::$table." SET $updatedField=0 WHERE $identField=$ident";
		DB::connect()->exec($sql);
	}
	
	/**
	 * Составляет подстроку запроса для where
	 */
	static private function whereQuery($data, $where)
	{
		$query = '';
		foreach ($where as $key) {
			$query .= $key.'='.DB::connect()->quote($data[$key]).' AND ';
		}
		return substr(trim($query), 0, -4);
	}
	
	/**
	 * Составляет запрос для update
	 */
	static private function updateQuery($data, $where)
	{
		$sets = '';
		foreach ($data as $k => $v) {
			$sets .= $k.'='.DB::connect()->quote($v).', ';
		}
		return "UPDATE ".DB::$table." SET ".substr(trim($sets), 0, -1)." WHERE ".self::whereQuery($data, $where)." LIMIT 1";
	}
	
	/**
	 * Составляет запрос для insert
	 */
	static private function insertQuery($data)
	{
		$keys = '';
		$valus = '';
		foreach ($data as $k => $v) {
			$keys .= $k.', ';
			$valus .= DB::connect()->quote($v).', ';
		}
		return "INSERT INTO ".DB::$table." (".substr(trim($keys), 0, -1).") VALUES (".substr(trim($valus), 0, -1).")";
	}
	
	/**
	 * Удаляет товар
	 */
	static public function deleteGoods($field, $value)
	{
		$field = DB::connect()->quote($field);
		$value = DB::connect()->quote($value);
		$sql = "DELETE FROM ".DB::$table." WHERE $field=$value";
		DB::connect()->exec($sql);
	}
	
	/**
	 * Удаляет все файлы в директории
	 *
	 * @param string $id Имя конечной директории в attachments
	 * @param string $folder Имя промежуточной директории в attachments
	 */
	static public function eraseAttachInIdFolder($id, $folder = false)
	{
		$sub = '/';
		if (is_string($folder)) {
			$sub = '/'.$folder.'/';
		}
		$path = PARSER_ATTACHMENTS.$sub.$id;
		if (file_exists($path) and is_dir($path)) {
			foreach (glob($path.'/*') as $file) {
				if (file_exists($file) and !is_dir($file)) {
					unlink($file);
				}
			}
		}
	}
	
	/**
	 * Выкачивание изображений
	 *
	 * @param string $id Имя конечной директории в attachments
	 * @param array $images Массив ссылок на изображения
	 * @param string $folder Имя промежуточной директории в attachments
	 */
	static public function saveOnUpdateImages($id, $images, $folder = false)
	{
		if (empty($images)) {
			return null;
		}
		foreach ($images as $image) {
			copy($image, self::getImgPath($id, $folder).'/'.self::getImgName($image));
		}
	}
	
	/**
	 * Отдает навание изображения
	 */
	static private function getImgName($link)
	{
		preg_match('/.*\/(.+(?:JPG|JPEG|PNG|GIF))\/?.*/i', $link, $match);
		return $match[1];
	}
	
	/**
	 * Отдает путь для сохранения изображения
	 */
	static private function getImgPath($id, $folder)
	{
		$resource = ATTACHMENTS;
		if (!file_exists($resource) or !is_dir($resource)) {
			mkdir($resource);
		}
		$resource = PARSER_ATTACHMENTS;
		if (!file_exists($resource) or !is_dir($resource)) {
			mkdir($resource);
		}
		if (is_string($folder)) {
			$resource = $resource.'/'.$folder;
			if (!file_exists($resource) or !is_dir($resource)) {
				mkdir($resource);
			}
		}
		$resource = $resource.'/'.$id;
		if (!file_exists($resource) or !is_dir($resource)) {
			mkdir($resource);
		}
		return $resource;
	}
}
