<?php

namespace Eadmin\component;

use Eadmin\Admin;
use Eadmin\component\basic\Dialog;
use Eadmin\component\basic\Drawer;
use Eadmin\component\layout\Column;
use Eadmin\component\layout\Content;
use Eadmin\form\Form;
use think\helper\Str;

abstract class Component implements \JsonSerializable
{
    use Where, ForMap;

    //组件名称
    protected $name;
    //属性
    protected $attribute = [];
    //内容
    protected $content = [];
    //绑定值
    protected $bind = [];
    //属性绑定
    protected $bindAttribute = [];
    //事件
    protected $event = [];
    //自定义指令
    protected $directive = [];
    //双向绑定
    protected $modelBind = [];
    /**
     * 设置标题
     * @param string $title
     * @return $this
     */
    public function title(string $title)
    {
        $this->bind('eadmin_title', $title);
        return $this;
    }
    /**
     * 设置描述
     * @param string $description
     * @return $this
     */
    public function description($description)
    {
        $this->bind('eadmin_description', $description);
        return $this;
    }
    /**
     * 设置属性
     * @param string $name 属性名
     * @param null $value 值
     * @return $this|mixed
     */
    public function attr(string $name, $value = null)
    {
        if (is_null($value)) {
            return $this->attribute[$name] ?? null;
        } else {
            $this->attribute[$name] = $value;
            return $this;
        }
    }

    public function attrs(array $attrs)
    {
        $this->attribute = array_merge($this->attribute, $attrs);
        return $this;
    }

    public function removeAttr($name)
    {
        unset($this->attribute[$name]);
        return $this;
    }

    public function removeBind($name)
    {
        unset($this->bind[$name]);
    }

    public function removeAttrBind($name)
    {
        unset($this->modelBind[$name]);
        unset($this->bindAttribute[$name]);
    }

    /**
     * 绑定属性对应绑定字段
     * @param string $name 属性名称
     * @param string $field 绑定字段名称
     * @param bool $model 是否双向绑定
     */
    public function bindAttr(string $name, $field = null, $model = false)
    {
        if (is_null($field)) {
            return $this->bindAttribute[$name] ?? null;
        } else {
            if ($model) {
                $this->modelBind[$name] = $field;
            }
            $this->bindAttribute[$name] = $field;
            return $this;
        }
    }

    /**
     * 绑定值
     * @param string $name 字段名称
     * @param mixed $value 值
     * @return $this
     */
    public function bind(string $name, $value = null)
    {
        if (is_null($value)) {
            return $this->bind[$name] ?? null;
        } else {
            $this->bind[$name] = $value;
            return $this;
        }
    }

    /**
     * 绑定属性值
     * @param $name
     * @param $value
     * @return string
     */
    protected function bindAttValue($name, $value)
    {
        $field = Str::random(30, 3);
        $this->bind($field, $value);
        $this->bindAttr($name, $field);
        return $field;
    }

    public function __call($name, $arguments)
    {
        if (empty($arguments)) {
            return $this->attr($name, true);
        } else {
            return $this->attr($name, ...$arguments);
        }

    }

    /**
     * @param string $name 指令名称
     * @param string $value 值
     * @param string $argument 参数(可选)
     * @return $this
     */
    public function directive($name, $value, $argument = '')
    {
        $this->directive[] = ['name' => $name, 'argument' => $argument, 'value' => $value];
        return $this;
    }

    public function event($name, array $value)
    {
        $name = ucfirst($name);
        if (isset($this->event[$name])) {
            $this->event[$name] = array_merge($this->event[$name], $value);
        } else {
            $this->event[$name] = $value;
        }
        return $this;
    }

    /**
     * 跳转路径
     * @param string $url
     * @param array $params
     * @return $this
     */
    public function redirect($url, array $params = [])
    {
        if ($params) {
            $url = $url . '?' . http_build_query($params);
        }
        $style = $this->attr('style') ?? [];
        $style = array_merge($style, ['cursor' => 'pointer']);
        $this->attr('style', $style);
        return $this->directive('redirect', $url);
    }

    /**
     * 插槽内容
     * @param mixed $content 内容
     * @param string $name 插槽名称
     * @return $this
     */
    public function content($content, $name = 'default')
    {

        if (is_null($content)) {
            return $this;
        }
        if (is_array($content)) {
            foreach ($content as $item) {
                $this->content($item);
            }
        } else {
            if (!($content instanceof Component)) {
                $content = Admin::dispatch($content);
            }
            if ($content instanceof Form && ($this instanceof Dialog || $this instanceof Drawer)) {
                $field = $this->bindAttr('modelValue');
                $content->eventSuccess([$field => false]);
            }
            $this->content[$name][] = $content;
        }
        return $this;
    }



    public function jsonSerialize()
    {
        return [
            'name' => $this->name,
            'where' => $this->where,
            'map' => $this->map,
            'bind' => $this->bind,
            'attribute' => $this->attribute,
            'modelBind' => $this->modelBind,
            'bindAttribute' => $this->bindAttribute,
            'content' => $this->content,
            'event' => $this->event,
            'directive' => $this->directive,
        ];
    }
}
