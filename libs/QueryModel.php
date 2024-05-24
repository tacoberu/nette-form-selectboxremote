<?php declare(strict_types = 1);

/**
 * Copyright (c) since 2004 Martin Takáč (http://martin.takac.name)
 * @license https://opensource.org/licenses/MIT MIT
 */

namespace Taco\Nette\Forms;

/**
 * @author Martin Takáč <martin@takac.name>
 */
interface QueryModel
{

	/**
	 * @param array<string, mixed> $args
	 * @return \stdClass {total: int, items: array<array{id: string, label: string}>}
	 */
	function range(string $term, int $page, int $pageSize, array $args = []);



	/**
	 * @param string|int $id
	 * @return array{id: string, label: string}|null
	 */
	function read($id);

}
