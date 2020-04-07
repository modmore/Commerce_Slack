<?php
namespace modmore\Commerce_Slack\Modules;
use modmore\Commerce\Admin\Widgets\Form\DescriptionField;
use modmore\Commerce\Modules\BaseModule;
use Symfony\Component\EventDispatcher\EventDispatcher;

require_once dirname(dirname(__DIR__)) . '/vendor/autoload.php';

class Slack extends BaseModule {

    public function getName()
    {
        $this->adapter->loadLexicon('commerce_slack:default');
        return $this->adapter->lexicon('commerce_slack');
    }

    public function getAuthor()
    {
        return 'modmore';
    }

    public function getDescription()
    {
        return $this->adapter->lexicon('commerce_slack.description');
    }

    public function initialize(EventDispatcher $dispatcher)
    {
        // Load our lexicon
        $this->adapter->loadLexicon('commerce_slack:default');

        // Add the xPDO package, so Commerce can detect the derivative classes
        $root = dirname(__DIR__, 2);
        $path = $root . '/model/';
        $this->adapter->loadPackage('commerce_slack', $path);
    }

    public function getModuleConfiguration(\comModule $module)
    {
        $fields = [];

        $fields[] = new DescriptionField($this->commerce, [
            'description' => $this->adapter->lexicon('commerce_slack.module_instructions'),
            'raw' => true,
        ]);

        return $fields;
    }
}
