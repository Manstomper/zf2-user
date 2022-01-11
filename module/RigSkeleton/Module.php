<?php
namespace RigSkeleton;

class Module
{
	public function onBootstrap(\Zend\Mvc\MvcEvent $e)
	{
		$moduleRouteListener = new \Zend\Mvc\ModuleRouteListener();
		$moduleRouteListener->attach($e->getApplication()->getEventManager());
	}
	
	public function getAutoloaderConfig()
	{
		return array(
			'Zend\Loader\StandardAutoloader' => array(
				'namespaces' => array(
					__NAMESPACE__ => __DIR__ . '/src/',
				),
			),
		);
	}
	
	public function getConfig()
	{
		return array(
			'translator' => array(
				'locale' => 'en_US',
				'translation_file_patterns' => array(
					array(
						'type' => 'gettext',
						'base_dir' => __DIR__ . '/language',
						'pattern' => '%s.mo',
					),
				),
			),
			'view_manager' => array(
				'display_not_found_reason' => true,
				'display_exceptions' => true,
				'doctype' => 'HTML5',
				'not_found_template' => 'error/index',
				'exception_template' => 'error/index',
				'template_path_stack' => array(
					__DIR__ . '/view',
				),
			),
		);
	}
}
