<?php
/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

namespace Magento\Backend\Test\Unit\Controller\Adminhtml\Cache;

use Magento\Backend\Controller\Adminhtml\Cache\FlushAll;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;
use PHPUnit\Framework\TestCase;
use Magento\Framework\Event\ManagerInterface as EventManagerInterface;
use Magento\Backend\App\Action\Context;
use Magento\Framework\Message\ManagerInterface as MessageManagerInterface;
use Magento\Backend\Model\View\Result\Redirect;
use Magento\Framework\Controller\Result\RedirectFactory as ResultRedirectFactory;
use Magento\Framework\App\Cache\Manager as CacheManager;

class FlushAllTest extends TestCase
{
    /** @var FlushAll */
    private $controller;

    /** @var EventManagerInterface */
    private $eventManagerMock;

    /** @var MessageManagerInterface */
    private $messageManagerMock;

    /** @var Redirect */
    private $redirectMock;

    /** @var CacheManager */
    protected $cacheManagerMock;

    public function setUp()
    {
        $objectManagerHelper = new ObjectManagerHelper($this);

        $this->eventManagerMock = $this->getMockBuilder(EventManagerInterface::class)
            ->getMockForAbstractClass();

        $this->messageManagerMock = $this->getMockBuilder(MessageManagerInterface::class)
            ->getMockForAbstractClass();

        $this->redirectMock = $this->getMockBuilder(Redirect::class)
            ->disableOriginalConstructor()
            ->disableOriginalClone()
            ->getMock();

        $this->redirectMock->expects($this->once())
            ->method('setPath')
            ->with('adminhtml/*')
            ->willReturnSelf();

        $resultRedirectFactory = $this->getMockBuilder(ResultRedirectFactory::class)
            ->disableOriginalConstructor()
            ->disableOriginalClone()
            ->getMock();

        $resultRedirectFactory->expects($this->once())
            ->method('create')
            ->willReturn($this->redirectMock);

        $contextMock = $this->getMockBuilder(Context::class)
            ->disableOriginalConstructor()
            ->disableOriginalClone()
            ->getMock();

        $contextMock->method('getEventManager')
            ->willReturn($this->eventManagerMock);

        $contextMock->method('getMessageManager')
            ->willReturn($this->messageManagerMock);

        $contextMock->method('getResultRedirectFactory')
            ->willReturn($resultRedirectFactory);

        $this->cacheManagerMock = $this->getMockBuilder(CacheManager::class)
            ->disableOriginalConstructor()
            ->disableOriginalClone()
            ->getMock();

        $this->controller = $objectManagerHelper->getObject(FlushAll::class, [
            'context' => $contextMock,
            'cacheManager' => $this->cacheManagerMock
        ]);
    }

    public function testExecute()
    {
        $this->eventManagerMock->expects($this->once())
            ->method('dispatch')
            ->with('adminhtml_cache_flush_all');

        $this->messageManagerMock->expects($this->once())
            ->method('addSuccessMessage')
            ->with('You flushed the cache storage.');

        $this->cacheManagerMock->expects($this->once())
            ->method('getAvailableTypes')
            ->willReturn(['config', 'pageCache']);

        $this->cacheManagerMock->expects($this->once())
            ->method('flush')
            ->with(['config', 'pageCache']);

        $this->assertSame($this->redirectMock, $this->controller->execute());
    }
}
