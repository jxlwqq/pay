<?php

namespace WizardPro\Pay\Gateways\Wechat;

use Symfony\Component\HttpFoundation\Request;
use WizardPro\Pay\Events;
use WizardPro\Pay\Exceptions\GatewayException;
use WizardPro\Pay\Exceptions\InvalidArgumentException;
use WizardPro\Pay\Exceptions\InvalidSignException;
use WizardPro\Pay\Gateways\Wechat;
use WizardPro\Supports\Collection;

class TransferGateway extends Gateway
{
    /**
     * Pay an order.
     *
     * @author yansongda <me@yansongda.cn>
     *
     * @param string $endpoint
     * @param array  $payload
     *
     * @throws GatewayException
     * @throws InvalidArgumentException
     * @throws InvalidSignException
     *
     * @return Collection
     */
    public function pay($endpoint, array $payload): Collection
    {
        if (Wechat::MODE_SERVICE === $this->mode) {
            unset($payload['sub_mch_id'], $payload['sub_appid']);
        }

        $type = Support::getTypeName($payload['type'] ?? '');

        $payload['mch_appid'] = Support::getInstance()->getConfig($type, '');
        $payload['mchid'] = $payload['mch_id'];

        if ('cli' !== php_sapi_name() && !isset($payload['spbill_create_ip'])) {
            $payload['spbill_create_ip'] = Request::createFromGlobals()->server->get('SERVER_ADDR');
        }

        unset($payload['appid'], $payload['mch_id'], $payload['trade_type'],
            $payload['notify_url'], $payload['type']);

        $payload['sign'] = Support::generateSign($payload);

        Events::dispatch(new Events\PayStarted('Wechat', 'Transfer', $endpoint, $payload));

        return Support::requestApi(
            'mmpaymkttransfers/promotion/transfers',
            $payload,
            true
        );
    }

    /**
     * Find.
     *
     * @author yansongda <me@yansongda.cn>
     *
     * @param $order
     *
     * @return array
     */
    public function find($order): array
    {
        return [
            'endpoint' => 'mmpaymkttransfers/gettransferinfo',
            'order' => is_array($order) ? $order : ['partner_trade_no' => $order],
            'cert' => true,
        ];
    }

    /**
     * Get trade type config.
     *
     * @author yansongda <me@yansongda.cn>
     *
     * @return string
     */
    protected function getTradeType(): string
    {
        return '';
    }
}
