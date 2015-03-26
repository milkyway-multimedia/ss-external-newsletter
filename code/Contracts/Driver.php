<?php namespace Milkyway\SS\ExternalNewsletter\Contracts;

interface Driver {
	public function title();
	public function prefix();
	public function map();
	public function service();
}