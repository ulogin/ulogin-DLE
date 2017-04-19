<?php
if (!defined('DATALIFEENGINE'))
    die("Hacking attempt!");

class UloginModel {
    private $db;
    private $pref;
    private $upref;
    private $config;

    function __construct() {
        global $db;
        global $config;
        $this->db = $db;
        $this->config = $config;
        $this->pref = PREFIX . '_';
        $this->upref = (USERPREFIX ? USERPREFIX : PREFIX) . '_';
    }

    function UloginModel() {
        $this->__construct();
    }

    private function sql($s, $convert = false) {
        if ($convert) {
            $s = $this->config['charset'] != 'utf-8' ? convert_unicode($s, $this->config['charset']) : $s;
        }
        return $this->db->safesql(strip_tags(stripslashes($s)));
    }


    /**
     * Проверка, есть ли пользователь с указанным id в базе
     * @param $u_id
     * @return bool
     */
    public function checkUserId ($u_id) {
        $sql = "SELECT user_id
				FROM  " . $this->upref . "users
				WHERE user_id = '" . (int)$u_id . "'";

        $row = $this->db->super_query($sql);

        if ($row['user_id'] > 0) {
            return true;
        }
        return false;
    }


//--------------------
    /**
     * Получение id пользователя по email
     * @param string $email
     * @return int|bool
     */
    public function getUserIdByEmail ($email = '') {
        $sql = "SELECT user_id
				FROM  " . $this->upref . "users
				WHERE email = '" . $this->sql($email) . "'";

        $row = $this->db->super_query($sql);

        if (!empty($row['user_id'])) {
            return $row['user_id'];
        }
        return false;
    }


//--------------------
    /**
     * Получение данных о пользователе из таблицы ulogin_users
     * @param $data
     * @return bool|mixed
     */
    public function getUloginUserItem ($data = array()) {
        if (!is_array($data) || empty($data)) { return false; }

        $sql = "SELECT *
				FROM " . $this->pref . "ulogin";

        if (!($sql_where = $this->addWhere($data))) { return false; }

        $sql .= " WHERE $sql_where";

        $row = $this->db->super_query($sql);

        if ($row) {
            return $row;
        }
        return false;
    }


//--------------------
    /**
     * Получение массива соцсетей пользователя по значению поля $user_id
     * @param int $user_id
     * @return array|bool
     */
    public function getUloginUserNetworks ($user_id = 0) {
        $sql = "SELECT network
				FROM " . $this->pref . "ulogin
				WHERE user_id = '" . (int)$user_id . "'";

        $rows = $this->db->super_query($sql, true);

        if (!$rows) { return false; }

        $networks = array();
        foreach ($rows as $row)
        {
            $networks[] = $row["network"];
        }

        return $networks;
    }


//--------------------
    /**
     * Удаление данных о пользователе из таблицы ulogin_user
     * @param int $user_id
     * @return bool
     */
    public function deleteUloginAccount ($data = array()) {
        if (!is_array($data) || empty($data)) { return false; }

        $sql = "DELETE FROM " . $this->pref . "ulogin";

        if (!($sql_where = $this->addWhere($data))) { return false; }

        $sql .= " WHERE $sql_where";
        $this->db->super_query($sql);
        return true;
    }


//--------------------
    /**
     * Добавление данных о пользователе в таблицы ulogin_user
     * @param array $data
     * @return bool
     */
    public function addUloginAccount ($data = array()) {
        if (!is_array($data)
            || empty($data)
            || !(array_key_exists('user_id', $data)
                && array_key_exists('identity', $data)
                && array_key_exists('network', $data))) {
            return false;
        }

        $sql = "INSERT INTO " . $this->pref . "ulogin";

        if (!($sql_set = $this->addWhere($data, ','))) { return false; }

        $sql .= " SET $sql_set";
        $this->db->super_query($sql);
        return true;
    }


//--------------------
    /**
     * Проверка, есть ли пользователь с указанным username в базе
     * @param string $username
     * @return bool
     */
    public function checkUserName ($username = '') {
        $sql = "SELECT user_id
				FROM  " . $this->upref . "users
				WHERE name = '" . $this->sql($username, true) . "'";

        $row = $this->db->super_query($sql);

        if ($row['user_id'] > 0) {
            return true;
        }
        return false;
    }


//--------------------
    /**
     * Регистрация нового пользователя
     * @param $login
     * @param $password
     * @param $email
     * @param int $group
     * @return bool
     */
    public function registerUser ($login, $password, $email, $group = 4) {
        $group = intval($group);
        $group = $group ? $group : 4;

        $reg_date = time() + ($this->config['date_adjust'] * 60);
        $_IP = $_SERVER['REMOTE_ADDR'];

        $data = array(
            'name' => $login,
            'email' => $email,
            'password' => $password,
            'user_group' => $group,
            'reg_date' => $reg_date,
            'lastdate' => $reg_date,
            'logged_ip' => $_IP,
            'info' => '',
            'signature' => '',
            'favorites' => '',
            'xfields' => '',
        );


        $sql = "INSERT INTO " . $this->upref . "users";

        if (!($sql_set = $this->addWhere($data, ',', true))) { return false; }
        $sql .= " SET $sql_set";

        $this->db->super_query($sql);
        $user_id = $this->db->insert_id();

        return $user_id;
    }


//--------------------
    /**
     * Обновление данных о пользователе
     * @param int $user_id
     * @param $data
     * @return bool
     */
    public function updateUserData ($user_id, $data) {
        if (!is_array($data) || empty($data) || (int)$user_id <= 0) { return false; }

        $sql = "UPDATE LOW_PRIORITY " . $this->upref . "users";

        if (!($sql_set = $this->addWhere($data, ',', true))) { return false; }
        $sql .= " SET $sql_set WHERE user_id = '" . (int)$user_id . "'";

        $this->db->query($sql);

        return true;
    }


//--------------------
    /**
     * Получение данных о пользователе по id
     * @param int $id
     * @return array
     */
    public function getUserDataById ($id = 0) {
        if ((int)$id <= 0) { return array(); }

        $sql = "SELECT *
				FROM  " . $this->upref . "users
				WHERE user_id = '" . (int)$id . "'";

        $row = $this->db->super_query($sql);

        if (!empty($row['user_id']) && $row['user_id'] == $id) {
            return $row;
        }
        return array();
    }



//--------------------
    /**
     * Получение данных о группе по id
     * @param int $id
     * @return array
     */
    public function getUserGroupById ($id = 0) {
        if ((int)$id <= 0) { return array(); }

        $sql = "SELECT *
				FROM  " . $this->upref . "usergroups
				WHERE id = '" . (int)$id . "'";

        $row = $this->db->super_query($sql);

        if (!empty($row['id']) && $row['id'] == $id) {
            return $row;
        }
        return array();
    }



//----------------------------------------------------------
    /** Получение условия where для массива данных $fields
     * @param array $fields
     * @return string
     */
    private function addWhere ($fields = array(), $delimiter = 'AND', $convert=false) {
        if (!is_array($fields) || empty($fields)) { return ''; }
        $i = 0;
        $sql = '';

        foreach ($fields as $field => $value) {
            if ($i > 0) {
                $sql .= " $delimiter ";
            }

            $sql .= "$field = '" . $this->sql($value, $convert) . "'";
            $i++;
        }

        return $sql;
    }

//----------------------------------------------------------
    /**
     * Создание таблицы ulogin
     */
    public function checkUloginTable(){
        $sql = "
			CREATE TABLE IF NOT EXISTS `" . $this->upref . "ulogin` (
					`id` INTEGER UNSIGNED NOT NULL AUTO_INCREMENT,
					`user_id` INTEGER UNSIGNED NOT NULL,
					`identity` VARCHAR(255) NOT NULL,
					`network` VARCHAR(50) DEFAULT NULL,
				PRIMARY KEY (`id`),
				INDEX (`user_id`),
				INDEX (`identity`)
			) ENGINE=MyISAM /*!40101 DEFAULT CHARACTER SET " . COLLATE . " COLLATE " . COLLATE . "_general_ci */
		";

        $this->db->query($sql);

        $sql = "SHOW COLUMNS FROM " . $this->upref . "ulogin LIKE 'ident'";
        $row = $this->db->super_query($sql);

        if (isset($row['Field']) && $row['Field'] == 'ident') {
            $this->db->query("ALTER TABLE `" . $this->upref . "ulogin` CHANGE COLUMN `ident` `identity` varchar(255)");
        }

        $sql = "SHOW COLUMNS FROM " . $this->upref . "ulogin LIKE 'network'";
        $row = $this->db->super_query($sql);

        if (!isset($row['Field']) || $row['Field'] != 'network') {

            $this->db->query("ALTER TABLE `" . $this->upref . "ulogin` ADD COLUMN `network` varchar(50) NULL DEFAULT ''");
            $this->fillUloginNetworkData();
        }
    }

//-------------------	
    function fillUloginNetworkData(){
        $sql = "SELECT id, identity, network
				FROM " . $this->pref . "ulogin";

        $result = $this->db->super_query($sql, true);

        if ($result) {
            foreach ($result as $key => $row) {
                if (preg_match("/^https?:\/\/vk\.com/", $row['identity'])) {
                    $result[$key]['network'] = 'vkontakte';
                } else if (preg_match("/^https?:\/\/odnoklassniki\.ru/", $row['identity'])) {
                    $result[$key]['network'] = 'odnoklassniki';
                } else if (preg_match("/^https?:\/\/login\.yandex\.ru/", $row['identity'])) {
                    $result[$key]['network'] = 'yandex';
                } else if (preg_match("/^https?:\/\/plus\.google\.com/", $row['identity'])) {
                    $result[$key]['network'] = 'google';
                } else if (preg_match("/^https?:\/\/steamcommunity\.com/", $row['identity'])) {
                    $result[$key]['network'] = 'steam';
                } else if (preg_match("/^https?:\/\/soundcloud\.com/", $row['identity'])) {
                    $result[$key]['network'] = 'soundcloud';
                } else if (preg_match("/^https?:\/\/(www\.)?last\.fm/", $row['identity'])) {
                    $result[$key]['network'] = 'lastfm';
                } else if (preg_match("/^https?:\/\/(www\.)?linkedin\.com/", $row['identity'])) {
                    $result[$key]['network'] = 'linkedin';
                } else if (preg_match("/^https?:\/\/(www\.)?facebook\.com/", $row['identity'])) {
                    $result[$key]['network'] = 'facebook';
                } else if (preg_match("/^https?:\/\/my\.mail\.ru/", $row['identity'])) {
                    $result[$key]['network'] = 'mailru';
                } else if (preg_match("/^https?:\/\/twitter\.com/", $row['identity'])) {
                    $result[$key]['network'] = 'twitter';
                } else if (preg_match("/^https?:\/\/profile\.live\.com/", $row['identity'])) {
                    $result[$key]['network'] = 'liveid';
                } else if (preg_match("/^https?:\/\/(www\.)?flickr\.com/", $row['identity'])) {
                    $result[$key]['network'] = 'flickr';
                } else if (preg_match("/^https?:\/\/vimeo\.com/", $row['identity'])) {
                    $result[$key]['network'] = 'vimeo';
                } else if (preg_match("/^https?:\/\/(.*?)\.livejournal\.com/", $row['identity'])) {
                    $result[$key]['network'] = 'livejournal';
                } else if (preg_match("/^https?:\/\/openid\.com/", $row['identity'])) {
                    $result[$key]['network'] = 'openid';
                } else if (preg_match("/^https?:\/\/(.*?)\.wmkeeper\.com/", $row['identity'])) {
                    $result[$key]['network'] = 'webmoney';
                } else if (preg_match("/^https?:\/\/gdata\.youtube\.com/", $row['identity'])) {
                    $result[$key]['network'] = 'youtube';
                } else if (preg_match("/^https?:\/\/foursquare\.com/", $row['identity'])) {
                    $result[$key]['network'] = 'foursquare';
                } else if (preg_match("/^https?:\/\/(www\.)?tumblr\.com/", $row['identity'])) {
                    $result[$key]['network'] = 'tumblr';
                } else if (preg_match("/^https?:\/\/plus\.google\.com/", $row['identity'])) {
                    $result[$key]['network'] = 'googleplus';
                } else if (preg_match("/^https?:\/\/dudu\.com/", $row['identity'])) {
                    $result[$key]['network'] = 'dudu';
                } else {
                    unset($result[$key]);
                }
            }

            if ($result) {
                foreach ($result as $row) {
                    $sql = "UPDATE " . $this->pref . "ulogin";
                    $sql .= " SET network = '" . $row['network'] . "' WHERE id = '" . (int)$row['id'] . "'; ";
                    $this->db->query($sql);
                }
            }
        }
    }

//-------------------
    /**
     * Добавление колонки upass в таблицу users
     */
    public function checkUpassColumn(){
        $sql = "SHOW COLUMNS FROM " . $this->upref . "users LIKE 'upass'";
        $row = $this->db->super_query($sql);

        if (isset($row['Field']) && $row['Field'] == 'upass') {
            return 1;
        }

        $sql = "ALTER TABLE `" . $this->upref . "users` ADD `upass` VARCHAR( 32 ) NOT NULL DEFAULT ''";
        $this->db->query($sql);

        return 2;
    }


//-------------------
    /**
     * Проверка наличичя группы пользователей uLogin. Создание, если отсутствует.
     * @return bool|int
     */
    public function getUloginGroup(){
        $sql = "SELECT id
				FROM  " . $this->upref . "usergroups
				WHERE group_name='uLogin'";

        $row = $this->db->super_query($sql);

        if (!empty($row['id']) && $row['id'] > 0) {
            return $row['id'];
        }

        // создание группы на основе группы "Посетители"
        $sql = "SELECT *
				FROM  " . $this->upref . "usergroups
				WHERE id = '4'"; // Посетители

        $row = $this->db->super_query($sql);

        if (empty($row['id'])) {
            return false;
        }

        $row['group_name'] = 'uLogin';
        unset($row['id']);

        $sql = "INSERT INTO " . $this->upref . "usergroups";

        if (!($sql_set = $this->addWhere($row, ','))) { return false; }
        $sql .= " SET $sql_set";

        $row = $this->db->super_query($sql);

        if (empty($row['id'])) {
            return false;
        }

        // запись в set_vars usergroup
        $user_group = array();
        $this->db->query( "SELECT * FROM " . USERPREFIX . "_usergroups ORDER BY id ASC" );
        while ( $row = $this->db->get_row() ) {
            $user_group[$row['id']] = array ();
            foreach ( $row as $key => $value ) {
                $user_group[$row['id']][$key] = stripslashes($value);
            }
        }
        set_vars( "usergroup", $user_group );

        return $row['id'];
    }

//-------------------
    /**
     * Добавление пункта ulogin в таблицу admin_sections
     */
    public function setUloginAdminSection($data){
        $sql = "SELECT id
				FROM  " . $this->pref . "admin_sections
				WHERE name='" . $data['name'] . "'";

        $row = $this->db->super_query($sql);

        if (!empty($row['id']) && $row['id'] > 0) {
            return $row['id'];
        }

        $sql = "INSERT INTO " . $this->pref . "admin_sections";

        if (!($sql_set = $this->addWhere($data, ','))) { return false; }
        $sql .= " SET $sql_set";

        $row = $this->db->super_query($sql);

        if (empty($row['id'])) {
            return false;
        }

        return $row['id'];
    }

//---------------------------------------------
    /**
     * Получение настроек модуля
     * @return array
     */
    public function getUloginConfig() {
        global $ulogin_config;

        if (!isset($ulogin_config)) {
            if (file_exists (ENGINE_DIR . '/data/uloginconfig.php')) {
                require_once(ENGINE_DIR . '/data/uloginconfig.php');
            } else {
                $ulogin_group_id = $this->getUloginGroup();
                $ulogin_config = array(
                    'uloginid' => '',
                    'uloginid_profile' => '',
                    'ulogin_group_id' => (int)$ulogin_group_id > 0 ? (int)$ulogin_group_id : 4,
                );
            }
        }

        return $ulogin_config;
    }

    public function safesql($s)
    {
        return $this->db->safesql($s);
    }
}
