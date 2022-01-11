<?php
return array(
	'db' => array(
		'driver' => 'Pdo',
		'dsn' => 'mysql:dbname=zf2;host=127.0.0.1',
		'driver_options' => array(
			PDO::MYSQL_ATTR_INIT_COMMAND > 'SET NAMES \'UTF8\''
		),
	),
	'service_manager' => array(
		'factories' => array(
			'dbAdapter' => 'Zend\Db\Adapter\AdapterServiceFactory',
			'navigation' => 'RigSkeleton\View\Navigation\NavigationFactory',
			'translator' => 'Zend\I18n\Translator\TranslatorServiceFactory',
		)
	)
);
