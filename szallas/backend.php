<?php

class backend
{
    private const PSW_SALTING = 'SSE_A_KIRÁLY!';
    public $reservation_datas = array();
    
    function __construct()
    {
        require_once 'db.php';
        $this->db_controller = new db_controller();
    }
    
    public function clean_var($value, $type = 'dafault')
    {
        if (!empty($value))
        {
            if ($type == 'email')
            {
                if (!filter_var($value, FILTER_VALIDATE_EMAIL))
                {
                    $value = '';
                }
            }
            else
            {
                $this->string_clean($value);
            }
        }

        return $value;
    }
    
    private function string_clean(&$value)
    {
        $value = strip_tags(nl2br(trim(urldecode(urldecode($value))), false));
        
        $preg_patterns = $this->db_controller->security_preg_patterns;
        $chk_value = preg_replace($preg_patterns, '', $value);
        
        if ($chk_value != $value)
        {
            $value = '';
        }
    }
    
    public function chk_used_email(&$email)
    {
        $return = false;
        
        if (!empty($email))
        {
            $sql = "SELECT email FROM sse_szallasfoglalo_users WHERE email='$email' LIMIT 1;";
            $return_sql = $this->db_controller->sql_query($sql);
            if (!empty($return_sql['email']))
            {
                $return = true;
            }
        }
        
        return $return;
    }
    
    public function registration_process(&$user_datas)
    {
        $this->psw_convert($user_datas['password']);
        $sql = "INSERT INTO sse_szallasfoglalo_users (" . implode(',',array_keys($user_datas)) . ") VALUES ('" . implode("','",array_values($user_datas)) . "');";
        $sql_success = $this->db_controller->sql_query($sql);
        if (!empty($sql_success) && $sql_success == 1)
        {
            $this->log_in_process($user_datas['email'],$user_datas['password']);
        }
    }
    
    private function psw_convert(&$string)
    {
        $password_salting = self::PSW_SALTING;
        $string = hash('sha512', md5(strlen($string) . $string . substr($string, 0, 1) . (!empty($password_salting) ? strlen($password_salting) . $password_salting . substr($password_salting, 0, 1) : '')));
    }
    
    public function user_modify_process(&$user_datas,$uid,$base_url)
    {
        $this->psw_convert($user_datas['password']);
        
        $sql_set = '';
        foreach ($user_datas as $k => $v)
        {
            $sql_set.= (empty($sql_set) ? '' : ', ') . $k . "='" . $v . "'";
        }
        
        $sql = "UPDATE sse_szallasfoglalo_users SET $sql_set WHERE id='$uid';";
        $sql_success = $this->db_controller->sql_query($sql);
        if (!empty($sql_success) && $sql_success == 1)
        {
            header('location:' . $base_url);
            exit;
        }
    }
    
    public function log_in_process($email,$psw)
    {
        $user_datas = array();
        
        $sql = "SELECT id, password, ban_time, missfire, name FROM sse_szallasfoglalo_users WHERE email='$email' LIMIT 1;";
        $return = $this->db_controller->sql_query($sql);
        
        if (!empty($return['id']))
        {
            if ($return['ban_time'] > 0)
            {
                $ban_to = $return['ban_time'] + 8 * 3600;
                if ($ban_to > time())
                {
                    $user_datas['error'] = 'Sokszori hibás belépési adatok megadása miatt a fiókod 8 órára letiltásra került, és még nem járt le!';
                }
            }
            
            if (empty($user_datas['error']))
            {
                if (empty($_POST['password']) || $_POST['password'] == $psw)
                {
                    $this->psw_convert($psw);
                }
                
                if ($return['password'] == $psw)
                {
                    unset($return['password']);
                    unset($return['ban_time']);
                    unset($return['missfire']);
                    unset($_POST);
                    
                    $sql = "UPDATE sse_szallasfoglalo_users SET last_login='" . time() . "', ban_time='0', missfire='0' WHERE id='" . $return['id'] . "';";
                    $this->db_controller->sql_query($sql);
                    $user_datas = $return;
                }
                else
                {
                    unset($return['password']);
                    $this->log_in_missfire($return);
                    $user_datas['error'] = 'Hibás jelszót adtál meg!';
                }
            }
        }
        
        if (!empty($user_datas['id']) && empty($user_datas['error']))
        {
            $this->session_user = $user_datas;
            $_SESSION['sse_szallas'] = $this->session_user;
            header('location:' . $this->base_url . 'foglalas');
            exit;
        }
        
        return $user_datas;
    }
    
    public function log_in_missfire($user_datas)
    {
        if ($user_datas['missfire'] >= 5)
        {
            $sql = "UPDATE sse_szallasfoglalo_users SET ban_time='" . time() . "', missfire='0' WHERE id='" . $user_datas['id'] . "';";
        }
        else
        {
            $sql = "UPDATE sse_szallasfoglalo_users SET missfire='" . ++$user_datas['missfire'] . "' WHERE id='" . $user_datas['id'] . "';";
        }
        $this->db_controller->sql_query($sql);
    }
    
    public function get_user_datas_for_modify(&$sudatas)
    {
        $sql = "SELECT email, phone FROM sse_szallasfoglalo_users WHERE id='" . $sudatas['id'] . "';";
        $udatas = $this->db_controller->sql_query($sql);
        $_POST+= $sudatas + $udatas;
    }
    
    public function get_calendar_datas()
    {
        $months = array(
            '01' => 'Január',
            '02' => 'Február',
            '03' => 'Március',
            '04' => 'Április',
            '05' => 'Május',
            '06' => 'Június',
            '07' => 'Július',
            '08' => 'Augusztus',
            '09' => 'Szeptember',
            '10' => 'Október',
            '11' => 'November',
            '12' => 'December',
        );
        
        $days = array(
            'Mon' => array('H','Hétfő'),
            'Tue' => array('K','Kedd'),
            'Wed' => array('Sze','Szerda'),
            'Thu' => array('Cs','Csütörtök'),
            'Fri' => array('P','Péntek'),
            'Sat' => array('Szo','Szombat'),
            'Sun' => array('V','Vasárnap'),
        );
        
        $today = array(
            'year' => date('Y'),
            'month_nr' => date('m'),
            'month_name' => $months[date('m')],
            'day_nr' => date('d'),
            'day_name_short' => $days[date('D')][0],
            'day_name_full' => $days[date('D')][1],
        );
        
        $next_month = array(
            'year' => date('Y', strtotime('+1 months')),
            'month_nr' => date('m',strtotime('first day of +1 month')),
        );
        
        $month_days = array(
            $today['month_nr'] => array(
                'days_nr' => cal_days_in_month(CAL_GREGORIAN, $today['month_nr'], $today['year']),
                'first_day' => $days[date('D', strtotime($today['year'].'-'.$today['month_nr'].'-01'))][0],
            ),
            $next_month['month_nr'] => array(
                'days_nr' => cal_days_in_month(CAL_GREGORIAN, $next_month['month_nr'], $next_month['year']),
                'first_day' => $days[date('D', strtotime($next_month['year'].'-'.$next_month['month_nr'].'-01'))][0],
            ),
        );
        
        $two_months = array(
            $today['month_nr'] => $today + $month_days[$today['month_nr']],
            $next_month['month_nr'] => $next_month,
        );
        $two_months[$next_month['month_nr']]+= array('month_name' => $months[$next_month['month_nr']]) + $month_days[$next_month['month_nr']];
        
        $calendar_datas = array(
            'days' => array_values($days),
            'today' => $today,
            'two_months' => $two_months,
        );
        
        return $calendar_datas;
    }
    
    public function save_reservation_datas(&$sudatas)
    {
        $uid = $sudatas['id'];
        $uname = base64_encode($sudatas['name']);
        $days_json = $this->clean_var($_POST['days_activity_datas']);
        if (!empty($days_json))
        {
            $day_codes = '';
            $day_ids = array();
            $save_datas = array();
            $days_activities = json_decode($days_json,true);
            foreach($days_activities as $day_code => $activities)
            {
                $save_datas[$day_code][$uid] = $activities;
                $save_datas[$day_code][$uid]['name'] = $uname;
                $day_codes.= (empty($day_codes) ? '' : ',') . "'$day_code'";
            }

            $sql = "SELECT id, day_activities FROM sse_szallasfoglalo_reservations WHERE day_code IN ($day_codes);";
            $sql_datas = $this->db_controller->sql_query($sql,true);
            if (!empty($sql_datas))
            {
                foreach($sql_datas as $sql_day_datas)
                {
                    $sql_day_id = $sql_day_datas['id'];
                    $sql_day_activities = $sql_day_datas['day_activities'];
                    if (!empty($sql_day_activities))
                    {
                        $sql_day_activities = json_decode($sql_day_activities,true);
                        $sql_day_code = array_key_first($sql_day_activities);
                        $day_ids[$sql_day_code] = $sql_day_id;
                        
                        $user_day = $save_datas[$sql_day_code][$uid];
                        if (count($user_day) == 1 && !empty($user_day['name']))
                        {
                            unset($sql_day_activities[$sql_day_code][$uid]);
                            unset($save_datas[$sql_day_code][$uid]);
                        }
                        $save_datas[$sql_day_code]+= $sql_day_activities[$sql_day_code];
                    }
                }
            }
 
            $sql_insert = '';
            foreach($save_datas as $day_code => $arr)
            {
                $day_activities = json_encode(array($day_code=>$arr));
                if (!empty($day_ids[$day_code]))
                {
                    $sql_update = "UPDATE sse_szallasfoglalo_reservations SET day_activities='$day_activities' WHERE id='" . $day_ids[$day_code] . "';";
                    $this->db_controller->sql_query($sql_update);
                }
                else
                {
                    $sql_insert.= (empty($sql_insert) ? "INSERT INTO sse_szallasfoglalo_reservations (day_code,day_activities) VALUES " : ',') . "('$day_code','$day_activities')";
                }
            }
            
            if (!empty($sql_insert))
            {
                $this->db_controller->sql_query($sql_insert.";");
            }
        }
        unset($_POST['days_activity_datas']);
        unset($_POST['submit_send']);
    }
    
    public function read_reservation_datas(&$two_months)
    {
        $return = array();
        
        $day_codes = '';
        foreach($two_months as $month_datas)
        {
            $code_part = $month_datas['year'] . $month_datas['month_nr'];
            $day_nr = !empty($month_datas['day_nr']) ? $month_datas['day_nr'] : 1;
            for($i=$day_nr;$i<=$month_datas['days_nr'];$i++)
            {
                $day_codes.= (empty($day_codes) ? "'" : "','") . $code_part . str_pad($i,2,0,STR_PAD_LEFT);
            }
        }
        $day_codes.= "'";
        $sql = "SELECT id, day_code, day_activities FROM sse_szallasfoglalo_reservations WHERE day_code IN ($day_codes);";
        $sql_datas = $this->db_controller->sql_query($sql,true);
        if (!empty($sql_datas))
        {
            foreach($sql_datas as $v)
            {
                $return[$v['day_code']] = array(
                    'id' => $v['id'],
                    'activity' => json_decode($v['day_activities'],true),
                );
            }
        }
        
        return $return;
    }
    
    public function read_reservation_day_datas(&$day_code)
    {
        $return = array();
        
        $sql = "SELECT day_activities FROM sse_szallasfoglalo_reservations WHERE day_code ='$day_code';";
        $day_datas_json = $this->db_controller->sql_query($sql);
        if (!empty($day_datas_json))
        {
            $day_datas = json_decode($day_datas_json['day_activities'],true);
            if (!empty($day_datas[$day_code]))
            {
                foreach($day_datas[$day_code] as $v)
                {
                    $name = base64_decode($v['name']);
                    $return[$name] = array(
                        'sleep' => array('Alvás',(!empty($v['sleep']) ? $v['sleep'] : 0)),
                        'staff' => array('Szervezés',(!empty($v['staff']) ? $v['staff'] : 0)),
                        'play' => array('Játék',(!empty($v['play']) ? $v['play'] : 0)),
                    );
                }
            }
        }
        
        if (!empty($return))
        {
            ksort($return);
        }
        
        return $return;
    }
    
}

?>