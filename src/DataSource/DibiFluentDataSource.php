<?php

/**
 * @copyright   Copyright (c) 2015 ublaboo <ublaboo@paveljanda.com>
 * @author      Pavel Janda <me@paveljanda.com>
 * @package     Ublaboo
 */

namespace Ublaboo\DataGrid\DataSource;

use DibiFluent;
use Nette\Utils\Callback;
use Nette\Utils\Strings;
use Ublaboo\DataGrid\Filter;

class DibiFluentDataSource implements IDataSource
{

	/**
	 * @var DibiFluent
	 */
	protected $data_source;

	/**
	 * @var array
	 */
	protected $data = [];

	/**
	 * @var string
	 */
	protected $primary_key;


	/**
	 * @param DibiFluent $data_source
	 * @param string $primary_key
	 */
	public function __construct(DibiFluent $data_source, $primary_key)
	{
		$this->data_source = $data_source;
		$this->primary_key = $primary_key;
	}


	/********************************************************************************
	 *                          IDataSource implementation                          *
	 ********************************************************************************/


	/**
	 * Get count of data
	 * @return int
	 */
	public function getCount()
	{
		return $this->data_source->count($this->primary_key);
	}


	/**
	 * Get the data
	 * @return array
	 */
	public function getData()
	{
		return $this->data ?: $this->data_source->fetchAll();
	}


	/**
	 * Filter data
	 * @param array $filters
	 * @return self
	 */
	public function filter(array $filters)
	{
		foreach ($filters as $filter) {
			if ($filter->isValueSet()) {
				if ($filter->hasConditionCallback()) {
					Callback::invokeArgs(
						$filter->getConditionCallback(),
						[$this->data_source, $filter->getValue()]
					);
				} else {
					if ($filter instanceof Filter\FilterText) {
						$this->applyFilterText($filter);
					} else if ($filter instanceof Filter\FilterSelect) {
						$this->applyFilterSelect($filter);
					} else if ($filter instanceof Filter\FilterDate) {
						$this->applyFilterDate($filter);
					} else if ($filter instanceof Filter\FilterDateRange) {
						$this->applyFilterDateRange($filter);
					} else if ($filter instanceof Filter\FilterRange) {
						$this->applyFilterRange($filter);
					}
				}
			}
		}

		return $this;
	}


	/**
	 * Filter data - get one row
	 * @param array $condition
	 * @return self
	 */
	public function filterOne(array $condition)
	{
		$this->data_source->where($condition)->limit(1);

		return $this;
	}


	/**
	 * Filter by date
	 * @param  Filter\FilterDate $filter
	 * @return void
	 */
	public function applyFilterDate(Filter\FilterDate $filter)
	{
		$conditions = $filter->getCondition();

		$date = \DateTime::createFromFormat($filter->getPhpFormat(), $conditions[$filter->getColumn()]);

		$this->data_source->where('DATE(%n) = ?', $filter->getColumn(), $date->format('Y-m-d'));
	}


	/**
	 * Filter by date range
	 * @param  Filter\FilterDateRange $filter
	 * @return void
	 */
	public function applyFilterDateRange(Filter\FilterDateRange $filter)
	{
		$conditions = $filter->getCondition();

		$value_from = $conditions[$filter->getColumn()]['from'];
		$value_to   = $conditions[$filter->getColumn()]['to'];

		if ($value_from) {
			$date_from = \DateTime::createFromFormat($filter->getPhpFormat(), $value_from);
			$date_from->setTime(0, 0, 0);

			$this->data_source->where('DATE(%n) >= ?', $filter->getColumn(), $date_from);
		}

		if ($value_to) {
			$date_to = \DateTime::createFromFormat($filter->getPhpFormat(), $value_to);
			$date_to->setTime(23, 59, 59);

			$this->data_source->where('DATE(%n) <= ?', $filter->getColumn(), $date_to);
		}
	}


	/**
	 * Filter by range
	 * @param  Filter\FilterRange $filter
	 * @return void
	 */
	public function applyFilterRange(Filter\FilterRange $filter)
	{
		$conditions = $filter->getCondition();

		$value_from = $conditions[$filter->getColumn()]['from'];
		$value_to   = $conditions[$filter->getColumn()]['to'];

		if ($value_from) {
			$this->data_source->where('%n >= ?', $filter->getColumn(), $value_from);
		}

		if ($value_to) {
			$this->data_source->where('%n <= ?', $filter->getColumn(), $value_to);
		}
	}


	/**
	 * Filter by keyword
	 * @param  Filter\FilterText $filter
	 * @return void
	 */
	public function applyFilterText(Filter\FilterText $filter)
	{
		$condition = $filter->getCondition();

		foreach ($condition as $column => $value) {
			$words = explode(' ', $value);

			foreach ($words as $word) {
				$escaped = $this->data_source->getConnection()->getDriver()->escapeLike($word, 0);

				if (preg_match("/[\x80-\xFF]/", $escaped)) {
					$or[] = "$column LIKE $escaped COLLATE utf8_bin";
				} else {
					$escaped = Strings::toAscii($escaped);
					$or[] = "$column LIKE $escaped COLLATE utf8_general_ci";
				}
			}
		}

		if (sizeof($or) > 1) {
			$this->data_source->where('(%or)', $or);
		} else {
			$this->data_source->where($or);
		}
	}


	/**
	 * Filter by select value
	 * @param  Filter\FilterSelect $filter
	 * @return void
	 */
	public function applyFilterSelect(Filter\FilterSelect $filter)
	{
		$this->data_source->where($filter->getCondition());
	}


	/**
	 * Apply limit and offet on data
	 * @param int $offset
	 * @param int $limit
	 * @return self
	 */
	public function limit($offset, $limit)
	{
		$this->data = $this->data_source->fetchAll($offset, $limit);

		return $this;
	}


	/**
	 * Order data
	 * @param  array  $sorting
	 * @return self
	 */
	public function sort(array $sorting)
	{
		if ($sorting) {
			$this->data_source->removeClause('ORDER BY');
			$this->data_source->orderBy($sorting);
		} else {
			/**
			 * Has the statement already a order by clause?
			 */
			$this->data_source->clause('ORDER BY');

			$reflection = new \ReflectionClass('DibiFluent');
			$cursor_property = $reflection->getProperty('cursor');
			$cursor_property->setAccessible(TRUE);
			$cursor = $cursor_property->getValue($this->data_source);

			if (!$cursor) {
				$this->data_source->orderBy($this->primary_key);
			}
		}

		return $this;
	}

}
