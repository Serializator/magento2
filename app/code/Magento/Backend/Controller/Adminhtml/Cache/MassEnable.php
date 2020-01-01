<?php
/**
 *
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\Backend\Controller\Adminhtml\Cache;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\App\State;
use Magento\Framework\App\ObjectManager;

/**
 * Controller enables some types of cache
 */
class MassEnable extends \Magento\Backend\Controller\Adminhtml\Cache
{
    /**
     * Authorization level of a basic admin session
     *
     * @see _isAllowed()
     */
    const ADMIN_RESOURCE = 'Magento_Backend::toggling_cache_type';

    /**
     * @var State
     */
    private $state;

    /**
     * Mass action for cache enabling
     *
     * @return \Magento\Backend\Model\View\Result\Redirect
     */
    public function execute()
    {
        if ($this->getState()->getMode() === State::MODE_PRODUCTION) {
            $this->messageManager->addErrorMessage(__('You can\'t change status of cache type(s) in production mode'));
        } else {
            $this->enableCache();
        }

        return $this->resultFactory->create(ResultFactory::TYPE_REDIRECT)->setPath('adminhtml/*');
    }

    /**
     * Enable cache
     *
     * @return void
     */
    private function enableCache()
    {
        try {
            $types = $this->getRequest()->getParam('types');

            if (!is_array($types)) {
                $types = [];
            }
            $this->_validateTypes($types);

            $updatedTypes = count($this->cacheManager->setEnabled($types, true));

            if ($updatedTypes > 0) {
                $this->messageManager->addSuccessMessage(__("%1 cache type(s) enabled.", $updatedTypes));
            }
        } catch (LocalizedException $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
        } catch (\Exception $e) {
            $this->messageManager->addExceptionMessage($e, __('An error occurred while enabling cache.'));
        }
    }

    /**
     * Get State Instance
     *
     * @return State
     * @deprecated 100.2.0
     */
    private function getState()
    {
        if ($this->state === null) {
            $this->state = ObjectManager::getInstance()->get(State::class);
        }

        return $this->state;
    }
}
