<?php declare(strict_types = 1);

namespace Tests;

use Nette\Application\Response as ApplicationResponse;
use Nette\Application\UI\Form;
use Nette\Application\UI\Presenter;
use Nette\Http\Request;
use Nette\Http\Response;
use Nette\Http\UrlScript;
use Nette\Routing\Router;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;
use Nette\Http\IRequest;


abstract class PresenterTestCase extends TestCase
{

	/**
	 * Plain form, not attached to any presenter. Enough for controls'
	 * setValue()/getValue() logic, which never needs getPresenter().
	 */
	protected function createForm(): Form
	{
		return new Form();
	}



	/**
	 * Form attached to a bare Presenter, so SignalControl::getPresenter()
	 * resolves and signals can be dispatched/handled.
	 * @return array{0: Presenter, 1: Form}
	 */
	protected function createFormWithPresenter(): array
	{
		$presenter = new class extends Presenter {

};

		$router = new class implements Router {

			function match(IRequest $httpRequest): ?array // @phpcs:ignore SlevomatCodingStandard.TypeHints.ReturnTypeHint.MissingTraversableTypeHintSpecification
			{
				return null;
			}



			function constructUrl(array $params, UrlScript $refUrl): ?string // @phpcs:ignore SlevomatCodingStandard.TypeHints.ParameterTypeHint.MissingTraversableTypeHintSpecification
			{
				return 'http://localhost/?' . http_build_query($params);
			}

};

		$presenter->injectPrimary(
			null,
			null,
			$router,
			new Request(new UrlScript('http://localhost/')),
			new Response()
		);

		// A presenter is normally named/started by the Application during
		// run(); link() needs both to be set, which we skip here on purpose.
		$presenter->setParent(null, 'Test');
		$this->setPresenterAction($presenter, 'default');

		$form = new Form($presenter, 'form');

		return [$presenter, $form];
	}



	/**
	 * Reads the response a Presenter was about to send via sendResponse(),
	 * which always aborts via an exception instead of returning it.
	 */
	protected function getSentResponse(Presenter $presenter): ?ApplicationResponse
	{
		$ref = new ReflectionProperty(Presenter::class, 'response');
		$ref->setAccessible(true);

		return $ref->getValue($presenter);
	}



	private function setPresenterAction(Presenter $presenter, string $action): void
	{
		$ref = new ReflectionProperty(Presenter::class, 'action');
		$ref->setAccessible(true);
		$ref->setValue($presenter, $action);
	}

}
