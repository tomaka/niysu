<?php
namespace Niysu;

class HTTPResponseETagFilter extends HTTPResponseFilter {
	public function __construct(HTTPResponseInterface $output) {
		parent::__construct($output);
	}

	public function __destruct() {
		parent::__destruct();
	}



	private $dataBuffer = '';
};

?>