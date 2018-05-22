<?php 
namespace Jsnlib\Joomla;

// 這個類別提供 Composer 自動載入的一個進入點，但因為實作是 Trait 所以我們這裡保持空白
class EasyRequest
{}

// Trait 支援 PHP 5.4.0+
trait EasyRequestTrait {

    protected $app;
    protected $input;
    protected $get;
    protected $post;

    // 非常重要的初始化，因為 trait 沒有 __construct() 所以務必讓每個 public 進入的方法都能被支援
    protected function init()
    {
        // 避免重複載入
        if (is_object($this->app) && 
            is_object($this->input) && 
            is_object($this->get) && 
            is_object($this->post))
        {
            return false;
        }

        $this->app = \JFactory::getApplication();
        $this->input = $this->app->input;
        $this->get  = $this->input->get;
        $this->post = $this->input->post;
    }

    /**
     * 取得 Joomla Input 的方法
     * 
     * @param  string $type      post|get
     * @param  string $_callName __call 的參數 name
     * @return array            [呼叫的型態, Joomla Input 轉換型態的方法名稱]
     *                          例如 [String , getString]
     *                               [Int , getInt]
     */
    protected function inputMethod($type, $_callName)
    {
        $callType = str_replace($type, null, $_callName);
        $method = "get{$callType}";
        return [ucfirst($callType), $method];
    }

    /**
     * 呼叫 Joomla Input 的對應方式例如 getString()、getInt()
     * 
     * @param  string $post_get  post/get
     * @param  array  $arguments __call 的參數 arguments
     * @param  string $method    對應的方法，例如 getString
     * @return                   取得的內容
     */
    protected function callJoomlaMethod($post_get, $arguments, $method)
    {
        $key     = isset($arguments[0]) ? $arguments[0]: false;
        $default = isset($arguments[1]) ? $arguments[1]: false;

        if ($key === false)
        {
            return $this->$post_get->$method();
        }
        elseif ($default === false)
        {
            return $this->$post_get->$method($key);
        }
        return $this->$post_get->$method($key, $default);
    }

    /**
     * 取得 post/get 內容
     * 
     * @param  string $post_get  post|get
     * @param  string $callName  __call 的參數 name
     * @param  array  $arguments __call 的參數 arguments
     * @return                  取得的內容
     */
    protected function httpMethod($post_get, $callName, $arguments)
    {            
        list($type, $method) = $this->inputMethod($post_get, $callName);

        return $this->callJoomlaMethod($post_get, $arguments, $method);
    }

    /**
     * 魔術方法，用來快速取得 post|get，例如繼承的類別
     * 可以使用 $this->getString('task') 或 $this->posString('title')
     *  
     * @param  string $name      
     * @param  array  $arguments 
     */
    public function __call($name , $arguments)
    {
        $this->init();

        // 透過第一次出現的位置，若位置為 0 代表存在，false 代表不存在
        if (strpos($name, "get") === 0)
        {
            return $this->httpMethod("get", $name, $arguments);
        }
        elseif (strpos($name, "post") === 0)
        {
            return $this->httpMethod("post", $name, $arguments);
        }
        else
        {
            throw new \Exception(" 呼叫的方法不存在 {$name}() ");
        }
    }

    public function get($name = null, $default = null, $filter = 'cmd')
    {
        $this->init();

        if ($name === null)
        {
            return $this->getArray();
        }

        return $this->get->get($name, $default, $filter);
    }

    public function post($name = null, $default = null, $filter = 'cmd')
    {
        $this->init();

        if ($name === null)
        {
            return $this->postArray();
        }

        return $this->post->get($name, $default, $filter);
    }
}