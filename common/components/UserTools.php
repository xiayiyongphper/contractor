<?php 
namespace common\components;

use common\Mathematics\Point;
use common\models\LeContractor;

use common\models\RegionArea;
use common\Mathematics\Algorithm\Polygon;
use framework\components\ToolsAbstract;
use service\components\Proxy;
use service\components\Tools;
use service\message\common\ContractorStatics;
use service\message\common\Order;
use service\message\common\Product;
use service\message\common\Store;
use service\message\contractor\ContractorHomeDataRequest;
use service\message\contractor\OrderTrackingRequest;
use service\message\contractor\OrderTrackingResponse;
use service\message\contractor\StoreRecentlyOrderRequest;
use service\message\contractor\StoreRecentlyOrderResponse;
use service\message\contractor\StoresListRequest;
use service\message\contractor\StoresResponse;
use service\message\core\RecentlyOrderStoresRequest;

use service\message\merchant\ContractorMerchantRequest;
use service\message\merchant\ContractorMerchantResponse;
use service\message\merchant\getProductBriefRequest;
use service\message\merchant\getProductBriefResponse;
use service\message\merchant\getStoreDetailRequest;
use service\message\sales\DriverOrderCollectionRequest;
use service\message\sales\DriverOrderDetailRequest;
use service\message\sales\OrderCollectionResponse;
use service\message\sales\GetCustomerFirstOrderRequest;

use service\models\common\CustomerException;
use service\models\common\DriverException;


/**
* public function
*/
class UserTools 
{
    //生成随机数
    const CHARS_LOWERS                          = 'abcdefghijklmnopqrstuvwxyz';
    const CHARS_UPPERS                          = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
    const CHARS_DIGITS                          = '0123456789';

	/**
	  * 加密解密函数
	  */
	public static function authcode($string, $operation = 'DECODE', $key = '', $expiry = 0) {   
	    // 动态密匙长度，相同的明文会生成不同密文就是依靠动态密匙   
	    $ckey_length = 4;   
	       
	    // 密匙   
	    $key = md5($key ? $key : 'lelai_pcweb');   
	       
	    // 密匙a会参与加解密   
	    $keya = md5(substr($key, 0, 16));   
	    // 密匙b会用来做数据完整性验证   
	    $keyb = md5(substr($key, 16, 16));   
	    // 密匙c用于变化生成的密文   
	    $keyc = $ckey_length ? ($operation == 'DECODE' ? substr($string, 0, $ckey_length): 
	substr(md5(microtime()), -$ckey_length)) : '';   
	    // 参与运算的密匙   
	    $cryptkey = $keya.md5($keya.$keyc);   
	    $key_length = strlen($cryptkey);   
	    // 明文，前10位用来保存时间戳，解密时验证数据有效性，10到26位用来保存$keyb(密匙b)， 
	//解密时会通过这个密匙验证数据完整性   
	    // 如果是解码的话，会从第$ckey_length位开始，因为密文前$ckey_length位保存 动态密匙，以保证解密正确   
	    $string = $operation == 'DECODE' ? base64_decode(substr($string, $ckey_length)) :  
	sprintf('%010d', $expiry ? $expiry + time() : 0).substr(md5($string.$keyb), 0, 16).$string;   
	    $string_length = strlen($string);   
	    $result = '';   
	    $box = range(0, 255);   
	    $rndkey = array();   
	    // 产生密匙簿   
	    for($i = 0; $i <= 255; $i++) {   
	        $rndkey[$i] = ord($cryptkey[$i % $key_length]);   
	    }   
	    // 用固定的算法，打乱密匙簿，增加随机性，好像很复杂，实际上对并不会增加密文的强度   
	    for($j = $i = 0; $i < 256; $i++) {   
	        $j = ($j + $box[$i] + $rndkey[$i]) % 256;   
	        $tmp = $box[$i];   
	        $box[$i] = $box[$j];   
	        $box[$j] = $tmp;   
	    }   
	    // 核心加解密部分   
	    for($a = $j = $i = 0; $i < $string_length; $i++) {   
	        $a = ($a + 1) % 256;   
	        $j = ($j + $box[$a]) % 256;   
	        $tmp = $box[$a];   
	        $box[$a] = $box[$j];   
	        $box[$j] = $tmp;   
	        // 从密匙簿得出密匙进行异或，再转成字符   
	        $result .= chr(ord($string[$i]) ^ ($box[($box[$a] + $box[$j]) % 256]));   
	    }   
	    if($operation == 'DECODE') {  
	        // 验证数据有效性，请看未加密明文的格式   
	        if((substr($result, 0, 10) == 0 || substr($result, 0, 10) - time() > 0) &&  
	substr($result, 10, 16) == substr(md5(substr($result, 26).$keyb), 0, 16)) {   
	            return substr($result, 26);   
	        } else {   
	            return '';   
	        }   
	    } else {   
	        // 把动态密匙保存在密文里，这也是为什么同样的明文，生产不同密文后能解密的原因   
	        // 因为加密后的密文可能是一些特殊字符，复制过程可能会丢失，所以用base64编码   
	        return $keyc.str_replace('=', '', base64_encode($result));   
	    }   
	}

	/**
	 * 通过超市经纬找出这个超市所在的area ID
	 * @param $lng
	 * @param $lat
	 * @return array|bool
	 */
	public static function findAreaIdByLocation($lat,$lng){
		$areaId = 0;
		if ($lat && $lng) {
			$areas = (new RegionArea())->find();
			//根据最大最小经纬度粗略查找所在区域
			$areas->where(['<','min_lat',$lat])->andWhere(['>','max_lat',$lat])
				  ->andWhere(['<','min_lng',$lng])->andWhere(['>','max_lng',$lng]);
			$areasOne = $areas->asArray()->all();
			foreach ($areasOne as $key => $item) {
				//精细查找
				if (static::whetherInTheRange($lat, $lng,$item)) {
					//运营保证区域ID不重合，一个超市只能属于一个区域ID
					//发现匹配的区域后，直接退出循环
					//如果后面也有匹配区域暂时不做处理
					$areaId = $item['entity_id'];
					break;
				}
			}
		}
		return $areaId;
	}

	/**
	 *
	 * 确认超市是否在区域，有时间看下这里的算法
	 * @param $lat
	 * @param $lng
	 * @param $item
	 * @return bool
	 */
	public static function whetherInTheRange($lat, $lng,$item)
	{
		//TODO:有时间看看这里的算法
		$point = new Point($lng, $lat);
		$regions = unserialize($item['polygon']);
		$flag = false;

		foreach ($regions as $region) {
			$points = explode(';', $region['coordinates']);
			$polygon = new Polygon($point);
			foreach ($points as $onePoint) {
				$pointArray = explode(',', $onePoint);
				$new_point = new Point($pointArray[0], $pointArray[1]);
				$polygon->addPoint($new_point);
			}
			if ($polygon->pointInPolygon()) {
				$flag = true;
				break;
			}
		}
		return $flag;
	}

	/**
	 * Function: getStoreInfo
	 * Author: Jason Y. Wang
	 *
	 * @param $wholesaler_id
	 * @return Store
	 * @throws CustomerException
	 */
	public static function getStoreInfoByProxy($wholesaler_id){
		//初始化店铺
		$storeDetailRequest = new getStoreDetailRequest();
		$storeDetailRequest->setWholesalerId($wholesaler_id);
		$response = Proxy::sendRequest('merchant.getStoreDetail',$storeDetailRequest);
		/** @var Store $store */
		//得到店铺信息
		try{
		    $store = new Store();
			$store->parseFromString($response->getPackageBody());
		}catch(\Exception $e){
			$store = null;
		}
		return $store;

	}

	public static function getOrderInfoByProxy($id,$field){

		$orderDetailRequest = new DriverOrderDetailRequest();
        if($id == 'increment_id'){
            $orderDetailRequest->setIncrementId($field);
        }else if($id == 'order_id'){
            $orderDetailRequest->setOrderId($field);
        }else{
            DriverException::driverOrderIdInvalid();
        }

		$response = Proxy::sendRequest('sales.driverOrderDetail',$orderDetailRequest);
		/** @var Order $order */
		//得到店铺信息
		try{
		    $order = new Order();
			$order->parseFromString($response->getPackageBody());
		}catch(\Exception $e){
			$order = null;
		}
		return $order;

	}

	public static function getOrderInfoCollectionsByProxy($orderIds){

		$orderDetailCollectionRequest = new DriverOrderCollectionRequest();
		$order_ids = [
			'order_ids' => $orderIds,
		];
		$orderDetailCollectionRequest->setFrom($order_ids);
		$response = Proxy::sendRequest('sales.driverOrderCollection',$orderDetailCollectionRequest);
		//得到店铺信息
		try{
			/** @var OrderCollectionResponse $orders */
            $orders = new OrderCollectionResponse();
			$orders->parseFromString($response->getPackageBody());
			$orders = $orders->getItems();
		}catch(\Exception $e){
			$orders = null;
		}
		return $orders;

	}


    /**
     * getContractorHomeDataByProxy
     * Author Jason Y. wang
     *
     * @param LeContractor $contractor
     * @return \framework\message\Message|ContractorStatics
     */
	public static function getContractorHomeDataByProxy($contractor)
	{
		$request = new ContractorHomeDataRequest();
        $city = $contractor->city;
        $city_list = $contractor->city_list;
        $role = $contractor->role;

		$data = [
			'contractor_id' => $contractor->entity_id,
            'city' => $city,
            'role' => $role,
            'city_list' => array_filter(explode('|',$city_list)),
		];
		$request->setFrom(Tools::pb_array_filter($data));
		$responseData = Proxy::sendRequest('sales.contractorHomeData',$request);
		//得到店铺信息
		try{
            $response = new ContractorStatics();
			$response->parseFromString($responseData->getPackageBody());
		}catch(\Exception $e){
			$response = null;
		}
		return $response;
	}

    /**
     * @param LeContractor $contractor
     * Author Jason Y. wang
     *
     * @return array
     */
    public static function getContractorOrderTrackingByProxy($contractor)
    {
        $request = new OrderTrackingRequest();

        $data = [
            'contractor_id' => $contractor->entity_id,
            'auth_token' => $contractor->auth_token,
            'role' => $contractor->role,
            'city_list' => array_filter(explode('|',$contractor->city_list)),
        ];
        $request->setFrom(Tools::pb_array_filter($data));
        $responseData = Proxy::sendRequest('sales.contractorOrderTracking',$request);
        //得到店铺信息
        try{
            $response = new OrderTrackingResponse();
            $response->parseFromString($responseData->getPackageBody());
            $response = $response->toArray();
        }catch(\Exception $e){
            $response = null;
        }
        return $response;
    }


    /**
     * @param $customer_id
     * Author Jason Y. wang
     *
     * @return null|\ProtocolBuffers\Message|StoreRecentlyOrderResponse
     */
    public static function getCustomerOrderDataByProxy($customer_id)
    {
        if(!$customer_id){
            return null;
        }
        $request = new StoreRecentlyOrderRequest();
        $request->setCustomerId($customer_id);
        $message = Proxy::sendRequest('sales.customerOrderData',$request);
        //得到店铺信息
        try{
            /* @var StoreRecentlyOrderResponse $response */

            $response = new StoreRecentlyOrderResponse();
            $response->parseFromString($message->getPackageBody());
        }catch(\Exception $e){
            $response = null;
            ToolsAbstract::logException($e);
        }catch (\Error $e){
            ToolsAbstract::logError($e);
        }

        return $response;
    }

    /**
     * 获取最近N天下了M1至M2单的店铺列表
     *
     * @param StoresListRequest $req
     * @return null|StoresResponse
     */
    public static function getRecentlyOrderStoreDataByProxy(StoresListRequest $req)
    {
        if (!$req) {
            return null;
        }

        $proxyReq = new RecentlyOrderStoresRequest();
        $proxyReq->setContractorId($req->getContractorId());
        $proxyReq->setAuthToken($req->getAuthToken());
        $proxyReq->setCity($req->getCity());
        $proxyReq->setDay($req->getOrderDay());
        $proxyReq->setMin($req->getOrderStart());
        $proxyReq->setMax($req->getOrderEnd());

        $result = Proxy::sendRequest('sales.getRecentlyOrderStores', $proxyReq);
        try {
            /** @var StoresResponse $response */
            $response = new StoresResponse();
            $response->parseFromString($result->getPackageBody());
        } catch (\Exception $e) {
            $response = null;
        }

        return $response;
    }

	/**
	 * Function: getProductsByProxy
	 * Author: Jason Y. Wang
	 * 得到单个商品信息
	 * @param $city
	 * @param $product_ids
	 * @return Product
	 * @throws CustomerException
	 */
	public static function getProductsByProxy($city,$product_ids){
		//请求商品信息
		$productRequest = new getProductBriefRequest();

		$request = [
			'city' => $city,
			'product_ids' => $product_ids,
		];
		$productRequest->setFrom(array_filter($request));
		//得到商品信息
		try{
			$productResult = Proxy::sendRequest('merchant.getProductBrief',$productRequest);
            $productResponse = new getProductBriefResponse();
			$productResponse->parseFromString($productResult->getPackageBody());
			$productListResponse = $productResponse->getProductList();
		}catch(\Exception $e){
			//只返回包头，未返回包体的情况
			//可能是无商品或其它异常
			$productListResponse = null;
		}
//		/** @var getProductBriefResponse $productResponse */
//		$productResponse = getProductBriefResponse::parseFromString($productResult->getPackageBody());
//		$productListResponse = $productResponse->getProductList();
		return $productListResponse;
	}


    //获取用户首单
    public static function getCustomerFirstOrderByProxy($customer_id,$start_date = null,$end_date = null,$contractor_id = null,$city = null){

        $request = new GetCustomerFirstOrderRequest();
        $request->setCustomerId($customer_id);
        if($start_date){
            $request->setStartDate($start_date);
        }
        if($end_date){
            $request->setEndDate($end_date);
        }
        if($contractor_id){
            $request->setContractorId($contractor_id);
        }
        if($city){
            $request->setCity($city);
        }

        $message = Proxy::sendRequest('sales.customerFirstOrder',$request);

        $response = new Order();
        $response->parseFromString($message->getPackageBody());

        return $response;
    }

    /**
     * @param int $city
     * @param LeContractor $contractor
     * @return  ContractorMerchantResponse
     */
	public static function getCityMerchants($city, $contractor)
    {
        $request = new ContractorMerchantRequest();
        $request->setCity($city);
        $request->setContractorId($contractor->entity_id);
        $request->setAuthToken($contractor->auth_token);

        try {
            $result = Proxy::sendRequest('merchant.getMerchantByContractor', $request);
            /** @var ContractorMerchantResponse $response */
            $response = new ContractorMerchantResponse();
            $response->parseFromString($result->getPackageBody());
        } catch (\Exception $e) {
            $response = null;
        }

        return $response;
    }

	public static function formatFormData(&$data){
        if(count($data)){
            foreach($data as $key => $value){
                if($value === null){
                    unset($data[$key]);
                }
            }
        }
    }

    public static function getRandomString($len, $chars = null)
    {
        if (is_null($chars)) {
            $chars = self::CHARS_LOWERS . self::CHARS_UPPERS . self::CHARS_DIGITS;
        }
        for ($i = 0, $str = '', $lc = strlen($chars)-1; $i < $len; $i++) {
            $str .= $chars[mt_rand(0, $lc)];
        }
        return $str;
    }

}