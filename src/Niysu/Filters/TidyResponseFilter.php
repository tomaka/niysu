<?php
namespace Niysu\Filters;

/**
 * Calls tidy_repair_string() on the output if it is HTML or XML.
 *
 * @copyright 	2012 Pierre Krieger <pierre.krieger1708@gmail.com>
 * @license 	MIT http://opensource.org/licenses/MIT
 * @link 		http://github.com/Tomaka17/niysu
 */
class TidyResponseFilter extends \Niysu\HTTPResponseFilterInterface {
	public function __construct(\Niysu\HTTPResponseInterface $next) {
		if (!\extension_loaded('tidy'))
			throw new \LogicException('php_tidy must be installed to use this filter');

		parent::__construct($next);
		$this->setHeader('Content-Type', 'text/csv');
	}

	/**
	 * Sets the configuration to use when calling tidy_repair_string()
	 *
	 * @param mixed 	$config 	See http://fr2.php.net/manual/fr/tidy.repairstring.php
	 */
	public function setConfiguration($config) {
		$this->config = $config;
	}


	public function isHeadersListSent() {
		return $this->dataBuffer != '';
	}

	public function appendData($data) {
		if ($this->enabled)		$this->data .= $data;
		else					parent::appendData($data);
	}

	public function setHeader($header, $data) {
		if (strtolower($header) == 'content-type')
			$this->enabled = $this->isContentTypeRelevant($data);
		parent::setHeader($header, $data);
	}

	public function addHeader($header, $data) {
		if (strtolower($header) == 'content-type')
			$this->enabled = $this->isContentTypeRelevant($data);
		parent::addHeader($header, $data);
	}

	public function flush() {
		if ($this->enabled)
			parent::appendData(\tidy_repair_string($this->data, $this->config, 'utf8'));
		parent::flush();

		$dataBuffer = '';
	}


	private function isContentTypeRelevant($value) {
		if (strpos('text/html', $value) === 0)
			return true;
		if (strpos('application/xhtml', $value) === 0)
			return true;
		return false;
	}

	private $dataBuffer;
	private $enabled = false;
	private $config = [];
};

?>