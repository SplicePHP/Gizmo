<?php
/**
 * CakePHP(tm) : Rapid Development Framework (http://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (http://cakefoundation.org)
 * @link          http://cakephp.org CakePHP(tm) Project
 * @since         3.0.0
 * @license       http://www.opensource.org/licenses/mit-license.php MIT License
 */

namespace Gizmo\View;

use Cake\Core\App;
use Cake\Utility\Inflector;
use System\View\View;

/**
 * Provides gizmo() method for usage in Controller and View classes.
 *
 */

trait GizmoTrait {

/**
 * Renders the given gizmo.
 *
 * Example:
 *
 * {{{
 * // Taxonomy\View\Gizmo\TagCloudGizmo::smallList()
 * $gizmo = $this->gizmo('Taxonomy.TagCloud::smallList', ['limit' => 10]);
 *
 * // App\View\Gizmo\TagCloudGizmo::smallList()
 * $gizmo = $this->gizmo('TagCloud::smallList', ['limit' => 10]);
 * }}}
 *
 * The `display` action will be used by default when no action is provided:
 *
 * {{{
 * // Taxonomy\View\Gizmo\TagCloudGizmo::display()
 * $gizmo = $this->gizmo('Taxonomy.TagCloud');
 * }}}
 *
 * Gizmos are not rendered until they are echoed.
 *
 * @param string $gizmo You must indicate gizmo name, and optionally a gizmo action. e.g.: `TagCloud::smallList`
 * will invoke `View\Gizmo\TagCloudGizmo::smallList()`, `display` action will be invoked by default when none is provided.
 * @param array $data Additional arguments for gizmo method. e.g.:
 *    `gizmo('TagCloud::smallList', ['a1' => 'v1', 'a2' => 'v2'])` maps to `View\Gizmo\TagCloud::smallList(v1, v2)`
 * @param array $options Options for Gizmo's constructor
 * @return \Cake\View\Gizmo The gizmo instance
 * @throws \Cake\View\Exception\MissingGizmoException If Gizmo class was not found.
 * @throws \BadMethodCallException If Gizmo class does not specified gizmo action.
 */
	public function gizmo($gizmo, array $data = [], array $options = []) {
		$parts = explode('::', $gizmo);

		if (count($parts) === 2) {
			list($pluginAndGizmo, $action) = [$parts[0], $parts[1]];
		} else {
			list($pluginAndGizmo, $action) = [$parts[0], 'display'];
		}

		list($plugin, $gizmoName) = pluginSplit($pluginAndGizmo);
		$className = App::className($pluginAndGizmo, 'View/Gizmo', 'Gizmo');

		if (!$className) {
			throw new Exception\MissingGizmoException(array('className' => $pluginAndGizmo . 'Gizmo'));
		}

		$gizmo = $this->_createGizmo($className, $action, $plugin, $options);
		if (!empty($data)) {
			$data = array_values($data);
		}

		try {
			$reflect = new \ReflectionMethod($gizmo, $action);
			$reflect->invokeArgs($gizmo, $data);
			return $gizmo;
		} catch (\ReflectionException $e) {
			throw new \BadMethodCallException(sprintf(
				'Class %s does not have a "%s" method.',
				$className,
				$action
			));
		}
	}

/**
 * Create and configure the gizmo instance.
 *
 * @param string $className The gizmo classname.
 * @param string $action The action name.
 * @param string $plugin The plugin name.
 * @param array $options The constructor options for the gizmo.
 * @return \Cake\View\Gizmo;
 */
	protected function _createGizmo($className, $action, $plugin, $options) {
		$instance = new $className($this->request, $this->response, $this->eventManager(), $options);
		$instance->template = Inflector::underscore($action);
		$instance->plugin = !empty($plugin) ? $plugin : null;
		$instance->theme = !empty($this->theme) ? $this->theme : null;
		if (!empty($this->helpers)) {
//			$instance->helpers = $this->helpers;
		}
		if (isset($this->viewClass)) {
			$instance->viewClass = $this->viewClass;
		}
		if ($this instanceof View) {
			$instance->viewClass = get_class($this);
		}
		return $instance;
	}

}
