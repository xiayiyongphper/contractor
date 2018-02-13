<?php
/**
 * Created by PhpStorm.
 * User: Ryan Hong
 * Date: 2017/11/7
 * Time: 10:23
 */

namespace service\resources\contractor\v1;

use common\models\contractor\TargetHelper;
use common\models\LeContractor;
use framework\components\ToolsAbstract;
use service\components\Tools;
use service\message\contractor\ShelvesHomeResponse;
use service\message\contractor\ShelvesHomeRequest;
use service\models\common\Contractor;
use service\models\common\ContractorException;
use common\models\LeCustomers;
use common\models\CustomerShelvesProduct;
use common\models\CustomerShelvesCategory;
use common\models\ShelvesProductCategoryMap;
use yii\db\Expression;
use yii\db\Query;

/**
 * Class shelvesHome
 * @package service\resources\contractor\v1
 */
class shelvesHome extends Contractor
{
    public function run($data){
        /** @var ShelvesHomeRequest $request */
        $request = self::parseRequest($data);
        $response = self::response();
        /** @var LeContractor $contractor */
        $contractor = $this->initContractor($request);

        $customerId = $request->getCustomerId();
        /** @var LeCustomers $customer */
        $customer = LeCustomers::findByCustomerId($customerId);
        if(empty($customer)){
            ContractorException::storeNotExist();
        }

        //所有货架分类
        $shelvesCategory = CustomerShelvesCategory::find()->asArray()->all();
//        ToolsAbstract::log($shelvesCategory,'shelves.log');
        $data = [];
        foreach ($shelvesCategory as $item){
            $data[$item['entity_id']] = [
//                'shelves_category_id' => $item['entity_id'],
                'category_name' => $item['name'],
                'product_count' => 0,
                'out_of_stock_count' => 0
            ];
        }

        $now = Tools::getDate()->date("Y-m-d H:i:s");
        $wholesalerIds = Tools::getWholesalerIdsByAreaId($customer->area_id);
        if(!empty($wholesalerIds)){
            $existQuery = (new Query())->select(['entity_id'])
                ->from(['p' => 'lelai_booking_product_a.products_city_'.$customer->city])
                ->where("p.lsin = a.lsin and p.wholesaler_id in (".join(',',$wholesalerIds).") and p.status=1 and p.state=2 and shelf_from_date < '".$now."' and shelf_to_date > '".$now."'");

            $shelvesData = CustomerShelvesProduct::find()
                ->alias('a')
                ->select(['b.shelves_category_id',new Expression('count(1) as product_count'),new Expression('sum(out_of_stock) as out_of_stock_count')])
                ->leftJoin(['b' => ShelvesProductCategoryMap::tableName()],'b.first_category_id=a.first_category_id')
                ->where(['customer_id' => $customerId])
                ->andWhere(['exists',$existQuery])
                ->groupBy('shelves_category_id');
            ToolsAbstract::log($shelvesData->createCommand()->getRawSql(),'shelves.log');
            $shelvesData = $shelvesData->asArray()->all();

//            $outOfStockData = CustomerShelvesProduct::find()
//                ->alias('a')
//                ->select(['b.shelves_category_id','count(1) as out_of_stock_count'])
//                ->leftJoin(['b' => ShelvesProductCategoryMap::tableName()],'b.first_category_id=a.first_category_id')
//                ->where([
//                    'customer_id' => $customerId,
//                    'out_of_stock' => 1,
//                ])
//                ->groupBy('shelves_category_id');
//            ToolsAbstract::log($outOfStockData->createCommand()->getRawSql(),'shelves.log');
//            $outOfStockData = $outOfStockData->asArray()->all();

            foreach ($shelvesData as $row){
                if(isset($data[$row['shelves_category_id']])){
                    $data[$row['shelves_category_id']]['product_count'] = $row['product_count'];
                    $data[$row['shelves_category_id']]['out_of_stock_count'] = $row['out_of_stock_count'];
                }
            }

//            foreach ($outOfStockData as $row){
//                if(isset($data[$row['shelves_category_id']])){
//                    $data[$row['shelves_category_id']]['out_of_stock_count'] = $row['out_of_stock_count'];
//                }
//            }
        }

        $quickEntry = [];
        foreach ($data as $k=>$v){
            $item = [
                'name' => $v['category_name']."({$v['product_count']})",
                'schema' => "lelaibd://shelf/detail?shelvesCategoryId=".$k."&shelvesCategoryName=".$v['category_name']."&customerId=".$customerId,
            ];
            if($v['product_count'] > 0 && $v['out_of_stock_count'] > 0){
//                $item['sub_name'] = $v['out_of_stock_count'] > 0 ? $v['out_of_stock_count'].'件商品急需补货' : '暂无补货需求';
                $item['sub_name'] = $v['out_of_stock_count'].'件商品急需补货';
            }

            $quickEntry []= $item;
        }
//        ToolsAbstract::log($quickEntry,'shelves.log');
        $result = [
            'quick_entry' => $quickEntry
        ];
        $response->setFrom(Tools::pb_array_filter($result));
        return $response;
    }

    public static function request()
    {
        return new ShelvesHomeRequest();
    }

    public static function response()
    {
        return new ShelvesHomeResponse();
    }
}