<?php

namespace common\models;

use Yii;
use framework\db\ActiveRecord;
/**
 * This is the model class for table "city_config".
 *
 * @property integer $entity_id
 * @property string $city
 * @property string $chinese_name
 * @property integer $wallet_switch
 * @property string $rebates_order_created_from
 * @property string $additional_package_subsidies_range_from
 * @property string $additional_package_subsidies_range_to
 * @property string $additional_package_month_recharge_limit
 * @property string $additional_package_one_day_consume_limit
 * @property string $register_give_from
 * @property string $register_give_to
 * @property string $register_additional_package
 */
class CityConfig extends ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'city_config';
    }

    /**
     * @return \yii\db\Connection the database connection used by this AR class.
     */
    public static function getDb()
    {
        return Yii::$app->get('commonDb');
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['wallet_switch'], 'integer'],
            [['rebates_order_created_from'], 'safe'],
            [['additional_package_subsidies_range_from', 'additional_package_subsidies_range_to', 'additional_package_month_recharge_limit', 'register_give_from', 'register_give_to'], 'number'],
            [['city'], 'string', 'max' => 10],
            [['chinese_name'], 'string', 'max' => 120],
            [['city'], 'unique']
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'entity_id' => 'Entity ID',
            'city' => 'City',
            'chinese_name' => 'Chinese Name',
            'wallet_switch' => '城市钱包开关',
            'rebates_order_created_from' => '返现到钱包所要求的订单下单最早日期，只有在这个日期之后下单且开启钱包功能，才会返现到钱包',
            'additional_package_subsidies_range_from' => '额度包每单返现范围下限',
            'additional_package_subsidies_range_to' => '额度包每单返现范围上限',
            'additional_package_month_recharge_limit' => '额度包每月充值上限',
            'register_give_from' => '注册赠送金额范围下限',
            'register_give_to' => '注册赠送金额范围上限',
        ];
    }
}
