<?php

class lucky 
{
	const PageSize = 1000;

	const UrlPattern = "https://m.55128.cn/kjh/ssq-history-{page_size}.htm";

	const WatchAction = 'watch';
	const GuessAction = 'guess';
	const PrintFirstAction = 'printFirst';
	const PrintAllAction = 'printAll';
	const PrintFirstStepAction = 'printFirstStep';
	const PrintAllUniqueNumsAction = 'printAllUniqueNums';
	const MonitorAction = 'monitor';
	const GetLuckyNumsAction = 'luckynumber';

	private $is_refresh = false;

	private $action = null;

	private $data_set_lenth = 120;

	private $abandon_latest_data = false;

	public function __construct($is_refresh = false, $action = self::WatchAction, $data_set_lenth = 120, $abandon_latest_data = false)
	{
		$this->is_refresh = $is_refresh;
		$this->action = $action;
		$this->data_set_lenth = $data_set_lenth;
		$this->abandon_latest_data = $abandon_latest_data;
	}

	private function getUrl()
	{
		return str_replace("{page_size}", self::PageSize, self::UrlPattern);
	}

	private function parse($html)
	{
		if ( !preg_match_all("/<div class=\"kj-detail\">(.*?)<\/div>/is", $html, $matches)) {
			die("parse error");
		}

		if (empty($matches[1])) {
			die("html struct error");
		}

		$arr = [];
		foreach ($matches[1] as $key => $value) {
			if ( !preg_match_all("/\d+/is", $value, $match)) {
				die("get number error");		
			}

			array_push($arr, $match[0]);
		}

		return $arr;
	}

	private function request()
	{
		$url = $this->getUrl();
		$html = file_get_contents("./html_cache.txt");
		if ($html && !$this->is_refresh) {
			return $html;
		}

		$ch = curl_init();
	    curl_setopt($ch, CURLOPT_URL, $url);
	    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
	    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 30);
	    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
	    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
	    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
	    if ($refer) {
	        curl_setopt($ch, CURLOPT_REFERER, $refer);
	    }
	    if ($cookie) {
	        $headers = array(
	            "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/74.0.3729.169 Safari/537.36",
	            "{$cookie}",
	        );

	        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
	    }

	    $response = curl_exec($ch);
	    curl_close($ch);

	    file_put_contents("./html_cache.txt", $response);

	    return $response;
	}

	private function getPracticeDataSet($data)
	{
		if ($this->abandon_latest_data) {
			array_shift($data);
		}

		return array_slice($data, 0, $this->data_set_lenth);
	}

	private function getPossibleLongestRedStep()
	{
		$range = array_merge(array_pad([], 13, 9), array_pad([], 12, 8), array_pad([], 11, 10), array_pad([], 8, 11));
		shuffle($range);

		return current($range);
	}

	private function getPossibleBlueStep()
	{
		$range = array_merge(array_pad([], 9, 1), array_pad([], 7, 6), array_pad([], 6, 2), array_pad([], 6, 5), array_pad([], 5, 7));
		shuffle($range);

		return current($range);
	}

	public function printFirst($data)
	{
		echo implode(" ", $data[0]);die;
	}

	public function printFirstStep($data)
	{
		$steps = [];
		$datum = $data[0];
		foreach ($datum as $index => $number) {
			for ($j = 1; $j < self::PageSize; $j++) { 
				$search = $data[$j];
				if (in_array($number, $search)) {
					array_push($steps, $j - $i);
					break;
				}
			}
		}

		echo implode(" ", $steps);die;	
	}

	public function getRedNumbers()
	{
		$numbers = [];
		for ($i=1; $i <= 33; $i++) { 
			if ($i <= 9) {
				array_push($numbers, "0{$i}");
				continue;
			}

			array_push($numbers, "{$i}");
		}

		return $numbers;
	}

	public function getBlueNumbers()
	{
		$numbers = [];
		for ($i=1; $i <= 16; $i++) { 
			if ($i <= 9) {
				array_push($numbers, "0{$i}");
				continue;
			}

			array_push($numbers, "{$i}");
		}

		return $numbers;
	}

	public function getMustSelection($data)
	{
		$array_intersect = array_intersect(array_slice($data[0], 0, 6), array_slice($data[1], 0, 6));


		return [array_diff($data[0], $array_intersect), array_diff($data[1], $array_intersect)];
	}

	// only 7% red ball will apear one stituation that 5 number at the same
	public function checkComboHasExist($combo, $data)
	{
		unset($combo[6]);
		foreach ($data as $key => $value) {
			unset($value[6]);
			if (count(array_intersect($combo, $value)) >= 5) {
				return true;
			}
		}

		return false;
	}

	public function getGuessCombo($data, $show_data = true)
	{
		list ($must_selection_1, $must_selection_2) = $this->getMustSelection($data);
		shuffle($must_selection_2);
		shuffle($must_selection_1);

		$numbers = [];
		for ($i = 0; $i < count($must_selection_1); $i++) { 
			$number = $this->getPossibleCombo4($data, $i, $must_selection_1, $must_selection_2);
			if ( !$number) {
				continue;
			}

			array_push($numbers, $number);
		}

		if (count($numbers) < 5) {
			for ($i = 0; $i < count($must_selection_1); $i++) { 
				$number = $this->getPossibleCombo1($data, $i, $must_selection_1, $must_selection_2);
				if ( !$number) {
					continue;
				}

				array_push($numbers, $number);
			}
		}

		if ($show_data) {
			foreach ($numbers as $value) {
				echo implode("  ", $value). PHP_EOL;
			}
		}

		return $numbers;
	}

	public function getPossibleCombo($data, $serial_no = 0, $must_selection_1, $must_selection_2)
	{
		$red_balls = [];

		// 38% 同时出现这种情况
		$red_ball_1 = $must_selection_1[$serial_no];
		$red_ball_2 = $must_selection_2[$serial_no];

		// 28% 出现这种情况
		$unused_balls = $this->getRecentUnUsedNums($data, 0, 10);
		shuffle($unused_balls);
		$red_ball_3 = current($unused_balls);

		$exclude_red_balls = array_diff(array_merge($must_selection_1, $must_selection_2), [$red_ball_1, $red_ball_2]);
		$possible_red_balls = array_diff($this->getRedNumbers(), $exclude_red_balls, [$red_ball_1, $red_ball_2, $red_ball_3]);
		sort($possible_red_balls);
		$exclude_indexes = [];
		array_push($red_balls, $red_ball_1, $red_ball_2, $red_ball_3);
		for ($i = 0; $i < 3; $i++) { 
			while (true) {
				$indexes = mt_rand(0, count($possible_red_balls) - 1);
				if (in_array($indexes, $exclude_indexes)) {
					continue;
				}

				array_push($exclude_indexes, $indexes);
				array_push($red_balls, $possible_red_balls[$indexes]);
				break;
			}
		}

		$blue_ball = null;
		$exclude_blue_balls = array_merge($data[0], $data[1]);
		$possible_steps = [2, 6, 9];
		shuffle($possible_steps);
		foreach ($possible_steps as $step) {
			$datum = $data[$step];
			shuffle($datum);
			foreach ($datum as $ball) {
				if (!in_array($ball, $exclude_blue_balls) && $ball <= 16) {
					$blue_ball = $ball;
					break;
				}
			}
		}

		if ( !$blue_ball) {
			return false;
		}

		sort($red_balls);

		return array_merge($red_balls, [$blue_ball]);
	}

	public function getPossibleCombo1($data, $serial_no = 0, $must_selection_1, $must_selection_2)
	{
		$red_balls = [];

		// 38% 同时出现这种情况
		$red_ball_1 = $must_selection_1[$serial_no];
		$red_ball_2 = $must_selection_2[$serial_no];

		// 28% 出现这种情况
		$unused_balls = $this->getRecentUnUsedNums($data, 0, 10);
		shuffle($unused_balls);
		$red_ball_3 = current($unused_balls);

		shuffle($must_selection_1);
		$possible_red_ball4s = array_diff($must_selection_1, [$red_ball_1]);
		$red_ball_4 = current($possible_red_ball4s);

		$exclude_red_balls = array_diff(array_merge($must_selection_1, $must_selection_2), [$red_ball_1, $red_ball_2, $red_ball_4]);
		$possible_red_balls = array_diff($this->getRedNumbers(), $exclude_red_balls, [$red_ball_1, $red_ball_2, $red_ball_3, $red_ball_4]);
		sort($possible_red_balls);
		$exclude_indexes = [];
		array_push($red_balls, $red_ball_1, $red_ball_2, $red_ball_3, $red_ball_4);
		for ($i = 0; $i < 2; $i++) { 
			while (true) {
				$indexes = mt_rand(0, count($possible_red_balls) - 1);
				if (in_array($indexes, $exclude_indexes)) {
					continue;
				}

				array_push($exclude_indexes, $indexes);
				array_push($red_balls, $possible_red_balls[$indexes]);
				break;
			}
		}

		$blue_ball = null;
		$exclude_blue_balls = array_merge($data[0], $data[1]);
		$possible_steps = [2, 6, 9];
		shuffle($possible_steps);
		foreach ($possible_steps as $step) {
			$datum = $data[$step];
			shuffle($datum);
			foreach ($datum as $ball) {
				if (!in_array($ball, $exclude_blue_balls) && $ball <= 16) {
					$blue_ball = $ball;
					break;
				}
			}
		}

		if ( !$blue_ball) {
			return false;
		}

		// sort($red_balls);

		return array_merge($red_balls, [$blue_ball]);
	}

	public function getPossibleCombo2($data, $serial_no = 0, $must_selection_1, $must_selection_2)
	{
		$red_balls = [];

		// 38% 同时出现这种情况
		shuffle($must_selection_1);
		$red_ball_1 = $must_selection_1[$serial_no];
		array_push($red_balls, $red_ball_1);

		$yes_or_no = mt_rand(0, 1);
		if ($yes_or_no == 1) {
			shuffle($must_selection_2);
			$red_ball_2 = $must_selection_2[$serial_no];
			array_push($red_balls, $red_ball_2);
		}

		$yes_or_no = mt_rand(0, 1);
		if ($yes_or_no == 1) {
			$unused_balls = $this->getRecentUnUsedNums($data, 0, 10);
			if ($unused_balls) {
				$unused_balls = array_diff($unused_balls, $red_balls);
				shuffle($unused_balls);
				$red_ball_3 = current($unused_balls);
				array_push($red_balls, $red_ball_3);
			}
		}

		$yes_or_no = mt_rand(0, 1);
		if ($yes_or_no == 1) {
			$unused_balls = $this->getRecentUnUsedNums($data, 0, 7);
			if ($unused_balls) {
				$unused_balls = array_diff($unused_balls, $red_balls);
				shuffle($unused_balls);
				$red_ball_4 = current($unused_balls);
				array_push($red_balls, $red_ball_4);
			}
		}

		$exclude_red_balls = array_diff(array_merge($must_selection_1, $must_selection_2), $red_balls);
		$possible_red_balls = array_diff($this->getRedNumbers(), $exclude_red_balls, $red_balls);
		$_count = 7 - count($red_balls);
		sort($possible_red_balls);
		$exclude_indexes = [];
		for ($i = 0; $i < $_count; $i++) { 
			while (true) {
				$indexes = mt_rand(0, count($possible_red_balls) - 1);
				if (in_array($indexes, $exclude_indexes)) {
					continue;
				}

				array_push($exclude_indexes, $indexes);
				array_push($red_balls, $possible_red_balls[$indexes]);
				$sum++;
				break;
			}
		}

		$blue_ball = null;
		$exclude_blue_balls = array_merge($data[0], $data[1]);
		$possible_steps = [2, 6, 9];
		shuffle($possible_steps);
		foreach ($possible_steps as $step) {
			$datum = $data[$step];
			shuffle($datum);
			foreach ($datum as $ball) {
				if (!in_array($ball, $exclude_blue_balls) && $ball <= 16) {
					$blue_ball = $ball;
					break;
				}
			}
		}

		if ( !$blue_ball) {
			return false;
		}

		sort($red_balls);

		return array_merge($red_balls, [$blue_ball]);
	}

	public function getPossibleCombo3($data, $serial_no = 0, $must_selection_1, $must_selection_2)
	{
		$red_balls = [];

		// 38% 同时出现这种情况
		$red_ball_1 = $must_selection_1[$serial_no];

		$selection_3 = $data[2];
		$selection_4 = $data[3];
		$selection_5 = $data[4];
		$selection_6 = $data[5];
		$selection_7 = $data[6];
		shuffle($selection_3);
		shuffle($selection_4);
		shuffle($selection_5);
		shuffle($selection_6);
		shuffle($selection_7);

		foreach ($must_selection_2 as $value) {
			if ( !in_array($value, $red_balls)) {
				array_push($red_balls, $value);				
				break;
			}
		}

		foreach ($selection_3 as $value) {
			if ( !in_array($value, $red_balls)) {
				array_push($red_balls, $value);				
				break;
			}
		}

		foreach ($selection_4 as $value) {
			if ( !in_array($value, $red_balls)) {
				array_push($red_balls, $value);				
				break;
			}
		}

		foreach ($selection_5 as $value) {
			if ( !in_array($value, $red_balls)) {
				array_push($red_balls, $value);				
				break;
			}
		}

		foreach ($selection_6 as $value) {
			if ( !in_array($value, $red_balls)) {
				array_push($red_balls, $value);				
				break;
			}
		}

		foreach ($selection_7 as $value) {
			if ( !in_array($value, $red_balls)) {
				array_push($red_balls, $value);				
				break;
			}
		}

		return $red_balls;
	}

	// the highest rate
	public function getPossibleCombo4($data)
	{
		$data = array_slice($data, 0, 3);
		$balls = [];
		foreach ($data as $key => $value) {
			$balls = array_merge($balls, $value);
		}
		$balls = array_unique($balls);

		$possible_balls = [];
		while (true) {
			shuffle($balls);
			if (count($possible_balls) == 7) {
				break;
			}

			if (count($possible_balls) < 6) {
				$ball = $balls[0];
				$balls = array_slice($balls, 1);
				array_push($possible_balls, $ball);
				sort($possible_balls);
				continue;
			}

			$ball = $balls[0];
			if ($ball <= 16) {
				$balls = array_slice($balls, 1);
				array_push($possible_balls, $ball);
				continue;
			}
		}

		return $possible_balls;
	}

	public function getPossibleCombo5($data)
	{
		$first_rows = $data[0];
		unset($first_rows[6]);
		shuffle($first_rows);
		$ball_1 = $first_rows[0];

		$possible_balls = [];
		foreach (array_slice($data, 1, 3) as $key => $value) {
			$possible_balls = array_merge($possible_balls, $value);
		}

		$possible_balls = array_unique($possible_balls);

	}

	public function getRecentUnUsedNums($data, $start = 0, $rows = 10)
	{
		$numbers = $this->getAllUniqueNums($data, $start, $rows);
		
		return array_diff($this->getRedNumbers(), $numbers);
	}

	public function getAllUniqueNums($data, $start = 0, $rows = 10)
	{
		$data = array_slice($data, $start, $rows);
		$numbers = [];
		foreach ($data as $key => $value) {
			$numbers = array_merge($numbers, $value);
		}
		$numbers = array_unique($numbers);
		sort($numbers);

		return $numbers;
	}

	public function printAllUniqueNums($data, $start = 0, $rows = 9)
	{
		$numbers = $this->getAllUniqueNums($data, $start, $rows);
		echo "number count: ". PHP_EOL. count($numbers). PHP_EOL;
		echo "number list:". PHP_EOL;
		echo implode("  ", $numbers). PHP_EOL;
		echo "number not on the list:". PHP_EOL;
		echo implode("  ", array_diff($this->getRedNumbers(), $numbers));
		echo PHP_EOL;
		die;
	}

	public function printAll($data)
	{
		foreach ($data as $key => $value) {
			echo implode(" ", $value);
			echo PHP_EOL;
		}
		die;
	}

	public function monitor($data)
	{		
		$success_nums = 0;
		for ($i = 0; $i < 50; $i++) { 
			$the_numbers = $data[$i];
			$data_sets = array_slice($data, $i, 100);
			$try = 0;
			while (true) {
				if ($try >= 100) {
					echo "try: {$try} failure". PHP_EOL;
					break;
				}

				$numbers = $this->getGuessCombo($data_sets, false);
				foreach ($numbers as $key => $value) {
					if (count(array_intersect($value, $the_numbers)) >= 7) {
						echo "{$i} try: {$try} success". PHP_EOL;
						echo implode(" ", $the_numbers). PHP_EOL;
						echo implode(" ", $value). PHP_EOL;
						$success_nums++;
						break 2;
					}
				}

				$try++;
			}
		}
		echo "success: {$success_nums}". PHP_EOL;
	}

	public function getLuckyNumbers($data)
	{
		while (true) {
			$lucky_number = mt_rand(1, 100);
			if ( !strstr($lucky_number, "7")) {
				continue;
			}

			$numbers = $this->getGuessCombo($data, false);
			if ($lucky_number == 7) {
				$this->printAll($numbers);
				break;
			}
		}
	}

	public function getRangeNumber($low, $high)
	{
		$range = range($low, $high);
		foreach ($range as $key => &$value) {
			if ($value < 10) {
				$value = "0{$value}";
			}
		}

		return $range;
	}

	public function watch($data)
	{
		$total = 0;
		$len = 1000;
		// $rows = [];
		// $argv_rows = [];
		// $longest_steps = [];
		// $longest_steps_sum = [];
		// $blue_step = [];
		// $sum = [];
		// $count = 0;
		// $exclude = [];
		$sortss = [];
		$sortaa = [];

		// $possible = array_slice($data, 0, 5);
		// $a = [];
		// foreach ($possible as $key => $value) {
		// 	$a = array_merge($a, $value);
		// }
		// $a = array_unique($a);
		// echo implode("  ", $a);die;

		$combo_apear_sort = [];
		// $range = [
		// 	["01", "02", "03", "04", "05"],
		// 	["06", "07", "10", "09", "05"],
		// 	["14", "15", "13", "11", "12"],
		// 	["22", "19", "23", "20", "17"],
		// 	["27", "26", "25", "28", "24"],
		// 	["33", "32", "31", "30", "29"],
		// ];

		$range = [
			$this->getRangeNumber(1, 6),
			$this->getRangeNumber(5, 12),
			$this->getRangeNumber(9, 18),
			$this->getRangeNumber(16, 24),
			$this->getRangeNumber(20, 30),
			$this->getRangeNumber(28, 33),
		];


		for ($i = 0; $i < $len; $i++) { 
			$frontend_balls = $data[$i];
			unset($frontend_balls[6]);
			$same = true;
			foreach ($frontend_balls as $key => $value) {
				$v = $range[$key];
				if ( !in_array($value, $v)) {
					$same = false;
					break;
				}
			}

			if ($same) {
				echo implode("  ", $frontend_balls). PHP_EOL;
			}

			// unset($frontend_balls[6]);
			// for ($j=$i + 1; $j < $len - 1; $j++) { 
			// 	$backend_balls = $data[$j];
			// 	unset($backend_balls[6]);
			// 	if (count(array_intersect($frontend_balls, $backend_balls)) >= 5) {
			// 		echo "{$i}:  ". implode(" ", $frontend_balls). PHP_EOL;
			// 		echo "{$j}:  ". implode(" ", $backend_balls). PHP_EOL;
			// 		echo PHP_EOL;
			// 		// $number++;
			// 		$combo_apear_sort[$i] += 1;
			// 		$combo_apear_sort[$j] += 1;
			// 	}
			// }


			// $frontend_balls = $data[$i];
			// $backend_balls = $data[$i + 1];
			// unset($frontend_balls[6], $backend_balls[6]);
			// $array_intersect = array_intersect($frontend_balls, $backend_balls);
			// if ($array_intersect) {
			// 	$index1 = array_search(current($array_intersect), $backend_balls);
			// 	$index2 = array_search(current($array_intersect), $frontend_balls);
			// 	$sortss[$index1 + 1] += 1;
			// 	$sortaa[current($array_intersect)] += 1;
			// 	// echo "frontend: ". ($index2 + 1)  ."          backend: ". ($index1 + 1) . "      number:".current($array_intersect) .PHP_EOL;
			// 	// if (($index1 + 1) == 2) {
			// 	if (current($array_intersect) == "03" && ($index1 + 1) == 2) {
			// 		echo implode(" ", $frontend_balls). PHP_EOL;
			// 		echo implode(" ", $backend_balls). PHP_EOL;
			// 		// if (current($array_intersect) == "03") {
			// 		// 	echo "bingo". PHP_EOL;
			// 		// }
			// 		echo PHP_EOL;
			// 	}
			// }


			// $biaoben = $data[$i];
			// $big_data = [];
			// for ($j = 1; $j <= 5; $j++) { 
			// 	$big_data = array_merge($big_data, $data[$i + $j]);
			// 	if (count(array_diff($biaoben, $big_data)) <= 2) {
			// 		$unique_nums = count(array_unique($big_data));
			// 		$unused_nums_list = $this->getRecentUnUsedNums(array_slice($data, $i + 1, $j));
			// 		$unused_nums = count($unused_nums_list);
			// 		$in_list_nums = count(array_intersect($unused_nums_list, $biaoben));
			// 		echo "第{$i} 全部数字在 {$j} 层内，一共出现数字个数: {$unique_nums}, 之前未出现的数字有{$unused_nums}个，命中个数: {$in_list_nums} 个". PHP_EOL;
			// 		break;
			// 	}
			// }

			// $boolean = array_intersect($data[$i], $data[$i + 4]) 
			// && array_intersect($data[$i], $data[$i + 1]) 
			// && array_intersect($data[$i], $data[$i + 2]) 
			// && array_intersect($data[$i], $data[$i + 3]) 
			// && array_intersect($data[$i], $data[$i + 5])
			// && array_intersect($data[$i], $data[$i + 6]);
			// $array_intersect1 = array_intersect($data[$i], $data[$i + 1]);
			// $array_intersect2 = array_intersect($data[$i], $data[$i + 2]);
			// $array_intersect3 = array_intersect($data[$i], $data[$i + 3]);
			// $array_intersect4 = array_intersect($data[$i], $data[$i + 4]);
			// $array_intersect5 = array_intersect($data[$i], $data[$i + 5]);
			// $array_intersect6 = array_intersect($data[$i], $data[$i + 6]);
			// $result = array_intersect($array_intersect1, $array_intersect2, $array_intersect3, $array_intersect4, $array_intersect5, $array_intersect6);
			// if ($boolean && !$result) {
			// 	$count += 1;
			// }

			// var_dump($data[$i], $this->getRecentUnUsedNums($data, $i + 1, 10));die;
			// if (array_intersect($data[$i], $this->getRecentUnUsedNums($data, $i + 1, 10)) && count($this->getRecentUnUsedNums($data, $i + 1, 10)) <= 4) {
			// 	echo implode(" ", $this->getRecentUnUsedNums($data, $i + 1, 10)). '    ->     ' . implode(" ", array_intersect($data[$i], $this->getRecentUnUsedNums($data, $i + 1, 10))). PHP_EOL;
			// 	// echo implode(" ", array_intersect($data[$i], $this->getRecentUnUsedNums($data, $i + 1, 10))). PHP_EOL;
			// 	// echo implode(" ", $this->getRecentUnUsedNums($data, $i + 1, 10)). PHP_EOL;
			// 	$sum[count((array_intersect($data[$i], $this->getRecentUnUsedNums($data, $i + 1, 10))))] += 1;
			// 	$count += 1;
			// } else {
			// 	echo "------------". PHP_EOL;
			// }

			// $datum1 = array_slice($data[$i], 0, 6);
			// $datum2 = array_slice($data[$i+1], 0, 6);
			// $datum3 = array_slice($data[$i+2], 0, 6);
			// $array_intersect = array_intersect($datum1, $datum3);
			// if ($array_intersect) {
			// 	echo implode("  ", $array_intersect). PHP_EOL;
			// 	$sum[count($array_intersect)] += 1;
			// } else {
			// 	echo "------------". PHP_EOL;
			// }
			// if (array_intersect($datum1, $datum2, $datum3) && !in_array($i, $exclude)) {
			// 	$count += 1;
			// 	array_push($exclude, $i, $i+1, $i+2);
			// }

			// $combo = $data[$i];
			// $blue_ball = $combo[6];
			// for ($j=$i + 1; $j < self::PageSize; $j++) { 
			// 	if ($blue_ball == $data[$j][6]) {
			// 		// array_push($blue_step, $j - $i);
			// 		$blue_step[$j - $i] += 1;
			// 		break;
			// 	}
			// }

			// $combo = $data[$i];
			// $steps = [];
			// $datum = array_slice($combo, 0, 6);
			// $longest_steps_sign = null;
			// $longest_step = 0;
			// foreach ($datum as $index => $number) {
			// 	for ($j = $i + 1; $j < self::PageSize; $j++) { 
			// 		$search = $data[$j];
			// 		if (in_array($number, $search)) {
			// 			array_push($steps, $j - $i);
			// 			if (max($steps) == $j - $i) {
			// 				$longest_steps_sign = $index == 6 ? "blue" : "red";
			// 				$longest_step = max($longest_step, $j - $i);
			// 			}
			// 			break;
			// 		}
			// 	}
			// }

			// sort($steps);
			// array_push($steps, $longest_steps_sign);
			// array_push($longest_steps, $longest_step);
			// $longest_steps_sum[$longest_step] += 1;
			// echo implode(" ", $steps). PHP_EOL;
			// die;
			// array_push($rows, $steps);
			// array_push($argv_rows, intval(array_sum($steps) / 7));
		}

		// arsort($combo_apear_sort);
		// print_r($combo_apear_sort);
		// die;

		// arsort($sortaa);
		// print_r($sortaa);

		// var_dump($count);die;
		// arsort($sum);
		// var_dump($sum);
		// die;

		// arsort($blue_step);
		// print_r($blue_step);die;

		// echo implode(" ", $blue_step). PHP_EOL;
		// echo array_sum($blue_step) / 100;die;

		// arsort($longest_steps_sum);
		// print_r($longest_steps_sum);die;
		// var_dump(array_sum($longest_steps) / 100);

		// $arr = [];
		// foreach ($argv_rows as $key => $value) {
		// 	$arr[$value] += 1;
		// }

		// arsort($arr);
		// print_r($arr);

		// echo implode(" ", $argv_rows). PHP_EOL;


		// $rules = [];
		// foreach ($rows as $key => $combo) {
		// 	for ($i=0; $i < 3; $i++) { 
		// 		$range = array_slice($combo, $i, $i+2);
		// 		$index = implode("_", $range);
		// 		$rules[$index] += 1;
		// 	}
		// }

		// arsort($rules);
		// print_r($rules);
	}

	public function main()
	{
		$data = $this->parse($this->request());
		$data = $this->getPracticeDataSet($data);
		switch ($this->action) {
			case self::WatchAction:
				return $this->watch($data);

			case self::GuessAction:
				return $this->getGuessCombo($data);

			case self::PrintFirstAction:
				return $this->printFirst($data);

			case self::PrintAllAction:
				return $this->printAll($data);

			case self::PrintAllUniqueNumsAction:
				return $this->printAllUniqueNums($data);

			case self::PrintFirstStepAction:
				return $this->printFirstStep($data);

			case self::MonitorAction:
				return $this->monitor($data);
				
			case self::GetLuckyNumsAction:
				return $this->getLuckyNumbers($data);

			default:
				die("without action param");
		}
	}
}

// (new lucky($is_refresh = true, lucky::PrintAllAction, 1000, false))->main();
// die;

(new lucky($is_refresh = false, lucky::WatchAction, 1000, false))->main();
die;

// (new lucky($is_refresh = false, lucky::PrintAllAction, 1000, false))->main();
// die;

// (new lucky($is_refresh = false, lucky::GetLuckyNumsAction, 1000, false))->main();
// die;

// (new lucky($is_refresh = false, lucky::MonitorAction, 1000, false))->main();
