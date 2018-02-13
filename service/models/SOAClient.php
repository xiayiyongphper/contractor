<?php

namespace service\models;

use framework\components\TStringFuncFactory;
use framework\message\Message;
use service\message\common\Header;
use service\message\common\Image;
use service\message\common\PlanGroup;
use service\message\common\SourceEnum;
use service\message\contractor\addVisitRecordBriefRequest;
use service\message\contractor\addVisitRecordRequest;
use service\message\contractor\ChangeVisitPlanRequest;
use service\message\contractor\CityPlanGroupRequest;
use service\message\contractor\CityPlanGroupResponse;
use service\message\contractor\MarkPriceOptionsRequest;
use service\message\contractor\MarkPriceRequest;
use service\message\contractor\PlanGroupEditRequest;
use service\message\contractor\PlanGroupListRequest;
use service\message\contractor\searchStoresRequest;
use service\message\contractor\StoresListRequest;
use service\message\contractor\StoreListFilterRequest;
use service\message\contractor\TargetCenterRequest;
use service\message\contractor\TargetItem;
use service\message\contractor\TargetListRequest;
use service\message\contractor\UpdateTargetCurrentValueRequest;
use service\message\contractor\visitedRecordDetailRequest;
use service\message\contractor\visitedRecordsRequest;
use service\message\contractor\visitedRecordsRequestNew;
use service\message\contractor\getVisitFilterItemsRequest;
use service\message\contractor\ContractorAuthenticationRequest;
use service\message\contractor\SaveStoreRequest;
use service\message\contractor\EditStoreInfoRequest;
use service\message\contractor\contractorCityListRequest;
use service\message\contractor\SetStoreValidRequest;
use service\message\contractor\GetStoreInfoRequest;
use service\message\contractor\VisitPlanRequest;
use service\models\client\ClientAbstract;
use service\message\contractor\workManageRequest;
use service\message\contractor\GetWholeTargetRequest;
use service\message\contractor\GetContractorTargetRequest;
use service\message\contractor\IsTargetSetRequest;
use service\message\contractor\SetCityTargetRequest;
use service\message\contractor\SetContractorTargetRequest;
use service\message\contractor\MarkPriceProductListRequest;
use service\message\contractor\WholesalerListRequest;

/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2016/1/8
 * Time: 12:01
 */
class SOAClient extends ClientAbstract
{
    protected $_responseClass;
    protected $_contractorId = 17;
    protected $_customerId = 35;
    protected $_authToken = 'cpXV5Qgv13TuAqyu';

    //protected $_authToken = 'gm8USpoGSGIgRjvg';

    public function systemMessage()
    {
        swoole_timer_tick(1, function () {
            $data = [
                'class' => 'service',
                'method' => 'name',
                'time' => time(),
            ];
            $this->send(Message::packJson($data));
        });
    }

    public function onReceive($client, $data)
    {
        $message = new Message();
        $message->unpackResponse($data);
        $responseClass = $this->_responseClass;
        if ($message->getHeader()->getCode() > 0) {
            echo sprintf('程序执行异常：%s', $message->getHeader()->getMsg()) . PHP_EOL;
        } else {
            if (TStringFuncFactory::create()->strlen($message->getPackageBody()) > 0) {
                $response = new $responseClass();
                $response->parseFromString($message->getPackageBody());
                echo PHP_EOL;
                print_r($response->toArray());
            } else {
                print_r('返回值为空');
            }
        }
    }

    public function home()
    {
        $this->_responseClass = 'service\message\contractor\HomeResponse';
        $request = new ContractorAuthenticationRequest();
        $request->setContractorId($this->_contractorId);
        $request->setAuthToken($this->_authToken);
        $header = new Header();
        $header->setSource(SourceEnum::CUSTOMER);
        $header->setVersion(1);
        $header->setRoute('contractor.home');
        $this->send(Message::pack($header, $request));
    }

    public function home2()
    {
        $this->_responseClass = 'service\message\contractor\HomeResponse2';
        $request = new ContractorAuthenticationRequest();
        $request->setContractorId(16);
        $request->setAuthToken('kouAkLZ1MEAHlfFz');
        $header = new Header();
        $header->setSource(SourceEnum::CUSTOMER);
        $header->setVersion(1);
        $header->setRoute('contractor.home2');
        $this->send(Message::pack($header, $request));
    }

    public function visitedRecords()
    {
        $this->_responseClass = 'service\message\contractor\visitedRecordsResponse';
        $request = new visitedRecordsRequestNew();
        $request->setContractorId(18);
        $request->setAuthToken('rNzpuqqYLWOEXqAx');
        //$request->setCustomerId(1088);
        $request->setCity(441800);
//        $request->setVisitDateStart("2016-08-26");
//        $request->setVisitDateEnd("2016-08-26");
        $request->setRole(1);

//        $request->setVisitTimeStart(0);
//        $request->setVisitTimeEnd(10);
//        $request->setArrivalDistanceStart(0);
//        $request->setArrivalDistanceEnd(20);
//        $request->setLeaveDistanceStart(0);
//        $request->setLeaveDistanceEnd(20);
//
//        //$request->appendVisitPurpose('');
//        //$request->appendVisitPurpose('超市注册');
//        $request->appendVisitPurpose('');
//        //$request->appendVisitPurpose('督促超市下单');
//        //$request->appendVisitPurpose('调研回访');
//        //$request->appendVisitWay("微信拜访");
//        $request->appendVisitWay("");
//        //$request->appendVisitWay("电话拜访");
//        //$request->appendChosenContractorId(0);
//        $request->appendChosenContractorId(17);
//        $request->appendChosenContractorId(18);
        $header = new Header();
        $header->setSource(SourceEnum::CUSTOMER);
        $header->setVersion(1);
        $header->setRoute('contractor.visitedRecordsNew');
        $this->send(Message::pack($header, $request));
    }

    public function addVisitRecord()
    {
        $this->_responseClass = 'service\message\contractor\VisitRecord';
        $request = new addVisitRecordRequest();
        $request->setContractorId($this->_contractorId);
        $request->setAuthToken($this->_authToken);
        $header = new Header();
        $header->setSource(SourceEnum::CUSTOMER);
        $header->setVersion(1);
        $header->setRoute('contractor.addVisitRecord');
        $this->send(Message::pack($header, $request));
    }

    public function addVisitRecord2()
    {
        $this->_responseClass = 'service\message\contractor\VisitRecord';
        $request = new addVisitRecordRequest();
        $request->setContractorId(32);
        $request->setAuthToken('dA64DHAvjeRFOzrV');
        $request->setAction(2);
        $request->setFrom([
            'record' => [
                'record_id' => 187,
                'customer_id' => 35,
                'is_intended' => 0,
                'visit_purpose' => '123',
                'visit_way' => '上门拜访',
                'visit_content' => '开发测试123',
                'feedback' => '是的这是个自己测试',
                'locate_address' => '玩得来大厦南座',
                'lat' => '2',
                'lng' => '2',
                'see_boss' => 1,
            ]
        ]);
        $header = new Header();
        $header->setSource(SourceEnum::CUSTOMER);
        $header->setVersion(1);
        $header->setRoute('contractor.addVisitRecord2');
        $this->send(Message::pack($header, $request));
    }

    public function addVisitRecordBrief2()
    {
        $this->_responseClass = 'service\message\contractor\VisitRecord';
        $request = new addVisitRecordBriefRequest();
        $request->setContractorId(32);
        $request->setAuthToken('dA64DHAvjeRFOzrV');
        $request->setRole(0);
        $request->setFrom([
            'record' => [
                'customer_id' => 35,
                'is_intended' => 0,
                'store_name' => '沙县小吃',
                'locate_address' => '玩得来大厦',
                'lat' => '1',
                'lng' => '1',
            ]
        ]);
        $header = new Header();
        $header->setSource(SourceEnum::CUSTOMER);
        $header->setVersion(1);
        $header->setRoute('contractor.addVisitRecordBrief2');
        $this->send(Message::pack($header, $request));
    }

    public function searchStores()
    {
        $this->_responseClass = 'service\message\contractor\StoresResponse';
        $request = new searchStoresRequest();
        $request->setContractorId(32);
        $request->setFilterContractorId(7);
        $request->setDate('2017-12-14');
        $request->setVisitPlan(1);
        $request->setType(3);
        $request->setCity(441800);
        $request->setAuthToken('dA64DHAvjeRFOzrV');
//        $request->setKeyword('测试');
        $header = new Header();
        $header->setSource(SourceEnum::CUSTOMER);
        $header->setVersion(1);
        $header->setRoute('contractor.searchStores');
        $this->send(Message::pack($header, $request));
    }

    public function storeList()
    {
        $this->_responseClass = 'service\message\contractor\StoresResponse';
        $request = new StoresListRequest();
        $request->setContractorId(32);
        $request->setAuthToken('dA64DHAvjeRFOzrV');
        $request->setListType(3);
        $request->setCity(441800);
        $header = new Header();
        $header->setSource(SourceEnum::CUSTOMER);
        $header->setVersion(1);
        $header->setRoute('contractor.storeList');
        $this->send(Message::pack($header, $request));
    }

    public function storeListFilter()
    {
        $this->_responseClass = 'service\message\contractor\StoreListFilterResponse';
        $request = new StoreListFilterRequest();
        $request->setContractorId(32);
        $request->setAuthToken('dA64DHAvjeRFOzrV');
        $request->setCity(441800);
        $header = new Header();
        $header->setSource(SourceEnum::CUSTOMER);
        $header->setVersion(1);
        $header->setRoute('contractor.storeListFilter2');
        $this->send(Message::pack($header, $request));
    }

    public function getVisitFilterItems()
    {
        $this->_responseClass = 'service\message\contractor\getVisitFilterItemsResponse';
        $request = new getVisitFilterItemsRequest();
        $request->setContractorId($this->_contractorId);
        $request->setAuthToken($this->_authToken);
        $request->setCity(441800);
        $header = new Header();
        $header->setSource(SourceEnum::CUSTOMER);
        $header->setVersion(1);
        $header->setRoute('contractor.getVisitFilterItems');
        $this->send(Message::pack($header, $request));
    }

    public function saveStore()
    {
        $request = new SaveStoreRequest();
        $request->setContractorId($this->_contractorId);
        $request->setAuthToken($this->_authToken);
        $request->setStoreId(8);
        $request->setAreaId(6);
        $request->setAddress("广东省深圳市");
        $request->setDetailAddress("深圳市南山区");
        $request->setStoreName("hhh");
        $request->setStorekeeper("hl");
        $request->setPhone("12345678911");
        $request->setLat("22.548515");
        $request->setLng("114.066112");
        $request->setNotPassReason('');
        $request->setStatus(0);
        //$request->appendType();
        $request->setLevel(0);
        //$request->setBusinessLicenseNo();
        $request->setBusinessLicenseImg('');
        $request->setStoreFrontImg('');
        $request->setStorekeeperInstoreTimes('');
        $request->setUsername('test');
        //$request->setImgLat('');
        $request->setImgLng('');
        //$request->setIntentionId();
        //$request->setStoreType('便利店');
        //$request->setStoreAreaNew('30平米以下');
        $header = new Header();
        $header->setSource(SourceEnum::CUSTOMER);
        $header->setVersion(1);
        $header->setRoute('contractor.saveStoreInfo');
        $this->send(Message::pack($header, $request));
    }

    public function editStoreInfo()
    {
        $this->_responseClass = 'service\message\contractor\EditStoreInfoResponse';
        $request = new EditStoreInfoRequest();
        $request->setContractorId($this->_contractorId);
        $request->setAuthToken($this->_authToken);
        $header = new Header();
        $header->setSource(SourceEnum::CUSTOMER);
        $header->setVersion(1);
        $header->setRoute('contractor.EditStoreInfo');
        $this->send(Message::pack($header, $request));
    }

    public function contractorCityList()
    {
        $this->_responseClass = 'service\message\contractor\contractorCityListResponse';
        $request = new contractorCityListRequest();
        $request->setContractorId(9);
        $request->setAuthToken('vhdbMEKoRd4C83Wf');
        $header = new Header();
        $header->setSource(SourceEnum::CUSTOMER);
        $header->setVersion(1);
        $header->setRoute('contractor.contractorCityList');
        $this->send(Message::pack($header, $request));
    }

    public function workManage()
    {
        $this->_responseClass = 'service\message\contractor\workManageResponse';
        $request = new workManageRequest();
        $request->setContractorId(32);
        $request->setAuthToken('u6riyHcHM2ch2cGC');
        $request->setCity(441800);

        $header = new Header();
        $header->setSource(SourceEnum::CUSTOMER);
        $header->setVersion(1);
        $header->setRoute('contractor.workManage');
        $this->send(Message::pack($header, $request));
    }

    public function orderManage()
    {
        $this->_responseClass = 'service\message\contractor\ManageResponse';
        $request = new ContractorAuthenticationRequest();
        $request->setContractorId(32);
        $request->setAuthToken('dA64DHAvjeRFOzrV');
        $request->setCity(441800);

        $header = new Header();
        $header->setSource(SourceEnum::CUSTOMER);
        $header->setVersion(1);
        $header->setRoute('contractor.orderManage');
        $this->send(Message::pack($header, $request));
    }

    public function contractorList()
    {
        $this->_responseClass = 'service\message\contractor\getVisitFilterItemsResponse';
        $request = new getVisitFilterItemsRequest();
        $request->setContractorId(32);
        $request->setAuthToken('u6riyHcHM2ch2cGC');
        $request->setCity(441800);

        $header = new Header();
        $header->setSource(SourceEnum::CUSTOMER);
        $header->setVersion(1);
        $header->setRoute('contractor.contractorList');
        $this->send(Message::pack($header, $request));
    }

    public function getMarkPriceOptions()
    {
        $this->_responseClass = 'service\message\contractor\MarkPriceOptionsResponse';
        $request = new MarkPriceOptionsRequest();
        $request->setContractorId(26); // 17
        $request->setAuthToken('2CgyIYxVAlKIcqQi'); // BDEbhnbGNUTbReEG
        $request->setCity(441800);

        $header = new Header();
        $header->setSource(SourceEnum::CUSTOMER);
        $header->setVersion(1);
        $header->setRoute('contractor.GetMarkPriceOptions');
        $this->send(Message::pack($header, $request));
    }

    public function markPrice()
    {
        $request = new MarkPriceRequest();
        $request->setContractorId(26); // 17
        $request->setAuthToken('2CgyIYxVAlKIcqQi'); // BDEbhnbGNUTbReEG
        $request->setPrice(100.5);
        $request->setProductId(24);
        $request->setSource('xxxx');
        $request->setSourceType(1);
        $request->setStoreId(0);
        $request->setStoreName('xxx店铺1');
        $img1 = new Image();
        $img1->setSrc('http://assets.lelai.com/images/catalog/product/600x600/15061610/315052509209.jpg');
        $request->appendGallery($img1);
        $img2 = new Image();
        $img2->setSrc('http://assets.lelai.com/images/catalog/product/600x600/15072815/690142433394_0_1_1.jpg');
        $request->appendGallery($img2);

        $header = new Header();
        $header->setSource(SourceEnum::CUSTOMER);
        $header->setVersion(1);
        $header->setRoute('contractor.markPrice');
        $this->send(Message::pack($header, $request));
    }

    public function targetCenter()
    {
        $this->_responseClass = 'service\message\contractor\TargetCenterResponse';
        $request = new TargetCenterRequest();
        $request->setContractorId(29); // 17
        $request->setAuthToken('GSfw8Ug32pMSe0zv'); // BDEbhnbGNUTbReEG
        $request->setCity(441800);

        $header = new Header();
        $header->setSource(SourceEnum::CUSTOMER);
        $header->setVersion(1);
        $header->setRoute('contractor.TargetCenter');
        $this->send(Message::pack($header, $request));
    }

    public function targetList()
    {
        $this->_responseClass = 'service\message\contractor\HomeResponse2';
        $request = new TargetListRequest();
        $request->setContractorId(29); // 17
        $request->setAuthToken('yVs5WF3emyuED2Jz'); // BDEbhnbGNUTbReEG
        $request->setCity(441800);
        $request->setTargetContractorId(29);
        $request->setDate('2017-07');

        $header = new Header();
        $header->setSource(SourceEnum::CUSTOMER);
        $header->setVersion(1);
        $header->setRoute('contractor.TargetList');
        $this->send(Message::pack($header, $request));
    }

    public function home2_new()
    {
        $this->_responseClass = 'service\message\contractor\HomeResponse2';
        $request = new ContractorAuthenticationRequest();
        $request->setContractorId(29);
        $request->setAuthToken('yVs5WF3emyuED2Jz');
        $request->setCity('441800');
        $header = new Header();
        $header->setSource(SourceEnum::CUSTOMER);
        $header->setVersion(1);
        $header->setRoute('contractor.home2');
        $this->send(Message::pack($header, $request));
    }

    public function updateTargetCurrentValue()
    {
        $request = new UpdateTargetCurrentValueRequest();
        $request->setContractorId(18); // 17
        $request->setAuthToken('wIdDWx51HddrmCgU'); // BDEbhnbGNUTbReEG
        $item = new TargetItem();
        $item->setCity('441800');
        $item->setContractorId(0);
        $item->setMetricId(2);
        $item->setCurrentValue(2000);
        $request->setItemValue($item);

        $header = new Header();
        $header->setSource(SourceEnum::CUSTOMER);
        $header->setVersion(1);
        $header->setRoute('contractor.UpdateTargetCurrentValue');
        $this->send(Message::pack($header, $request));
    }

    public function saveStoreIntention()
    {
        $request = new SaveStoreRequest();
        $request->setContractorId($this->_contractorId);
        $request->setAuthToken($this->_authToken);
        $request->setStoreId(8);
        $request->setAreaId(6);
        $request->setAddress("广东省深圳市");
        $request->setDetailAddress("深圳市南山区");
        $request->setStoreName("hhh");
        $request->setStorekeeper("hl");
        $request->setPhone("12345678911");
        $request->setLat("22.548515");
        $request->setLng("114.066112");
        //$request->setBusinessLicenseNo('');
        $request->setBusinessLicenseImg('');
        $request->setStoreFrontImg('');
        $request->setStoreType('便利店');
        $request->setStoreAreaNew('30平米以下');

        var_dump($request->getBusinessLicenseNo());
        $header = new Header();
        $header->setSource(SourceEnum::CUSTOMER);
        $header->setVersion(1);
        $header->setRoute('contractor.saveStoreIntention');
        $this->send(Message::pack($header, $request));
    }

    public function setStoreValid()
    {
        $request = new SetStoreValidRequest();
        $request->setContractorId($this->_contractorId);
        $request->setAuthToken($this->_authToken);
        $request->setStoreId(8);
        $request->setCustomerStyle(1);
        $request->setDisabled(0);
        $header = new Header();
        $header->setSource(SourceEnum::CUSTOMER);
        $header->setVersion(1);
        $header->setRoute('contractor.setStoreValid');
        $this->send(Message::pack($header, $request));
    }

    public function GetStoreInfo()
    {
        $this->_responseClass = 'service\message\contractor\GetStoreInfoResponse';
        $request = new GetStoreInfoRequest();
        $request->setContractorId($this->_contractorId);
        $request->setAuthToken($this->_authToken);
        $request->setCustomerId(1102);
        $request->setCustomerStyle(0);
        $header = new Header();
        $header->setSource(SourceEnum::CUSTOMER);
        $header->setVersion(1);
        $header->setRoute('contractor.getStoreInfo');
        $this->send(Message::pack($header, $request));
    }

    public function getWholeTarget()
    {
        $this->_responseClass = 'service\message\contractor\GetWholeTargetResponse';
        $request = new GetWholeTargetRequest();
        $request->setContractorId(16);
        $request->setAuthToken('kouAkLZ1MEAHlfFz');
        $request->setMonth(201707);
        $request->setCity(441800);
        $header = new Header();
        $header->setSource(SourceEnum::CUSTOMER);
        $header->setVersion(1);
        $header->setRoute('contractor.getWholeTarget');
        $this->send(Message::pack($header, $request));
    }

    public function getCityTarget()
    {
        $this->_responseClass = 'service\message\contractor\GetCityTargetResponse';
        $request = new GetWholeTargetRequest();
        $request->setContractorId(3);
        $request->setAuthToken('tXNx4WkcOvQVecOY');
        //$request->setMonth(201707);
        $request->setCity(441200);
        $header = new Header();
        $header->setSource(SourceEnum::CUSTOMER);
        $header->setVersion(1);
        $header->setRoute('contractor.getCityTarget');
        $this->send(Message::pack($header, $request));
    }

    public function getContractorTarget()
    {
        $this->_responseClass = 'service\message\contractor\GetContractorTargetResponse';
        $request = new GetContractorTargetRequest();
        $request->setContractorId(16);
        $request->setAuthToken('kouAkLZ1MEAHlfFz');
        //$request->setMonth(201708);
        $request->setChosenContractorId(9);
        //$request->setCity(441800);
        $header = new Header();
        $header->setSource(SourceEnum::CUSTOMER);
        $header->setVersion(1);
        $header->setRoute('contractor.getContractorTarget');
        $this->send(Message::pack($header, $request));
    }

    public function isTargetSet()
    {
        $request = new IsTargetSetRequest();
        $request->setContractorId(16);
        $request->setAuthToken('kouAkLZ1MEAHlfFz');
        $request->setMonth(201706);
        $request->setChosenContractorId(9);
        $request->setCity(441800);
        $request->setType(2);
        $header = new Header();
        $header->setSource(SourceEnum::CUSTOMER);
        $header->setVersion(1);
        $header->setRoute('contractor.isTargetSet');
        $this->send(Message::pack($header, $request));
    }

    public function setCityTarget()
    {
        $request = new SetCityTargetRequest();
        $request->setContractorId(16);
        $request->setAuthToken('kouAkLZ1MEAHlfFz');
        $request->setMonth(201709);
        $request->setCity(441800);
        $task = new TargetItem();
        $task->setMetricId(1);
        $task->setBaseValue(10);
        $task->setTargetValue(20);
        $task->setPerfectValue(30);
        $request->appendCityTarget($task);
        $task = new TargetItem();
        $task->setMetricId(2);
        $task->setBaseValue(11);
        $task->setTargetValue(22);
        $task->setPerfectValue(33);
        $request->appendCityTarget($task);
        $header = new Header();
        $header->setSource(SourceEnum::CUSTOMER);
        $header->setVersion(1);
        $header->setRoute('contractor.setCityTarget');
        $this->send(Message::pack($header, $request));
    }

    public function setContractorTarget()
    {
        $request = new SetContractorTargetRequest();
        $request->setContractorId(16);
        $request->setAuthToken('kouAkLZ1MEAHlfFz');
        $request->setMonth(201709);
        $request->setChosenContractorId(9);
        $task = new TargetItem();
        $task->setMetricId(1);
        $task->setTargetValue(10);
        $request->appendContractorTarget($task);
        $task = new TargetItem();
        $task->setMetricId(2);
        $task->setTargetValue(12);
        $request->appendContractorTarget($task);
        $header = new Header();
        $header->setSource(SourceEnum::CUSTOMER);
        $header->setVersion(1);
        $header->setRoute('contractor.setContractorTarget');
        $this->send(Message::pack($header, $request));
    }

    public function makePriceProductList()
    {
        $this->_responseClass = 'service\message\contractor\MarkPriceProductListResponse';
        $request = new MarkPriceProductListRequest();
        $request->setContractorId(16);
        $request->setAuthToken('kouAkLZ1MEAHlfFz');
        $request->setCity(441800);
        $request->setCheckType(1);
        $header = new Header();
        $header->setSource(SourceEnum::CUSTOMER);
        $header->setVersion(1);
        $header->setRoute('contractor.markPriceProductList2');
        $this->send(Message::pack($header, $request));
    }


    public function storeList2()
    {
        $this->_responseClass = 'service\message\contractor\StoresResponse';
        $request = new StoresListRequest();
        $request->setContractorId(17);
        $request->setAuthToken('I3tqU736fgxySeAD');
//        $request->setListType(1);
        $request->setCity(441800);
        $request->setGrouped(1);
        $request->setFilterContractorId(17);
        $request->setListType(1);
        $header = new Header();
        $header->setSource(SourceEnum::CUSTOMER);
        $header->setVersion(1);
        $header->setRoute('contractor.storeList2');
        $this->send(Message::pack($header, $request));
    }

    // 获取供应商列表
    public function wholesalerList()
    {
        $this->_responseClass = 'service\message\contractor\WholesalerListResponse';
        $request = new WholesalerListRequest();
        $request->setContractorId(32);
        $request->setAuthToken('dA64DHAvjeRFOzrV');
        $request->setCity(441800);
        $request->setPage(1);
        $request->setPageSize(10);
        $header = new Header();
        $header->setSource(SourceEnum::CONTRACTOR);
        $header->setVersion(1);
        $header->setRoute('contractor.wholesalerList');
        $this->send(Message::pack($header, $request));
    }

    // 超市路线规划列表
    public function planGroupList()
    {
        $this->_responseClass = 'service\message\contractor\PlanGroupListResponse';
        $request = new PlanGroupListRequest();
        $request->setContractorId(17);
        $request->setAuthToken('cpXV5Qgv13TuAqyu');
        $request->setCity(441800);
        $request->setFilterContractorId(0);
        $header = new Header();
        $header->setSource(SourceEnum::CONTRACTOR);
        $header->setVersion(1);
        $header->setRoute('contractor.planGroupList');
        $this->send(Message::pack($header, $request));
    }

    // 超市路线规划新增编辑 移动 修改
    public function planGroupEdit()
    {
        $this->_responseClass = 'service\message\contractor\PlanGroupEditResponse';
        $request = new PlanGroupEditRequest();
        $request->setContractorId(17);
        $request->setAuthToken('I3tqU736fgxySeAD');
        $request->setCity(441800);
        $request->setFrom([
            'plan_group' => [
                'group_id'=>2,
                'name' => '中国',
            ],
            'store_id' => [1],
//            'del_group' => 1,
        ]);
        $header = new Header();
        $header->setSource(SourceEnum::CONTRACTOR);
        $header->setVersion(1);
        $header->setRoute('contractor.planGroupEdit');
        $this->send(Message::pack($header, $request));
    }

    // 城市所有路线规划
    public function cityPlanGroup()
    {
        $this->_responseClass = 'service\message\contractor\CityPlanGroupResponse';
        $request = new CityPlanGroupRequest();
        $request->setContractorId(32);
        $request->setAuthToken('dA64DHAvjeRFOzrV');
        $request->setCity(441800);
        $header = new Header();
        $header->setSource(SourceEnum::CONTRACTOR);
        $header->setVersion(1);
        $header->setRoute('contractor.cityPlanGroup');
        $this->send(Message::pack($header, $request));
    }

    // 拜访计划首页
    public function visitPlanList()
    {
        $this->_responseClass = 'service\message\contractor\VisitPlanResponse';
        $request = new VisitPlanRequest();
        $request->setContractorId(16);
        $request->setAuthToken('ibTgx2rcL4HPveTN');
        $request->setCity(441800);
        $request->setFilterContractorId(17);
        $request->setDate('2017-12-02');
        $header = new Header();
        $header->setSource(SourceEnum::CONTRACTOR);
        $header->setVersion(1);
        $header->setRoute('contractor.visitPlanList');
        $this->send(Message::pack($header, $request));
    }

    // 新增或者删除拜访计划的超市
    public function changeVisitPlan()
    {
        $this->_responseClass = 'service\message\contractor\ChangeVisitPlanResponse';
        $request = new ChangeVisitPlanRequest();
        $request->setContractorId(17);
        $request->setAuthToken('cpXV5Qgv13TuAqyu');
        $request->setDate('2017-11-28');
        $request->setFrom([
            'customer_id' => [
                42
            ],
        ]);
        $request->setRemark('新增');
        $header = new Header();
        $header->setSource(SourceEnum::CONTRACTOR);
        $header->setVersion(1);
        $header->setRoute('contractor.changeVisitPlan');
        $this->send(Message::pack($header, $request));
    }

    // 拜访记录详情
    public function visitedRecordDetail()
    {
        $this->_responseClass = 'service\message\contractor\visitedRecordDetailResponse';
        $request = new visitedRecordDetailRequest();
        $request->setContractorId(154);
        $request->setAuthToken('dkauIOkVJxqYbThl');
        $request->setRecordId(6);
        $request->setRole(1);
        $header = new Header();
        $header->setSource(SourceEnum::CONTRACTOR);
        $header->setVersion(1);
        $header->setRoute('contractor.visitedRecordDetail');
        $this->send(Message::pack($header, $request));
    }


    public function onConnect($client)
    {
        echo "client connected" . PHP_EOL;
        $this->searchStores();
//        $this->changeVisitPlan();
//        $this->visitPlanList();
//        $this->cityPlanGroup();
//          $this->visitedRecords();
//        $this->planGroupEdit();
//        $this->planGroupList();
//        $this->addVisitRecordBrief2();
//        $this->addVisitRecord2();
//        $this->wholesalerList();
//        $this->storeList2();
//        $this->createOrders();
//        $this->home();
//        $this->config();
        //$this->orderDetail();
//        $this->orderStatusHistory();
//        $this->orderCancel();
//        $this->revokeCancel();
//        $this->decline();
//        $this->reorder();
//        $this->systemMessage();
//        $this->visitedRecordDetail();
//        $this->visitedRecords();
//        $this->visitedRecords();
        //$this->getVisitFilterItems();
        //$this->home();
//        $this->targetCenter();
//        $this->targetList();
//        $this->home2_new();
//        $this->updateTargetCurrentValue();
//        $this->getMarkPriceOptions();
        //$this->markPrice();
        //$this->saveStore();
        //$this->saveStoreIntention();
        //$this->editStoreInfo();
        //$this->contractorCityList();
//        $this->storeList();
//        $this->GetStoreInfo();
//        $this->orderManage();
//        $this->contractorList();
        //$this->setStoreValid();
        //$this->getWholeTarget();
        //$this->getCityTarget();
        //$this->getContractorTarget();
        //$this->isTargetSet();
        //$this->setCityTarget();
        //$this->setContractorTarget();
//        $this->setContractorTarget();
        //$this->makePriceProductList();
//        $this->storeListFilter();
    }

}
