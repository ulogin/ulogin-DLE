<?php
if (!defined('DATALIFEENGINE'))
	die("Hacking attempt!");

class uLogin {
	protected $u_data;
	protected $currentUserId;
	protected $userIsLogged;
	protected $doRedirect;
	protected $token;
	protected $redirect;
	private $model;
	private $language;
	private $upass;

	function __construct() {
		require_once ULOGIN_DIR . '/ulogin_model.class.php';
		$this->model = new UloginModel();

		require_once ULOGIN_DIR . '/ulogin.lng';
		$this->language = $ulogin_lang;

		$this->model->checkUloginTable();
		$this->model->checkUpassColumn();

		$this->upass = '';

		global $member_id;
		global $is_logged;
		$this->userIsLogged = $is_logged;
		if ($this->userIsLogged) {
			$this->currentUserId = isset($member_id['user_id']) ? $member_id['user_id'] : 0;

			// установка $this->upass для пользователя online
			if (
				isset($_SESSION['dle_user_id'])
			    && intval($_SESSION['dle_user_id']) > 0
			    && !empty($_SESSION['dle_password'])
				&& $member_id['user_id'] == $_SESSION['dle_user_id']
				&& $member_id['password'] == md5($_SESSION['dle_password'])
			) {
				$this->upass = $_SESSION['dle_password'];
			} else if (
				isset($_COOKIE['dle_user_id'])
				&& intval($_COOKIE['dle_user_id']) > 0
				&& !empty($_COOKIE['dle_password'])
				&& $member_id['user_id'] == $_COOKIE['dle_user_id']
				&& $member_id['password'] == md5($_COOKIE['dle_password'])
			) {
				$this->upass = $_SESSION['dle_password'];
			}
		} else {
			if (isset($_POST['ulogin_pass']) && !empty($_POST['ulogin_pass'])) {
				$this->upass = md5($_POST['ulogin_pass']);
				unset($_POST['ulogin_pass']);
			}
		}
	}

	function uLogin() {
		$this->__construct();
	}


	public function login() {

		$title = '';
		$msg = '';

		$this->doRedirect = !(isset($_POST['isAjax']) ? true : false);

		if ($this->userIsLogged){
			$msg = 'ulogin_add_account_success';//'Аккаунт успешно добавлен';
		}

		$this->uloginLogin($title, $msg);

		if (!$this->doRedirect) {
			exit;
		}
	}


	public function delete() {
		$this->deleteAccount();
	}


//=================================================================================

	protected function uloginLogin ($title = '', $msg = '') {

		$this->u_data = $this->uloginParseRequest();

		if ( !$this->u_data ) {
			return;
		}

		try {
			$u_user_db = $this->model->getUloginUserItem(array('identity' => $this->u_data['identity']));
			$user_id = 0;

			if ( $u_user_db ) {

				if ($this->model->checkUserId($u_user_db['user_id'])) {
					$user_id = $u_user_db['user_id'];
				}

				if ( intval( $user_id ) > 0 ) {
					if ( !$this->checkCurrentUserId( $user_id ) ) {
						// если $user_id != ID текущего пользователя
						return;
					}
				} else {
					// данные о пользователе есть в ulogin_table, но отсутствуют в users. Необходимо переписать запись в ulogin_table и в базе users.
					$user_id = $this->newUloginAccount( $u_user_db );
				}

			} else {
				// пользователь НЕ обнаружен в ulogin_table. Необходимо добавить запись в ulogin_table и в базе users.
				$user_id = $this->newUloginAccount();
			}

			// обновление данных и Вход
			if ( $user_id > 0 ) {
				$this->loginUser( $user_id );

				$networks = $this->model->getUloginUserNetworks( $user_id );
				$this->sendMessage(array(
					'title' => $title,
					'msg' => $msg,
					'networks' => $networks,
					'type' => 'success',
				));
				return;
			}

			$this->sendMessage (array(
				'title' => '',
				'msg' => 'ulogin_login_error',
				'type' => 'error'
			));
			return;
		}

		catch (Exception $e){
			$this->sendMessage (array(
				'title' => 'ulogin_db_error',//"Ошибка при работе с БД.",
				'msg' => "Exception: " . $e->getMessage(),
				'type' => 'error'
			));
			return;
		}
	}


	/**
	 * Отправляет данные как ответ на ajax запрос, если код выполняется в результате вызова callback функции,
	 * либо добавляет сообщение в сессию для вывода в режиме redirect
	 * @param array $params
	 */
	protected function sendMessage ($params = array()) {
		global $config;

		$params = array(
			'type' => isset($params['type']) ? $params['type'] : '',
			'script' => isset($params['script']) ? $params['script'] : '',
			'networks' => isset($params['networks']) ? $params['networks'] : '',
			'title' => $this->language[$params['title']],
			'msg' => !is_array($params['msg'])
				? $this->language[$params['msg']]
				: sprintf($this->language[$params['msg'][0]], $params['msg'][1]),
		);

		$params['msg'] = (!empty($params['title']) ? $params['title'] . '<br/>' : '') . $params['msg'];
		switch ($params['type']) {
			case 'error':
				$params['title'] = $this->language['error'];
				break;
			case 'success': default:
				$params['title'] = $this->language['information'];
				break;
		}

		if ($this->doRedirect) {
			$title = $params['title'];
			$message = $params['msg'];

			if ($config['charset'] == "windows-1251") {
				$title = iconv('UTF-8','windows-1251//IGNORE', $title);
				$message = iconv('UTF-8','windows-1251//IGNORE', $message);
			}

			if (!empty($params['script'])) {
				$token = !empty($params['script']['token']) ? $params['script']['token'] : '';
				$identity = !empty($params['script']['identity']) ? $params['script']['identity'] : '';
				$s = '';

				if  ($token && $identity) {
					$s = "uLogin.mergeAccounts('$token', '$identity');";
				} else if ($token) {
					$s = "uLogin.mergeAccounts('$token');";
				}

				if ($s) {
					$message .= "<script type=\"text/javascript\">$s</script>";
				}
			}

			if (!empty($message)) {
				$_SESSION['ulogin_title'] = $title;
				$_SESSION['ulogin_message'] = $message;
			}

			$backurl = isset($_GET['backurl']) ? preg_replace('/\._php([$|\?])/i', '.php$1', urldecode($_GET['backurl'])) : '';

			header("HTTP/1.0 301 Moved Permanently");
			header("Location: http://".$_SERVER['HTTP_HOST'] . $backurl);
			die("Redirect");

		} else {
			echo json_encode($params);
			exit;
		}
	}


	/**
	 * Добавление в таблицу uLogin
	 * @param $u_user_db - при непустом значении необходимо переписать данные в таблице uLogin
	 */
	protected function newUloginAccount($u_user_db = ''){
		$u_data = $this->u_data;

		if ($u_user_db) {
			// данные о пользователе есть в ulogin_user, но отсутствуют в users => удалить их
			$this->model->deleteUloginAccount(array('id' => $u_user_db['id']));
		}

		$CMSuserId = $this->model->getUserIdByEmail($u_data['email']);

		// $emailExists == true -> есть пользователь с таким email
		$user_id = 0;
		$emailExists = false;
		if ($CMSuserId) {
			$user_id = $CMSuserId; // id юзера с тем же email
			$emailExists = true;
		}

		// $userIsLogged == true -> пользователь онлайн
		$currentUserId = $this->currentUserId;
		$userIsLogged = $this->userIsLogged;

		if (!$emailExists && !$userIsLogged) {
			// отсутствует пользователь с таким email в базе -> регистрация в БД
			$user_id = $this->regUser();
			$this->addUloginAccount($user_id);
		} else {
			if (!$userIsLogged && (int)$user_id > 0) {
				$member_id = $this->model->getUserDataById($user_id);

				if ($member_id['password'] != md5($member_id['password'])) {
					$this->sendMessage(
						array(
							'title' => 'ulogin_enter_password',
							'msg' => 'ulogin_enter_password_text',
							'type' => 'enter_pass',
						)
					);
					return false;
				}
			}

			// существует пользователь с таким email или это текущий пользователь
			if (intval($u_data["verified_email"]) != 1){
				// Верификация аккаунта

				$this->sendMessage(
					array(
						'title' => 'ulogin_verify',//'Подтверждение аккаунта.',
						'msg' => 'ulogin_verify_text',
						'script' => array('token' => $this->token),
					)
				);
				return false;
			}

			$user_id = $userIsLogged ? $currentUserId : $user_id;

			$other_u = $this->model->getUloginUserItem(array(
				'user_id' => $user_id,
			));

			if ($other_u) {
				// Синхронизация аккаунтов
				if(!$userIsLogged && !isset($u_data['merge_account'])){
					$this->sendMessage(
						array(
							'title' => 'ulogin_synch',//'Синхронизация аккаунтов.',
							'msg' => 'ulogin_synch_text',
							'script' => array('token' => $this->token, 'identity' => $other_u['identity']),
						)
					);
					return false;
				}
			}

			$this->addUloginAccount($user_id);
		}

		return $user_id;
	}



	/**
	 * Регистрация пользователя в БД users
	 * @return mixed
	 */
	protected function regUser(){
		$u_data = $this->u_data;

		$login = $this->generateNickname($u_data['first_name'],$u_data['last_name'],$u_data['nickname'],$u_data['bdate']);

		// генерация пароля
		$password = md5($u_data['identity'].time().mt_rand());
		$password = md5($password);
		$this->upass = $password;
		$password = md5($password);

		$email = $u_data['email'];

		//группа пользователей
		$ulogin_config = $this->model->getUloginConfig();
		$group = $ulogin_config['ulogin_group_id'];

		$user_id = $this->model->registerUser($login, $password, $email, $group);

		if (!$user_id) {
			$this->sendMessage (array(
				'title' => 'ulogin_reg_error',
				'msg' => 'ulogin_reg_error_text',
				'type' => 'error'
			));
			return false;
		}

		return $user_id;
	}



	/**
	 * Добавление записи в таблицу ulogin_user
	 * @param $user_id
	 * @return bool
	 */
	protected function addUloginAccount($user_id){
		$res = $this->model->addUloginAccount(array(
			'user_id' => $user_id,
			'identity' => strval($this->u_data['identity']),
			'network' => $this->u_data['network'],
		));

		if (!$res) {
			$this->sendMessage (array(
				'title' => 'ulogin_auth_error',//"Произошла ошибка при авторизации.",
				'msg' => 'ulogin_add_account_error',//"Не удалось записать данные об аккаунте.",
				'type' => 'error'
			));
			return false;
		}

		return true;
	}



	/**
	 * Выполнение входа пользователя в систему по $user_id
	 * @param $u_user
	 * @param int $user_id
	 */
	protected function loginUser($user_id = 0) {
		global $is_logged, $member_id, $config, $dle_login_hash;
		$data = array();

		$u_data = $this->u_data;

		$member_id = $this->model->getUserDataById($user_id);

		// чтерие upass из базы
		if (!empty($member_id['upass']) && empty($this->upass)) {
			$this->upass = $member_id['upass'];
		}

		if (!$this->userIsLogged) {
			if ($member_id['password'] != md5($this->upass)) {
				$this->sendMessage(
					array(
						'title' => 'ulogin_enter_password',
						'msg'   => 'ulogin_enter_password_text',
						'type'  => 'enter_pass',
					)
				);
				return false;
			}

			session_regenerate_id();

			// передача пароля в переменные сессии, куки

			set_cookie("dle_user_id", $member_id['user_id'], 365);
			set_cookie("dle_password", $this->upass, 365);

			$_SESSION['dle_user_id'] = $member_id['user_id'];
			$_SESSION['dle_password'] = $this->upass;
			$_SESSION['member_lasttime'] = $member_id['lastdate'];

			$_TIME = time();
			$_IP = $_SERVER['REMOTE_ADDR'];

			$member_id['lastdate'] = $_TIME;

			$dle_login_hash = md5(SECURE_AUTH_KEY . $_SERVER['HTTP_HOST'] . $member_id['user_id'] . sha1($this->upass) . $config['key'] . date("Ymd"));

			$hash = '';
			if ($config['log_hash']) {
				if (function_exists('openssl_random_pseudo_bytes')) {
					$stronghash = md5(openssl_random_pseudo_bytes(15));
				} else {
					$stronghash = md5(uniqid(mt_rand(), TRUE));
				}

				$salt = sha1(str_shuffle("abcdefghjkmnpqrstuvwxyz0123456789") . $stronghash);

				for ($i = 0; $i < 9; $i ++) {
					$hash .= $salt{mt_rand(0, 39)};
				}
				$hash = md5($hash);

				set_cookie("dle_hash", $hash, 365);
				$_COOKIE['dle_hash'] = $hash;
				$member_id['hash'] = $hash;
			}


			$data['lastdate'] = $_TIME;
			$data['logged_ip'] = $_IP;
			$data['hash'] = $hash;
		} // is logged


		if (empty($member_id['fullname']) && (isset($u_data['last_name']) || isset($u_data['first_name']))) {
			$first_name = isset($u_data['first_name']) ? $u_data['first_name'] : '';
			$last_name = isset($u_data['last_name']) ? $u_data['last_name'] : '';
			$fullname = trim($first_name . ' ' . $last_name);

			$member_id['fullname'] = $fullname;
			$data['fullname'] = $fullname;
		}

		if (empty($member_id['land']) && (isset($u_data['country']) || isset($u_data['city']))) {
			$land = isset($u_data['country']) ? $u_data['country'] : '';
			$land .= isset($u_data['city']) ? (($land ? ', ' : '') . $u_data['city']) : '';

			$member_id['land'] = $land;
			$data['land'] = $land;
		}

		// загрузка аватара
		if (empty($member_id['foto']) && (!empty($u_data['photo_big']) || !empty($u_data['photo']))) {
			$photo_url = (!empty($u_data['photo_big']))
				? $u_data['photo_big']
				: (!empty( $u_data['photo'] ) ? $u_data['photo'] : '');

			$data['foto'] = $this->setAvatar($photo_url, $member_id);
		}

		// сохранение upass в базу
		if (!empty($this->upass) && isset($member_id['upass']) && $member_id['upass'] != $this->upass) {
			$data['upass'] = $this->upass;
		}
		$this->upass = '';

		$result = $this->model->updateUserData($user_id, $data);
		if (!$result && $data) {
			if ($this->userIsLogged) {
				$msg = 'ulogin_edit_error';
			} else {
				$msg = 'ulogin_auth_error'; // "Произошла ошибка при авторизации."
			}

			$this->sendMessage (
				array(
					'title' => '',
					'msg' => $msg,
					'type' => 'error',
				)
			);
			return false;
		}

		$is_logged = TRUE;

		return true;
	}


	/**
	 * Заргузка аватара пользователя
	 * @param $photo_url
	 * @param $member_id
	 * @return string
	 */
	private function setAvatar($photo_url, $member_id) {
		global $config;
		
		$user_group_id = $member_id['user_group'];
		$user_id = $member_id['user_id'];

		if ((int)$user_group_id <= 0) {
			return '';
		}

		$user_group = $this->model->getUserGroupById($user_group_id);

		if( $photo_url && intval($user_group['max_foto']) > 0) {

			$res = $this->getResponse($photo_url, false);
			$res = (!$res && in_array('curl', get_loaded_extensions())) ? file_get_contents($photo_url) : $res;

			if(!$res) {
				return '';
			}

			$savepath = ROOT_DIR . "/uploads/fotos/";

			if (!is_dir($savepath) || !is_writable($savepath)) {
				return '';
			}

			$tmp_name = "foto_" . $user_id . '.tmp';

			$handle = fopen($savepath . $tmp_name, "w");
			$fileSize = fwrite($handle, $res);
			fclose($handle);

			if(!$fileSize)
			{
				@unlink($savepath . $tmp_name);
				return '';
			}


			list($width, $height, $image_type) = getimagesize( $savepath . $tmp_name );
			if ($width == 0 || $height == 0) {
				return '';
			}

			switch ( $image_type ) {
				case IMAGETYPE_GIF:
					$file_ext = 'gif';
					break;
				case IMAGETYPE_JPEG:
					$file_ext = 'jpg';
					break;
				case IMAGETYPE_PNG:
					$file_ext = 'png';
					break;
				default:
					$file_ext = 'jpg';
					break;
			}

			$photo_name = str_replace('.tmp', '.' . $file_ext, $tmp_name);


			include_once ENGINE_DIR . '/classes/thumb.class.php';
			@chmod( $savepath . $tmp_name, 0666 );

			$thumb = new thumbnail( $savepath . $tmp_name);

			$min_size = min($width, $height, $user_group['max_foto']);

			if( $thumb->size_auto($min_size . "x" . $min_size)) {
				$thumb->jpeg_quality( $config['jpeg_quality'] );
				$thumb->save( $savepath . $photo_name );
			} else {
				if($file_ext == "jpg" || $file_ext == "jpeg") {
					$thumb->jpeg_quality( $config['jpeg_quality'] );
					$thumb->save( $savepath . $photo_name );
				} else {
					@rename( $savepath . $tmp_name, $savepath . $photo_name );
				}
			}
			@unlink( $savepath . $tmp_name );
			@chmod( $savepath . $photo_name, 0666 );

			return $photo_name;
		}

		return '';
	}


	/**
	 * Проверка текущего пользователя
	 * @param $user_id
	 */
	protected function checkCurrentUserId($user_id){
		$currentUserId = $this->currentUserId;
		if($this->userIsLogged) {
			if ($currentUserId == $user_id) {
				return true;
			}
			$this->sendMessage (
				array(
					'title' => '',
					'msg' => 'ulogin_account_not_available',
					'type' => 'error',
				)
			);
			return false;
		}
		return true;
	}



	/**
	 * Обработка ответа сервера авторизации
	 */
	protected function uloginParseRequest(){
		$this->token = isset($_POST['token']) ? $_POST['token'] : '';

		if (!$this->token) {
			$this->sendMessage (array(
				'title' => 'ulogin_auth_error', //"Произошла ошибка при авторизации.",
				'msg' => 'ulogin_no_token_error', //"Не был получен токен uLogin.",
				'type' => 'error'
			));
			return false;
		}

		$s = $this->getUserFromToken();

		if (!$s){
			$this->sendMessage (array(
				'title' => 'ulogin_auth_error', //"Произошла ошибка при авторизации.",
				'msg' => 'ulogin_no_user_data_error', //"Не удалось получить данные о пользователе с помощью токена.",
				'type' => 'error'
			));
			return false;
		}

		$this->u_data = json_decode($s, true);

		if (!$this->checkTokenError()){
			return false;
		}

		return $this->u_data;
	}


	/**
	 * "Обменивает" токен на пользовательские данные
	 */
	protected function getUserFromToken() {
		global $config;
		$response = false;
		if ($this->token){
			$host = $_SERVER['SERVER_NAME'];
			$data = array(
				'cms' => 'dle',
				'version' => $config['version_id'],
			);
			$request = 'http://ulogin.ru/token.php?token=' . $this->token . '&host=' . $host . '&data='.base64_encode(json_encode($data));
			$response = $this->getResponse($request);
		}
		return $response;
	}

	/**
	 * Получение данных с помощью curl или file_get_contents
	 * @param string $url
	 * @return bool|mixed|string
	 */
	private function getResponse($url="", $do_abbort=true) {
		$result = false;

		if (in_array('curl', get_loaded_extensions())) {
			$request = curl_init($url);
			curl_setopt($request, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($request, CURLOPT_BINARYTRANSFER, 1);
			$result = curl_exec($request);
		}elseif (function_exists('file_get_contents') && ini_get('allow_url_fopen')){
			$result = file_get_contents($url);
		}

		if (!$result) {
			if ($do_abbort) {
				$this->sendMessage(array(
					'title' => 'ulogin_read_response_error',
					'msg' => 'ulogin_read_response_error_text',
					'type' => 'error'
				));
			}
			return false;
		}

		return $result;
	}


	/**
	 * Проверка пользовательских данных, полученных по токену
	 */
	protected function checkTokenError(){
		if (!is_array($this->u_data)){
			$this->sendMessage (array(
				'title' => 'ulogin_auth_error', //"Произошла ошибка при авторизации.",
				'msg' => 'ulogin_wrong_user_data_error', //"Данные о пользователе содержат неверный формат.",
				'type' => 'error'
			));
			return false;
		}

		if (isset($this->u_data['error'])){
			$strpos = strpos($this->u_data['error'],'host is not');
			if ($strpos !== FALSE){
				$this->sendMessage (array(
					'title' => 'ulogin_auth_error', //"Произошла ошибка при авторизации.",
					'msg' => array('ulogin_host_address_error', substr($this->u_data['error'],intval($strpos)+12)),//"<i>ERROR</i>: адрес хоста не совпадает с оригиналом " . substr($this->u_data['error'],intval($strpos)+12),
					'type' => 'error'
				));
				return false;
			}
			switch ($this->u_data['error']){
				case 'token expired':
					$this->sendMessage (array(
						'title' => 'ulogin_auth_error', //"Произошла ошибка при авторизации.",
						'msg' => 'ulogin_token_expired_error', //"<i>ERROR</i>: время жизни токена истекло",
						'type' => 'error'
					));
					break;
				case 'invalid token':
					$this->sendMessage (array(
						'title' => 'ulogin_auth_error', //"Произошла ошибка при авторизации.",
						'msg' => 'ulogin_invalid_token_error', //"<i>ERROR</i>: неверный токен",
						'type' => 'error'
					));
					break;
				default:
					$this->sendMessage (array(
						'title' => 'ulogin_auth_error', //"Произошла ошибка при авторизации.",
						'msg' => "<i>ERROR</i>: " . $this->u_data['error'],
						'type' => 'error'
					));
			}
			return false;
		}
		if (!isset($this->u_data['identity'])){
			$this->sendMessage (array(
				'title' => 'ulogin_auth_error', //"Произошла ошибка при авторизации.",
				'msg' => array('ulogin_no_variable_error', 'identity'), //"В возвращаемых данных отсутствует переменная <b>identity</b>.",
				'type' => 'error'
			));
			return false;
		}
		if (!isset($this->u_data['email'])){
			$this->sendMessage (array(
				'title' => 'ulogin_auth_error', //"Произошла ошибка при авторизации.",
				'msg' => array('ulogin_no_variable_error', 'email'), //"В возвращаемых данных отсутствует переменная <b>email</b>",
				'type' => 'error'
			));
			return false;
		}
		return true;
	}


	/**
	 * Гнерация логина пользователя
	 * в случае успешного выполнения возвращает уникальный логин пользователя
	 * @param $first_name
	 * @param string $last_name
	 * @param string $nickname
	 * @param string $bdate
	 * @param array $delimiters
	 * @return string
	 */
	protected function generateNickname($first_name, $last_name="", $nickname="", $bdate="", $delimiters=array('.', '_')) {
		$delim = array_shift($delimiters);

		$first_name = $this->translitIt($first_name);
		$first_name_s = substr($first_name, 0, 1);

		$variants = array();
		if (!empty($nickname))
			$variants[] = $nickname;
		$variants[] = $first_name;
		if (!empty($last_name)) {
			$last_name = $this->translitIt($last_name);
			$variants[] = $first_name.$delim.$last_name;
			$variants[] = $last_name.$delim.$first_name;
			$variants[] = $first_name_s.$delim.$last_name;
			$variants[] = $first_name_s.$last_name;
			$variants[] = $last_name.$delim.$first_name_s;
			$variants[] = $last_name.$first_name_s;
		}
		if (!empty($bdate)) {
			$date = explode('.', $bdate);
			$variants[] = $first_name.$date[2];
			$variants[] = $first_name.$delim.$date[2];
			$variants[] = $first_name.$date[0].$date[1];
			$variants[] = $first_name.$delim.$date[0].$date[1];
			$variants[] = $first_name.$delim.$last_name.$date[2];
			$variants[] = $first_name.$delim.$last_name.$delim.$date[2];
			$variants[] = $first_name.$delim.$last_name.$date[0].$date[1];
			$variants[] = $first_name.$delim.$last_name.$delim.$date[0].$date[1];
			$variants[] = $last_name.$delim.$first_name.$date[2];
			$variants[] = $last_name.$delim.$first_name.$delim.$date[2];
			$variants[] = $last_name.$delim.$first_name.$date[0].$date[1];
			$variants[] = $last_name.$delim.$first_name.$delim.$date[0].$date[1];
			$variants[] = $first_name_s.$delim.$last_name.$date[2];
			$variants[] = $first_name_s.$delim.$last_name.$delim.$date[2];
			$variants[] = $first_name_s.$delim.$last_name.$date[0].$date[1];
			$variants[] = $first_name_s.$delim.$last_name.$delim.$date[0].$date[1];
			$variants[] = $last_name.$delim.$first_name_s.$date[2];
			$variants[] = $last_name.$delim.$first_name_s.$delim.$date[2];
			$variants[] = $last_name.$delim.$first_name_s.$date[0].$date[1];
			$variants[] = $last_name.$delim.$first_name_s.$delim.$date[0].$date[1];
			$variants[] = $first_name_s.$last_name.$date[2];
			$variants[] = $first_name_s.$last_name.$delim.$date[2];
			$variants[] = $first_name_s.$last_name.$date[0].$date[1];
			$variants[] = $first_name_s.$last_name.$delim.$date[0].$date[1];
			$variants[] = $last_name.$first_name_s.$date[2];
			$variants[] = $last_name.$first_name_s.$delim.$date[2];
			$variants[] = $last_name.$first_name_s.$date[0].$date[1];
			$variants[] = $last_name.$first_name_s.$delim.$date[0].$date[1];
		}
		$i=0;

		$exist = true;
		while (true) {
			if ($exist = $this->userExist($variants[$i])) {
				foreach ($delimiters as $del) {
					$replaced = str_replace($delim, $del, $variants[$i]);
					if($replaced !== $variants[$i]){
						$variants[$i] = $replaced;
						if (!$exist = $this->userExist($variants[$i]))
							break;
					}
				}
			}
			if ($i >= count($variants)-1 || !$exist)
				break;
			$i++;
		}

		if ($exist) {
			while ($exist) {
				$nickname = $first_name.mt_rand(1, 100000);
				$exist = $this->userExist($nickname);
			}
			return $nickname;
		} else
			return $variants[$i];
	}


	/**
	 * Проверка существует ли пользователь с заданным логином
	 */
	protected function userExist($login){
		if (!$this->model->checkUserName(strtolower($login))){
			return false;
		}
		return true;
	}


	/**
	 * Транслит
	 */
	protected function translitIt($str) {
		$tr = array(
			"А"=>"a","Б"=>"b","В"=>"v","Г"=>"g",
			"Д"=>"d","Е"=>"e","Ж"=>"j","З"=>"z","И"=>"i",
			"Й"=>"y","К"=>"k","Л"=>"l","М"=>"m","Н"=>"n",
			"О"=>"o","П"=>"p","Р"=>"r","С"=>"s","Т"=>"t",
			"У"=>"u","Ф"=>"f","Х"=>"h","Ц"=>"ts","Ч"=>"ch",
			"Ш"=>"sh","Щ"=>"sch","Ъ"=>"","Ы"=>"yi","Ь"=>"",
			"Э"=>"e","Ю"=>"yu","Я"=>"ya","а"=>"a","б"=>"b",
			"в"=>"v","г"=>"g","д"=>"d","е"=>"e","ж"=>"j",
			"з"=>"z","и"=>"i","й"=>"y","к"=>"k","л"=>"l",
			"м"=>"m","н"=>"n","о"=>"o","п"=>"p","р"=>"r",
			"с"=>"s","т"=>"t","у"=>"u","ф"=>"f","х"=>"h",
			"ц"=>"ts","ч"=>"ch","ш"=>"sh","щ"=>"sch","ъ"=>"y",
			"ы"=>"y","ь"=>"","э"=>"e","ю"=>"yu","я"=>"ya"
		);
		if (preg_match('/[^A-Za-z0-9\_\-]/', $str)) {
			$str = strtr($str,$tr);
			$str = preg_replace('/[^A-Za-z0-9\_\-\.]/', '', $str);
		}
		return $str;
	}


	/**
	 * Удаление привязки к аккаунту соцсети в таблице ulogin_user для текущего пользователя
	 */
	protected function deleteAccount() {
		$isAjaxRequest = isset($_POST['isAjax']) ? true : false;

		if (!$isAjaxRequest) {
			global $lang;
			@header( "HTTP/1.0 404 Not Found" );
			msgbox( $lang['all_err_1'], $lang['news_err_12'] );
		}

		if(!$this->userIsLogged) {exit;}

		$user_id = $this->currentUserId;

		$network = isset($_POST['network']) ? $_POST['network'] : '';

		if ($user_id > 0 && $network != '') {
			try {
				$this->model->deleteUloginAccount(array('user_id' => $user_id, 'network' => $network));
				$this->sendMessage (array(
					'title' => '',
					'msg' => array('ulogin_delete_account_success', $network), //"Удаление аккаунта $network успешно выполнено",
					'type' => 'success'
				));
				exit;

			} catch (Exception $e) {
				$this->sendMessage (array(
					'title' => 'ulogin_delete_account_error', //"Ошибка при удалении аккаунта",
					'msg' => "Exception: " . $e->getMessage(),
					'type' => 'error'
				));
				exit;
			}
		}
		exit;
	}

}