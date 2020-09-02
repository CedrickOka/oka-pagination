<?php
namespace Oka\PaginationBundle\Tests\Pagination\FilterExpression\ODM;

use Oka\PaginationBundle\Pagination\FilterExpression\ODM\RangeFilterExpression;
use Oka\PaginationBundle\Tests\Document\Page;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 *
 * @author Cedrick Oka Baidai <okacedrick@gmail.com>
 *
 */
class RangeFilterExpressionTest extends KernelTestCase
{
	/**
	 * @var \Doctrine\ODM\MongoDB\DocumentManager
	 */
	protected $documentManager;
	
	public function setUp() :void
	{
		static::bootKernel();
		
		$this->documentManager = static::$container->get('doctrine_mongodb.odm.document_manager');
	}
	
	/**
	 * @covers
	 */
	public function testThatFilterCanEvaluateExpression()
	{
		$filterExpression = new RangeFilterExpression();
		$queryBuilder = $this->documentManager->createQueryBuilder(Page::class);
		
		$result = $filterExpression->evaluate($queryBuilder, 'field', 'range[1,2]', 'int');
		/** @var \Doctrine\ODM\MongoDB\Query\Expr $expr */
		$expr = $result->getExpr();
		
		$this->assertEquals(['$and' => [['field' => ['$gte' => 1]], ['field' => ['$lte' => 2]]]], $expr->getQuery());
		$this->assertEmpty($result->getValues());
		
		$result = $filterExpression->evaluate($queryBuilder, 'field', 'range]1,2]', 'int');
		/** @var \Doctrine\ODM\MongoDB\Query\Expr $expr */
		$expr = $result->getExpr();
		
		$this->assertEquals(['$and' => [['field' => ['$gt' => 1]], ['field' => ['$lte' => 2]]]], $expr->getQuery());
		$this->assertEmpty($result->getValues());
		
		$result = $filterExpression->evaluate($queryBuilder, 'field', 'range[1,2[', 'int');
		/** @var \Doctrine\ODM\MongoDB\Query\Expr $expr */
		$expr = $result->getExpr();
		
		$this->assertEquals(['$and' => [['field' => ['$gte' => 1]], ['field' => ['$lt' => 2]]]], $expr->getQuery());
		$this->assertEmpty($result->getValues());
		
		$result = $filterExpression->evaluate($queryBuilder, 'field', 'range]1,2[', 'int');
		/** @var \Doctrine\ODM\MongoDB\Query\Expr $expr */
		$expr = $result->getExpr();
		
		$this->assertEquals(['$and' => [['field' => ['$gt' => 1]], ['field' => ['$lt' => 2]]]], $expr->getQuery());
		$this->assertEmpty($result->getValues());
		
		$result = $filterExpression->evaluate($queryBuilder, 'field', 'range]1,[', 'int');
		/** @var \Doctrine\ODM\MongoDB\Query\Expr $expr */
		$expr = $result->getExpr();
		
		$this->assertEquals(['field' => ['$gt' => 1]], $expr->getQuery());
		$this->assertEmpty($result->getValues());
		
		$result = $filterExpression->evaluate($queryBuilder, 'field', 'range],2[', 'int');
		/** @var \Doctrine\ODM\MongoDB\Query\Expr $expr */
		$expr = $result->getExpr();
		
		$this->assertEquals(['field' => ['$lt' => 2]], $expr->getQuery());
		$this->assertEmpty($result->getValues());
	}
}