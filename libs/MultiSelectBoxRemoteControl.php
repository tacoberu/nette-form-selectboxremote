<?php declare(strict_types = 1);

/**
 * Copyright (c) since 2004 Martin Takáč (http://martin.takac.name)
 * @license https://opensource.org/licenses/MIT MIT
 */

namespace Taco\Nette\Forms\Controls;

use Nette;
use Nette\Utils\Validators;
use Nette\Forms\Controls;
use Nette\Application\UI\ISignalReceiver;
use Nette\Application\Responses\JsonResponse;
use Taco\Nette\Forms\QueryModel;


/**
 * Select, which load options from remote.
 * - The records load only this control by injected model.
 * - The records load remote by AJAX.
 * - Validate selectdata in side backend.
 * - Infinite scrolling content.
 * - In frontend support for Select2, Change, selectmenu.
 *
 * @author Martin Takáč <martin@takac.name>
 */
class MultiSelectBoxRemoteControl extends Controls\MultiSelectBox implements ISignalReceiver
{

	/**
	 * Díky tomuto traitu je možné tomuto prvku posílat signály.
	 */
	use SignalControl;

	/**
	 * Minimální počet znaků, než se začne dotazovat serveru.
	 */
	private int $minInput = 1;

	/**
	 * Size of page
	 */
	private int $pageSize = 10;

	/**
	 * @var array<array{id: string, label: string}>
	 */
	private array $selectedItems = [];

	/**
	 * @param string $label Popisek prvku.
	 * @param int|null $pageSize
	 */
	function __construct(private QueryModel $model, $label = NULL, $pageSize = NULL)
	{
		parent::__construct($label);

		if ($pageSize) {
			$this->pageSize = (int) $pageSize;
		}
	}



	/**
	 * @param int $val Set optional PageSize
	 * @return static
	 */
	function setPageSize($val): self
	{
		$this->pageSize = (int) $val;
		return $this;
	}



	/**
	 * Dotaz zpátky sem na komponentu ohledně balíčku záznamů.
	 * @param string $term Vyhledávaný text.
	 * @param numeric $page O kolikátou stránku se jedná. Počítáno o 1.
	 * @param numeric|null $pageSize
	 */
	function handleRange($term, $page, $pageSize = NULL): void
	{
		Validators::assert($term, 'string|null');
		Validators::assert($page, 'numeric|null');
		list($term, $page, $pageSize) = $this->prepareRequestRange();
		if ($pageSize === NULL) {
			$pageSize = $this->pageSize;
		}
		$page = (int) $page;
		$pageSize = (int) $pageSize;
		if ( $pageSize === 0) {
			$pageSize = $this->pageSize;
		}
		if ( $page === 0) {
			$page = 1;
		}

		$payload = $this->model->range((string)$term, $page, $pageSize);
		Validators::assertField((array)$payload, 'total', 'numeric');
		Validators::assertField((array)$payload, 'items', 'array');

		// Zda existuje další záznam.
		$payload->isMoreResults = ($page * $pageSize <= $payload->total);
		$payload->term = $term;
		$payload->page = (int) $page;
		$payload->pageSize = $pageSize;

		// Výsledky vyhledávání.
		$payload->items = array_values($payload->items);

		$this->getPresenter()->sendResponse(new JsonResponse($payload));
	}



	function getControl(): Nette\Utils\Html
	{
		$el = parent::getControl();
		$el->data('type', 'remoteselect');
		$el->data('data-url', $this->link('//range!', []));
		$el->data('min-input', $this->minInput);

		return $el;
	}



	/**
	 * Loads HTTP data.
	 */
	function loadHttpData(): void
	{
		$values = $this->getHttpData(Nette\Forms\Form::DATA_TEXT);


		if (empty($values) /*|| (is_array($this->disabled) && isset($this->disabled[$value])) */){
			$this->value = [];
		}
		else {
			$this->setValue($values);
		}
	}



	/**
	 * Sets selected items (by keys).
	 * @param array<int, string|int>|null $values
	 * @return self
	 * @internal
	 */
	function setValue($values)
	{
		$this->selectedItems = [];

		if (empty($values)) {
			$this->value = [];
			return $this;
		}

		$items = [];
		foreach ($values as $id) {
			$row = $this->fetchOne((string) $id);
			if (empty($row)) {
				throw new Nette\InvalidArgumentException("Value '$id' is not found of resource.");
			}
			$this->selectedItems[] = $row;
			$items[$row['id']] = $row['label'];
		}
		$this->items = $items;
		$this->value = $values;
		return $this;
	}



	/**
	 * Returns selected key.
	 * @return array<int, string|int>
	 */
	function getValue(): array
	{
		if ($this->selectedItems === []) {
			return [];
		}

		$xs = [];
		foreach ($this->selectedItems as $x) {
			$xs[] = $x['id'];
		}
		return $xs;
	}



	/**
	 * Returns selected values.
	 * @return array<int, string>
	 */
	function getSelectedItems(): array
	{
		if ($this->selectedItems === []) {
			return [];
		}
		$xs = [];
		foreach ($this->selectedItems as $x) {
			$xs[] = $x['label'];
		}
		return $xs;
	}



	/**
	 * @return array{id: string, label: string}|null
	 */
	private function fetchOne(string $id): ?array
	{
		Validators::assert($id, 'string');
		if ($value = $this->model->read($id)) {
			return (array) $value;
		}
		return NULL;
	}



	/**
	 * @FIXME
	 * Protože parametry jsou navzdory zvyklostem posílány absolutně.
	 * @return array{0: mixed, 1: mixed, 2: mixed}
	 */
	private function prepareRequestRange(): array
	{
		$arr = $this->getPresenter()->getParameters();
		unset($arr['do']);
		unset($arr['action']);
		return [$arr['term'] ?? '', $arr['page'] ?? 1, $arr['pageSize'] ?? NULL];
	}

}
