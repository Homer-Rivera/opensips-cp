<?php
require_once(__DIR__."/../../../../admin/dashboard/template/widget/widget.php");

class monit_cpu_widget extends widget
{
    public $cpu_res;
	public $box_id;
	public $service;

    function __construct($array) {
        parent::__construct($array['panel_id'], $array['widget_name'], 2,2, $array['widget_name']);
		$this->box_id = $array['widget_box'];
		$this->service = $array['widget_service'];
        $this->set_cpu();
        $this->color = "rgb(207, 207, 207)";
    }

    function get_name() {
        return "CPU monit widget";
    }
    function display_test() {
		echo ('
			<table class="ttable" style="table-layout: fixed;
			width: 110px; height:15px; margin: auto;" cellspacing="0" cellpadding="0" border="0">
			');
		echo ('
			<tr><td class="rowEven"><span style="color:black;">USR: '.$this->cpu_res['usr'].'%</span></td></tr>
			<tr><td class="rowEven"><span style="color:black;">SYS: '.$this->cpu_res['sys'].'%</span></td></tr>
			</tr>  ');
		echo('</table>');
	
	}

	function set_cpu() {
		foreach ($_SESSION['boxes'] as $box) {
			if ($box['id'] == $this->box_id)
				$widget_box = $box;
		}
		$auth_user = $widget_box['monit_user'];
		$auth_pass = $widget_box['monit_pass'];
		$protocol = "http";
		if ($widget_box['monit_ssl'])
			$protocol = "https";
		$host = $widget_box['monit_conn'];


		$auth = base64_encode($auth_user.":".$auth_pass);
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $protocol."://".$host."/_status?service=".$this->service);
		curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "GET");
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
		curl_setopt($ch, CURLOPT_HEADER, FALSE);
		curl_setopt($ch, CURLOPT_HTTPHEADER, array(
			'Accept: application/json',
			'Authorization: Basic '.$auth)                                                                       
		);
		$response = curl_exec($ch);
	
		if($response === false){
			$errors[] = curl_error($ch);
			return false;
		}
	
		$status = curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
	
		curl_close($ch);

		if ($status>=300) {
			$errors[] = "MI HTTP request failed with ".$status." reply";
			return NULL;
		}

		preg_match('/(cpu[\s\r\n]*(?<cpu_info>.*?)[\s\r\n]*memory)/s', $response, $matches);
		$cpu_info = utf8_encode($matches['cpu_info']);
		preg_match_all('/(?<cpu_info>.*?) /', $cpu_info, $matches);
		foreach($matches['cpu_info'] as $key => $entry) {
			preg_match('/(?<value>([0-9]*\.[0-9])*)\%(?<name>.*)/', $entry, $new_matches);
			$this->cpu_res[$new_matches['name']] = utf8_encode($new_matches['value']);
		}
	}

    function echo_content() {
        $this->display_test();
    }

    public static function get_boxes() {
        $boxes_names = [];
        foreach ($_SESSION['boxes'] as $box) {
            $boxes_names[] = $box['id'];
        }
        return $boxes_names;
    }

    function get_as_array() {
        return array($this->get_html(), $this->get_sizeX(), $this->get_sizeY());
    }

    public static function new_form($params = null) {  
		if (is_null($params)) {
			$params['widget_service'] = "localhost.localdomain";
		}
        form_generate_input_text("Title", "", "widget_name", null, $params['widget_name'], 20,null);
		form_generate_input_text("Service", "", "widget_service", null, $params['widget_service'], 20,null);
		form_generate_select("Box", "", "widget_box", null,  $params['widget_box'], self::get_boxes());
	}

}

?>