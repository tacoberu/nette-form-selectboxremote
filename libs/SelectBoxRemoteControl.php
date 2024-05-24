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
class SelectBoxRemoteControl extends Controls\SelectBox implements ISignalReceiver
{

 /**
	 * Díky tomuto traitu je možné tomuto prvku posílat signály.
	 */
	use SignalControl;

	private QueryModel $model;

	/**
	 * Minimální počet znaků, než se začne dotazovat serveru.
	 */
	private int $minInput = 1;

	/**
	 * Size of page
	 */
	private int $pageSize = 10;

	/**
	 * @var array{id: string, label: string}|null
	 */
	private ?array $item = null;

	/**
	 * @param string $label Popisek prvku.
	 * @param int|null $pageSize
	 */
	function __construct(QueryModel $model, $label = NULL, $pageSize = NULL)
	{
		$this->model = $model;

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
		if ($this->getPrompt()) {
			$el->data('prompt', $this->getPrompt());
		}

		return $el;
	}



	/**
	 * Loads HTTP data.
	 */
	function loadHttpData(): void
	{
		$value = $this->getHttpData(Nette\Forms\Form::DATA_TEXT);
		if (($value === NULL) || ($this->getPrompt() && $value === '')){
			$this->value = NULL;
		}
		else {
			$this->setValue($value);
		}
	}



	/**
	 * Sets selected item (by key).
	 * @param string|int|null $value
	 * @return self
	 * @internal
	 */
	function setValue($value)
	{
		Validators::assert($value, 'string|int|null');
		if (/*$this->checkAllowedValues && */
			$value !== NULL
			&& in_array($this->fetchOne((string) $value), [null, []], true)
		) {
			throw new Nette\InvalidArgumentException("Value '$value' is not found of resource.");
		}
		if ($value && $this->item = $this->fetchOne((string) $value)) {
			$this->value = $this->item['id'];
			$this->items = [$this->item['id'] => $this->item['label']];
		}
		else {
			$this->value = NULL;
		}

		return $this;
	}



	/**
	 * Returns selected key.
	 * @return string|int|null
	 */
	function getValue()
	{
		if (empty($this->value)) {
			return NULL;
		}

		if ( ! $this->item = $this->fetchOne($this->value)) {
			return NULL;
		}
		$this->items = [$this->item['id'] => $this->item['label']];
		return $this->item['id'];
	}



	/**
	 * Returns selected value.
	 * @return mixed
	 */
	function getSelectedItem()
	{
		$item = $this->item;
		if ($item === NULL) {
			return NULL;
		}
		return $item['label'];
	}



	/**
	 * @param string $id
	 * @return array{id: string, label: string}|null
	 */
	private function fetchOne($id): ?array
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
