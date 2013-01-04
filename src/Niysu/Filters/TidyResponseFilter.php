<?php
namespace Niysu\Filters;

/**
 * Calls tidy_repair_string() on the output if it is HTML or XML.
 *
 * @copyright 	2012 Pierre Krieger <pierre.krieger1708@gmail.com>
 * @license 	MIT http://opensource.org/licenses/MIT
 * @link 		http://github.com/Tomaka17/niysu
 */
class TidyResponseFilter implements \Niysu\HTTPResponseInterface {
	use \Niysu\HTTPResponseFilterTrait;

	public function __construct(\Niysu\HTTPResponseInterface $next) {
		if (!\extension_loaded('tidy'))
			throw new \LogicException('php_tidy must be installed to use this filter');

		$this->outputResponse = $next;

		// setting default configuration
		$this->config = [
	        'break-before-br' => false,
	        'doctype' => '<!DOCTYPE HTML>',
	        'hide-comments' => true,
	        'indent' => true,
	        'indent-spaces' => 4,
	        'new-blocklevel-tags' => 'article,header,footer,section,nav',
	        'new-inline-tags' => 'video,audio,canvas,ruby,rt,rp',
	        'sort-attributes' => 'alpha',
	        'tidy-mark' => false,
	        'output-xhtml' => false,
	        'vertical-space' => false,
	        'wrap' => 180,
	        'wrap-attributes' => false
	    ];
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
		else					$this->outputResponse->appendData($data);
	}

	public function setHeader($header, $data) {
		if (strtolower($header) == 'content-type')
			$this->enabled = $this->isContentTypeRelevant($data);
		$this->outputResponse->setHeader($header, $data);
	}

	public function addHeader($header, $data) {
		if (strtolower($header) == 'content-type')
			$this->enabled = $this->isContentTypeRelevant($data);
		$this->outputResponse->addHeader($header, $data);
	}

	public function flush() {
		if ($this->enabled)
			$this->outputResponse->appendData(\tidy_repair_string($this->data, $this->config, 'utf8'));
		$this->outputResponse->flush();

		$dataBuffer = '';
	}


	private function isContentTypeRelevant($value) {
		if (strpos($value, 'text/html') === 0)
			return true;
		if (strpos($value, 'application/xhtml') === 0)
			return true;
		return false;
	}

	private $dataBuffer;
	private $enabled = false;
	private $config = [];
};

?>