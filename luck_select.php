<?php

error_reporting(E_ALL & ~E_NOTICE);

class lucky 
{
    const PageSize = 1000;

    const UrlPattern = "https://m.55128.cn/kjh/ssq-history-{page_size}.htm";

    const WatchAction = 'watch';
    const GuessAction = 'guess';
    const PrintFirstAction = 'printFirst';
    const PrintAllAction = 'printAll';
    const PrintAllD1Action = 'printAllD1';
    const PrintAllD2Action = 'printAllD2';    
    const PrintBlueAction = 'printBlue';
    const PrintFirstStepAction = 'printFirstStep';
    const PrintAllUniqueNumsAction = 'printAllUniqueNums';
    const MonitorAction = 'monitor';
    const GetLuckyNumsAction = 'luckynumber';
    const GetRecentUnUsedAction = 'unused';
    const CheckAction = 'check';

    private $is_refresh = false;

    private $action = null;

    private $data_set_lenth = 120;

    private $abandon_latest_data = false;

    private $only_choose_all = false;

    public function __construct($is_refresh = false, $action = self::WatchAction, $data_set_lenth = 120, $abandon_latest_data = false, $only_choose_all = false)
    {
        $this->is_refresh = $is_refresh;
        $this->action = $action;
        $this->data_set_lenth = $data_set_lenth;
        $this->abandon_latest_data = $abandon_latest_data;
        $this->only_choose_all = $only_choose_all;
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

        if ( !preg_match_all("/<strong>(\d+)<\/strong>/is", $html, $matches)) {
            die("parse error");
        }

        if (empty($matches[1])) {
            die("html struct error");
        }

        $arr1 = $matches[1];

        return array($arr, $arr1);
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
            $number = $this->getPossibleCombo5($data, $i, $must_selection_1, $must_selection_2);
            if ( !$number) {
                continue;
            }

            array_push($numbers, $number);
        }

        // if (count($numbers) < 5) {
        //  for ($i = 0; $i < count($must_selection_1); $i++) { 
        //      $number = $this->getPossibleCombo1($data, $i, $must_selection_1, $must_selection_2);
        //      if ( !$number) {
        //          continue;
        //      }

        //      array_push($numbers, $number);
        //  }
        // }

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
        $range = [
            $this->getRangeNumber(1, 6),
            $this->getRangeNumber(5, 12),
            $this->getRangeNumber(9, 18),
            $this->getRangeNumber(16, 24),
            $this->getRangeNumber(20, 30),
            $this->getRangeNumber(28, 33),
        ];

        $balls = [];
        foreach ($range as $key => $combo) {
            while (true) {
                shuffle($combo);
                $ball = current($combo);
                if (in_array($ball, $balls)) {
                    continue;
                }

                array_push($balls, $ball);
                break;
            }
        }

        return $balls;
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
            unset($value[6]);
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

    public function getRangeNumberList()
    {
        return [
            $this->getRangeNumber(1, 7),
            $this->getRangeNumber(5, 14),
            $this->getRangeNumber(9, 18),
            $this->getRangeNumber(15, 25),
            $this->getRangeNumber(20, 31),
            $this->getRangeNumber(28, 33),
        ];
        // return [
        //     $this->getRangeNumber(1, 10),
        //     $this->getRangeNumber(1, 10),
        //     $this->getRangeNumber(11, 21),
        //     $this->getRangeNumber(11, 21),
        //     $this->getRangeNumber(22, 33),
        //     $this->getRangeNumber(22, 33),
        // ];
    }

    public function printAllDetail1($data)
    {
        $range = $this->getRangeNumberList();
        $sort = [];
        $total_nums = 0;
        $unused_step = 9;
        $l_nums = $t_nums = $u_nums = 0;
        $odd_sort_nums = [];
        $level_and_position_sort = [];
        $level_position_sort = [];
        $pos_combo = [];
        foreach ($data as $key => $value) {
            $_value11 = $value;
            unset($_value11[6]);
            $sum = array_sum($_value11);
            if ($sum < 100) {
                $sum = "0{$sum}";
            }

            $frontend_balls = $value;
            unset($frontend_balls[6]);
            $symbol_list = [];
            $special_nums = [];
            $lianshu_nums = 0;
            $odd_nums = 0;
            $level_steps = [];
            $red_ball_pos_detail = [];
            $red_ball_total_level = 0;
            $level_and_position_arr = [];
            $pos1 = [];
            $pos2 = [];
            foreach ($frontend_balls as $key1 => $value5) {
                $v = $range[$key1];
                if (in_array($value5, $v)) {
                    array_push($symbol_list, 1);
                } else {
                    array_push($symbol_list, 0);
                }
                if (strstr($value5, '6') || strstr($value5, '7') || strstr($value5, '8')) {
                    array_push($special_nums, $value5);
                }
                if ($key1 < 5) {
                    if (abs($value5 - $frontend_balls[$key1 + 1]) == 1) {
                        $lianshu_nums++;
                        $l_nums++;
                    }
                }
                if ($value5 % 2 != 0) {
                    $odd_nums++;
                }
                if ( !in_array(intval($value5 / 10), $level_steps)) {
                    array_push($level_steps, intval($value5 / 10));
                }
                for ($j = $key + 1; $j < count($data) - 9; $j++) { 
                    if ($j - $key > 20) {
                        array_push($pos1, "x");
                        array_push($pos2, "x");
                        break;
                    }
                    $backend_ballsss = array_slice($data[$j], 0, 6);
                    if (in_array($value5, $backend_ballsss)) {
                        $pos = array_search($value5, $backend_ballsss) + 1;
                        $level = $j - $key;
                        array_push($red_ball_pos_detail, str_pad("{$level}", 2, " "). "($pos)");
                        array_push($pos1, $level);
                        array_push($pos2, $pos);
                        $red_ball_total_level += $level;
                        array_push($level_and_position_arr, "{$level}-{$pos}");    
                        $level_position_sort["{$level}-{$pos}"] += 1;
                        break;
                    }
                }
            }

            $level_and_position_sort[implode(" ", array_slice($frontend_balls, 0, 2))] += 1;
            $level_and_position_sort[implode(" ", array_slice($frontend_balls, 1, 2))] += 1;
            $level_and_position_sort[implode(" ", array_slice($frontend_balls, 2, 2))] += 1;
            $level_and_position_sort[implode(" ", array_slice($frontend_balls, 3, 2))] += 1;
            $level_and_position_sort[implode(" ", array_slice($frontend_balls, 0, 3))] += 1;
            $level_and_position_sort[implode(" ", array_slice($frontend_balls, 1, 3))] += 1;
            $level_and_position_sort[implode(" ", array_slice($frontend_balls, 2, 3))] += 1;
            

            if (!in_array(0, $symbol_list)) {
                $middle = str_pad("(ALL)", 5, " ");
                $sort[$sum] += 1;
                $total_nums++;
            } else {
                $middle = str_pad("", 5, " ");
                if ($this->only_choose_all) {
                    continue;
                }
            }

            if ($lianshu_nums > 0) {
                $middle .= str_pad("(L{$lianshu_nums})", 4, " ");
            } else {
                $middle .= str_pad('', 4, " ");
            }

            if ($key < count($data) - $unused_step - 1) {
                $unused_nums = $this->getRecentUnUsedNums($data, $key + 1, $unused_step);
                $array_intersect1 = array_intersect($unused_nums, $value);
                $unused_num_case_lianshu_nums = 0;
                foreach ($array_intersect1 as $key6 => $value6) {
                    $index = array_search($value6, $value);
                    if ($index == 0) {
                        if (abs($value[$index] - $value[$index + 1]) == 1) {
                            $unused_num_case_lianshu_nums++;
                            $u_nums++;
                            break;
                        }
                    } else if ($index == 5) {
                        if (abs($value[$index] - $value[$index - 1]) == 1) {
                            $unused_num_case_lianshu_nums++;
                            $u_nums++;
                            break;
                        }
                    } else if (abs($value[$index] - $value[$index - 1]) == 1 || abs($value[$index] - $value[$index + 1]) == 1) {
                        $unused_num_case_lianshu_nums++;
                        $u_nums++;
                        break;
                    }
                }
            
                if ($unused_num_case_lianshu_nums > 0) {
                    $middle .= str_pad("(U{$unused_num_case_lianshu_nums})", 4, " ");
                } else {
                    $middle .= str_pad("", 4, " ");
                }
            } else {
                $middle .= str_pad("", 4, " ");
            }

            $last_num_cause_lianshu_nums = 0;
            if ($key != count($data) - 1) {
                $value1 = array_slice($value, 0, 6);
                $value2 = array_slice($data[$key + 1], 0, 6);
                $array_intersect = array_intersect($value1, $value2);
                foreach ($array_intersect as $key2 => $value3) {
                    $index = array_search($value3, $value1);
                    if ($index == 0) {
                        if (abs($value[$index] - $value[$index + 1]) == 1) {
                            $last_num_cause_lianshu_nums++;
                            $t_nums++;
                            break;
                        }
                    } else if ($index == 5) {
                        if (abs($value[$index] - $value[$index - 1]) == 1) {
                            $last_num_cause_lianshu_nums++;
                            $t_nums++;
                            break;
                        }
                    } else if (abs($value[$index] - $value[$index - 1]) == 1 || abs($value[$index] - $value[$index + 1]) == 1) {
                        $last_num_cause_lianshu_nums++;
                        $t_nums++;
                        break;
                    }
                }
            }

            if ($last_num_cause_lianshu_nums > 0) {
                $middle .= str_pad("(T{$last_num_cause_lianshu_nums})", 4, " ");
            } else {
                $middle .= str_pad("", 4, " ");
            }

            $odd_sort_nums[$odd_nums] += 1;
            $even_nums = 6 - $odd_nums;
            $count_level_nums = count($level_steps);
            $middle .= str_pad("({$odd_nums}-{$even_nums})(S{$count_level_nums})", 5, " ");

            array_push($frontend_balls, '('.$value[6].')');
            echo str_pad("{$sum}{$middle} : ". implode(" ", $frontend_balls), 26, " ");
            if ($key != count($data) - 1) {
                echo " | SWP: ";
                if ($array_intersect) {
                    foreach ($array_intersect as $key2 => &$value3) {
                        $index = array_search($value3, $value2);
                        $position1 = $index + 1;
                        $index = array_search($value3, $value1);
                        $position2 = $index + 1;
                        $value3 .= "({$position2}-{$position1})";
                    }

                    echo str_pad(implode(" ", $array_intersect), 35, " ");
                } else {
                    echo str_pad("", 35, " ");
                }

                echo " | POS: ";
                echo str_pad(implode(" | ", [implode(" ", $pos1), implode(" ", $pos2)]), 35, " ");
                // if (in_array("4", $pos1) && array_count_values($pos1)['4'] >= 2 && in_array("1", $pos1)) {
                //     echo "[441]";
                // } else {
                //     echo "      ";
                // }
                if (in_array("4", $pos1) && array_count_values($pos1)['4'] >= 2 && in_array("1", $pos1)) {
                    echo "[441]";
                    $__count = array_sum($pos1);
                    $max_pos = max($pos1);
                    if ($max_pos == 14) {
                        array_push($pos_combo, implode(" ", $pos1). "  ({$__count}) (max:{$max_pos})");    
                    }
                    
                } else {
                    echo "      ";
                }
                // echo str_pad(implode(" ", $red_ball_pos_detail), 42, " ");
            }
            echo PHP_EOL;
        }

        echo PHP_EOL;
        $keys = array_keys($sort);
        $average = intval(array_sum($keys) / count($keys));
        echo "Summary:". PHP_EOL;
        echo "  Red Sum:". PHP_EOL;
        echo "      min: ". min($keys). PHP_EOL;
        echo "      max: ". max($keys). PHP_EOL;
        echo "      average: ". $average. PHP_EOL;
        echo "      total_nums: {$total_nums}". PHP_EOL;
        echo "      rate(%): ". ($total_nums / count($data) * 100). PHP_EOL;
        echo PHP_EOL;

        echo "  L:". PHP_EOL;
        echo "      Sums: {$l_nums}". PHP_EOL;
        echo "      rate(%): ".  ($l_nums / count($data) * 100). PHP_EOL;
        echo PHP_EOL;

        echo "  U:". PHP_EOL;
        echo "      Sums: {$u_nums}". PHP_EOL;
        echo "      rate(%): ".  ($u_nums / count($data) * 100). PHP_EOL;
        echo PHP_EOL;

        echo "  T:". PHP_EOL;
        echo "      Sums: {$t_nums}". PHP_EOL;
        echo "      rate(%): ".  ($t_nums / count($data) * 100). PHP_EOL;
        echo PHP_EOL;

        echo "  Current Unused:". PHP_EOL;
        $unused_nums = $this->getRecentUnUsedNums($data, 0, $unused_step);
        echo "      unused_nums(0-{$unused_step}): ". implode(" ", $unused_nums). PHP_EOL;

        $high = $unused_step - 1;
        $unused_nums1 = $this->getRecentUnUsedNums($data, 0, $high);
        echo "      unused_nums(0-{$high}): ". implode(" ", $unused_nums1). PHP_EOL;
        echo PHP_EOL;

        echo "  Odd-Even(Num):". PHP_EOL;
        arsort($odd_sort_nums);
        foreach ($odd_sort_nums as $k => $v) {
            $even = 6 - $k;
            echo "      {$k} - {$even}  ($v)". PHP_EOL; 
        }

        arsort($level_and_position_sort);
        print_r($level_and_position_sort);
        echo PHP_EOL. PHP_EOL;

        print_r($pos_combo);
        echo PHP_EOL. PHP_EOL;        
    }

    public function printAllDetail2($data, $lottery_dates)
    {
        $range = $this->getRangeNumberList();
        $sort = [];
        $total_nums = 0;
        $unused_step = 9;
        $l_nums = $t_nums = $u_nums = 0;
        $odd_sort_nums = [];
        $level_position_sort = [];
        $last_sums = $diff = "00";
        $_dd_sort = [];
        $_dd_sort_1 = [];
        $range_combo_sort = [];
        $special_diff_arr = [];
        foreach ($data as $key => $value) {
            $_value11 = $value;
            unset($_value11[6]);
            $sum = array_sum($_value11);
            $sum_range = 0;
            if ($last_sums == 0) {
                $last_sums = $sum;
            } else {
                $diff = abs($sum - $last_sums);
                $last_sums = $sum;
            }
            $sum_range = intval($sum / 10);
            if ($sum < 100) {
                $sum = "0{$sum}";
            }

            $diff_sections = [];
            $frontend_balls = $value;
            unset($frontend_balls[6]);
            $symbol_list = [];
            $special_nums = [];
            $lianshu_nums = 0;
            $odd_nums = 0;
            $level_steps = [];
            $red_ball_pos_detail = [];
            $red_ball_total_level = 0;
            $red_all_in_4_levels = true;
            foreach ($frontend_balls as $key1 => $value5) {
                $v = $range[$key1];
                if (in_array($value5, $v)) {
                    array_push($symbol_list, "*");
                } else {
                    array_push($symbol_list, "0");
                }
                if (strstr($value5, '6') || strstr($value5, '7') || strstr($value5, '8')) {
                    array_push($special_nums, $value5);
                }
                if ($key1 < 5) {
                    if (abs($value5 - $frontend_balls[$key1 + 1]) == 1) {
                        $lianshu_nums++;
                        $l_nums++;
                    }
                }
                if ($value5 % 2 != 0) {
                    $odd_nums++;
                }
                if ( !in_array(intval($value5 / 10), $level_steps)) {
                    array_push($level_steps, intval($value5 / 10));
                }
                for ($j = $key + 1; $j < count($data) - 9; $j++) { 
                    if ($j - $key > 4) {
                        $red_all_in_4_levels = false;
                    }
                    if ($j - $key > 8) {
                        break;
                    }
                    $backend_ballsss = array_slice($data[$j], 0, 6);
                    if (in_array($value5, $backend_ballsss)) {
                        $pos = array_search($value5, $backend_ballsss) + 1;
                        $level = $j - $key;
                        array_push($red_ball_pos_detail, str_pad("{$level}", 2, " "). "($pos)");
                        $red_ball_total_level += $level;
                        $level_position_sort["{$level}-{$pos}"] += 1;
                        break;
                    }
                }
                if (count($data) - $key < 9) {
                    $red_all_in_4_levels = false;
                }
                if ($key1 > 0) {
                    $diff_ = $value5 - $frontend_balls[$key1 - 1];
                    array_push($diff_sections, str_pad("{$diff_}", 2, " "));
                    $_dd_sort_1[$diff_] += 1;
                }
            }

            if (!in_array("0", $symbol_list)) {
                $middle = str_pad("(ALL)", 5, " ");
                $sort[$sum] += 1;
                $total_nums++;
            } else {
                $middle = str_pad("", 5, " ");
                if ($this->only_choose_all) {
                    continue;
                }
            }

            if ($lianshu_nums > 0) {
                $middle .= str_pad("(L{$lianshu_nums})", 4, " ");
            } else {
                $middle .= str_pad('', 4, " ");
            }

            if ($key < count($data) - $unused_step - 1) {
                $unused_nums = $this->getRecentUnUsedNums($data, $key + 1, $unused_step);
                $array_intersect1 = array_intersect($unused_nums, $value);
                $unused_num_case_lianshu_nums = 0;
                foreach ($array_intersect1 as $key6 => $value6) {
                    $index = array_search($value6, $value);
                    if ($index == 0) {
                        if (abs($value[$index] - $value[$index + 1]) == 1) {
                            $unused_num_case_lianshu_nums++;
                            $u_nums++;
                            break;
                        }
                    } else if ($index == 5) {
                        if (abs($value[$index] - $value[$index - 1]) == 1) {
                            $unused_num_case_lianshu_nums++;
                            $u_nums++;
                            break;
                        }
                    } else if (abs($value[$index] - $value[$index - 1]) == 1 || abs($value[$index] - $value[$index + 1]) == 1) {
                        $unused_num_case_lianshu_nums++;
                        $u_nums++;
                        break;
                    }
                }
            
                if ($unused_num_case_lianshu_nums > 0) {
                    $middle .= str_pad("(U{$unused_num_case_lianshu_nums})", 4, " ");
                } else {
                    $middle .= str_pad("", 4, " ");
                }
            } else {
                $middle .= str_pad("", 4, " ");
            }

            $last_num_cause_lianshu_nums = 0;
            if ($key != count($data) - 1) {
                $value1 = array_slice($value, 0, 6);
                $value2 = array_slice($data[$key + 1], 0, 6);
                $array_intersect = array_intersect($value1, $value2);
                foreach ($array_intersect as $key2 => $value3) {
                    $index = array_search($value3, $value1);
                    if ($index == 0) {
                        if (abs($value[$index] - $value[$index + 1]) == 1) {
                            $last_num_cause_lianshu_nums++;
                            $t_nums++;
                            break;
                        }
                    } else if ($index == 5) {
                        if (abs($value[$index] - $value[$index - 1]) == 1) {
                            $last_num_cause_lianshu_nums++;
                            $t_nums++;
                            break;
                        }
                    } else if (abs($value[$index] - $value[$index - 1]) == 1 || abs($value[$index] - $value[$index + 1]) == 1) {
                        $last_num_cause_lianshu_nums++;
                        $t_nums++;
                        break;
                    }
                }
            }

            if ($last_num_cause_lianshu_nums > 0) {
                $middle .= str_pad("(T{$last_num_cause_lianshu_nums})", 4, " ");
            } else {
                $middle .= str_pad("", 4, " ");
            }

            if ($red_all_in_4_levels) {
                $middle .= str_pad("(In4L)", 6, " ");
            } else {
                $middle .= str_pad("", 6, " ");   
            }

            $odd_sort_nums[$odd_nums] += 1;
            $even_nums = 6 - $odd_nums;
            $count_level_nums = count($level_steps);
            $lottery_date = $lottery_dates[$key];
            $middle .= str_pad("({$odd_nums}-{$even_nums})(S{$count_level_nums})({$lottery_date})", 10, " ");

            array_push($frontend_balls, '('.$value[6].')');
            $diff = str_pad("{$diff}", 3, " ");
            echo str_pad("{$sum}($diff){$middle} : ". implode(" ", $frontend_balls), 35, " ");

            echo " | SRAND: ";
            mt_srand($lottery_date);
            $_combo_1 = [];
            for ($i=0; $i < 20; $i++) { 
                $number = mt_rand(1, 33);
                if ($number < 10)  {
                    $number = "0{$number}";
                }
                if (in_array($number, $_combo_1)) {
                    continue;
                }

                array_push($_combo_1, (string) $number);
                if (count($_combo_1) == 6) {
                    break;
                }
            }
            $similar_with_srand = array_intersect($frontend_balls, $_combo_1);

            echo str_pad(implode(" ", $similar_with_srand), 12, " ");

            echo " | IR: ";
            echo str_pad(implode(" ", $symbol_list), 12, " ");

            echo " | DI: ";
            $_sum = array_sum($diff_sections);
            $_dd_sort[$_sum] += 1;
            $array_count_values = array_count_values($diff_sections);
            $diff_addition = "";
            if ($array_count_values['1 '] >= 2 && $array_count_values['4 '] >= 1) {
                $diff_special_nums = array_diff($diff_sections, ["1 ", "4 "]);
                sort($diff_special_nums);
                $diff_special_nums_str = implode(" ", $diff_special_nums);
                $diff_special_nums_sum = array_sum($diff_special_nums);
                $diff_addition = "(Y1-[{$diff_special_nums_str}]-[{$diff_special_nums_sum}])";
                array_push($special_diff_arr, implode(" ", $diff_sections). "   ({$diff_special_nums_str}) - ({$diff_special_nums_sum})");

            } else if ($array_count_values['1 '] >= 2) {
                $diff_addition = "(Y2)";
            }
            echo str_pad(implode(" ", $diff_sections), 14, " "). " ($_sum){$diff_addition}";
            echo PHP_EOL;

            $range_combo_sort["{$sum_range}"][implode(" ", $symbol_list)] += 1;
        }

        // arsort($_dd_sort);
        // print_r($_dd_sort);

        // arsort($_dd_sort_1);
        // print_r($_dd_sort_1);

        echo PHP_EOL. PHP_EOL;
        // arsort($range_combo_sort);
        foreach ($range_combo_sort as $range_index => $value8) {
            arsort($value8);
            $combo_counts = array_values($value8);
            $counts = array_sum($combo_counts);
            $midu = intval($counts / count($value8));
            echo "{$range_index}0-". ($range_index + 1). "0 ({$counts}) ($midu): ". PHP_EOL;
            print_r($value8);
        }

        // arsort($range_combo_sort);
        // print_r($range_combo_sort);

        print_r($special_diff_arr);


        echo PHP_EOL. PHP_EOL;
    }

    // 
    public function printBlueDetail($data)
    {
        $data = array_slice($data, 0, 1000);
        $data = array_reverse($data);
        $range = $this->getRangeNumberList();
        $last_thread_features = [];
        $current_thread_features = [];
        $period = 32;
        $blue_balls = array_column($data, 6);
        $blue_ball_pos = [];
        foreach ($blue_balls as $key => $value) {
            $blue_ball_pos[$value][] = $key + 1;
        }

        $sort = [];
        foreach ($blue_ball_pos as $number => $arr) {
            $steps = [];
            foreach ($arr as $key1 => $value1) {
                if ($key1 == 0) {
                    continue;
                }

                $step = $value1 - $arr[$key1 - 1];
                array_push($steps, $step);
                $sort[$step] += 1;
            }

            $count = count($arr);
            echo "{$number} ($count): ". PHP_EOL;
            echo json_encode($steps). PHP_EOL;
            echo PHP_EOL;
        }

        arsort($sort);
        print_r($sort);


        echo PHP_EOL. PHP_EOL;
    }

    public function printAll1($data, $lottery_dates)
    {
        $range = $this->getRangeNumberList();
        $sort = [];
        $total_nums = 0;
        $unused_step = 9;
        $l_nums = $t_nums = $u_nums = 0;
        $odd_sort_nums = [];
        foreach ($data as $key => $value) {
            $_value11 = $value;
            unset($_value11[6]);
            $sum = array_sum($_value11);
            if ($sum < 100) {
                $sum = "0{$sum}";
            }

            $frontend_balls = $value;
            unset($frontend_balls[6]);
            $symbol_list = [];
            $special_nums = [];
            $lianshu_nums = 0;
            $odd_nums = 0;
            $level_steps = [];
            foreach ($frontend_balls as $key1 => $value5) {
                $v = $range[$key1];
                if (in_array($value5, $v)) {
                    array_push($symbol_list, 1);
                } else {
                    array_push($symbol_list, 0);
                }
                if (strstr($value5, '6') || strstr($value5, '7') || strstr($value5, '8')) {
                    array_push($special_nums, $value5);
                }
                if ($key1 < 5) {
                    if (abs($value5 - $frontend_balls[$key1 + 1]) == 1) {
                        $lianshu_nums++;
                        $l_nums++;
                    }
                }
                if ($value5 % 2 != 0) {
                    $odd_nums++;
                }
                if ( !in_array(intval($value5 / 10), $level_steps)) {
                    array_push($level_steps, intval($value5 / 10));
                }
            }

            if (!in_array(0, $symbol_list)) {
                $middle = str_pad("(ALL)", 5, " ");
                $sort[$sum] += 1;
                $total_nums++;
            } else {
                $middle = str_pad("", 5, " ");
                if ($this->only_choose_all) {
                    continue;
                }
            }

            if ($lianshu_nums > 0) {
                $middle .= str_pad("(L{$lianshu_nums})", 4, " ");
            } else {
                $middle .= str_pad('', 4, " ");
            }

            if ($key < count($data) - $unused_step - 1) {
                $unused_nums = $this->getRecentUnUsedNums($data, $key + 1, $unused_step);
                $array_intersect1 = array_intersect($unused_nums, $frontend_balls);
                $unused_num_case_lianshu_nums = 0;
                foreach ($array_intersect1 as $key6 => $value6) {
                    $index = array_search($value6, $value);
                    if ($index == 0) {
                        if (abs($value[$index] - $value[$index + 1]) == 1) {
                            $unused_num_case_lianshu_nums++;
                            $u_nums++;
                            break;
                        }
                    } else if ($index == 5) {
                        if (abs($value[$index] - $value[$index - 1]) == 1) {
                            $unused_num_case_lianshu_nums++;
                            $u_nums++;
                            break;
                        }
                    } else if (abs($value[$index] - $value[$index - 1]) == 1 || abs($value[$index] - $value[$index + 1]) == 1) {
                        $unused_num_case_lianshu_nums++;
                        $u_nums++;
                        break;
                    }
                }
            
                if ($unused_num_case_lianshu_nums > 0) {
                    $middle .= str_pad("(U{$unused_num_case_lianshu_nums})", 4, " ");
                } else {
                    $middle .= str_pad("", 4, " ");
                }
            } else {
                $middle .= str_pad("", 4, " ");
            }

            $last_num_cause_lianshu_nums = 0;
            if ($key != count($data) - 1) {
                $value1 = array_slice($value, 0, 6);
                $value2 = array_slice($data[$key + 1], 0, 6);
                $array_intersect = array_intersect($value1, $value2);
                foreach ($array_intersect as $key2 => $value3) {
                    $index = array_search($value3, $value1);
                    if ($index == 0) {
                        if (abs($value[$index] - $value[$index + 1]) == 1) {
                            $last_num_cause_lianshu_nums++;
                            $t_nums++;
                            break;
                        }
                    } else if ($index == 5) {
                        if (abs($value[$index] - $value[$index - 1]) == 1) {
                            $last_num_cause_lianshu_nums++;
                            $t_nums++;
                            break;
                        }
                    } else if (abs($value[$index] - $value[$index - 1]) == 1 || abs($value[$index] - $value[$index + 1]) == 1) {
                        $last_num_cause_lianshu_nums++;
                        $t_nums++;
                        break;
                    }
                }
            }

            if ($last_num_cause_lianshu_nums > 0) {
                $middle .= str_pad("(T{$last_num_cause_lianshu_nums})", 4, " ");
            } else {
                $middle .= str_pad("", 4, " ");
            }

            $odd_sort_nums[$odd_nums] += 1;
            $even_nums = 6 - $odd_nums;
            $count_level_nums = count($level_steps);
            $middle .= str_pad("({$odd_nums}-{$even_nums})(S{$count_level_nums})", 5, " ");

            array_push($frontend_balls, '('.$value[6].')');
            echo str_pad("{$sum}{$middle} : ". implode(" ", $frontend_balls), 26, " ");
            if ($key != count($data) - 1) {
                echo " | SWP: ";
                if ($array_intersect) {
                    foreach ($array_intersect as $key2 => &$value3) {
                        $index = array_search($value3, $value2);
                        $position1 = $index + 1;
                        $index = array_search($value3, $value1);
                        $position2 = $index + 1;
                        $value3 .= "({$position2}-{$position1})";
                    }
                    
                    echo str_pad(implode(" ", $array_intersect), 25, " ");
                } else {
                    echo str_pad("", 25, " ");
                }

                echo " | SR: ";
                mt_srand($lottery_dates[$key]);
                $_combo_1 = [];
                for ($i=0; $i < 20; $i++) { 
                    $_number_1 = mt_rand(1, 33);
                    if ($_number_1 < 10)  {
                        $_number_1 = "0{$_number_1}";
                    }
                    if (in_array($_number_1, $_combo_1)) {
                        continue;
                    }

                    array_push($_combo_1, (string) $_number_1);
                    if (count($_combo_1) == 6) {
                        break;
                    }
                }
                $similar_with_srand = array_intersect($frontend_balls, $_combo_1);
                echo str_pad(implode(" ", $similar_with_srand), 12, " ");

                // echo " | SRWN: ";
                // $_combo_1 = [];
                // for ($i=0; $i < 20; $i++) { 
                //     $_number_1 = mt_rand(1, 16);
                //     if ($_number_1 < 10)  {
                //         $_number_1 = "0{$_number_1}";
                //     }
                //     if (in_array($_number_1, $_combo_1)) {
                //         continue;
                //     }

                //     array_push($_combo_1, (string) $_number_1);
                //     if (count($_combo_1) == 3) {
                //         break;
                //     }
                // }
                // $similar_with_srand = array_intersect($value2, $_combo_1);
                // echo str_pad(implode(" ", $similar_with_srand), 12, " ");

                // echo " | BLU: ";
                // if (in_array($data[$key + 1][6], $value)) {
                //     $index = array_search($data[$key + 1][6], $value) + 1;
                //     echo str_pad("{$data[$key + 1][6]}({$index})", 6, " ");
                // } else {
                //     echo str_pad("", 6, " ");
                // }

                echo " | SN: ";
                if ($special_nums) {
                    echo str_pad(implode(" ", $special_nums), 15, " ");
                } else {
                    echo str_pad("", 15, " ");  
                }

                echo " | UUS: ";
                $unused_suffix = $unused_prefix = '';
                if ($key < count($data) - $unused_step - 1) {
                    if ($unused_nums) {
                        $unused_suffix = str_pad('  ('. implode(" ", $unused_nums) .')', 29);
                    } else {
                        $unused_suffix = str_pad("", 29, " ");  
                    }

                    if ($array_intersect1) {
                        $unused_prefix = str_pad(implode(" ", $array_intersect1), 15);
                    } else {
                        $unused_prefix = str_pad("", 15, " ");  
                    }
                }

                echo $unused_prefix. $unused_suffix;
            }
            echo PHP_EOL;
        }

        echo PHP_EOL;
        $keys = array_keys($sort);
        $average = intval(array_sum($keys) / count($keys));
        echo "Summary:". PHP_EOL;
        echo "  Red Sum:". PHP_EOL;
        echo "      min: ". min($keys). PHP_EOL;
        echo "      max: ". max($keys). PHP_EOL;
        echo "      average: ". $average. PHP_EOL;
        echo "      total_nums: {$total_nums}". PHP_EOL;
        echo "      rate(%): ". ($total_nums / count($data) * 100). PHP_EOL;
        echo PHP_EOL;

        echo "  L:". PHP_EOL;
        echo "      Sums: {$l_nums}". PHP_EOL;
        echo "      rate(%): ".  ($l_nums / count($data) * 100). PHP_EOL;
        echo PHP_EOL;

        echo "  U:". PHP_EOL;
        echo "      Sums: {$u_nums}". PHP_EOL;
        echo "      rate(%): ".  ($u_nums / count($data) * 100). PHP_EOL;
        echo PHP_EOL;

        echo "  T:". PHP_EOL;
        echo "      Sums: {$t_nums}". PHP_EOL;
        echo "      rate(%): ".  ($t_nums / count($data) * 100). PHP_EOL;
        echo PHP_EOL;

        echo "  Current Unused:". PHP_EOL;
        $unused_nums = $this->getRecentUnUsedNums($data, 0, $unused_step);
        echo "      unused_nums(0-{$unused_step}): ". implode(" ", $unused_nums). PHP_EOL;

        $high = $unused_step - 1;
        $unused_nums1 = $this->getRecentUnUsedNums($data, 0, $high);
        echo "      unused_nums(0-{$high}): ". implode(" ", $unused_nums1). PHP_EOL;
        echo PHP_EOL;

        echo "  Next Srand Red balls:". PHP_EOL;
        mt_srand($lottery_dates[0] + 1);
        $_combo_1 = [];
        for ($i=0; $i < 20; $i++) { 
            $_number_1 = mt_rand(1, 33);
            if ($_number_1 < 10)  {
                $_number_1 = "0{$_number_1}";
            }
            if (in_array($_number_1, $_combo_1)) {
                continue;
            }

            array_push($_combo_1, (string) $_number_1);
            if (count($_combo_1) == 6) {
                break;
            }
        }
        sort($_combo_1);
        echo "      ". implode(" ", $_combo_1). PHP_EOL;
        echo PHP_EOL;

        echo "  Odd-Even(Num):". PHP_EOL;
        arsort($odd_sort_nums);
        foreach ($odd_sort_nums as $k => $v) {
            $even = 6 - $k;
            echo "      {$k} - {$even}  ($v)". PHP_EOL; 
        }


        echo PHP_EOL. PHP_EOL;
    }

    public function printAll($data, $lottery_dates)
    {
        $range = $this->getRangeNumberList();
        $sort = [];
        $total_nums = 0;
        $unused_step = 9;
        $l_nums = $t_nums = $u_nums = 0;
        $odd_sort_nums = [];
        foreach ($data as $key => $value) {
            $_value11 = $value;
            unset($_value11[6]);
            $sum = array_sum($_value11);
            if ($sum < 100) {
                $sum = "0{$sum}";
            }

            $frontend_balls = $value;
            unset($frontend_balls[6]);
            $symbol_list = [];
            $special_nums = [];
            $lianshu_nums = 0;
            $odd_nums = 0;
            $level_steps = [];
            foreach ($frontend_balls as $key1 => $value5) {
                $v = $range[$key1];
                if (in_array($value5, $v)) {
                    array_push($symbol_list, 1);
                } else {
                    array_push($symbol_list, 0);
                }
                if (strstr($value5, '6') || strstr($value5, '7') || strstr($value5, '8')) {
                    array_push($special_nums, $value5);
                }
                if ($key1 < 5) {
                    if (abs($value5 - $frontend_balls[$key1 + 1]) == 1) {
                        $lianshu_nums++;
                        $l_nums++;
                    }
                }
                if ($value5 % 2 != 0) {
                    $odd_nums++;
                }
                if ( !in_array(intval($value5 / 10), $level_steps)) {
                    array_push($level_steps, intval($value5 / 10));
                }
            }

            if (!in_array(0, $symbol_list)) {
                $middle = str_pad("(ALL)", 5, " ");
                $sort[$sum] += 1;
                $total_nums++;
            } else {
                $middle = str_pad("", 5, " ");
                if ($this->only_choose_all) {
                    continue;
                }
            }

            if ($lianshu_nums > 0) {
                $middle .= str_pad("(L{$lianshu_nums})", 4, " ");
            } else {
                $middle .= str_pad('', 4, " ");
            }

            if ($key < count($data) - $unused_step - 1) {
                $unused_nums = $this->getRecentUnUsedNums($data, $key + 1, $unused_step);
                $array_intersect1 = array_intersect($unused_nums, $frontend_balls);
                $unused_num_case_lianshu_nums = 0;
                foreach ($array_intersect1 as $key6 => $value6) {
                    $index = array_search($value6, $value);
                    if ($index == 0) {
                        if (abs($value[$index] - $value[$index + 1]) == 1) {
                            $unused_num_case_lianshu_nums++;
                            $u_nums++;
                            break;
                        }
                    } else if ($index == 5) {
                        if (abs($value[$index] - $value[$index - 1]) == 1) {
                            $unused_num_case_lianshu_nums++;
                            $u_nums++;
                            break;
                        }
                    } else if (abs($value[$index] - $value[$index - 1]) == 1 || abs($value[$index] - $value[$index + 1]) == 1) {
                        $unused_num_case_lianshu_nums++;
                        $u_nums++;
                        break;
                    }
                }
            
                if ($unused_num_case_lianshu_nums > 0) {
                    $middle .= str_pad("(U{$unused_num_case_lianshu_nums})", 4, " ");
                } else {
                    $middle .= str_pad("", 4, " ");
                }
            } else {
                $middle .= str_pad("", 4, " ");
            }

            $last_num_cause_lianshu_nums = 0;
            if ($key != count($data) - 1) {
                $value1 = array_slice($value, 0, 6);
                $value2 = array_slice($data[$key + 1], 0, 6);
                $array_intersect = array_intersect($value1, $value2);
                foreach ($array_intersect as $key2 => $value3) {
                    $index = array_search($value3, $value1);
                    if ($index == 0) {
                        if (abs($value[$index] - $value[$index + 1]) == 1) {
                            $last_num_cause_lianshu_nums++;
                            $t_nums++;
                            break;
                        }
                    } else if ($index == 5) {
                        if (abs($value[$index] - $value[$index - 1]) == 1) {
                            $last_num_cause_lianshu_nums++;
                            $t_nums++;
                            break;
                        }
                    } else if (abs($value[$index] - $value[$index - 1]) == 1 || abs($value[$index] - $value[$index + 1]) == 1) {
                        $last_num_cause_lianshu_nums++;
                        $t_nums++;
                        break;
                    }
                }
            }

            if ($last_num_cause_lianshu_nums > 0) {
                $middle .= str_pad("(T{$last_num_cause_lianshu_nums})", 4, " ");
            } else {
                $middle .= str_pad("", 4, " ");
            }

            $odd_sort_nums[$odd_nums] += 1;
            $even_nums = 6 - $odd_nums;
            $count_level_nums = count($level_steps);
            $middle .= str_pad("({$odd_nums}-{$even_nums})(S{$count_level_nums})", 5, " ");

            array_push($frontend_balls, '('.$value[6].')');
            echo str_pad("{$sum}{$middle} : ". implode(" ", $frontend_balls), 26, " ");
            if ($key != count($data) - 1) {
                echo " | SWP: ";
                if ($array_intersect) {
                    $array_intersect221 = $array_intersect;
                    foreach ($array_intersect221 as $key2 => &$value3) {
                        $index = array_search($value3, $value2);
                        $position1 = $index + 1;
                        $index = array_search($value3, $value1);
                        $position2 = $index + 1;
                        $value3 .= "({$position2}-{$position1})";
                    }
                    
                    echo str_pad(implode(" ", $array_intersect221), 25, " ");
                } else {
                    echo str_pad("", 25, " ");
                }

                echo " | SR: ";
                mt_srand($lottery_dates[$key]);
                $_combo_1 = [];
                for ($i=0; $i < 20; $i++) { 
                    $_number_1 = mt_rand(1, 33);
                    if ($_number_1 < 10)  {
                        $_number_1 = "0{$_number_1}";
                    }
                    if (in_array($_number_1, $_combo_1)) {
                        continue;
                    }

                    array_push($_combo_1, (string) $_number_1);
                    if (count($_combo_1) == 6) {
                        break;
                    }
                }
                sort($_combo_1);
                echo str_pad(implode(" ", $_combo_1), 15, " ") ." ";
                $similar_with_srand = array_intersect($frontend_balls, $_combo_1);
                echo str_pad("(".implode(" ", $similar_with_srand). ")", 13, " ");

                // echo " | SRWN: ";
                // $_combo_1 = [];
                // for ($i=0; $i < 20; $i++) { 
                //     $_number_1 = mt_rand(1, 16);
                //     if ($_number_1 < 10)  {
                //         $_number_1 = "0{$_number_1}";
                //     }
                //     if (in_array($_number_1, $_combo_1)) {
                //         continue;
                //     }

                //     array_push($_combo_1, (string) $_number_1);
                //     if (count($_combo_1) == 3) {
                //         break;
                //     }
                // }
                // $similar_with_srand = array_intersect($value2, $_combo_1);
                // echo str_pad(implode(" ", $similar_with_srand), 12, " ");

                // echo " | BLU: ";
                // if (in_array($data[$key + 1][6], $value)) {
                //     $index = array_search($data[$key + 1][6], $value) + 1;
                //     echo str_pad("{$data[$key + 1][6]}({$index})", 6, " ");
                // } else {
                //     echo str_pad("", 6, " ");
                // }

                echo " | SN: ";
                if ($special_nums) {
                    echo str_pad(implode(" ", $special_nums), 15, " ");
                } else {
                    echo str_pad("", 15, " ");  
                }

                // echo " | UUS: ";
                $unused_suffix = $unused_prefix = '';
                if ($key < count($data) - $unused_step - 1) {
                    if ($unused_nums) {
                        $unused_suffix = str_pad('  ('. implode(" ", $unused_nums) .')', 29);
                    } else {
                        $unused_suffix = str_pad("", 29, " ");  
                    }

                    if ($array_intersect1) {
                        $unused_prefix = str_pad(implode(" ", $array_intersect1), 15);
                    } else {
                        $unused_prefix = str_pad("", 15, " ");  
                    }
                }

                // echo $unused_prefix. $unused_suffix;


                echo " | PICK: ";
                $possible_reds = array_merge($array_intersect, $similar_with_srand, $array_intersect1);
                $possible_reds_1 = array_unique($possible_reds);
                $repeat_nums = count($possible_reds) - count($possible_reds_1);
                echo str_pad("(".implode(" ", $possible_reds_1). ")", 11, " "). "[{$repeat_nums}]";
            }
            echo PHP_EOL;
        }

        echo PHP_EOL;
        $keys = array_keys($sort);
        $average = intval(array_sum($keys) / count($keys));
        echo "Summary:". PHP_EOL;
        echo "  Red Sum:". PHP_EOL;
        echo "      min: ". min($keys). PHP_EOL;
        echo "      max: ". max($keys). PHP_EOL;
        echo "      average: ". $average. PHP_EOL;
        echo "      total_nums: {$total_nums}". PHP_EOL;
        echo "      rate(%): ". ($total_nums / count($data) * 100). PHP_EOL;
        echo PHP_EOL;

        echo "  L:". PHP_EOL;
        echo "      Sums: {$l_nums}". PHP_EOL;
        echo "      rate(%): ".  ($l_nums / count($data) * 100). PHP_EOL;
        echo PHP_EOL;

        echo "  U:". PHP_EOL;
        echo "      Sums: {$u_nums}". PHP_EOL;
        echo "      rate(%): ".  ($u_nums / count($data) * 100). PHP_EOL;
        echo PHP_EOL;

        echo "  T:". PHP_EOL;
        echo "      Sums: {$t_nums}". PHP_EOL;
        echo "      rate(%): ".  ($t_nums / count($data) * 100). PHP_EOL;
        echo PHP_EOL;

        echo "  Current Unused:". PHP_EOL;
        $unused_nums = $this->getRecentUnUsedNums($data, 0, $unused_step);
        echo "      unused_nums(0-{$unused_step}): ". implode(" ", $unused_nums). PHP_EOL;

        $high = $unused_step - 1;
        $unused_nums1 = $this->getRecentUnUsedNums($data, 0, $high);
        echo "      unused_nums(0-{$high}): ". implode(" ", $unused_nums1). PHP_EOL;
        echo PHP_EOL;

        echo "  Next Srand Red balls:". PHP_EOL;
        mt_srand($lottery_dates[0] + 1);
        $_combo_1 = [];
        for ($i=0; $i < 20; $i++) { 
            $_number_1 = mt_rand(1, 33);
            if ($_number_1 < 10)  {
                $_number_1 = "0{$_number_1}";
            }
            if (in_array($_number_1, $_combo_1)) {
                continue;
            }

            array_push($_combo_1, (string) $_number_1);
            if (count($_combo_1) == 6) {
                break;
            }
        }
        sort($_combo_1);
        echo "      ". implode(" ", $_combo_1). PHP_EOL;
        echo PHP_EOL;

        echo "  Current Srand Red balls:". PHP_EOL;
        mt_srand($lottery_dates[0]);
        $_combo_1 = [];
        for ($i=0; $i < 20; $i++) { 
            $_number_1 = mt_rand(1, 33);
            if ($_number_1 < 10)  {
                $_number_1 = "0{$_number_1}";
            }
            if (in_array($_number_1, $_combo_1)) {
                continue;
            }

            array_push($_combo_1, (string) $_number_1);
            if (count($_combo_1) == 6) {
                break;
            }
        }
        sort($_combo_1);
        echo "      ". implode(" ", $_combo_1). PHP_EOL;
        echo PHP_EOL;

        echo "  Odd-Even(Num):". PHP_EOL;
        arsort($odd_sort_nums);
        foreach ($odd_sort_nums as $k => $v) {
            $even = 6 - $k;
            echo "      {$k} - {$even}  ($v)". PHP_EOL; 
        }


        echo PHP_EOL. PHP_EOL;
    }

    public function getComboSumLevel($red_combos)
    {
        $sum = array_sum($red_combos);
        $history_sums = $this->getHistorySumRanges();
        if (in_array($sum, $history_sums)) {
            return 2;
        }

        if ($this->checkSumInRange($sum)) {
            return 1;
        }

        return 0;
    }

    public function checkSumInRange($sum)
    {
        list ($low, $high) = $this->getRedSumsRange();
        if ($low <= $sum && $high >= $sum) {
            return true;
        }

        return false;
    }

    public function getRedSumsRange()
    {
        return [90, 128];
    }

    public function getHistorySumRanges()
    {
        $range = <<<EOL
[107,115,108,110,103,116,102,105,109,111,112,104,117,106,120,124,118,113,"098",114,121,101,119,"092","090","093",125,127,128,"099",123]
EOL;

        $range = json_decode($range, true);

        return $range;
    }

    public function monitor($data)
    {       
        $success_nums = 0;
        for ($i = 0; $i < 50; $i++) { 
            $the_numbers = $data[$i];
            $data_sets = array_slice($data, $i, 100);
            $try = 0;
            while (true) {
                if ($try >= 1000) {
                    echo "try: {$try} failure". PHP_EOL;
                    break;
                }

                $numbers = $this->getGuessCombo($data_sets, false);
                foreach ($numbers as $key => $value) {
                    if (count(array_intersect($value, $the_numbers)) >= 6) {
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

    public function watch($data, $lottery_dates)
    {
        $sort_count = [];
        $_sort_1 = 0;
        foreach ($data as $key => $value) {
            if ($key >= count($data) - 9) {
                echo "ending.. ";
                break;
            }

            $reds = array_slice($value, 0, 6);
            $next_reds = array_slice($data[$key + 1], 0, 6);
            $possble_reds = [];
            $unused_nums = $this->getRecentUnUsedNums($data, $key + 1, 9);
            if (empty($unused_nums)) {
                echo "lack of unused number, continue...". PHP_EOL;
                continue;
            }

            $current_srand_nums = $this->getSrandNums($lottery_dates[$key]);
            $next_srand_nums = $this->getSrandNums($lottery_dates[$key + 1]);
            $possible_exclude_nums = [];
            $same_srand_numbers = array_intersect($current_srand_nums, $next_srand_nums);
            if ($same_srand_numbers) {
                $possible_exclude_nums = array_merge($possible_exclude_nums, $same_srand_numbers);
            }

            $arr = array_unique(array_merge($next_reds, $next_srand_nums, $current_srand_nums, $unused_nums));
            sort($arr);
            $intersect = array_intersect($arr, $reds);
            $count = count($intersect);
            $exclude_count = count($arr) - $count;
            $sort_count[$count] += 1;
            if (count(array_intersect($reds, $next_reds)) >= 1 && count(array_intersect($reds, $current_srand_nums)) >= 1 && count(array_intersect($reds, $next_srand_nums)) >= 1 && count(array_intersect($reds, $unused_nums)) >= 1) {
                $_sort_1++;
                echo "{$key}    ->         {$count}        {$exclude_count}";
            }

            echo PHP_EOL;
        }

        var_dump($_sort_1);die;

        arsort($sort_count);
        print_r($sort_count);
    }

    public function getSrandNums($srand)
    {
        mt_srand($srand);
        $_combo_1 = [];
        for ($i=0; $i < 20; $i++) { 
            $_number_1 = mt_rand(1, 33);
            if ($_number_1 < 10)  {
                $_number_1 = "0{$_number_1}";
            }
            if (in_array($_number_1, $_combo_1)) {
                continue;
            }

            array_push($_combo_1, (string) $_number_1);
            if (count($_combo_1) == 6) {
                break;
            }
        }
        sort($_combo_1);

        return $_combo_1;
    }

    public function main()
    {
        list ($data, $lottery_dates) = $this->parse($this->request());
        $data = $this->getPracticeDataSet($data);
        switch ($this->action) {
            case self::WatchAction:
                return $this->watch($data, $lottery_dates);

            case self::GuessAction:
                return $this->getGuessCombo($data);

            case self::PrintFirstAction:
                return $this->printFirst($data);

            case self::PrintAllAction:
                return $this->printAll($data, $lottery_dates);

            case self::PrintAllD1Action:
                return $this->printAllDetail1($data, $lottery_dates);

            case self::PrintAllD2Action:
                return $this->printAllDetail2($data, $lottery_dates);

            case self::PrintBlueAction:
                return $this->printBlueDetail($data);

            case self::PrintAllUniqueNumsAction:
                return $this->printAllUniqueNums($data);

            case self::PrintFirstStepAction:
                return $this->printFirstStep($data);

            case self::MonitorAction:
                return $this->monitor($data);
                
            case self::GetLuckyNumsAction:
                return $this->getLuckyNumbers($data);

            case self::GetRecentUnUsedAction:
                echo implode(" ", $this->getRecentUnUsedNums($data, 0, 9)). PHP_EOL. PHP_EOL;
                return;

            case self::CheckAction:
                // $combo = ['6',  '8',  '16',  '17',  '25',  '33'];
                // $combo = ['6',  '8',  '16',  '17',  '25',  '29'];
                // $combo = ['6',  '8',  '16',  '17',  '25',  '30'];
                // $combo = ['6',  '8',  '16',  '17',  '25',  '31'];
                // $combo = ['6',  '8',  '16',  '17',  '25',  '32'];
                // $combo = ["06", "08", "12", "17", "25", "30"];
                // if ($this->checkComboHasExist($combo, $data)) {
                //     echo "exits". PHP_EOL;
                // } else {
                //     echo "non-exits". PHP_EOL;
                // }
                $combo = ["3", "10", "13", "19", "20", "25"];
                $DI = [];
                foreach ($combo as $key => $value) {
                    if ($key == 0) {
                        continue;
                    }

                    $str = $value - $combo[$key - 1];
                    $str = str_pad($str, 2, " ");
                    array_push($DI, $str);
                }

                echo implode(" ", $DI);
                echo PHP_EOL;
                echo array_sum($DI). PHP_EOL;

                return;

            default:
                die("without action param");
        }
    }
}

// (new lucky($is_refresh = false, lucky::CheckAction, 1000, false))->main();
// die;

// (new lucky($is_refresh = false, lucky::GetRecentUnUsedAction, 1000, false))->main();
// die;

(new lucky($is_refresh = false, lucky::WatchAction, 1000, false))->main();
die;

// (new lucky($is_refresh = false, lucky::PrintAllAction, 1000, false, false))->main();
// die;

// (new lucky($is_refresh = false, lucky::PrintBlueAction, 1000, false, false))->main();
// die;

// (new lucky($is_refresh = false, lucky::PrintAllD1Action, 1000, false, false))->main();
// die;

// (new lucky($is_refresh = false, lucky::PrintAllD2Action, 1000, false, false))->main();
// die;

// (new lucky($is_refresh = false, lucky::GetLuckyNumsAction, 1000, false))->main();
// die;

// (new lucky($is_refresh = false, lucky::MonitorAction, 1000, false))->main();
