<?php

use noobsec\AntiScanScanClub\AntiScanScanClub;
use Orchestra\Testbench\TestCase;

class AntiScanScanClubTest extends Orchestra\Testbench\TestCase
{
	public function setUp() {
		parent::setUp();
		$this->ASSC = new AntiScanScanClub();
	}

	public function test_checkIp() {
		$checkIp = $this->ASSC->checkIp('127.0.0.1');
		$this->assertEquals(false, $checkIp);
	}
}