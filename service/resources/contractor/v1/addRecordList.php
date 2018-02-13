<?php

namespace service\resources\contractor\v1;

use common\models\LeCustomers;
use common\models\RecordList;
use common\models\RecordProducts;
use common\models\RecordWholesalers;
use framework\components\Date;
use service\components\ElasticSearchExt;
use service\components\ProductHelper;
use service\components\Tools;
use service\message\contractor\addRecordListRequest;
use service\message\contractor\RecordDetail;
use service\models\common\Contractor;
use service\models\common\ContractorException;


class addRecordList extends Contractor
{
    const ACTION_DRAFT = 0;
    const ACTION_FINISH = 1;

    public function run($data)
    {
        /** @var addRecordListRequest $request */
        $request = self::parseRequest($data);
        $response = self::response();

        $contractor = $this->initContractor($request);
        if ($contractor->role != self::COMMON_CONTRACTOR) {
            ContractorException::recordNotAllow();
        }

        $customer = LeCustomers::findOne(['entity_id' => $request->getCustomerId()]);

        if (!$customer || $customer->contractor_id != $contractor->entity_id) {
            ContractorException::customerNotMatch();
        }

        $date = new Date();
        if (!$request->getRecordId()) {
            $recordList = new RecordList();
            $dbTrans = $recordList->getDb()->beginTransaction();

            $recordList->contractor_id = $request->getContractorId();
            $recordList->customer_id = $request->getCustomerId();
            $recordList->store_name = $customer->store_name;
            $recordList->created_at = $date->date();
            $recordList->updated_at = $recordList->created_at;

            if (!$recordList->save()) {
                $dbTrans->rollBack();
                Tools::log($recordList->errors, 'jun.log');
                ContractorException::recordListSaveFailed();
            }

        } else {
            $where = [
                'entity_id' => $request->getRecordId(),
                'contractor_id' => $request->getContractorId(),
                'customer_id' => $request->getCustomerId()
            ];
            if (!empty($request->getProducts())) {
                $where['status'] = self::ACTION_DRAFT;
            }

            $recordList = RecordList::findOne($where);
            if (!$recordList) {
                ContractorException::recordListNotExist();
            }
            $dbTrans = $recordList->getDb()->beginTransaction();

            $request->getRemark() && $recordList->remark = $request->getRemark();
            $request->getAction() && $recordList->status = self::ACTION_FINISH;
            $request->getAction() || $recordList->status = self::ACTION_DRAFT;
            $recordList->updated_at = $date->date();

            if (!$recordList->save()) {
                $dbTrans->rollBack();
                Tools::log($recordList->errors, 'jun.log');
                ContractorException::recordListUpdateFailed();
            }
        }

        if (!empty($request->getProducts())) {

            foreach ($request->getProducts() as $product) {
                $productId = $product->getProductId();
                $esProduct = (new ProductHelper())->initWithProductIds([$productId], $customer->city, [], '388x388', false)
                    ->getTags()->getData();;

                if (!isset($esProduct[$productId]) || empty($esProduct[$productId])) {
                    $dbTrans->rollBack();
                    Tools::log($productId, 'esProduct.log');
                    ContractorException::markPriceProductNotFound();
                }
                $esProduct = $esProduct[$productId];
                $wholesalerId = $esProduct['wholesaler_id'];

                $recordWholesalers = RecordWholesalers::findOne([
                    'record_id' => $recordList->entity_id,
                    'wholesaler_id' => $wholesalerId,
                    'status' => 0
                ]);

                if (!$recordWholesalers) {
                    $recordWholesalers = new RecordWholesalers();
                    $recordWholesalers->record_id = $recordList->entity_id;
                    $recordWholesalers->wholesaler_id = $wholesalerId;
                    $recordWholesalers->wholesaler_name = $esProduct['wholesaler_name'];
                }
                $recordWholesalers->updated_at = $date->date();

                if (!$recordWholesalers->save()) {
                    $dbTrans->rollBack();
                    Tools::log($recordWholesalers->errors, 'jun.log');
                    ContractorException::recordWholesalersSaveFailed();
                }

                $recordProduct = RecordProducts::findOne([
                    'record_wholesaler_id' => $recordWholesalers->entity_id,
                    'product_id' => $productId
                ]);
                if (!$recordProduct && !$product->getNum()) {
                    $dbTrans->rollBack();
                    ContractorException::invalidParam();
                }

                if (!$product->getNum()) {
                    if (!$recordProduct->delete()) {
                        $dbTrans->rollBack();
                        Tools::log($recordProduct->errors, 'jun.log');
                        ContractorException::recordProductsDeleteFailed();
                    }
                    if (!RecordProducts::find()->where(['record_wholesaler_id' => $recordWholesalers->entity_id])->count()) {
                        if (!$recordWholesalers->delete()) {
                            $dbTrans->rollBack();
                            Tools::log($recordProduct->errors, 'jun.log');
                            ContractorException::recordWholesalersDeleteFailed();
                        }
                    }
                } else {
                    if (!$recordProduct) {
                        $recordProduct = new RecordProducts();
                        $recordProduct->record_wholesaler_id = $recordWholesalers->entity_id;
                        $recordProduct->product_id = $productId;
                        $recordProduct->product_name = $esProduct['name'];
                        $recordProduct->created_at = $date->date();
                        $recordProduct->customer_id = $request->getCustomerId();
                    }

                    $recordProduct->num = $product->getNum();
                    $recordProduct->price = $esProduct['price'];
                    $recordProduct->total_price = round($recordProduct->price * $recordProduct->num, 2);

                    if (!$recordProduct->save()) {
                        $dbTrans->rollBack();
                        Tools::log($recordProduct->errors, 'jun.log');
                        ContractorException::recordProductsSaveFailed();
                    }
                }
            }
        }

        $dbTrans->commit();
        $response->setRecordId($recordList->entity_id);

        return $response;
    }

    public static function request()
    {
        return new addRecordListRequest();
    }

    public static function response()
    {
        return new RecordDetail();
    }

}