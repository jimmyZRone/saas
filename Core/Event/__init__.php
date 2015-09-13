<?php

    namespace Core;

    /**
     * 事件处理
     * @author lishengyou
     * 最后修改时间 2015年2月11日 下午3:40:08
     *
     */
    class Event
    {

        protected $_event = array();

        /**
         * 绑定事件
         * @author lishengyou
         * 最后修改时间 2015年2月11日 下午3:41:24
         *
         * @param unknown $event
         * @param unknown $callback
         */
        public function bind($event , $callback)
        {
            $vmCallback = new \Core\Event\Callback();
            if ($vmCallback->bind($callback))
            {
                $this->_event[$event][] = $vmCallback;
                return true;
            }
            return false;
        }

        /**
         * 解除绑定
         * @author lishengyou
         * 最后修改时间 2015年2月11日 下午3:42:25
         *
         * @param unknown $event
         * @param string $callback
         */
        public function unbind($event , $callback = null)
        {
            if (!isset($this->_event[$event]))
            {
                return false;
            }
            if (!$callback)
            {
                unset($this->_event[$event]);
                return true;
            }
            $callbackType = gettype($callback);
            $queue = array();
            foreach ($this->_event[$event] as $_callback)
            {
                $_callback = $_callback->getBind();
                if ($callbackType != gettype($callback))
                {
                    $queue[] = $_callback;
                    continue;
                }
                if ($callbackType == 'string' && $_callback != $callback)
                {
                    $queue[] = $_callback;
                    continue;
                }
                if ($callbackType == 'array' && ($_callback[0] != $callback[0] || $_callback[1] != $callback[1]))
                {
                    $queue[] = $_callback;
                    continue;
                }
                if ($callbackType == 'object' && spl_object_hash($callback) != spl_object_hash($_callback))
                {
                    $queue[] = $_callback;
                    continue;
                }
            }
            if (!isset($this->_event[$event]))
                $this->_event[$event] = array();
            $this->_event[$event] = $queue;
            if (empty($this->_event[$event]))
            {
                unset($this->_event[$event]);
            }
        }

        /**
         * 通知事件
         * @var unknown
         */
        const EVENT_NOTICE = 1;

        /**
         * 传递事件
         * @var unknown
         */
        const EVENT_TRANSFER = 2;

        /**
         * 触发事件
         * @author lishengyou
         * 最后修改时间 2015年2月11日 下午3:42:01
         *
         * @param unknown $event
         */
        public function trigger($event , &$e = null , $type = self::EVENT_NOTICE)
        {
            if (!isset($this->_event[$event]))
            {
                return false;
            }
            foreach ($this->_event[$event] as $callback)
            {
                try
                {
                    if ($type == self::EVENT_NOTICE)
                    {
                        if (is_object($e))
                        {
                            $callback->trigger(clone $e);
                        }
                        else
                        {
                            $callback->trigger($e);
                        }
                    }
                    else
                    {
                        $rs = $callback->trigger($e);
                        $e = is_null($rs) ? $e : $rs;
                    }
                } catch (Event\Exception $ex)
                {
                    logError(__CLASS__ . ':' . __LINE__ . 'Exc:' . $ex->getMessage());
                    break;
                } catch (\Exception $ex)
                {
                    logError(__CLASS__ . ':' . __LINE__ . 'Exc:' . $ex->getMessage());
                    dump(__CLASS__ . ':' . __LINE__ . 'Exc:' . $ex->getMessage());
                    continue;
                }
            }
            return $e;
        }

    }
    