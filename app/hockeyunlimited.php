<?php
require __DIR__.'/../core.php';

Logger::send("|START|SUCCESS| - Скрипт запущен. Парсинг из ".PARSER_NAME);

$pause = 0;

// Проверяем не находится ли категория в блэк листе
function isBlackCat($link) {
	$categories = [
		'?ObjectPath=/Shops/2014061601/Categories/Lahjavinkit',
		'?ObjectPath=/Shops/2014061601/Categories/MVVARUSTEET',
		'?ObjectPath=/Shops/2014061601/Categories/POISTOKORI',
	];
	return in_array($link, $categories);
}

// Получаем массив ссылок на категории
function getCategories() {
	global $pause;
	$html = Request::curl('https://www.hockeyunlimited.fi/', $pause);
	$dom = phpQuery::newDocument($html);
	$links = $dom->find('#NavBarElementID2266322 a');
	$res = [];
	foreach ($links as $link) {
		if (isBlackCat(pq($link)->attr('href'))) continue;
		$res[trim(pq($link)->text())] = 'https://www.hockeyunlimited.fi/epages/hockeyunlimited.sf/fi_FI/'.pq($link)->attr('href');
	}
	$dom->unloadDocument();
	unset($html, $dom, $links);
	if (empty($res)) {
		Logger::send("|CATEGORIES|ERROR| - Не удалось создать список категорий. Скрипт остановлен");
		exit();
	}
	return $res;
}

// Получаем кол-во страниц
function getPagesCount($link, $size = 500) {
	global $pause;
	$html = Request::curl($link.'&PageSize='.$size.'&Page=1', $pause);
	$dom = phpQuery::newDocument($html);
	$marker = $dom->find('a[rel=next]');
	if (count($marker) < 1) {
		$dom->unloadDocument();
		return 1;
	}
	$pages[] = 1;
	foreach ($marker as $mark) {
		$cur = pq($mark)->text();
		if (is_numeric($cur)) {
			$pages[] = $cur;
		}
	}
	$dom->unloadDocument();
	unset($html, $dom, $marker, $cur);
	return max($pages);
}

// Получаем сезон
function getSeason($string) {
	preg_match('/.*S(\d\d).*/', $string, $match);
	unset($string);
	if (isset($match[1]) and !empty($match[1])) {
		return '20'.$match[1];
	}
	return '';
}

// Получаем Product Age
function getProductAge($string) {
	preg_match('/.*(junior|senior|youth).*/', strtolower($string), $match);
	unset($string);
	if (isset($match[1]) and !empty($match[1])) {
		return $match[1];
	}
	return '';
}

// Получаем название производителя
function getManufacturer($string) {
	preg_match('/.*(CCM|Bauer).*/', $string, $match);
	unset($string);
	if (isset($match[1]) and !empty($match[1])) {
		return $match[1];
	}
	return '';
}

// Получаем параметры
function getParams($dom, $name) {
	$params = [];
	$options = $dom->find('label:contains("'.$name.'")')->parent()->parent()->find('option');
	if (count($options) < 1) {
		$params[] = '';
	} else {
		foreach ($options as $param) {
			if (stristr(pq($param)->text(), 'vaihtoehto') === false) {
				$params[] = trim(pq($param)->text());
			}
		}
	}
	if (empty($params)) {
		return [''];
	}
	unset($dom, $name, $options);
	return $params;
}

// Получаем id товара
function getIdent($html) {
	preg_match("/.*objectId:\s'(\d+)'.*/", $html, $match);
	unset($html);
	return trim($match[1]);
}

// Получаем фото товара
function getPhotos($dom) {
	$imgs = [];
	$results = $dom->find('img[data-src-l]');
	foreach ($results as $res) {
		$imgs[] = 'https://www.hockeyunlimited.fi'.trim(pq($res)->attr('data-src-l'));
	}
	unset($results);
	return $imgs;
}

// Разбираем страницу товара и отправляем на запись (Koko)
function parseGoodKoko($href, $category) {
	global $pause;
	$html = Request::curl($href, $pause);
	$dom = phpQuery::newDocument($html);
	foreach (getParams($dom, 'Koko') as $koko) {
		$data = [
			'product_id' => getIdent($html),
			'category' => trim($dom->find('a.BreadcrumbItem')->eq(1)->text()),
			'title' => trim($dom->find('span.name')->text()),
			'season' => getSeason(trim($dom->find('span.name')->text())),
			'price' => trim($dom->find('span.price-value>span')->attr('content')),
			'price_without_discount' => str_replace(['€', ',', ' '], ['', '.', ''], $dom->find('span.LineThrough')->text()),
			'product_type' => trim($dom->find('a.BreadcrumbItem')->eq(1)->text()),
			'product_age' => getProductAge(trim($dom->find('span.name')->text())),
			'manufacturer' => getManufacturer(trim($dom->find('span.name')->text())),
			'availableurl' => $href,
			'koko' => $koko,
			'updated' => 0,
		];
		$csv = Writer::insertOrUpdateCSV($data, [
			'availableurl',
			'koko',
		], $category/*, ['updated', 'product_id']*/);
		switch ($csv) {
			case 1:
				Logger::send("|RECORD|ADD| - Товар ".$data['title']." добавлен");
				break;
			case 2:
				Logger::send("|RECORD|UPDATE| - Товар ".$data['title']." обновлен");
				break;
		}
	}
	//Writer::deleteNoUpdatedCSV($data['product_id'], 'product_id', 'updated', $category);
	Writer::eraseAttachInIdFolder($data['product_id']);
	Writer::saveOnUpdateImages($data['product_id'], getPhotos($dom));
	$dom->unloadDocument();
	unset($html, $dom, $data, $csv, $category, $href, $pause);
}

// Разбираем страницу товара и отправляем на запись (Flex Katisyys)
function parseGoodFlexKatisyys($href, $category) {
	global $pause;
	$html = Request::curl($href, $pause);
	$dom = phpQuery::newDocument($html);
	foreach (getParams($dom, 'Flex') as $flex) {
		foreach (getParams($dom, 'Kätisyys') as $katisyys) {
			$data = [
				'product_id' => getIdent($html),
				'category' => trim($dom->find('a.BreadcrumbItem')->eq(1)->text()),
				'title' => trim($dom->find('span.name')->text()),
				'season' => getSeason(trim($dom->find('span.name')->text())),
				'price' => trim($dom->find('span.price-value>span')->attr('content')),
				'price_without_discount' => str_replace(['€', ',', ' '], ['', '.', ''], $dom->find('span.LineThrough')->text()),
				'product_type' => trim($dom->find('a.BreadcrumbItem')->eq(1)->text()),
				'product_age' => getProductAge(trim($dom->find('span.name')->text())),
				'manufacturer' => getManufacturer(trim($dom->find('span.name')->text())),
				'availableurl' => $href,
				'flex' => $flex,
				'katisyys' => $katisyys,
				'updated' => 0,
			];
			$csv = Writer::insertOrUpdateCSV($data, [
				'availableurl',
				'flex',
				'katisyys',
			], $category/*, ['updated', 'product_id']*/);
			switch ($csv) {
				case 1:
					Logger::send("|RECORD|ADD| - Товар ".$data['title']." добавлен");
					break;
				case 2:
					Logger::send("|RECORD|UPDATE| - Товар ".$data['title']." обновлен");
					break;
			}
		}
	}
	//Writer::deleteNoUpdatedCSV($data['product_id'], 'product_id', 'updated', $category);
	Writer::eraseAttachInIdFolder($data['product_id']);
	Writer::saveOnUpdateImages($data['product_id'], getPhotos($dom));
	$dom->unloadDocument();
	unset($html, $dom, $data, $csv, $category, $href, $pause);
}

// Разбираем страницу товара и отправляем на запись (Koko Vari)
function parseGoodKokoVari($href, $category) {
	global $pause;
	$html = Request::curl($href, $pause);
	$dom = phpQuery::newDocument($html);
	foreach (getParams($dom, 'Koko') as $koko) {
		foreach (getParams($dom, 'Väri') as $vari) {
			$data = [
				'product_id' => getIdent($html),
				'category' => trim($dom->find('a.BreadcrumbItem')->eq(1)->text()),
				'title' => trim($dom->find('span.name')->text()),
				'season' => getSeason(trim($dom->find('span.name')->text())),
				'price' => trim($dom->find('span.price-value>span')->attr('content')),
				'price_without_discount' => str_replace(['€', ',', ' '], ['', '.', ''], $dom->find('span.LineThrough')->text()),
				'product_type' => trim($dom->find('a.BreadcrumbItem')->eq(1)->text()),
				'product_age' => getProductAge(trim($dom->find('span.name')->text())),
				'manufacturer' => getManufacturer(trim($dom->find('span.name')->text())),
				'availableurl' => $href,
				'koko' => $koko,
				'vari' => $vari,
				'updated' => 0,
			];
			$csv = Writer::insertOrUpdateCSV($data, [
				'availableurl',
				'koko',
				'vari',
			], $category/*, ['updated', 'product_id']*/);
			switch ($csv) {
				case 1:
					Logger::send("|RECORD|ADD| - Товар ".$data['title']." добавлен");
					break;
				case 2:
					Logger::send("|RECORD|UPDATE| - Товар ".$data['title']." обновлен");
					break;
			}
		}
	}
	//Writer::deleteNoUpdatedCSV($data['product_id'], 'product_id', 'updated', $category);
	Writer::eraseAttachInIdFolder($data['product_id']);
	Writer::saveOnUpdateImages($data['product_id'], getPhotos($dom));
	$dom->unloadDocument();
	unset($html, $dom, $data, $csv, $category, $href, $pause);
}

// Разбираем страницу товара и отправляем на запись (Koko Vari Flex Katisyys)
function parseGoodKokoVariFlexKatisyys($href, $category) {
	global $pause;
	$html = Request::curl($href, $pause);
	$dom = phpQuery::newDocument($html);
	foreach (getParams($dom, 'Koko') as $koko) {
		foreach (getParams($dom, 'Väri') as $vari) {
			foreach (getParams($dom, 'Flex') as $flex) {
				foreach (getParams($dom, 'Kätisyys') as $katisyys) {
					$data = [
						'product_id' => getIdent($html),
						'category' => trim($dom->find('a.BreadcrumbItem')->eq(1)->text()),
						'title' => trim($dom->find('span.name')->text()),
						'season' => getSeason(trim($dom->find('span.name')->text())),
						'price' => trim($dom->find('span.price-value>span')->attr('content')),
						'price_without_discount' => str_replace(['€', ',', ' '], ['', '.', ''], $dom->find('span.LineThrough')->text()),
						'product_type' => trim($dom->find('a.BreadcrumbItem')->eq(1)->text()),
						'product_age' => getProductAge(trim($dom->find('span.name')->text())),
						'manufacturer' => getManufacturer(trim($dom->find('span.name')->text())),
						'availableurl' => $href,
						'koko' => $koko,
						'vari' => $vari,
						'flex' => $flex,
						'katisyys' => $katisyys,
						'updated' => 0,
					];
					$csv = Writer::insertOrUpdateCSV($data, [
						'availableurl',
						'koko',
						'vari',
						'flex',
						'katisyys',
					], $category/*, ['updated', 'product_id']*/);
					switch ($csv) {
						case 1:
							Logger::send("|RECORD|ADD| - Товар ".$data['title']." добавлен");
							break;
						case 2:
							Logger::send("|RECORD|UPDATE| - Товар ".$data['title']." обновлен");
							break;
					}
				}
			}
		}
	}
	//Writer::deleteNoUpdatedCSV($data['product_id'], 'product_id', 'updated', $category);
	Writer::eraseAttachInIdFolder($data['product_id']);
	Writer::saveOnUpdateImages($data['product_id'], getPhotos($dom));
	$dom->unloadDocument();
	unset($html, $dom, $data, $csv, $category, $href, $pause);
}

// Разбираем страницу товара и отправляем на запись (Koko Vari Pituus)
function parseGoodKokoVariPituus($href, $category) {
	global $pause;
	$html = Request::curl($href, $pause);
	$dom = phpQuery::newDocument($html);
	foreach (getParams($dom, 'Koko') as $koko) {
		foreach (getParams($dom, 'Väri') as $vari) {
			foreach (getParams($dom, 'Pituus') as $pituus) {
				$data = [
					'product_id' => getIdent($html),
					'category' => trim($dom->find('a.BreadcrumbItem')->eq(1)->text()),
					'title' => trim($dom->find('span.name')->text()),
					'season' => getSeason(trim($dom->find('span.name')->text())),
					'price' => trim($dom->find('span.price-value>span')->attr('content')),
					'price_without_discount' => str_replace(['€', ',', ' '], ['', '.', ''], $dom->find('span.LineThrough')->text()),
					'product_type' => trim($dom->find('a.BreadcrumbItem')->eq(1)->text()),
					'product_age' => getProductAge(trim($dom->find('span.name')->text())),
					'manufacturer' => getManufacturer(trim($dom->find('span.name')->text())),
					'availableurl' => $href,
					'koko' => $koko,
					'vari' => $vari,
					'pituus' => $pituus,
					'updated' => 0,
				];
				$csv = Writer::insertOrUpdateCSV($data, [
					'availableurl',
					'koko',
					'vari',
					'pituus',
				], $category/*, ['updated', 'product_id']*/);
				switch ($csv) {
					case 1:
						Logger::send("|RECORD|ADD| - Товар ".$data['title']." добавлен");
						break;
					case 2:
						Logger::send("|RECORD|UPDATE| - Товар ".$data['title']." обновлен");
						break;
				}
			}
		}
	}
	//Writer::deleteNoUpdatedCSV($data['product_id'], 'product_id', 'updated', $category);
	Writer::eraseAttachInIdFolder($data['product_id']);
	Writer::saveOnUpdateImages($data['product_id'], getPhotos($dom));
	$dom->unloadDocument();
	unset($html, $dom, $data, $csv, $category, $href, $pause);
}

// Разбираем страницу товара и отправляем на запись (Koko Vari Elain Pituus)
function parseGoodKokoVariElainPituus($href, $category) {
	global $pause;
	$html = Request::curl($href, $pause);
	$dom = phpQuery::newDocument($html);
	foreach (getParams($dom, 'Koko') as $koko) {
		foreach (getParams($dom, 'Väri') as $vari) {
			foreach (getParams($dom, 'Eläin') as $elain) {
				foreach (getParams($dom, 'Pituus') as $pituus) {
					$data = [
						'product_id' => getIdent($html),
						'category' => trim($dom->find('a.BreadcrumbItem')->eq(1)->text()),
						'title' => trim($dom->find('span.name')->text()),
						'season' => getSeason(trim($dom->find('span.name')->text())),
						'price' => trim($dom->find('span.price-value>span')->attr('content')),
						'price_without_discount' => str_replace(['€', ',', ' '], ['', '.', ''], $dom->find('span.LineThrough')->text()),
						'product_type' => trim($dom->find('a.BreadcrumbItem')->eq(1)->text()),
						'product_age' => getProductAge(trim($dom->find('span.name')->text())),
						'manufacturer' => getManufacturer(trim($dom->find('span.name')->text())),
						'availableurl' => $href,
						'koko' => $koko,
						'vari' => $vari,
						'elain' => $elain,
						'pituus' => $pituus,
						'updated' => 0,
					];
					$csv = Writer::insertOrUpdateCSV($data, [
						'availableurl',
						'koko',
						'vari',
						'elain',
						'pituus',
					], $category/*, ['updated', 'product_id']*/);
					switch ($csv) {
						case 1:
							Logger::send("|RECORD|ADD| - Товар ".$data['title']." добавлен");
							break;
						case 2:
							Logger::send("|RECORD|UPDATE| - Товар ".$data['title']." обновлен");
							break;
					}
				}
			}
		}
	}
	//Writer::deleteNoUpdatedCSV($data['product_id'], 'product_id', 'updated', $category);
	Writer::eraseAttachInIdFolder($data['product_id']);
	Writer::saveOnUpdateImages($data['product_id'], getPhotos($dom));
	$dom->unloadDocument();
	unset($html, $dom, $data, $csv, $category, $href, $pause);
}

// Разбираем страницу товара и отправляем на запись (Koko Vari Maku)
function parseGoodKokoVariMaku($href, $category) {
	global $pause;
	$html = Request::curl($href, $pause);
	$dom = phpQuery::newDocument($html);
	foreach (getParams($dom, 'Koko') as $koko) {
		foreach (getParams($dom, 'Väri') as $vari) {
			foreach (getParams($dom, 'Maku') as $maku) {
				$data = [
					'product_id' => getIdent($html),
					'category' => trim($dom->find('a.BreadcrumbItem')->eq(1)->text()),
					'title' => trim($dom->find('span.name')->text()),
					'season' => getSeason(trim($dom->find('span.name')->text())),
					'price' => trim($dom->find('span.price-value>span')->attr('content')),
					'price_without_discount' => str_replace(['€', ',', ' '], ['', '.', ''], $dom->find('span.LineThrough')->text()),
					'product_type' => trim($dom->find('a.BreadcrumbItem')->eq(1)->text()),
					'product_age' => getProductAge(trim($dom->find('span.name')->text())),
					'manufacturer' => getManufacturer(trim($dom->find('span.name')->text())),
					'availableurl' => $href,
					'koko' => $koko,
					'vari' => $vari,
					'maku' => $maku,
					'updated' => 0,
				];
				$csv = Writer::insertOrUpdateCSV($data, [
					'availableurl',
					'koko',
					'vari',
					'maku',
				], $category/*, ['updated', 'product_id']*/);
				switch ($csv) {
					case 1:
						Logger::send("|RECORD|ADD| - Товар ".$data['title']." добавлен");
						break;
					case 2:
						Logger::send("|RECORD|UPDATE| - Товар ".$data['title']." обновлен");
						break;
				}
			}
		}
	}
	//Writer::deleteNoUpdatedCSV($data['product_id'], 'product_id', 'updated', $category);
	Writer::eraseAttachInIdFolder($data['product_id']);
	Writer::saveOnUpdateImages($data['product_id'], getPhotos($dom));
	$dom->unloadDocument();
	unset($html, $dom, $data, $csv, $category, $href, $pause);
}

// Разбираем страницу товара и отправляем на запись (Koko Vari Pouli)
function parseGoodKokoVariPouli($href, $category) {
	global $pause;
	$html = Request::curl($href, $pause);
	$dom = phpQuery::newDocument($html);
	foreach (getParams($dom, 'Koko') as $koko) {
		foreach (getParams($dom, 'Väri') as $vari) {
			foreach (getParams($dom, 'Puoli') as $puoli) {
				$data = [
					'product_id' => getIdent($html),
					'category' => trim($dom->find('a.BreadcrumbItem')->eq(1)->text()),
					'title' => trim($dom->find('span.name')->text()),
					'season' => getSeason(trim($dom->find('span.name')->text())),
					'price' => trim($dom->find('span.price-value>span')->attr('content')),
					'price_without_discount' => str_replace(['€', ',', ' '], ['', '.', ''], $dom->find('span.LineThrough')->text()),
					'product_type' => trim($dom->find('a.BreadcrumbItem')->eq(1)->text()),
					'product_age' => getProductAge(trim($dom->find('span.name')->text())),
					'manufacturer' => getManufacturer(trim($dom->find('span.name')->text())),
					'availableurl' => $href,
					'koko' => $koko,
					'vari' => $vari,
					'puoli' => $puoli,
					'updated' => 0,
				];
				$csv = Writer::insertOrUpdateCSV($data, [
					'availableurl',
					'koko',
					'vari',
					'puoli',
				], $category/*, ['updated', 'product_id']*/);
				switch ($csv) {
					case 1:
						Logger::send("|RECORD|ADD| - Товар ".$data['title']." добавлен");
						break;
					case 2:
						Logger::send("|RECORD|UPDATE| - Товар ".$data['title']." обновлен");
						break;
				}
			}
		}
	}
	//Writer::deleteNoUpdatedCSV($data['product_id'], 'product_id', 'updated', $category);
	Writer::eraseAttachInIdFolder($data['product_id']);
	Writer::saveOnUpdateImages($data['product_id'], getPhotos($dom));
	$dom->unloadDocument();
	unset($html, $dom, $data, $csv, $category, $href, $pause);
}

// Разбираем страницу товара и отправляем на запись (Vari)
function parseGoodVari($href, $category) {
	global $pause;
	$html = Request::curl($href, $pause);
	$dom = phpQuery::newDocument($html);
	foreach (getParams($dom, 'Väri') as $vari) {
		$data = [
			'product_id' => getIdent($html),
			'category' => trim($dom->find('a.BreadcrumbItem')->eq(1)->text()),
			'title' => trim($dom->find('span.name')->text()),
			'season' => getSeason(trim($dom->find('span.name')->text())),
			'price' => trim($dom->find('span.price-value>span')->attr('content')),
			'price_without_discount' => str_replace(['€', ',', ' '], ['', '.', ''], $dom->find('span.LineThrough')->text()),
			'product_type' => trim($dom->find('a.BreadcrumbItem')->eq(1)->text()),
			'product_age' => getProductAge(trim($dom->find('span.name')->text())),
			'manufacturer' => getManufacturer(trim($dom->find('span.name')->text())),
			'availableurl' => $href,
			'vari' => $vari,
			'updated' => 0,
		];
		$csv = Writer::insertOrUpdateCSV($data, [
			'availableurl',
			'vari',
		], $category/*, ['updated', 'product_id']*/);
		switch ($csv) {
			case 1:
				Logger::send("|RECORD|ADD| - Товар ".$data['title']." добавлен");
				break;
			case 2:
				Logger::send("|RECORD|UPDATE| - Товар ".$data['title']." обновлен");
				break;
		}
	}
	//Writer::deleteNoUpdatedCSV($data['product_id'], 'product_id', 'updated', $category);
	Writer::eraseAttachInIdFolder($data['product_id']);
	Writer::saveOnUpdateImages($data['product_id'], getPhotos($dom));
	$dom->unloadDocument();
	unset($html, $dom, $data, $csv, $category, $href, $pause);
}

// Разбираем страницу товара и отправляем на запись (Koko Maku)
function parseGoodKokoMaku($href, $category) {
	global $pause;
	$html = Request::curl($href, $pause);
	$dom = phpQuery::newDocument($html);
	foreach (getParams($dom, 'Koko') as $koko) {
		foreach (getParams($dom, 'Maku') as $maku) {
			$data = [
				'product_id' => getIdent($html),
				'category' => trim($dom->find('a.BreadcrumbItem')->eq(1)->text()),
				'title' => trim($dom->find('span.name')->text()),
				'season' => getSeason(trim($dom->find('span.name')->text())),
				'price' => trim($dom->find('span.price-value>span')->attr('content')),
				'price_without_discount' => str_replace(['€', ',', ' '], ['', '.', ''], $dom->find('span.LineThrough')->text()),
				'product_type' => trim($dom->find('a.BreadcrumbItem')->eq(1)->text()),
				'product_age' => getProductAge(trim($dom->find('span.name')->text())),
				'manufacturer' => getManufacturer(trim($dom->find('span.name')->text())),
				'availableurl' => $href,
				'koko' => $koko,
				'maku' => $maku,
				'updated' => 0,
			];
			$csv = Writer::insertOrUpdateCSV($data, [
				'availableurl',
				'koko',
				'maku',
			], $category/*, ['updated', 'product_id']*/);
			switch ($csv) {
				case 1:
					Logger::send("|RECORD|ADD| - Товар ".$data['title']." добавлен");
					break;
				case 2:
					Logger::send("|RECORD|UPDATE| - Товар ".$data['title']." обновлен");
					break;
			}
		}
	}
	//Writer::deleteNoUpdatedCSV($data['product_id'], 'product_id', 'updated', $category);
	Writer::eraseAttachInIdFolder($data['product_id']);
	Writer::saveOnUpdateImages($data['product_id'], getPhotos($dom));
	$dom->unloadDocument();
	unset($html, $dom, $data, $csv, $category, $href, $pause);
}

// Получаем линки объявлений со страницы
function parsPage($link, $page, $category, $size = 500) {
	global $pause;
	$html = Request::curl($link.'&PageSize='.$size.'&Page='.$page, $pause);
	$dom = phpQuery::newDocument($html);
	$goods = $dom->find('h3.TopPaddingWide>a');
	if (count($goods) < 1) {
		$dom->unloadDocument();
		Logger::send("|GOODS|ERROR| - Не удалось получить список товаров");
		return false;
	}
	$hrefs = [];
	foreach ($goods as $good) {
		$hrefs[] = 'https://www.hockeyunlimited.fi/epages/hockeyunlimited.sf/fi_FI/'.pq($good)->attr('href');
	}
	$dom->unloadDocument();
	if (empty($hrefs)) {
		$dom->unloadDocument();
		Logger::send("|GOODS|ERROR| - Не удалось получить список товаров");
		return false;
	}
	unset($html, $dom, $goods);
	foreach ($hrefs as $href) {
		if (
			$category == 'Jääkiekkoluistimet' or
			$category == 'Jääkiekkohartiasuojat' or
			$category == 'Kyynärpääsuojat' or
			$category == 'Polvisuojat' or
			$category == 'Alasuojat' or
			$category == 'Fanituotteet'
		) {
			parseGoodKoko($href, $category);
		} elseif ($category == 'Jääkiekkomailat') {
			parseGoodFlexKatisyys($href, $category);
		} elseif (
			$category == 'Jääkiekkokypärät' or
			$category == 'Jääkiekkohanskat' or
			$category == 'Jääkiekkohousut' or
			$category == 'Rullakiekko' or
			$category == 'Erotuomarit' or
			$category == 'Tekstiilit' or
			$category == 'Tekniset asusteet' or
			$category == 'Varustekassit' or
			$category == 'VAPAA-AJAN TUOTTEET'
		) {
			parseGoodKokoVari($href, $category);
		} elseif ($category == 'Maalivahdin varusteet') {
			parseGoodKokoVariFlexKatisyys($href, $category);
		} elseif ($category == 'Jääkiekon oheistarvikkeet') {
			parseGoodKokoVariPituus($href, $category);
		} elseif ($category == 'Taitoluistelu') {
			parseGoodKokoVariElainPituus($href, $category);
		} elseif ($category == 'Ringette') {
			parseGoodKokoVariMaku($href, $category);
		} elseif ($category == 'Salibandy') {
			parseGoodKokoVariPouli($href, $category);
		} elseif (
			$category == 'Oheisharjoittelu' or
			$category == 'TEIPIT' or
			$category == 'FING SPINNER'
		) {
			parseGoodVari($href, $category);
		} elseif ($category == 'Kaukalopallo') {
			parseGoodKokoMaku($href, $category);
		} else {
			break;
		}
		CSV::$connect = null;
	}
}

foreach (getCategories() as $name => $link) {
	$pages = getPagesCount($link);
	for ($p = 1; $p <= $pages; $p++) {
		Logger::send("|CATEGORY|WAIT| - Категория: $name. Страница: $p из $pages");
		parsPage($link, $p, $name);
	}
	Logger::send("|CATEGORY|FINISH| - Парсинг из категории $name завершен");
}

Logger::send("|FINISH|SUCCESS| - Работа скрипта завершена. Парсинг из ".PARSER_NAME);