<?php
/**
 * Copyright (c) since 2004 Martin Takáč (http://martin.takac.name)
 * @license   https://opensource.org/licenses/MIT MIT
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
	 * @var numeric
	 */
	private $minInput = 1;


	/**
	 * Size of page
	 * @var numeric
	 */
	private $pageSize = 10;


	/**
	 * @var QueryModel
	 */
	private $model;


	/** @var [{id:string, label:string}] */
	private $selectedItems = [];


	/**
	 * @param string $label Popisek prvku.
	 */
	function __construct(QueryModel $model, $label = NULL, $pageSize = NULL)
	{
		parent::__construct($label);
		$this->model = $model;
		if ($pageSize) {
			$this->pageSize = (int) $pageSize;
		}
	}



	/**
	 * @param int $val Set optional PageSize
	 */
	function setPageSize($val)
	{
		$this->pageSize = (int) $val;
		return $this;
	}



	/**
	 * Dotaz zpátky sem na komponentu ohledně balíčku záznamů.
	 * @param string $term Vyhledávaný text.
	 * @param numeric $page O kolikátou stránku se jedná. Počítáno o 1.
	 */
	function handleRange($term, $page, $pageSize = NULL)
	{
		Validators::assert($term, 'string|null');
		Validators::assert($page, 'numeric|null');
		list($term, $page, $pageSize) = $this->prepareRequestRange();
		if ($pageSize === NULL) {
			$pageSize = $this->pageSize;
		}
		$page = (int) $page;
		$pageSize = (int) $pageSize;
		if ( ! $pageSize) {
			$pageSize = $this->pageSize;
		}
		if ( ! $page) {
			$page = 1;
		}

		$payload = $this->model->range((string)$term, $page, $pageSize);
		Validators::assertField((array)$payload, 'total', 'numeric');
		Validators::assertField((array)$payload, 'items', 'array');

		// Zda existuje další záznam.
		$payload->isMoreResults = ($page * $pageSize <= $payload->total);
		$payload->term = $term;
		$payload->page = (int) $page;
		$payload->pageSize = (int) $pageSize;

		// Výsledky vyhledávání.
		$payload->items = array_values($payload->items);

		$this->getPresenter()->sendResponse(new JsonResponse($payload));
	}



	function getControl() : Nette\Utils\Html
	{
		/** @var Nette\Utils\Html $el */
		$el = parent::getControl();
		$el->data('type', 'remoteselect');
		$el->data('data-url', $this->link('//range!', array()));
		$el->data('min-input', $this->minInput);

		return $el;
	}



	/**
	 * Loads HTTP data.
	 * @return void
	 */
	function loadHttpData() : void
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
	 * Sets selected item (by key).
	 * @param  string|int|null
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
			$row = $this->fetchOne($id);
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
	 */
	function getValue() : array
	{
		if (empty($this->selectedItems)) {
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
	 */
	function getSelectedItems() : array
	{
		if (empty($this->selectedItems)) {
			return [];
		}
		$xs = [];
		foreach ($this->selectedItems as $x) {
			$xs[] = $x['label'];
		}
		return $xs;
	}



	/**
	 * @param string $id
	 * @return {id:string, label:string}
	 */
	private function fetchOne($id)
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
	 * @return [term, page]
	 */
	private function prepareRequestRange()
	{
		$arr = $this->getPresenter()->getParameters();
		unset($arr['do']);
		unset($arr['action']);
		return array(
			isset($arr['term']) ? $arr['term'] : '',
			isset($arr['page']) ? $arr['page'] : 1,
			isset($arr['pageSize']) ? $arr['pageSize'] : NULL,
		);
	}

}
