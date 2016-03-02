<?php

class Riskified_Full_Model_Container_Beacon extends Enterprise_PageCache_Model_Container_Abstract
{
    protected function _getCacheId()
    {
        $identifier =  $this->_getCookieValue('frontend', '') . $this->_getCookieValue('rCookie', '');
        $cache = md5($this->_placeholder->getAttribute('cache_id'));
        return 'RISKIFIED_FULL_BEACON_CACHE_' . $cache . '_'  . $identifier;
    }

    protected function _renderBlock()
    {
        $blockClass = $this->_placeholder->getAttribute('block');;
        $template = $this->_placeholder->getAttribute('template');
        $block = new $blockClass;
        $block->setTemplate($template);
        return $block->toHtml();
    }
}