<?php
namespace RigSkeleton\View\Navigation;

use Zend\Navigation\Service\AbstractNavigationFactory;
use Zend\ServiceManager\FactoryInterface;
use Zend\ServiceManager\ServiceLocatorInterface;

class NavigationFactory extends AbstractNavigationFactory implements FactoryInterface
{
	protected function getName()
	{
		return 'navigation';
	}
	
	protected function getPages(ServiceLocatorInterface $serviceLocator)
	{
		if ($this->pages === null)
		{
			$pages = array(
				array(
					'label' => 'Home',
					'route' => 'user',
				),
			);
			
			$e = $serviceLocator->get('Application')->getMvcEvent();
			$this->pages = $this->injectComponents($pages, $e->getRouteMatch(), $e->getRouter());
		}
		
		return $this->pages;
	}
}