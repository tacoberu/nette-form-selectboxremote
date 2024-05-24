<?php declare(strict_types = 1);

/**
 * This file is part of the Nella Project (http://nella-project.org).
 *
 * Copyright (c) Patrik Votoček (http://patrik.votocek.cz)
 *
 * For the full copyright and license information,
 * please view the file LICENSE.md that was distributed with this source code.
 */

namespace Taco\Nette\Forms\Controls;

use Nette\Application\UI\ComponentReflection;
use Nette\Application\UI\Form;
use Nette\Application\UI\Presenter;
use Nette\ComponentModel\IContainer;
use Nette\ComponentModel\IComponent;
use Nette\Utils\Strings;
use Nette\Application\UI\ISignalReceiver;
use Nette\InvalidStateException;
use Nette\Application\UI\BadSignalException;
use Nette\InvalidArgumentException;


/**
 * @credits Patrik Votoček
 */
trait SignalControl
{

	/**
	 * @var array<mixed>
	 */
	private $params = [];

	function signalReceived(string $signal): void
	{
		$methodName = sprintf('handle%s', Strings::firstUpper($signal));
		if (!method_exists($this, $methodName)) {
			throw new BadSignalException(sprintf('Method %s does not exist', $methodName));
		}

		$presenterComponentReflection = new ComponentReflection(static::class);
		$methodReflection = $presenterComponentReflection->getMethod($methodName);
		$args = ComponentReflection::combineArgs($methodReflection, $this->params);
		$methodReflection->invokeArgs($this, $args);
	}



	protected function validateParent(IContainer $parent): void
	{
		parent::validateParent($parent);

		$this->monitor(Presenter::class, $this->attached(...), $this->detached(...));
	}



	/**
	 * This method will be called when the component (or component's parent)
	 * becomes attached to a monitored object. Do not call this method yourself.
	 */
	protected function attached(IComponent $component): void
	{
		if (!$this instanceof ISignalReceiver) {
			throw new InvalidStateException(
				sprintf('%s must implements Nette\Application\UI\ISignalReceiver', static::class)
			);
		}
		if (!$component instanceof Form && !$component instanceof Presenter) {
			throw new InvalidStateException(
				sprintf('%s must be attached to Nette\Application\UI\Form', static::class)
			);
		}

		if ($component instanceof Presenter) {
			$this->params = $component->popGlobalParameters($this->getUniqueId());
		}

		parent::attached($component);
	}



	protected function getPresenter(): Presenter
	{
		$form = $this->getForm();
		if (!$form instanceof Form) {
			throw new InvalidStateException(sprintf('%s must be attached to Nette\Application\UI\Form', static::class));
		}
		return $form->getPresenter();
	}



	/**
	 * Generates URL to presenter, action or signal.
	 *
	 * @param string $destination Signal name in format "signal!"
	 * @param array<string, mixed> $args
	 */
	protected function link(string $destination, array $args = []): string
	{
		$destination = trim($destination);
		if (!str_ends_with($destination, '!') || str_contains($destination, ':')) {
			throw new InvalidArgumentException(sprintf('%s support only own signals.', static::class));
		}

		$fullPath = str_starts_with($destination, '//');
		if ($fullPath) {
			$destination = Strings::substring($destination, 2);
		}
		$destination = sprintf('%s%s-%s', $fullPath ? '//' : '', $this->getUniqueId(), $destination);
		$newArgs = [];
		foreach ($args as $key => $value) {
			$newArgs[sprintf('%s-%s', $this->getUniqueId(), $key)] = $value;
		}
		$args = $newArgs;

		return $this->getPresenter()->link($destination, $args);
	}



	/**
	 * Returns a fully-qualified name that uniquely identifies the component
	 * within the presenter hierarchy.
	 *
	 * @return string
	 */
	private function getUniqueId()
	{
		return $this->lookupPath(Presenter::class, TRUE);
	}

}
