<?php
/**
 * Copyright (c) since 2004 Martin Takáč (http://martin.takac.name)
 * @license   https://opensource.org/licenses/MIT MIT
 */

namespace Taco\Nette\Forms;

use Nette\Utils\Validators;


/**
 * Simple QueryModel implementation by pair of callbacks.
 * @author Martin Takáč <martin@takac.name>
 */
class CallbackQueryModel implements QueryModel
{

	/**
	 * callback(term:string, page:numeric, pageSize:numeric) -> {total:numeric, items:array of {id:string, label:string}}
	 * @var callable
	 */
	private $dataquery;


	/**
	 * callback(id:string) -> {id:string, label:string}
	 * @var callable
	 */
	private $dataread;


	/**
	 * @param callable $dataquery (term:string, page:numeric, pageSize:numeric) -> {total:numeric, items:array of {id:string, label:string}}
	 * @param callable $dataread (id:string) -> {id:string, label:string}
	 */
	function __construct($dataquery, $dataread)
	{
		$this->dataquery = $dataquery;
		$this->dataread = $dataread;
	}



	function range(string $term, int $page, int $pageSize, array $args = [])
	{
		$fn = $this->dataquery;
		return $fn($term, $page, $pageSize);
	}



	function read(string|int $id)
	{
		Validators::assert($id, 'string:1..');
		$fn = $this->dataread;
		return $fn($id);
	}

}
