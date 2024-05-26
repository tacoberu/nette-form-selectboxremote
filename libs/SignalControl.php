<?php
/**
 * This file is part of the Nella Project (http://nella-project.org).
 *
 * Copyright (c) Patrik Votoček (http://patrik.votocek.cz)
 *
 * For the full copyright and license information,
 * please view the file LICENSE.md that was distributed with this source code.
 */

namespace Taco\Nette\Forms\Controls;

use Nette\Application\UI\Form;
use Nette\Application\UI\Presenter;
use Nette\Application\UI\PresenterComponentReflection;
use Nette\ComponentModel\IContainer;
use Nette\ComponentModel\IComponent;
use Nette\Utils\Strings;


/**
 * @credits Patrik Votoček
 */
trait SignalControl
{

	/** @var array|mixed[] */
	private $params = array();

	protected function validateParent(IContainer $parent) : void
	{
		parent::validateParent($parent);

		$this->monitor('Nette\Application\UI\Presenter');
	}

	/**
	 * Returns a fully-qualified name that uniquely identifies the component
	 * within the presenter hierarchy.
	 *
	 * @return string
	 */
	private function getUniqueId()
	{
		return $this->lookupPath('Nette\Application\UI\Presenter', TRUE);
	}

	/**
	 * This method will be called when the component (or component's parent)
	 * becomes attached to a monitored object. Do not call this method yourself.
	 */
	protected function attached(IComponent $component) : void
	{
		if (!$this instanceof \Nette\Application\UI\ISignalReceiver) {
			throw new \Nette\InvalidStateException(
				sprintf('%s must implements Nette\Application\UI\ISignalReceiver', get_called_class())
			);
		}
		if (!$component instanceof Form && !$component instanceof Presenter) {
			throw new \Nette\InvalidStateException(
				sprintf('%s must be attached to Nette\Application\UI\Form', get_called_class())
			);
		}

		if ($component instanceof Presenter) {
			$this->params = $component->popGlobalParameters($this->getUniqueId());
		}

		parent::attached($component);
	}


	function signalReceived(string $signal) : void
	{
		$methodName = sprintf('handle%s', \Nette\Utils\Strings::firstUpper($signal));
		if (!method_exists($this, $methodName)) {
			throw new \Nette\Application\UI\BadSignalException(sprintf('Method %s does not exist', $methodName));
		}

		$presenterComponentReflection = new PresenterComponentReflection(get_called_class());
		$methodReflection = $presenterComponentReflection->getMethod($methodName);
		$args = $presenterComponentReflection->combineArgs($methodReflection, $this->params);
		$methodReflection->invokeArgs($this, $args);
	}

	protected function getPresenter(): Presenter
	{
		return $this->getForm()->getPresenter(); // @phpstan-ignore-line
	}

	/**
	 * Generates URL to presenter, action or signal.
	 *
	 * @param string $destination in format "signal!"
	 * @param array|mixed $args
	 */
	protected function link($destination, $args = array()): string
	{
		$destination = trim($destination);
		if (!Strings::endsWith($destination, '!') || Strings::contains($destination, ':')) {
			throw new \Nette\InvalidArgumentException(sprintf('%s support only own signals.', get_called_class()));
		}

		$args = is_array($args) ? $args : array_slice(func_get_args(), 1);
		$fullPath = Strings::startsWith($destination, '//');
		if ($fullPath) {
			$destination = Strings::substring($destination, 2);
		}
		$destination = sprintf('%s%s-%s', $fullPath ? '//' : '', $this->getUniqueId(), $destination);
		$newArgs = [];
		foreach ($args as $key => $value) {
			$newArgs[sprintf('%s-%s', $this->getUniqueId(), $key)] = $value;
		}
		$args = $newArgs;

		return $this->getPresenter()->link($destination, $args); // @phpstan-ignore-line
	}

}
