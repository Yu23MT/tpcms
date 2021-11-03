<?php

namespace app\command;

use think\console\Command;
use think\console\Input;
use think\console\Output;
use think\Db;

class Hello extends Command
{
    protected function configure()
    {
        // 指令配置
        $this->setName('hello')
            ->setDescription('Say Hello');
        // 设置参数
        
    }

    protected function execute(Input $input, Output $output)
    {
        $data = Db::table('author')->where('id',1)->select();

        \think\facade\Cache::store('redis')->set('blog:article',$data,100);
    	// 指令输出
    	$output->writeln('hello');
    }
}
