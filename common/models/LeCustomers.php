<?php
namespace common\models;

use framework\components\Debug;
use framework\components\es\Console;
use framework\components\Security;
use service\components\Events;
use service\components\Proxy;
use service\components\Tools;

use Yii;
use yii\base\NotSupportedException;
use framework\db\ActiveRecord;
use yii\web\IdentityInterface;

/**
 * User model
 * @property integer $entity_id
 * @property string $username
 * @property integer $province
 * @property integer $city
 * @property integer $phone
 * @property integer $district
 * @property integer $area_id
 * @property string $address
 * @property string $detail_address
 * @property string $store_name
 * @property string $storekeeper
 * @property string $store_area
 * @property string $lat
 * @property string $lng
 * @property string $img_lat
 * @property string $img_lng
 * @property string $password_reset_token
 * @property string $email
 * @property string $contractor
 * @property string $auth_token
 * @property float $orders_total_price
 * @property integer $status
 * @property integer $state
 * @property integer $type
 * @property integer $level
 * @property integer $contractor_id
 * @property string $created_at
 * @property string $updated_at
 * @property string $apply_at
 * @property integer $first_order_id
 * @property integer $tag_id
 * @property string $first_order_at
 * @property string $business_license_img
 * @property string $store_front_img
 * @property string $password write-only password
 * @property string $new_password
 * @property float $balance
 * @property float $business_license_no
 * @property string $last_visited_at
 * @property string $last_place_order_at
 * @property string $storekeeper_instore_times
 * @property string $store_type
 * @property string $store_area_new
 * @property string $disabled
 */
class LeCustomers extends ActiveRecord implements IdentityInterface
{
    public $data_debug;

    /**
     * 默认状态
     */
    const STATE_PENDING = 0;
    /**
     * 待客服审核
     */
    const STATE_PENDING_REVIEW = 1;
    /**
     * 人工审核通过
     */
    const STATE_MANUAL_APPROVED = 2;
    /**
     * 人工审核不通过
     */
    const STATE_MANUAL_DISAPPROVED = 3;
    /**
     * 系统审核通过
     */
    const STATE_AUTOMATIC_APPROVED = 4;

    const STATUS_DELETED = 0;
    const STATUS_ACTIVE = 10;
    const CUSTOMERS_INFO_COLLECTION = 'customers_info_collection';
    public $auth_key;

    /**
     * 是否意向超市
     * @var integer
     */
    public $intention;
	public $distance;
	protected $customer_style = 0;

	// 余额每天消费限额
	const BALANCE_CONSUME_LIMIT = 500;

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
        return 'le_customers';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            ['phone', 'required'],
        ];
    }

    /**
     * get userinfo by entity_id and update userinfo in redis
     */
    public static function findIdentity($id)
    {
        $userinfo = static::findOne(['entity_id' => $id]);
        if ($userinfo) {
            //Yii::$app->redisCache->set('le_user_'.$userinfo->entity_id,$userinfo);
            Yii::$app->getSession()->set('le_user_' . $userinfo->entity_id, $userinfo);
        }
        return $userinfo;
    }


    /**
     * 更新session,redis,identity中保存的用户信息
     * @param $id
     * @return null|static
     */
    public static function getAndUpUserbyId($id)
    {
        $userInfo = static::findOne(['entity_id' => $id]);
        if ($userInfo) {
//        	Yii::$app->redisCache->set('le_user_'.$userinfo->entity_id,$userinfo);
            Yii::$app->getSession()->set('le_user_' . $userInfo->entity_id, $userInfo);
            Yii::$app->redisCache->set('le_user_'.$id,$userInfo);
            Yii::$app->user->identity = $userInfo;

        }
        return $userInfo;
    }

    /**
     * 变更绑定手机号
     * @param $phone
     * @param $id
     * @return null|static
     */
    public static function changeBandingPhone($id,$phone)
    {
        $userInfo = static::findOne(['entity_id' => $id]);
        if ($userInfo) {
            $userInfo->phone = $phone;
            $userInfo->save();
            self::getAndUpUserbyId($id);
            return true;
        }
        return false;
    }


    /**
     * @inheritdoc
     */
    public static function findIdentityByAccessToken($token, $type = null)
    {
        throw new NotSupportedException('"findIdentityByAccessToken" is not implemented.');
    }

    /**
     * Finds user by username
     *
     * @param string $username
     * @param string $password
     * @return bool|LeCustomers
     */
    public static function findByUsername($username, $password)
    {
        /** @var LeCustomers $customer */
        $customer = self::find()->where(['username' => $username])
           ->orWhere(['phone' => $username])->one();
        //手机号登陆或用户名登陆都可以
        $newPassword = '';
        $flag = Security::passwordVerify($password,$customer->password,$newPassword);
        if($newPassword){
            $customer->password = $newPassword;
            $customer->save();
        }
        if($flag === true){
            return $customer;
        }else{
            return false;
        }
    }

    /**
     * Finds user by username
     *
     * @param LeCustomers $customer
     * @param string $password
     * @return bool|LeCustomers
     */
    public static function verifyPassword($customer, $password)
    {
        //手机号登陆或用户名登陆都可以
        $newPassword = '';
        $flag = Security::passwordVerify($password,$customer->password,$newPassword);
        if($newPassword){
            $customer->password = $newPassword;
            $customer->save();
        }
        if($flag === true){
            return $customer;
        }else{
            return false;
        }
    }

    /**
     * Function: checkUsername
     * Author: Jason Y. Wang
     *
     * @param $username
     * @return bool
     */
    public static function checkUsername($username)
    {
        //手机号登陆或用户名登陆都可以
        return self::find()->where(['username' => $username])
            ->orWhere(['phone' => $username])->exists();
    }

    /**
     * 通过userId得到超市模型
     * @param $customerId
     * @return null|static
     */
    public static function findByCustomerId($customerId)
    {
        return static::findOne(['entity_id' => $customerId]);
    }

    /**
     * Finds user by phone
     *
     * @param string $phone
     * @return static|null
     */
    public static function findByPhone($phone)
    {
        return static::findOne(['phone' => $phone]);
    }

    /**
     * @param $phone
     * @param $id
     * Author Jason Y. wang
     * 检查用户是否重复
     * @return array|null|ActiveRecord
     */
    public static function checkUserDuplicated($phone,$id)
    {

        return static::find()->where(['phone' => $phone])->andWhere(['<>','entity_id',$id])->one();
    }

    /**
     * 检查用户名是否存在
     * @param $username
     * @return null|static
     */
    public static function checkUserByUserName($username)
    {
        return static::findOne(['username' => $username]);
    }

    /**
     * 检查电话号码是否存在
     * @param $phone
     * @return null|static
     */
    public static function checkUserByPhone($phone)
    {

        return static::findOne(['phone' => $phone]);
    }

    /**
     * Finds user by password reset token
     *
     * @param string $token password reset token
     * @return static|null
     */
    public static function findByPasswordResetToken($token)
    {
        if (!static::isPasswordResetTokenValid($token)) {
            return null;
        }

        return static::findOne([
            'password_reset_token' => $token,
            'status' => self::STATUS_ACTIVE,
        ]);
    }

    /**
     * Finds out if password reset token is valid
     *
     * @param string $token password reset token
     * @return boolean
     */
    public static function isPasswordResetTokenValid($token)
    {
        if (empty($token)) {
            return false;
        }
        $expire = Yii::$app->params['user.passwordResetTokenExpire'];
        $parts = explode('_', $token);
        $timestamp = (int)end($parts);
        return $timestamp + $expire >= time();
    }

    /**
     * @inheritdoc
     */
    public function getId()
    {
        return $this->getPrimaryKey();
    }

    /**
     * @inheritdoc
     */
    public function getAuthKey()
    {
        return $this->auth_key;
    }

    /**
     * @inheritdoc
     */
    public function validateAuthKey($authKey)
    {
        return $this->getAuthKey() === $authKey;
    }

    /**
     * Validates password
     *
     * @param string $password password to validate
     * @return boolean if password provided is valid for current user
     */
    public function validatePassword($password)
    {
        return Yii::$app->security->validatePassword($password, $this->password_hash);
    }

    /**
     * Generates password hash from password and sets it to the model
     *
     * @param string $password
     */
    public function setPassword($password)
    {
        $this->password_hash = Yii::$app->security->generatePasswordHash($password);
    }

    /**
     * Generates "remember me" authentication key
     */
    public function generateAuthKey()
    {
        $this->auth_key = Yii::$app->security->generateRandomString();
    }

    /**
     * Generates new password reset token
     */
    public function generatePasswordResetToken()
    {
        $this->password_reset_token = Yii::$app->security->generateRandomString() . '_' . time();
    }

    /**
     * Removes password reset token
     */
    public function removePasswordResetToken()
    {
        $this->password_reset_token = null;
    }
    public function afterSave($insert, $changedAttributes)
    {
        parent::afterSave($insert, $changedAttributes); // TODO: Change the autogenerated stub
        //用户信息存入缓存
        Tools::getRedis()->hDel(self::CUSTOMERS_INFO_COLLECTION,$this->entity_id);
    }

    public function save($runValidation = true, $attributeNames = null)
    {
        if($this->getIsNewRecord()){
            Tools::log($this->phone,'create.log');
            Tools::log($this->data_debug,'create.log');
            Tools::log(Debug::backtrace(true,false),'create.log');
        }else{
            Tools::log('+++++++++++++++','create.log');
        }
        return parent::save($runValidation, $attributeNames); // TODO: Change the autogenerated stub
    }


    /**
	 * 增加余额
	 * @param string $title
	 * @param string $action
	 * @param int    $amount
	 * @param null   $order_id
	 * @param null   $order_no
	 *
	 * @return bool
	 */
	public function addBalance($title='UNKNOW', $action='UNDEFINED', $amount=0, $order_id=null, $order_no=null)
	{
		Tools::log('addBalance', 'balance.txt');
		$customer_id = $this->getId();
		if(!$customer_id){
			return false;
		}
		if($amount<0){
			return false;
		}
		Tools::log([$customer_id, $title, $action, 1, $amount, $this->balance+$amount, $order_id, $order_no], 'balance.txt');
		$this->addBalanceLog($customer_id, $title, $action, 1, $amount, $this->balance+$amount, $order_id, $order_no);
		$this->setAttribute('balance', $this->balance+$amount);
		$this->save();
		if(!$this->save()){
			Console::get()->log(
				[
					'errors'=>$this->errors,
					'params'=>[$customer_id, $title, $action, 1, $amount, $this->balance+$amount, $order_id, $order_no],
				], null,
				['error', 'balance', 'addBalance'],
				Console::ES_LEVEL_ERROR
			);
		}

		return true;
	}


	/**
	 * 减少余额
	 * @param string $title
	 * @param string $action
	 * @param int    $amount
	 * @param null   $order_id
	 * @param null   $order_no
	 */
	public function reduceBalance($title='UNKNOW', $action='UNDEFINED', $amount=0, $order_id=null, $order_no=null)
	{
		Tools::log('reduceBalance', 'balance.txt');
		$customer_id = $this->getId();
		if(!$customer_id){
			return;
		}
		if($amount<0){
			return;
		}

		Tools::log([$customer_id,$title, $action, 0, $amount, $this->balance-$amount, $order_id, $order_no], 'balance.txt');
		$this->addBalanceLog($customer_id, $title, $action, 0, $amount, $this->balance-$amount, $order_id, $order_no);
		$this->setAttribute('balance', $this->balance-$amount);
		if(!$this->save()){
			Console::get()->log(
				[
					'errors'=>$this->errors,
					'params'=>[$customer_id, $title, $action, 0, $amount, $this->balance-$amount, $order_id, $order_no],
				], null,
				['balance', 'reduceBalance'],
				Console::ES_LEVEL_ERROR
			);
		}
	}


	/**
	 * @param null   $transaction_no
	 * @param null   $customer_id
	 * @param string $title
	 * @param string $action
	 * @param int    $type
	 * @param int    $amount
	 * @param null   $balance
	 * @param null   $order_id
	 * @param null   $order_no
	 *
	 * @return string
	 */
	public function addBalanceLog(
		$customer_id=null,
		$title='UNKNOW',
		$action='UNDEFINED',
		$type=1,
		$amount=0,
		$balance=null,
		$order_id=null,
		$order_no=null)
	{
		// 交易号
		$transaction_no = LeCustomersBalanceLog::getTransactionNo();

		/** @var LeCustomersBalanceLog $bLog */
		$bLog = new LeCustomersBalanceLog();
		$data = [
			'transaction_no' => $transaction_no,
			'customer_id' => $customer_id,
			'title' => $title,
			'action' => $action,
			'type' => $type,
			'amount' => $amount,
			'balance' => $balance,
			'order_id' => $order_id,
			'order_no' => $order_no,
			'created_at' => date('Y-m-d H:i:s',time()),
		];
		$bLog->setAttributes($data, false);
		Tools::log('addBalanceLog', 'balance.txt');
		Tools::log($bLog->toArray(), 'balance.txt');
		if(!$bLog->insert(false)){
			Console::get()->log(
				[
					'data'=>$data,
					'errors'=>$bLog->errors,
				], null,
				['balance', 'addBalanceLog'],
				Console::ES_LEVEL_ERROR
			);
		}else{
			Console::get()->log($data, null, ['balance', 'addBalanceLog'], Console::ES_LEVEL_NOTICE);
		}

		// 在增加balanceLog的同时也新增一条记录到订单状态里
		// 与订单相关的才通知core模块
		if($order_id){
			// 现在只有取消退回钱包的才发消息给core
			if($action=='RETURN'){
				// Comment
				$data['comment'] = '已退回¥'.$amount.'到零钱';

				$name = Events::EVENT_BALANCE_CHANGE;
				$eventName = Events::getCoreEventName($name);
				$events[$eventName] = [
					'name' => $name,
					'data' => $data,
				];
				foreach ($events as $eventName => $event) {
					Proxy::sendMessage($eventName, $event);
				}
			}

		}

	}

	/**
	 * @return string
	 */
	public function getBalanceAvailableToday()
	{
		$consumeRecord = LeCustomersBalanceLog::find()
			->where([
				'customer_id'=>$this->getId(),
				'action'=>'CONSUME',
			])
			->andWhere('created_at >= "'.date('Y-m-d H:i:s').'"')
			->sum('amount');
		if(!$consumeRecord){
			return self::BALANCE_CONSUME_LIMIT;
		}elseif(floatval($consumeRecord)<self::BALANCE_CONSUME_LIMIT){
			return self::BALANCE_CONSUME_LIMIT - floatval($consumeRecord);
		}else{
			return 0;
		}

	}


	/**
	 * 增加额度包余额
	 * @param string $title
	 * @param string $action
	 * @param int    $amount
	 * @param null   $order_id
	 * @param null   $order_no
	 */
	public function addBalanceAdditionalPackage($title='UNKNOW', $action='UNDEFINED', $amount=0, $order_id=null, $order_no=null)
	{
		Tools::log('addBalanceAdditionalPackage', 'additional_package.txt');
		$customer_id = $this->getId();
		if(!$customer_id){
			return false;
		}
		if($amount<0){
			return false;
		}

		// 用户当前额度包余额
		$ap = $this->getAdditionalPackage();
		Tools::log([$customer_id, $title, $action, 1, $amount, $ap+$amount, $order_id, $order_no], 'additional_package.txt');
		$this->addBalanceAdditionalPackageLog($customer_id, $title, $action, 1, $amount, $ap+$amount, $order_id, $order_no);

		// 保存额度包余额
		/** @var LeCustomersBalanceAdditionalPackage $bap */
		$bap = LeCustomersBalanceAdditionalPackage::findByCustomerId($this->getId());
		if(!$bap) {
			// 还未建立记录
			$bap = new LeCustomersBalanceAdditionalPackage();
			$bap->customer_id = $customer_id;
		}
		// 更新
		$bap->setAttribute('balance', $ap+$amount);

		if(!$bap->save()){
			Console::get()->log(
				[
					'errors'=>$bap->errors,
					'params'=>[$customer_id, $title, $action, 1, $amount, $ap+$amount, $order_id, $order_no],
				], null,
				['error', 'database_error', 'balance_additional_package', 'addBalanceAdditionalPackage'],
				Console::ES_LEVEL_ERROR
			);
			return false;
		}

		return true;
	}


	/**
	 * 减少余额
	 * @param string $title
	 * @param string $action
	 * @param int    $amount
	 * @param null   $order_id
	 * @param null   $order_no
	 */
	public function reduceBalanceAdditionalPackage($title='UNKNOW', $action='UNDEFINED', $amount=0, $order_id=null, $order_no=null)
	{
		Tools::log('reduceBalanceAdditionalPackage', 'additional_package.txt');
		$customer_id = $this->getId();
		if(!$customer_id){
			return;
		}
		if($amount<0){
			return;
		}

		// 用户当前额度包余额
		$ap = $this->getAdditionalPackage();
		Tools::log([$customer_id,$title, $action, 0, $amount, $ap-$amount, $order_id, $order_no], 'additional_package.txt');
		$this->addBalanceAdditionalPackageLog($customer_id, $title, $action, 0, $amount, $ap-$amount, $order_id, $order_no);

		// 保存额度包余额
		$bap = LeCustomersBalanceAdditionalPackage::findByCustomerId($this->getId());
		if(!$bap) {
			// 还未建立记录
			$bap = new LeCustomersBalanceAdditionalPackage();
			$bap->customer_id = $customer_id;
		}
		$bap->setAttribute('balance', $ap-$amount);

		if(!$bap->save()){
			Console::get()->log(
				[
					'errors'=>$this->errors,
					'params'=>[$customer_id, $title, $action, 0, $amount, $ap-$amount, $order_id, $order_no],
				], null,
				['balance_additional_package', 'reduceBalanceAdditionalPackage'],
				Console::ES_LEVEL_ERROR
			);

		}
	}

	/**
	 * @param null   $transaction_no
	 * @param null   $customer_id
	 * @param string $title
	 * @param string $action
	 * @param int    $type
	 * @param int    $amount
	 * @param null   $balance
	 * @param null   $order_id
	 * @param null   $order_no
	 *
	 * @return string
	 */
	public function addBalanceAdditionalPackageLog(
		$customer_id=null,
		$title='UNKNOW',
		$action='UNDEFINED',
		$type=1,
		$amount=0,
		$balance=null,
		$order_id=null,
		$order_no=null)
	{
		// 交易号
		$transaction_no = LeCustomersBalanceLog::getTransactionNo();

		/** @var LeCustomersBalanceAdditionalPackageLog $bLog */
		$bLog = new LeCustomersBalanceAdditionalPackageLog();
		$data = [
			'transaction_no' => $transaction_no,
			'customer_id' => $customer_id,
			'title' => $title,
			'action' => $action,
			'type' => $type,
			'amount' => $amount,
			'balance' => $balance,
			'order_id' => $order_id,
			'order_no' => $order_no,
			'created_at' => date('Y-m-d H:i:s',time()),
		];
		$bLog->setAttributes($data, false);
		Tools::log('addBalanceAdditionalPackageLog', 'additional_package.txt');
		Tools::log($bLog->toArray(), 'additional_package.txt');
		if(!$bLog->insert(false)){
			Console::get()->log(
				[
					'data'=>$data,
					'errors'=>$bLog->errors,
				], null,
				['balance_additional_package', 'addBalanceAdditionalPackageLog'],
				Console::ES_LEVEL_ERROR
			);
		}else{
			Console::get()->log($data, null, ['balance_additional_package', 'addBalanceAdditionalPackageLog'], Console::ES_LEVEL_NOTICE);
		}

	}

	/**
	 * @return bool|int|string
	 */
	public function getAdditionalPackage(){
		if(!$this->getId()){
			return false;
		}
		return LeCustomersBalanceAdditionalPackage::getByCustomerId($this->getId());
	}

	public function beforeSave($insert)
    {
        if(!$this->state){
            $this->state = LeCustomers::STATE_PENDING_REVIEW;
        }

        return parent::beforeSave($insert); // TODO: Change the autogenerated stub
    }
}
