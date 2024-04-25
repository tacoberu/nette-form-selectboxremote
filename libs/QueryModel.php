<?php
/**
 * Copyright (c) since 2010 Martin Takáč (http://martin.takac.name)
 * @license   https://opensource.org/licenses/MIT MIT
 */

namespace Taco\Nette\Forms;


/**
 * @author Martin Takáč <martin@takac.name>
 */
interface QueryModel
{

	/**
	 * @param string $term
	 * @param int $page
	 * @param int $pageSize
	 * @return array{total: int, items: array{id:string, label:string}}
	 */
	function range(string $term, int $page, int $pageSize, array $args = []);


	/**
	 * @return {id:string, label:string}
	 */
	function read(string|int $id);

}
