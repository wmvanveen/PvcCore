<?php

class PwvcPageProxy extends WireData {

  protected $_page = null;

  public function __construct(Page $page) {
    $this->_page = &$page;
    $view = $this->_setupView();
    // get action and store view file name as altFilename with page’s template
    // (just to make viewable check pass)
    $controller = $view->get('controller');
    $action = $controller->calledAction(true);
    $filename = $view->getViewFilename($action);
    $page->template->set('filename', $filename);
    $page->template->set('altFilename', '');

    return $this->_page->id;
  }


  /**
   * Render page output, replaces $page->output()
   * @method output
   * @param bool $forceNew Forces it to return a new (non-cached) TemplateFile object (default=false)
   * @return PwvcView (extends TemplateFile)
   */
  public function output($forceNew=true) {
    $view = $this->get('output');
    // use existing view, when existing and !$forceNew
    if(!($view instanceof PwvcView && !$forceNew)) {
      $view = $this->_setupView();
    }
    return $view;
  }

  private function _setupView() {
    $page = $this->_page;
    $templateName = $page->template->name;
    // save working dir
    $saveDir = getcwd();
    chdir(dirname(getenv('SCRIPT_FILENAME')));
    // get controller class
    $controllerClass = PwvcCore::getClassName($templateName, 'controller');
    $controllerFilename = PwvcCore::getConfigValue('cfgControllersPath') . PwvcCore::getFilename('controller', $controllerClass);
    if(file_exists($controllerFilename)) require_once($controllerFilename);
    if(!((class_exists($controllerClass)) || (PwvcController::extend($controllerClass)))) {
      throw new WireException(sprintf($this->_('Wasn’t able to set up controller "%s".'), $controllerClass));
    }
    $controller = new $controllerClass($this);
    // get view class
    $viewClass = PwvcCore::getClassName($templateName, 'view');
    $viewFilename = PwvcCore::getConfigValue('cfgViewsPath') . PwvcCore::getFilename('view', $viewClass);
    if(file_exists($viewFilename)) require_once($viewFilename);
    if(!((class_exists($viewClass)) || (PwvcView::extend($viewClass)))) {
      throw new WireException(sprintf($this->_('Wasn’t able to set up view "%s".'), $viewClass));
    }
    $view = new $viewClass($controller);
    // store view instead of template file with page
    $page->set('output', $view);
    // restore working dir
    chdir($saveDir);
    // return view
    return $view;
  }


  public function __get($key) {
    return $this->_page->get($key);
  }
  public function __set($key, $value) {
    $this->_page->set($key, $value);
    return $this;
  }
  public function __call($method, $arguments=null) {
    return call_user_func(array(&$this->_page, $method), $arguments);
  }
}