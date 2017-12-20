<?php

namespace Sunxiaozheng\Ip;

/**
 * 地理位置查询类
 * 使用纯真IP地址库 支持查询省和城市
 * 由于使用UTF8编码 如果使用纯真IP地址库的话 需要对返回结果进行编码转换 
 * @author Shawn Sun <ershawnsun@gmail.com>
 */
class Addr
{

    /**
     * qqwry.dat文件指针 
     * 
     * @var resource 
     */
    private static $fp;

    /**
     * 第一条IP记录的偏移地址 
     * 
     * @var int 
     */
    private static $firstip;

    /**
     * 最后一条IP记录的偏移地址 
     * 
     * @var int 
     */
    private static $lastip;

    /**
     * IP记录的总条数（不包含版本信息记录） 
     * 
     * @var int 
     */
    private static $totalip;

    /**
     * 构造函数，打开 qqwry.dat 文件并初始化类中的信息 
     * 
     * @param string $filename 
     * @return IpLocation 
     */
    private static function construct($filename = "qqwry.dat")
    {
        static::$fp = 0;
        if ((static::$fp = fopen(dirname(__FILE__) . '/' . $filename, 'rb')) !== false) {
            static::$firstip = static::getlong();
            static::$lastip = static::getlong();
            static::$totalip = (static::$lastip - static::$firstip) / 7;
        }
    }

    /**
     * 返回读取的长整型数 
     * 
     * @access private 
     * @return int 
     */
    private static function getlong()
    {
        //将读取的little-endian编码的4个字节转化为长整型数 
        $result = unpack('Vlong', fread(static::$fp, 4));
        return $result['long'];
    }

    /**
     * 返回读取的3个字节的长整型数 
     * 
     * @access private 
     * @return int 
     */
    private static function getlong3()
    {
        //将读取的little-endian编码的3个字节转化为长整型数 
        $result = unpack('Vlong', fread(static::$fp, 3) . chr(0));
        return $result['long'];
    }

    /**
     * 返回压缩后可进行比较的IP地址 
     * 
     * @access private 
     * @param string $ip 
     * @return string 
     */
    private static function packip($ip)
    {
        // 将IP地址转化为长整型数，如果在PHP5中，IP地址错误，则返回False， 
        // 这时intval将Flase转化为整数-1，之后压缩成big-endian编码的字符串 
        return pack('N', intval(ip2long($ip)));
    }

    /**
     * 返回读取的字符串 
     * 
     * @access private 
     * @param string $data 
     * @return string 
     */
    private static function getstring($data = "")
    {
        $char = fread(static::$fp, 1);
        while (ord($char) > 0) {        // 字符串按照C格式保存，以\0结束 
            $data .= $char;             // 将读取的字符连接到给定字符串之后 
            $char = fread(static::$fp, 1);
        }
        return $data;
    }

    /**
     * 返回地区信息 
     * 
     * @access private 
     * @return string 
     */
    private static function getarea()
    {
        $byte = fread(static::$fp, 1);    // 标志字节 
        switch (ord($byte)) {
            case 0:                     // 没有区域信息 
                $area = "";
                break;
            case 1:
            case 2:                     // 标志字节为1或2，表示区域信息被重定向 
                fseek(static::$fp, static::getlong3());
                $area = static::getstring();
                break;
            default:                    // 否则，表示区域信息没有被重定向 
                $area = static::getstring($byte);
                break;
        }
        return $area;
    }

    private static $provinces = array("黑龙江省", "辽宁省", "吉林省", "河北省", "河南省", "湖北省", "湖南省", "山东省", "山西省", "陕西省",
        "安徽省", "浙江省", "江苏省", "福建省", "广东省", "海南省", "四川省", "云南省", "贵州省", "青海省", "甘肃省",
        "江西省", "台湾省", "内蒙古", "宁夏", "新疆", "西藏", "广西", "北京市", "上海市", "天津市", "重庆市", "香港", "澳门");

    /**
     * 根据所给 IP 地址或域名返回所在地区信息 
     * 
     * @access public 
     * @param string $ip 
     * @return array 
     */
    private static function getlocation($ip = '')
    {
        static::construct();

        if (!static::$fp)
            return null;            // 如果数据文件没有被正确打开，则直接返回空 
        if (empty($ip))
            $ip = static::get_client_ip();
        $location['ip'] = gethostbyname($ip);   // 将输入的域名转化为IP地址 
        $ip = static::packip($location['ip']);   // 将输入的IP地址转化为可比较的IP地址 
        // 不合法的IP地址会被转化为255.255.255.255 
        // 对分搜索 
        $l = 0;                         // 搜索的下边界 
        $u = static::$totalip;            // 搜索的上边界 
        $findip = static::$lastip;        // 如果没有找到就返回最后一条IP记录（QQWry.Dat的版本信息） 
        while ($l <= $u) {              // 当上边界小于下边界时，查找失败 
            $i = floor(($l + $u) / 2);  // 计算近似中间记录 
            fseek(static::$fp, static::$firstip + $i * 7);
            $beginip = strrev(fread(static::$fp, 4));     // 获取中间记录的开始IP地址 
            // strrev函数在这里的作用是将little-endian的压缩IP地址转化为big-endian的格式 
            // 以便用于比较，后面相同。 
            if ($ip < $beginip) {       // 用户的IP小于中间记录的开始IP地址时 
                $u = $i - 1;            // 将搜索的上边界修改为中间记录减一 
            } else {
                fseek(static::$fp, static::getlong3());
                $endip = strrev(fread(static::$fp, 4));   // 获取中间记录的结束IP地址 
                if ($ip > $endip) {     // 用户的IP大于中间记录的结束IP地址时 
                    $l = $i + 1;        // 将搜索的下边界修改为中间记录加一 
                } else {                  // 用户的IP在中间记录的IP范围内时 
                    $findip = static::$firstip + $i * 7;
                    break;              // 则表示找到结果，退出循环 
                }
            }
        }

        //获取查找到的IP地理位置信息 
        fseek(static::$fp, $findip);
        $location['beginip'] = long2ip(static::getlong());   // 用户IP所在范围的开始地址 
        $offset = static::getlong3();
        fseek(static::$fp, $offset);
        $location['endip'] = long2ip(static::getlong());     // 用户IP所在范围的结束地址 
        $byte = fread(static::$fp, 1);    // 标志字节 
        switch (ord($byte)) {
            case 1:                     // 标志字节为1，表示国家和区域信息都被同时重定向 
                $countryOffset = static::getlong3();         // 重定向地址 
                fseek(static::$fp, $countryOffset);
                $byte = fread(static::$fp, 1);    // 标志字节 
                switch (ord($byte)) {
                    case 2:             // 标志字节为2，表示国家信息又被重定向 
                        fseek(static::$fp, static::getlong3());
                        $location['country'] = static::getstring();
                        fseek(static::$fp, $countryOffset + 4);
                        $location['area'] = static::getarea();
                        break;
                    default:            // 否则，表示国家信息没有被重定向 
                        $location['country'] = static::getstring($byte);
                        $location['area'] = static::getarea();
                        break;
                }
                break;
            case 2:                     // 标志字节为2，表示国家信息被重定向 
                fseek(static::$fp, static::getlong3());
                $location['country'] = static::getstring();
                fseek(static::$fp, $offset + 8);
                $location['area'] = static::getarea();
                break;
            default:                    // 否则，表示国家信息没有被重定向 
                $location['country'] = static::getstring($byte);
                $location['area'] = static::getarea();
                break;
        }
        if (trim($location['country']) == 'CZ88.NET') {  // CZ88.NET表示没有有效信息 
            $location['country'] = '未知';
        }
        if (trim($location['area']) == 'CZ88.NET') {
            $location['area'] = '';
        }
        $location['country'] = @iconv('gbk', 'utf-8', $location['country']); //转换格式，防止乱码 
        $location['area'] = @iconv('gbk', 'utf-8', $location['area']); //转换格式，防止乱码 
        foreach (static::$provinces as $v)
        {
            if (strpos($location['country'], $v) === 0) {
                $location['province'] = $v;
                $location['city'] = str_replace($v, '', $location['country']);
                break;
            }
        }
        if (empty($location['province']))
            $location['province'] = $location['country'];
        if (empty($location['city']))
            $location['city'] = $location['country'];
        return $location;
    }

    /**
     * 获取客户端IP
     * @param integer $type 0 返回IP地址 1 返回IP地址数字
     */
    private static function get_client_ip($type = 0)
    {
        $type = $type ? 1 : 0;
        static $ip = NULL;
        if ($ip !== NULL)
            return $ip[$type];
        if ($_SERVER['HTTP_X_REAL_IP']) {//nginx 代理模式下，获取客户端真实IP
            $ip = $_SERVER['HTTP_X_REAL_IP'];
        } elseif (isset($_SERVER['HTTP_CLIENT_IP'])) {//客户端的ip
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {//浏览当前页面的用户计算机的网关
            $arr = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']);
            $pos = array_search('unknown', $arr);
            if (false !== $pos)
                unset($arr[$pos]);
            $ip = trim($arr[0]);
        } elseif (isset($_SERVER['REMOTE_ADDR'])) {
            $ip = $_SERVER['REMOTE_ADDR']; //浏览当前页面的用户计算机的ip地址
        } else {
            $ip = $_SERVER['REMOTE_ADDR'];
        }
        // IP地址合法验证
        $long = sprintf("%u", ip2long($ip));
        $ip = $long ? array($ip, $long) : array('0.0.0.0', 0);
        return $ip[$type];
    }

    /**
     * 封装一个 get 方法
     * @param string $ip IP地址
     * @param string $param 具体信息
     * @version 1.0.0.1219
     * cy:city prov:province net:newwork
     */
    public static function get($ip = '', $param = '')
    {
        $res = '';
        $location = static::getlocation($ip);
        switch ($param) {
            case 'prov':
                $res = $location['province'] ?: '';
                break;
            case 'cy':
                $res = $location['city'] ?: '';
                break;
            case 'net':
                $res = $location['area'] ?: '';
                break;
            default:
                $res = $location['country'] ?: '';
                break;
        }
        return $res;
    }

    /**
     * 析构函数，用于在页面执行结束后自动关闭打开的文件。 
     * 
     */
    public function __destruct()
    {
        if (static::$fp) {
            fclose(static::$fp);
        }
        static::$fp = 0;
    }

}
