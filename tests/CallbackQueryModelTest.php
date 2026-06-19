<?php declare(strict_types = 1);

namespace Tests;

use Nette\Utils\AssertionException;
use PHPUnit\Framework\TestCase;
use Taco\Nette\Forms\CallbackQueryModel;
use stdClass;


class CallbackQueryModelTest extends TestCase
{

	function testRangeForwardsCallToCallback(): void
	{
		$expected = new stdClass();
		$expected->total = 2;
		$expected->items = [];
		$received = null;

		$model = new CallbackQueryModel(
			static function (string $term, int $page, int $pageSize) use ($expected, &$received) {
				$received = [$term, $page, $pageSize];
				return $expected;
			},
			static function ($id) {
				return null;
			}
		);

		$result = $model->range('abc', 2, 5);

		$this->assertSame(['abc', 2, 5], $received);
		$this->assertSame($expected, $result);
	}



	function testReadForwardsCallToCallbackAndReturnsResult(): void
	{
		$row = ['id' => '1', 'label' => 'One'];

		$model = new CallbackQueryModel(
			static function () {
				return null;
			},
			static function ($id) use ($row) {
				return $id === '1'
					? $row
					: null;
			}
		);

		$this->assertSame($row, $model->read('1'));
	}



	function testReadReturnsNullWhenCallbackReturnsNull(): void
	{
		$model = new CallbackQueryModel(
			static function () {
				return null;
			},
			static function ($id) {
				return null;
			}
		);

		$this->assertNull($model->read('unknown'));
	}



	function testReadRejectsEmptyId(): void
	{
		$model = new CallbackQueryModel(
			static function () {
				return null;
			},
			static function ($id) {
				return null;
			}
		);

		$this->expectException(AssertionException::class);
		$model->read('');
	}

}
