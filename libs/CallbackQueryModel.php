<?php
/**
 * Copyright (c) since 2010 Martin Takáč (http://martin.takac.name)
 * @license   https://opensource.org/licenses/MIT MIT
 */

namespace Taco\Nette\Forms;


/**
 * Simple QueryModel implementation by pair of callbacks.
 * @author Martin Takáč <martin@takac.name>
 */
class CallbackQueryModel implements QueryModel
{

	/**
	 * @var calback(term:string, page:numeric, pageSize:numeric) -> {total:numeric, items:array of {id:string, label:string}}
	 */
	private $dataquery;


	/**
	 * @var calback(id:string) -> {id:string, label:string}
	 */
	private $dataread;


	/**
	 * @param calback(term:string, page:numeric, pageSize:numeric) -> {total:numeric, items:array of {id:string, label:string}}
	 * @param calback(id:string) -> {id:string, label:string}
	 */
	function __construct($dataquery, $dataread)
	{
		$this->dataquery = $dataquery;
		$this->dataread = $dataread;
	}



	/**
	 * @param string $term
	 * @param numeric $page
	 * @param numeric $pageSize
	 * @return {total:numeric, items:array of {id:string, label:string}}
	 */
	function range(string $term, int $page, int $pageSize, array $args = [])
	{
		$fn = $this->dataquery;
		return $fn($term, $page, $pageSize);
	}



	/**
	 * One for setDefaults();
	 * @param string|int $id
	 * @return array{id:string, label:string}
	 */
	function read(string|int $id)
	{
		$fn = $this->dataread;
		return $fn($id);
	}

}
