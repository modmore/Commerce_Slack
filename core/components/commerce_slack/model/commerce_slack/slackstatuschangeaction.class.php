<?php

use modmore\Commerce\Admin\Widgets\Form\CheckboxField;
use modmore\Commerce\Admin\Widgets\Form\DescriptionField;
use modmore\Commerce\Admin\Widgets\Form\SectionField;
use modmore\Commerce\Admin\Widgets\Form\TextField;
use modmore\Commerce_Slack\Communication\Message;
use modmore\Commerce_Slack\Communication\Sender;

/**
 * Slack for Commerce.
 *
 * Copyright 2020 by Mark Hamstra <mark@modmore.com>
 *
 * This file is meant to be used with Commerce by modmore. A valid Commerce license is required.
 *
 * @package commerce_slack
 * @license See core/components/commerce_slack/docs/license.txt
 */
class SlackStatusChangeAction extends comStatusChangeAction
{
    public function getModelFields()
    {
        $fields = [];
        
        $fields[] = new SectionField($this->commerce, [
            'label' => $this->adapter->lexicon('commerce_slack.configuration')
        ]);

        $fields[] = new DescriptionField($this->commerce, [
            'description' => $this->adapter->lexicon('commerce_slack.webhook_url.description'),
            'raw' => true,
        ]);

        $url = $this->getProperty('webhook_url');
        $fields[] = new TextField($this->commerce, [
            'name' => 'properties[webhook_url]',
            'label' => $this->adapter->lexicon('commerce_slack.webhook_url'),
            'value' => $url,
        ]);

        $fields[] = new CheckboxField($this->commerce, [
            'label' => $this->adapter->lexicon('commerce_slack.send_test_message'),
            'name' => 'send_test',
            'value' => 0,
        ]);

        $lastResponse = $this->getProperty('last_response');
        if (!empty($lastResponse)) {
            $fields[] = new DescriptionField($this->commerce, [
                'description' => '<div class="ui visible message error">Received an error sending test message: ' . htmlentities($lastResponse) . '</div>',
                'raw' => true,
            ]);
            $this->unsetProperty('last_response');
            $this->save();
        }

        $fields[] = new CheckboxField($this->commerce, [
            'label' => $this->adapter->lexicon('commerce_slack.include_addresses'),
            'name' => 'properties[include_addresses]',
            'value' => $this->getProperty('include_addresses'),
            'default' => true,
        ]);
        $fields[] = new CheckboxField($this->commerce, [
            'label' => $this->adapter->lexicon('commerce_slack.include_taxes'),
            'name' => 'properties[include_taxes]',
            'value' => $this->getProperty('include_taxes'),
            'default' => true,
        ]);
        $fields[] = new CheckboxField($this->commerce, [
            'label' => $this->adapter->lexicon('commerce_slack.include_shipping'),
            'name' => 'properties[include_shipping]',
            'value' => $this->getProperty('include_shipping'),
            'default' => true,
        ]);
        $fields[] = new CheckboxField($this->commerce, [
            'label' => $this->adapter->lexicon('commerce_slack.include_payment'),
            'name' => 'properties[include_payment]',
            'value' => $this->getProperty('include_payment'),
            'default' => true,
        ]);

        return $fields;
    }

    /**
     * Sends a test message to confirm it works
     */
    public function sendTestMessage(): void
    {
        $siteName = $this->commerce->getOption('site_name');

        // Grab the last order to send as a test
        $c = $this->adapter->newQuery('comOrder');
        $c->where([
            'test' => $this->commerce->isTestMode(),
        ]);
        $c->sortby('received_on', 'DESC');
        $order = $this->adapter->getObject('comOrder', $c);
        if ($order instanceof comOrder && $status = $order->getOne('Status')) {
            $response = $this->sendOrderToSlack($order, $status);
            if ($response->getStatusCode() !== 200) {
                $body = (string)$response->getBody();
                $this->setProperty('last_response', $body);
                $this->save();
                return;
            }
        }

        // Send another message confirming it worked
        $payload = new Message('Success! ' . $siteName . ' can send order notifications to Slack');
        $payload->addBlock([
            'type' => 'section',
            'text' => [
                'type' => 'mrkdwn',
                'text' => 'Success! ' . $siteName . ' can send order notifications to Slack :tada:'
            ]
        ]);
        $sender = new Sender($this->getProperty('webhook_url'));
        $response = $sender->send($payload);
        if ($response->getStatusCode() !== 200) {
            $body = $response->getBody()->getContents();
            $this->setProperty('last_response', $body);
        }
    }

    /**
     * Called automatically by FormWidget to handle the send_test value.
     *
     * @param $value
     */
    public function setFieldValueSend_test($value): void
    {
        if (empty($value)) {
            return;
        }

        $url = (string)$this->getProperty('webhook_url');
        if (!empty($url)) {
            $this->sendTestMessage($url);
        }
    }

    public function sendOrderToSlack(comOrder $order, comStatus $status): \Psr\Http\Message\ResponseInterface
    {
        $billing = $order->getBillingAddress(true);
        $shipping = $order->getShippingAddress(true);
        $name = $this->getName($billing, $shipping);
        $total = $order->get('total_formatted');
        $payload = new Message("New order {$order->get('reference')} by {$name} for {$order->get('total_formatted')}");

        $payload->addBlock([
            'type' => 'section',
            'text' => [
                'type' => 'mrkdwn',
                'text' => "Received order *{$order->get('reference')}* by *{$name}* for *{$total}*"
            ]
        ]);

        if ($this->getProperty('include_addresses', true)) {
            $addressBlock = [
                'type' => 'context',
                'elements' => [],
            ];
            if ($billing) {
                $addressBlock['elements'][] = [
                    'type' => 'mrkdwn',
                    'text' => "*Billing Address* :classical_building:" . $this->formatAddress($billing),
                ];
            }
            if ($shipping) {
                $addressBlock['elements'][] = [
                    'type' => 'mrkdwn',
                    'text' => "*Shipping Address* :mailbox:" . $this->formatAddress($shipping),
                ];
            }
            if (count($addressBlock['elements']) > 0) {
                $payload->addBlock($addressBlock);
            }
        }

        // Items block
        $itemsText = [];
        foreach ($order->getItems() as $item) {
            $name = $item->get('name');
//            if ($link = $item->get('link')) {
//                $name = '<' . $link . '|' . $name . '>';
//            }
            $itemsText[] = "{$item->get('quantity')}× *{$name}*, {$item->get('total_before_tax_formatted')}";
        }
        $payload->addBlock([
            'type' => 'section',
            'fields' => [
                [
                    'type' => 'mrkdwn',
                    'text' => implode("\n", $itemsText),
                ]
            ],
        ]);

        $misc = [];

        if ($this->getProperty('include_taxes', true)) {
            $taxTotals = $order->getTaxTotals();
            foreach ($taxTotals as $taxTotal) {
                $misc[] = [
                    'type' => 'mrkdwn',
                    'text' => "{$taxTotal['name']} ({$taxTotal['percentage_formatted']}): *{$taxTotal['total_tax_amount_formatted']}*",
                ];
            }
        }

        if ($this->getProperty('include_shipping', true)) {
            foreach ($order->getShipments() as $shipment) {
                if ($method = $shipment->getShippingMethod()) {
                    $text = "*{$method->get('name')}*";
                    if ($shipment->get('fee') !== 0) {
                        $text .= ": {$shipment->get('fee_ex_tax_formatted')}";
                    }
                    $misc[] = [
                        'type' => 'mrkdwn',
                        'text' => $text,
                    ];
                }
            }
        }

        if ($this->getProperty('include_payment', true)) {
            foreach ($order->getTransactions() as $transaction) {
                if ($transaction->isCompleted() && $method = $transaction->getMethod()) {
                    $misc[] = [
                        'type' => 'mrkdwn',
                        'text' => "Paid with *{$method->get('name')}*",
                    ];
                }
            }
        }

        if (count($misc) > 0) {
            $payload->addBlock([
                'type' => 'context',
                'elements' => $misc,
            ]);
        }

        // Grab the full site url
        $siteUrl = $this->adapter->getOption('site_url');
        $siteUrl = rtrim($siteUrl, '/');

        // Fix protocol relative urls; slack doesn't like those
        if (strpos($siteUrl, '//') === 0) {
            $siteUrl = 'https:' . $siteUrl;
        }

        // Grab the url in the manager to view the order
        $viewUrl = $siteUrl . $this->adapter->makeAdminUrl('order', ['order' => $order->get('id')]);
        $payload->addBlock([
            'type' => 'actions',
            'elements' => [
                [
                    'type' => 'button',
                    'text' => [
                        'type' => 'plain_text',
                        'text' => 'View Order',
                    ],
                    'url' => $viewUrl,
                ]
            ]
        ]);

        $sender = new \modmore\Commerce_Slack\Communication\Sender($this->getProperty('webhook_url'));
        $response = $sender->send($payload);

        if ($response->getStatusCode() !== 200) {
            $body = (string)$response->getBody();
            $this->adapter->log(1, '[Slack for Commerce] Failed sending notification to Slack. Received ' . $response->getStatusCode() . ' with body ' . $body . ' for payload ' . json_encode($payload->getPayload()));
        }
        return $response;
    }

    public function process(comOrder $order, comStatus $oldStatus, comStatus $newStatus, comStatusChange $statusChange)
    {
        $this->sendOrderToSlack($order, $newStatus);
        // Always return true - we don't want notificiations to halt processing.
        return true;
    }

    private function getName($billing, $shipping): string
    {
        if ($billing instanceof comOrderAddress) {
            return $billing->get('fullname') ?: $billing->get('firstname') . ' ' . $billing->get('lastname');
        }
        if ($shipping instanceof comOrderAddress) {
            return $shipping->get('fullname') ?: $shipping->get('firstname') . ' ' . $shipping->get('lastname');
        }
        return 'unknown customer';
    }

    private function formatAddress(comOrderAddress $address)
    {
        $t = $address->toArray();
        $formatted = $this->commerce->formatAddress($t);

        // <br> to \n; Slack will reverse this
//        $formatted = str_replace('<br>', "\n", $formatted);

        $formatted = strip_tags($formatted);

        return $formatted;
    }
}
