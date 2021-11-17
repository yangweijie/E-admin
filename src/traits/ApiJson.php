<?php
/**
 * @Author: rocky
 * @Copyright: 广州拓冠科技 <http://my8m.com>
 * Date: 2019/7/11
 * Time: 15:48
 */


namespace Eadmin\traits;


use app\common\service\ApiCode;
use plugin\apidoc\resource\Resource;
use think\Collection;
use think\exception\HttpResponseException;
use think\facade\Db;
use think\Model;


trait  ApiJson
{
    protected $data = [];
    protected $example = [];

    /**
     * 返回成功json
     * @Author: rocky
     * 2019/7/11 16:02
     * @param array $data 输出数据
     * @param int $code 错误代码
     * @param string $msg 提示信息
     * @return \think\response\Json
     */
    public function successCode($data = [], $code = 200, $msg = '')
    {
        $response = $this->responseJsonData($data, $code, $msg);
        throw new HttpResponseException($response);
    }

    /**
     * 返回失败json
     * @Author: rocky
     * 2019/7/11 16:02
     * @param int $code 错误代码
     * @param string $msg 错误信息
     * @param array $data 输出数据
     * @param int $http_code http状态码
     * @return \think\response\Json
     */
    public function errorCode($code = 999, $msg = '', $data = [], $http_code = 200)
    {
        $response = $this->responseJsonData($data, $code, $msg, $http_code);
        throw new HttpResponseException($response);
    }

    public function addData($key, $data, $desc)
    {
        $this->data[$key] = $data;
        $this->example[$key] = $desc;
        $this->createExample($data,$key);
        return $this;
    }

    public function getExample()
    {
        return $this->example;
    }

    protected function createExample($data, $parentKey = null)
    {
        if($data instanceof Resource){
            $example = $data->getExample();
            foreach ($example as $field=>$desc){
                if(!is_null($parentKey)){
                    $this->example[$parentKey.'.'.$field] = $desc;
                }else{
                    $this->example[$field] = $desc;
                }
            }
        }elseif ($data instanceof Collection && $data->count() > 0) {
            $data = $data[0];
        }
        if ($data instanceof Model) {
            $fields = $data->getFields();
            foreach ($fields as $field => $row) {
                $key = $field;
                if(!empty($parentKey)){
                    $key = $parentKey.'.'.$field;
                }

                if ($row['primary'] && empty($row['comment'])) {
                    $table = $data->getTable();
                    $arr = Db::query("show table status like '$table'");
                    $info = current($arr);
                    $this->example[$key] = $info['Comment'] . $field;
                }
                if (!empty($row['comment'])) {
                    $this->example[$key] = $row['comment'];
                }
            }
        }
    }

    /**
     * 输出自定义json
     * @Author: rocky
     * 2019/7/12 17:08
     * @param array $data 数据
     * @param int $code 错误码
     * @param string $errMsg 错误信息
     * @param int $http_code 状态码
     */
    protected function responseJsonData($data = [], $code = 200, $errMsg = '', $http_code = 200)
    {
        if (empty($data)) {
            $data = $this->data;
        } else {
            $this->data = $data;
        }
        $this->createExample($data);
        $return['code'] = (int)$code;
        if (!empty($errMsg)) {
            $return['message'] = $errMsg;
        } else {
            $message = isset(config('apiCode')[$code]) ? config('apiCode')[$code] : '';
            $return['message'] = $message;
        }
        $return['data'] = $data;
        return json($return, $http_code);
    }

    public function getData()
    {
        return $this->data;
    }


}
