<?php
$readyToWork = true;
$readyDatabase = true;
$db = false;

function IsBitrix(){
	global $readyToWork;
	if( !is_dir($_SERVER["DOCUMENT_ROOT"] . "/bitrix/") ||
		!file_exists($_SERVER["DOCUMENT_ROOT"] . "/bitrix/.settings.php") ||
		!file_exists($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/admin/define.php"))
		$readyToWork = false;
}

if( $readyToWork ){
	$settings = include $_SERVER["DOCUMENT_ROOT"] . "/bitrix/.settings.php";
	$arConnectConfig = $settings["connections"]["value"]["default"];

	function IsConnect(){
		global $readyDatabase;
		global $arConnectConfig;
		global $db;
		$db = @new mysqli($arConnectConfig["host"], $arConnectConfig["login"], $arConnectConfig["password"], $arConnectConfig["database"]);
		if($db->connect_error)
			$readyDatabase = false;
	}

	function getFileHash(){
		include $_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/admin/define.php";
		return TEMPORARY_CACHE;
	}

	function getDBHash(){
		global $db;
		$query = $db->query("SELECT VALUE FROM b_option WHERE NAME='admin_passwordh'");
		$arResult = $query->fetch_array();
		return $arResult["VALUE"];
	}

	function setFileHash( $hash ){
		file_put_contents($_SERVER["DOCUMENT_ROOT"] . "/bitrix/modules/main/admin/define.php", "<?Define(\"TEMPORARY_CACHE\", \"" . $hash ."\");?>");
	}

	function setDBHash( $hash ){
		global $db;
		$query = $db->query("UPDATE b_option SET VALUE='" . $hash . "' WHERE NAME='admin_passwordh'");
	}

	function clearCache($dir){
		if (is_dir($dir)) {
			$objects = scandir($dir);
			foreach ($objects as $object) {
				if ($object != "." && $object != "..") {
					if (filetype($dir."/".$object) == "dir")
						clearCache($dir."/".$object);
					else unlink   ($dir."/".$object);
				}
			}
			reset($objects);
			rmdir($dir);
		}
	}
}



$init = function(){
	IsBitrix();
	IsConnect();
};

$init();

if( isset($_POST["ACTION"]) && $_POST["ACTION"]== "GET" ){
	$json = [
		"file_hash" => getFileHash(),
		"db_hash" => getDBHash()
	];

	echo json_encode($json);
	die();
}

if( isset($_POST["ACTION"]) && $_POST["ACTION"]== "SET" ){

	setDBHash( strip_tags($_POST["DB_HASH"]) );
	setFileHash( strip_tags($_POST["FILE_HASH"]) );

	$dirname = $_SERVER["DOCUMENT_ROOT"] . "/bitrix/managed_cache/MYSQL";
	clearCache($dirname);

	$json = [
		"success" => true
	];

	echo json_encode($json);
	die();
}
?>

<!DOCTYPE html>
<html lang="ru">
	<head>
		<meta name="description" content="BX Trial tools поможет продлить триал" />
		<meta charset="utf-8">
		<title>BX Trial tool</title>
		<meta name="viewport" content="width=device-width, initial-scale=1">
		<meta name="author" content="Viktor Karionov">
		<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.0.1/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-+0n0xVW2eSR5OomGNYDnhzAbDsOXxcvSN1TPprVMTNDbiYZCxYbOOl7+AMvyTG2x" crossorigin="anonymous">
		<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.5.0/font/bootstrap-icons.css">
	</head>
	<body>

	<div class="col-lg-8 mx-auto p-3 py-md-5">
		<main>
			<h1 class="display-1 mb-5">
				<i class="bi bi-alt"></i> BX Trial tool
			</h1>
			<p class="fs-5 mb-5">Данный инструмент поможет быстро получить и заменить значения хэшей. Берите хэши из "свежей" версии БУС с триальным периодом и вставляйте на сайте с просроченным триальным периодом. Не берите хэши из копий с установленной лицензией.</p>

			<?if($readyToWork):?>
				<?if($readyDatabase):?>
					<div class="alert alert-primary d-flex align-items-center" role="alert">
						<svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" fill="currentColor" class="bi bi-chevron-right" viewBox="0 0 16 16"><path fill-rule="evenodd" d="M4.646 1.646a.5.5 0 0 1 .708 0l6 6a.5.5 0 0 1 0 .708l-6 6a.5.5 0 0 1-.708-.708L10.293 8 4.646 2.354a.5.5 0 0 1 0-.708z"/></svg>
						<div>
							Скрипт готов к работе
						</div>
					</div>
				<?else:?>
					<div class="alert alert-warning d-flex align-items-center" role="alert">
						<svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" fill="currentColor" class="bi bi-chevron-right" viewBox="0 0 16 16"><path fill-rule="evenodd" d="M4.646 1.646a.5.5 0 0 1 .708 0l6 6a.5.5 0 0 1 0 .708l-6 6a.5.5 0 0 1-.708-.708L10.293 8 4.646 2.354a.5.5 0 0 1 0-.708z"/></svg>
						<div>
							Не удаётся подключится к базе данных. Проверьте настройки <b>/bitrix/.settings.php</b>. Работа скрипта не возможна.
							<br>
							<?=$db->connect_error;?>
						</div>
					</div>
				<?endif;?>
			<?else:?>
				<div class="alert alert-warning d-flex align-items-center" role="alert">
					<svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" fill="currentColor" class="bi bi-chevron-right" viewBox="0 0 16 16"><path fill-rule="evenodd" d="M4.646 1.646a.5.5 0 0 1 .708 0l6 6a.5.5 0 0 1 0 .708l-6 6a.5.5 0 0 1-.708-.708L10.293 8 4.646 2.354a.5.5 0 0 1 0-.708z"/></svg>
					<div>
						Не найдены признаки Битрикса. Работа скрипта не возможна.
					</div>
				</div>
			<?endif;?>
			<div class="container-lg">
				<div class="row">
					<div class="col p-3 border bg-light">
						<h4>1. Получение</h4>
						<small class="text-muted">Получите хеши на свежей версии Битрикса с неистёкшим триалом</small>
						<div class="mb-3 mt-4">
							<label for="getHashInput1" class="form-label">Хеш 1</label>
							<input type="text" class="form-control" id="getHashInput1" placeholder="Получите хеш" readonly>
						</div>
						<div class="mb-3">
							<label for="getHashInput2" class="form-label">Хеш 2</label>
							<input type="text" class="form-control" id="getHashInput2" placeholder="Получите хеш" readonly	>
						</div>
						<button type="submit" id="getHashBtn" class="btn btn-primary" <?if(!$readyToWork || !$readyDatabase):?>disabled<?endif;?>>Получить хеши</button>
					</div>
					<div class="col p-3 border bg-light">
						<h4>2. Установка</h4>
						<small class="text-muted">Установите хеши на версии сайта где заканчивается (или закончился) триальный период</small>
						<div class="mb-3 mt-4">
							<label for="setHashInput1" class="form-label">Хеш 1</label>
							<input type="text" class="form-control" id="setHashInput1" placeholder="Вставьте хеш 1">
						</div>
						<div class="mb-3">
							<label for="setHashInput2" class="form-label">Хеш 2</label>
							<input type="text" class="form-control" id="setHashInput2" placeholder="Вставьте хеш 2">
						</div>
						<div id="alertSuccess" class="alert alert-success d-flex align-items-center d-none" role="alert">
							<small>
								Хеши установены. Не забудьте удалить этот скрипт.
							</small>
						</div>
						<button type="submit" id="setHashBtn" class="btn btn-primary" <?if(!$readyToWork || !$readyDatabase):?>disabled<?endif;?>>Установить хеши</button>
					</div>
				</div>
			</div>
		</main>


		<footer class="pt-5 my-5 text-muted border-top">
			<div class="col text-center h4">
				Помог? Подпишись на инсту <a href="https://www.instagram.com/vkarionov/" target="_blank">подписаться</a>
			</div>
			<div class="text-center">
				<a href="https://www.instagram.com/vkarionov/" target="_blank">Viktor Karionov</a> © 2021
			</div>
		</footer>
	</div>

	<!-- JavaScript Bundle with Popper -->
	<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
	<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.0.1/dist/js/bootstrap.bundle.min.js" integrity="sha384-gtEjrD/SeCtmISkJkNUaaKMoLD0//ElJ19smozuHV6z3Iehds+3Ulb9Bn9Plx0x4" crossorigin="anonymous"></script>

	<script>
		$(function(){
			$("#getHashBtn").on("click", function(){
				let xhr = $.ajax(window.location.href, {
					method: "POST",
					dataType: "json",
					data: {"ACTION" : "GET"}
				});

				xhr.done(function(res){
					$("#getHashInput1").val( res.file_hash );
					$("#getHashInput2").val( res.db_hash );
				});
			});

			$("#setHashBtn").on("click", function(){
				let self = $(this);
				if( confirm("Внимание! \n\nБудут заменены хеши и очищен managed_cache. \n\nНе делайте этого на сайтах с установленной лицензией.") ){
					self.attr("disabled", "disabled");
					let xhr = $.ajax(window.location.href, {
						method: "POST",
						dataType: "json",
						data: {
							"ACTION" : "SET",
							"DB_HASH" : $.trim( $("#setHashInput2").val()),
							"FILE_HASH" : $.trim( $("#setHashInput1").val()),
						}
					});

					xhr.done(function(){
						self.removeAttr("disabled");
						$("#alertSuccess").removeClass("d-none");
					})
				}
			});
		});
	</script>
	</body>
</html>