<?php

namespace console\controllers;

use service\components\ElasticSearchExt;
use yii\console\Controller;


/**
 * Created by Jason.
 * Author: Jason Y. Wang
 * Date: 2016/2/1
 * Time: 14:40
 */

class EsController extends Controller
{

    public function actionIndex(){
        $client = new ElasticSearchExt(441800);
        $result = $client->searchByProductIds([15,16,17]);
        print_r(array_keys($result));
        $result = $client->searchByProductIds([15,16,17]);
        print_r($result);
    }

}
