<?php
namespace tests\service\resources\route\v1;

use framework\components\ProxyAbstract;
use framework\message\Message;

use tests\service\ApplicationTest;
use service\message\common\Service;
use service\message\common\SourceEnum;
use service\resources\Exception;
use service\resources\route\v1\fetch;

/**
 * Created by PhpStorm.
 * User: henryzhu
 * Date: 16-10-27
 * Time: 下午3:49
 * Email: henryzxj1989@gmail.com
 */

/**
 * Class fetchTest
 * @package tests\service\resources\route\v1
 * @coversDefaultClass service\resources\route\v1\fetch
 */
class fetchTest extends ApplicationTest
{
    public function getModel()
    {
        return new fetch();
    }

    public function testGetFetch()
    {
        $this->assertInstanceOf('service\resources\route\v1\fetch', $this->model);
    }

    /**
     * @covers service\resources\route\v1\fetch::request
     */
    public function testGetFetchRouteRequest()
    {
        $this->assertInstanceOf('service\message\core\FetchRouteRequest', fetch::request());
    }

    /**
     * @covers service\resources\route\v1\fetch::response
     */
    public function testGetFetchRouteResponse()
    {
        $this->assertInstanceOf('service\message\core\FetchRouteResponse', fetch::response());
    }

    public function testGetHeader()
    {
        $this->assertInstanceOf('service\message\common\Header', $this->header);
    }

    public function testGetRequest()
    {
        $this->assertInstanceOf('framework\Request', $this->request);
    }

    /**
     * @covers service\resources\route\v1\fetch::run
     */
    public function testRunInvalidRouteFetchToken()
    {
        $this->request->setRemote(true);
        $request = fetch::request();
        $request->setAuthToken(ProxyAbstract::ROUTE_FETCH_TOKEN . '2');
        $this->header->setSource(SourceEnum::CORE);
        $this->header->setAppVersion(2.3);
        $this->header->setEncrypt('des');
        $this->header->setVersion(1);
        $this->header->setRoute('route.fetch');
        $rawBody = Message::pack($this->header, $request);
        $this->request->setRawBody($rawBody);
        $response = $this->application->handleRequest($this->request);
        $this->assertNotEmpty($response);
        /** @var \ProtocolBuffers\Message $data */
        /** @var \service\message\common\ResponseHeader $header */
        list($header, $data) = $response;
        $this->assertEquals(Exception::INVALID_AUTH_TOKEN, $header->getCode());
        $this->assertEquals(false, $data);
    }

    /**
     * @covers service\resources\route\v1\fetch::run
     */
    public function testRun()
    {
        $this->request->setRemote(true);
        $request = fetch::request();
        $request->setAuthToken(ProxyAbstract::ROUTE_FETCH_TOKEN);
        $this->header->setSource(SourceEnum::CORE);
        $this->header->setAppVersion(2.3);
        $this->header->setEncrypt('des');
        $this->header->setVersion(1);
        $this->header->setRoute('route.fetch');
        $rawBody = Message::pack($this->header, $request);
        $this->request->setRawBody($rawBody);
        $response = $this->application->handleRequest($this->request);
        $this->assertNotEmpty($response);
        /** @var \ProtocolBuffers\Message $data */
        /** @var \service\message\common\ResponseHeader $header */
        list($header, $data) = $response;
        $this->assertEquals(0, $header->getCode());
        $this->assertInstanceOf('service\message\core\FetchRouteResponse', $data);
        $this->assertNotEmpty($data->getServices());
        foreach ($data->getServices() as $service) {
            /** @var Service $service */
            $this->assertInstanceOf('service\message\common\Service', $service);
            $this->assertAttributeNotEmpty('module', $service);
            $this->assertAttributeNotEmpty('ip', $service);
            $this->assertAttributeNotEmpty('port', $service);
        }
    }
}