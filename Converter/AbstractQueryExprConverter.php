<?php
namespace Oka\PaginationBundle\Converter;

/**
 *
 * @author Cedrick Oka Baidai <okacedrick@gmail.com>
 *
 */
abstract class AbstractQueryExprConverter implements QueryExprConverterInterface
{
	/**
	 * {@inheritDoc}
	 * @see \Oka\PaginationBundle\Converter\QueryExprConverter::supports()
	 */
	public function supports($dbDriver, $exprValue)
	{
		return false;
	}
}