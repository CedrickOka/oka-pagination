<?php
namespace Oka\PaginationBundle\DependencyInjection;

use Oka\PaginationBundle\Converter\LikeQueryExprConverter;
use Oka\PaginationBundle\Converter\NotLikeQueryExprConverter;
use Oka\PaginationBundle\Converter\ORM\RangeQueryExprConverter;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\Alias;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

/**
 * This is the class that loads and manages your bundle configuration
 * 
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html}
 */
class OkaPaginationExtension extends Extension
{
	/**
	 * @var array $doctrineDrivers
	 */
	public static $doctrineDrivers = [
			'orm' => [
					'registry' => 'doctrine',
					'tag' => 'doctrine.event_subscriber',
			],
			'mongodb' => [
					'registry' => 'doctrine_mongodb',
					'tag' => 'doctrine_mongodb.odm.event_subscriber',
			]
	];
	
	/**
	 * {@inheritDoc}
	 */
	public function load(array $configs, ContainerBuilder $container)
	{
		$configuration = new Configuration();
		$config = $this->processConfiguration($configuration, $configs);
		
		$loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
		$loader->load('services.yml');
		
		$container->setAlias('oka_pagination.default.doctrine_registry', new Alias(self::$doctrineDrivers[$config['db_driver']]['registry'], false));
		$definition = $container->getDefinition('oka_pagination.default.object_manager');
		$definition->addArgument($config['model_manager_name']);
		$definition->setFactory([new Reference('oka_pagination.default.doctrine_registry'), 'getManager']);
		
		// Entity manager default name
		$container->setParameter('oka_pagination.model_manager_name', $config['model_manager_name']);
		
		// Pagination default parameters
		$container->setParameter('oka_pagination.item_per_page', $config['item_per_page']);
		$container->setParameter('oka_pagination.max_page_number', $config['max_page_number']);
		$container->setParameter('oka_pagination.template', $config['template']);
		
		// Request Configuration
		$this->loadRequestConfiguration($config, $container);
		
		// Twig Configuration
		$this->loadTwigExtension($config, $container);
		
		// Pagination bag
		$definition = $container->getDefinition('oka_pagination.manager_bag');
		$definition->replaceArgument(0, $config['pagination_managers']);
		
		// Query Expression Converters Configuration
		$this->loadQueryExprConverter($config, $container);
	}
	
	protected function loadRequestConfiguration(array $config, ContainerBuilder $container)
	{
		if (!empty($config['sort']['attributes_availables']) || $config['sort']['delimiter'] !== null) {
			@trigger_error('The configuration value `oka_pagination.sort` is deprecated since 1.3.0 and will be removed in 1.4.0. Use `oka_pagination.request.sort` instead', E_USER_DEPRECATED);				
		}
		
		if ($config['sort']['delimiter'] !== null && $config['request']['sort']['delimiter'] === ',') {
			$config['request']['sort']['delimiter'] = $config['sort']['delimiter'];
		}
		
		if (!empty($config['sort']['attributes_availables']) && empty($config['request']['sort']['attributes_availables'])) {
			$config['request']['sort']['attributes_availables'] = $config['sort']['attributes_availables'];
		}
		
		$container->setParameter('oka_pagination.request', $config['request']);
		unset($config['sort']);
	}
	
	protected function loadTwigExtension(array $config, ContainerBuilder $container)
	{
		$container->setParameter('oka_pagination.twig.enable_global', $config['twig']['enable_global']);
		$definition = $container->getDefinition('oka_pagination.twig.extension');
		
		if ($config['twig']['enable_extension'] === true) {
			$definition->addTag('twig.extension');
		}
	}
	
	protected function loadQueryExprConverter(array $config, ContainerBuilder $container)
	{
		$mapConverters = [
				[
						'db_drivers' 	=> ['orm', 'mongodb'],
						'pattern' 		=> \Oka\PaginationBundle\Converter\LikeQueryExprConverter::PATTERN,
						'class' 		=> 'Oka\PaginationBundle\Converter\LikeQueryExprConverter'
				],
				[
						'db_drivers' 	=> ['orm', 'mongodb'],
						'pattern' 		=> \Oka\PaginationBundle\Converter\NotLikeQueryExprConverter::PATTERN,
						'class' 		=> 'Oka\PaginationBundle\Converter\NotLikeQueryExprConverter'
				],
				[
						'db_drivers' 	=> ['orm'],
						'pattern' 		=> \Oka\PaginationBundle\Converter\ORM\RangeQueryExprConverter::PATTERN,
						'class' 		=> 'Oka\PaginationBundle\Converter\ORM\RangeQueryExprConverter'
				],
				[
						'db_drivers' 	=> ['mongodb'],
						'pattern' 		=> \Oka\PaginationBundle\Converter\Mongodb\RangeQueryExprConverter::PATTERN,
						'class' 		=> 'Oka\PaginationBundle\Converter\Mongodb\RangeQueryExprConverter'
				]
		];
		
		if (!empty($config['query_expr_converters'])) {
			foreach ($config['query_expr_converters'] as $converter) {
				$mapConverters[] = $converter;
			}
		}
		
		$definition = $container->getDefinition('oka_pagination.query_builder_manipulator');
		$definition->replaceArgument(0, $mapConverters);
	}
}
