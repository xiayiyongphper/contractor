<?php

namespace service\resources\contractor\v1;

use common\models\LeContractor;
use common\models\RecordList as Record;
use common\models\RecordProducts;
use common\models\RecordWholesalers;
use framework\data\Pagination;
use service\components\Tools;
use service\message\contractor\RecordListRequest;
use service\models\common\Contractor;


class recordList extends Contractor
{
    const ACTION_DRAFT = 0;
    const ACTION_FINISH = 1;
    const PAGE_SIZE = 20;

    public function run($data)
    {
        /** @var RecordListRequest $request */
        $request = self::parseRequest($data);
        $response = self::response();

        $contractor = $this->initContractor($request);

        $query = Record::find()->alias('r');
        $query->leftJoin('le_customers as c', 'r.customer_id = c.entity_id')
            ->select([
                'r.entity_id as record_id',
                'r.customer_id',
                'r.store_name',
                'r.status',
                'r.updated_at',
                'r.remark',
                'c.store_front_img'
            ]);

        if ($request->getKeyword()) {
            $query->andWhere([
                'or',
                ['like', 'c.store_name', $request->getKeyword()],
                ['like', 'c.phone', $request->getKeyword()],
            ]);
        }

        if ($contractor->role != self::COMMON_CONTRACTOR) {
            $city = $request->getCity() ?: $contractor->city;
            $contractorAll = LeContractor::find()
                ->select('entity_id')
                ->where(['city' => $city])
                ->asArray()->all();

            $query->andWhere(['r.contractor_id' => array_column($contractorAll, 'entity_id')]);
            $query->andWhere(['r.status' => self::ACTION_FINISH]);
        } else {
            $query->andWhere(['r.contractor_id' => $request->getContractorId()]);

            if ($request->getCustomerId())
                $query->andWhere(['r.customer_id' => $request->getCustomerId()]);

            if ($request->getAction()) {
                $query->andWhere(['r.status' => self::ACTION_FINISH]);
            } else {
                $query->andWhere(['r.status' => self::ACTION_DRAFT]);
            }
        }

        $totalCount = $query->count();
        $paginationReq = $request->getPagination();
        if ($paginationReq) {
            $page = $paginationReq->getPage() ?: 1;
            $pageSize = $paginationReq->getPageSize() ?: self::PAGE_SIZE;
        } else {
            $page = 1;
            $pageSize = self::PAGE_SIZE;
        }

        $pagination = new Pagination(['totalCount' => $totalCount]);
        $pagination->setCurPage($page);
        $pagination->setPageSize($pageSize);
        $query->offset($pagination->getOffset())->limit($pageSize);
        $query->orderBy('r.updated_at DESC');

        Tools::log($query->createCommand()->getRawSql(), 'jun_sql.log');
        $records = $query->asArray()->all();

        $recordIds = array_column($records, 'record_id');
        $recordWholesalers = RecordWholesalers::find()->where(['record_id' => $recordIds])->asArray()->all();
        $recordWholesalersIds = array_column($recordWholesalers, 'entity_id');

        $recordWholesalersByRecordId = [];
        foreach ($recordWholesalers as $val) {
            $recordWholesalersByRecordId[$val['record_id']][] = $val;
        }

        $recordProducts = RecordProducts::find()
            ->select(['record_wholesaler_id', 'sum(num) as num', 'sum(total_price) as total_price'])
            ->where(['record_wholesaler_id' => $recordWholesalersIds])
            ->groupBy(['record_wholesaler_id'])
            ->asArray()->all();

        $recordProducts = array_column($recordProducts, null, 'record_wholesaler_id');

        foreach ($records as $key => &$record) {
            if (!isset($recordWholesalersByRecordId[$record['record_id']]) || empty($recordWholesalersByRecordId[$record['record_id']])) {
//                Record::findOne(['entity_id' => $record['record_id']])->delete();
//                unset($records[$key]);
                continue;
            }

            foreach ($recordWholesalersByRecordId[$record['record_id']] as $recordWholesaler) {
                if (!isset($recordProducts[$recordWholesaler['entity_id']]) || empty($recordProducts[$recordWholesaler['entity_id']]))
                    continue;

                $record['wholesalers'][] = [
                    'wholesaler_id' => $recordWholesaler['wholesaler_id'],
                    'wholesaler_name' => $recordWholesaler['wholesaler_name'],
                    'status' => $recordWholesaler['status'],
                    'num' => $recordProducts[$recordWholesaler['entity_id']]['num'],
                    'total_price' => $recordProducts[$recordWholesaler['entity_id']]['total_price'],
                ];
            }
        }

        $respData['record_list'] = $records;
        $respData['pagination'] = Tools::getPagination($pagination);

        $response->setFrom(Tools::pb_array_filter($respData));
        return $response;
    }


    public static function request()
    {
        return new RecordListRequest();
    }

    public static function response()
    {
        return new \service\message\contractor\RecordList();
    }

}