<?php


namespace app\api\exception;


use think\Exception;

//业务错误信息
class ApiException extends Exception
{
    /**
     * @var mixed 携带API数据包
     */
    protected $api_data;

    public function setApiData($api_data)
    {
        $this->api_data = $api_data;
    }

    public function getApiData()
    {
        return $this->api_data;
    }

    public function hasApiData(): bool
    {
        return !empty($this->api_data);
    }
}