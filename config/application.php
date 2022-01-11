<?php
return array(
	'modules' => array(
		'RigSkeleton',
		'RigUser',
	),
	'module_listener_options' => array(
		'module_paths' => array(
			'./module',
		),
		'config_glob_paths' => array(
			'config/autoload/{,*.}{global,local}.php',
		),
	),
);