<?php


namespace Aoxiang\WebmanSunctum\Exception;


class SunctumException extends \RuntimeException
{
    protected $error;

    public function __construct($error, $code = 401)
    {
        parent::__construct();
        $this->error   = $error;
        $this->code    = $code;
        $this->message = is_array($error) ? implode(PHP_EOL, $error) : $error;
    }

    /**
     * @param  mixed  $code
     *
     * @return SunctumException
     */
    public function setCode($code) : SunctumException
    {
        $this->code = $code;

        return $this;
    }

    /**
     * 获取验证错误信息
     *
     * @access public
     * @return array|string
     */
    public function getError()
    {
        return $this->error;
    }
}