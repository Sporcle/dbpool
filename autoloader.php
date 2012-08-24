<?php
spl_autoload_register(function($class) {
	$relative_path = strpos($class, '\\') !== false ? str_replace('\\', DIRECTORY_SEPARATOR, $class) : $class;
	$file_path = __DIR__.DIRECTORY_SEPARATOR.$relative_path.'.php';
	if(file_exists($file_path)) {
		require_once($file_path);
	}
});
