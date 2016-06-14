<?php
/**
* ZF 2-for-1
*
* @link https://github.com/Roave/zf2-for-1 for the canonical source repository
* @copyright Copyright (c) 2005-2013 Evan Coury (http://blog.evan.pro/)
* @license New BSD License
 */

use Zend\Loader\AutoloaderFactory;
use Zend\Mvc\Application;
use Zend\Stdlib\ArrayUtils;

class Zf2for1_Resource_Zf2
    extends Zend_Application_Resource_ResourceAbstract
{
    protected $app;

    public function init()
    {
        $this->registerZf2Autoloader();

        $options = $this->getOptions();
        $configPath = isset($options['config_path'])
            ? $options['config_path']
            : dirname(APPLICATION_PATH) . '/config';

        //whole zf1 application config
        $zf1Config = $this->getBootstrap()->getApplication()->getOptions();

        // get zf2 application config
        $appConfig = require $configPath . '/application.config.php';

        // Load application config overrides from apigility style development.config.php
        if (file_exists($configPath . '/development.config.php')) {
            $appConfig = ArrayUtils::merge($appConfig, require $configPath . '/development.config.php');
        }

        $appConfig = ArrayUtils::merge(
            $appConfig,
            array(
                'modules' => array(
                    'Zf2for1'
                ),
                'module_listener_options' => array(
                    'extra_config' => array(
                        'zf1' => $zf1Config,
                        'service_manager' => array(
                            'services' => array(
                                'zf1_bootstrap' => $this->getBootstrap(),
                            )
                        )
                    )
                )
            )
        );

        if (!empty($_SESSION['zf2app'])) {
            $this->app = $_SESSION['zf2app'];
            unset($_SESSION['zf2app']);
        }
        else {
            $this->app = Application::init($appConfig);
        }
        
        if (
            isset($options['add_sm_to_registry'])
            && $options['add_sm_to_registry'] == true
        ) {
            $serviceManager = $this->getServiceManager();
            $registry = Zend_Registry::getInstance();
            $registry->set('service_manager', $serviceManager);
        }
        return $this;
    }

    protected function registerZf2Autoloader()
    {
        $options = $this->getOptions();

        if (!empty($options['zf2_path'])) {
            include_once $options['zf2_path'] . '/zend-loader/src/AutoloaderFactory.php';
        }

        if (!class_exists('Zend\\Loader\\AutoloaderFactory', true)) {
            throw new DomainException('Option "zf2Path" was not provided');
        }

        AutoloaderFactory::factory(array(
            'Zend\Loader\StandardAutoloader' => array(
                'autoregister_zf' => true
            )
        ));
    }

    public function getServiceManager()
    {
        return $this->app->getServiceManager();
    }

    /**
     * @return Application
     */
    public function getApplication()
    {
        return $this->app;
    }
}
