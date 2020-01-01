<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\Backend\Test\Unit\Controller\Adminhtml\Cache;

use PHPUnit_Framework_MockObject_MockObject as MockObject;
use Magento\Backend\Controller\Adminhtml\Cache\MassDisable;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;
use Magento\Framework\App\State;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Message\ManagerInterface as MessageManager;
use Magento\Framework\Controller\ResultFactory;
use Magento\Backend\Model\View\Result\Redirect;
use Magento\Framework\App\RequestInterface as Request;
use Magento\Framework\App\Cache\TypeListInterface as CacheTypeList;
use Magento\Framework\App\Cache\StateInterface as CacheState;
use Magento\Framework\App\Cache\Manager as CacheManager;
use Magento\Framework\App\Cache\Type\FrontendPool;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class MassDisableTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var MassDisable
     */
    private $controller;

    /**
     * @var State|MockObject
     */
    private $stateMock;

    /**
     * @var MessageManager|MockObject
     */
    private $messageManagerMock;

    /**
     * @var Redirect|MockObject
     */
    private $redirectMock;

    /**
     * @var Request|MockObject
     */
    private $requestMock;

    /**
     * @var CacheTypeList|MockObject
     */
    private $cacheTypeListMock;

    /**
     * @var CacheManager
     */
    private $cacheManagerMock;

    protected function setUp()
    {
        $objectManagerHelper = new ObjectManagerHelper($this);

        $this->stateMock = $this->getMockBuilder(State::class)
            ->disableOriginalConstructor()
            ->disableOriginalClone()
            ->getMock();

        $this->messageManagerMock = $this->getMockBuilder(MessageManager::class)
            ->getMockForAbstractClass();

        $this->requestMock = $this->getMockBuilder(Request::class)
            ->getMockForAbstractClass();

        $this->cacheTypeListMock = $this->getMockBuilder(CacheTypeList::class)
            ->getMockForAbstractClass();

        $this->redirectMock = $this->getMockBuilder(Redirect::class)
            ->disableOriginalConstructor()
            ->disableOriginalClone()
            ->getMock();
        $this->redirectMock->expects($this->once())
            ->method('setPath')
            ->with('adminhtml/*')
            ->willReturnSelf();

        $this->cacheManagerMock = $this->getMockBuilder(CacheManager::class)
            ->disableOriginalConstructor()
            ->disableOriginalClone()
            ->getMock();

        $resultFactoryMock = $this->getMockBuilder(ResultFactory::class)
            ->disableOriginalConstructor()
            ->disableOriginalClone()
            ->getMock();
        $resultFactoryMock->expects($this->once())
            ->method('create')
            ->with(ResultFactory::TYPE_REDIRECT)
            ->willReturn($this->redirectMock);

        $contextMock = $this->getMockBuilder(Context::class)
            ->disableOriginalConstructor()
            ->disableOriginalClone()
            ->getMock();
        $contextMock->expects($this->once())
            ->method('getMessageManager')
            ->willReturn($this->messageManagerMock);
        $contextMock->expects($this->once())
            ->method('getResultFactory')
            ->willReturn($resultFactoryMock);
        $contextMock->expects($this->once())
            ->method('getRequest')
            ->willReturn($this->requestMock);

        $cacheStateMock = $this->getMockBuilder(CacheState::class)
            ->getMockForAbstractClass();

        $this->controller = $objectManagerHelper->getObject(
            MassDisable::class,
            [
                'context' => $contextMock,
                'cacheTypeList' => $this->cacheTypeListMock,
                'cacheState' => $cacheStateMock,
                'cacheManager' => $this->cacheManagerMock
            ]
        );
        $objectManagerHelper->setBackwardCompatibleProperty($this->controller, 'state', $this->stateMock);
    }

    public function testExecuteInProductionMode()
    {
        $this->stateMock->expects($this->once())
            ->method('getMode')
            ->willReturn(State::MODE_PRODUCTION);

        $this->messageManagerMock->expects($this->once())
            ->method('addErrorMessage')
            ->with('You can\'t change status of cache type(s) in production mode', null)
            ->willReturnSelf();

        $this->assertSame($this->redirectMock, $this->controller->execute());
    }

    public function testExecuteInvalidTypeCache()
    {
        $this->stateMock->expects($this->once())
            ->method('getMode')
            ->willReturn(State::MODE_DEVELOPER);

        $this->cacheTypeListMock->expects($this->once())
            ->method('getTypes')
            ->willReturn([
                'pageCache' => [
                    'id' => 'pageCache',
                    'label' => 'Cache of Page'
                ]
            ]);

        $this->requestMock->expects($this->once())
            ->method('getParam')
            ->with('types')
            ->willReturn(['someCache']);

        $this->messageManagerMock->expects($this->once())
            ->method('addErrorMessage')
            ->with('These cache type(s) don\'t exist: someCache')
            ->willReturnSelf();

        $this->assertSame($this->redirectMock, $this->controller->execute());
    }

    public function testExecuteWithException()
    {
        $exception = new \Exception();

        $this->stateMock->expects($this->once())
            ->method('getMode')
            ->willReturn(State::MODE_DEVELOPER);

        $this->requestMock->expects($this->once())
            ->method('getParam')
            ->willThrowException($exception);

        $this->messageManagerMock->expects($this->once())
            ->method('addExceptionMessage')
            ->with($exception, 'An error occurred while disabling cache.')
            ->willReturnSelf();

        $this->assertSame($this->redirectMock, $this->controller->execute());
    }

    public function testExecuteSuccess()
    {
        $cacheType = 'pageCache';

        $this->stateMock->expects($this->once())
            ->method('getMode')
            ->willReturn(State::MODE_DEVELOPER);

        $this->cacheTypeListMock->expects($this->once())
            ->method('getTypes')
            ->willReturn([
                'pageCache' => [
                    'id' => 'pageCache',
                    'label' => 'Cache of Page'
                ]
            ]);

        $this->requestMock->expects($this->once())
            ->method('getParam')
            ->with('types')
            ->willReturn([$cacheType]);

        $this->cacheManagerMock->expects($this->once())
            ->method('setEnabled')
            ->with([$cacheType], false)
            ->willReturn([$cacheType]);

        $this->messageManagerMock->expects($this->once())
            ->method('addSuccessMessage')
            ->with('1 cache type(s) disabled.')
            ->willReturnSelf();

        $this->assertSame($this->redirectMock, $this->controller->execute());
    }
}
