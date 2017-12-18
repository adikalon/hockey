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
	static public function insertOrUpdateCSV($data, $where, $category, $updated = false)
	{
		$csv_name = self::getCSVName($category);
		self::issetCSV($data, $csv_name);
		$id = self::getIssetInCSV($data, $where, $csv_name);
		if ($id === false) {
			CSV::connect()->save($csv_name, [$data], true);
			CSV::disconnect();
			return 1;
		} else {
			if (is_array($updated)) {
				$data[$updated[0]] = 1;
				CSV::connect()->parse($csv_name);
				CSV::connect()->data[$id] = $data;
				CSV::connect()->save();
				self::updatedStatusCSV($updated[0], $updated[1], $data[$updated[1]], $csv_name);
				CSV::disconnect();
				return 2;
			} else {
				CSV::connect()->parse($csv_name);
				CSV::connect()->data[$id] = $data;
				CSV::connect()->save();
				CSV::disconnect();
				return 2;
			}
		}
	}
	
	/**
	 * Проверяет наличие CSV файла и создает его в случае отсутствия
	 */
	static private function issetCSV($fields, $csv_name) {
		if (!file_exists(CSV) or !is_dir(CSV)) {
			mkdir(CSV);
		}
		if (!file_exists(CSV_PARSER) or !is_dir(CSV_PARSER)) {
			mkdir(CSV_PARSER);
		}
		if (file_exists($csv_name) and !is_dir($csv_name) and filesize($csv_name) < 4) {
			unlink($csv_name);
		}
		if (!file_exists($csv_name) or is_dir($csv_name)) {
			$header = [];
			foreach ($fields as $key => $value) {
				$header[] = $key;
			}
			CSV::connect()->save($csv_name, [$header], true);
			CSV::disconnect();
		}
	}
	
	/**
	 * Обновляет статус-поля в CSV
	 */
	static private function updatedStatusCSV($updated, $updated_field, $ident, $csv_name) {
		CSV::connect()->parse($csv_name);
		$find = CSV::connect()->data;
		foreach ($find as $id => $good) {
			if ($good[$updated] != 1 and $good[$updated_field] == $ident) {
				$good[$updated] = 2;
				CSV::connect()->parse($csv_name);
				CSV::connect()->data[$id] = $good;
				CSV::connect()->save();
			}
		}
		unset($find);
		CSV::disconnect();
	}
	
	/**
	 * Проверяет наличие записи в csv таблице
	 *
	 * @param array $data Данные для записи ['поле' => 'значение']
	 * @param array $where Имена полей из $data, по которым проверяется уникальность
	 * @return false Запись не найдена
	 * @return int id записи
	 */
	static private function getIssetInCSV($data, $where, $csv_name) {
		$count = count($where);
		CSV::connect()->parse($csv_name);
		$find = CSV::connect()->data;
		CSV::disconnect();
		foreach ($find as $id => $good) {
			$i = 0;
			foreach ($where as $key) {
				if ($good[$key] == $data[$key]) {
					$i++;
				}
			}
			if ($i >= $count) {
				unset($find);
				return $id;
			}
		}
		unset($find);
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
	static public function deleteNoUpdatedCSV($ident, $identField, $updatedField, $category = 'category')
	{

		//$sql = "DELETE FROM ".DB::$table." WHERE $updatedField=2 AND $identField=$ident";
		$csv_name = self::getCSVName($category);
		CSV::connect()->parse($csv_name);
		$findTwo = CSV::connect()->data;
		foreach ($findTwo as $id => $good) {
			if ($good[$identField] == $ident and $good[$updatedField] == 2) {
				$erase = [];
				foreach ($good as $k => $v) {
					$erase[$k] = '';
				}
				CSV::connect()->parse($csv_name);
				CSV::connect()->data[$id] = $erase;
				CSV::connect()->save();
			}
		}
		CSV::connect()->parse($csv_name);
		$find = CSV::connect()->data;
		foreach ($find as $id => $good) {
			if ($good[$identField] == $ident) {
				$good[$updatedField] = 0;
				CSV::connect()->parse($csv_name);
				CSV::connect()->data[$id] = $good;
				CSV::connect()->save();
			}
		}
		unset($findTwo, $find);
		CSV::disconnect();
	}
	
	/**
	 * Возвращает имя CSV файла
	 */
	static public function getCSVName($category)
	{
		$name = str_replace(['ä', ' '], ['a', '_'], mb_strtolower($category)).'.csv';
		return CSV_PARSER.'/'.$name;
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
