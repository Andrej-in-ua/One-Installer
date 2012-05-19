<?php
session_start();

class Installer extends One
{
	protected
		$fileDir = './', $fileName = 'install',
		$exceptions = '.svn .git* installer.php',
		$start = 0;
		
	public function __construct()
	{
		// Первый вызов инициализирует слежение за временным лимитом
		parent::wotch_dog();
		$this->title = 'Installer v0.1';
		$this->logo = '';
	//	$this->menu = array('index'=>'Pack');
		parent::__construct();
	}
	
	public function indexAction()
	{
		
		?><h1>Создание автоматического архива</h1><form action="installer.php?action=run" method="post">
		<p><label>Папка для сохранения:</label><input type="text" name="dir" value="<?php echo $this->fileDir; ?>" /></p>
		<p><label>Имя файла:</label><input type="text" name="name" value="<?php echo $this->fileName; ?>" />.php</p>
		<p><label>Исключить:</label><textarea name="exceptions" cols="60" rows="3"><?php echo $this->exceptions; ?></textarea></p>
		<div class="fl" style="padding: 8px 20px;"><input type="submit" value="Создать" class="button" style="padding: 6px 14px;"></div>
		<div><p><strong>Внимание!</strong><br>Процесс упаковки может занять определенное время.<br />
		Не закрывайте и не перезагужайте страницу браузера после старта!</p></div>
		</form><?php
	}
	
	public function runAction()
	{
		// Проверка настроек
		$this->fileDir = ( isset($_POST['dir']) ? trim($_POST['dir'], '/').'/'  : './' );
		$this->fileName = ( isset($_POST['name']) ? $_POST['name'] : 'install' );
		
		if ( file_exists($this->fileDir.$this->fileName.'.php') )
		{
			$this->error('Указанный файл уже существует!');
			return $this->indexAction();
		}
		
		if ( ( ! file_exists($this->fileDir) AND ! mkdir($this->fileDir, 0777, true) )
			OR ! is_writable($this->fileDir) )
		{
			$this->error('Нет прав на запись в указанный каталог!');
			return $this->indexAction();
		}
		
		$this->exceptions = $_SESSION['exeptions'] = $_POST['exceptions'];
		$_SESSION['fileName'] = $this->fileDir.$this->fileName.'.php';
		
		$fp = fopen($_SESSION['fileName'], 'w');
		fwrite($fp, '<?php'.PHP_EOL);
		fclose($fp);
		?><h1 id="step">Архив создается...</h1>
	<style>


		.progress {
  height: 20px; /* Можно любую */
  position: relative;
  background: #fff;
  -webkit-border-radius: 6px;border-radius: 6px;
  border: 1px #CECECE solid;
  padding: 2px;
}

.progress > span {
	-webkit-border-radius: 4px;border-radius: 4px;
  display: block;
  height: 100%;
  position: relative;
  top:-16px;
  overflow: hidden;
  background: #D2FF52;
  z-index: 5;
}
.progress > div{position: relative;text-align:center;z-index: 10;font-weight: bold;}
</style>		
		<div class="progress" id="progress">
		  <div class="status">? / ?</div>
		  <span style="width: 1%"></span>
		  
		</div>

		<div id="log"></div>
		<script>
		$(function(){
			var progress = {
				setText: function(text){ $('#progress > div').html(text); },
				setPercent: function(p){ $('#progress > span').width(p+'%'); },
			}
			
			var install = {
				
				uri: 'installer.php',
				step: 1,
				mapCountTotal:0,
				mapCurrent: 0,
				
				run: function(){
					switch(install.step)
					{
						case 1: return setTimeout(install.step_1,100);
						case 2: return setTimeout(install.step_2,100);
						case 3: return setTimeout(install.step_3,100);
						case 4: return setTimeout(install.step_4,100);
					}
				},
				setTitle: function(title){ $('#step').html(title); },
				
				setMapCount: function(c, t){
					if ( t >= 0) this.mapCountTotal = t;
					if ( c > this.mapCountTotal ) c = this.mapCountTotal;
					progress.setText(c + ' / ' + this.mapCountTotal);
					
					progress.setPercent(( ( 90 / this.mapCountTotal ) * c) + 5 );
				},
				
				step_1: function()
				{
					install.setTitle('Шаг 1: Создание карты файлов...');
					
					$.get( install.uri, { action: "map" }, function(e){
						progress.setPercent(5);
						e = parseInt(e);
						if ( e > 0 )
						{
							install.setMapCount(0, e);
							install.step = 2;
							install.run();
						}
					});
				},
				step_2: function()
				{
					install.setTitle('Шаг 2: Генерация архива...');
					
					if( install.mapCurrent < install.mapCountTotal )
					{
						$.get( install.uri, { action: "create", start: install.mapCurrent }, function(e){
							install.mapCurrent = parseInt(e);
							install.setMapCount(install.mapCurrent);
							install.step_2();
						});
					} else {
						install.step = 3;
						install.run();
					}
				},
				
				step_3: function()
				{
					install.setTitle('Шаг 3: Завершение...');
					progress.setPercent(100);
					install.step = 4;
							install.run();
				},
				
				step_4: function()
				{
					install.setTitle('Архив создан!');
					$('.pbar').hide();
				}
			}
			
			install.run();
		});
		
		</script>
		
		<?php
	}
	
	public function mapAction()
	{
		$this->exceptions = explode(' ', str_replace('*','.*', str_replace('.','\.', $_SESSION['exeptions'])));
		$_SESSION['map'] = $this->loadmap();
		
		echo count($_SESSION['map']);
		die();
	}
	
	public function loadmap( $patch = './', $map = array() )
	{
		if ($handle = opendir($patch))
		{
			while( false !== ($entry = readdir($handle)) )
			{
				if ( $entry == '.' OR $entry == '..' ) continue;
				
				foreach ($this->exceptions as $pattern)
					if ( preg_match('/^('.$pattern.')$/i', $entry) ) continue 2;

				$entry = rtrim($patch, '/').'/'.$entry;
				if ( is_dir($entry) )
				{
					$map[] = array('d', $entry);
					$map = $this->loadmap($entry, $map);
				} else if ( is_file($entry) )
				{
					$map[] = array('f', $entry);
				}
			}
		
			closedir($handle);
		}
		return $map;
	}
	
	public function createAction()
	{
		$this->start = (int) $_GET['start'];
		$total = count($_SESSION['map']);
		$map = $_SESSION['map'];
		
		$fp = fopen($_SESSION['fileName'], 'a');
		
		for( ; $this->start < $total; $this->start++)
		{
			if( $map[$this->start][0] == 'f' )
			{
				$content = 'b(\''.$map[$this->start][1].'\',\''.base64_encode(bzcompress(file_get_contents($map[$this->start][1]), 9)).'\');'.PHP_EOL;
			} else {
				$content = 'b(\''.$map[$this->start][1].'\',0);'.PHP_EOL;
				//$content[$map[$this->start][1]] = null;
				//$content .= '$a[\''.$map[$this->start][1].'\']=null;'.PHP_EOL;
			}
			fwrite($fp, $content);
			//$this->wotch_dog();
		}
		// fwrite($fp, 'foreach($a as $k=>$c){if($c===0)mkdir($k,0777,true); else file_put_contents($k,bzdecompress(base64_decode($c)));}');
		fwrite($fp, 'function b($k,$c){if($c===0)mkdir($k,0777,true); else file_put_contents($k,bzdecompress(base64_decode($c)));}');
		
		fclose($fp);
		return $this->createTimeout();
	}
	
	public function createTimeout()
	{
		echo ++$this->start;
		die();
	}
	
}


class One
{
	protected
		$action,
		$title = 'One file admin',
		$logo = 'Central Panel',
		$menu = array(),
		
		$timeStart, $timeLimit = 1;
	
	protected function __construct()
	{
		$this->action = ( isset($_GET['action']) ? $_GET['action'] : 'index' );
		
		if ( ! is_callable(array($this, $this->action.'Action')) ) $this->error('Action is not exists', 404);
		ob_start();
		call_user_func(array(&$this, $this->action.'Action'));
		
		$this->layout();
		
	}
	
	protected function error($msg, $code = 0)
	{
		if ( $code != 0 ) {
			$this->layout('<div class="error">'.$msg.'</div>');
			die();
		} else {
			echo '<div class="error">'.$msg.'</div>';	
		}
	}
	
	protected function wotch_dog()
	{
		if ( $this->timeStart === null )
		{
			$this->timeStart = microtime(true);
			return true;	
		}
		
		if ( $this->timeStart + $this->timeLimit > microtime(true) ) return true;

		if ( is_callable(array($this, $this->action.'Timeout')) ) {
			call_user_func(array(&$this, $this->action.'Timeout'));
		}
		
		$this->error(404, 'Action timeout');
	}
	
	protected function layout( $_content = null )
	{
		if ( $_content === null ) $_content = ob_get_clean();
		ob_end_clean();
		
?><!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
<title><?php echo $this->title; ?></title>
<style>
body{background-color:#f1f1f1;font-family:Verdana, Geneva, sans-serif;font-size:13px;color:#333}
.main{width:850px;margin:15px auto 0}
.header a{font-size:135%;font-weight:bold;text-decoration:none;color:#000;margin:1em;display:block}
#content{border:#b3b3b3 solid 1px;background-color:#fff;padding:0 16px 16px}
#navbar a{color:#000;text-decoration:none;font-size:115%;border:#b3b3b3 solid 1px;background-color:#fff;margin:0 2px;padding:5px 8px}
#navbar a:hover{background-color: #F7F7F7;}
h1{font-size:135%;font-weight:bold;color:#333;width:100%;border-bottom:#b3b3b3 1px solid;padding:10px 0 3px;margin-bottom:16px}
.result_table td{border:#CCC solid 1px;text-align:center;padding:2px 20px}
.result_table th{border-top:#333 solid 1px;border-bottom:#333 solid 1px;text-align:center;background:#EFEFEF;padding:2px 20px}
.ok{font-size:115%;color:#000;background-color:#EBFFDD;border:1px solid #060;margin:20px 0;padding:10px}
.error{font-size:115%;color:#000;background-color:#FFC;border:1px solid #F90;margin:20px 0;padding:10px}
.button{border:#b3b3b3 solid 1px;background-color:#fff;text-decoration:none;color:#000;margin:0 2px;padding:4px 8px}
label{width:258px;display:inline-block;padding:4px 7px;border-left: 2px solid #CECECE;vertical-align: top;}
p:hover label{border-left: 2px solid #333}
hr{border:0;border-bottom: 1px dashed #CECECE;}
form p{border-bottom: 1px dashed #CECECE; padding-bottom:12px;}
.fl{float:left}.fr{float:right}
.copy{color:#999;font-size:10px;margin: 8px;}
.copy a, .copy strong{color:#666;font-weight:normal;text-decoration:underline}
.copy img{opacity:.4}


/*---- clearfix ----*/
.clearfix:after {content: "."; display: block; clear: both;	visibility: hidden;	line-height: 0;	height: 0;}
.clearfix {display: inline-block;}
html[xmlns] .clearfix {display: block;}
* html .clearfix {height: 1%;}
.ok, .error,#content, #navbar a{border-radius:3px;-moz-border-radius:3px;-webkit-border-radius:3px}
</style>
<script type="text/javascript" src="http://ajax.googleapis.com/ajax/libs/jquery/1.7.2/jquery.min.js"></script>
</head>
<body>
<div class="main">
<div class="header"><a href="<?php echo $_SERVER['PHP_SELF']; ?>"><?php echo $this->logo; ?></a></div>
<div id="navbar"><?php
foreach( $this->menu as $link => $title )
{
	echo '<span><a href="'.$_SERVER['PHP_SELF'].'?action='.$link.'">'.$title.'</a></span>'; 
}
	?></div><div id="content"><?php echo $_content; ?></div>
<div class="copy fl">Техническая поддержка: <script type="text/javascript">
<!--
document.write('<img src="http://status.icq.com/online.gif?icq=851028&amp;img=26&amp;rnd='+Math.random()+'" style="border:none;vertical-align: bottom;" />')
//-->
</script> <strong>851028</strong> | <strong>mrpkmail@gmail.com</strong></div>
<div class="copy fr">Powered by Sevastianov Andrej &copy; 2011 |
<a href="http://andrej.in.ua" target="_blank">www.andrej.in.ua</a></div></div>
</body>
</html><?php
	}
}

new Installer();