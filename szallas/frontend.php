<?php
 
class frontend
{
    public $is_local;
    public $base_url;
    public $page;
    public $session_user = array();
    private $backend;
    
    function __construct()
    {
        $this->get_base_url();
        $this->page = $this->check_page();
        
        if ($this->page == 'kijelentkezes')
        {
            unset($_SESSION['sse_szallas']);
            unset($this->session_user);
            header('location:' . $this->base_url);
            exit;
        }
        
        require_once 'backend.php';
        $this->backend = new backend();
        
        if (!empty($_SESSION['sse_szallas']))
        {
            $this->session_user = $_SESSION['sse_szallas'];
        }
    }
    
    private function get_base_url()
    {
        $this->is_local = (stristr($_SERVER['HTTP_HOST'], 'local.') && stristr($_SERVER['SERVER_NAME'], 'local.')) ? true : false;
        if ($this->is_local == false)
        {
            $domain = !empty($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : '';
            $is_https = !empty($_SERVER['HTTPS']) && mb_strtolower($_SERVER['HTTPS'],'UTF-8') != 'off' ? true : false;
            $this->base_url = ($is_https == true ? 'https://' : 'http://') . $domain . '/szallas/';
        }
        else
        {
            $this->base_url = 'http://local.sse-szallasfoglalo.hu/szallas/';
        }
    }
    
    private function check_page()
    {
        $return = '';
        
        if ($_SERVER['HTTP_HOST'] == 'localhost' && $_SERVER['SERVER_NAME'] == 'localhost')
        {
            $breadcrumb_str = str_replace('/SSE_szallas','',$_SERVER['REQUEST_URI']);
        }
        else
        {
            $breadcrumb_str = $_SERVER['REQUEST_URI'];
        }
        
        $breadcrumb_arr = explode('/',trim($breadcrumb_str,'/'));
        if ($breadcrumb_arr[0] == 'szallas')
        {
            if (empty($breadcrumb_arr[1]))
            {
                $return = 'main';
            }
            else if (in_array($breadcrumb_arr[1],array('foglalas','kijelentkezes','bejelentkezes','regisztracio')))
            {
                $return = $breadcrumb_arr[1];
            }
        }
        
        if (empty($return))
        {
            header('location:http://www.soosandras.hu/szallas');
            exit;
        }
        
        return $return;
    }
    
    public function get_header()
    {
        $ver = 1;
        
        $return = '<head>';
        $return.= '<title>SSE szállásfoglaló</title>';
        $return.= '<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />';
        $return.= '<meta name="Robots" content="NOINDEX,NOFOLLOW">';
        $return.= '<base href="' . $this->base_url . '">';
        $return.= '<script src="jquery/js/jquery.min.js?v=' . $ver . '" type="text/javascript"></script>';
        $return.= '<script src="sse_szallas.js?v=' . $ver . '" type="text/javascript"></script>';
        $return.= '<link href="sse_szallas.css?v=' . $ver . '" rel="stylesheet" type="text/css" />';
        $return.= '<link rel="shortcut icon" href="' . $this->base_url . 'favicon.ico" />';
        $return.= '</head>';
        
        return $return;
    }
    
    public function get_headline()
    {
        $links = array();
        
        if (!empty($this->session_user['id']) && !empty($this->session_user['name']))
        {
            $links['Kijelentkezés'] = $this->base_url . 'kijelentkezes';
        }
        else
        {
            $links['Regisztráció'] = $this->base_url . 'regisztracio';
            $links['Bejelentkezés'] = $this->base_url . 'bejelentkezes';
        }
        $links['Foglalás'] = $this->base_url . 'foglalas';
        
        $return = '<div class="headline_block">';
            $return.= '<div class="headline_title"><a href="' . $this->base_url . '">';
                $return.= '<img src="' . $this->base_url . 'logos/sse.webp' . '" alt="Siófoki Strandröplabda Egyesület" title="Siófoki Strandröplabda Egyesület" />';
                $return.= '<span>SSE szállásfoglaló</span>';
            $return.='</a></div>';
            
            if (!empty($this->session_user['id']) && !empty($this->session_user['name']))
            {
                $return.= '<div class="headline_user" title="Adatok módosítása"><a href="' . $this->base_url . 'regisztracio">' . $this->session_user['name'] . '</a></div>';
            }
            
            foreach($links as $item_title => $item_link)
            {
                $underline_class = stristr($item_link,$this->page) ? ' underlined' : '';
                $return.= '<div class="headline_item ' . strtolower($item_title) . $underline_class . '">';
                $return.= '<a href="' . $item_link . '">' . $item_title . '</a>';
                $return.= '</div>'; //headline_item
            }
        $return.= '</div>'; //headline_block
        
        return $return;
    }
    
    protected function get_main_content()
    {
        $logos = array(
            'TIBI ATYA KUPA' => array($this->base_url . 'logos/tibiatyakupa.webp','https://www.facebook.com/tibiatyakupa'),
            'MATCH BALL KUPA' => array($this->base_url . 'logos/matchballkupa.webp','https://facebook.com/matchballkupa'),
            'SSE KUPA' => array($this->base_url . 'logos/ssekupa.webp','https://facebook.com/SiofokiStrandroplabda'),
        );
        
        $return = '<div class="main_content_block">';
            foreach ($logos as $kupa_title => $kupa_links)
            {
                $return.= '<div class="logo_item ' . strtolower(str_replace(' ','',$kupa_title)) . '">';
                    $return.= '<a href="' . $kupa_links[1] . '" target="_blank" title="' . $kupa_title . '" />';
                        $return.= '<img src="' . $kupa_links[0] . '" alt="' . $kupa_title . '" />';
                    $return.= '</a>';
                $return.= '</div>'; //logo_item
            }
        $return.= '</div>'; //main_content_block
            
        return $return;
    }
    
    protected function get_login_content()
    {
        $inputs = array(
            'email' => 'Email',
            'password' => 'Jelszó',
        );
        
        $return = '<div class="login_block">';
            $return.= '<div class="block_title">BEJELENTEKZÉS</div>';
            $return.= '<form name="login_form" id="login_form" method="POST" action="' . $this->base_url . 'bejelentkezes">';
                $login_ok = true;    
                foreach($inputs as $field_name => $field_title)
                {
                    $$field_name = !empty($_POST[$field_name]) ? $this->backend->clean_var($_POST[$field_name],$field_name) : '';
                    
                    if (!empty($_POST['submit_send']) && $_POST['submit_send'] == 1)
                    {
                        $error = '';
                        switch ($field_name)
                        {
                            case 'email':
                                if (empty($$field_name))
                                {
                                    $error = 'Nem adtál meg email címet!';
                                }
                                else
                                {
                                    $is_used = $this->backend->chk_used_email($$field_name);
                                    if ($is_used === false)
                                    {
                                        $error = 'Az email cím nincs regisztrálva a rendszerben!';
                                    }
                                }
                            break;

                            case 'password':
                                $minlength = 8;
                                $error = !empty($$field_name) ? '' : 'Nem adtál meg jelszót!';
                            break;
                        }
                        
                        if (!empty($error))
                        {
                            $login_ok = false;
                        }
                    }
                    
                    $return.= '<div class="form_item">';
                        $return.= '<div class="form_item_input' . (!empty($error) ? ' error_item' : '') . '">';
                            $return.= '<input type="' . $field_name . '" name="' . $field_name . '" id="' . $field_name . '" placeholder="' . $field_title . '" minlength="' . (!empty($minlength) ? $minlength : 4) . '" maxlength="255" value="' . $$field_name . '" autocomplete="chrome-off" />';
                        $return.= '</div>'; //form_item_input
                        $return.= '<div class="form_item_explanation">' . (empty($error) ? '' : $error) . '</div>';
                    $return.= '</div>'; //form_item
                }
                
                if (!empty($_POST['submit_send']) && $_POST['submit_send'] == 1 && $login_ok === true)
                {
                    $user_datas = $this->backend->log_in_process($email,$password);
                }
                
                $return.= '<div class="hidden_area"><input type="hidden" name="submit_send" id="submit_send" value="1" autocomplete="chrome-off" /></div>';
                $return.= '<div class="error_notice">' . (!empty($user_datas['error']) ? $user_datas['error'] : '') . '</div>';
                $return.= '<div class="submit_btn"><input type="submit" value="BEJELENTKEZÉS"></div>';
                $return.= '<div class="login_notice">Ha elfelejtetted a jelszavad, akkor küldj <a class="fblink" href="https://www.facebook.com/harciropi" target="_blank" title="Soós András [facebook]">nekem</a> üzenetet és egy tábla csokit!</div>';
            $return.= '</form>';
        $return.= '</div>'; //login_block
        
        return $return;
    }
    
    protected function get_registration_content()
    {
        $inputs = array(
            'name' => 'Név *',
            'email' => 'Email *',
            'phone' => 'Telefon',
            'password' => 'Jelszó *',
            'repassword' => 'Jelszó újra *',
        );
        
        $explanations = array(
            'name' => 'A neved, amit a foglalásoknál olvashatnak a sporttársak.',
            'email' => empty($this->session_user['id']) ? 'A bejelentkezéshez használni kívánt e-mail címed. Nem módosítható a későbbiekben!' : 'Az email cím módosítása nem lehetséges, ha mégis szeretnéd, regisztrálj újra!',
            'phone' => 'A telefonszámod, amin elérhetünk szükség esetén, tehát ha a világvége a köszöbünkön áll!',
            'password' => 'A jelszavad, ami legalább 8 karakter hosszú, és kínai írásjeleket tartalmaz.',
            'repassword' => 'A csodálatos jelszavad már el is felejtetted? Inkább add meg újra!',
        );
        
        if (!empty($this->session_user['id']) && empty($_POST['id']))
        {
            $this->backend->get_user_datas_for_modify($this->session_user);
        }
        
        $return = '<div class="registration_block' . (!empty($this->session_user['id']) ? ' save_block' : '') . '">';
            $return.= '<div class="block_title">' . (!empty($this->session_user['id']) ? 'ADATMÓDOÍTÁS' : 'REGISZTRÁCIÓ') . '</div>';
            $return.= '<form name="reg_form" id="reg_form" method="POST" action="' . $this->base_url . 'regisztracio">';
                $registration_ok = true;
                foreach($inputs as $field_name => $field_title)
                {
                    switch ($field_name)
                    {
                        case 'email':
                            $type = $field_name;
                            $$field_name = !empty($_POST[$field_name]) ? $this->backend->clean_var($_POST[$field_name],'email') : '';
                        break;
                    
                        case 'password':
                        case 'repassword':
                            $type = 'password';
                            $minlength = 8;
                            $$field_name = !empty($_POST[$field_name]) ? $this->backend->clean_var($_POST[$field_name]) : '';
                        break;
                        
                        default:
                            $type = 'text';
                            $$field_name = !empty($_POST[$field_name]) ? $this->backend->clean_var($_POST[$field_name]) : '';
                        break;
                    }
                    
                    if (!empty($_POST['submit_send']) && $_POST['submit_send'] == 1)
                    {
                        $error = '';
                        switch ($field_name)
                        {
                            case 'name':
                                $error = !empty($$field_name) ? '' : 'Nem megfelelő név, vagy nincs kitöltve!';
                            break;
                            
                            case 'email':
                                if (empty($this->session_user['id']))
                                {
                                    $is_used = $this->backend->chk_used_email($$field_name);
                                    if ($is_used === true)
                                    {
                                        $error = 'Az email cím már regisztrált a foglalási rendszerben!';
                                    }
                                    else
                                    {
                                        $error = !empty($$field_name) ? '' : 'Nem megfelelő email, vagy nincs kitöltve!';
                                    }
                                }
                            break;

                            case 'password':
                                $error = !empty($$field_name) ? '' : 'Nem adtál meg jelszót!';
                            break;
                        
                            case 'repassword':
                                if (!empty($password) && $$field_name != $password)
                                {
                                    $error = !empty($_POST[$field_name]) ? '' : 'Nem egyezik a két megadott jelszó!';
                                }
                            break;
                        }
                        
                        if (!empty($error))
                        {
                            $registration_ok = false;
                        }
                    }
                    
                    $disabled_class = (!empty($this->session_user['id']) && $field_name == 'email') ? ' disabled' : '';
                    $return.= '<div class="form_item">';
                        $return.= '<div class="form_item_title">' . $field_title . '</div>';
                        $return.= '<div class="form_item_input' . (!empty($error) ? ' error_item' : '') . $disabled_class . '">';
                            $return.= '<input type="' . $type . '" name="' . $field_name . '" id="' . $field_name . '" minlength="' . (!empty($minlength) ? $minlength : 4) . '" maxlength="255" value="' . $$field_name . '" autocomplete="chrome-off"' . $disabled_class . ' />';
                        $return.= '</div>'; //form_item_input
                        $return.= '<div class="form_item_explanation">' . (empty($error) ? $explanations[$field_name] : $error) . '</div>';
                    $return.= '</div>'; //form_item
                }
                
                if (!empty($_POST['submit_send']) && $_POST['submit_send'] == 1 && $registration_ok === true)
                {
                    $inputs = array(
                        'name' => $name,
                        'phone' => !empty($phone) ? $phone : '',
                        'password' => $password,
                    );
                    
                    if (empty($this->session_user['id']))
                    {
                        $inputs['time'] = time();
                        $inputs['email'] = $email;
                        $this->backend->registration_process($inputs);
                    }
                    else
                    {
                        $inputs['modify'] = time();
                        $this->backend->user_modify_process($inputs,$this->session_user['id'],$this->base_url);
                    }
                }
                
                $return.= '<div class="hidden_area"><input type="hidden" name="submit_send" id="submit_send" value="1" autocomplete="chrome-off" /></div>';
                $return.= '<div class="required_notice">* Az adatok megadása kötelező!</div>';
                $return.= '<div class="submit_btn"><input type="submit" value="' . (empty($this->session_user['id']) ? 'REGISZTRÁCIÓ' : 'MÓDOSÍTÁS') . '"></div>';
                $return.= empty($this->session_user['id']) ? '<div class="login_notice">Sikeres regisztrációt követően a bejelentkezés automatikus!</div>' : '';
            $return.= '</form>';
        $return.= '</div>'; //registration_block
        
        return $return;
    }
    
    protected function get_reservation_content()
    {
        if (!empty($_POST['submit_send']) && $_POST['submit_send'] == 1 && !empty($_POST['days_activity_datas']))
        {
            $this->backend->save_reservation_datas($this->session_user);
        }
        
        $calendar_datas = $this->backend->get_calendar_datas();
        $reservations = $this->backend->read_reservation_datas($calendar_datas['two_months']);
        $today_id = $calendar_datas['today']['year'] . $calendar_datas['today']['month_nr'] . str_pad($calendar_datas['today']['day_nr'],2,0,STR_PAD_LEFT);
        $clicked_days = array();
        $blank_days_nr = 0;
        
        $return = '<div class="reservation_block' . (empty($this->session_user['id']) ? '' : ' logged_in') . '">';
            $return.= '<div class="block_title">FOGLALÁS</div>';
            $return.= '<div class="ajax_content ajax_message">Ha foglalni szeretnél be kell jelenkezned!</div>';
            $return.= '<div class="block_content">';
                foreach($calendar_datas['two_months'] as $month_nr => $month_datas)
                {
                    $return.= '<div class="month_item">';
                        $return.= '<div class="month_title">' . $month_datas['year'] . ' - ' . $month_datas['month_name'] . '</div>';
                        $return.= '<div class="row week_row">';
                            foreach($calendar_datas['days'] as $k => $day)
                            {
                                $return.= '<div class="row_item week_day" title="' . $day[1] . '">' . $day[0] . '</div>';
                                $blank_days_nr = ($month_datas['first_day'] == $day[0]) ? $k : $blank_days_nr;
                            }
                        $return.= '</div>'; //week_row
                        for($i=1;$i<=$month_datas['days_nr']+$blank_days_nr;$i++)
                        {
                            $item_id = ($i-$blank_days_nr>0) ? $month_datas['year'] . $month_datas['month_nr'] . str_pad($i-$blank_days_nr,2,0,STR_PAD_LEFT) : 0;
                            $reserved_nr = empty($reservations[$item_id]['activity'][$item_id]) ? '' : count($reservations[$item_id]['activity'][$item_id]) . ' fő';
                            
                            if (!empty($reserved_nr) && !empty($this->session_user['id']) && $item_id>0 && !empty($reservations[$item_id]['activity'][$item_id]))
                            {
                                if (!empty($reservations[$item_id]['activity'][$item_id][$this->session_user['id']]))
                                {
                                    $clicked_days[$item_id] = $reservations[$item_id]['activity'][$item_id][$this->session_user['id']];
                                    unset($clicked_days[$item_id]['name']);
                                }
                            }
                            
                            $disabled_class = ($today_id>$item_id && $item_id>0) ? ' disabled' : '';
                            $clicked_class = !empty($clicked_days[$item_id]) ? ' clicked' : '';
                            
                            $content = $i-$blank_days_nr>0 ? '">' . '<div class="upper_line">' . ($i-$blank_days_nr) . '</div><div class="bottom_line">' . $reserved_nr . '</div>' : ' before_item">';
                            $return.= ($i%7 == 1) ? '<div class="row days_row">' : '';
                            $return.= '<div' . ($item_id>0 ? ' id="' . $item_id . '"' : '') . ' class="row_item day_item' . $clicked_class . $disabled_class . $content . '<div class="hover_shadow_frame"></div></div>';
                            $return.= ($i%7 == 0) ? '</div>' : '';
                            $return.= ($i == $month_datas['days_nr']+$blank_days_nr) ? '</div>' : ''; //days_row
                        }
                        
                        for($j=1;$j<=($i+$blank_days_nr)%7;$j++)
                        {
                            $return.= '<div class="row_item day_item blank_item"></div>';
                        }
                    $return.= '</div>'; //month_item
                }
            $return.= '</div>'; //block_content
            $return.= empty($clicked_days) ? '<div class="ajax_content reservation_datas"></div>' : $this->ajax_processing(array('show_days' => implode('|',array_keys($clicked_days)),'reservation_datas'=>$clicked_days));
        $return.= '</div>'; //reservation_block
        
        return $return;
    }
    
    protected function ajax_processing($params = array())
    {
        $reservation_datas_from_post = !empty($_POST['pad']) ? $this->backend->clean_var($_POST['pad']) : '';
        $reservation_datas = !empty($reservation_datas_from_post) ? json_decode($reservation_datas_from_post,true) : (!empty($params['reservation_datas']) ? $params['reservation_datas'] : array());
        
        $return = '<div class="ajax_content" data-pad=' . json_encode($reservation_datas) . '>';
            if (!empty($_POST['ajax_event']) && $_POST['ajax_event'] == 'calendar_day' || !empty($params))
            {
                if (!empty($_POST['day_id']))
                {
                    $day_code = !empty($_POST['day_id']) ? $this->backend->clean_var($_POST['day_id']) : '';
                    if (!empty($day_code))
                    {
                        $day_datas = $this->backend->read_reservation_day_datas($day_code);
                        if (!empty($day_datas))
                        {
                            $return.= '<div class="users_activities">';
                                $return.= '<div class="users_activity_row user_activity_date">' . substr($day_code,0,4) . '.' . substr($day_code,4,2) . '.' . substr($day_code,6) . '.</div>';
                                foreach($day_datas as $name => $activity)
                                {
                                    $return.= '<div class="users_activity_row user_row">';
                                        $return.= '<div class="user_name_item">' . $name . '</div>';
                                        foreach($activity as $activity_code => $v)
                                        {
                                            $checked = !empty($v[1]) ? ' checked' : '';
                                            $return.= '<div class="activity_chkbox ' . $activity_code . '">';
                                                $return.= '<input type="checkbox" value="' . $v[1] . '"' . $checked . ' />';
                                                $return.= '<div class="input_label">' . $v[0] . '</div>';
                                            $return.= '</div>'; //activity_chkbox
                                        }
                                    $return.= '</div>'; //user_row
                                }
                            $return.= '</div>'; //users_activities
                        }
                    }
                }
                
                if (!empty($this->session_user['id']))
                {
                    $show_days = !empty($params['show_days']) ? $params['show_days'] : (!empty($_POST['show_days']) ? $this->backend->clean_var($_POST['show_days']) : '');
                    if (!empty($show_days))
                    {
                        $days = explode('|',$show_days);
                        if (!empty($days))
                        {
                            $inputs = array(
                                'sleep' => 'Alvás',
                                'staff' => 'Szervezés',
                                'play' => 'Játék',
                            );

                            $return.= '<div class="days_activities">';
                                $return.= '<form name="days_activity_form" id="days_activity_form" method="POST" action="' . $this->base_url . 'foglalas">';
                                $return.= '<div class="submit_btn" title="Csak módosítást követően menthetsz!"><input type="submit" value="MENTÉS"></div>';
                                    foreach($days as $day_id)
                                    {
                                        $return.= '<div id="day-' . $day_id . '" class="day_activity_item">';
                                            $return.= '<div class="day_date">' . substr($day_id,0,4) . '.' . substr($day_id,4,2) . '.' . substr($day_id,6) . '.</div>';
                                            $return.= '<div class="day_activities">';
                                                foreach($inputs as $input_name => $input_title)
                                                {
                                                    $chkbox_value = !empty($reservation_datas[$day_id][$input_name]) ? 1 : 0;
                                                    $return.= '<div class="activity_chkbox">';
                                                        $return.= '<input type="checkbox" id="' . $day_id . '-' . $input_name . '" name="' . $day_id . '-' . $input_name . '" value="' . $chkbox_value . '"' . (!empty($chkbox_value) ? ' checked' : '') . ' />';
                                                        $return.= '<label for="' . $day_id . '-' . $input_name . '">' . $input_title . '</label>';
                                                    $return.= '</div>'; //activity_chkbox
                                                }
                                            $return.= '</div>'; //day_activities
                                        $return.= '</div>'; //day_activity_item
                                    }
                                    $return.= '<div class="hidden_area">';
                                        $return.='<input type="hidden" name="submit_send" id="submit_send" value="1" autocomplete="chrome-off" />';
                                        $return.='<input type="hidden" name="days_activity_datas" id="days_activity_datas" value=' . (!empty($reservation_datas) ? json_encode($reservation_datas) : '""') . ' autocomplete="chrome-off" />';
                                    $return.= '</div>'; //hidden_area
                                $return.= '</form>'; //days_activity_form
                            $return.= '</div>'; //days_activities
                        }
                    }
                }
            }
        $return.= '</div>'; //ajax_content
        
        if (!empty($reservation_datas))
        {
            $return = str_replace('<div class="ajax_content','<div class="ajax_content reservation_datas',$return);
        }
        
        return $return;
    }
    
    public function get_footer()
    {
        $return = '<div class="footer_line">';
            $return.= '<div class="f_left"><a href="https://facebook.com/SiofokiStrandroplabda">Siófoki Strandröplabda Egyesület</a><br>Copyright &copy; ' . date('Y') . '  All Rights Reserved</div>';
            $return.= '<div class="f_right"><div class="mouse_icon"></div><a href="https://www.linkedin.com/in/soosandras-850427" target="_blank" title="LinkedIn - Soós András">Soós András</a></div>';
        $return.='</div>';
        
        return $return;
    }
    
    public function show_output()
    {
        if (!empty($_POST['ajax_event']))
        {
            return $this->ajax_processing();
        }
        
        $output = $this->get_header();
        $output.= '<div class="sse_szallasfoglalo">';
            $output.= $this->get_headline();
            switch ($this->page)
            {
                case 'main':
                   $output.= $this->get_main_content();
                break;

                case 'foglalas':
                   $output.= $this->get_reservation_content();
                break;

                case 'bejelentkezes':
                   $output.= $this->get_login_content();
                break;
            
                case 'regisztracio':
                   $output.= $this->get_registration_content();
                break;
            }
            $output.= $this->get_footer();
        $output.= '</div>'; //sse_szallasfoglalo
        
        return $output;
    }
}

?>
