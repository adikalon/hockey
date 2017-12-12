<?php

$pause = 1;

require __DIR__.'/../core.php';

Logger::send("|START|SUCCESS| - Скрипт запущен. Парсинг из ".PARSER_NAME);

// Список категорий
$categories = [
	[
		'name' => 'Urheilutekstiili',
		'type' => 'Lapset & nuoret',
		'link' => 'https://www.karkkainen.com/verkkokauppa/urheilutekstiili/lapset---nuoret',
	],
	[
		'name' => 'Urheilutekstiili',
		'type' => 'Unisex',
		'link' => 'https://www.karkkainen.com/verkkokauppa/urheilutekstiili/unisex-24257501',
	],
	[
		'name' => 'Urheilutekstiili',
		'type' => 'Miehet',
		'link' => 'https://www.karkkainen.com/verkkokauppa/urheilutekstiili/miehet-24302001',
	],
	[
		'name' => 'Urheilutekstiili',
		'type' => 'Naiset',
		'link' => 'https://www.karkkainen.com/verkkokauppa/urheilutekstiili/naiset-24331501',
	],
	[
		'name' => 'Urheilutekstiili',
		'type' => 'Kengät',
		'link' => 'https://www.karkkainen.com/verkkokauppa/urheilutekstiili/kengat',
	],
	[
		'name' => 'Talviurheilu',
		'type' => 'Lautailu',
		'link' => 'https://www.karkkainen.com/verkkokauppa/talviurheilu/lautailu',
	],
	[
		'name' => 'Talviurheilu',
		'type' => 'Laskettelu',
		'link' => 'https://www.karkkainen.com/verkkokauppa/talviurheilu/laskettelu',
	],
	[
		'name' => 'Talviurheilu',
		'type' => 'Lasketteluvarusteet',
		'link' => 'https://www.karkkainen.com/verkkokauppa/talviurheilu/lasketteluvarusteet',
	],
	[
		'name' => 'Talviurheilu',
		'type' => 'Jääurheilu',
		'link' => 'https://www.karkkainen.com/verkkokauppa/talviurheilu/jaaurheilu',
	],
	[
		'name' => 'Talviurheilu',
		'type' => 'Hiihtourheilu',
		'link' => 'https://www.karkkainen.com/verkkokauppa/talviurheilu/hiihtourheilu',
	],
	[
		'name' => 'Sisä- ja kuntourheilu',
		'type' => 'Sykemittarit ja aktiivisuusrannekkeet',
		'link' => 'https://www.karkkainen.com/verkkokauppa/sisa--ja-kuntourheilu/sykemittarit-ja-aktiivisuusrannekkeet',
	],
	[	
		'name' => 'Sisä- ja kuntourheilu',
		'type' => 'Urheilujuomat ja lisäravinteet',
		'link' => 'https://www.karkkainen.com/verkkokauppa/sisa--ja-kuntourheilu/urheilujuomat-ja-lisaravinteet',
	],
	[	
		'name' => 'Sisä- ja kuntourheilu',
		'type' => 'Kuntourheilu',
		'link' => 'https://www.karkkainen.com/verkkokauppa/sisa--ja-kuntourheilu/kuntourheilu',
	],
	[	
		'name' => 'Sisä- ja kuntourheilu',
		'type' => 'Pöytätennis',
		'link' => 'https://www.karkkainen.com/verkkokauppa/sisa--ja-kuntourheilu/poytatennis-pingis',
	],
	[	
		'name' => 'Sisä- ja kuntourheilu',
		'type' => 'Lentopallo',
		'link' => 'https://www.karkkainen.com/verkkokauppa/sisa--ja-kuntourheilu/lentopallo',
	],
	[	
		'name' => 'Sisä- ja kuntourheilu',
		'type' => 'Salibandy',
		'link' => 'https://www.karkkainen.com/verkkokauppa/sisa--ja-kuntourheilu/salibandy',
	],
	[	
		'name' => 'Sisä- ja kuntourheilu',
		'type' => 'Koripallo',
		'link' => 'https://www.karkkainen.com/verkkokauppa/sisa--ja-kuntourheilu/koripallo',
	],
	[	
		'name' => 'Sisä- ja kuntourheilu',
		'type' => 'Sulkapallo',
		'link' => 'https://www.karkkainen.com/verkkokauppa/sisa--ja-kuntourheilu/sulkapallo',
	],
	[	
		'name' => 'Sisä- ja kuntourheilu',
		'type' => 'Squash',
		'link' => 'https://www.karkkainen.com/verkkokauppa/sisa--ja-kuntourheilu/squash',
	],
	[	
		'name' => 'Pyöräily',
		'type' => 'Pyörät',
		'link' => 'https://www.karkkainen.com/verkkokauppa/pyoraily/pyorat',
	],
	[	
		'name' => 'Pyöräily',
		'type' => 'Renkaat',
		'link' => 'https://www.karkkainen.com/verkkokauppa/pyoraily/renkaat-rengas',
	],
	[	
		'name' => 'Pyöräily',
		'type' => 'Pyörätarvikkeet ja -varusteet',
		'link' => 'https://www.karkkainen.com/verkkokauppa/pyoraily/pyoratarvikkeet-ja--varusteet',
	],
	[	
		'name' => 'Pyöräily',
		'type' => 'Pyöräilytekstiilit ja -varusteet',
		'link' => 'https://www.karkkainen.com/verkkokauppa/pyoraily/pyorailytekstiilit-ja--varusteet',
	],
	[	
		'name' => 'Kesälajit',
		'type' => 'Pesäpallo',
		'link' => 'https://www.karkkainen.com/verkkokauppa/kesalajit/pesapallo',
	],
	[	
		'name' => 'Kesälajit',
		'type' => 'Frisbeegolf',
		'link' => 'https://www.karkkainen.com/verkkokauppa/kesalajit/frisbeegolf',
	],
	[	
		'name' => 'Kesälajit',
		'type' => 'Rullalautailu ja -luistelu',
		'link' => 'https://www.karkkainen.com/verkkokauppa/kesalajit/rullalautailu-ja--luistelu',
	],
	[	
		'name' => 'Kesälajit',
		'type' => 'Golf',
		'link' => 'https://www.karkkainen.com/verkkokauppa/kesalajit/golf',
	],
	[	
		'name' => 'Kesälajit',
		'type' => 'Katukiekko',
		'link' => 'https://www.karkkainen.com/verkkokauppa/kesalajit/katukiekko',
	],
	[	
		'name' => 'Kesälajit',
		'type' => 'Yleisurheilu',
		'link' => 'https://www.karkkainen.com/verkkokauppa/kesalajit/yleisurheilu',
	],
	[	
		'name' => 'Kesälajit',
		'type' => 'Tennis',
		'link' => 'https://www.karkkainen.com/verkkokauppa/kesalajit/tennis',
	],
	[
		'name' => 'Kesälajit',
		'type' => 'Jalkapallo',
		'link' => 'https://www.karkkainen.com/verkkokauppa/kesalajit/jalkapallo',
	],
	[
		'name' => 'Kesälajit',
		'type' => 'Vesiurheilu',
		'link' => 'https://www.karkkainen.com/verkkokauppa/kesalajit/vesiurheilu',
	],
	[
		'name' => 'Kesälajit',
		'type' => 'Kesäpelit',
		'link' => 'https://www.karkkainen.com/verkkokauppa/kesalajit/kesapelit',
	]
];

// Инфа о категории
function getCatInfo($category) {
	global $pause;
	$storeId = 'unknown';
	$catalogId = 'unknown';
	$categoryId = 'unknown';
	$parentCategoryId = 'unknown';
	$html = Request::curl($category, $pause);
	preg_match('/.*storeId:\s*(\d+),.*/', $html, $match);
	if (isset($match[1]) and !empty($match[1])) {
		$storeId = $match[1];
	}
	preg_match('/.*catalogId:\s*(\d+),.*/', $html, $match);
	if (isset($match[1]) and !empty($match[1])) {
		$catalogId = $match[1];
	}
	preg_match('/.*categoryId:\s*(\d+),.*/', $html, $match);
	if (isset($match[1]) and !empty($match[1])) {
		$categoryId = $match[1];
	}
	preg_match('/.*parentCategoryId:\s*(\d+),.*/', $html, $match);
	if (isset($match[1]) and !empty($match[1])) {
		$parentCategoryId = $catalogId.'_'.$match[1];
	}
	return [
		'storeId' => $storeId,
		'catalogId' => $catalogId,
		'categoryId' => $categoryId,
		'parentCategoryId' => $parentCategoryId,
	];
}

// Получаем сезон
function getSeason($string) {
	preg_match('/.*S(\d\d).*/', $string, $match);
	if (isset($match[1]) and !empty($match[1])) {
		return $match[1];
	}
	return '';
}

// Получаем цену без скидки
function getPriceWithDisc($string) {
	preg_match('/.*class="original-price-value">([0-9,]+).*<\/span>.*/', $string, $match);
	if (isset($match[1]) and !empty($match[1])) {
		return str_replace(',', '.', $match[1]);
	}
	return '';
}

// Получаем Product Age
function getProductAge($string) {
	preg_match('/.*(junior|senior|youth).*/', strtolower($string), $match);
	if (isset($match[1]) and !empty($match[1])) {
		return trim($match[1]);
	}
	return '';
}

// Получаем название производителя
function getManufacturer($string) {
	preg_match('/(.+)[-_\s].*\.(?:png|jpg|jpeg|gif|bmp)/', $string, $match);
	if (isset($match[1]) and !empty($match[1])) {
		return trim($match[1]);
	}
	return '';
}

// Получаем фото товара
function getPhotos($images) {
	$imgs = [];
	foreach ($images as $image) {
		$imgs[] = 'https://www.karkkainen.com/tuotekuva/ZOOM/'.$image->image;
	}
	return $imgs;
}

// Получаем параметры
function getParams($link, $name) {
	global $pause;
	$params = [];
	$html = Request::curl($link, $pause);
	$dom = phpQuery::newDocument($html);
	$options = $dom->find("[data-name='$name']")->parent()->find('button');
	if (count($options) < 1) {
		$params[] = '';
	} else {
		foreach ($options as $param) {
			$params[] = trim(pq($param)->text());
		}
	}
	$dom->unloadDocument();
	return $params;
}

// Оотправляем на запись
function goodRecord($good, $category) {
	global $pause;
	$link = 'https://www.karkkainen.com/verkkokauppa/'.$good->seoToken;
	foreach (getParams($link, 'Koko') as $koko) {
		foreach (getParams($link, 'Väri') as $vari) {
			foreach (getParams($link, 'Päähineen koko') as $paahineen_koko) {
				foreach (getParams($link, 'Pituus, cm') as $pituus_cm) {
					foreach (getParams($link, 'Sidetyyppi') as $sidetyyppi) {
						foreach (getParams($link, 'Maku') as $maku) {
							foreach (getParams($link, 'unit-of-measure') as $unit_of_measure) {
								foreach (getParams($link, 'Vaihteiden määrä') as $vaihteiden_maara) {
									foreach (getParams($link, 'Kätisyys') as $katisyys) {
										$data = [
											'product_id' => str_replace(['.jpg', '.jpeg', '.png', '.gif', '.bmp'], '',$good->mainImage),
											'category' => $category['name'],
											'title' => $good->name,
											'season' => getSeason($good->name),
											'price' => $good->items[0]->quantityPrices->{'1.0'}->price,
											'price_without_discount' => getPriceWithDisc($good->priceHTML),
											'product_type' => $category['type'],
											'product_age' => getProductAge($good->name),
											'manufacturer' => getManufacturer($good->brandLogo),
											'availableurl' => $link,
											'koko' => $koko,
											'vari' => $vari,
											'paahineen_koko' => $paahineen_koko,
											'pituus_cm' => $pituus_cm,
											'sidetyyppi' => $sidetyyppi,
											'maku' => $maku,
											'unit_of_measure' => $unit_of_measure,
											'vaihteiden_maara' => $vaihteiden_maara,
											'katisyys' => $katisyys,
											'updated' => 0,
										];
										$csv = Writer::insertOrUpdateCSV($data, [
											'availableurl',
											'koko',
											'paahineen_koko',
											'pituus_cm',
											'sidetyyppi',
											'maku',
											'unit_of_measure',
											'vaihteiden_maara',
											'katisyys',
										]/*, ['updated', 'product_id']*/);
										switch ($csv) {
											case 1:
												Logger::send("|RECORD|ADD| - Товар ".$data['title']." добавлен в CSV");
												break;
											case 2:
												Logger::send("|RECORD|UPDATE| - Товар ".$data['title']." обновлен в CSV");
												break;
										}
										$mysql = Writer::insertOrUpdate($data, [
											'availableurl',
											'koko',
											'paahineen_koko',
											'pituus_cm',
											'sidetyyppi',
											'maku',
											'unit_of_measure',
											'vaihteiden_maara',
											'katisyys',
										], ['updated', 'product_id']);
										switch ($mysql) {
											case 1:
												Logger::send("|RECORD|ADD| - Товар ".$data['title']." добавлен в MySQL");
												break;
											case 2:
												Logger::send("|RECORD|UPDATE| - Товар ".$data['title']." обновлен в MySQL");
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
	}
	Writer::deleteNoUpdated($data['product_id'], 'product_id', 'updated');
	//Writer::deleteNoUpdatedCSV($data['product_id'], 'product_id', 'updated');
	Writer::eraseAttachInIdFolder($data['product_id']);
	Writer::saveOnUpdateImages($data['product_id'], getPhotos($good->images));
}

// Ходим по страницам категории
function pagesWalk($category) {
	global $pause;
	Logger::send("|CATEGORY|WAIT| - Категория: ".$category['name']);
	$beginIndex = 0;
	$info = getCatInfo($category['link']);
	while (true) {
		$options = [
			CURLOPT_POST => true,
			CURLOPT_POSTFIELDS => 'storeId='.$info['storeId'].'&catalogId='.$info['catalogId'].'&langId=-11&searchTerm=&categoryCatalogId=&categoryId='.$info['categoryId'].'&parentCategoryId='.$info['parentCategoryId'].'&minPrice=&maxPrice=&pageSize=20&beginIndex='.$beginIndex.'&orderBy=&ajaxRequest=true&searchParams=ajaxRequest%3Atrue%7CbeginIndex%3A'.$beginIndex.'%7CcatalogId%3A'.$info['catalogId'].'%7CcategoryCatalogId%3Anull%7CcategoryId%3A'.$info['categoryId'].'%7Cfacet%3A%7ClangId%3A-11%7CmaxPrice%3A%7CminPrice%3A%7CorderBy%3A%7CpageSize%3A20%7CparentCategoryId%3A'.$info['parentCategoryId'].'%7CsearchTerm%3Anull%7CstoreId%3A'.$info['storeId'].'%7C',
			CURLOPT_HTTPHEADER => [
				"Accept: application/json, text/javascript, */*; q=0.01",
				"X-Requested-With: XMLHttpRequest"
			]
		];
		$html = Request::curl('https://www.karkkainen.com/verkkokauppa/urheilutekstiili/AjaxFacetedGrid', $pause, $options);
		$html = json_decode($html);
		$products = $html->products;
		if (count($products) < 1) {
			Logger::send("|CATEGORY|FINISH| - Парсинг из категории ".$category['name']." завершен");
			break;
		}
		foreach ($products as $product) {
			goodRecord($product, $category);
		}
		$beginIndex += 20;
	}
}

foreach ($categories as $category) {
	pagesWalk($category);
}