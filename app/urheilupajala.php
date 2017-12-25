<?php

$pause = 0;

require __DIR__.'/../core.php';

Logger::send("|START|SUCCESS| - Скрипт запущен. Парсинг из ".PARSER_NAME);

// Массив категорий по которым ходим
$categories = [
	//'ILOTULITTEET' => 'http://urheilupajala.fi/tuoteryhma/ilotulitteet',
	'TALVIURHEILU' => 'http://urheilupajala.fi/tuoteryhma/talviurheilu',
	'JÄÄKIEKKOVARUSTEET' => 'http://urheilupajala.fi/tuoteryhma/jaaurheilu',
	'PYÖRÄILY' => 'http://urheilupajala.fi/tuoteryhma/pyoraily',
	'KUNTOILU JA FITNESS' => 'http://urheilupajala.fi/tuoteryhma/murtomaahiihto_ale_poistot',
	'METSÄSTYS JA RETKEILY' => 'http://urheilupajala.fi/tuoteryhma/metsastys-ja-retkeily',
	'MAILA- JA PALLOPELIT' => 'http://urheilupajala.fi/tuoteryhma/maila-ja-pallopelit',
	'KENGÄT JA TEKSTIILIT' => 'http://urheilupajala.fi/tuoteryhma/kengat-ja-tekstiilit',
	'ALE - POISTOT' => 'http://urheilupajala.fi/tuoteryhma/poistotuotteet',
];

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
function parseGood($href, $category) {
	global $pause;
	$html = Request::curl($href, $pause);
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
																				'category' => $category,
																				'title' => getName($html),
																				'season' => getSeason(getName($html)),
																				'price' => str_replace(['€', ',', ' '], ['', '.', ''], $dom->find('span.js-product-price')->text()),
																				'price_without_discount' => str_replace(['€', ',', ' '], ['', '.', ''], $dom->find('span.js-product-original-price')->text()),
																				'product_type' => $category,
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
																			], $data['product_type']/*, ['updated', 'product_id']*/);
																			switch ($csv) {
																				case 1:
																					Logger::send("|RECORD|ADD| - Товар ".$data['title']." добавлен в CSV");
																					break;
																				case 2:
																					Logger::send("|RECORD|UPDATE| - Товар ".$data['title']." обновлен в CSV");
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
	//Writer::deleteNoUpdatedCSV($data['product_id'], 'product_id', 'updated');
	Writer::eraseAttachInIdFolder($data['product_id']);
	Writer::saveOnUpdateImages($data['product_id'], getPhotos($dom));
	$dom->unloadDocument();
}













// Разбираем страницу товара и отправляем на запись (Suksen mitta, Koko, Koko (EU), Kengän numero (EU), Väri)
function parseGoodSuksenMittaKokoKokoEUKenganNumeroEUVari($href, $category) {
	global $pause;
	$html = Request::curl($href, $pause);
	$dom = phpQuery::newDocument($html);
	foreach (getParams($dom, 'Suksen mitta') as $suksen_mitta) {
		foreach (getParams($dom, 'Koko') as $koko) {
			foreach (getParams($dom, 'Koko (EU)') as $koko_eu) {
				foreach (getParams($dom, 'Kengän numero (EU)') as $kengan_numero_eu) {
					foreach (getParams($dom, 'Väri') as $vari) {
						$data = [
							'product_id' => getIdent($html),
							'category' => $category,
							'title' => getName($html),
							'season' => getSeason(getName($html)),
							'price' => str_replace(['€', ',', ' '], ['', '.', ''], $dom->find('span.js-product-price')->text()),
							'price_without_discount' => str_replace(['€', ',', ' '], ['', '.', ''], $dom->find('span.js-product-original-price')->text()),
							'product_type' => $category,
							'product_age' => getProductAge(getName($html)),
							'manufacturer' => getManufacturer($html),
							'availableurl' => $href,
							'suksen_mitta' => $suksen_mitta,
							'koko' => $koko,
							'koko_eu' => $koko_eu,
							'kengan_numero_eu' => $kengan_numero_eu,
							'vari' => $vari,
							'updated' => 0,
						];
						$csv = Writer::insertOrUpdateCSV($data, [
							'availableurl',
							'suksen_mitta',
							'koko',
							'koko_eu',
							'kengan_numero_eu',
							'vari',
						], $data['product_type']/*, ['updated', 'product_id']*/);
						switch ($csv) {
							case 1:
								Logger::send("|RECORD|ADD| - Товар ".$data['title']." добавлен в CSV");
								break;
							case 2:
								Logger::send("|RECORD|UPDATE| - Товар ".$data['title']." обновлен в CSV");
								break;
						}
					}
				}
			}
		}
	}
	//Writer::deleteNoUpdatedCSV($data['product_id'], 'product_id', 'updated');
	Writer::eraseAttachInIdFolder($data['product_id']);
	Writer::saveOnUpdateImages($data['product_id'], getPhotos($dom));
	$dom->unloadDocument();
}















// Получаем линки объявлений со страницы
function parsPage($category, $page, $name, $size = 500) {
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
		parseGood($href, $name);
	}
}

foreach ($categories as $name => $link) {
	for ($p = 1; isPage($link, $p); $p++) {
		Logger::send("|CATEGORY|WAIT| - Категория: $name. Страница: $p");
		if ($link == 'http://urheilupajala.fi/tuoteryhma/talviurheilu') {
			parseGoodSuksenMittaKokoKokoEUKenganNumeroEUVari($link, $p, $name);
		} elseif ($link == '') {
			
		} else {
			break;
		}
		
	}
	Logger::send("|CATEGORY|FINISH| - Парсинг из категории $name завершен");
}

Logger::send("|FINISH|SUCCESS| - Работа скрипта завершена. Парсинг из ".PARSER_NAME);