<?php

if (!defined('TYPO3_MODE')) {
    die('Access denied.');
}

if (!function_exists('register_client')) {
    function register_client()
    {
        if (!class_exists(\Networkteam\SentryClient\Service\ConfigurationService::class)) {
            return;
        }

        if (!\TYPO3\CMS\Core\Core\Bootstrap::usesComposerClassLoading()) {
            $autoloadFile = \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('sentry_client') . 'vendor/autoload.php';
            require_once($autoloadFile);
            \Raven_Autoloader::register();
        }

        if (!\Networkteam\SentryClient\Service\ConfigurationService::registerClient()) {
            return;
        }

        $release = isset($GLOBALS['sentryRelease']) ? $GLOBALS['sentryRelease']: null;
        $environment = isset($GLOBALS['sentryEnvironment']) ? $GLOBALS['sentryEnvironment']: null;
        $additionalOptions = array();
        if ($release) {
            $additionalOptions['release'] = $release;
        }
        if ($environment) {
            $additionalOptions['environment'] = $environment;
        }

        $GLOBALS['USER']['sentryClient'] = new \Networkteam\SentryClient\Client($additionalOptions);
        $errorHandler = new Raven_ErrorHandler($GLOBALS['USER']['sentryClient'], true);
        $errorHandler->registerExceptionHandler();
        $errorHandler->registerShutdownFunction();

        $GLOBALS['TYPO3_CONF_VARS']['SYS']['Objects'][\TYPO3\CMS\Frontend\ContentObject\Exception\ProductionExceptionHandler::class] = [
            'className' => \Networkteam\SentryClient\Content\ProductionExceptionHandler::class
        ];
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['debugExceptionHandler'] = \Networkteam\SentryClient\DebugExceptionHandler::class;
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['productionExceptionHandler'] = \Networkteam\SentryClient\ProductionExceptionHandler::class;
    }
}

register_client();
