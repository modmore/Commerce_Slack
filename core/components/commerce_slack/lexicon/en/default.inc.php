<?php

$_lang['commerce_slack'] = 'Slack';
$_lang['commerce_slack.description'] = 'Allows you to send notifications of new orders to Slack.';
$_lang['commerce_slack.module_instructions'] = "<p>After enabling the module, you'll need to go into your <a href='https://docs.modmore.com/en/Commerce/v1/Statuses/index.html' target='_blank' rel='noopener'>Status workflow</a> and add the <em>Send new order to Slack</em> status change action. Typically, you'll do this on your Payment Received status change.</p>
<ol class='c ui ordered list'>
    <li>Enable the module, and save.</li>
    <li>Go to Configuration > Statuses and click on the name of the status change that processes when a customer completes an order.</li>
    <li>In the menu for the <em>Add Status Change Action</em> button, choose <em>Send new order to Slack</em> and follow the instructions provided to you there. There you'll authorize Commerce to send messages to your Slack team.</li>
</ol>";
$_lang['commerce_slack.configuration'] = 'Slack Configuration';
$_lang['commerce_slack.webhook_url'] = 'Incoming Webhook URL';
$_lang['commerce_slack.webhook_url.description'] = '<p>To send messages into your Slack account, Commerce uses the <em>Incoming Webhook</em> integration. To use that, you need to generate a webhook URL that allows Commerce to post to your account.</p>
<ol class="c ui ordered list">
<li><a href="https://slack.com/apps/A0F7XDUAZ-incoming-webhooks?next_id=0" target="_blank" rel="noopener">Sign in to Slack and navigate to the Incoming Webhook App</a>.</li>
<li>Click on <em>Add to Slack</em> in the left sidebar.</li>
<li>Follow the instructions by choosing a Channel (or user) to send messages to. Copy the Webhook URL shown into the field, below.</li>
<li>Scroll down past the Setup Instructions to the <em>Integration Settings</em> to set the description of the integration, the name, and choose an image or emoji to use.</li>
<li>Save the settings, and save the module. </li>
</ol>';

$_lang['commerce_slack.webhook_url.will_send_test'] = '<p>If everything is set-up correctly, you\'ll see a confirmation message in Slack after saving the module.</p>';

$_lang['commerce_slack.send_test_message'] = 'Send test message on save';

$_lang['commerce.add_SlackStatusChangeAction'] = 'Send new order to Slack';
$_lang['commerce.SlackStatusChangeAction'] = 'Send new order to Slack';
