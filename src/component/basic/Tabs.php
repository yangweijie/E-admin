<?php
/**
 * Created by PhpStorm.
 * User: rocky
 * Date: 2021-01-06
 * Time: 23:30
 */

namespace Eadmin\component\basic;

use Eadmin\component\form\Field;
use think\helper\Str;

/**
 * 标签页
 * Class Tabs
 * @package Eadmin\component\basic
 * @method $this closable(bool $value = true) 标签是否可关闭
 * @method $this addable(bool $value = true) 标签是否可增加
 * @method $this editable(bool $value = true) 标签是否同时可增加和关闭
 * @method $this stretch(bool $value = true) 标签的宽度是否自撑开
 * @method $this type(string $value) 风格类型 card/border-card
 * @method $this tabPosition(string $value) 选项卡所在位置 top/right/bottom/left
 */
class Tabs extends Field
{
	protected $name = 'ElTabs';

	public static function create($field = null, $value = 1)
	{
		return parent::create($field, $value); // TODO: Change the autogenerated stub
	}

	/**
	 * @param string $title   标题
	 * @param mixed  $content 内容
	 * @param mixed  $name    与选项卡绑定值
	 * @param bool   $destroy 是否切换卸载重新加载
	 * @return $this
	 */
	public function pane($title, $content, $name = null, $destroy = true)
	{
		$tabPane = new TabPane();
		if (is_null($name)) {
			$name = $this->getContentCount();
		}
		$tabPane->name($name);
		$tabPane->content($title, 'label');
		$tabPane->content($content);
		$tabPane->lazy();
		$content = end($tabPane->content['default']);
		if (is_object($content) && $destroy) {
			$content->where($this->bindAttr('modelValue'), $this->getContentCount());
            $content->attr('initLoad',true);
		}
		$this->content($tabPane);
		return $this;
	}

	public function getContentCount()
	{
		if (isset($this->content['default'])) {
			return count($this->content['default']) + 1;
		} else {
			return 1;
		}
	}
}
