<?php

class Riskified_Full_Model_Container_Beacon extends Enterprise_PageCache_Model_Container_Abstract
{
    protected function _getCacheId()
    {
        $cache = md5($this->_placeholder->getAttribute('cache_id'));
        return 'RISKIFIED_FULL_BEACON_CACHE_' . $cache . '_' . $this->_getSessionId() . '_' . $this->_getRiskifiedCookie();
    }

    protected function _renderBlock()
    {
        $blockClass = $this->_placeholder->getAttribute('block');;
        $template = $this->_placeholder->getAttribute('template');
        $block = new $blockClass;
        $block->setTemplate($template);
        return $block->toHtml();
    }

    protected function _getSessionId()
    {
        return $this->_getCookieValue('frontend', '');
    }

    protected function _getRiskifiedCookie()
    {
        return $this->_getCookieValue('rCookie', '');
    }
}

