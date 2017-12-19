# laravel-ip2location

> 本插件功能介绍来自百度翻译

> Get location information based on IP address

> 意思是：根据IP地址获取位置信息

- 数据最后更新时间：2017-12-19
- 更新周期：每五天更新一次
- 数据源为：http://www.cz88.net/

### 功能介绍：
1. 只需传入一个IP，即可获得该IP所在的省份、城市以及运营商等信息。
2. 拒绝使用数据库，减轻服务器压力。
3. composer安装管理，方便快捷。
4. 数据源为纯真IP数据库，更新、更全、更准确。
5. 插件名字不重要，非laravel框架也可以使用，作者是个laravel死忠粉，嘘。

### 举个栗子

#### 安装
`composer require sunxiaozheng/ip2location`

#### 使用
1. 非laravel用户

```
require 'vendor/autoload.php';

use Sunxiaozheng\Ip\Location;

$ip = '1.86.10.173';
$param = ''; 
/*
 * 传入prov返回该IP所在的省份
 * 传入cy返回该IP所在的城市
 * 传入net返回该IP所在的运营商（移动、联通、电信）
 * 不传默认返回该IP所在的省份+城市
 */
var_dump(Location::get($ip, $param));
```
2. laravel用户
- 在 app/config/app.php(Laravel 4) 或 config/app.php(Laravel 5.0 - 5.4)，或者你自定义配置的 app.php 文件内添加，如果是 Laravel 5.5 ，支持扩展包发现，不需要添加下面的代码

- Laravel 5.5 不需要添加
```
'aliases' => array(
    'Location'  => 'Sunxiaozheng\Ip\Location',
),
```
- 在项目中使用 `Location::get($ip, $param)` 或 `Location::get(Request::getClientIp(), $param)`

#### 啰嗦一句
有什么新的想法和建议，欢迎提交 issue 或者 Pull Requests 。

#### License MIT