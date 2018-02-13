<?php

namespace service\resources\contractor\v1;

use common\models\LeContractor;
use common\models\LeCustomers;
use common\models\LeCustomersAddressBook;
use common\models\RecordList;
use common\models\RecordProducts;
use common\models\RecordWholesalers;
use framework\db\readonly\models\core\SalesFlatOrder;
use service\components\ProductHelper;
use service\components\Proxy;
use service\components\Tools;
use service\message\contractor\RecordDetail;
use service\message\contractor\RecordListRequest;
use service\models\common\Contractor;
use service\models\common\ContractorException;


class recordListDetail extends Contractor
{

    public function run($data)
    {
        /** @var RecordListRequest $request */
        $request = self::parseRequest($data);
        $response = self::response();

        $contractor = $this->initContractor($request);
        $customer = LeCustomers::findOne(['entity_id' => $request->getCustomerId()]);

        if (!$customer || ($customer->contractor_id != $contractor->entity_id && $contractor->role == self::COMMON_CONTRACTOR)) {
            ContractorException::customerNotMatch();
        }

        $query = RecordList::find()
            ->select([
                'entity_id as record_id',
                'contractor_id',
                'customer_id',
                'store_name',
                'status',
                'updated_at',
                'remark'
            ])
            ->where([
                'entity_id' => $request->getRecordId(),
                'customer_id' => $request->getCustomerId()
            ]);

        if ($contractor->role == self::COMMON_CONTRACTOR) {
            $query->andWhere(['contractor_id' => $request->getContractorId()]);
        }

        $recordList = $query->asArray()->one();
        if (!$recordList) {
            ContractorException::recordListNotExist();
        }

        $addressBook = LeCustomersAddressBook::findReceiverCustomerId(['customer_id' => $customer->getId()]);
        if ($addressBook && $addressBook->getId()) {
            $recordList['receiver_name'] = $addressBook->receiver_name;
            $recordList['receiver_phone'] = $addressBook->phone;
        }
        $recordList['customer_phone'] = $customer->phone;
        $recordList['address'] = $customer->address;
        $recordList['detail_address'] = $customer->detail_address;

        $recordList['contractor_name'] = $contractor->name;
        if ($recordList['contractor_id'] != $request->getContractorId()) {
            $contractorObj = LeContractor::findOne(['entity_id' => $recordList['contractor_id']]);
            $recordList['contractor_name'] = $contractorObj->name;
        }
        unset($recordList['contractor_id']);

        $recordWholesalers = RecordWholesalers::find()
            ->select(['entity_id', 'wholesaler_id', 'wholesaler_name', 'status', 'order_id'])
            ->where(['record_id' => $request->getRecordId()])
            ->orderBy('status desc, updated_at desc')
            ->asArray()->all();
        $recordWholesalersIds = array_column($recordWholesalers, 'entity_id');
        $wholesalerIds = array_column($recordWholesalers, 'wholesaler_id');
        $wholesalerList = Tools::getStoreDetailBrief($wholesalerIds, $customer->area_id);

        $recordProducts = RecordProducts::find()
            ->select(['record_wholesaler_id', 'product_id', 'product_name', 'num', 'price', 'in_cart'])
            ->where(['record_wholesaler_id' => $recordWholesalersIds])
            ->orderBy('created_at desc')
            ->asArray()->all();


        $productIds = array_unique(array_column($recordProducts, 'product_id'));

        $esProducts = (new ProductHelper())->initWithProductIds($productIds, $customer->city, [], '388x388', false)
            ->getTags()->getData();

        $recordProductsByRWId = [];
        foreach ($recordProducts as $product) {
            $recordProductsByRWId[$product['record_wholesaler_id']][] = $product;
        }

        foreach ($recordWholesalers as $k => &$recordWholesaler) {
            if (!isset($recordProductsByRWId[$recordWholesaler['entity_id']]) || empty($recordProductsByRWId[$recordWholesaler['entity_id']])) {
                RecordWholesalers::findOne(['entity_id' => $recordWholesaler['entity_id']])->delete();
                unset($recordWholesalers[$k]);
                continue;
            }

            foreach ($recordProductsByRWId[$recordWholesaler['entity_id']] as $recordProduct) {
                if (!isset($esProducts[$recordProduct['product_id']]) || empty($esProducts[$recordProduct['product_id']])) {
                    ContractorException::markPriceProductNotFound();
                }
                $esProduct = $esProducts[$recordProduct['product_id']];
                $esProduct['num'] = $recordProduct['num'];
                $esProduct['in_cart'] = $recordProduct['in_cart'];

                if ($recordWholesaler['status']) {
                    $esProduct['name'] = $recordProduct['product_name'];
                    $esProduct['price'] = $recordProduct['price'];
                }

                $recordWholesaler['products'][] = $esProduct;
            }

            $orderCountToday = Proxy::getOrderCountToday($customer, $recordWholesaler['wholesaler_id']);

            if (isset($wholesalerList[$recordWholesaler['wholesaler_id']])) {
                $recordWholesaler['min_trade_amount'] = $orderCountToday > 0 ? 0 : $wholesalerList[$recordWholesaler['wholesaler_id']]['min_trade_amount'];
            }

            if ($recordWholesaler['order_id']) {
                $order = SalesFlatOrder::findOne(['entity_id' => $recordWholesaler['order_id']]);
                $recordWholesaler['order_status'] = $order->status;
                $recordWholesaler['sub_total'] = $order->subtotal;
                $recordWholesaler['grand_total'] = $order->grand_total;
                $recordWholesaler['prom_total'] = $order->subtotal - $order->grand_total;
            }

            $recordWholesaler['id'] = $recordWholesaler['entity_id'];

            if (isset($wholesalerList[$recordWholesaler['wholesaler_id']]) && $wholesalerList[$recordWholesaler['wholesaler_id']]['store_type'] == 6) {
                //是否为乐来自营零食店  0：不是  1：是  默认为0
                $recordWholesaler['is_self_wholesaler'] = 1;
            }


            unset($recordWholesaler['entity_id']);
        }

        $recordList['wholesalers'] = $recordWholesalers;
        Tools::log($recordList, 'recordListDetail.log');
        $response->setFrom(Tools::pb_array_filter($recordList));
        return $response;
    }


    public static function request()
    {
        return new RecordListRequest();
    }

    public static function response()
    {
        return new RecordDetail();
    }

}