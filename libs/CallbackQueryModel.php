<?php declare(strict_types = 1);

/**
 * Copyright (c) since 2004 Martin Takáč (http://martin.takac.name)
 * @license https://opensource.org/licenses/MIT MIT
 */

namespace Taco\Nette\Forms;

use Nette\Utils\Validators;
use stdClass;


/**
 * Simple QueryModel implementation by pair of callbacks.
 * @author Martin Takáč <martin@takac.name>
 */
class CallbackQueryModel implements QueryModel
{

	/**
	 * @var callable(string, int, int): \stdClass
	 */
	private $dataquery;

	/**
	 * @var callable(string|int): (array{id: string, label: string, ...}|null)
	 */
	private $dataread;

	/**
	 * @param callable(string, int, int): \stdClass $dataquery
	 * @param callable(string|int): (array{id: string, label: string, ...}|null) $dataread
	 */
	function __construct(callable $dataquery, callable $dataread)
	{
		$this->dataquery = $dataquery;
		$this->dataread = $dataread;
	}



	/**
	 * Items may carry arbitrary extra fields besides id/label (e.g. flag, icon,
	 * description) for the JS renderer to pick up.
	 * @param array<string, mixed> $args
	 * @return \stdClass {total: int, items: array<array{id: string, label: string, ...}>}
	 */
	function range(string $term, int $page, int $pageSize, array $args = []): stdClass
	{
		$fn = $this->dataquery;
		return $fn($term, $page, $pageSize);
	}



	/**
	 * One for setDefaults();
	 * @param string|int $id
	 * @return array{id: string, label: string, ...}|null
	 */
	function read($id): ?array
	{
		Validators::assert($id, 'string:1..');
		$fn = $this->dataread;
		return $fn($id);
	}

}
