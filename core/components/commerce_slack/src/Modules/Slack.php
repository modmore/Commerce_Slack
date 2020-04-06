<?php
namespace modmore\Commerce_Slack\Modules;
use modmore\Commerce\Admin\Configuration\About\ComposerPackages;
use modmore\Commerce\Admin\Sections\SimpleSection;
use modmore\Commerce\Admin\Widgets\Form\CheckboxField;
use modmore\Commerce\Admin\Widgets\Form\DescriptionField;
use modmore\Commerce\Admin\Widgets\Form\TextField;
use modmore\Commerce\Events\Admin\PageEvent;
use modmore\Commerce\Modules\BaseModule;
use modmore\Commerce_Slack\Communication\Message;
use modmore\Commerce_Slack\Communication\Sender;
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

        // Add composer libraries to the about section (v0.12+)
        $dispatcher->addListener(\Commerce::EVENT_DASHBOARD_LOAD_ABOUT, [$this, 'addLibrariesToAbout']);
    }

    public function getModuleConfiguration(\comModule $module)
    {
        $fields = [];

        $fields[] = new DescriptionField($this->commerce, [
            'description' => $this->adapter->lexicon('commerce_slack.webhook_url.description'),
            'raw' => true,
        ]);

        $url = $module->getProperty('webhook_url');
        $fields[] = new TextField($this->commerce, [
            'name' => 'properties[webhook_url]',
            'label' => $this->adapter->lexicon('commerce_slack.webhook_url'),
            'value' => $url,
        ]);

        $fields[] = new CheckboxField($this->commerce, [
            'label' => $this->adapter->lexicon('commerce_slack.send_test_message'),
            'name' => 'properties[send_test]',
        ]);

        // Check the response first
        $lastResponse = $module->getProperty('last_response');
        if (!empty($lastResponse)) {
            $fields[] = new DescriptionField($this->commerce, [
                'description' => '<div class="ui visible message error">Received an error sending test message: ' . htmlentities($lastResponse) . '</div>',
                'raw' => true,
            ]);
            $module->unsetProperty('last_response');
            $module->save();
        }

        // Only after that see if we should send a test
        if (!empty($url) && $module->getProperty('send_test')) {
            $payload = new Message('Commerce has permission to talk to you on Slack');
            $payload->addBlock([
                'type' => 'section',
                'text' => [
                    'type' => 'mrkdwn',
                    'text' => ':white_check_mark: Commerce has permission to talk to you on Slack'
                ]
            ]);

            // Send it away
            $sender = new Sender($url);
            $response = $sender->send($payload);
            if ($response->getStatusCode() !== 200) {
                $body = $response->getBody()->getContents();
                $module->setProperty('last_response', $body);
            }

            $module->setProperty('send_test', false);
            $module->save();
        }

        return $fields;
    }

    public function addLibrariesToAbout(PageEvent $event)
    {
        $lockFile = dirname(dirname(__DIR__)) . '/composer.lock';
        if (file_exists($lockFile)) {
            $section = new SimpleSection($this->commerce);
            $section->addWidget(new ComposerPackages($this->commerce, [
                'lockFile' => $lockFile,
                'heading' => $this->adapter->lexicon('commerce.about.open_source_libraries') . ' - ' . $this->adapter->lexicon('commerce_slack'),
                'introduction' => '', // Could add information about how libraries are used, if you'd like
            ]));

            $about = $event->getPage();
            $about->addSection($section);
        }
    }
}
