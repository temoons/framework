<?php
namespace Seeruo;

use Exception;
use \Seeruo\Core\Cmd;
use \Seeruo\Core\Log;
use \Seeruo\Core\Init;
use \Seeruo\Core\Create;
use \Seeruo\Core\Server;
use \Seeruo\Core\Push;
use \Seeruo\Core\Build;
use \Seeruo\Core\HooksMan;

// 公共函数
include 'Common/common.php';

/**
* 框架主类
*/
class Application
{
    /**
     * 初始配置
     */
	private $config = [
        // 基础设置::必填
        'root'              => '',                  // 应用根路径
        // 以下配置根据情况选填
        'title'             => '我的博客',           // 网站名称
        'url'               => '',                  // 网站URL
        'themes'            => 'default',           // 网站主题
        'author'            => 'your name',         // 您的名字
        'home_page'         => '',                  // 主页文件
        // 本地调试设置
        'server_address'    => 'localhost:9001',    // 本地服务器调试地址
        'auto_open'         => false,               // 自动打开浏览器
        // 发布到服务器空间
        'push_type'         => 'ssh',               // 暂时只支持ssh方式，需要服务器开启的SSH支持
        'push_user'         => 'root',              // SSH账号
        'push_address'      => '127.0.0.1',         // SSH推送地址
        'push_path'         => '/var/www/html/blog',// SSH服务器网站根路径,该路径需开启写权限
    ];

    /**
     * 构造：接受配置，设置各项初始
     */
    public function __construct($config = [])
    {
        date_default_timezone_set('PRC'); //设置中国时区 
        // 合并配置
    	if ($config) {
    		$this->config = array_merge($this->config, $config);
    	}
        
        // 目录设置
        $this->config['public_dir'] = $this->config['root'].DIRECTORY_SEPARATOR.'_Public';
        $this->config['themes_dir'] = $this->config['root'].DIRECTORY_SEPARATOR.'Themes'.DIRECTORY_SEPARATOR.$this->config['themes'];
        $this->config['source_dir'] = $this->config['root'].DIRECTORY_SEPARATOR.'Source';
        $this->config['plugins_dir'] = $this->config['root'].DIRECTORY_SEPARATOR.'Plugins';

        // 检查配置
        if (empty($this->config['root'])) {
            die('root:缺少值'.PHP_EOL);
        }

        // 初始钩子
        HooksMan::getinstance($this->config);
    }

    /**
     * 执行入口
     * $model 开发模式 cli命令行模式，web网页模式
     */
    public function run($model='cli')
    {
        // 如果是网页请求模式
        if ($model=='web') {
            $this->config['web_develop'] = true;
            $model = new Build($this->config);
            $model->web();
            exit;
        }
        // 解析命令
        Cmd::parse();
        // 检测需要执行的指令
        // 帮助
        if (Cmd::has('h') || Cmd::has('help')) {
            Cmd::help();
            exit;
        }
        // 初始化
        if (Cmd::has('init')) {
            $model = new Init($this->config);
            $model->run();
            exit;
        }
        // 创建模板文件
        if (Cmd::has('c') || Cmd::has('create')) {
            $fileName = Cmd::has('c') ? Cmd::get('c') : Cmd::get('create');
            if (empty($fileName)) {
                Log::info( 'Miss FileName!', 'error');
            }
            $model = new Create($this->config);
            $model->run($fileName);
            exit;
        }
        // 构建静态文件
        if (Cmd::has('b') || Cmd::has('build')) {
            $model = new Build($this->config);
            $model->run();
            exit;
        }
        // 发布网站到服务器
        if (Cmd::has('p') || Cmd::has('push')) {
            $model = new Push($this->config);
            $model->run();
            exit;
        }
        // 本地调试服务器
        if (Cmd::has('s') || Cmd::has('server')) {
            $model = new Server($this->config);
            $model->preview();
            exit;
        }
        // 本地调试服务器
        if (Cmd::has('d') || Cmd::has('develop')) {
            $model = new Server($this->config);
            $model->develop();
            exit;
        }
    }
}