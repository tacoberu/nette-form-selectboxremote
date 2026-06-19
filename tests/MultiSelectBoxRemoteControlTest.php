<?php declare(strict_types = 1);

namespace Tests;

use Nette\Application\AbortException;
use Nette\Application\Responses\JsonResponse;
use Nette\Application\UI\BadSignalException;
use Nette\InvalidArgumentException;
use Nette\InvalidStateException;
use Taco\Nette\Forms\CallbackQueryModel;
use Taco\Nette\Forms\Controls\MultiSelectBoxRemoteControl;
use stdClass;


class MultiSelectBoxRemoteControlTest extends PresenterTestCase
{

	function testSetValueAndGetValueRoundTrip(): void
	{
		$model = $this->createModel([
			'1' => ['id' => '1', 'label' => 'One'],
			'2' => ['id' => '2', 'label' => 'Two'],
		]);
		$control = new MultiSelectBoxRemoteControl($model, 'Label');
		$this->createForm()->addComponent($control, 'sel');

		$control->setValue(['1', '2']);

		$this->assertSame(['1', '2'], $control->getValue());
		$this->assertSame(['One', 'Two'], $control->getSelectedItems());
	}



	function testGetValueIsEmptyWhenNothingSelected(): void
	{
		$model = $this->createModel();
		$control = new MultiSelectBoxRemoteControl($model, 'Label');
		$this->createForm()->addComponent($control, 'sel');

		$this->assertSame([], $control->getValue());
		$this->assertSame([], $control->getSelectedItems());
	}



	function testSetValueAcceptsEmptyArrayAndClearsSelection(): void
	{
		$model = $this->createModel(['1' => ['id' => '1', 'label' => 'One']]);
		$control = new MultiSelectBoxRemoteControl($model, 'Label');
		$this->createForm()->addComponent($control, 'sel');

		$control->setValue(['1']);
		$control->setValue([]);

		$this->assertSame([], $control->getValue());
	}



	function testSetValueThrowsForUnknownId(): void
	{
		$model = $this->createModel(['1' => ['id' => '1', 'label' => 'One']]);
		$control = new MultiSelectBoxRemoteControl($model, 'Label');
		$this->createForm()->addComponent($control, 'sel');

		$this->expectException(InvalidArgumentException::class);
		$control->setValue(['1', 'unknown']);
	}



	function testGetPresenterThrowsWhenFormHasNoPresenter(): void
	{
		$model = $this->createModel();
		$control = new MultiSelectBoxRemoteControl($model, 'Label');
		$this->createForm()->addComponent($control, 'sel');

		$this->expectException(InvalidStateException::class);
		$control->handleRange('', 1);
	}



	function testHandleRangeViaSignalBuildsJsonPayload(): void
	{
		$received = null;
		$model = new CallbackQueryModel(
			static function (string $term, int $page, int $pageSize) use (&$received): stdClass {
				$received = [$term, $page, $pageSize];
				$result = new stdClass();
				$result->total = 2;
				$result->items = [
					'x' => ['id' => '1', 'label' => 'One'],
					'y' => ['id' => '2', 'label' => 'Two'],
				];
				return $result;
			},
			static function () {
				return null;
			}
		);

		[$presenter, $form] = $this->createFormWithPresenter();
		$control = new MultiSelectBoxRemoteControl($model, 'Label');
		$form->addComponent($control, 'sel');
		$presenter->loadState(['term' => 'abc', 'page' => '2', 'pageSize' => '5']);

		try {
			$control->signalReceived('range');
			$this->fail('Expected AbortException.');
		}
		catch (AbortException) {
			// sendResponse() always aborts; that is the expected signal.
		}

		$this->assertSame(['abc', 2, 5], $received);

		$response = $this->getSentResponse($presenter);
		$this->assertInstanceOf(JsonResponse::class, $response);
		$payload = $response->getPayload();

		$this->assertSame(2, $payload->total);
		$this->assertSame([
			['id' => '1', 'label' => 'One'],
			['id' => '2', 'label' => 'Two'],
		], $payload->items);
		$this->assertFalse($payload->isMoreResults);
		$this->assertSame('abc', $payload->term);
		$this->assertSame(2, $payload->page);
		$this->assertSame(5, $payload->pageSize);
	}



	function testHandleRangeFallsBackToControlPageSizeWhenMissing(): void
	{
		$received = null;
		$model = new CallbackQueryModel(
			static function (string $term, int $page, int $pageSize) use (&$received): stdClass {
				$received = [$term, $page, $pageSize];
				$result = new stdClass();
				$result->total = 0;
				$result->items = [];
				return $result;
			},
			static function () {
				return null;
			}
		);

		[$presenter, $form] = $this->createFormWithPresenter();
		$control = new MultiSelectBoxRemoteControl($model, 'Label');
		$control->setPageSize(7);
		$form->addComponent($control, 'sel');
		$presenter->loadState([]); // no term/page/pageSize supplied

		try {
			$control->signalReceived('range');
			$this->fail('Expected AbortException.');
		}
		catch (AbortException) {
			// expected
		}

		$this->assertSame(['', 1, 7], $received);
	}



	function testSignalReceivedThrowsForUnknownSignal(): void
	{
		$model = $this->createModel();
		[, $form] = $this->createFormWithPresenter();
		$control = new MultiSelectBoxRemoteControl($model, 'Label');
		$form->addComponent($control, 'sel');

		$this->expectException(BadSignalException::class);
		$control->signalReceived('unknown');
	}



	function testGetControlRendersDataAttributes(): void
	{
		$model = $this->createModel();
		[, $form] = $this->createFormWithPresenter();
		$control = new MultiSelectBoxRemoteControl($model, 'Label');
		$form->addComponent($control, 'sel');

		$el = $control->getControl();

		$this->assertSame('remoteselect', $el->getAttribute('data-type'));
		$this->assertSame(1, $el->getAttribute('data-min-input'));
		$this->assertStringContainsString('do=form-sel-range', (string) $el->getAttribute('data-data-url'));
	}



	/**
	 * @param array<string, array{id: string, label: string}> $rows
	 */
	private function createModel(array $rows = []): CallbackQueryModel
	{
		return new CallbackQueryModel(
			static function () {
				return null;
			},
			static function ($id) use ($rows) {
				return $rows[$id] ?? null;
			}
		);
	}

}
