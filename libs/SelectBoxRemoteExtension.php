<?php
/**
 * Copyright (c) since 2010 Martin Takáč (http://martin.takac.name)
 * @license   https://opensource.org/licenses/MIT MIT
 */

namespace Taco\Nette\Forms\Controls;

use Nette\DI\CompilerExtension;
use Nette\Schema\Schema;
use Nette\Schema\Expect;
use Nette\PhpGenerator\ClassType;
use LogicException;


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
		// @var stdClass $config
		$config = $this->getConfig();
		$init = $class->getMethods()['initialize'];
		$init->addBody(SelectBoxRemoteControl::class . '::register(?);', [$config->name]);
		$init->addBody(MultiSelectBoxRemoteControl::class . '::register(?);', [$config->name]);
	}

}
