<?php


namespace Eadmin\controller;


use Eadmin\Admin;
use Eadmin\component\grid\Sidebar;
use Eadmin\Controller;
use Eadmin\form\Form;
use Eadmin\grid\SidebarGrid;
use Eadmin\model\AdminModel;
use Eadmin\model\SystemFile;
use Eadmin\model\SystemFileCate;
use Eadmin\service\FileService;
use Eadmin\service\FileSystemService;

class FileSystem extends Controller
{
    public function index($uploadFinder=false)
    {
        if ($uploadFinder) {
            return $this->uploadFinder();
        } else {
            $data = FileSystemService::instance()->getFiles($this->request->get('path'), $this->request->get('search'));
            $fileSystem = new \Eadmin\component\basic\FileSystem($data);
            $fileSystem->initPath(FileSystemService::instance()->getPath())->title('资源库')->description('列表');
            return $fileSystem;
        }
    }

    public function uploadFinder()
    {
        $search = $this->request->get('search');
        $cate_id = $this->request->get('cate_id');
        $data = SystemFile::where('admin_id', Admin::id())
            ->where('uptype', 'local')
            ->when($cate_id, ['cate_id'=>$cate_id])
            ->when($search, [['real_name', 'like', "%{$search}%"]])
            ->pages()
            ->select()->map(function ($item) {
                $item['url'] = FileService::instance()->url($item['path'], 'local');
                $item['path'] = \think\facade\Filesystem::disk('local')->path('/') . $item['path'];
                $item['dir'] = false;
                $item['size'] = $item['file_size'];
                $item['permission'] = '';
                $item['author'] = AdminModel::where('id',$item['admin_id'])->value('nickname');
                $item['update_time'] = $item['create_time'];
                return $item;
            })->toArray();
        $fileSystem = new \Eadmin\component\basic\FileSystem($data);
        $fileSystem->initPath(FileSystemService::instance()->getPath())
            ->attr('height', '350px')
            ->uploadFinder();
        $sidebarGrid = SidebarGrid::create(new SystemFileCate(), $fileSystem)
            ->form($this->cateForm())
            ->field('cate_id')
            ->params(['uploadFinder' => true]);
        $sidebarGrid->model()->where('status', 1)->where('admin_id',Admin::id());
        return $sidebarGrid;
    }

    public function cateForm()
    {
        $form = new Form(new SystemFileCate);
        $options = Admin::menu()->listOptions(SystemFileCate::where('admin_id',Admin::id())->select()->toArray());
        $form->select('pid', '分类')
            ->options([0 => '顶级分类'] + array_column($options, 'label', 'id'))
            ->required();
        $form->text('name', '分类名称')->required();
        $form->switch('status', '显示')->default(1);
        $form->number('sort', '排序')->default(0);
        $form->hidden('admin_id')->default(Admin::id());
        return $form;
    }

    //新建文件夹
    public function mkdir($path)
    {
        if (is_dir($path)) {
            admin_error_message('文件夹已存在');
        }
        mkdir($path, 0755);
        admin_success('成功', '新建文件夹成功');
    }

    //重命名文件夹
    public function rename($name, $path)
    {
        if (is_dir($path)) {
            $newPath = dirname($path) . DIRECTORY_SEPARATOR . $name;
            if (is_dir($newPath)) {
                admin_error_message('重命名文件夹名称已存在');
            }
            rename($path, $newPath);
            admin_success('成功', '文件夹重命名成功');
        }
        admin_error_message('文件夹不存在');
    }

    public function del($paths)
    {
        foreach ($paths as $path){
            $path = str_replace( \think\facade\Filesystem::disk('local')->path('/'),'',$path);
            SystemFile::where('path',$path)->delete();
        }
        FileSystemService::instance()->delFiels($paths);
        admin_success('成功', '删除完成');
    }
}
