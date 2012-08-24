<?php
spl_autoload_register(function($class) {
	$pos = strpos($class, 'Sporcle');
	if($pos === 0) {
		$relative_path = str_replace('\\', DIRECTORY_SEPARATOR, substr($class, $pos+8));
		$file_path = __DIR__.DIRECTORY_SEPARATOR.$relative_path.'.php';
		if(file_exists($file_path)) {
			require_once($file_path);
		}
	}
});
