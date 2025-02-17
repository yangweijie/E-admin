<?php
/**
 * Created by PhpStorm.
 * User: rocky
 * Date: 2020-05-21
 * Time: 00:05
 */

namespace Eadmin\service;

use Eadmin\Admin;
use Eadmin\model\SystemFile;
use Eadmin\Query;
use Intervention\Image\ImageManagerStatic;
use Overtrue\Flysystem\Qiniu\Plugins\UploadToken;
use think\App;
use think\facade\Cache;
use think\facade\Db;
use think\facade\Filesystem;
use Eadmin\Service;
use think\File;

class FileService extends Service
{

    protected $totalSizeCacheKey;
    public $upType = 'local';
    protected $disableExt = [];
    public function __construct(App $app)
    {
        parent::__construct($app);
        $this->upType = config('admin.uploadDisks', 'local');
        $this->disableExt = config('admin.upload.extension.disable',['php']);
    }

    /**
     * 本地分片上传
     * @param mixed $file 文件对象
     * @param string $filename 文件名
     * @param mixed $chunkNumber 分块编号
     * @param mixed $totalChunks 总块数量
     * @param mixed $chunkSize 分片大小
     * @param mixed $totalSize 总文件大小
     * @param string $saveDir 指定保存目录
     * @param bool $isUniqidmd5 是否唯一文件名
     * @param string $upType disk
     * @return bool|string
     */
    public function chunkUpload($file, $filename, $chunkNumber, $totalChunks, $chunkSize, $totalSize, $saveDir, bool $isUniqidmd5, $upType = 'local')
    {
        $this->upType = $upType;
        $names = str_split(md5($filename), 16);
        $chunkSaveDir = $names[0];
        if (is_null($file)) {

            if ($totalChunks == 1) {
                $res = $this->fileExist($upType, $saveDir . $filename, $totalSize);

                if ($res === true) {
                    return $this->url($saveDir . $filename);
                } else {
                    return $res;
                }
            } elseif ($isUniqidmd5 == false) {
                $res = $this->fileExist($upType, $saveDir . $filename, $totalSize);

                if ($res === true) {
                    return $this->url($saveDir . $filename);
                } elseif ($res == -1) {
                    return $res;
                } else {
                    return $this->checkChunkExtis($filename, $chunkSaveDir, $chunkNumber, $chunkSize, $totalSize);
                }
            } else {

                return $this->checkChunkExtis($filename, $chunkSaveDir, $chunkNumber, $chunkSize, $totalSize);
            }
        } else {
            if ($totalChunks == 1) {
                //分片总数量1直接保存
                if (substr($saveDir, -1) == '/') {
                    $saveDir = substr($saveDir, 0, -1);
                }
                return $this->upload($file, $filename, $saveDir, $this->upType, $isUniqidmd5);
            } else {

                $this->totalSizeCacheKey = md5($filename . 'totalSize');

                $chunkName = $names[1] . $chunkNumber;
                //写分片文件
                $res = Filesystem::disk($this->upType)->putFileAs($chunkSaveDir, $file, $chunkName);

                //判断分片数量是否和总数量一致,一致就合并分片文件

                if ($this->getChunkDirCounts($chunkSaveDir) == $totalChunks) {
                    if (!Cache::has(md5($filename))) {
                        Cache::set(md5($filename), 1, 10);
                        $url = $this->merge($chunkSaveDir, $filename, $totalChunks, $saveDir, $isUniqidmd5);
                        Cache::delete(md5($filename));
                        return $url;
                    }
                    return true;
                }
                if ($res === false) {
                    return false;
                } else {

                    if (Cache::has($this->totalSizeCacheKey)) {
                        $totalSizeCache = Cache::get($this->totalSizeCacheKey);
                        if ($totalSizeCache != $totalSize) {
                            Cache::set($this->totalSizeCacheKey, $totalSize, 3600 * 3);
                        }
                    } else {
                        Cache::set($this->totalSizeCacheKey, $totalSize, 3600 * 3);
                    }
                    return true;
                }
            }
        }
    }

    /**
     * 判断文件是否存在大小一致
     * @param string $upType 上传类型
     * @param string $filePath 文件路径
     * @param mixed $totalSize 文件大小
     * @return bool
     * @throws \League\Flysystem\FileNotFoundException
     */
    protected function fileExist($upType, $filePath, $totalSize)
    {
        if (Filesystem::disk($upType)->has($filePath)) {
            if (Filesystem::disk($upType)->getSize($filePath) == $totalSize) {
                return true;
            } else {
                //文件名相同，但大小不一致，判断文件不一样
                return -1;
            }
        } else {
            return false;
        }
    }

    /**
     * 保存文件
     * @param string $filename 文件名
     * @param mixed $content 文件内容
     * @return bool|string
     */
    public function save($filename,$content){
        return $this->upload($content,$filename);
    }
    /**
     * 上传文件
     * @param mixed $file 文件对象或文件内容
     * @param string $fileName 文件名
     * @param string $saveDir 保存目录
     * @param string $upType disk
     * @param bool $isUniqidmd5 是否使用MD5文件名
     * @return  bool|string
     */
    public function upload($file, $fileName = null, $saveDir = '/', $upType = null, bool $isUniqidmd5 = false)
    {
        //存储上传驱动类型
        if (!empty($upType)) {
            $this->upType = $upType;
        }
        //获取文件名后缀
        $extension = pathinfo($fileName, PATHINFO_EXTENSION);
        if($file instanceof File){
            $extension = pathinfo($file->getOriginalName(), PATHINFO_EXTENSION);
        }

        //判断禁止上传文件后缀
        if(in_array($extension,$this->disableExt)){
            return false;
        }

        //获取文件名
        if($file instanceof File && $isUniqidmd5){
            $fileName = md5_file($file->getRealPath()). '.' . $extension;
        }elseif ($isUniqidmd5) {
            $fileName = md5((string)microtime(true)) . '.' . $extension;
        } elseif (empty($fileName)) {
            $fileName = $file->getOriginalName();
        }
        //原文件名
        $real_name = $fileName;
        if($file instanceof File){
            $real_name = $file->getOriginalName();
        }

        //获取上传内容
        $stream = $file;
        if ($file instanceof File) {
            $stream = file_get_contents($file->getRealPath());
        }
        //保存路径
        $path = trim($saveDir . $fileName, '/');
        //判断是否存在
        $result = true;
        if(!Filesystem::disk($this->upType)->has($path)){
            //写入文件
            $result = Filesystem::disk($this->upType)->put($path, $stream);
        }
        if ($result) {
            $filename = Filesystem::disk($this->upType)->path($path);
            $this->compressImage($filename);
            $url = $this->url($path);
            $this->saveData($fileName,$real_name,$url,$path,$extension);
            return $url;
        } else {
            return false;
        }
    }
    private function saveData($fileName,$real_name,$url,$path,$extension){
        $data = [
            'name' => $fileName,
            'real_name' => request()->param('filename',$real_name),
            'url' => $url,
            'path' => $path,
            'cate_id' => request()->param('cate_id', 0),
            'ext' => $extension,
            'file_size' => request()->param('totalSize', 0),
            'file_type' => request()->param('file_type', ''),
            'uptype' => $this->upType,
            'admin_id' => Admin::id(),
        ];
        $model =  config(Admin::getAppName().'.database.file_model');
        if($model::where($data)->count() == 0){
            $model::create($data);
        }
    }
    /**
     * 获取目录下文件数量
     * @param string $chunkSaveDir
     * @return int
     */
    protected function getChunkDirCounts($chunkSaveDir)
    {
        $dir = Filesystem::disk($this->upType)->path('');
        $chunkSaveDir = $dir . $chunkSaveDir;
        $handle = opendir($chunkSaveDir);
        $i = 0;
        while (false !== $file = (readdir($handle))) {
            if ($file !== '.' && $file != '..') {
                $i++;
            }
        }
        closedir($handle);
        return $i;
    }

    /**
     * 判断文件分片是否存在实现秒传
     * @param string $filename 文件名
     * @param string $chunkSaveDir 分片保存目录
     * @param mixed $chunkNumber 第几片
     * @param mixed $chunkSize 分片大小
     * @param mixed $totalChunks 总大小
     * @return bool
     */
    protected function checkChunkExtis($filename, $chunkSaveDir, $chunkNumber, $chunkSize, $totalSize)
    {
        $this->totalSizeCacheKey = md5($filename . 'totalSize');
        if (Cache::has($this->totalSizeCacheKey)) {
            $totalSizeCache = Cache::get($this->totalSizeCacheKey);
            if ($totalSizeCache != $totalSize) {
                return false;
            }
        }
        $dir = Filesystem::disk($this->upType)->path('');
        $names = str_split(md5($filename), 16);
        $chunkSaveDir = $dir . $chunkSaveDir;
        $filenameChunk = $chunkSaveDir . DIRECTORY_SEPARATOR . $names[1] . $chunkNumber;
        if (file_exists($filenameChunk) && filesize($filenameChunk) == $chunkSize) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * 获取存储路径
     * @param string $url 文件完整url
     * @param string $upType 存储类型
     * @return string|null
     */
    public function path($url, $upType = null){
        $model =  config(Admin::getAppName().'.database.file_model');
        $fileInfo = $model::where('url',$url)
            ->when($upType,['uptype'=>$upType])
            ->find();
        if(empty($fileInfo)){
            return null;
        }
        return Filesystem::disk($fileInfo['uptype'])->path($fileInfo['path']);
    }
    /**
     * 获取访问路径
     * @param string $name 文件名
     * @param string $upType 存储类型
     * @return string
     */
    public function url($name, $upType = '')
    {
        if (empty($upType)) {
            $upType = $this->upType;
        }
        $config = Filesystem::disk($upType)->getConfig();
        if ($upType == 'safe') {
            return $name;
        } else {
            if (Filesystem::getDiskConfig($upType, 'type') == 'local') {
                return $this->app->request->domain() . $config->get('url') . '/' . $name;
            } else {
                $domain = config('filesystem.disks.' . $upType . '.domain');
                return $domain . '/' . $name;
            }
        }
    }

    /**
     * 合并分片文件
     * @param string $chunkSaveDir 分片保存目录
     * @param string $filename 文件名
     * @param mixed $totalChunks
     * @param string $saveDir 指定保存目录
     * @param bool $isUniqidmd5 是否唯一文件名
     * @return bool|string
     */
    protected function merge($chunkSaveDir, $filename, $totalChunks, $saveDir, $isUniqidmd5)
    {
        set_time_limit(0);
        $dir = Filesystem::disk($this->upType)->path('');

        $chunkSaveDir = $dir . $chunkSaveDir;
        $extend = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        if ($isUniqidmd5 == 'true') {
            $saveName = $saveDir . md5(uniqid() . $filename) . '.' . $extend;
        } else {
            $saveName = $saveDir . $filename;
        }
        $put_filename = $dir . $saveName;
        if (file_exists($put_filename)) {
            unlink($put_filename);
        }
        $names = str_split(md5($filename), 16);
        for ($i = 1; $i <= $totalChunks; $i++) {
            $filenameChunk = $chunkSaveDir . DIRECTORY_SEPARATOR . $names[1] . $i;
            $fileData = file_get_contents($filenameChunk);
            file_exists(dirname($put_filename)) || mkdir(dirname($put_filename), 0755, true);
            $res = file_put_contents($put_filename, $fileData, FILE_APPEND);
        }
        array_map('unlink', glob("{$chunkSaveDir}/*"));
        rmdir($chunkSaveDir);
        if ($res) {
            $this->compressImage($put_filename);
            $url =  $this->url($saveName);
            $this->saveData($filename,$filename,$url,$saveName,$extend);
            return $url;
        } else {
            return false;
        }
    }

    /**
     * 压缩图片
     * @param string $filename 文件路径
     */
    private function compressImage($filename)
    {
        if (file_exists($filename)) {
            $quality = Filesystem::getDiskConfig('local', 'quality', 90);
            [$width, $height, $type, $attr] = getimagesize($filename);

            if ($type > 1 && $type < 17 && $quality) {
                $extension = image_type_to_extension($type, false);
                $fun = "imagecreatefrom" . $extension;
                $image = $fun($filename);
                $image_thump = imagecreatetruecolor($width, $height);
                if ($type == 3) {
                    $alpha = imagecolorallocatealpha($image_thump, 0, 0, 0, 127);
                    imagefill($image_thump, 0, 0, $alpha);
                    imagesavealpha($image_thump, true);
                }
                imagecopyresampled($image_thump, $image, 0, 0, 0, 0, $width, $height, $width, $height);
                imagedestroy($image);
                $funcs = "image" . $extension;
                if ($type == 2) {
                    $funcs($image_thump, $filename, $quality);
                } else {
                    $funcs($image_thump, $filename);
                }
                imagedestroy($image_thump);
            }
        }
    }

    /**
     * 清理已删除文件
     * @throws \League\Flysystem\FileNotFoundException
     */
    public function clear()
    {
        $model =  config(Admin::getAppName().'.database.file_model');
        $model::where('is_delete', 1)->chunk(100, function ($files) {
            foreach ($files as $file) {
                try {
                    $filePath = config('filesystem.disks.'.$file['uptype'].'.root').DIRECTORY_SEPARATOR.Admin::getAppName().DIRECTORY_SEPARATOR.$file['path'];
                    if(is_file($filePath) && unlink($filePath)){
                        $file->delete();
                    }else{
                        $res = Filesystem::disk($file['uptype'])->delete($file['path']);
                        if ($res) {
                            $file->delete();
                        }
                    }
                }catch (\Exception $exception){

                }
            }
        });
    }

    /**
     * 注册上传路由
     */
    public function registerRoute()
    {
        $this->app->route->post('eadmin/uploadAfter',function (){
            $data = $this->app->request->post();
            $data['admin_id'] = Admin::id();
            SystemFile::create($data);
            return json(['code' => 200, 'message' => '上传成功']);
        });
        $this->app->route->get('eadmin/uploadConfig', function () {
            $diskType = config('admin.uploadDisks');
            $config = config('filesystem.disks.' . $diskType);
            $data = $config;
            if ($config['type'] == 'qiniu') {
                $data['bucket'] = $config['bucket'];
                $data['domain'] = $config['domain'];
                Filesystem::disk('qiniu')->addPlugin(new UploadToken());
                $data['uploadToken'] = Filesystem::disk('qiniu')->getUploadToken(null, 3600 * 3);
            } else {
                $data['url'] = request()->domain() . '/eadmin/upload';
            }
            return json($data);
        });
        $this->app->route->get('eadmin/download', function () {
            $filename = $this->app->request->param('filename');
            $path = $this->app->request->param('path');
            return download($path, $filename);
        });
        $this->app->route->any('eadmin/upload', function () {
            $file = $this->app->request->file('file');
            $filename = $this->app->request->param('filename');
            $chunks = $this->app->request->param('totalChunks');
            $chunk = $this->app->request->param('chunkNumber');
            $saveDir = $this->app->request->param('saveDir', '/');
            $totalSize = $this->app->request->param('totalSize');
            $chunkSize = $this->app->request->param('chunkSize');
            $isUniqidmd5 = $this->app->request->param('isUniqidmd5', false);
            $upType = $this->app->request->param('upType', 'local');
            if ($isUniqidmd5 == 'true') {
                $isUniqidmd5 = true;
            } else {
                $isUniqidmd5 = false;
            }
            if ($this->app->request->method() == 'POST' && empty($chunk)) {
                $res = Admin::file()->upload($file, $filename, $saveDir . 'editor', $upType, $isUniqidmd5);
                if (!$res) {
                    return json(['code' => 999, 'message' => '上传失败'], 404);
                } else {
                    return json(['code' => 200, 'data' => $res], 200);
                }
            }
            $res = Admin::file()->chunkUpload($file, $filename, $chunk, $chunks, $chunkSize, $totalSize, $saveDir, $isUniqidmd5, $upType);

            if ($this->app->request->method() == 'POST') {
                if (!$res) {
                    return json(['code' => 999, 'message' => '上传失败'], 404);
                } elseif ($res !== true) {
                    return json(['code' => 200, 'data' => $res], 200);
                } elseif ($res === true) {
                    return json(['code' => 202, 'message' => '分片上传成功'], 202);
                }
            } else {

                if ($res === -1) {
                    return json(['code' => 999, 'message' => '文件名重复,请重命名文件重新上传'], 404);
                } elseif ($res === true) {
                    return json(['code' => 202, 'data' => $res, 'message' => '分片秒传成功'], 202);
                } elseif ($res) {
                    return json(['code' => 201, 'data' => $res, 'message' => '秒传成功'], 201);
                } else {
                    return json(['code' => 203, 'message' => '请重新上传分片'], 203);
                }
            }
        });
    }

    function getSize($filesize)
    {
        if ($filesize >= 1073741824) {
            $filesize = round($filesize / 1073741824 * 100) / 100 . ' GB';
        } elseif ($filesize >= 1048576) {
            $filesize = round($filesize / 1048576 * 100) / 100 . ' MB';
        } elseif ($filesize >= 1024) {
            $filesize = round($filesize / 1024 * 100) / 100 . ' KB';
        } else {
            $filesize = $filesize . ' 字节';
        }
        return $filesize;
    }
}
