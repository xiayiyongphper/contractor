<?php
namespace common\models\contractor;

use service\components\Tools;
use framework\components\ToolsAbstract;
use Yii;
use framework\db\ActiveRecord;

/**
 * Class ContractorTasks
 * @package common\models\contractor
 * @property integer $entity_id
 * @property integer $owner_id
 * @property integer $metric_id
 * @property float $base_value
 * @property float $target_value
 * @property float $perfect_value
 * @property integer $month
 * @property integer $city
 * @property integer $owner_type
 */
class ContractorTasks extends ActiveRecord
{
    const OWNER_TYPE_CITY = 1;//所有者类型：城市
    const OWNER_TYPE_CONTRACTOR = 2;//所有者类型：业务员
    const KEY_CONTRACTOR_TASK_SET_TIME = 'contractor_task_set_time_%s_%s_%s';//业务员目标设置时间key,contractor_task_set_time_{contractor_id}_{month}_{city}
    /**
     * @return \yii\db\Connection the database connection used by this AR class.
     */
    public static function getDb()
    {
        return Yii::$app->get('mainDb');
    }

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'contractor_tasks';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['metric_id', 'month', 'target_value','city'], 'required'],
        ];
    }

    //获取某个城市某月的指标
    public static function getCityTask($city,$month)
    {
        $task = static::findAll(['city' => $city,'month' => $month,'owner_type' => self::OWNER_TYPE_CITY]);
        //Tools::log($task,'whole_target.log');
        return $task;
    }

    //获取某个城市某月所有业务员的指标
    public static function getContractorsTaskByCity($city,$month)
    {
        $task = static::findAll(['city' => $city,'month' => $month,'owner_type' => self::OWNER_TYPE_CONTRACTOR]);
        //Tools::log($task,'whole_target.log');
        return $task;
    }

    //获取业务员某月的指标
    public static function getContractorTask($contractor_id,$month,$city)
    {
        $task = static::findAll(['owner_id' => $contractor_id,'month' => $month,'city' => $city,'owner_type' => self::OWNER_TYPE_CONTRACTOR]);
        return $task;
    }

    /**
     * @param int $owerId
     * @param int $month
     * @param int $city
     * @return static
     */
    public static function getTasksByOwnerIdMonth($owerId, $month, $city = null)
    {
        $cond = [
            'owner_id' => $owerId,
            'owner_type' => self::getOwnerTypeByOwnerId($owerId),
            'month' => $month
        ];

        if ($city) {
            $cond['city'] = $city;
        }
        return static::find()->orderBy('entity_id ASC')->where($cond)->all();
    }

    /**
     * 获取最近设置的指标
     *
     * @param integer $city
     * @param integer $owerId
     * @return array|null|ContractorTasks
     */
    public static function getLastTask($city, $owerId)
    {
        $month = ToolsAbstract::getDate()->date('Ym');
        return static::find()->where([
            'owner_id' => $owerId,
            'city' => $city,
            'owner_type' => self::getOwnerTypeByOwnerId($owerId)
        ])->andWhere(['<=', 'month', $month])->orderBy('month DESC')->one();
    }

    /**
     * 根据OwnerId获取类型
     *
     * @param $ownerId
     * @return int
     */
    public static function getOwnerTypeByOwnerId($ownerId)
    {
        return ($ownerId === 0) ? self::OWNER_TYPE_CITY : self::OWNER_TYPE_CONTRACTOR;
    }
}