<?php

class Riskified_Full_Model_Container_Beacon extends Enterprise_PageCache_Model_Container_Abstract
{
    protected function _getCacheId()
    {
        $timestamp = floor(time() / 3600);
        $identifier = $this->_getIdentifier();
        $cache = md5($this->_placeholder->getAttribute('cache_id'));
        return 'RISKIFIED_FULL_BEACON_CACHE_' . $cache . '_'  . $identifier . '_' . $timestamp;
    }

    protected function _renderBlock()
    {
        $blockClass = $this->_placeholder->getAttribute('block');;
        $template = $this->_placeholder->getAttribute('template');
        $block = new $blockClass;
        $block->setTemplate($template);
        return $block->toHtml();
    }

    protected function _getIdentifier()
    {
        return $this->_getCookieValue('rCookie', '');
    }
}