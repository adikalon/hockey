<?php
require __DIR__.'/../core.php';

Logger::send("|START|SUCCESS| - Script is started. Parsing of ".PARSER_NAME);

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
	$html = Request::curl('https://www.hockeyunlimited.fi/', 5);
	$dom = phpQuery::newDocument($html);
	$links = $dom->find('#NavBarElementID2266322 a');
	$res = [];
	foreach ($links as $link) {
		if (isBlackCat(pq($link)->attr('href'))) continue;
		$res[trim(pq($link)->text())] = 'https://www.hockeyunlimited.fi/epages/hockeyunlimited.sf/fi_FI/'.pq($link)->attr('href');
	}
	$dom->unloadDocument();
	if (empty($res)) {
		Logger::send("|CATEGORIES|ERROR| - Could not create a list of categories. The script is stopped");
		exit();
	}
	return $res;
}

// Получаем кол-во страниц
function getPagesCount($link, $size = 500) {
	$html = Request::curl($link.'&PageSize='.$size.'&Page=1', 5);
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
	return max($pages);
}

// Получаем сезон
function getSeason($string) {
	preg_match('/.*S(\d\d).*/', $string, $match);
	if (isset($match[1]) and !empty($match[1])) {
		return '20'.$match[1];
	}
	return '';
}

// Получаем Product Age
function getProductAge($string) {
	preg_match('/.*(junior|senior|youth).*/', strtolower($string), $match);
	if (isset($match[1]) and !empty($match[1])) {
		return $match[1];
	}
	return '';
}

// Получаем название производителя
function getManufacturer($string) {
	preg_match('/.*(CCM|Bauer).*/', $string, $match);
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
	return $params;
}

// Получаем id товара
function getIdent($html) {
	preg_match("/.*objectId:\s'(\d+)'.*/", $html, $match);
	return trim($match[1]);
}

// Получаем фото товара
function getPhotos($dom) {
	$imgs = [];
	$results = $dom->find('img[data-src-l]');
	foreach ($results as $res) {
		$imgs[] = 'https://www.hockeyunlimited.fi'.trim(pq($res)->attr('data-src-l'));
	}
	return $imgs;
}

// Разбираем страницу товара и отправляем на запись
function parseGood($href) {
	$html = Request::curl($href, 5);
	$dom = phpQuery::newDocument($html);
	foreach (getParams($dom, 'Koko') as $koko) {
		foreach (getParams($dom, 'Flex') as $flex) {
			foreach (getParams($dom, 'Kätisyys') as $katisyys) {
				foreach (getParams($dom, 'Väri') as $vari) {
					foreach (getParams($dom, 'Pituus') as $pituus) {
						foreach (getParams($dom, 'Eläin') as $elain) {
							foreach (getParams($dom, 'Maku') as $maku) {
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
										'flex' => $flex,
										'katisyys' => $katisyys,
										'vari' => $vari,
										'pituus' => $pituus,
										'elain' => $elain,
										'maku' => $maku,
										'puoli' => $puoli,
										'updated' => 0,
									];
									$csv = Writer::insertOrUpdateCSV($data, [
										'availableurl',
										'koko',
										'flex',
										'katisyys',
										'vari',
										'pituus',
										'elain',
										'maku',
										'puoli',
									]/*, ['updated', 'product_id']*/);
									switch ($csv) {
										case 1:
											Logger::send("|RECORD|ADD| - Good ".$data['title']." added in CSV");
											break;
										case 2:
											Logger::send("|RECORD|UPDATE| - Good ".$data['title']." updated in CSV");
											break;
									}
									$mysql = Writer::insertOrUpdate($data, [
										'availableurl',
										'koko',
										'flex',
										'katisyys',
										'vari',
										'pituus',
										'elain',
										'maku',
										'puoli',
									], ['updated', 'product_id']);
									switch ($mysql) {
										case 1:
											Logger::send("|RECORD|ADD| - Good ".$data['title']." added in MySQL");
											break;
										case 2:
											Logger::send("|RECORD|UPDATE| - Good ".$data['title']." updated in MySQL");
											break;
									}
								}
							}
						}
					}
				}
			}
		}
	}
	Writer::deleteNoUpdated($data['product_id'], 'product_id', 'updated');
	//Writer::deleteNoUpdatedCSV($data['product_id'], 'product_id', 'updated');
	Writer::eraseAttachInIdFolder($data['product_id']);
	Writer::saveOnUpdateImages($data['product_id'], getPhotos($dom));
	$dom->unloadDocument();
}

// Получаем линки объявлений со страницы
function parsPage($link, $page, $size = 500) {
	$html = Request::curl($link.'&PageSize='.$size.'&Page='.$page, 5);
	$dom = phpQuery::newDocument($html);
	$goods = $dom->find('h3.TopPaddingWide>a');
	if (count($goods) < 1) {
		$dom->unloadDocument();
		Logger::send("|GOODS|ERROR| - Failed to get list of products");
		return false;
	}
	$hrefs = [];
	foreach ($goods as $good) {
		$hrefs[] = 'https://www.hockeyunlimited.fi/epages/hockeyunlimited.sf/fi_FI/'.pq($good)->attr('href');
	}
	$dom->unloadDocument();
	if (empty($hrefs)) {
		$dom->unloadDocument();
		Logger::send("|GOODS|ERROR| - Failed to get list of products");
		return false;
	}
	foreach ($hrefs as $href) {
		parseGood($href);
	}
}

foreach (getCategories() as $name => $link) {
	$pages = getPagesCount($link);
	for ($p = 1; $p <= $pages; $p++) {
		Logger::send("|CATEGORY|WAIT| - Category: $name. Page: $p of $pages");
		parsPage($link, $p);
	}
	Logger::send("|CATEGORY|FINISH| - Bypassing the $name category is complete");
}

Logger::send("|FINISH|SUCCESS| - Script has successfully worked. Parsing of ".PARSER_NAME);