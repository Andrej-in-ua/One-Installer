<?php

$app = new Installer();
//$app->run()

class Installer
{
	public $error = array();
	
	public 	$filedir,
			$filename;
	
	public $action = 'form';
	
	
	public function __construct()
	{
		// Название файла
		$this->filename = ( isset($_GET['filename']) ? $_GET['filename'] : basename(__DIR__).'.php' );
		$this->filedir = ( isset($_GET['patch']) ? $_GET['patch'] : './' );
		
		// Получаем действие
		$this->action = ( isset($_GET['action']) ? $_GET['action'] : 'form' );
		
		// Первый запуск
		if ( $this->action == 'form' ) return $this->form();
		
		// Проверка каталога
		if ( ! $this->checkPatch($this->filedir) ) $this->error[] = array(1, 'Need save directory');
		
		
		if ( $this->action == 'run' )
		{
			$this->step = ( isset($_GET['step']) ? $_GET['step'] : 0 );
			$map = $this->loadmap('.');
			v($map);
		}
		
		if ( count($this->error) > 0 )
		{
			$this->error($this->error);
			return $this->form();
		}
	}
	
	public function loadmap( $patch, $map = array() )
	{
		if ( count($map) == 0 )
		{
			
		}
		
		if ($handle = opendir($patch))
		{
			while( false !== ($entry = readdir($handle)) )
			{
				if ( $entry == '.' OR $entry == '..' OR $entry == '.git' OR $entry == '.svn' ) continue;
				$entry = $patch.'/'.$entry;
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
		//var_dump($map);
		return $map;
	}
	
	public function form()
	{
		?><form action="installer.php" method="get">
		<p>Папка для сохранения: <input type="text" name="patch" value="<?php echo $this->filedir; ?>" /></p>
		<p>Имя файла: <input type="text" name="filename" value="<?php echo $this->filename; ?>" /></p>
		
		<input type="hidden" name="action" value="run" />
		<input type="hidden" name="step" value="0" />
		<p><input type="submit" value="Start" /></p>
		</form><?php
	} 
	
	public function error( $error )
	{
		if ( ! is_array($error) ) return false;
		
		// Строгий формат
		if ( ! is_array($error[0]) ) $error = array($error);
		
		foreach ($error as $e)
		{
			echo '<div class="error">['.$e[0].'] '.$e[1].'</div>';
		}
	}
	
	protected function checkPatch( $patch )
	{
		// Каталога нет
		if ( ! file_exists($patch) AND ! mkdir($patch, 0777, true) ) return false;
		return is_writable($patch);
	}
}

function v($v){echo '<pre>'.print_r($v, 1).'</pre>';}