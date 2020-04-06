<?php

use modmore\Commerce_Slack\Communication\Message;

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
        
        
        
        return $fields;
    }
    
    public function process(comOrder $order, comStatus $oldStatus, comStatus $newStatus, comStatusChange $statusChange)
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
        $taxTotals = $order->getTaxTotals();
        foreach ($taxTotals as $taxTotal) {
            $misc[] = [
                'type' => 'mrkdwn',
                'text' => "{$taxTotal['name']} ({$taxTotal['percentage_formatted']}): *{$taxTotal['total_tax_amount_formatted']}*",
            ];
        }
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
        foreach ($order->getTransactions() as $transaction) {
            if ($transaction->isCompleted() && $method = $transaction->getMethod()) {
                $misc[] = [
                    'type' => 'mrkdwn',
                    'text' => "Paid with *{$method->get('name')}*",
                ];
            }
        }
        $payload->addBlock([
            'type' => 'context',
            'elements' => $misc,
        ]);

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

        $sender = new \modmore\Commerce_Slack\Communication\Sender($this->getModuleProperty('webhook_url'));
        $response = $sender->send($payload);

        if ($response->getStatusCode() !== 200) {
            $this->adapter->log(1, '[Slack for Commerce] Failed sending notification to Slack. Received ' . $response->getStatusCode() . ' with body ' . $response->getBody()->getContents() . ' for payload ' . json_encode($payload->getPayload()));
        }

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

    private function getModuleProperty(string $key, $default = null)
    {
        if ($module = $this->adapter->getObject('comModule', ['class_name' => \modmore\Commerce_Slack\Modules\Slack::class])) {
            return $module->getProperty($key, $default);
        }
        return $default;
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
