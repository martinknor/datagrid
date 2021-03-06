<?php

namespace Ublaboo\DataGrid\Tests\Cases;

use Tester\TestCase,
	Tester\Assert,
	Mockery,
	Ublaboo\DataGrid\DataGrid,
	Ublaboo;

require __DIR__ . '/../bootstrap.php';
require __DIR__ . '/../Files/XTestingDataGridFactory.php';

final class ColumnActionTest extends TestCase
{

	/**
	 * @var DataGrid
	 */
	private $grid;


	public function setUp()
	{
		$factory = new Ublaboo\DataGrid\Tests\Files\XTestingDataGridFactory;
		$this->grid = $factory->createXTestingDataGrid();
	}


	public function render($column)
	{
		$item = new Ublaboo\DataGrid\Row($this->grid, ['id' => 1, 'name' => 'John'], 'id');

		return (string) $column->render($item);
	}


	public function testActionDuplcitColumn()
	{
		$action = $this->grid->addAction('action', 'Do', 'doStuff!');

		$grid = $this->grid;
		$add_action = function() use ($grid) {
			$grid->addAction('action', 'Do', 'doStuff!');
		};

		Assert::exception(
			$add_action,
			'Ublaboo\DataGrid\DataGridException',
			'There is already action at key [action] defined.'
		);
	}


	public function testActionLink()
	{
		$action = $this->grid->addAction('action', 'Do', 'doStuff!');

		Assert::same(
			'<a href="doStuff!?id=1" class="btn btn-xs btn-default">Do</a>',
			$this->render($action)
		);

		$action = $this->grid->addAction('detail', 'Do');

		Assert::same(
			'<a href="detail?id=1" class="btn btn-xs btn-default">Do</a>',
			$this->render($action)
		);

		$action = $this->grid->addAction('title', 'Do', 'detail', ['id', 'name']);
		Assert::same(
			'<a href="detail?id=1&amp;name=John" class="btn btn-xs btn-default">Do</a>',
			$this->render($action)
		);

		$action = $this->grid->addAction('title2', 'Do', 'detail', [
			'id' => 'name', 'name' => 'id'
		]);
		Assert::same(
			'<a href="detail?id=John&amp;name=1" class="btn btn-xs btn-default">Do</a>',
			$this->render($action)
		);
	}


	public function testActionIcon()
	{
		$action = $this->grid->addAction('action', 'Do', 'doStuff!');

		DataGrid::$icon_prefix = 'icon-';
		$action->icon('user');

		Assert::same(
			'<a href="doStuff!?id=1" class="btn btn-xs btn-default"><span class="icon-user"></span>&nbsp;Do</a>',
			$this->render($action)
		);
	}


	public function testActionClass()
	{
		$action = $this->grid->addAction('action', 'Do', 'doStuff!')->class('btn');

		Assert::same('<a href="doStuff!?id=1" class="btn">Do</a>', $this->render($action));

		$action->setClass(NULL);

		Assert::same('<a href="doStuff!?id=1">Do</a>', $this->render($action));
	}


	public function testActionTitle()
	{
		$action = $this->grid->addAction('action', 'Do', 'doStuff!')->title('hello');

		Assert::same(
			'<a href="doStuff!?id=1" title="hello" class="btn btn-xs btn-default">Do</a>',
			$this->render($action)
		);
	}


	public function testActionConfirm()
	{
		$action = $this->grid->addAction('action', 'Do', 'doStuff!')->confirm('Really?');

		Assert::same(
			'<a href="doStuff!?id=1" class="btn btn-xs btn-default" data-confirm="Really?">Do</a>',
			$this->render($action)
		);
	}

}


$test_case = new ColumnActionTest;
$test_case->run();
