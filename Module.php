<?php
namespace CustomAdvancedSearch;

use Omeka\Module\AbstractModule;
use Zend\Mvc\Controller\AbstractController;
use Zend\View\Renderer\PhpRenderer;
use Zend\EventManager\SharedEventManagerInterface;
use Zend\EventManager\Event;

class Module extends AbstractModule
{
    public function getConfig()
    {
        return include __DIR__.'/config/module.config.php';
    }

    public function attachListeners(SharedEventManagerInterface $sharedEventManager)
    {
        $sharedEventManager->attach(
            '*',
            'view.advanced_search',
            [$this, 'modifyAdvancedSearch']
        );
    }

    public function handleConfigForm(AbstractController $controller)
    {
        $params = $controller->params()->fromPost();
        $purifier = $this->getServiceLocator()->get('Omeka\HtmlPurifier');
        if (isset($params['intro'])) {
            $intro = $purifier->purify($params['intro']);
        } else {
            $intro = '';
        }

        if (isset($params['form_inputs']) && is_array($params['form_inputs'])) {
            $formInputs = $params['form_inputs'];
        } else {
            $formInputs = [];
        }
        $globalSettings = $this->getServiceLocator()->get('Omeka\Settings');
        $globalSettings->set('custom_advanced_search_intro', $intro);
        $globalSettings->set('custom_advanced_search_inputs', $formInputs);
    }

    public function getConfigForm(PhpRenderer $renderer)
    {
        $settings = $this->getServiceLocator()->get('Omeka\Settings');
        $intro = $settings->get('custom_advanced_search_intro');
        $inputs = $settings->get('custom_advanced_search_inputs', []);

        return $renderer->partial('custom-advanced-search/config', compact('intro', 'inputs'));
    }

    public function modifyAdvancedSearch(Event $event)
    {
        $settings = $this->getServiceLocator()->get('Omeka\Settings');
        $partials = $event->getParam('partials');

        foreach (array_reverse($settings->get('custom_advanced_search_inputs')) as $inputName => $inputLabel) {
            $originalPartial = 'common/advanced-search/' . $inputName;
            $newPartial = 'custom-advanced-search/' . $inputName;
            $index = array_search($originalPartial, $partials, true);
            if ($index === false) {
                continue;
            }

            unset($partials[$index]);
            array_unshift($partials, $newPartial);
        }

        if ($settings->get('custom_advanced_search_intro')) {
            array_unshift($partials, 'custom-advanced-search/intro-text');
        }
        $event->setParam('partials', $partials);
    }
}
