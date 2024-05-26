<?php declare(strict_types = 1);

/**
 * Copyright (c) since 2010 Martin Takáč (http://martin.takac.name)
 * @license https://opensource.org/licenses/MIT MIT
 */

namespace Taco\Nette\Forms\Controls;

use Nette\DI\CompilerExtension;
use Nette\Schema\Schema;
use Nette\Schema\Expect;
use Nette\PhpGenerator\ClassType;
use function assert;
use stdClass;


class SelectBoxRemoteExtension extends CompilerExtension
{

	function getConfigSchema(): Schema
	{
		return Expect::structure([
			'name' => Expect::string()->default('SelectRemote'),
		]);
	}



	function afterCompile(ClassType $class): void
	{
		$config = $this->getConfig();
		assert($config instanceof stdClass);
		$init = $class->getMethods()['initialize'];
		$init->addBody(SelectBoxRemoteControl::class . '::register(?);', [$config->name]);
		$init->addBody(MultiSelectBoxRemoteControl::class . '::register(?);', [$config->name]);
	}

}
