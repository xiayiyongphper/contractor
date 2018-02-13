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
class ContractorTasksLog extends ActiveRecord
{
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
        return 'log_contractor_task_set';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['operate_id','metric_id', 'contractor_id', 'value'], 'required'],
        ];
    }
}