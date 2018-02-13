<?php
namespace tests\service;

use framework\Application;
use framework\Request;
use framework\resources\ApiAbstract;
use service\message\common\Header;
use service\resources\ResourceAbstract;
use tests\AbstractTest;

/**
 * Created by PhpStorm.
 * User: henryzhu
 * Date: 16-10-27
 * Time: 下午6:08
 * Email: henryzxj1989@gmail.com
 */
abstract class ApplicationTest extends AbstractTest
{
    /**
     * @var ResourceAbstract
     */
    protected $model;

    /**
     * @var Header
     */
    protected $header;

    /**
     * @var Request
     */
    protected $request;

    /**
     * @var Application
     */
    protected $application;

    /**
     * @return ApiAbstract
     */
    abstract protected function getModel();

    /**
     * Set up
     */
    public function setUp()
    {
        parent::setUp();
        $this->model = $this->getModel();
        $this->header = new Header();
        $this->request = new Request();
        $this->application = new Application($this->config);
    }

    public function tearDown()
    {
        parent::tearDown();
        $this->model = null;
        $this->header = null;
        $this->request = null;
    }
}