<?php

namespace service\tasks;

use common\models\contractor\MarkPriceProduct;
use common\models\LeContractor;
use framework\components\ToolsAbstract;
use framework\tasks\TaskAbstract;

/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2016/1/21
 * Time: 15:09
 */

/**
 * 业务员标的商品价格上报任务分配，定时任务
 * Class assignMarkProductPriceTask
 * @package service\tasks
 */
class assignMarkProductPriceTask extends TaskAbstract
{
    public function run($data)
    {
        $this->log('assignMarkProductPriceTask-start');
        $cities = MarkPriceProduct::find()->addSelect('city')->andWhere(['status' => 1])->groupBy('city')->column();
        foreach ($cities as $city) {
            $this->log($city . '------BOF');
            $productIds = MarkPriceProduct::find()->where(['city' => $city])->andWhere(['status' => 1])->column();
            //打乱排序
            shuffle($productIds);
            $productCount = count($productIds);

            $contractorIds = LeContractor::find()
                ->alias('c')
                ->leftJoin(['a' => 'auth_assignment'], 'a.user_id=c.entity_id')
                ->where(['city' => $city, 'status' => 1])
                ->andWhere(['a.item_name' => '普通业务员'])
                ->column();
            //打乱排序
            shuffle($contractorIds);
            $contractorCount = count($contractorIds);
            $this->log($productCount . '--' . $contractorCount);

            if ($contractorCount == 0 || $productCount == 0) {
                $this->log('NO CONTRACTOR OR NO PRODUCT');
                continue;
            }

            $bulkSize = floor($productCount / $contractorCount);

            if ($bulkSize == 0) {
                $bulkSize = 1;
            }

            //数据分块
            $chunks = array_chunk($productIds, $bulkSize);

            if (count($chunks) > $contractorCount) {
                $extraIds = end($chunks);
                foreach ($extraIds as $key => $extraId) {
                    $chunks[$key][] = $extraId;
                }
            }

            $result = [];
            foreach ($contractorIds as $key => $contractorId) {
                if (isset($chunks[$key])) {
                    $result[$contractorId] = $chunks[$key];
                }
            }

            $this->log($result);
            $this->log($city . '------EOF');

            $connection = MarkPriceProduct::getDb();
            $transaction = $connection->beginTransaction();
            $date = ToolsAbstract::getDate();
            try {
                foreach ($result as $contractorId => $productIdArray) {
                    if (count($productIdArray) == 0) {
                        //当业务员当前没有分配商品时，跳过
                        $this->log('当业务员当前没有分配商品时，跳过');
                        continue;
                    }
                    $idString = implode(',', $productIdArray);
                    $updatedAt = $date->date();
                    //拼接sql
                    $sql = 'UPDATE ' . MarkPriceProduct::tableName() . ' SET contractor_id = ' . $contractorId . ", updated_at = '$updatedAt'" . " WHERE entity_id in ( $idString )";
                    $this->log($sql);
                    $connection->createCommand($sql)->execute();
                }
                $transaction->commit();
            } catch (\Exception $e) {
                $transaction->rollBack();
                $this->log($e->__toString());
            } catch (\Throwable $e) {
                $transaction->rollBack();
                $this->log($e->__toString());
            }
        }
        $this->log('assignMarkProductPriceTask-finished');
    }

    protected function getLogFileName()
    {
        if (!$this->logFileName) {
            $this->logFileName = $this->getDefaultLogFileName();
        }
        return $this->logFileName;
    }
}