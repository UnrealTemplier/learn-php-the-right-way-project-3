<?php

namespace App\Clockwork\DataSource;

use Doctrine\ORM\EntityManagerInterface;

class DoctrineDataSource extends DBALDataSource
{
	public function __construct(?EntityManagerInterface $em = null)
	{
		parent::__construct($em?->getConnection());
	}
}
