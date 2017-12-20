<?php

require __DIR__ . '/../vendor/autoload.php';

use Sunxiaozheng\Ip\Addr;

class TestIp
{

    /**
     * 测试文件（作者好不专业，竟然没用PHPunit）
     * 
     * @author Shawn Sun <ershawnsun@gmail.com>
     * @version 1.0.0.1219
     */
    public function test()
    {
        /*
         * @param $ip IP地址
         * @param $param 不传的话默认返回省份+城市
         * prov-省份 cy-城市 net-运营商
         */
        $ip = '1.86.10.173';
        $param = 'prov';
        echo Addr::get($ip, $param);
    }

}

$Test = new TestIp();
$Test->test();
