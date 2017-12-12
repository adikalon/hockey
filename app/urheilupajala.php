<?php

$pause = 5;

require __DIR__.'/../core.php';

Logger::send("|START|SUCCESS| - Скрипт запущен. Парсинг из ".PARSER_NAME);

// Проверяем не находится ли категория в блэк листе
function isBlackCat($link) {
	$categories = [
		'/tuoteryhma/joulukalenteri-859684',
		'/tuoteryhma/lahjakortit',
		'/tuoteryhma/yrityksille-ja-seuroille',
	];
	return in_array($link, $categories);
}

// Проверяем не наличие ключевого слова в ссылке категории
function nonKeyCat($link) {
	if (strpos($link, 'tuoteryhma') !== false) {
		return false;
	}
	return true;
}

// Получаем массив ссылок на категории
function getCategories() {
	global $pause;
	$html = Request::curl('http://urheilupajala.fi/', $pause);
	$dom = phpQuery::newDocument($html);
	$links = $dom->find('li a');
	$res = [];
	foreach ($links as $link) {
		if (isBlackCat(pq($link)->attr('href'))) continue;
		if (nonKeyCat(pq($link)->attr('href'))) continue;
		$res[trim(pq($link)->text())] = 'http://urheilupajala.fi'.pq($link)->attr('href');
	}
	$dom->unloadDocument();
	if (empty($res)) {
		Logger::send("|CATEGORIES|ERROR| - Не удалось создать список категорий. Скрипт остановлен");
		exit();
	}
	return $res;
}

// Возвращает false, если страница отсутствует
function isPage($category, $page, $size = 500) {
	global $pause;
	$html = Request::curl($category.'?form_token=sort&product_amout='.$size.'&sort-by=created-ascending&page='.$page, $pause);
	if (strpos($html, 'product-caption') !== false) {
		return true;
	}
	return false;
}

// Получаем id товара
function getIdent($html) {
	preg_match('/.*cdn\.finqu\.com\/users.+product\/(\d+)-.*/', $html, $match);
	if (isset($match[1]) and !empty($match[1])) {
		return trim($match[1]);
	}
	return '';
}

// Получаем название товара
function getName($html) {
	preg_match('/.*<meta\sitemprop="name"\scontent="(.*)">.*/u', $html, $match);
	if (isset($match[1]) and !empty($match[1])) {
		return trim($match[1]);
	}
	return '';
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
		return trim($match[1]);
	}
	return '';
}

// Получаем название производителя
function getManufacturer($string) {
	preg_match('/.*<meta\s+property="product:brand"\s+content="(.*)">.*/', $string, $match);
	if (isset($match[1]) and !empty($match[1])) {
		return trim($match[1]);
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
			if (stristr(pq($param)->text(), 'Valitse') === false) {
				$params[] = trim(preg_replace('/\s+/', ' ', pq($param)->text()));
			}
		}
	}
	return $params;
}

// Получаем фото товара
function getPhotos($dom) {
	$imgs = [];
	$results = $dom->find('span.product-image-object');
	foreach ($results as $res) {
		$imgs[] = 'http:'.trim(pq($res)->attr('data-zoom-image'));
	}
	return $imgs;
}

// Разбираем страницу товара и отправляем на запись
function parseGood($href) {
	global $pause;
	$html = Request::curl($href, $pause);
	if (strpos($html, 'Tuote on tilapäisesti loppunut') !== false) {
		Writer::deleteGoods('product_id', getIdent($html));
		return null;
	}
	$dom = phpQuery::newDocument($html);
	foreach (getParams($dom, 'Suksen mitta') as $suksen_mitta) {
		foreach (getParams($dom, 'Koko (EU)') as $koko_eu) {
			foreach (getParams($dom, 'Koko') as $koko) {
				foreach (getParams($dom, 'Sauvan mitta') as $sauvan_mitta) {
					foreach (getParams($dom, 'Väri') as $vari) {
						foreach (getParams($dom, 'Kengän numero (EU)') as $kengan_numero_eu) {
							foreach (getParams($dom, 'Väri ja koko') as $vari_ja_koko) {
								foreach (getParams($dom, 'Otekorkeus') as $otekorkeus) {
									foreach (getParams($dom, 'Tuumakoko') as $tuumakoko) {
										foreach (getParams($dom, 'Kätisyys') as $katisyys) {
											foreach (getParams($dom, 'Väri ja Runkokoko') as $vari_ja_runkokoko) {
												foreach (getParams($dom, 'Runkokoko') as $runkokoko) {
													foreach (getParams($dom, 'Väri ja paino') as $vari_ja_paino) {
														foreach (getParams($dom, 'Väri ja kätisyys') as $vari_ja_katisyys) {
															foreach (getParams($dom, 'Pituus ja paino') as $pituus_ja_paino) {
																foreach (getParams($dom, 'Kätisyys, lapa, jäykkyys') as $katisyys_lapa_jaykkyys) {
																	foreach (getParams($dom, 'Kypärän Koko') as $kyparan_koko) {
																		foreach (getParams($dom, 'VOITELU') as $voitelu) {
																			$data = [
																				'product_id' => getIdent($html),
																				'category' => trim($dom->find('li.active')->eq(0)->find('a')->text()),
																				'title' => getName($html),
																				'season' => getSeason(getName($html)),
																				'price' => str_replace(['€', ',', ' '], ['', '.', ''], $dom->find('span.js-product-price')->text()),
																				'price_without_discount' => str_replace(['€', ',', ' '], ['', '.', ''], $dom->find('span.js-product-original-price')->text()),
																				'product_type' => trim($dom->find('li.active')->eq(0)->find('a')->text()),
																				'product_age' => getProductAge(getName($html)),
																				'manufacturer' => getManufacturer($html),
																				'availableurl' => $href,
																				'suksen_mitta' => $suksen_mitta,
																				'koko_eu' => $koko_eu,
																				'koko' => $koko,
																				'sauvan_mitta' => $sauvan_mitta,
																				'vari' => $vari,
																				'kengan_numero_eu' => $kengan_numero_eu,
																				'vari_ja_koko' => $vari_ja_koko,
																				'otekorkeus' => $otekorkeus,
																				'tuumakoko' => $tuumakoko,
																				'katisyys' => $katisyys,
																				'vari_ja_runkokoko' => $vari_ja_runkokoko,
																				'runkokoko' => $runkokoko,
																				'vari_ja_paino' => $vari_ja_paino,
																				'vari_ja_katisyys' => $vari_ja_katisyys,
																				'pituus_ja_paino' => $pituus_ja_paino,
																				'katisyys_lapa_jaykkyys' => $katisyys_lapa_jaykkyys,
																				'kyparan_koko' => $kyparan_koko,
																				'voitelu' => $voitelu,
																				'updated' => 0,
																			];
																			$csv = Writer::insertOrUpdateCSV($data, [
																				'availableurl',
																				'suksen_mitta',
																				'koko_eu',
																				'koko',
																				'sauvan_mitta',
																				'vari',
																				'kengan_numero_eu',
																				'vari_ja_koko',
																				'otekorkeus',
																				'tuumakoko',
																				'katisyys',
																				'vari_ja_runkokoko',
																				'vari_ja_paino',
																				'vari_ja_katisyys',
																				'pituus_ja_paino',
																				'katisyys_lapa_jaykkyys',
																				'kyparan_koko',
																				'voitelu',
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
																				'suksen_mitta',
																				'koko_eu',
																				'koko',
																				'sauvan_mitta',
																				'vari',
																				'kengan_numero_eu',
																				'vari_ja_koko',
																				'otekorkeus',
																				'tuumakoko',
																				'katisyys',
																				'vari_ja_runkokoko',
																				'vari_ja_paino',
																				'vari_ja_katisyys',
																				'pituus_ja_paino',
																				'katisyys_lapa_jaykkyys',
																				'kyparan_koko',
																				'voitelu',
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
function parsPage($category, $page, $size = 500) {
	global $pause;
	$html = Request::curl($category.'?form_token=sort&product_amout='.$size.'&sort-by=created-ascending&page='.$page, $pause);
	$dom = phpQuery::newDocument($html);
	$goods = $dom->find('div.product-name>a');
	if (count($goods) < 1) {
		$dom->unloadDocument();
		Logger::send("|GOODS|ERROR| - Не удалось получить список товаров");
		return false;
	}
	$hrefs = [];
	foreach ($goods as $good) {
		$hrefs[] = 'http://urheilupajala.fi'.pq($good)->attr('href');
	}
	$dom->unloadDocument();
	if (empty($hrefs)) {
		$dom->unloadDocument();
		Logger::send("|GOODS|ERROR| - Не удалось получить список товаров");
		return false;
	}
	foreach ($hrefs as $href) {
		parseGood($href);
	}
}

foreach (getCategories() as $name => $link) {
	for ($p = 1; isPage($link, $p); $p++) {
		Logger::send("|CATEGORY|WAIT| - Категория: $name. Страница: $p");
		parsPage($link, $p);
	}
	Logger::send("|CATEGORY|FINISH| - Парсинг из категории $name завершен");
}

Logger::send("|FINISH|SUCCESS| - Работа скрипта завершена. Парсинг из ".PARSER_NAME);