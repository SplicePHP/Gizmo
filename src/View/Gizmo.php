<?php

namespace System\View;

use Cake\Event\EventManager;
use Cake\Event\EventManagerTrait;
use Cake\Model\ModelAwareTrait;
use Cake\Network\Request;
use Cake\Network\Response;
use Cake\Utility\Inflector;
use Cake\View\Exception\MissingCellViewException;
use Cake\View\Exception\MissingViewException;
use Cake\View\ViewVarsTrait;

abstract class Gizmo {
    
    public function hello(){
        echo 'hello';
    }

	use EventManagerTrait;
	use ModelAwareTrait;
	use ViewVarsTrait;

/**
 * Instance of the View created during rendering. Won't be set until after
 * Cell::__toString() is called.
 *
 * @var \Cake\View\View
 */
	public $View;

/**
 * Name of the template that will be rendered.
 * This property is inflected from the action name that was invoked.
 *
 * @var string
 */
	public $template;

/**
 * Automatically set to the name of a plugin.
 *
 * @var string
 */
	public $plugin = null;

/**
 * An instance of a Cake\Network\Request object that contains information about the current request.
 * This object contains all the information about a request and several methods for reading
 * additional information about the request.
 *
 * @var \Cake\Network\Request
 */
	public $request;

/**
 * An instance of a Response object that contains information about the impending response
 *
 * @var \Cake\Network\Response
 */
	public $response;

/**
 * The name of the View class this cell sends output to.
 *
 * @var string
 */
	public $viewClass = null;

/**
 * The theme name that will be used to render.
 *
 * @var string
 */
	public $theme;

/**
 * The helpers this cell uses.
 *
 * This property is copied automatically when using the CellTrait
 *
 * @var array
 */
	public $helpers = [];

/**
 * These properties can be set directly on Cell and passed to the View as options.
 *
 * @var array
 * @see \Cake\View\View
 */
	protected $_validViewOptions = [
		'viewVars', 'helpers', 'viewPath', 'plugin', 'theme'
	];

/**
 * List of valid options (constructor's fourth arguments)
 * Override this property in subclasses to whitelist
 * which options you want set as properties in your Cell.
 *
 * @var array
 */
	protected $_validCellOptions = [];

/**
 * Constructor.
 *
 * @param \Cake\Network\Request $request the request to use in the cell
 * @param \Cake\Network\Response $response the response to use in the cell
 * @param \Cake\Event\EventManager $eventManager then eventManager to bind events to
 * @param array $cellOptions cell options to apply
 */
	public function __construct(Request $request = null, Response $response = null,
			EventManager $eventManager = null, array $cellOptions = []) {
		$this->eventManager($eventManager);
		$this->request = $request;
		$this->response = $response;
		$this->modelFactory('Table', ['Cake\ORM\TableRegistry', 'get']);

		foreach ($this->_validCellOptions as $var) {
			if (isset($cellOptions[$var])) {
				$this->{$var} = $cellOptions[$var];
			}
		}
	}

/**
 * Render the cell.
 *
 * @param string $template Custom template name to render. If not provided (null), the last
 * value will be used. This value is automatically set by `CellTrait::cell()`.
 * @return void
 * @throws \Cake\View\Exception\MissingCellViewException When a MissingViewException is raised during rendering.
 */
	public function render($template = null) {
		if ($template !== null && strpos($template, '/') === false) {
			$template = Inflector::underscore($template);
		}
		if ($template === null) {
			$template = $this->template;
		}

		$this->View = null;
		$this->getView();

		$this->View->layout = false;
		$className = explode('\\', get_class($this));
		$className = array_pop($className);
		$name = substr($className, 0, strpos($className, 'Cell'));
		$this->View->subDir = 'Cell' . DS . $name;

		try {
			return $this->View->render($template);
		} catch (MissingViewException $e) {
			throw new MissingCellViewException(['file' => $template, 'name' => $name]);
		}
	}

/**
 * Magic method.
 *
 * Starts the rendering process when Cell is echoed.
 *
 * *Note* This method will trigger an error when view rendering has a problem.
 * This is because PHP will not allow a __toString() method to throw an exception.
 *
 * @return string Rendered cell
 */
	public function __toString() {
		try {
			return $this->render();
		} catch (\Exception $e) {
			trigger_error('Could not render cell - ' . $e->getMessage(), E_USER_WARNING);
			return '';
		}
	}

/**
 * Debug info.
 *
 * @return array
 */
	public function __debugInfo() {
		return [
			'plugin' => $this->plugin,
			'template' => $this->template,
			'viewClass' => $this->viewClass,
			'request' => $this->request,
			'response' => $this->response,
		];
	}

}
