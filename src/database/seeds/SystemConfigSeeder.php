<?php

use think\migration\Seeder;

class SystemConfigSeeder extends Seeder
{
    /**
     * Run Method.
     *
     * Write your database seeder using this method.
     *
     * More information on writing seeders is available here:
     * http://docs.phinx.org/en/latest/seeding.html
     */
    public function run()
    {
        $datas = array(
            0 =>
                array(
                    'name' => 'system_web_name',
                    'value' => 'Ex-admin',
                ),
            1 =>
                array(
                    'name' => 'system_web_logo',
                    'value' => 'https://www.ex-admin.com/cms/logo.png',
                ),
            2 =>
                array(
                    'name' => 'system_web_miitbeian',
                    'value' => '粤ICP备16006642号-2',
                ),
            3 =>
                array(
                    'name' => 'system_web_copyright',
                    'value' => '©版权所有 2014-2020',
                ),

            4 =>
                array(
                    'name' => 'databackup_on',
                    'value' => '1',
                ),
            5 =>
                array(
                    'name' => 'database_number',
                    'value' => '10',
                ),
            6 =>
                array(
                    'name' => 'database_day',
                    'value' => '1',
                ),
        );
        foreach ($datas as $data){
            sysconf($data['name'],$data['value']);
        }
    }
}
