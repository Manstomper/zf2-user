<?php
chdir(dirname(__DIR__));
date_default_timezone_set('GMT');

//change this to point to the framework files on your system
if ($zf2Path = realpath('C:\xampp\php\ZendFramework\Loader\AutoloaderFactory.php'))
{
	include $zf2Path;
	Zend\Loader\AutoloaderFactory::factory(array('Zend\Loader\StandardAutoloader' => array('autoregister_zf' => true)));
	Zend\Mvc\Application::init(require 'config/application.php')->run();
}
else
{
	throw new RuntimeException('Failed to load Zend Framework library.');
}