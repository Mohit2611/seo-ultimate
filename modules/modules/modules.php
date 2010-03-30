<?php
/**
 * Module Manager Module
 * 
 * @version 1.2.1
 * @since 0.7
 */

if (class_exists('SU_Module')) {

class SU_Modules extends SU_Module {
	
	function get_module_title() { return __('Module Manager', 'seo-ultimate'); }
	function get_menu_title() { return __('Modules', 'seo-ultimate'); }
	function get_menu_pos()   { return 10; }
	function is_menu_default(){ return true; }
	
	function init() {
		if ($this->is_action('update')) {
			
			foreach ($_POST as $key => $value) {
				if (substr($key, 0, 3) == 'su-') {
					$key = str_replace(array('su-', '-module-status'), '', $key);
					$value = intval($value);
					
					$this->plugin->dbdata['modules'][$key] = $value;
				}
			}
		}
	}
	
	function admin_page_contents() {
		echo "<p>";
		_e("SEO Ultimate&#8217;s features are located in groups called &#8220;modules.&#8221; By default, most of these modules are listed in the &#8220;SEO&#8221; menu on the left. Whenever you&#8217;re working with a module, you can view documentation by clicking the tabs in the upper-right-hand corner of your administration screen.", 'seo-ultimate');
		echo "</p><p>";
		_e("The Module Manager lets you  disable or hide modules you don&#8217;t use. You can also silence modules from displaying bubble alerts on the menu.", 'seo-ultimate');
		echo "</p>";
		
		$this->admin_form_start(false, false);
		
		$headers = array(
			  __("Status", 'seo-ultimate')
			, __("Module", 'seo-ultimate')
		);
		echo <<<STR
<table class="widefat" cellspacing="0">
	<thead><tr>
		<th scope="col" class="module-status">{$headers[0]}</th>
		<th scope="col" class="module-name">{$headers[1]}</th>
	</tr></thead>
	<tbody>

STR;
		
		$statuses = array(
			  SU_MODULE_ENABLED => __('Enabled', 'seo-ultimate')
			, SU_MODULE_SILENCED => __('Silenced', 'seo-ultimate')
			, SU_MODULE_HIDDEN => __('Hidden', 'seo-ultimate')
			, SU_MODULE_DISABLED => __('Disabled', 'seo-ultimate')
		);
		
		$modules = array();
		
		foreach ($this->plugin->modules as $key => $x_module) {
			$module =& $this->plugin->modules[$key];
			
			//On some setups, get_parent_class() returns the class name in lowercase
			if (strcasecmp(get_parent_class($module), 'SU_Module') == 0 && !in_array($key, array('modules')) && $module->is_independent_module())
				$modules[$key] = $module->get_page_title();
		}
		
		foreach ($this->plugin->disabled_modules as $key => $class) {
			
			if (call_user_func(array($class, 'is_independent_module'))) {
				$title = call_user_func(array($class, 'get_page_title'));
				if (!$title) $title = call_user_func(array($class, 'get_module_title'));
				$modules[$key] = $title;
			}
		}
		
		asort($modules);
		
		//Do we have any modules requiring the "Silenced" column? Store that boolean in $any_hmc
		$any_hmc = false;
		foreach ($modules as $key => $name) {
			if ($this->plugin->call_module_func($key, 'has_menu_count', $hmc) && $hmc) {
				$any_hmc = true;
				break;
			}
		}
		
		foreach ($modules as $key => $name) {
			
			$currentstatus = $this->plugin->dbdata['modules'][$key];
			
			echo "\t\t<tr>\n\t\t\t<td class='module-status' id='module-status-$key'>\n";
			echo "\t\t\t\t<input type='hidden' name='su-$key-module-status' id='su-$key-module-status' value='$currentstatus' />\n";
			
			foreach ($statuses as $statuscode => $statuslabel) {
				
				$hmc = ($this->plugin->call_module_func($key, 'has_menu_count', $_hmc) && $_hmc);
				
				$is_current = false;
				$style = '';
				switch ($statuscode) {
					case SU_MODULE_ENABLED:
						if ($currentstatus == SU_MODULE_SILENCED && !$hmc) $is_current = true;
						break;
					case SU_MODULE_SILENCED:
						if (!$any_hmc) continue 2;
						if (!$hmc) $style = " style='visibility: hidden;'";
						break;
				}
				
				if ($is_current || $currentstatus == $statuscode) $current = ' current'; else $current = '';
				$codeclass = str_replace('-', 'n', strval($statuscode));
				echo "\t\t\t\t\t<span class='status-$codeclass'$style>";
				echo "<a href='javascript:void(0)' onclick=\"javascript:set_module_status('$key', $statuscode, this)\" class='$current'>$statuslabel</a></span>\n";
			}
			
			if ($currentstatus > SU_MODULE_DISABLED) {
				$cellcontent = "<a href='".$this->get_admin_url($key)."'>$name</a>";
			} else
				$cellcontent = $name;
			
			echo <<<STR
				</td>
				<td class='module-name'>
					$cellcontent
				</td>
			</tr>

STR;
		}
		
		echo "\t</tbody>\n</table>\n";
		
		$this->admin_form_end(false, false);
	}
}

} elseif ($_GET['css'] == 'admin') {
	header('Content-type: text/css');
?>

#su-modules td.module-status {
	padding-right: 2em;
}

#su-modules td.module-status input {
	display: none;
}

#su-modules td.module-status a {
	float: left;
	display: block;
	border: 1px solid white;
	padding: 0.3em 0.5em;
	color: #999;
	margin-right: 0.2em;
}

#su-modules td.module-status a:hover {
	border-color: #ccc #666 #666 #ccc;
}

#su-modules td.module-status a.current {
	border-color: #666 #ccc #ccc #666;
}

#su-modules td.module-status .status-10 a.current { color: green; }
#su-modules td.module-status .status-5  a.current { color: black; }
#su-modules td.module-status .status-0 a.current  { color: darkorange; }
#su-modules td.module-status .status-n10 a.current{ color: red; }

<?php
} elseif ($_GET['js'] == 'admin') {
	header('Content-type: text/javascript');
?>

function set_module_status(key, input_value, a_obj) {
	var td_id = "module-status-"+key;
	var input_id = "su-"+key+"-module-status";
	
	jQuery("td#"+td_id+" a").removeClass("current");
	document.getElementById(input_id).value = input_value;
	a_obj.className += " current";
}

<?php
}
?>