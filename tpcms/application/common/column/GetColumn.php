<?php
/**
 * Created by PhpStorm.
 * User: Lenovo
 * Date: 2020/10/28
 * Time: 10:02
 */

namespace app\common\column;


use think\Db;

class GetColumn
{
    /**获取便利栏目
     * @return array|mixed|\PDOStatement|string|\think\Collection
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    static function get ()
    {
        $data = Db::table("column")->where("status",1)->select();
        $dataArr = [];
        foreach ($data as $value) {
            if ($rows = $value) {
                $dataArr[ $rows["f_id"] ][] = $rows;
            }
        }
        function getTree($data,$level=1){
            //     数组   等级
            foreach ($data[$level] as $key => $value) {
                if (array_key_exists($value["id"],$data)) {
                    $data[$level][$key]["child"] = getTree($data,$value["id"]);
                }
            }
            return $data[$level];
        }

        $data = getTree($dataArr,0);
        return $data;
    }
}